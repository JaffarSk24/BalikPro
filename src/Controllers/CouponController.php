<?php

namespace BalikPro\Controllers;

use BalikPro\Models\Coupon;
use BalikPro\Models\Partner;
use BalikPro\Utils\Database;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

class CouponController
{
    private $couponModel;
    private $partnerModel;
    private $logger;

    public function __construct()
    {
        $this->couponModel = new Coupon();
        $this->partnerModel = new Partner();
        $this->logger = new Logger('coupons.log');
    }

    public function showRedemptionPage(int $couponId, string $qrHash): void
    {
        try {
            $coupon = $this->couponModel->getCouponWithDetails($couponId, $qrHash);

            if (!$coupon) {
                $this->show404Page('Kupón nebol nájdený');
                return;
            }

            // Check if coupon is valid
            if ($coupon['status'] === 'redeemed') {
                $this->showExpiredPage('Tento kupón už bol použitý');
                return;
            }

            if ($coupon['status'] === 'expired' || strtotime($coupon['valid_until']) < time()) {
                $this->showExpiredPage('Tento kupón už vypršal');
                return;
            }

            if ($coupon['status'] === 'revoked') {
                $this->showExpiredPage('Tento kupón bol zrušený');
                return;
            }

            // Show redemption page
            $this->showRedemptionForm($coupon);

        } catch (\Exception $e) {
            $this->logger->error("Redemption page error: " . $e->getMessage());
            $this->show500Page();
        }
    }

