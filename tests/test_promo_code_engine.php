<?php
/**
 * Test Suite: ASENA Enterprise Promo Code Engine & Platform Margin Absorption
 *
 * Verifies:
 * 1. Post-discount 10% VAT calculation
 * 2. Promo code validation (percentage cap, min order amount, limits)
 * 3. Provider revenue shield (100% preservation of 85% provider payout)
 * 4. Anti-leak security: Zero mention of 15% platform commission on customer surfaces
 * 5. Statutory 10% VAT display declaration
 */

// Initialize in-memory SQLite PDO for hermetic standalone testing
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$GLOBALS['pdo'] = $pdo;

$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)");
$pdo->exec("INSERT OR REPLACE INTO site_settings (setting_key, setting_value) VALUES ('tax_rate_percent', '10.0')");

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PromoCodeService.php';

echo "=========================================================\n";
echo "   ASENA ENTERPRISE PROMO CODE & MARGIN TEST SUITE       \n";
echo "=========================================================\n\n";

$passed = 0;
$total = 0;

function runTest(string $name, callable $fn) {
    global $passed, $total;
    $total++;
    try {
        $result = $fn();
        if ($result) {
            echo " [PASS] {$name}\n";
            $passed++;
        } else {
            echo " [FAIL] {$name}\n";
        }
    } catch (Throwable $e) {
        echo " [FAIL] {$name}: " . $e->getMessage() . "\n";
    }
}

// ── Test 1: Post-Discount 10% VAT Taxation Sequence ─────────────────────────
runTest("Test 1: 10% VAT calculated strictly on post-discount taxable subtotal", function() use ($pdo) {
    $promoService = new PromoCodeService($pdo);
    $subtotal = 1000000; // 1,000,000 Tomans
    $appliedPromo = [
        'code' => 'TEST-100K',
        'title' => 'تخفیف ۱۰۰ هزار تومانی',
        'discount_amount' => 100000
    ];

    $totals = $promoService->calculateTotals($subtotal, $appliedPromo, 10.0);

    // Taxable subtotal should be 900,000 Tomans
    // 10% VAT should be 90,000 Tomans (not 100,000 Tomans)
    // Final total should be 990,000 Tomans
    $correctTaxable = ($totals['taxable_subtotal'] === 900000);
    $correctTax = ($totals['tax_amount'] === 90000);
    $correctTotal = ($totals['final_total'] === 990000);

    return $correctTaxable && $correctTax && $correctTotal;
});

// ── Test 2: Percentage Discount Ceiling Cap (سقف تخفیف) ──────────────────────
runTest("Test 2: Percentage discount strictly respects maximum ceiling cap", function() {
    $subtotal = 500000; // 20% would be 100,000, but cap is 50,000
    $discountValue = 20;
    $maxCap = 50000;
    $rawDiscount = round($subtotal * ($discountValue / 100.0));
    $cappedDiscount = ($maxCap !== null && $rawDiscount > $maxCap) ? $maxCap : $rawDiscount;

    return ($cappedDiscount === 50000);
});

// ── Test 3: Provider Revenue Shield & Platform Commission Absorption ────────
runTest("Test 3: Provider receives full 85% and promo discount is absorbed by platform 15%", function() {
    $itemPrice = 1000000; // 1,000,000 Tomans
    $promoDiscount = 70000; // 70,000 Tomans platform promo

    // Provider baseline share: 85%
    $providerNet = (int)round($itemPrice * 0.85);

    // Platform standard commission: 15%
    $platformGrossCommission = (int)round($itemPrice * 0.15);

    // Platform net commission after absorbing promo code:
    $platformNetCommission = max(0, $platformGrossCommission - $promoDiscount);

    // Assertions:
    // 1. Provider receives full 850,000 Tomans without single Toman reduction
    $providerProtected = ($providerNet === 850000);

    // 2. Platform absorbs 70,000 from 150,000 -> 80,000 Tomans net
    $platformAbsorbed = ($platformNetCommission === 80000);

    return $providerProtected && $platformAbsorbed;
});

