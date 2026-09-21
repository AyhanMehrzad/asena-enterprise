<?php
/**
 * ==============================================================================
 * ASENA Enterprise - Automated Daily Database Backup Engine
 * Version: 2.0.0 (Chewy-grade Enterprise Reliability)
 * 
 * Works in all hosting environments:
 * 1. Primary: High-speed mysqldump CLI binary with gzip streaming
 * 2. Fallback: Pure PDO table schema & row chunk streamer with gzopen
 * 
 * Retention: Automatically prunes backups older than 14 days.
 * Security: Enforces directory permissions and .htaccess deny to block web access.
 * ==============================================================================
 */

// Only allow execution from CLI or with an authorized security token
$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));
if (!$isCli) {
    $secret = defined('BACKUP_CRON_SECRET') ? BACKUP_CRON_SECRET : 'asena_backup_cron_token_sec98';
    if (!isset($_GET['token']) || !hash_equals($secret, (string)$_GET['token'])) {
        http_response_code(403);
        die("Forbidden: Access denied.");
    }
}

// Disable script execution time limit for large DB exports
@set_time_limit(600);
@ini_set('memory_limit', '512M');

$rootDir = realpath(__DIR__ . '/..');
require_once $rootDir . '/includes/Env.php';
require_once $rootDir . '/includes/db.php';

function logMsg(string $msg): void {
    $formatted = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $formatted;
}

logMsg("----------------------------------------------------------------------");
logMsg("Starting ASENA Enterprise Database Backup...");

// 1. Resolve credentials
$dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
$dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'asencomp_asena_db');
$dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'asencomp_admin');
$dbPass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: 'X3~YN,HY9M:j%jx');

logMsg("[INFO] Target Host: {$dbHost} | Database: {$dbName} | User: {$dbUser}");

// 2. Resolve Backup Directory
// If running on cPanel (/home/asencomp/), prefer /home/asencomp/backups/ outside webroot
$cpanelBackupDir = '/home/asencomp/backups';
if (is_dir('/home/asencomp') && (is_dir($cpanelBackupDir) || @mkdir($cpanelBackupDir, 0700, true))) {
    $backupDir = $cpanelBackupDir;
} else {
    $backupDir = $rootDir . '/database/backups';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0700, true);
    }
}

// Place security protection in the backup directory
if (is_dir($backupDir)) {
    @file_put_contents($backupDir . '/.htaccess', "Order Deny,Allow\nDeny from all\n");
    @file_put_contents($backupDir . '/index.html', "");
}

$timestamp = date('Y-m-d_H-i-s');
$backupFilename = "asena_{$dbName}_{$timestamp}.sql.gz";
$backupFilePath = $backupDir . '/' . $backupFilename;

logMsg("[INFO] Destination File: {$backupFilePath}");

// 3. Attempt Method 1: mysqldump binary
$mysqldumpPath = null;
$possiblePaths = [
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump',
    '/opt/lampp/bin/mysqldump',
    '/usr/local/mysql/bin/mysqldump'
];

foreach ($possiblePaths as $path) {
    if (@is_executable($path)) {
        $mysqldumpPath = $path;
        break;
    }
}

if (!$mysqldumpPath) {
    $which = @trim((string)shell_exec('which mysqldump 2>/dev/null'));
    if (!empty($which) && @is_executable($which)) {
        $mysqldumpPath = $which;
    }
}

$dumpSuccess = false;

if ($mysqldumpPath && function_exists('proc_open')) {
    logMsg("[INFO] Method 1: Attempting high-speed mysqldump CLI ({$mysqldumpPath})...");
    
    $cmd = escapeshellcmd($mysqldumpPath) . ' ' .
           '-h ' . escapeshellarg($dbHost) . ' ' .
           '-u ' . escapeshellarg($dbUser) . ' ';
    
    if (!empty($dbPass)) {
        $cmd .= '-p' . escapeshellarg($dbPass) . ' ';
    }
    
    $cmd .= '--default-character-set=utf8mb4 ' .
            '--single-transaction ' .
            '--quick ' .
            '--triggers ' .
            escapeshellarg($dbName);

    // Stream directly into gzip to save disk I/O and handle large databases
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($cmd, $descriptors, $pipes);
    if (is_resource($process)) {
        fclose($pipes[0]);
        $gz = gzopen($backupFilePath, 'wb9');
        if ($gz) {
            while (!feof($pipes[1])) {
                $buffer = fread($pipes[1], 65536);
                if ($buffer !== false && strlen($buffer) > 0) {
                    gzwrite($gz, $buffer);
                }
            }
            gzclose($gz);
        }
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        if ($returnCode === 0 && file_exists($backupFilePath) && filesize($backupFilePath) > 1024) {
            $dumpSuccess = true;
            logMsg("[SUCCESS] mysqldump completed successfully.");
        } else {
            logMsg("[WARN] mysqldump exited with code {$returnCode}: {$stderr}. Switching to PDO fallback...");
            @unlink($backupFilePath);
        }
    }
}

