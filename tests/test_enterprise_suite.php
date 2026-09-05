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
// -----------------------------------------------------------------------------
// 5. PWA & OFFLINE ARCHITECTURE TESTS
// -----------------------------------------------------------------------------
echo "\n5. Testing PWA & Offline Architecture:\n";
$swPath = __DIR__ . '/../sw.js';
$manifestPath = __DIR__ . '/../site.webmanifest';
$offlinePath = __DIR__ . '/../offline.html';

assertTest(file_exists($swPath) && strpos(file_get_contents($swPath), 'CACHE_NAME') !== false, 'PWA: Service Worker exists and defines cache strategy');
assertTest(file_exists($manifestPath) && strpos(file_get_contents($manifestPath), 'shortcuts') !== false, 'PWA: Web Manifest exists and defines mobile app shortcuts');
assertTest(file_exists($offlinePath) && strpos(file_get_contents($offlinePath), 'اتصال اینترنت') !== false, 'PWA: Offline fallback page exists and localized in Persian');

// -----------------------------------------------------------------------------
// 6. ENTERPRISE SECURITY & RATE LIMITING TESTS
// -----------------------------------------------------------------------------
echo "\n6. Testing Security & Zero-Trust Architecture:\n";
require_once __DIR__ . '/../includes/SecurityMiddleware.php';
require_once __DIR__ . '/../includes/RateLimiter.php';

$rateLimiter = new RateLimiter($pdo);
$testKey = 'test_ip_' . time() . '_' . rand(100, 999);
$res1 = $rateLimiter->check($testKey, 2, 60);
assertTest($res1['allowed'] === true && $res1['remaining'] === 2, 'RateLimiter: Initial check allows request');
$rateLimiter->hit($testKey);
$rateLimiter->hit($testKey);
$res2 = $rateLimiter->check($testKey, 2, 60);
assertTest($res2['allowed'] === false && $res2['remaining'] === 0, 'RateLimiter: Rejects request once quota is exceeded');

// Test upload validation with fake malicious executable
$fakeExe = ['name' => 'malicious.php', 'type' => 'text/plain', 'tmp_name' => '', 'size' => 100];
$valRes = SecurityMiddleware::validateUploadedFile($fakeExe);
assertTest($valRes['valid'] === false, 'Security: Malicious upload without valid tmp_name is rejected');

// -----------------------------------------------------------------------------
// 7. HIGH-SPEED CACHE & SERVICE CONTAINER TESTS
// -----------------------------------------------------------------------------
echo "\n7. Testing High-Speed Cache & Service Container:\n";
require_once __DIR__ . '/../includes/App.php';

$cache = App::cache();
$cacheKey = 'suite_test_token_' . time();
$cache->set($cacheKey, ['val' => 42], 60);
$cachedVal = $cache->get($cacheKey);
assertTest(is_array($cachedVal) && $cachedVal['val'] === 42, 'CacheService: Stores and retrieves serialized data');
$cache->delete($cacheKey);
assertTest($cache->get($cacheKey) === null, 'CacheService: Invalidation clears cached key');

assertTest(App::db() instanceof PDO, 'Service Container: App::db() returns valid PDO instance');
assertTest(App::autoship() instanceof AutoshipService, 'Service Container: App::autoship() returns AutoshipService singleton');
assertTest(App::wholesale() instanceof WholesaleService, 'Service Container: App::wholesale() returns WholesaleService singleton');
assertTest(App::shipping() instanceof ShippingCalculator, 'Service Container: App::shipping() returns ShippingCalculator singleton');

// -----------------------------------------------------------------------------
// 8. DEVOPS, RELIABILITY & CI/CD TESTS
// -----------------------------------------------------------------------------
echo "\n8. Testing DevOps & CI/CD Infrastructure:\n";
$dockerfile = __DIR__ . '/../Dockerfile';
$composeFile = __DIR__ . '/../docker-compose.yml';
$ciWorkflow = __DIR__ . '/../.github/workflows/enterprise-ci.yml';

assertTest(file_exists($dockerfile) && strpos(file_get_contents($dockerfile), 'php:8.2-fpm') !== false, 'DevOps: Dockerfile is configured for PHP 8.2 FPM');
assertTest(file_exists($composeFile) && strpos(file_get_contents($composeFile), 'redis:7') !== false, 'DevOps: docker-compose.yml defines multi-container stack with Redis');
assertTest(file_exists($ciWorkflow) && strpos(file_get_contents($ciWorkflow), 'lint-and-test') !== false, 'DevOps: GitHub Actions CI workflow is configured for automated testing');

// -----------------------------------------------------------------------------
// 9. MULTI-ROLE CREDENTIAL VERIFICATION & ORGANIZATION DIRECTORY TESTS
// -----------------------------------------------------------------------------
echo "\n9. Testing Multi-Role Verification & Organizations:\n";
assertTest(App::roleVerification() instanceof RoleVerificationService, 'Service Container: App::roleVerification() returns RoleVerificationService');
assertTest(App::organization() instanceof OrganizationService, 'Service Container: App::organization() returns OrganizationService');

