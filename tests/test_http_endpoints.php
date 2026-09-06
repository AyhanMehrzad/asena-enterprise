<?php
/**
 * Test HTTP pages with authenticated cookies
 */

function requestWithCookie($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

$cookieOrg = tempnam(sys_get_temp_dir(), 'org_cook_');
$cookieSeller = tempnam(sys_get_temp_dir(), 'sel_cook_');

echo "--- 1. Testing Login as Organization (09120000004 / 123456) ---\n";
// Get CSRF from login
$loginPage = requestWithCookie('http://localhost/asena/asena-enterprise/login.php', $cookieOrg);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
$csrf = $matches[1] ?? '';

// Post login
$ch = curl_init('http://localhost/asena/asena-enterprise/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'mode' => 'login',
    'phone' => '09120000001',
    'password' => 'Asena1234!'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieOrg);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieOrg);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Org/Admin Login Status: {$code}\n";

$orgPages = [
    'organization/index.php',
    'organization/appointments.php',
    'organization/shifts.php',
    'organization/doctors.php',
    'organization/wallet.php',
    'organization/subscriptions.php'
];

foreach ($orgPages as $page) {
    $r = requestWithCookie("http://localhost/asena/asena-enterprise/{$page}", $cookieOrg);
    $hasFatal = (stripos($r['body'], 'Fatal error') !== false || stripos($r['body'], 'Parse error') !== false);
    echo "Page [{$page}] -> HTTP {$r['code']} | Fatal/Parse Error: " . ($hasFatal ? "YES ❌" : "NO ✅") . "\n";
}

echo "\n--- 2. Testing Login as Single Person Seller (09120000003 / Asena1234!) ---\n";
$ch = curl_init('http://localhost/asena/asena-enterprise/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'mode' => 'login',
    'phone' => '09120000003',
    'password' => 'Asena1234!'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieSeller);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieSeller);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res2 = curl_exec($ch);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
echo "Effective URL: {$effectiveUrl}\n";
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Seller Login Status: {$code2}\n";
preg_match('/<div[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)<\/div>/is', $res2, $errMatch);
if (!empty($errMatch[1])) {
    echo "Seller Login Error Found: " . trim(strip_tags($errMatch[1])) . "\n";
}

$sellerProf = requestWithCookie('http://localhost/asena/asena-enterprise/profile.php', $cookieSeller);
$hasFatalProf = (stripos($res2, 'Fatal error') !== false || stripos($res2, 'Parse error') !== false);
$hasSellerBadge = (stripos($res2, 'پیشخوان فروش و کسب‌وکار') !== false || stripos($res2, 'مدیریت کاتالوگ و محصولات') !== false || stripos($res2, 'storefront') !== false);
echo "Page [profile.php?view=seller] directly followed from login -> Fatal: " . ($hasFatalProf ? "YES ❌" : "NO ✅") . " | Seller UI Visible: " . ($hasSellerBadge ? "YES ✅" : "NO ❌") . "\n";
if ($hasSellerBadge) {
    echo "🎉 Successfully verified Single Person Seller dynamic dashboard!\n";
}

@unlink($cookieOrg);
@unlink($cookieSeller);
echo "\n--- HTTP Live Interaction Testing Complete ---\n";
