<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use BalikPro\Controllers\WebhookController;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $logger = new Logger('webhooks.log');
    
    // Parse the request
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // Remove query string and webhook prefix
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = str_replace('/webhook', '', $path);
    $pathParts = array_filter(explode('/', $path));
    
    $logger->info("Webhook Request", [
        'method' => $requestMethod,
        'path' => $path,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    if ($requestMethod !== 'POST') {
        Response::error('Method not allowed', 405);
        return;
    }

    if (empty($pathParts)) {
        Response::error('Not found', 404);
        return;
    }

    $provider = reset($pathParts);
    $controller = new WebhookController();

    switch ($provider) {
        case 'revolut':
            $controller->handleRevolutWebhook();
            break;

        default:
            Response::error('Unknown webhook provider', 404);
    }

} catch (\Exception $e) {
    $logger = new Logger('webhook_errors.log');
    $logger->error("Webhook Error: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    Response::error('Internal server error', 500);
}