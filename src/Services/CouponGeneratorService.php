<?php

namespace BalikPro\Services;

use BalikPro\Utils\Logger;
use BalikPro\Models\Order;
use BalikPro\Models\Bundle;
use BalikPro\Models\Coupon;

class CouponGeneratorService
{
    private $logger;
    private $config;
    private $pdfDir;
    private $tmpDir;
    private $root;

    public function __construct()
    {
        $this->logger = new Logger('coupons.log');

        $this->root = dirname(__DIR__, 2);
        $cfg  = @require $this->root . '/config/app.php';
        $this->config = is_array($cfg) ? $cfg : [];

        // Папка для PDF (с фолбэком на проект)
        $pdfBase = rtrim($this->config['pdfs_path'] ?? '', '/');
        if (!$pdfBase || !$this->ensureDir($pdfBase)) {
            $pdfBase = $this->root . '/storage/pdfs';
            $this->ensureDir($pdfBase);
        }
        $this->pdfDir = $pdfBase;

        // Временная папка для mPDF
        $this->tmpDir = $this->root . '/storage/tmp';
        $this->ensureDir($this->tmpDir);
    }

    private function ensureDir(string $dir): bool
    {
        if (is_dir($dir)) return true;
        return @mkdir($dir, 0775, true) || is_dir($dir);
    }

