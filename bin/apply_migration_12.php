<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/12_autoship_inventory_auth_and_contracts.sql');
    
    // Execute multiple statements
    $pdo->exec($sql);
    echo "Migration 12 executed successfully!\n";

    // Verify tables and columns
    $tables = $pdo->query("SHOW TABLES LIKE 'contract_acceptances'")->fetchAll();
    echo "contract_acceptances table exists: " . (count($tables) > 0 ? "YES" : "NO") . "\n";

    $colsUsers = $pdo->query("SHOW COLUMNS FROM users LIKE 'contract_accepted_version'")->fetchAll();
    echo "users.contract_accepted_version exists: " . (count($colsUsers) > 0 ? "YES" : "NO") . "\n";

    $colsProd = $pdo->query("SHOW COLUMNS FROM products LIKE 'autoship_min_months_stock'")->fetchAll();
    echo "products.autoship_min_months_stock exists: " . (count($colsProd) > 0 ? "YES" : "NO") . "\n";

    $colsMed = $pdo->query("SHOW COLUMNS FROM pharmacy_medicines LIKE 'autoship_min_months_stock'")->fetchAll();
    echo "pharmacy_medicines.autoship_min_months_stock exists: " . (count($colsMed) > 0 ? "YES" : "NO") . "\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
