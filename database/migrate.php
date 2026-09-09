<?php
/**
 * ASENA Enterprise - Comprehensive Migration Runner
 * Iterates through all migrations in sequence and applies them safely.
 */
require_once __DIR__ . '/../includes/db.php';

global $pdo;

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

echo "==================================================\n";
echo "      ASENA ENTERPRISE - DATABASE MIGRATIONS     \n";
echo "==================================================\n";

if (empty($files)) {
    echo "No migration files found in $migrationsDir\n";
    exit(0);
}

$successCount = 0;
$errorCount = 0;

foreach ($files as $file) {
    $filename = basename($file);
    echo "Running migration: {$filename}... ";
    
    try {
        $sql = file_get_contents($file);
        if (!empty(trim($sql))) {
            $pdo->exec($sql);
        }
        echo "[OK]\n";
        $successCount++;
    } catch (Throwable $e) {
        echo "[FAILED: " . $e->getMessage() . "]\n";
        $errorCount++;
    }
}

echo "--------------------------------------------------\n";
echo "Migrations finished: {$successCount} applied, {$errorCount} warnings/errors.\n";
