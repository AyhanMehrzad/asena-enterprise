<?php
/**
 * ASENA Enterprise - Comprehensive Verification Test Suite
 * Validating: Chewy (Autoship & Pet Passport), Amazon (Order Lifecycle & Recommendations),
 *             Alibaba (Wholesale Tiers & RFQ), Digikala (Shipping Calculator & Tax Invoicing)
 */

echo "=========================================================\n";
echo "    ASENA ENTERPRISE - AUTOMATED VERIFICATION SUITE      \n";
echo "=========================================================\n\n";

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/AutoshipService.php';
require_once __DIR__ . '/../includes/PetPassportService.php';
require_once __DIR__ . '/../includes/OrderLifecycleService.php';
require_once __DIR__ . '/../includes/RecommendationService.php';
require_once __DIR__ . '/../includes/WholesaleService.php';
require_once __DIR__ . '/../includes/ShippingCalculator.php';
require_once __DIR__ . '/../includes/FlashSaleService.php';

global $pdo;
$passedTests = 0;
$totalTests = 0;

function assertTest($condition, $testName) {
    global $passedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $testName\n";
    } else {
        echo "  [FAIL] $testName\n";
    }
}

// -----------------------------------------------------------------------------
// 1. CHEWY.COM TESTS: Autoship & Pet Health Passport
// -----------------------------------------------------------------------------
echo "1. Testing Chewy.com Benchmark Services:\n";

// 1.1 Autoship frequency mapping
assertTest(AutoshipService::frequencyToDays('1_week') === 7, 'Autoship: 1_week maps to 7 days');
assertTest(AutoshipService::frequencyToDays('2_weeks') === 14, 'Autoship: 2_weeks maps to 14 days');
assertTest(AutoshipService::frequencyToDays('4_weeks') === 28, 'Autoship: 4_weeks maps to 28 days');

// 1.2 Pet Dosage Calculator
$dosageDewormer = PetPassportService::calculateDosage('dewormer', 20.0, 'dog');
assertTest(!empty($dosageDewormer['dosage']) && strpos($dosageDewormer['dosage'], '2') !== false, 'Pet Passport: Dewormer dosage for 20kg dog is 2 tablets');

$dosageFlea = PetPassportService::calculateDosage('flea_tick', 15.0, 'dog');
assertTest(!empty($dosageFlea['dosage']) && strpos($dosageFlea['dosage'], 'متوسط') !== false, 'Pet Passport: Flea/Tick dosage categorizes 15kg as medium band');

// 1.3 Pet Health Record Insertion
$petService = new PetPassportService($pdo);
// Get a valid user
$userStmt = $pdo->query("SELECT id FROM users LIMIT 1");
$testUserId = (int)$userStmt->fetchColumn();

if ($testUserId > 0) {
    $petId = $petService->savePet($testUserId, [
        'pet_name'           => 'مکس (تست خودکار)',
        'species'            => 'dog',
        'breed'              => 'Golden Retriever',
        'gender'             => 'male',
        'birth_date'         => '2023-01-15',
        'weight_kg'          => 28.5,
        'microchip_id'       => 'IR-982000451',
        'allergies'          => 'حساسیت به پروتئین مرغ',
        'chronic_conditions' => 'ندارد',
        'rabies_tag_num'     => 'RAB-2024-88',
    ]);
    assertTest($petId > 0, "Pet Passport: Successfully created pet profile (ID: $petId)");

    $vaccineId = $petService->addVaccination($petId, [
        'vaccine_name'      => 'هپاتیت و هاری (Rabies + DHPPi)',
        'administered_date' => date('Y-m-d'),
        'next_due_date'     => date('Y-m-d', strtotime('+365 days')),
        'vet_name'          => 'دکتر مهرزاد',
        'clinic_name'       => 'بیمارستان دامپزشکی آسنا',
        'batch_number'      => 'BATCH-9981'
    ]);
    assertTest($vaccineId > 0, "Pet Passport: Successfully added vaccination record (ID: $vaccineId)");
}

// -----------------------------------------------------------------------------
// 2. AMAZON.COM TESTS: Fulfillment State Machine & Recommendations
// -----------------------------------------------------------------------------
echo "\n2. Testing Amazon.com Benchmark Services:\n";

// 2.1 Order Lifecycle Status Meta
$meta = OrderLifecycleService::getStatusMeta('confirmed');
assertTest($meta['title'] === 'تأیید انبارداری' && $meta['step'] === 3, 'Order Lifecycle: Status meta for confirmed has step 3');

$allowedNext = OrderLifecycleService::getAllowedNextStatuses('picking');
assertTest(in_array('packed', $allowedNext), 'Order Lifecycle: picking can transition to packed');

