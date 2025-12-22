<?php
require_once __DIR__ . '/../autoload.php';

echo "Setting up database...\n";

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: 3306;
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'balikpro';

try {
    // 1. Connect without DB to create it
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating database '$dbname' if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // 2. Import Schema
    $schemaFile = __DIR__ . '/../data/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: Schema file not found at $schemaFile\n");
    }

    echo "Importing schema from $schemaFile...\n";
    $sql = file_get_contents($schemaFile);
    
    // Split by semicolon to execute statements
    // This is a simple split, for complex SQL might need better parsing but sufficient for this schema
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "Schema imported successfully.\n";

    // 3. Seed Data
    echo "Seeding initial data...\n";

    // Admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
    $stmt->execute(['roccreate@gmail.com']);
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_BCRYPT);
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $pdo->prepare("INSERT INTO admins (uuid, email, password_hash, name, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$uuid, 'roccreate@gmail.com', $passHash, 'Super Admin', 'superadmin']);
        echo "- Admin created: roccreate@gmail.com / admin123\n";
    }

    // Partner
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM partners WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pinHash = password_hash('pin1', PASSWORD_BCRYPT);
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $pdo->prepare("INSERT INTO partners (id, uuid, name, contact_person, email, pin_hash) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([1, $uuid, 'Test Partner', 'John Doe', 'partner@example.com', $pinHash]);
        echo "- Partner created: ID 1 / PIN pin1\n";
    }
    
    // Service & Bundle for testing
    // Check if main service exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE partner_id = 1 AND is_main = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $serviceUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $pdo->prepare("INSERT INTO services (uuid, partner_id, title, description, price, is_main, active) VALUES (?, 1, 'Main Service', 'Description of main service', 100.00, 1, 1)")
            ->execute([$serviceUuid]);
        $mainServiceId = $pdo->lastInsertId();
        echo "- Main Service created.\n";

        // Bundle
        $bundleUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $pdo->prepare("INSERT INTO bundles (uuid, name, main_service_id, active) VALUES (?, 'Test Bundle', ?, 1)")
            ->execute([$bundleUuid, $mainServiceId]);
        echo "- Test Bundle created.\n";
    }

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