    public function redeemCoupon(int $couponId): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Nevalidné dáta', 400);
                return;
            }

            $requiredFields = ['qr_hash', 'partner_id', 'pin'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    Response::error("Povinné pole chýba: {$field}", 400);
                    return;
                }
            }

            // Attempt to redeem coupon
            $result = $this->couponModel->redeemCoupon(
                $couponId,
                (int)$input['partner_id'],
                $input['pin'],
                $input['qr_hash']
            );

            if ($result['success']) {
                Response::success([
                    'coupon_status' => 'redeemed',
                    'message' => $result['message']
                ]);
            } else {
                $statusCode = match($result['message']) {
                    'Kupón nebol nájdený' => 404,
                    'Kupón už bol použitý' => 409,
                    'Kupón už vypršal' => 410,
                    'Nesprávny PIN partnera' => 401,
                    'Kupón nepatrí tomuto partnerovi' => 403,
                    default => 400
                };

                Response::error($result['message'], $statusCode);
            }

        } catch (\Exception $e) {
            $this->logger->error("Coupon redemption error: " . $e->getMessage());
            Response::error('Chyba pri aktivácii kupónu', 500);
        }
    }

    private function showRedemptionForm(array $coupon): void
    {
        $html = '<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivácia kupónu - Balík PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            padding: 20px;
            color: #1f2937;
        }
        .container { max-width: 600px; margin: 0 auto; }
        .card { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .header { 
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        .content { padding: 30px 20px; }
        .coupon-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .service-title { 
            font-size: 20px; 
            font-weight: bold; 
            color: #1f2937;
            margin-bottom: 10px;
        }
        .partner-name { color: #6b7280; margin-bottom: 15px; }
        .coupon-code { 
            font-family: monospace; 
            font-size: 16px; 
            font-weight: bold;
            padding: 8px 12px;
            background: #dbeafe;
            border-radius: 6px;
            display: inline-block;
        }
        .form-group { margin-bottom: 20px; }
        .form-label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500;
            color: #374151;
        }
        .form-input { 
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-input:focus { 
            outline: none; 
            border-color: #2563eb; 
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .submit-btn { 
            width: 100%; 
            padding: 14px; 
            background: #2563eb;
            color: white; 
            border: none; 
            border-radius: 8px;
            font-size: 16px; 
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-btn:hover { background: #1d4ed8; }
        .submit-btn:disabled { 
            background: #9ca3af; 
            cursor: not-allowed; 
        }
        .alert { 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .validity { color: #6b7280; font-size: 14px; margin-top: 15px; }
        .instructions {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .instructions h4 { color: #92400e; margin-bottom: 10px; }
        .instructions ol { margin-left: 20px; }
        .instructions li { margin-bottom: 5px; color: #78350f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">Balík PRO</div>
                <div>Aktivácia kupónu</div>
            </div>
            
            <div class="content">
                <div class="coupon-info">
                    <div class="service-title">' . htmlspecialchars($coupon['service_title']) . '</div>
                    <div class="partner-name">' . htmlspecialchars($coupon['partner_name']) . '</div>
                    <div class="coupon-code">' . htmlspecialchars($coupon['code']) . '</div>
                    <div class="validity">Platný do: ' . date('d.m.Y H:i', strtotime($coupon['valid_until'])) . '</div>
                </div>

                <div id="alert" class="alert"></div>

                <form id="redemption-form">
                    <input type="hidden" id="qr_hash" value="' . htmlspecialchars($coupon['qr_hash']) . '">
                    
                    <div class="form-group">
                        <label class="form-label" for="partner_id">ID Partnera:</label>
                        <input type="number" id="partner_id" class="form-input" required 
                               placeholder="Zadajte ID vášho partnera">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="pin">PIN kód:</label>
                        <input type="password" id="pin" class="form-input" required 
                               placeholder="Zadajte váš PIN kód" maxlength="10">
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submit-btn">
                        Aktivovať kupón
                    </button>
                </form>

                <div class="instructions">
                    <h4>Inštrukcie pre partnera:</h4>
                    <ol>
                        <li>Zadajte svoje Partner ID</li>
                        <li>Zadajte váš PIN kód</li>
                        <li>Stlačte "Aktivovať kupón"</li>
                        <li>Po úspešnej aktivácii môže zákazník využiť službu</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("redemption-form").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById("submit-btn");
            const alert = document.getElementById("alert");
            const partnerId = document.getElementById("partner_id").value;
            const pin = document.getElementById("pin").value;
            const qrHash = document.getElementById("qr_hash").value;
            
            if (!partnerId || !pin) {
                showAlert("Vyplňte všetky polia", "error");
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = "Aktivujem...";
            
            try {
                const response = await fetch("/api/coupons/' . $coupon['id'] . '/redeem", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        qr_hash: qrHash,
                        partner_id: parseInt(partnerId),
                        pin: pin
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    showAlert("Kupón bol úspešne aktivovaný! " + data.message, "success");
                    document.getElementById("redemption-form").style.display = "none";
                } else {
                    showAlert(data.message || "Chyba pri aktivácii kupónu", "error");
                }
            } catch (error) {
                showAlert("Chyba spojenia. Skúste to znovu.", "error");
            }
            
            submitBtn.disabled = false;
            submitBtn.textContent = "Aktivovať kupón";
        });
        
        function showAlert(message, type) {
            const alert = document.getElementById("alert");
            alert.className = "alert alert-" + type;
            alert.textContent = message;
            alert.style.display = "block";
        }
    </script>
</body>
</html>';

        Response::html($html);
    }

    private function show404Page(string $message): void
    {
        Response::html($this->getErrorPage('Kupón nebol nájdený', $message), 404);
    }

    private function showExpiredPage(string $message): void
    {
        Response::html($this->getErrorPage('Kupón nie je platný', $message), 410);
    }

    private function show500Page(): void
    {
        Response::html($this->getErrorPage('Systémová chyba', 'Nastala neočakávaná chyba. Skúste to znovu.'), 500);
    }

    private function getErrorPage(string $title, string $message): string
    {
        return '<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Balík PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #1f2937;
        }
        .container { text-align: center; max-width: 400px; padding: 40px; }
        .logo { font-size: 32px; font-weight: bold; color: #2563eb; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: 600; margin-bottom: 15px; color: #dc2626; }
        .message { font-size: 16px; color: #6b7280; margin-bottom: 30px; }
        .home-link { 
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .home-link:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Balík PRO</div>
        <div class="title">' . htmlspecialchars($title) . '</div>
        <div class="message">' . htmlspecialchars($message) . '</div>
        <a href="/" class="home-link">Späť na hlavnú stránku</a>
    </div>
</body>
</html>';
    }
}
