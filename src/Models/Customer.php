<?php

namespace BalikPro\Models;

class Customer extends BaseModel
{
    protected $table = 'customers';

    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findByEmail: " . $e->getMessage());
            return null;
        }
    }

    public function createCustomer(array $customerData): ?int
    {
        return $this->create([
            'name' => $customerData['name'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'] ?? null
        ]);
    }

    public function updateCustomerInfo(int $id, array $data): bool
    {
        $allowedFields = ['name', 'phone'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        return $this->update($id, $updateData);
    }

    public function getCustomerOrders(int $customerId): array
    {
        try {
            $sql = "SELECT 
                        o.*,
                        b.name as bundle_name,
                        COUNT(c.id) as coupon_count
                    FROM orders o
                    JOIN bundles b ON o.bundle_id = b.id
                    LEFT JOIN coupons c ON o.id = c.order_id
                    WHERE o.customer_id = ?
                    GROUP BY o.id
                    ORDER BY o.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Error in getCustomerOrders: " . $e->getMessage());
            return [];
        }
    }

    public function deleteCustomerData(int $customerId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Check if customer has any paid orders
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE customer_id = ? AND payment_status = 'paid'
            ");
            $stmt->execute([$customerId]);
            $paidOrdersCount = $stmt->fetchColumn();

            if ($paidOrdersCount > 0) {
                // Cannot delete customer with paid orders - anonymize instead
                $this->update($customerId, [
                    'name' => 'Anonymizovaný zákazník',
                    'email' => 'anonymized_' . $customerId . '@balikpro.sk',
                    'phone' => null
                ]);
            } else {
                // Safe to delete customer record
                $this->delete($customerId);
            }

            $this->pdo->commit();
            return true;

        } catch (\Exception $e) {
            $this->pdo->rollback();
            $this->logger->error("Error in deleteCustomerData: " . $e->getMessage());
            return false;
        }
    }
}
