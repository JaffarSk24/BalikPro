<?php

namespace BalikPro\Models;

class Service extends BaseModel
{
    protected $table = 'services';

    public function getServicesByPartner(int $partnerId, bool $activeOnly = true): array
    {
        try {
            $conditions = ['partner_id' => $partnerId];
            if ($activeOnly) {
                $conditions['active'] = 1;
            }

            return $this->findAll($conditions);
        } catch (\Exception $e) {
            $this->logger->error("Error in getServicesByPartner: " . $e->getMessage());
            return [];
        }
    }

    public function getMainServices(bool $activeOnly = true): array
    {
        try {
            $conditions = ['is_main' => 1];
            if ($activeOnly) {
                $conditions['active'] = 1;
            }

            return $this->findAll($conditions);
        } catch (\Exception $e) {
            $this->logger->error("Error in getMainServices: " . $e->getMessage());
            return [];
        }
    }

    public function getBonusServices(bool $activeOnly = true): array
    {
        try {
            $conditions = ['is_bonus' => 1];
            if ($activeOnly) {
                $conditions['active'] = 1;
            }

            return $this->findAll($conditions);
        } catch (\Exception $e) {
            $this->logger->error("Error in getBonusServices: " . $e->getMessage());
            return [];
        }
    }

    public function createService(array $serviceData): ?int
    {
        // Validate service type
        if (empty($serviceData['is_main']) && empty($serviceData['is_bonus'])) {
            $this->logger->error("Service must be either main or bonus");
            return null;
        }

        // Set defaults
        $serviceData['active'] = $serviceData['active'] ?? 1;
        $serviceData['price'] = $serviceData['price'] ?? 0;
        $serviceData['nominal_value'] = $serviceData['nominal_value'] ?? 0;

        return $this->create($serviceData);
    }

    public function toggleServiceStatus(int $serviceId): bool
    {
        try {
            $service = $this->findById($serviceId);
            if (!$service) {
                return false;
            }

            $newStatus = $service['active'] ? 0 : 1;
            return $this->update($serviceId, ['active' => $newStatus]);

        } catch (\Exception $e) {
            $this->logger->error("Error in toggleServiceStatus: " . $e->getMessage());
            return false;
        }
    }

    public function updateServicePricing(int $serviceId, float $price, float $nominalValue = null): bool
    {
        try {
            $updateData = ['price' => $price];
            
            if ($nominalValue !== null) {
                $updateData['nominal_value'] = $nominalValue;
            }

            return $this->update($serviceId, $updateData);

        } catch (\Exception $e) {
            $this->logger->error("Error in updateServicePricing: " . $e->getMessage());
            return false;
        }
    }

    public function getServiceStats(int $serviceId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_coupons,
                        SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_coupons,
                        SUM(CASE WHEN c.status = 'redeemed' THEN 1 ELSE 0 END) as redeemed_coupons,
                        SUM(CASE WHEN c.status = 'expired' THEN 1 ELSE 0 END) as expired_coupons
                    FROM coupons c
                    WHERE c.service_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$serviceId]);
            
            return $stmt->fetch() ?: [];

        } catch (\PDOException $e) {
            $this->logger->error("Error in getServiceStats: " . $e->getMessage());
            return [];
        }
    }
}
