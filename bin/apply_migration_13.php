<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/13_platform_interest_and_bank_schema.sql');
    
    // Execute multiple statements
    $pdo->exec($sql);
    echo "Migration 13 executed successfully!\n";

    // Verify settings
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'platform_commission_percent'");
    $stmt->execute();
    $rate = $stmt->fetchColumn();
    echo "Platform commission percent is now: $rate%\n";

    // Verify organizations columns
    $cols = $pdo->query("SHOW COLUMNS FROM organizations LIKE 'bank_name'")->fetchAll();
    echo "organizations.bank_name exists: " . (count($cols) > 0 ? "YES" : "NO") . "\n";

    // Verify sms_package_purchases
    $tables = $pdo->query("SHOW TABLES LIKE 'sms_package_purchases'")->fetchAll();
    echo "sms_package_purchases exists: " . (count($tables) > 0 ? "YES" : "NO") . "\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
