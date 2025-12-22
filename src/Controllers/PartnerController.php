<?php

namespace BalikPro\Controllers;

use BalikPro\Models\Partner;
use BalikPro\Models\Coupon;
use BalikPro\Utils\JWT;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

class PartnerController
{
    private $partnerModel;
    private $couponModel;
    private $logger;

    public function __construct()
    {
        $this->partnerModel = new Partner();
        $this->couponModel = new Coupon();
        $this->logger = new Logger('partner.log');
    }

    public function authenticate(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['partner_id']) || empty($input['pin'])) {
                Response::error('Partner ID a PIN sú povinné', 400);
                return;
            }

            $partnerId = (int)$input['partner_id'];
            $pin = $input['pin'];

            // Authenticate partner
            $partner = $this->partnerModel->findByPin($partnerId, $pin);
            
            if (!$partner) {
                $this->logger->warning("Failed login attempt", ['partner_id' => $partnerId]);
                Response::error('Nesprávne prihlasovacie údaje', 401);
                return;
            }

            // Generate JWT token
            $payload = [
                'partner_id' => $partner['id'],
                'partner_uuid' => $partner['uuid'],
                'partner_name' => $partner['name']
            ];

            $token = JWT::encode($payload);

            $this->logger->info("Partner logged in", ['partner_id' => $partnerId]);

            Response::success([
                'token' => $token,
                'expires_in' => 8 * 3600, // 8 hours
                'partner' => [
                    'id' => $partner['id'],
                    'name' => $partner['name'],
                    'email' => $partner['email']
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Authentication error: " . $e->getMessage());
            Response::error('Chyba pri prihlasovaní', 500);
        }
    }

    public function getDashboard(int $partnerId): void
    {
        try {
            if (!$this->validatePartnerAccess($partnerId)) {
                Response::error('Neautorizovaný prístup', 403);
                return;
            }

            $monthYear = $_GET['month'] ?? null;

            // Get partner statistics
            $stats = $this->partnerModel->getPartnerStats($partnerId, $monthYear);

            // Format response
            $response = [
                'totals' => [
                    'issued' => (int)($stats['totals']['total_issued'] ?? 0),
                    'active' => (int)($stats['totals']['active'] ?? 0),
                    'redeemed' => (int)($stats['totals']['redeemed'] ?? 0),
                    'expired' => (int)($stats['totals']['expired'] ?? 0),
                    'amount_due' => (float)($stats['totals']['amount_due'] ?? 0)
                ],
                'charts' => [
                    'by_day' => array_map(function($row) {
                        return [
                            'date' => $row['date'],
                            'redeemed' => (int)$row['redeemed']
                        ];
                    }, $stats['chart_data'])
                ]
            ];

            Response::success($response);

        } catch (\Exception $e) {
            $this->logger->error("Dashboard error: " . $e->getMessage());
            Response::error('Chyba pri načítaní dát', 500);
        }
    }

    public function getCoupons(int $partnerId): void
    {
        try {
            if (!$this->validatePartnerAccess($partnerId)) {
                Response::error('Neautorizovaný prístup', 403);
                return;
            }

            $filters = [
                'status' => $_GET['status'] ?? null,
                'month' => $_GET['month'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'per_page' => min(100, (int)($_GET['per_page'] ?? 50))
            ];

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->couponModel->getPartnerCoupons($partnerId, $filters);

            // Format response
            $response = [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'items' => array_map(function($coupon) {
                    return [
                        'id' => $coupon['id'],
                        'order_id' => $coupon['order_id'],
                        'order_number' => $coupon['order_number'],
                        'service_title' => $coupon['service_title'],
                        'customer_name' => $coupon['customer_name'],
                        'type' => $coupon['type'],
                        'status' => $coupon['status'],
                        'code' => $coupon['code'],
                        'valid_until' => $coupon['valid_until'],
                        'redeemed_at' => $coupon['redeemed_at'],
                        'created_at' => $coupon['created_at']
                    ];
                }, $result['coupons'])
            ];

            Response::success($response);

        } catch (\Exception $e) {
            $this->logger->error("Get coupons error: " . $e->getMessage());
            Response::error('Chyba pri načítaní kupónov', 500);
        }
    }

    public function exportCoupons(int $partnerId): void
    {
        try {
            if (!$this->validatePartnerAccess($partnerId)) {
                Response::error('Neautorizovaný prístup', 403);
                return;
            }

            $format = $_GET['format'] ?? 'csv';
            $month = $_GET['month'] ?? null;

            $filters = ['per_page' => 10000]; // Get all coupons
            if ($month) {
                $filters['month'] = $month;
            }

            $result = $this->couponModel->getPartnerCoupons($partnerId, $filters);

            if ($format === 'csv') {
                $this->exportToCsv($result['coupons'], $partnerId, $month);
            } else {
                Response::error('Nepodporovaný formát', 400);
            }

        } catch (\Exception $e) {
            $this->logger->error("Export error: " . $e->getMessage());
            Response::error('Chyba pri exporte', 500);
        }
    }

    private function validatePartnerAccess(int $partnerId): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $token = substr($authHeader, 7);
        $payload = JWT::decode($token);

        if (!$payload || $payload['partner_id'] != $partnerId) {
            return false;
        }

        return true;
    }

    private function exportToCsv(array $coupons, int $partnerId, ?string $month): void
    {
        $partner = $this->partnerModel->findById($partnerId);
        $filename = "coupons_partner_{$partnerId}";
        if ($month) {
            $filename .= "_" . str_replace(' ', '_', $month);
        }
        $filename .= "_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($output, [
            'ID',
            'Číslo objednávky',
            'Názov služby',
            'Zákazník',
            'Typ',
            'Status',
            'Kód kupónu',
            'Platný do',
            'Aktivovaný',
            'Vytvorený'
        ], ';');

        // Write data
        foreach ($coupons as $coupon) {
            fputcsv($output, [
                $coupon['id'],
                $coupon['order_number'],
                $coupon['service_title'],
                $coupon['customer_name'],
                $coupon['type'] === 'main' ? 'Hlavná' : 'Bonus',
                $this->getStatusLabel($coupon['status']),
                $coupon['code'],
                date('d.m.Y H:i', strtotime($coupon['valid_until'])),
                $coupon['redeemed_at'] ? date('d.m.Y H:i', strtotime($coupon['redeemed_at'])) : '',
                date('d.m.Y H:i', strtotime($coupon['created_at']))
            ], ';');
        }

        fclose($output);
        exit;
    }

    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'active' => 'Aktívny',
            'redeemed' => 'Použitý',
            'expired' => 'Vypršaný',
            'revoked' => 'Zrušený',
            default => $status
        };
    }
}
