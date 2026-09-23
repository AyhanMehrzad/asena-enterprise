<?php
/**
 * ASENA Enterprise - Search Engine Batch Indexer CLI
 * Usage: php bin/batch_index.php [--limit=100] [--google] [--indexnow]
 */

require_once __DIR__ . '/../includes/GoogleIndexingService.php';
require_once __DIR__ . '/../includes/IndexNowService.php';

$sitemapFile = __DIR__ . '/../sitemap.xml';
if (!file_exists($sitemapFile)) {
    die("Error: sitemap.xml not found.\n");
}

echo "====================================================\n";
echo " ASENA Search Engine Mass Indexer\n";
echo "====================================================\n";

$xml = simplexml_load_file($sitemapFile);
$urls = [];
foreach ($xml->url as $u) {
    $loc = trim((string)$u->loc);
    if (!empty($loc)) {
        $urls[] = $loc;
    }
}

$total = count($urls);
echo "Discovered {$total} total URLs in sitemap.xml.\n\n";

// 1. Instant IndexNow Broadcast (All URLs to Bing, Yandex, AI crawlers)
echo ">>> [1/2] Submitting to IndexNow (Bing / Yandex / AI search)...\n";
$indexNowRes = IndexNowService::submit($urls);
if ($indexNowRes['success']) {
    echo "✓ IndexNow broadcast SUCCESS! (HTTP {$indexNowRes['http_code']}, {$indexNowRes['count']} URLs accepted)\n\n";
} else {
    echo "✗ IndexNow broadcast FAILED: HTTP {$indexNowRes['http_code']} {$indexNowRes['error']}\n\n";
}

// 2. Google Web Search Indexing API (Priority Core Hubs & Top Pages)
// Prioritize root, hubs, tiers, animal categories
$priorityUrls = [];
$otherUrls = [];

foreach ($urls as $url) {
    if ($url === 'https://asena.company/' 
        || strpos($url, '/standard') !== false 
        || strpos($url, '/pharmacy') !== false
        || strpos($url, '/premium') !== false
        || strpos($url, '/basic') !== false
        || strpos($url, '/shop') !== false
        || strpos($url, '/booking') !== false
        || strpos($url, '/category=') !== false
        || strpos($url, '/knowledge_base') !== false) {
        $priorityUrls[] = $url;
    } else {
        $otherUrls[] = $url;
    }
}

$targetGoogle = array_slice(array_merge($priorityUrls, $otherUrls), 0, 100);
$googleCount = count($targetGoogle);

echo ">>> [2/2] Submitting top {$googleCount} high-priority URLs to Google Indexing API...\n";
$successCount = 0;
$failCount = 0;

foreach ($targetGoogle as $idx => $url) {
    $num = $idx + 1;
    $res = GoogleIndexingService::publish($url, 'URL_UPDATED');
    if ($res['success']) {
        $successCount++;
        echo " [{$num}/{$googleCount}] ✓ OK: {$url}\n";
    } else {
        $failCount++;
        $errMsg = $res['response']['error']['message'] ?? 'Unknown error';
        echo " [{$num}/{$googleCount}] ✗ FAIL: {$url} ({$errMsg})\n";
    }
    // Respect rate limits
    usleep(150000); // 150ms delay
}

echo "\n====================================================\n";
echo " INDEXING SUMMARY\n";
echo " - IndexNow: {$total} URLs submitted (HTTP {$indexNowRes['http_code']})\n";
echo " - Google Indexing API: {$successCount} succeeded, {$failCount} failed\n";
echo "====================================================\n";
