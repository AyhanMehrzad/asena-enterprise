<?php
/**
 * ASENA Enterprise - Master Test Suite Runner
 * 
 * Runs all core test batteries:
 * 1. 100% Self-Hosted CDNs & Iranian Intranet Resilience
 * 2. In-App Wallet & Strict Cancellation/Refund Rules Engine
 * 3. Organization Specialist Invitations Handshake, Groomer & Pansion Roles
 * 4. Promo Code Engine, 10% Statutory VAT & Platform Margin Absorption
 * 5. Master Architecture & Enterprise Core Suite
 */

echo "=======================================================================\n";
echo "           🚀 ASENA ENTERPRISE - MASTER TEST RUNNER 🚀                 \n";
echo "=======================================================================\n\n";

$suites = [
    'Self-Hosted CDNs' => __DIR__ . '/test_all_cdns_self_hosted.php',
    'Wallet & Cancellation Rules' => __DIR__ . '/test_cancellation_and_wallet_rules.php',
    'Organization & Specialist Roles' => __DIR__ . '/test_organization_invitations_and_new_roles.php',
    'Promo Engine & 10% VAT' => __DIR__ . '/test_promo_code_engine.php',
    'Enterprise Core Architecture' => __DIR__ . '/test_enterprise_suite.php'
];

$phpBin = PHP_BINARY;
if (!is_executable($phpBin) || strpos($phpBin, 'php') === false) {
    $phpBin = '/opt/lampp/bin/php';
    if (!file_exists($phpBin)) {
        $phpBin = 'php';
    }
}

$allPassed = true;
$results = [];

foreach ($suites as $name => $filePath) {
    if (!file_exists($filePath)) {
        echo "⚠ Skipping {$name}: File not found ({$filePath})\n";
        continue;
    }

    echo "▶ Running Suite: {$name}...\n";
    $startTime = microtime(true);
    
    $descriptorSpec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    
    $process = proc_open("{$phpBin} " . escapeshellarg($filePath), $descriptorSpec, $pipes, dirname(__DIR__));
    $output = '';
    $errors = '';
    
    if (is_resource($process)) {
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
    } else {
        $exitCode = 1;
        $output = "Failed to launch process";
    }

    $duration = round(microtime(true) - $startTime, 2);
    $status = ($exitCode === 0);

    if ($status) {
        echo "  ✔ [PASS] {$name} completed in {$duration}s\n\n";
    } else {
        echo "  ✖ [FAIL] {$name} failed with exit code {$exitCode} in {$duration}s\n";
        if (!empty($errors)) {
            echo "  Error: " . trim($errors) . "\n";
        }
        echo "\n";
        $allPassed = false;
    }

    $results[$name] = [
        'passed' => $status,
        'duration' => $duration,
        'exitCode' => $exitCode
    ];
}

echo "=======================================================================\n";
echo "                         TEST SUITE SUMMARY                            \n";
echo "=======================================================================\n";

$passCount = 0;
foreach ($results as $name => $res) {
    $icon = $res['passed'] ? '✔ PASS' : '✖ FAIL';
    printf(" %-35s [%s] (%0.2fs)\n", $name, $icon, $res['duration']);
    if ($res['passed']) $passCount++;
}

echo "-----------------------------------------------------------------------\n";
echo "Total Suites: " . count($results) . " | Passed: {$passCount} | Failed: " . (count($results) - $passCount) . "\n";

if ($allPassed) {
    echo "\n✨ ALL ASENA ENTERPRISE TEST SUITES PASSED FLAWLESSLY! ✨\n\n";
    exit(0);
} else {
    echo "\n💥 SOME TEST SUITES FAILED. PLEASE CHECK LOGS ABOVE. 💥\n\n";
    exit(1);
}
