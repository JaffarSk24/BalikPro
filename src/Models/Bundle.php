<?php

namespace BalikPro\Models;

class Bundle extends BaseModel
{
    protected $table = 'bundles';

    public function getActiveWithServices(): array
    {
        try {
            $sql = "SELECT 
                        b.id, b.uuid, b.active,
                        b.name_sk, b.name_ru, b.name_uk,
                        b.description_sk, b.description_ru, b.description_uk,

                        ms.id as main_service_id,
                        ms.title_sk as main_service_title_sk,
                        ms.title_ru as main_service_title_ru,
                        ms.title_uk as main_service_title_uk,
                        ms.description_sk as main_service_description_sk,
                        ms.description_ru as main_service_description_ru,
                        ms.description_uk as main_service_description_uk,
                        ms.price as main_service_price,
                        ms.contact_info as main_contact_info,

                        p.name_sk as partner_name_sk,
                        p.name_ru as partner_name_ru,
                        p.name_uk as partner_name_uk,
                        p.logo_path as partner_logo
                    FROM bundles b
                    JOIN services ms ON b.main_service_id = ms.id
                    JOIN partners p ON ms.partner_id = p.id
                    WHERE b.active = 1 AND ms.active = 1
                    ORDER BY b.id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $bundles = $stmt->fetchAll();

            // Подтягиваем бонусные сервисы и savings
            foreach ($bundles as &$bundle) {
                $bundle['bonus_services'] = $this->getBonusServices($bundle['id']);
                $bundle['total_savings'] = $this->calculateSavings($bundle['id']);
            }

            return $bundles;
        } catch (\PDOException $e) {
            $this->logger->error("Error in getActiveWithServices: " . $e->getMessage());
            return [];
        }
    }

    public function getBundleWithServices(int $bundleId): ?array
    {
        try {
            $sql = "SELECT 
                      b.id, b.uuid, b.active,
                      b.name_sk, b.name_ru, b.name_uk,
                      b.description_sk, b.description_ru, b.description_uk,

                      ms.id as main_service_id,
                      ms.title_sk as main_service_title_sk,
                      ms.title_ru as main_service_title_ru,
                      ms.title_uk as main_service_title_uk,
                      ms.description_sk as main_service_description_sk,
                      ms.description_ru as main_service_description_ru,
                      ms.description_uk as main_service_description_uk,
                      ms.price as main_service_price,
                      ms.contact_info as main_contact_info,

                      p.id as partner_id,
                      p.name_sk as partner_name_sk,
                      p.name_ru as partner_name_ru,
                      p.name_uk as partner_name_uk,
                      p.logo_path as partner_logo
                    FROM bundles b
                    JOIN services ms ON b.main_service_id = ms.id
                    JOIN partners p ON ms.partner_id = p.id
                    WHERE b.id = ? AND b.active = 1 AND ms.active = 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bundleId]);
            $bundle = $stmt->fetch();

            if (!$bundle) {
                return null;
            }

            $bundle['bonus_services'] = $this->getBonusServices($bundleId);
            $bundle['total_savings'] = $this->calculateSavings($bundleId);

            return $bundle;
        } catch (\PDOException $e) {
            $this->logger->error("Error in getBundleWithServices: " . $e->getMessage());
            return null;
        }
    }

    public function getBonusServices(int $bundleId): array
    {
        try {
            $sql = "SELECT 
                        s.id, s.uuid,
                        s.title_sk, s.title_ru, s.title_uk,
                        s.description_sk, s.description_ru, s.description_uk,
                        s.price, 
                        s.nominal_value,
                        s.contact_info,
                        p.name_sk as partner_name_sk,
                        p.name_ru as partner_name_ru,
                        p.name_uk as partner_name_uk,
                        p.logo_path as partner_logo,
                        bi.sort_order
                    FROM bundle_items bi
                    JOIN services s ON bi.service_id = s.id
                    JOIN partners p ON s.partner_id = p.id
                    WHERE bi.bundle_id = ? AND s.active = 1 AND s.is_bonus = 1
                    ORDER BY bi.sort_order, s.title_sk";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bundleId]);
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Error in getBonusServices: " . $e->getMessage());
            return [];
        }
    }

    public function calculateSavings(int $bundleId): float
    {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(s.nominal_value), 0) as bonus_nominal_sum
                    FROM bundles b
                    LEFT JOIN bundle_items bi ON b.id = bi.bundle_id
                    LEFT JOIN services s ON bi.service_id = s.id AND s.active = 1 AND s.is_bonus = 1
                    WHERE b.id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bundleId]);
            $result = $stmt->fetch();

            if (!$result) {
                return 0.0;
            }

            return (float)$result['bonus_nominal_sum'];
        } catch (\PDOException $e) {
            $this->logger->error("Error in calculateSavings: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Получить услугу по ID (для валидации main_service_id в корзине)
     */
    public function getServiceById(int $serviceId): ?array
    {
        try {
            $sql = "SELECT 
                        s.id, s.uuid,
                        s.title_sk, s.title_ru, s.title_uk,
                        s.description_sk, s.description_ru, s.description_uk,
                        s.price,
                        s.nominal_value,
                        s.contact_info,
                        s.is_bonus,
                        p.id as partner_id,
                        p.name_sk as partner_name_sk,
                        p.name_ru as partner_name_ru,
                        p.name_uk as partner_name_uk,
                        p.logo_path as partner_logo
                    FROM services s
                    JOIN partners p ON s.partner_id = p.id
                    WHERE s.id = ? AND s.active = 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();

            return $service ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in getServiceById: " . $e->getMessage());
            return null;
        }
    }
}