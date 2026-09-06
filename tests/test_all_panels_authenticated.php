<?php
/**
 * Comprehensive Authenticated Panels & Roles Integration Test Suite
 */

function curlLogin($phone, $password) {
    $cookieFile = tempnam(sys_get_temp_dir(), 'cook_' . md5($phone));
    
    // First get CSRF token if any
    $ch = curl_init('http://localhost/asena/asena-enterprise/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $body = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $matches);
    $csrf = $matches[1] ?? '';

    // Post credentials
    $ch = curl_init('http://localhost/asena/asena-enterprise/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'mode' => 'login',
        'phone' => $phone,
        'password' => $password,
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return ['cookie' => $cookieFile, 'final_url' => $url, 'body' => $res];
}

function curlGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'url' => $finalUrl];
}

echo "===============================================================\n";
echo "  ASENA ENTERPRISE - MULTI-ROLE & PANELS VERIFICATION SUITE   \n";
echo "===============================================================\n\n";

$suite = [
    [
        'role' => 'doctor',
        'phone' => '09129972056',
        'expected_landing' => 'doctor/index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/doctor/index.php',
                'name' => 'Doctor Dashboard',
                'expected' => ['پنل پزشکان', 'نوبت‌ها و تقویم روزانه', 'bg-tertiary'],
                'forbidden' => ['درخواست تسویه', 'شماره شبا جهت تسویه', 'Fatal error', 'Parse error']
            ]
        ]
    ],
    [
        'role' => 'pharmacist',
        'phone' => '09120000004',
        'expected_landing' => 'pharmacist/index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/pharmacist/index.php',
                'name' => 'Pharmacist Professional Panel',
                'expected' => ['پنل تخصصی داروساز', 'نسخه‌های در انتظار', 'ثبت داروی جدید', 'bg-tertiary'],
                'forbidden' => ['برداشت وجه شخصی', 'درخواست تسویه حساب شخصی', 'Fatal error', 'Parse error']
            ]
        ]
    ],
    [
        'role' => 'seller',
        'phone' => '09120000003',
        'expected_landing' => 'seller/index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/seller/index.php',
                'name' => 'Petshop & Seller Panel',
                'expected' => ['پنل فروشندگان و پت‌شاپ', 'سفارشات و ارسال کالا', 'کیف پول امانی و تسویه پایا', 'ویترین و انبار محصولات'],
                'forbidden' => ['پزشک همکار', 'نسخه الکترونیک', 'نوبت‌دهی کلینیک', 'Fatal error', 'Parse error']
            ]
        ]
    ],
    [
        'role' => 'organization',
        'phone' => '09122193637',
        'expected_landing' => 'organization/index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/organization/index.php',
                'name' => 'Organization Main Dashboard',
                'expected' => ['پنل مرکز درمانی', 'مدیریت و پیشخوان مرکز درمانی', 'پزشکان و کادر همکار', 'bg-tertiary'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/organization/appointments.php',
                'name' => 'Organization Appointments',
                'expected' => ['نوبت‌دهی و مراجعین کلینیک'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/organization/doctors.php',
                'name' => 'Organization Doctors & Staff',
                'expected' => ['پزشکان، داروسازان و گرومرها'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/organization/wallet.php',
                'name' => 'Organization Paya Escrow Wallet',
                'expected' => ['مدیریت مالی و تسویه (پایا)'],
                'forbidden' => ['Fatal error', 'Parse error']
            ]
        ]
    ],
    [
        'role' => 'admin',
        'phone' => '09123456789',
        'expected_landing' => 'admin/index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/admin/index.php',
                'name' => 'Super Admin Macro Dashboard',
                'expected' => ['مراکز درمانی و بیمارستان‌ها', 'پزشکان و متخصصین سراسری', 'فروشندگان مستقل و پت‌شاپ‌ها', 'bg-tertiary'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/admin/organizations.php',
                'name' => 'Super Admin Organizations Directory',
                'expected' => ['شبکه مراکز درمانی و بیمارستان‌ها', 'بررسی اکوسیستم', 'کل مراکز و کلینیک‌ها'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/admin/doctors.php',
                'name' => 'Super Admin Nationwide Doctors Directory',
                'expected' => ['فهرست جامع پزشکان و تعاملات درمانی', 'تعاملات بیماران', 'کل پزشکان فعال'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/admin/sellers.php',
                'name' => 'Super Admin Single Sellers Directory',
                'expected' => ['فهرست فروشندگان مستقل و پت‌شاپ‌ها', 'کل پت‌شاپ‌های فعال', 'موجودی آماده تسویه پایا'],
                'forbidden' => ['Fatal error', 'Parse error']
            ],
            [
                'url' => 'http://localhost/asena/asena-enterprise/admin/payouts.php',
                'name' => 'Super Admin Central Bank Paya Payouts',
                'expected' => ['تسویه حساب بازارگاه و وجوه امانی', 'حواله پایا'],
                'forbidden' => ['Fatal error', 'Parse error']
            ]
        ]
    ],
    [
        'role' => 'user',
        'phone' => '09000000001',
        'expected_landing' => 'index.php',
        'pages' => [
            [
                'url' => 'http://localhost/asena/asena-enterprise/profile.php',
                'name' => 'User Profile & Digital Wallet',
                'expected' => ['کیف پول دیجیتال اعتباری', 'شارژ کیف پول'],
                'forbidden' => ['شماره شبا جهت تسویه', 'برداشت وجه پایا', 'Fatal error', 'Parse error']
            ]
        ]
    ]
];

$allOk = true;

foreach ($suite as $testCase) {
    echo "▶ Authenticating Role [{$testCase['role']}] via {$testCase['phone']}...\n";
    $login = curlLogin($testCase['phone'], 'Asena1234!');
    echo "  → Landed on: {$login['final_url']}\n";

    if (strpos($login['final_url'], $testCase['expected_landing']) === false) {
        echo "  ⚠️ Warning: Landed URL did not match expected '{$testCase['expected_landing']}'\n";
    }

    foreach ($testCase['pages'] as $p) {
        echo "  Checking [{$p['name']}] -> {$p['url']}\n";
        $res = curlGet($p['url'], $login['cookie']);
        echo "    HTTP: {$res['code']}";

        if ($res['code'] !== 200) {
            echo " ❌ [Expected 200, Got {$res['code']}]\n";
            $allOk = false;
        } else {
            echo " ✅ [200 OK]\n";
        }

        // Expected strings
        foreach ($p['expected'] as $exp) {
            if (strpos($res['body'], $exp) === false) {
                echo "    ❌ Missing expected: '{$exp}'\n";
                $allOk = false;
            } else {
                echo "    ✅ Found expected: '{$exp}'\n";
            }
        }

        // Forbidden strings
        foreach ($p['forbidden'] as $forb) {
            if (strpos($res['body'], $forb) !== false) {
                echo "    ❌ Found FORBIDDEN element: '{$forb}'\n";
                $allOk = false;
            } else {
                echo "    ✅ Clean (Forbidden absent): '{$forb}'\n";
            }
        }
    }
    echo "\n";
}

if ($allOk) {
    echo "===============================================================\n";
    echo "  🎉 ALL TESTS PASSED WITH 100% SUCCESS ACROSS ALL ROLES!      \n";
    echo "===============================================================\n";
} else {
    echo "===============================================================\n";
    echo "  ⚠️ FAILURES DETECTED! CHECK LOGS ABOVE.                      \n";
    echo "===============================================================\n";
}
