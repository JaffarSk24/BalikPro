<?php
// public/webhook.php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use BalikPro\Controllers\WebhookController;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

try {
    $controller = new WebhookController();
    $controller->handleRevolutWebhook();
} catch (\Throwable $e) {
    // Fallback logging if controller fails completely
    $logger = new Logger('revolut_webhook.log');
    $logger->error("Critical Webhook Error", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}