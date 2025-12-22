<?php

namespace BalikPro\Services;

use BalikPro\Utils\Logger;
use BalikPro\Utils\Database;
use PDO;

class EmailService
{
    private $config;
    private $logger;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/mailgun.php';
        $this->logger = new Logger('email.log');
    }

    public function sendCouponEmail(string $toEmail, string $customerName, string $pdfPath, array $orderData): bool
    {
        try {
            $subject = "Vaše kupóny z Balík PRO - objednávka {$orderData['order_number']}";
            $body = $this->generateEmailBody($customerName, $orderData);
            
            if ($this->config['is_mock']) {
                return $this->sendMockEmail($toEmail, $subject, $body, $pdfPath, $orderData);
            }

            return $this->sendMailgunEmail($toEmail, $subject, $body, $pdfPath, $orderData);

        } catch (\Exception $e) {
            $this->logger->error("Email sending failed", [
                'error' => $e->getMessage(),
                'to' => $toEmail,
                'order' => $orderData['order_number'] ?? null
            ]);
            return false;
        }
    }

    private function sendMockEmail(string $toEmail, string $subject, string $body, string $pdfPath, array $orderData): bool
    {
        $this->logger->info("Mock email sent", [
            'to' => $toEmail,
            'subject' => $subject,
            'pdf_path' => $pdfPath,
            'order_number' => $orderData['order_number'] ?? null
        ]);

        $this->logEmail($toEmail, $subject, $body, 'sent', [
            'provider' => 'mock',
            'pdf_attachment' => $pdfPath,
            'order_id' => $orderData['id'] ?? null
        ]);

        return true;
    }

    private function sendMailgunEmail(string $toEmail, string $subject, string $body, string $pdfPath, array $orderData): bool
    {
        $url = "{$this->config['api_endpoint']}/{$this->config['domain']}/messages";

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        $postData = [
            'from'       => $this->config['from'],
            'to'         => $toEmail,
            'subject'    => $subject,
            'html'       => $body,
            'attachment' => curl_file_create($pdfPath, 'application/pdf', 'kupony.pdf')
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => 'api:' . $this->config['api_key'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("Mailgun HTTP error {$httpCode}: {$response}");
        }

        $this->logger->info("Mailgun email sent successfully", [
            'to' => $toEmail,
            'order_number' => $orderData['order_number'] ?? null,
            'http_code' => $httpCode,
            'response' => $response
        ]);

        $this->logEmail($toEmail, $subject, $body, 'sent', [
            'provider' => 'mailgun',
            'response' => $response,
            'http_code' => $httpCode,
            'order_id' => $orderData['id'] ?? null
        ]);

        return true;
    }

    private function generateEmailBody(string $customerName, array $orderData): string
    {
        $orderNumber = htmlspecialchars($orderData['order_number'] ?? 'N/A');
        $customerNameEscaped = htmlspecialchars($customerName);

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Vaše kupóny z Balík PRO</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2563eb; margin-bottom: 10px;">Balík PRO</h1>
                <p style="color: #6b7280;">Ďakujeme za Váš nákup!</p>
            </div>
            
            <div style="background: #f8fafc; padding: 25px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: #1f2937; margin-top: 0;">Milý/á ' . $customerNameEscaped . ',</h2>
                
                <p>Vaša objednávka <strong>' . $orderNumber . '</strong> 
                bola úspešne spracovaná a vaše kupóny sú pripravené na použitie!</p>
                
                <p>V prílohe nájdete PDF súbor s vašimi kupónmi. Každý kupón obsahuje:</p>
                <ul>
                    <li>QR kód pre jednoduchú aktiváciu</li>
                    <li>Jedinečný kód kupónu</li>
                    <li>Podrobné inštrukcie na použitie</li>
                    <li>Platnosť kupónu</li>
                </ul>
            </div>
            
            <div style="background: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #1e40af; margin-top: 0;">Ako použiť kupóny:</h3>
                <ol>
                    <li>Prejdite k príslušnému partnerovi</li>
                    <li>Ukážte QR kód alebo zadajte kód kupónu</li>
                    <li>Partner zadá svoj PIN pre aktiváciu</li>
                    <li>Kupón je aktivovaný a môžete využiť službu</li>
                </ol>
            </div>
            
            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; text-align: center;">
                <p style="color: #6b7280; font-size: 14px;">
                    Potrebujete pomoc? Kontaktujte nás na 
                    <a href="mailto:support@balikpro.sk" style="color: #2563eb;">support@balikpro.sk</a>
                </p>
                <p style="color: #6b7280; font-size: 12px;">
                    © 2025 Balík PRO - www.balikpro.sk
                </p>
            </div>
        </body>
        </html>';
    }

    private function logEmail(string $toEmail, string $subject, string $body, string $status, array $payload = []): void
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO email_logs (to_email, subject, body_preview, provider, provider_payload, status, sent_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $toEmail,
                $subject,
                substr(strip_tags($body), 0, 500),
                $payload['provider'] ?? 'unknown',
                json_encode($payload),
                $status
            ]);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to log email to database", [
                'error' => $e->getMessage(),
                'to' => $toEmail
            ]);
        }
    }
}