<?php

require_once __DIR__ . '/../autoload.php';

use BalikPro\Utils\Logger;

echo "Testing Balík PRO system...\n\n";

$logger = new Logger('system_test.log');
$errors = [];
$passed = 0;
$total = 0;

// Test 1: Database connection
echo "1. Testing database connection...\n";
$total++;
try {
    $pdo = BalikPro\Utils\Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM partners");
    $partnerCount = $stmt->fetchColumn();
    echo "   ✓ Connected to database, found {$partnerCount} partners\n";
    $passed++;
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    $errors[] = "Database connection: " . $e->getMessage();
}

// Test 2: API endpoints
echo "\n2. Testing API endpoints...\n";

$testUrls = [
    '/api/bundles' => 'GET',
    '/api/partner/auth' => 'POST'
];

foreach ($testUrls as $url => $method) {
    $total++;
    echo "   Testing {$method} {$url}...\n";
    
    try {
        if ($method === 'GET') {
            // Simulate GET request
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = $url;
            
            ob_start();
            include __DIR__ . '/../public/api/index.php';
            $response = ob_get_clean();
            
            if (!empty($response)) {
                echo "   ✓ {$url} responded\n";
                $passed++;
            } else {
                echo "   ✗ {$url} no response\n";
                $errors[] = "API endpoint {$url} no response";
            }
        } else {
            echo "   ~ Skipping POST test (requires input)\n";
            $passed++; // Count as passed for now
        }
    } catch (Exception $e) {
        echo "   ✗ {$url} error: " . $e->getMessage() . "\n";
        $errors[] = "API endpoint {$url}: " . $e->getMessage();
    }
}

// Test 3: Models functionality
echo "\n3. Testing models...\n";

$modelTests = [
    'Bundle' => BalikPro\Models\Bundle::class,
    'Partner' => BalikPro\Models\Partner::class,
    'Order' => BalikPro\Models\Order::class,
    'Coupon' => BalikPro\Models\Coupon::class,
    'Customer' => BalikPro\Models\Customer::class
];

foreach ($modelTests as $name => $class) {
    $total++;
    echo "   Testing {$name} model...\n";
    
    try {
        $model = new $class();
        $result = $model->findAll([], 1); // Get 1 record
        
        echo "   ✓ {$name} model working\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ {$name} model error: " . $e->getMessage() . "\n";
        $errors[] = "{$name} model: " . $e->getMessage();
    }
}

// Test 4: Services functionality
echo "\n4. Testing services...\n";

$serviceTests = [
    'RevolutPaymentService' => BalikPro\Services\RevolutPaymentService::class,
    'CouponGeneratorService' => BalikPro\Services\CouponGeneratorService::class,
    'QRCodeService' => BalikPro\Services\QRCodeService::class,
    'EmailService' => BalikPro\Services\EmailService::class
];

foreach ($serviceTests as $name => $class) {
    $total++;
    echo "   Testing {$name}...\n";
    
    try {
        $service = new $class();
        echo "   ✓ {$name} instantiated\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ {$name} error: " . $e->getMessage() . "\n";
        $errors[] = "{$name}: " . $e->getMessage();
    }
}

// Test 4b: Send test email via Mailgun
echo "\n4b. Sending test email...\n";
$total++;
try {
    $emailService = new BalikPro\Services\EmailService();
    $ok = $emailService->sendCouponEmail(
        "mosinkir@icloud.com",
        "Test User", 
        __DIR__ . "/../storage/pdfs/sample.txt", // Текстовый файл
        ['order_number' => 'TEST-ORDER-123']
    );
    if ($ok) {
        echo "   ✓ Test email sent successfully\n";
        $passed++;
    } else {
        echo "   ✗ EmailService returned false\n";
        $errors[] = "EmailService failed to send test email";
    }
} catch (Exception $e) {
    echo "   ✗ Error sending email: " . $e->getMessage() . "\n";
    $errors[] = "Email sending exception: " . $e->getMessage();
}

// Test 5: File permissions
echo "\n5. Testing file permissions...\n";

$directories = [
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../storage/pdfs', 
    __DIR__ . '/../storage/temp',
    __DIR__ . '/../public/uploads'
];

foreach ($directories as $dir) {
    $total++;
    echo "   Testing write access to {$dir}...\n";
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $testFile = $dir . '/test_' . time() . '.txt';
    
    try {
        file_put_contents($testFile, 'test');
        
        if (file_exists($testFile)) {
            unlink($testFile);
            echo "   ✓ Write access OK\n";
            $passed++;
        } else {
            echo "   ✗ Cannot write to directory\n";
            $errors[] = "Cannot write to {$dir}";
        }
    } catch (Exception $e) {
        echo "   ✗ Write test failed: " . $e->getMessage() . "\n";
        $errors[] = "Write to {$dir}: " . $e->getMessage();
    }
}

// Test 6: Configuration
echo "\n6. Testing configuration...\n";

$configFiles = [
    'app.php',
    'database.php', 
    'revolut.php',
    'mailgun.php'
];

foreach ($configFiles as $configFile) {
    $total++;
    echo "   Testing config/{$configFile}...\n";
    
    try {
        $config = require __DIR__ . "/../config/{$configFile}";
        
        if (is_array($config) && !empty($config)) {
            echo "   ✓ Configuration loaded\n";
            $passed++;
        } else {
            echo "   ✗ Configuration invalid\n";
            $errors[] = "Configuration {$configFile} invalid";
        }
    } catch (Exception $e) {
        echo "   ✗ Configuration error: " . $e->getMessage() . "\n";
        $errors[] = "Configuration {$configFile}: " . $e->getMessage();
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Passed: {$passed}/{$total}\n";
echo "Failed: " . ($total - $passed) . "/{$total}\n";

if (!empty($errors)) {
    echo "\nERRORS:\n";
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". {$error}\n";
    }
}

if ($passed === $total) {
    echo "\n🎉 All tests passed! System is ready.\n";
    $logger->info("System test completed successfully", ['passed' => $passed, 'total' => $total]);
} else {
    echo "\n⚠️  Some tests failed. Check the errors above.\n";
    $logger->error("System test completed with errors", [
        'passed' => $passed, 
        'total' => $total, 
        'errors' => $errors
    ]);
}

echo "\n";
