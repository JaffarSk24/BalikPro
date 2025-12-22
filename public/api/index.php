<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use BalikPro\Controllers\BundleController;
use BalikPro\Controllers\CheckoutController;
use BalikPro\Controllers\CouponController;
use BalikPro\Controllers\PartnerController;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $logger = new Logger('api.log');
    
    // Parse the request
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // Remove query string and API prefix
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = str_replace('/api', '', $path);
    // Strip .php extension if present
    $path = str_replace('.php', '', $path);
    $pathParts = array_values(array_filter(explode('/', $path)));
    
    $logger->info("API Request", [
        'method' => $requestMethod,
        'path' => $path,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Route the request
    switch ($requestMethod) {
        case 'GET':
            handleGetRequest($pathParts);
            break;
        case 'POST':
            handlePostRequest($pathParts);
            break;
        default:
            Response::error('Method not allowed', 405);
    }

} catch (\Exception $e) {
    $logger = new Logger('api_errors.log');
    $logger->error("API Error: " . $e->getMessage());
    Response::error('Internal server error', 500);
}

function handleGetRequest(array $pathParts): void
{
    if (empty($pathParts)) {
        Response::error('Not found', 404);
        return;
    }

    $resource = $pathParts[0];

    switch ($resource) {
        case 'bundles':
            $controller = new BundleController();
            if (isset($pathParts[1])) {
                $controller->getBundleDetail((int)$pathParts[1]);
            } else {
                $controller->getActiveBundles();
            }
            break;

        case 'partner':
            if (!isset($pathParts[1])) {
                Response::error('Partner ID required', 400);
                return;
            }
            
            $partnerId = (int)$pathParts[1];
            $controller = new PartnerController();
            
            if (isset($pathParts[2])) {
                switch ($pathParts[2]) {
                    case 'dashboard':
                        $controller->getDashboard($partnerId);
                        break;
                    case 'coupons':
                        if (isset($_GET['export'])) {
                            $controller->exportCoupons($partnerId);
                        } else {
                            $controller->getCoupons($partnerId);
                        }
                        break;
                    default:
                        Response::error('Not found', 404);
                }
            } else {
                Response::error('Action required', 400);
            }
            break;

        default:
            Response::error('Not found', 404);
    }
}

function handlePostRequest(array $pathParts): void
{
    if (empty($pathParts)) {
        Response::error('Not found', 404);
        return;
    }

    $resource = $pathParts[0];

    switch ($resource) {
        case 'checkout':
            $controller = new CheckoutController();
            $controller->createCheckout();
            break;

        case 'partner':
            if (isset($pathParts[1]) && $pathParts[1] === 'auth') {
                $controller = new PartnerController();
                $controller->authenticate();
            } else {
                Response::error('Not found', 404);
            }
            break;

        case 'coupons':
            if (isset($pathParts[1], $pathParts[2]) && $pathParts[2] === 'redeem') {
                $controller = new CouponController();
                $controller->redeemCoupon((int)$pathParts[1]);
            } else {
                Response::error('Not found', 404);
            }
            break;

        default:
            Response::error('Not found', 404);
    }
}
