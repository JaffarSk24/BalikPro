<?php

namespace BalikPro\Controllers;

use BalikPro\Models\Order;
use BalikPro\Services\RevolutPaymentService;
use BalikPro\Services\CouponGeneratorService;
use BalikPro\Services\EmailService;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;
use BalikPro\Utils\Database;
use PDO;

class WebhookController
{
    private $orderModel;
    private $paymentService;
    private $couponService;
    private $emailService;
    private $logger;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->paymentService = new RevolutPaymentService();
        $this->couponService = new CouponGeneratorService();
        $this->emailService = new EmailService();
        $this->logger = new Logger('revolut_webhook.log');
    }

    public function handleRevolutWebhook(): void
    {
        try {
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true);
            
            if (!$payload) {
                $this->logger->warning("Invalid JSON payload received");
                Response::error('Invalid payload', 400);
                return;
            }

            $this->logger->info("Webhook received", ['payload' => $payload]);

            // Проверяем формат payload: либо мок (order_id + status), либо реальный Revolut
            $isMockPayload = isset($payload['order_id']) && isset($payload['status']);

            if ($isMockPayload) {
                // Мок-режим для тестирования без реальной платёжки
                $orderId = (int)$payload['order_id'];
                $status = strtolower(trim($payload['status'])); // paid / failed
                $paymentId = $payload['payment_id'] ?? 'mock_' . uniqid();
                $rawPayload = $payload;

                if (!in_array($status, ['paid', 'failed'], true)) {
                    $this->logger->warning("Invalid status in mock payload", ['status' => $status]);
                    Response::error('Invalid status', 400);
                    return;
                }

                $this->logger->info("Processing mock webhook", [
                    'order_id' => $orderId,
                    'status' => $status
                ]);

            } else {
                // Реальный Revolut webhook
                $result = $this->paymentService->handleWebhook($payload);
                
                if (!$result['success']) {
                    $this->logger->error("Revolut webhook processing failed", ['error' => $result['error']]);
                    Response::error('Webhook processing failed', 400);
                    return;
                }

                $orderId = $result['order_id'];
                $status = $result['status'];
                $paymentId = $result['payment_id'];
                $rawPayload = $result['raw_payload'];
            }

            // Проверяем существование заказа
            $order = $this->orderModel->findById($orderId);
            if (!$order) {
                $this->logger->error("Order not found", ['order_id' => $orderId]);
                Response::error('Order not found', 404);
                return;
            }

            // Обновляем статус заказа в таблице orders
            $this->updateOrderStatus($orderId, $status);

            // Обновляем запись платежа
            $this->updatePaymentRecord($orderId, $paymentId, $status, $rawPayload);

            // Если платёж успешный — генерируем купоны и отправляем email
            if ($status === 'paid') {
                $this->processPaidOrder($orderId);
            }

            $this->logger->info("Webhook processed successfully", [
                'order_id' => $orderId,
                'status' => $status,
                'payment_id' => $paymentId
            ]);

            Response::success([
                'order_id' => $orderId,
                'status' => $status,
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Webhook error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Response::error('Internal server error', 500);
        }
    }

    private function updateOrderStatus(int $orderId, string $status): void
    {
        try {
            $pdo = Database::getInstance()->getConnection();

            // было: UPDATE orders SET status = ? ...
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $orderId]);

            $this->logger->info("Order payment_status updated", [
                'order_id' => $orderId,
                'status'   => $status
            ]);
        } catch (\PDOException $e) {
            $this->logger->error("Order payment_status update error", [
                'order_id' => $orderId,
                'error'    => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function processPaidOrder(int $orderId): void
    {
        try {
            $this->logger->info("Processing paid order", ['order_id' => $orderId]);

            // Генерируем купоны
            $couponResult = $this->couponService->generateCouponsForOrder($orderId);
            
            if (!$couponResult['success']) {
                throw new \Exception("Coupon generation failed: " . $couponResult['error']);
            }

            // Генерируем PDF (returns absolute path)
            $pdfPath = $this->couponService->generateCouponPDF(
                $couponResult['coupons'], 
                $couponResult['order']
            );

            // Обновляем заказ с путём к PDF
            $this->orderModel->update($orderId, ['pdf_path' => $pdfPath]);

            // Отправляем email с PDF
            $order = $couponResult['order'];
            // $pdfPath is already absolute
            
            $emailSent = $this->emailService->sendCouponEmail(
                $order['customer_email'],
                $order['customer_name'],
                $pdfPath,
                $order
            );

            if (!$emailSent) {
                $this->logger->error("Failed to send coupon email", ['order_id' => $orderId]);
            }

            $this->logger->info("Paid order processed successfully", [
                'order_id' => $orderId,
                'coupon_count' => count($couponResult['coupons']),
                'email_sent' => $emailSent
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Error processing paid order", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function updatePaymentRecord(int $orderId, string $paymentId, string $status, array $payload): void
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Проверяем, существует ли запись платежа
            $stmt = $pdo->prepare("SELECT id FROM payments WHERE order_id = ? AND provider_payment_id = ?");
            $stmt->execute([$orderId, $paymentId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // Обновляем существующую запись
                $stmt = $pdo->prepare("
                    UPDATE payments 
                    SET status = ?, raw_payload = ?, updated_at = NOW()
                    WHERE order_id = ? AND provider_payment_id = ?
                ");
                
                $stmt->execute([
                    $status,
                    json_encode($payload),
                    $orderId,
                    $paymentId
                ]);
            } else {
                // Создаём новую запись (для мок-платежей)
                $stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, provider_payment_id, status, raw_payload, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $orderId,
                    $paymentId,
                    $status,
                    json_encode($payload)
                ]);
            }

            $this->logger->info("Payment record updated", [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'status' => $status
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Payment record update error", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getFullPdfPath(string $filename): string
    {
        $config = require __DIR__ . '/../../config/app.php';
        return $config['pdfs_path'] . $filename;
    }
}