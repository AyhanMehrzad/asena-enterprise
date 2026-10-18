<?php
$logFile = __DIR__ . '/verify_log.txt';
$entry = date('Y-m-d H:i:s') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' | Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? '') . ' | UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'none') . "\n";
@file_put_contents($logFile, $entry, FILE_APPEND);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo "ksxzootlfz";
exit;
