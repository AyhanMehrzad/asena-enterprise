<?php
/**
 * Test Suite: 100% Self-Hosted CDNs & Offline / National Intranet (SHOMA) Resilience
 * Verifies that the entire application operates with zero external foreign CDN dependencies.
 */

$root = dirname(__DIR__);
$errors = [];
$passes = [];

function check($cond, $msg) {
    global $errors, $passes;
    if ($cond) {
        $passes[] = $msg;
        echo "  [PASS] $msg\n";
    } else {
        $errors[] = $msg;
        echo "  [FAIL] $msg\n";
    }
}

echo "=== Verifying 100% Self-Hosted Assets (Anti-Internet Blockade) ===\n\n";

// 1. Verify No Foreign CDN URLs in code
$forbiddenDomains = [
    'fonts.googleapis.com',
    'fonts.gstatic.com',
    'cdnjs.cloudflare.com',
    'cdn.jsdelivr.net',
    'unpkg.com',
    'cdn.tailwindcss.com',
    'placehold.co',
    'via.placeholder.com'
];

$filesToCheck = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iter as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $rel = str_replace($root . '/', '', $path);
    
    // Skip git, node_modules, tests, dist, docs, demo snapshots, and the tailwindcdn fallback file
    if (preg_match('#^(\.git|node_modules|dist|\.agents|tests|demo)/#', $rel)) continue;
    if (preg_match('#\.(md|sql|zip|gz|tar|png|jpg|jpeg|webp|woff2|woff|ttf|ico)$#', $rel)) continue;
    if ($rel === 'assets/js/tailwindcss-cdn.js') continue;

    $filesToCheck[] = $path;
}

$foundViolations = [];
foreach ($filesToCheck as $filePath) {
    $content = file_get_contents($filePath);
    foreach ($forbiddenDomains as $domain) {
        if (str_contains($content, $domain)) {
            $foundViolations[] = str_replace($root . '/', '', $filePath) . " contains '$domain'";
        }
    }
}

check(empty($foundViolations), "Zero foreign CDN / placeholder URLs found in " . count($filesToCheck) . " production files" . (empty($foundViolations) ? "" : ": " . implode(", ", $foundViolations)));

// 2. Verify Local Self-Hosted Assets Exist
$requiredLocalAssets = [
    'assets/fonts/Dxxo8j6PP2D_kU2muijlGMWWMmk.woff2' => 'Vazirmatn Arabic/Persian Font File',
    'assets/fonts/kJEPBvYX7BgnkSrUwT8OhrdQw4oELdPIeeII9v6oDMzBwG-RpA6RzaxHMPdY40KH8nGzv3fzfVJO1Q.woff2' => 'Material Symbols Woff2',
    'assets/fonts/gyByhwUxId8gMEwcGFU.woff2' => 'Geist Latin Woff2',
    'assets/fonts/fonts.css' => 'Self-Hosted fonts.css',
    'assets/css/vazirmatn.css' => 'Local Vazirmatn Stylesheet',
    'assets/css/material-symbols.css' => 'Local Material Symbols Stylesheet',
    'assets/css/geist.css' => 'Local Geist Stylesheet',
    'assets/css/tailwind.output.css' => 'Precompiled Tailwind CSS',
    'assets/vendor/leaflet/leaflet.js' => 'Local Leaflet JS',
    'assets/vendor/leaflet/leaflet.css' => 'Local Leaflet CSS',
    'assets/vendor/leaflet/images/marker-icon.png' => 'Leaflet Marker Icon',
    'assets/vendor/leaflet/images/marker-shadow.png' => 'Leaflet Marker Shadow',
    'assets/vendor/leaflet/images/layers.png' => 'Leaflet Layers Icon',
    'assets/vendor/leaflet/images/layers-2x.png' => 'Leaflet Retina Layers Icon',
    'assets/images/placeholders/placeholder-doctor.svg' => 'Local Doctor Placeholder SVG',
    'assets/images/placeholders/placeholder-campaign.svg' => 'Local Campaign Placeholder SVG',
    'assets/images/placeholders/placeholder-no-image.svg' => 'Local General Placeholder SVG'
];

foreach ($requiredLocalAssets as $relPath => $desc) {
    check(file_exists($root . '/' . $relPath) && filesize($root . '/' . $relPath) > 0, "$desc exists at $relPath");
}

// 3. Verify fonts.css format
$fontsCss = file_get_contents($root . '/assets/fonts/fonts.css');
check(str_contains($fontsCss, "font-family: 'Vazirmatn'"), "fonts.css declares Vazirmatn font-face");
check(str_contains($fontsCss, "font-family: 'Material Symbols Outlined'"), "fonts.css declares Material Symbols font-face");

// 4. Verify sw.js pre-caches fonts and offline assets
$swJs = file_get_contents($root . '/sw.js');
check(str_contains($swJs, 'assets/fonts/fonts.css'), "sw.js pre-caches assets/fonts/fonts.css");
check(str_contains($swJs, 'assets/fonts/Dxxo8j6PP2D_kU2muijlGMWWMmk.woff2'), "sw.js pre-caches Vazirmatn font woff2");
check(str_contains($swJs, 'assets/vendor/leaflet/images/layers.png'), "sw.js pre-caches Leaflet layer icons");

// 5. Verify SecurityMiddleware CSP has removed foreign CDNs
$secMiddleware = file_get_contents($root . '/includes/SecurityMiddleware.php');
check(!str_contains($secMiddleware, 'cdn.tailwindcss.com'), "CSP no longer permits cdn.tailwindcss.com");
check(!str_contains($secMiddleware, 'fonts.googleapis.com'), "CSP no longer permits fonts.googleapis.com");
check(!str_contains($secMiddleware, 'cdn.jsdelivr.net'), "CSP no longer permits cdn.jsdelivr.net");

echo "\nSummary: " . count($passes) . " passed, " . count($errors) . " failed.\n";
if (!empty($errors)) {
    exit(1);
}
echo "All CDN self-hosting checks passed successfully!\n";
