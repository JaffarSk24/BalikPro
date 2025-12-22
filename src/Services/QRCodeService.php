<?php

namespace BalikPro\Services;

use BalikPro\Utils\Logger;

class QRCodeService
{
    private Logger $logger;
    private array $config;
    private string $qrCodePath;
    private string $baseUrl;
    private string $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
        $this->logger = new Logger('qr.log');

        $cfg = @require $this->root . '/config/app.php';
        $this->config = is_array($cfg) ? $cfg : [];

        // uploads/qr с фолбэком на проект
        $uploadsBase = rtrim($this->config['uploads_path'] ?? '', '/');
        if (!$uploadsBase || !$this->ensureDir($uploadsBase)) {
            $uploadsBase = $this->root . '/public/uploads';
            $this->ensureDir($uploadsBase);
        }
        $this->qrCodePath = $uploadsBase . '/qr';
        $this->ensureDir($this->qrCodePath);

        // ВАЖНО: используем только явный домен из конфига (без HTTP_HOST),
        // чтобы код не указывал на локалку.
        $this->baseUrl = $this->resolveBaseUrl();
    }

    private function ensureDir(string $dir): bool
    {
        return is_dir($dir) || @mkdir($dir, 0775, true) || is_dir($dir);
    }

    private function resolveBaseUrl(): string
    {
        // приоритет явным ключам
        foreach (['qr_base_url','base_url','public_base_url','domain'] as $k) {
            if (!empty($this->config[$k])) {
                $v = (string)$this->config[$k];
                $v = preg_replace('#/+$#', '', $v);
                if (!str_starts_with($v, 'http')) {
                    $v = 'https://' . ltrim($v, '/');
                }
                return $v;
            }
        }
        // дефолт на прод
        return 'https://balikpro.sk';
    }

    public function generateQRCode(int $couponId, string $qrHash): string
    {
        // библиотека без автозагрузки
        require_once $this->root . '/vendor/phpqrcode/qrlib.php';

        $qrContent = $this->baseUrl . '/redeem/' . $couponId . '/' . $qrHash;

        $filename = "qr_{$couponId}_{$qrHash}.png";
        $filepath = $this->qrCodePath . '/' . $filename;

        // Больше модуль и выше коррекция — стабильнее сканируется
        \QRcode::png($qrContent, $filepath, QR_ECLEVEL_Q, 10, 2);

        // Ждем гарантированного появления файла
        $ok = $this->waitForFile($filepath, 400);
        if (!$ok || !filesize($filepath)) {
            $this->logger->error('QR code file not created or empty', ['path' => $filepath]);
            throw new \RuntimeException('QR code file not created');
        }

        $this->logger->info('QR code generated', [
            'coupon_id' => $couponId,
            'path'      => $filepath,
            'content'   => $qrContent,
        ]);

        return $filepath;
    }

    private function waitForFile(string $path, int $timeoutMs = 300): bool
    {
        $start = microtime(true);
        do {
            clearstatcache(true, $path);
            if (is_file($path) && filesize($path) > 0) return true;
            usleep(30_000);
        } while ((microtime(true) - $start) * 1000 < $timeoutMs);
        return false;
    }

    public function getQRCodeUrl(int $couponId, string $qrHash): string
    {
        $filename = "qr_{$couponId}_{$qrHash}.png";
        return "/uploads/qr/{$filename}";
    }
}