// 2.2 Frequently Bought Together Bundle Calculation
$recoService = new RecommendationService($pdo);
$bundle = RecommendationService::calculateBundle(
    ['price' => 200000, 'discount_price' => 180000],
    [['price' => 100000, 'discount_price' => 90000]],
    5.0
);
assertTest($bundle['regular_total'] === 270000, 'Recommendations: Bundle regular total is 270,000 Toman');
assertTest($bundle['bundle_total'] === 256500, 'Recommendations: 5% bundle discount gives 256,500 Toman');
assertTest($bundle['savings'] === 13500, 'Recommendations: Savings is 13,500 Toman');

// -----------------------------------------------------------------------------
// 3. ALIBABA.COM TESTS: Wholesale Tiered Pricing & RFQ
// -----------------------------------------------------------------------------
echo "\n3. Testing Alibaba.com Benchmark Services:\n";

$wholesale = new WholesaleService($pdo);
// Insert a sample volume pricing tier for product #1 if none exist
$tierStmt = $pdo->prepare("SELECT COUNT(*) FROM product_price_tiers WHERE product_id = 1");
$tierStmt->execute();
if ($tierStmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO product_price_tiers (product_id, is_pharmacy, min_qty, max_qty, discount_percent, unit_price) VALUES (1, 0, 10, 49, 10.0, 180000)")->execute();
    $pdo->prepare("INSERT INTO product_price_tiers (product_id, is_pharmacy, min_qty, max_qty, discount_percent, unit_price) VALUES (1, 0, 50, NULL, 20.0, 160000)")->execute();
}

$tierCheckLow = $wholesale->getTieredUnitPrice(1, false, 5, 200000);
assertTest($tierCheckLow['unit_price'] === 200000 && !$tierCheckLow['is_tiered'], 'Wholesale: Quantity 5 uses base price (200,000 Toman)');

$tierCheckBulk = $wholesale->getTieredUnitPrice(1, false, 25, 200000);
assertTest($tierCheckBulk['unit_price'] === 180000 && $tierCheckBulk['is_tiered'], 'Wholesale: Quantity 25 gets volume tier price (180,000 Toman, 10% off)');

$tierCheckSuperBulk = $wholesale->getTieredUnitPrice(1, false, 100, 200000);
assertTest($tierCheckSuperBulk['unit_price'] === 160000 && $tierCheckSuperBulk['is_tiered'], 'Wholesale: Quantity 100 gets top tier price (160,000 Toman, 20% off)');

// -----------------------------------------------------------------------------
// 4. DIGIKALA.COM TESTS: Logistics Rate Calculator & Flash Sales
// -----------------------------------------------------------------------------
echo "\n4. Testing Digikala.com Benchmark Services:\n";

$shipCalc = new ShippingCalculator($pdo);
$postPishtaz = $shipCalc->calculate('pishtaz', 'تهران', 800, false);
assertTest($postPishtaz['cost'] === 48000, 'Shipping: Intra-province Post Pishtaz for 800g is 48,000 Toman');

$postInter = $shipCalc->calculate('pishtaz', 'اصفهان', 800, false);
assertTest($postInter['cost'] === 70000, 'Shipping: Inter-provincial Post Pishtaz adds surcharge (70,000 Toman)');

$tipaxHeavy = $shipCalc->calculate('tipax', 'تهران', 2500, false);
assertTest($tipaxHeavy['cost'] > 65000, 'Shipping: Tipax extra weight scales cost accurately');

$coldChain = $shipCalc->calculate('cold_chain_express', 'تهران', 1000, true);
assertTest($coldChain['requires_cold_chain'] === true && $coldChain['cost'] >= 120000, 'Shipping: Cold chain thermal shipping applied correctly');

// Flash sale check
$flashService = new FlashSaleService($pdo);
// Insert a sample flash sale deal if none exist
$fStmt = $pdo->query("SELECT COUNT(*) FROM flash_sales WHERE is_active = 1");
if ($fStmt->fetchColumn() == 0) {
    $pdo->prepare("
        INSERT INTO flash_sales (product_id, is_pharmacy, special_price, stock_quota, claimed_count, starts_at, ends_at, badge_text, is_active)
        VALUES (1, 0, 149000, 20, 7, NOW(), DATE_ADD(NOW(), INTERVAL 48 HOUR), 'پیشنهاد شگفت‌انگیز', 1)
    ")->execute();
}
$deals = $flashService->getActiveFlashSales(5);
assertTest(count($deals) > 0, 'Flash Sales: Retrieved active flash sale deal');
if (!empty($deals[0])) {
    assertTest($deals[0]['progress_percent'] === 35, 'Flash Sales: Claimed 7 of 20 correctly calculated 35% progress');
}

echo "\n=========================================================\n";
echo "   TEST SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "=========================================================\n";
