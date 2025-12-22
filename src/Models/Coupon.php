<?php

namespace BalikPro\Models;

use BalikPro\Utils\Database;

class Coupon extends BaseModel
{
    protected $table = 'coupons';

    public function createCoupon(array $couponData): ?int
    {
        try {
            // Generate QR hash
            $qrContent = $couponData['order_id'] . '-' . $couponData['service_id'] . '-' . time();
            $qrHash = hash('sha256', $qrContent);
            
            // Generate readable code
            $code = 'BP' . date('y') . '-' . strtoupper(substr(uniqid(), -8));
            
            // Set validity period (1 year from now)
            $validUntil = date('Y-m-d H:i:s', strtotime('+1 year'));

            $data = array_merge($couponData, [
                'qr_hash' => $qrHash,
                'code' => $code,
                'valid_until' => $validUntil,
                'status' => 'active'
            ]);

            return $this->create($data);
        } catch (\Exception $e) {
            $this->logger->error("Error creating coupon: " . $e->getMessage());
            return null;
        }
    }

    public function findByQrHash(string $qrHash): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM coupons WHERE qr_hash = ?");
            $stmt->execute([$qrHash]);
            
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findByQrHash: " . $e->getMessage());
            return null;
        }
    }

    public function getCouponWithDetails(int $couponId, string $qrHash): ?array
    {
        try {
            $sql = "SELECT 
                        c.*,
                        s.title as service_title,
                        s.description as service_description,
                        s.contact_info as service_contact_info,
                        p.name as partner_name,
                        p.logo_path as partner_logo,
                        o.customer_name,
                        o.order_number
                    FROM coupons c
                    JOIN services s ON c.service_id = s.id
                    JOIN partners p ON c.partner_id = p.id
                    JOIN orders o ON c.order_id = o.id
                    WHERE c.id = ? AND c.qr_hash = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$couponId, $qrHash]);
            
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in getCouponWithDetails: " . $e->getMessage());
            return null;
        }
    }

    public function redeemCoupon(int $couponId, int $partnerId, string $pin, string $qrHash): array
    {
        try {
            Database::getInstance()->beginTransaction();

            // Get coupon details
            $coupon = $this->getCouponWithDetails($couponId, $qrHash);
            if (!$coupon) {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Kupón nebol nájdený'];
            }

            // Check if already redeemed
            if ($coupon['status'] === 'redeemed') {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Kupón už bol použitý'];
            }

            // Check if expired
            if (strtotime($coupon['valid_until']) < time()) {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Kupón už vypršal'];
            }

            // Verify partner PIN
            $partnerModel = new Partner();
            $partner = $partnerModel->findByPin($partnerId, $pin);
            if (!$partner) {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Nesprávny PIN partnera'];
            }

            // Check if partner owns this coupon
            if ($coupon['partner_id'] != $partnerId) {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Kupón nepatrí tomuto partnerovi'];
            }

            // Redeem coupon
            $success = $this->update($couponId, [
                'status' => 'redeemed',
                'redeemed_at' => date('Y-m-d H:i:s'),
                'redeemed_by' => $partner['name']
            ]);

            if (!$success) {
                Database::getInstance()->rollback();
                return ['success' => false, 'message' => 'Chyba pri aktivácii kupónu'];
            }

            // Log the redemption
            $this->logRedemption($couponId, $partnerId, true, 'Successfully redeemed');

            Database::getInstance()->commit();
            return ['success' => true, 'message' => 'Kupón bol úspešne aktivovaný'];

        } catch (\Exception $e) {
            Database::getInstance()->rollback();
            $this->logger->error("Error in redeemCoupon: " . $e->getMessage());
            return ['success' => false, 'message' => 'Systémová chyba'];
        }
    }

    public function getPartnerCoupons(int $partnerId, array $filters = []): array
    {
        try {
            $conditions = ['partner_id' => $partnerId];
            
            if (isset($filters['status'])) {
                $conditions['status'] = $filters['status'];
            }

            $whereClause = [];
            $params = [];

            foreach ($conditions as $field => $value) {
                $whereClause[] = "c.$field = ?";
                $params[] = $value;
            }

            // Add month filter if specified
            if (isset($filters['month'])) {
                $whereClause[] = "DATE_FORMAT(c.created_at, '%b %Y') = ?";
                $params[] = $filters['month'];
            }

            $whereClauseStr = implode(' AND ', $whereClause);

            $limit = $filters['per_page'] ?? 50;
            $offset = (($filters['page'] ?? 1) - 1) * $limit;

            $sql = "SELECT 
                        c.*,
                        s.title as service_title,
                        s.type as service_type,
                        o.customer_name,
                        o.order_number
                    FROM coupons c
                    JOIN services s ON c.service_id = s.id
                    JOIN orders o ON c.order_id = o.id
                    WHERE $whereClauseStr
                    ORDER BY c.created_at DESC
                    LIMIT $limit OFFSET $offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $coupons = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) FROM coupons c WHERE " . str_replace('c.created_at', 'c.created_at', $whereClauseStr);
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, count($conditions) + (isset($filters['month']) ? 1 : 0)));
            $total = $countStmt->fetchColumn();

            return [
                'coupons' => $coupons,
                'total' => $total,
                'page' => $filters['page'] ?? 1,
                'per_page' => $limit
            ];

        } catch (\PDOException $e) {
            $this->logger->error("Error in getPartnerCoupons: " . $e->getMessage());
            return ['coupons' => [], 'total' => 0, 'page' => 1, 'per_page' => 50];
        }
    }

    private function logRedemption(int $couponId, int $partnerId, bool $success, string $note = ''): void
    {
        try {
            $sql = "INSERT INTO redemption_logs (coupon_id, partner_id, success, note, ip, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $couponId,
                $partnerId,
                $success ? 1 : 0,
                $note,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (\PDOException $e) {
            $this->logger->error("Error logging redemption: " . $e->getMessage());
        }
    }
}
