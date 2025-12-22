<?php

namespace BalikPro\Models;

class Order extends BaseModel
{
    protected $table = 'orders';

    public function createOrder(array $orderData): ?int
    {
        try {
            // Generate unique order number
            $orderNumber = 'BP' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            $data = array_merge($orderData, [
                'order_number' => $orderNumber,
                'payment_status' => 'pending',
                'currency' => 'EUR'
            ]);

            return $this->create($data);
        } catch (\Exception $e) {
            $this->logger->error("Error creating order: " . $e->getMessage());
            return null;
        }
    }

    public function updatePaymentStatus(int $orderId, string $status, array $providerPayload = []): bool
    {
        try {
            $updateData = [
                'payment_status' => $status
            ];

            if (!empty($providerPayload)) {
                $updateData['payment_provider_payload'] = json_encode($providerPayload);
            }

            return $this->update($orderId, $updateData);
        } catch (\Exception $e) {
            $this->logger->error("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderWithBundle(int $orderId): ?array
    {
        try {
            $sql = "SELECT 
                        o.*, 
                        b.name as bundle_name,
                        b.description as bundle_description,
                        ms.title as main_service_title,
                        ms.price as main_service_price
                    FROM orders o
                    JOIN bundles b ON o.bundle_id = b.id
                    JOIN services ms ON b.main_service_id = ms.id
                    WHERE o.id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId]);
            
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in getOrderWithBundle: " . $e->getMessage());
            return null;
        }
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findByOrderNumber: " . $e->getMessage());
            return null;
        }
    }

    public function getPaidOrders(int $limit = 100): array
    {
        return $this->findAll(['payment_status' => 'paid'], $limit);
    }
}
