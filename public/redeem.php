<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BalikPro\Controllers\CouponController;
use BalikPro\Utils\Logger;

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $logger = new Logger('redemption.log');
    
    $couponId = (int)($_GET['coupon_id'] ?? 0);
    $qrHash = $_GET['qr_hash'] ?? '';
    
    if (!$couponId || !$qrHash) {
        http_response_code(400);
        echo "Invalid parameters";
        exit;
    }
    
    $logger->info("Redemption page accessed", [
        'coupon_id' => $couponId,
        'qr_hash' => $qrHash,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $controller = new CouponController();
    $controller->showRedemptionPage($couponId, $qrHash);

} catch (\Exception $e) {
    $logger = new Logger('redemption_errors.log');
    $logger->error("Redemption Error: " . $e->getMessage());
    
    http_response_code(500);
    echo "Internal server error";
}