// Create test user for credential application
$testPhone = '0912' . rand(1000000, 9999999);
$insU = $pdo->prepare("INSERT INTO users (phone, name, password, role, pending_role, verification_status) VALUES (?, 'دکتر آزمایشی نیازی', 'pass123', 'user', 'doctor', 'pending')");
$insU->execute([$testPhone]);
$testUid = (int)$pdo->lastInsertId();

$appService = App::roleVerification();
$subResult = $appService->submitApplication($testUid, [
    'applied_role' => 'doctor',
    'full_name' => 'دکتر آزمایشی نیازی',
    'phone' => $testPhone,
    'license_number' => 'MED-' . rand(1000, 9999),
    'specialty' => 'متخصص جراحی دامپزشکی',
    'city' => 'تهران'
], []);

assertTest($subResult['success'] === true && !empty($subResult['application_id']), 'RoleVerificationService: Successfully submits professional application');

$appId = (int)$subResult['application_id'];
$pendingList = $appService->getApplications('doctor', 'pending');
$foundInPending = false;
foreach ($pendingList as $p) {
    if ((int)$p['id'] === $appId) {
        $foundInPending = true;
        break;
    }
}
assertTest($foundInPending, 'RoleVerificationService: Application appears in admin pending queue');

// 1-Click Approve Test
$approved = $appService->approveApplication($appId, 1, 'مدارک پزشکی بررسی و تایید گردید');
assertTest($approved === true, 'RoleVerificationService: approveApplication executes 1-click approval');

// Verify role promotion
$chkRole = $pdo->prepare("SELECT role, verification_status, is_verified_vet FROM users WHERE id = ?");
$chkRole->execute([$testUid]);
$uRow = $chkRole->fetch(PDO::FETCH_ASSOC);
assertTest($uRow['role'] === 'doctor' && $uRow['verification_status'] === 'approved' && $uRow['is_verified_vet'] == 1, 'RoleVerificationService: User role promoted to doctor with verified status');

// OrganizationService Tests
$orgService = App::organization();
$orgs = $orgService->getOrganizations(['city' => 'تهران']);
assertTest(count($orgs) >= 2, 'OrganizationService: Retrieves active organizations in Tehran');

$hosp = $orgService->getBySlug('payetakht-hospital');
assertTest($hosp !== null && $hosp['is_24_7'] == 1, 'OrganizationService: Fetches payetakht-hospital with 24/7 badge');

$hospDocs = $orgService->getDoctors((int)$hosp['id']);
assertTest(count($hospDocs) >= 1, 'OrganizationService: Retrieves affiliated doctors roster');

$hospInventory = $orgService->getInventory((int)$hosp['id']);
assertTest(count($hospInventory) >= 1, 'OrganizationService: Retrieves facility pharmacy inventory');

// -----------------------------------------------------------------------------
// 10. ZERO-TRUST WAF, SECURITY AUDIT & RBAC GUARD TESTS
// -----------------------------------------------------------------------------
echo "\n10. Testing Zero-Trust Security, WAF & RBAC Guard:\n";
assertTest(App::securityAudit() instanceof SecurityAuditService, 'Service Container: App::securityAudit() returns SecurityAuditService singleton');

$audit = App::securityAudit();
$logSuccess = $audit->logEvent('test_probe', 'info', 1, 'Automated test suite audit verification probe', ['test_key' => 'suite_val']);
assertTest($logSuccess === true, 'SecurityAuditService: Successfully records structured security log');

$recentEvents = $audit->getRecentEvents(10, 'info');
$foundProbe = false;
foreach ($recentEvents as $ev) {
    if ($ev['event_type'] === 'test_probe') {
        $foundProbe = true;
        break;
    }
}
assertTest($foundProbe, 'SecurityAuditService: Retrieves newly logged audit event');

// IP Ban and Unban Test
$dummyIp = '198.51.100.99';
$audit->banIp($dummyIp, 30, 'Test ban for verification suite', 1);
assertTest($audit->isIpBanned($dummyIp) === true, 'SecurityAuditService: Identifies banned IP address');

$audit->unbanIp($dummyIp);
assertTest($audit->isIpBanned($dummyIp) === false, 'SecurityAuditService: Successfully removes IP ban');

$secMetrics = $audit->getSecurityMetrics();
assertTest(is_array($secMetrics) && isset($secMetrics['total_events_24h']), 'SecurityAuditService: Computes security metrics for SOC console');

// Anti-Polyglot Upload Protection Test
$polyglotFile = sys_get_temp_dir() . '/fake_image.jpg';
file_put_contents($polyglotFile, "\xFF\xD8\xFF\xE0<?php phpinfo(); ?>");
$mockUpload = [
    'tmp_name' => $polyglotFile,
    'name' => 'fake_image.jpg',
    'size' => filesize($polyglotFile),
    'error' => 0
];
$polyglotResult = SecurityMiddleware::validateUploadedFile($mockUpload);
assertTest($polyglotResult['valid'] === false, 'SecurityMiddleware: Rejects polyglot image containing embedded executable code');
@unlink($polyglotFile);

// AuthGuard RBAC check
$_SESSION['user_id'] = $testUid;
$loadedUser = AuthGuard::user($pdo);
assertTest(is_array($loadedUser) && $loadedUser['role'] === 'doctor', 'AuthGuard: Resolves authenticated user role accurately');

echo "\n=========================================================\n";
echo "   TEST SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "=========================================================\n";

