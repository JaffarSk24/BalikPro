<?php

namespace BalikPro\Controllers;

use BalikPro\Models\Bundle;
use BalikPro\Models\Order;
use BalikPro\Models\Customer;
use BalikPro\Services\RevolutPaymentService;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

class CheckoutController
{
    private $bundleModel;
    private $orderModel;
    private $customerModel;
    private $paymentService;
    private $logger;

    public function __construct()
    {
        $this->bundleModel = new Bundle();
        $this->orderModel = new Order();
        $this->customerModel = new Customer();
        $this->paymentService = new RevolutPaymentService();
        $this->logger = new Logger('checkout.log');
    }

    public function createCheckout(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                Response::error('Nevalidné dáta', 400);
                return;
            }

            $requiredFields = ['bundles', 'customer_name', 'customer_email', 'lang'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
                    Response::error("Povinné pole chýba: {$field}", 400);
                    return;
                }
            }

            if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Nevalidný email', 400);
                return;
            }
            if (!is_array($input['bundles']) || empty($input['bundles'])) {
                Response::error('Bundles musia byť neprázdne pole', 400);
                return;
            }
            if (!in_array($input['lang'], ['sk', 'ru', 'uk'], true)) {
                Response::error('Nevalidný jazyk (sk/ru/uk)', 400);
                return;
            }

            $totalAmount = 0;
            foreach ($input['bundles'] as $bundleItem) {
                if (empty($bundleItem['main_service_id'])) {
                    Response::error('Každý bundle musí mať main_service_id', 400);
                    return;
                }
                $service = $this->bundleModel->getServiceById($bundleItem['main_service_id']);
                if (!$service) {
                    Response::error("Služba {$bundleItem['main_service_id']} neexistuje", 404);
                    return;
                }
                $totalAmount += (float)$service['price'];
            }

            $customerId = $this->findOrCreateCustomer($input);
            if (!$customerId) {
                Response::error('Chyba pri vytváraní zákazníka', 500);
                return;
            }

            $orderData = [
                'customer_id' => $customerId,
                'customer_name' => $input['customer_name'],
                'customer_email' => $input['customer_email'],
                'customer_phone' => $input['customer_phone'] ?? null,
                'bundle_id' => null,
                'total_amount' => $totalAmount,
                'payment_provider' => 'revolut',
                'cart' => json_encode($input['bundles'], JSON_UNESCAPED_UNICODE),
                'lang' => $input['lang']
            ];

            $orderId = $this->orderModel->createOrder($orderData);
            if (!$orderId) {
                Response::error('Chyba pri vytváraní objednávky', 500);
                return;
            }

            // Создаём «платёж»
            $paymentResult = $this->paymentService->createPayment(
                $totalAmount,
                'EUR',
                $orderId
            );

            if (empty($paymentResult['success'])) {
                $err = $paymentResult['error'] ?? 'Unknown payment error';
                Response::error('Chyba pri vytváraní platby: ' . $err, 500);
                return;
            }

            // Фолбэк: гарантируем, что в checkout_url есть order_id для mock-платёжки
            $checkoutUrl = $paymentResult['checkout_url'] ?? '';
            if (!$checkoutUrl || strpos($checkoutUrl, 'order_id=') === false) {
                $checkoutUrl = '/mock-payment/index.php?order_id=' . urlencode((string)$orderId);
            }

            // Логгируем платёж
            $this->logPayment($orderId, $paymentResult);

            $this->logger->info("Checkout session created", [
                'order_id' => $orderId,
                'payment_id' => $paymentResult['payment_id'] ?? null,
                'customer_email' => $input['customer_email'],
                'lang' => $input['lang']
            ]);

            Response::success([
                'order_id' => $orderId,
                'checkout_url' => $checkoutUrl,
                'payment_id' => $paymentResult['payment_id'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Checkout error: " . $e->getMessage());
            Response::error('Chyba pri spracovaní objednávky', 500);
        }
    }

    public function createOfflineOrder(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                Response::error('Invalid JSON', 400);
                return;
            }

            $required = ['bundle_id', 'customer_name', 'customer_email'];
            foreach ($required as $f) {
                if (empty($input[$f])) {
                    Response::error("Required field missing: {$f}", 400);
                    return;
                }
            }

            if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Invalid email format', 400);
                return;
            }

            // Проверка bundle
            $bundle = $this->bundleModel->getBundleWithServices($input['bundle_id']);
            if (!$bundle) {
                Response::error('Bundle not found', 404);
                return;
            }

            // Создать/обновить клиента
            $customerId = $this->findOrCreateCustomer($input);
            if (!$customerId) {
                Response::error('Customer creation error', 500);
                return;
            }

            // Создать заказ без Revolut
            $orderData = [
                'customer_id'     => $customerId,
                'customer_name'   => $input['customer_name'],
                'customer_email'  => $input['customer_email'],
                'customer_phone'  => $input['customer_phone'] ?? null,
                'bundle_id'       => $input['bundle_id'],
                'total_amount'    => $bundle['main_service_price'],
                'payment_provider'=> null,
                'payment_status'  => 'pending'
            ];
            $orderId = $this->orderModel->createOrder($orderData);

            if (!$orderId) {
                Response::error('Order creation failed', 500);
                return;
            }

            $this->logger->info("Offline order created", ['order_id' => $orderId]);

            Response::success([
                'ok'       => true,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Offline checkout error: " . $e->getMessage());
            Response::error('Internal error', 500);
        }
    }

    private function findOrCreateCustomer(array $input): ?int
    {
        try {
            // Try to find existing customer by email
            $existingCustomer = $this->customerModel->findAll(['email' => $input['customer_email']]);
            
            if (!empty($existingCustomer)) {
                $customer = $existingCustomer[0];
                
                // Update customer info if needed
                $updateData = [];
                if ($customer['name'] !== $input['customer_name']) {
                    $updateData['name'] = $input['customer_name'];
                }
                if (isset($input['customer_phone']) && $customer['phone'] !== $input['customer_phone']) {
                    $updateData['phone'] = $input['customer_phone'];
                }
                
                if (!empty($updateData)) {
                    $this->customerModel->update($customer['id'], $updateData);
                }
                
                return $customer['id'];
            }

            // Create new customer
            return $this->customerModel->create([
                'name' => $input['customer_name'],
                'email' => $input['customer_email'],
                'phone' => $input['customer_phone'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Customer creation error: " . $e->getMessage());
            return null;
        }
    }

    private function logPayment(int $orderId, array $paymentResult): void
    {
        try {
            $pdo = \BalikPro\Utils\Database::getInstance()->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO payments (order_id, provider, provider_payment_id, amount, currency, status, raw_payload) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $order = $this->orderModel->findById($orderId);
            
            $stmt->execute([
                $orderId,
                'revolut',
                $paymentResult['payment_id'],
                $order['total_amount'],
                'EUR',
                'created',
                json_encode($paymentResult)
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Payment logging error: " . $e->getMessage());
        }
    }
}