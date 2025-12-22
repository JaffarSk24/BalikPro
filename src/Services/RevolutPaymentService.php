<?php

namespace BalikPro\Services;

use BalikPro\Utils\Logger;

class RevolutPaymentService
{
    private $config;
    private $logger;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/revolut.php';
        $this->logger = new Logger('revolut.log');
    }

    public function createPayment(float $amount, string $currency, int $orderId): array
    {
        try {
            if ($this->config['is_mock']) {
                return $this->createMockPayment($amount, $currency, $orderId);
            }

            // Real Revolut API integration would go here
            $payload = [
                'amount' => [
                    'value' => $amount * 100, // Convert to cents
                    'currency' => $currency
                ],
                'reference' => "ORDER-{$orderId}",
                'description' => 'Balík PRO - Nákup služieb',
                'capture_mode' => 'AUTOMATIC',
                'merchant_order_ext_ref' => (string)$orderId,
                'success_url' => $this->config['success_url'] . "?order_id={$orderId}",
                'failure_url' => $this->config['failure_url'] . "?order_id={$orderId}"
            ];

            // Make actual API call to Revolut
            $response = $this->makeApiCall('/orders', $payload);
            
            $this->logger->info("Payment created", ['order_id' => $orderId, 'response' => $response]);

            return [
                'success' => true,
                'payment_id' => $response['id'] ?? 'mock_' . uniqid(),
                'checkout_url' => $response['checkout_url'] ?? '/mock-payment/' . $orderId,
                'status' => $response['state'] ?? 'PENDING'
            ];

        } catch (\Exception $e) {
            $this->logger->error("Payment creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createMockPayment(float $amount, string $currency, int $orderId): array
    {
        $paymentId = 'mock_' . uniqid();
        
        $this->logger->info("Mock payment created", [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $orderId
        ]);

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'checkout_url' => "/mock-payment/{$orderId}",
            'status' => 'PENDING'
        ];
    }

    public function handleWebhook(array $payload): array
    {
        try {
            $this->logger->info("Webhook received", ['payload' => $payload]);

            if ($this->config['is_mock']) {
                return $this->handleMockWebhook($payload);
            }

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($payload)) {
                throw new \Exception('Invalid webhook signature');
            }

            $paymentId = $payload['id'] ?? '';
            $status = strtolower($payload['state'] ?? '');
            $orderId = $payload['merchant_order_ext_ref'] ?? '';

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'status' => $this->mapRevolutStatusToInternal($status),
                'order_id' => $orderId,
                'raw_payload' => $payload
            ];

        } catch (\Exception $e) {
            $this->logger->error("Webhook processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function handleMockWebhook(array $payload): array
    {
        $paymentId = $payload['payment_id'] ?? 'mock_' . uniqid();
        $status = $payload['status'] ?? 'completed';
        $orderId = $payload['order_id'] ?? '';

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $this->mapRevolutStatusToInternal($status),
            'order_id' => $orderId,
            'raw_payload' => $payload
        ];
    }

    private function verifyWebhookSignature(array $payload): bool
    {
        // Implement actual signature verification for production
        // This is a simplified mock implementation
        return true;
    }

    private function mapRevolutStatusToInternal(string $revolutStatus): string
    {
        $statusMap = [
            'completed' => 'paid',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$revolutStatus] ?? 'pending';
    }

    private function makeApiCall(string $endpoint, array $payload): array
    {
        // Real API implementation would use cURL or Guzzle
        // This is mock implementation
        throw new \Exception('Real API not implemented - use mock mode');
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            if ($this->config['is_mock']) {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'payment_id' => $paymentId
                ];
            }

            // Real API call to get payment status
            $response = $this->makeApiCall("/orders/{$paymentId}", []);
            
            return [
                'success' => true,
                'status' => $this->mapRevolutStatusToInternal($response['state'] ?? 'pending'),
                'payment_id' => $paymentId,
                'raw_response' => $response
            ];

        } catch (\Exception $e) {
            $this->logger->error("Get payment status failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
