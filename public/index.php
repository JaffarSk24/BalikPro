<?php
require_once __DIR__ . '/../autoload.php';

use BalikPro\Controllers\BundleController;

// --- ROUTER ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Serve static files directly if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // Let PHP built-in server handle it
}

// 2. API Routes
if (strpos($uri, '/api/') === 0) {
    // Forward to central API handler
    require __DIR__ . '/api/index.php';
    exit;
}

// 3. Webhook Routes
if ($uri === '/webhook' || strpos($uri, '/webhook/') === 0) {
    require __DIR__ . '/webhook.php';
    exit;
}

// 4. Specific Pages
switch ($uri) {
    case '/checkout':
    case '/checkout.php':
        require __DIR__ . '/checkout.php';
        exit;
        
    case '/partner':
    case '/partner/':
    case '/partner/index.php':
        require __DIR__ . '/partner/index.php';
        exit;
        
    case '/partner/dashboard':
    case '/partner/dashboard.php':
        require __DIR__ . '/partner/dashboard.php';
        exit;
}

// 5. Dynamic Routes (e.g. /redeem/123/hash)
if (preg_match('#^/redeem/#', $uri)) {
    require __DIR__ . '/redeem.php';
    exit;
}

// 6. Home Page (Default)
if ($uri === '/' || $uri === '/index.php') {
    // --- Автоопределение языка ---
    $acceptedLangs = ['sk', 'ru', 'uk'];
    $defaultLang = 'sk';
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $lang = in_array($browserLang, $acceptedLangs) ? $browserLang : $defaultLang;
    ?>
    <!DOCTYPE html>
    <html lang="<?= $lang ?>">
    <head>
        <?php include __DIR__ . '/views/header.php'; ?>
        <?php include __DIR__ . '/views/header-extra.php'; ?>
    </head>
    <body>
        <?php include __DIR__ . '/views/body.php'; ?>
        <?php include __DIR__ . '/views/body-extra.php'; ?>
        <?php include __DIR__ . '/views/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

// 7. 404 Not Found
http_response_code(404);
echo "404 Not Found";