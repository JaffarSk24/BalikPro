<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use BalikPro\Controllers\CheckoutController;
use BalikPro\Utils\Response;
use BalikPro\Utils\Logger;

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
        exit;
    }

    // Валидация JSON сразу перенесена в сам контроллер
    $controller = new CheckoutController();
    $controller->createCheckout();

} catch (\Exception $e) {
    $logger = new Logger('checkout_errors.log');
    $logger->error("Checkout Error: " . $e->getMessage());
    Response::error('Internal server error', 500);
}