    public function generateCouponsForOrder(int $orderId): array
    {
        try {
            $orderModel  = new Order();
            $bundleModel = new Bundle();
            $couponModel = new Coupon();

            // 1) Заказ
            $order = $orderModel->findById($orderId);
            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            // 2) Корзина из JSON
            $cart = [];
            if (!empty($order['cart'])) {
                $cart = json_decode($order['cart'], true);
            }
            if (!is_array($cart) || empty($cart)) {
                throw new \Exception("Cart is empty for order: {$orderId}");
            }

            $coupons = [];

            foreach ($cart as $bundle) {
                $mainPartnerId = null;

                // Основная услуга
                $mainId = (int)($bundle['main_service_id'] ?? 0);
                if ($mainId > 0) {
                    $mainService = $bundleModel->getServiceById($mainId);
                    if ($mainService) {
                        $mainPartnerId = $mainService['partner_id'] ?? null;

                        $mainCouponId = $couponModel->createCoupon([
                            'order_id'   => $orderId,
                            'service_id' => $mainId,
                            'partner_id' => $mainPartnerId,
                            'type'       => 'main',
                        ]);
                        if ($mainCouponId) {
                            $coupons[] = $couponModel->findById($mainCouponId);
                        }
                    }
                }

                // Бонус‑услуги
                $bonusIds = $bundle['bonus_service_ids'] ?? [];
                if (is_array($bonusIds)) {
                    foreach ($bonusIds as $bonusId) {
                        $bonusId = (int)$bonusId;
                        if ($bonusId <= 0) continue;

                        $bonusService  = $bundleModel->getServiceById($bonusId);
                        $bonusPartner  = $bonusService['partner_id'] ?? $mainPartnerId;

                        $bonusCouponId = $couponModel->createCoupon([
                            'order_id'   => $orderId,
                            'service_id' => $bonusId,
                            'partner_id' => $bonusPartner,
                            'type'       => 'bonus',
                        ]);
                        if ($bonusCouponId) {
                            $coupons[] = $couponModel->findById($bonusCouponId);
                        }
                    }
                }
            }

            $this->logger->info("Coupons generated for order", [
                'order_id'     => $orderId,
                'coupon_count' => count($coupons),
            ]);

            return [
                'success' => true,
                'coupons' => $coupons,
                'order'   => [
                    'order_number'  => $order['order_number'] ?? (string)$orderId,
                    'customer_name' => $order['customer_name'] ?? '',
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error("Coupon generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function generateCouponPDF(array $coupons, array $orderData): string
    {
        try {
            // Автолоадер на случай вызова вне фронт‑контроллера
            require_once $this->root . '/vendor/autoload.php';

            $mpdf = new \Mpdf\Mpdf([
                'mode'            => 'utf-8',
                'format'          => 'A4',
                'margin_left'     => 15,
                'margin_right'    => 15,
                'margin_top'      => 16,
                'margin_bottom'   => 16,
                'default_font'    => 'DejaVuSans',
                'autoLangToFont'  => true,
                'tempDir'         => $this->tmpDir,
            ]);

            $mpdf->SetTitle('Balík PRO - Vaše kupóny');
            $mpdf->SetAuthor('Balík PRO');

            // HTML с QR в виде data URI (никаких файловых путей по HTTP)
            $html = $this->generatePDFHtml($coupons, $orderData);
            $mpdf->WriteHTML($html);

            $safeOrder = preg_replace('/[^A-Za-z0-9_\-]/', '', $orderData['order_number'] ?? ('ORD' . ($coupons[0]['order_id'] ?? '')));
            $filename  = 'coupons_' . $safeOrder . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath  = rtrim($this->pdfDir, '/') . '/' . $filename;

            $this->ensureDir(dirname($filepath));
            $mpdf->Output($filepath, \Mpdf\Output\Destination::FILE);

            $this->logger->info("PDF generated", [
                'filename'     => $filename,
                'coupon_count' => count($coupons),
                'path'         => $filepath,
            ]);

            return $filepath;

        } catch (\Exception $e) {
            $this->logger->error("PDF generation failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePDFHtml(array $coupons, array $orderData): string
    {
        $qrService = new QRCodeService();

        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
                .coupon { border: 2px solid #e5e7eb; margin: 20px 0; padding: 20px; page-break-inside: avoid; }
                .coupon-header { background: #f3f4f6; padding: 10px; margin: -20px -20px 20px -20px; }
                .qr-code { text-align: center; margin: 20px 0; }
                .coupon-code { font-size: 14px; font-weight: bold; text-align: center; margin: 10px 0; }
                .validity { color: #6b7280; font-size: 11px; }
                .instructions { background: #fef3c7; padding: 15px; margin-top: 15px; border-radius: 5px; }
                .main-service { background: #dbeafe; }
                .bonus-service { background: #fef3c7; }
            </style>
        </head>
        <body>';

        $html .= '
        <div class="header">
            <div class="logo">Balík PRO</div>
            <p>Vaše kupóny pre objednávku: ' . htmlspecialchars($orderData['order_number'] ?? '') . '</p>
            <p>Zákazník: ' . htmlspecialchars($orderData['customer_name'] ?? '') . '</p>
        </div>';

        $count = count($coupons);
        foreach ($coupons as $index => $coupon) {
            $isMain = ($coupon['type'] ?? 'main') === 'main';
            $serviceClass = $isMain ? 'main-service' : 'bonus-service';
            $serviceType  = $isMain ? 'HLAVNÁ SLUŽBA' : 'BONUS SLUŽBA';

            // QR генерим в файл и встраиваем в PDF как data URI (без HTTP)
            $qrPath = $qrService->generateQRCode((int)$coupon['id'], (string)$coupon['qr_hash']);
            $qrData = base64_encode((string)file_get_contents($qrPath));

            $title = $coupon['service_title'] ?? 'Služba';
            $code  = $coupon['code'] ?? '';
            $until = $coupon['valid_until'] ?? '+6 months';

            $html .= '
            <div class="coupon">
                <div class="coupon-header ' . $serviceClass . '">
                    <strong>' . $serviceType . '</strong>
                </div>

                <h3>' . htmlspecialchars($title) . '</h3>

                <div class="qr-code">
                    <img src="data:image/png;base64,' . $qrData . '" width="120" height="120" />
                </div>

                <div class="coupon-code">
                    Kód: ' . htmlspecialchars($code) . '
                </div>

                <div class="validity">
                    Platný do: ' . htmlspecialchars(date('d.m.Y', strtotime($until))) . '
                </div>

                <div class="instructions">
                    <strong>Ako použiť kupón:</strong><br>
                    1. Prejdite k partnerovi<br>
                    2. Ukážte QR kód alebo zadajte kód kupónu<br>
                    3. Partner zadá svoj PIN pre aktiváciu<br>
                    4. Kupón je aktivovaný a môžete využiť službu
                </div>
            </div>';

            if (($index + 1) % 2 === 0 && $index < $count - 1) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }

        $html .= '
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p><strong>Balík PRO</strong> - www.balikpro.sk</p>
            <p style="font-size: 10px; color: #6b7280;">
                Pre podporu kontaktujte: support@balikpro.sk
            </p>
        </div>
        </body>
        </html>';

        return $html;
    }
}