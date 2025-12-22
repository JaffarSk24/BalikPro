<?php

namespace BalikPro\Models;

class Partner extends BaseModel
{
    protected $table = 'partners';

    public function findByPin(int $partnerId, string $pin): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM partners WHERE id = ?");
            $stmt->execute([$partnerId]);
            $partner = $stmt->fetch();
            
            if ($partner && password_verify($pin, $partner['pin_hash'])) {
                return $partner;
            }
            
            return null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findByPin: " . $e->getMessage());
            return null;
        }
    }

    public function createPartner(array $data): ?int
    {
        $data['pin_hash'] = password_hash($data['pin'], PASSWORD_DEFAULT);
        unset($data['pin']); // Remove plain PIN from data
        
        return $this->create($data);
    }

    public function updatePin(int $id, string $newPin): bool
    {
        return $this->update($id, [
            'pin_hash' => password_hash($newPin, PASSWORD_DEFAULT)
        ]);
    }

    public function getPartnerServices(int $partnerId, bool $activeOnly = true): array
    {
        try {
            $activeClause = $activeOnly ? 'AND s.active = 1' : '';
            
            $sql = "SELECT s.* FROM services s 
                    WHERE s.partner_id = ? $activeClause 
                    ORDER BY s.is_main DESC, s.title";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$partnerId]);
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Error in getPartnerServices: " . $e->getMessage());
            return [];
        }
    }

    public function getPartnerStats(int $partnerId, string $monthYear = null): array
    {
        try {
            $monthFilter = $monthYear ? "AND DATE_FORMAT(c.created_at, '%b %Y') = ?" : '';
            $params = [$partnerId];
            if ($monthYear) {
                $params[] = $monthYear;
            }

            // Get coupon statistics
            $sql = "SELECT 
                        COUNT(*) as total_issued,
                        SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN c.status = 'redeemed' THEN 1 ELSE 0 END) as redeemed,
                        SUM(CASE WHEN c.status = 'expired' THEN 1 ELSE 0 END) as expired,
                        SUM(CASE WHEN c.status = 'redeemed' AND c.type = 'main' THEN s.price * 0.5 ELSE 0 END) as amount_due
                    FROM coupons c
                    JOIN services s ON c.service_id = s.id
                    WHERE c.partner_id = ? $monthFilter";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch();

            // Get daily redemption chart data for last 30 days
            $chartSql = "SELECT DATE(c.redeemed_at) as date, COUNT(*) as redeemed
                        FROM coupons c
                        WHERE c.partner_id = ? AND c.status = 'redeemed'
                        AND c.redeemed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(c.redeemed_at)
                        ORDER BY date";

            $chartStmt = $this->pdo->prepare($chartSql);
            $chartStmt->execute([$partnerId]);
            $chartData = $chartStmt->fetchAll();

            return [
                'totals' => $stats,
                'chart_data' => $chartData
            ];
        } catch (\PDOException $e) {
            $this->logger->error("Error in getPartnerStats: " . $e->getMessage());
            return ['totals' => [], 'chart_data' => []];
        }
    }
}