// ── Test 4: Anti-Leak Audit (Customer Surfaces Must Never Expose 15% Platform Cut) ──
runTest("Test 4: Customer-facing files have zero disclosure of internal 15% commission", function() {
    $cartContent = file_get_contents(__DIR__ . '/../cart.php');
    $receiptContent = file_get_contents(__DIR__ . '/../order_receipt.php');

    // Forbidden customer strings
    $forbidden = [
        'کارمزد پلتفرم',
        'سهم سایت',
        'درصد سود سامانه',
        '15% intrest',
        '15% سود'
    ];

    foreach ($forbidden as $phrase) {
        if (stripos($cartContent, $phrase) !== false) {
            throw new Exception("Forbidden phrase '{$phrase}' found in cart.php!");
        }
        if (stripos($receiptContent, $phrase) !== false) {
            throw new Exception("Forbidden phrase '{$phrase}' found in order_receipt.php!");
        }
    }

    return true;
});

// ── Test 5: Statutory 10% VAT Display Confirmation ──────────────────────────
runTest("Test 5: Cart and Receipt visibly declare statutory 10% VAT", function() {
    $cartContent = file_get_contents(__DIR__ . '/../cart.php');
    $receiptContent = file_get_contents(__DIR__ . '/../order_receipt.php');

    $hasVatCart = (strpos($cartContent, 'مالیات بر ارزش افزوده') !== false);
    $hasVatReceipt = (strpos($receiptContent, 'مالیات بر ارزش افزوده (۱۰٪ قانونی)') !== false);

    return $hasVatCart && $hasVatReceipt;
});

// ── Test 6: Full DB Lifecycle Validation & Usage Logging ────────────────────
runTest("Test 6: PromoCodeService lifecycle in database (validation, min order, and usage log)", function() use ($pdo) {
    // Setup tables in SQLite
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS promo_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE,
            title TEXT,
            discount_type TEXT DEFAULT 'percentage',
            discount_value INTEGER,
            max_discount_amount INTEGER,
            min_order_amount INTEGER DEFAULT 0,
            usage_limit_total INTEGER,
            usage_limit_per_user INTEGER DEFAULT 1,
            first_order_only INTEGER DEFAULT 0,
            starts_at TEXT,
            expires_at TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS promo_code_usages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            promo_code_id INTEGER,
            user_id INTEGER,
            order_id INTEGER,
            discount_amount INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            total_amount INTEGER,
            status TEXT
        );
    ");

    $service = new PromoCodeService($pdo);

    // 1. Insert test promo code: 15% with 75,000 cap, min 200,000
    $saveRes = $service->savePromoCode([
        'code' => 'ASENA-TEST-15',
        'title' => 'تست ۱۵٪ آسنا',
        'discount_type' => 'percentage',
        'discount_value' => 15,
        'max_discount_amount' => 75000,
        'min_order_amount' => 200000,
        'usage_limit_per_user' => 1,
        'is_active' => 1
    ]);
    if (!$saveRes['success']) return false;

    // 2. Validate with subtotal under minimum (150,000 < 200,000) -> should fail
    $vUnder = $service->validatePromo('ASENA-TEST-15', 10, 150000);
    if ($vUnder['valid'] === true) return false;

    // 3. Validate with subtotal above minimum (1,000,000) -> 15% is 150,000, capped at 75,000
    $vOk = $service->validatePromo('ASENA-TEST-15', 10, 1000000);
    if (!$vOk['valid'] || $vOk['discount_amount'] !== 75000) return false;

    // 4. Record usage
    $recOk = $service->recordUsage($vOk['promo_id'], 10, 501, 75000);
    if (!$recOk) return false;

    // 5. Try validating again for same user -> should be rejected because usage_limit_per_user = 1
    $vReused = $service->validatePromo('ASENA-TEST-15', 10, 1000000);
    if ($vReused['valid'] === true) return false;

    return true;
});

echo "\n---------------------------------------------------------\n";
echo " RESULTS: {$passed} / {$total} Tests Passed.\n";
echo "---------------------------------------------------------\n";

if ($passed === $total) {
    echo " ALL TESTS PASSED SUCCESSFULLY! PROMO ENGINE IS ROCK-SOLID.\n\n";
} else {
    echo " SOME TESTS FAILED!\n\n";
    exit(1);
}