// 4. Method 2: PDO Fallback (guaranteed to work across any PHP/MySQL hosting setup)
if (!$dumpSuccess) {
    logMsg("[INFO] Method 2: Exporting tables via native PDO streaming fallback...");
    global $pdo;

    if (!$pdo || !($pdo instanceof PDO)) {
        logMsg("[FATAL ERROR] PDO connection unavailable.");
        exit(1);
    }

    $gz = gzopen($backupFilePath, 'wb9');
    if (!$gz) {
        logMsg("[FATAL ERROR] Unable to open {$backupFilePath} for gzip writing.");
        exit(1);
    }

    // Write SQL Header
    $header  = "-- ========================================================\n";
    $header .= "-- ASENA Enterprise Database Backup (PDO Engine)\n";
    $header .= "-- Host: {$dbHost} | Database: {$dbName}\n";
    $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $header .= "-- ========================================================\n\n";
    $header .= "SET NAMES utf8mb4;\n";
    $header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $header .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";
    gzwrite($gz, $header);

    try {
        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            logMsg("[INFO] Exporting table: {$table}");
            
            // Drop & Create Table Structure
            gzwrite($gz, "\n-- Table structure for table `{$table}`\n");
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");
            
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                gzwrite($gz, $createRow[1] . ";\n\n");
            }

            // Dump Table Data in chunks
            gzwrite($gz, "-- Dumping data for table `{$table}`\n");
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $totalRows = (int)$countStmt->fetchColumn();

            if ($totalRows > 0) {
                $batchSize = 500;
                for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {
                    $rowsStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}");
                    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) {
                        break;
                    }

                    $insertSql = "INSERT INTO `{$table}` VALUES ";
                    $valuesArr = [];
                    foreach ($rows as $row) {
                        $escapedValues = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote($val);
                        }, array_values($row));
                        $valuesArr[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    $insertSql .= implode(",\n", $valuesArr) . ";\n";
                    gzwrite($gz, $insertSql);
                }
            }
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
        gzwrite($gz, "-- Backup successfully finished at " . date('Y-m-d H:i:s') . "\n");
        gzclose($gz);
        $dumpSuccess = true;
        logMsg("[SUCCESS] PDO fallback export finished successfully.");
    } catch (Throwable $ex) {
        gzclose($gz);
        @unlink($backupFilePath);
        logMsg("[FATAL ERROR] PDO export failed: " . $ex->getMessage());
        exit(1);
    }
}

// 5. Verify Backup File
if ($dumpSuccess && file_exists($backupFilePath)) {
    $bytes = filesize($backupFilePath);
    $sizeFormatted = round($bytes / (1024 * 1024), 2) . ' MB (' . number_format($bytes) . ' bytes)';
    logMsg("[SUCCESS] Backup file created: {$backupFilePath}");
    logMsg("[SUCCESS] Compressed Size: {$sizeFormatted}");
} else {
    logMsg("[FATAL ERROR] Backup verification failed: File not found or empty.");
    exit(1);
}

// 6. Automated Pruning / Retention Policy (14 Days)
$retentionDays = 14;
$cutoffTimestamp = time() - ($retentionDays * 86400);
$prunedCount = 0;

logMsg("[INFO] Pruning backup archives older than {$retentionDays} days...");

$dirHandle = @opendir($backupDir);
if ($dirHandle) {
    while (($file = readdir($dirHandle)) !== false) {
        if ($file === '.' || $file === '..' || !str_ends_with($file, '.sql.gz')) {
            continue;
        }
        $fullPath = $backupDir . '/' . $file;
        if (is_file($fullPath) && filemtime($fullPath) < $cutoffTimestamp) {
            if (@unlink($fullPath)) {
                logMsg("[PRUNED] Deleted old archive: {$file}");
                $prunedCount++;
            }
        }
    }
    closedir($dirHandle);
}

logMsg("[INFO] Pruning completed: {$prunedCount} archive(s) pruned.");
logMsg("----------------------------------------------------------------------");
logMsg("ASENA Enterprise Database Backup Engine Completed Successfully.");
exit(0);
