<?php
/**
 * ASENA Enterprise - Migration Runner
 */
require_once __DIR__ . '/../includes/db.php';

global $pdo;
$migrationFile = __DIR__ . '/migrations/01_enterprise_master_schema.sql';

if (!file_exists($migrationFile)) {
    echo "ERROR: Migration file not found: $migrationFile\n";
    exit(1);
}

echo "Running migration: " . basename($migrationFile) . "...\n";

try {
    $sql = file_get_contents($migrationFile);
    // Execute the SQL statements
    $pdo->exec($sql);
    echo "SUCCESS: Migration completed successfully.\n";

    // Show summary of newly added tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables in database now: " . count($tables) . "\n";
    
    $enterpriseTables = [
        'pet_health_records', 'pet_vaccinations', 'prescriptions',
        'product_price_tiers', 'b2b_rfqs', 'b2b_rfq_items',
        'order_status_logs', 'shipping_rates', 'flash_sales',
        'user_wallets', 'wallet_transactions'
    ];
    
    foreach ($enterpriseTables as $t) {
        $status = in_array($t, $tables) ? "[FOUND]" : "[MISSING]";
        echo "  - $t: $status\n";
    }
} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
