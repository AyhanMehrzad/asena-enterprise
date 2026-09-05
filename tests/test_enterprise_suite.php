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

// =========================================================================
// 11. Testing Marketplace Escrow, Iran Post API & Weekly Payout Engine
// =========================================================================
echo "\n11. Testing Marketplace Escrow, Iran Post API & Weekly Payouts:\n";

$iranPost = App::iranPost();
$escrow = App::escrow();

assertTest($iranPost instanceof IranPostService, 'Service Container: App::iranPost() returns IranPostService singleton');
assertTest($escrow instanceof MarketplaceEscrowService, 'Service Container: App::escrow() returns MarketplaceEscrowService singleton');

// Test 24-digit barcode validation
$validBarcode = '100012345678901234567890';
$invalidBarcode = '123456';
assertTest($iranPost->isValidBarcode($validBarcode) === true, 'IranPostService: Validates authentic 24-digit barcode format');
assertTest($iranPost->isValidBarcode($invalidBarcode) === false, 'IranPostService: Rejects malformed tracking barcode');

// Test Tracking simulation events
$trackRes = $iranPost->trackBarcode($validBarcode);
assertTest($trackRes['success'] === true && count($trackRes['events']) >= 4, 'IranPostService: Generates full multi-stage transit timeline');
assertTest($trackRes['is_delivered'] === true, 'IranPostService: Correctly detects delivered parcel status');

// Test Sheba validation
$validSheba = 'IR120120000000001234567890';
$invalidSheba = 'IR1234';
$bankUpdateValid = $escrow->updateBankDetails($testUid, ['bank_sheba' => $validSheba, 'bank_name' => 'بانک سامان']);
$bankUpdateInvalid = $escrow->updateBankDetails($testUid, ['bank_sheba' => $invalidSheba]);
assertTest($bankUpdateValid['success'] === true, 'MarketplaceEscrowService: Accepts valid 24-digit Iranian Sheba (IR...)');
assertTest($bankUpdateInvalid['success'] === false, 'MarketplaceEscrowService: Rejects malformed Sheba number');

// Test Escrow Inflow for a test order
$testOrderStmt = $pdo->prepare("
    INSERT INTO orders (user_id, total_amount, status, post_tracking_code) 
    VALUES (:uid, 500000, 'shipped', :barcode)
");
$testOrderStmt->execute([
    'uid' => $testUid,
    'barcode' => $validBarcode
]);
$testOrderId = (int)$pdo->lastInsertId();

$itemStmt = $pdo->prepare("
    INSERT INTO order_items (order_id, product_id, seller_id, quantity, price_at_purchase, product_name_snapshot) 
    VALUES (:oid, 1, :sid, 2, 250000, 'غذای سگ رویال کنین')
");
$itemStmt->execute([
    'oid' => $testOrderId,
    'sid' => $testUid
]);

// Deposit order to escrow
$depositRes = $escrow->depositOrderToEscrow($testOrderId);
assertTest($depositRes['success'] === true, 'MarketplaceEscrowService: Successfully deposits paid order into corporate escrow');

// Check commission deduction (10% on 500,000 = 50,000 commission, 450,000 net)
assertTest($depositRes['total_commission'] === 50000, 'MarketplaceEscrowService: Accurately deducts 10% platform corporate commission');
assertTest($depositRes['total_seller_net'] === 450000, 'MarketplaceEscrowService: Holds remaining 90% net revenue in seller pending escrow');

// Verify seller wallet has pending balance
$walletBefore = $escrow->getSellerWallet($testUid);
assertTest((int)$walletBefore['balance_pending_escrow'] >= 450000, 'MarketplaceEscrowService: Reflects pending escrow balance in seller wallet');

// Simulate Post Delivery Sync
$syncPostRes = $iranPost->syncInTransitShipments();
assertTest($syncPostRes['success'] === true && $syncPostRes['delivered_count'] >= 1, 'IranPostService: Automatically syncs delivered status from Post API');

// Verify order marked delivered and 7-day maturation scheduled
$checkOrder = $pdo->prepare("SELECT status, delivered_at, escrow_status FROM orders WHERE id = ?");
$checkOrder->execute([$testOrderId]);
$orderRow = $checkOrder->fetch(PDO::FETCH_ASSOC);
assertTest($orderRow['status'] === 'delivered' && $orderRow['escrow_status'] === 'delivered_in_inspection', 'IranPostService: Order transitions to delivered with 7-day inspection window active');

// Test 7-Day Guarantee Matured Release
// Fast-forward payout_eligible_at to simulate 7 days elapsed
$pdo->prepare("
    UPDATE seller_escrow_ledger 
    SET payout_eligible_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) 
    WHERE order_id = ?
")->execute([$testOrderId]);

$releaseRes = $escrow->releaseMaturedEscrow();
assertTest($releaseRes['success'] === true && $releaseRes['released_count'] >= 1, 'MarketplaceEscrowService: Releases escrow funds to available balance after 7-day period expires');

$walletAfter = $escrow->getSellerWallet($testUid);
assertTest((int)$walletAfter['balance_available_for_payout'] >= 450000, 'MarketplaceEscrowService: Moves matured balance to balance_available_for_payout');

// Test Weekly Paya Batch Generation
$payoutRes = $escrow->generateWeeklyPayoutBatch(1);
assertTest($payoutRes['success'] === true, 'MarketplaceEscrowService: Generates weekly Central Bank Paya payout batch');
assertTest(str_contains($payoutRes['export_content'], 'IR120120000000001234567890'), 'MarketplaceEscrowService: Paya export file contains valid seller Sheba and amounts');
assertTest(str_starts_with($payoutRes['batch_code'], 'PAYA-'), 'MarketplaceEscrowService: Formats standard Paya batch tracking reference');

// Verify wallet balance moved to settled lifetime
$walletFinal = $escrow->getSellerWallet($testUid);
assertTest((int)$walletFinal['balance_available_for_payout'] === 0, 'MarketplaceEscrowService: Resets available payout balance to zero after batch dispatch');
assertTest((int)$walletFinal['balance_settled_lifetime'] >= 450000, 'MarketplaceEscrowService: Credits lifetime settled volume in seller wallet');

// =========================================================================
// 12. Testing Universal Request Logging, Cloudflare Anomaly & DDoS Defense
// =========================================================================
echo "\n12. Testing Universal Request Logging, Cloudflare Anomaly & DDoS Defense:\n";

$traffic = App::traffic();
assertTest($traffic instanceof TrafficMonitoringService, 'Service Container: App::traffic() returns TrafficMonitoringService singleton');

// Test Cloudflare IP and Header resolution
$_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.195';
$_SERVER['HTTP_CF_RAY'] = '8b4920aef1234567';
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'IR';
assertTest(TrafficMonitoringService::resolveClientIp() === '203.0.113.195', 'TrafficMonitoringService: Correctly resolves real client IP from Cloudflare header');
assertTest(TrafficMonitoringService::getCloudflareRayId() === '8b4920aef1234567', 'TrafficMonitoringService: Extracts Cloudflare Ray ID');
assertTest(TrafficMonitoringService::getCloudflareCountry() === 'IR', 'TrafficMonitoringService: Extracts Cloudflare country code');

// Test Sensitive Payload Redaction
$testPayload = [
    'username' => 'testuser',
    'password' => 'super_secret_123',
    'token' => 'jwt_secret_token',
    'card_number' => '6037991234567890',
    'public_note' => 'سلام سفارش عادی'
];
$redactedJson = TrafficMonitoringService::sanitizePayload($testPayload);
assertTest(!str_contains($redactedJson, 'super_secret_123'), 'TrafficMonitoringService: Redacts password from interaction logs');
assertTest(!str_contains($redactedJson, '6037991234567890'), 'TrafficMonitoringService: Redacts card number from interaction logs');
assertTest(str_contains($redactedJson, 'سلام سفارش عادی'), 'TrafficMonitoringService: Preserves non-sensitive request parameters');

// Test Malicious Scanner Probe Detection
$envProbe = TrafficMonitoringService::checkScannerProbe('/.env');
$wpProbe = TrafficMonitoringService::checkScannerProbe('/wp-login.php');
$shellProbe = TrafficMonitoringService::checkScannerProbe('/eval-stdin.php');
$normalUri = TrafficMonitoringService::checkScannerProbe('/products.php?category=pet-food');
assertTest(!empty($envProbe), 'TrafficMonitoringService: Detects environmental file (.env) scanner probe');
assertTest(!empty($wpProbe), 'TrafficMonitoringService: Detects CMS/WordPress scanner probe');
assertTest(!empty($shellProbe), 'TrafficMonitoringService: Detects webshell execution probe');
assertTest($normalUri === null, 'TrafficMonitoringService: Allows legitimate product and catalog URIs');

// Test Universal Request Logging
$logId1 = $traffic->logRequest([
    'ip_address' => '203.0.113.195',
    'user_id' => $testUid,
    'user_name' => 'دکتر امینی',
    'session_id' => 'sess_test_abc123',
    'action_type' => 'browse',
    'request_method' => 'GET',
    'request_uri' => '/products.php',
    'payload_summary' => '',
    'cf_ray' => '8b4920aef1234567',
    'cf_country' => 'IR',
    'user_agent' => 'Mozilla/5.0 ASENA-Test',
    'response_code' => 200,
    'is_suspicious' => 0
]);
$logId2 = $traffic->logRequest([
    'ip_address' => '203.0.113.195',
    'user_id' => $testUid,
    'user_name' => 'دکتر امینی',
    'session_id' => 'sess_test_abc123',
    'action_type' => 'checkout',
    'request_method' => 'POST',
    'request_uri' => '/checkout.php',
    'payload_summary' => '{"plan":"premium"}',
    'cf_ray' => '8b4920aef1234568',
    'cf_country' => 'IR',
    'user_agent' => 'Mozilla/5.0 ASENA-Test',
    'response_code' => 200,
    'is_suspicious' => 0
]);
assertTest($logId1 > 0 && $logId2 > 0, 'TrafficMonitoringService: Successfully records universal request interaction log');

// Test User / IP Request Journey Investigation ("what other requests has he given")
$ipHistory = $traffic->getRequestHistoryByIp('203.0.113.195');
assertTest(count($ipHistory) >= 2, 'TrafficMonitoringService: Retrieves chronological request journey by IP address');
$userHistory = $traffic->getRequestHistoryByUser($testUid);
assertTest(count($userHistory) >= 2, 'TrafficMonitoringService: Retrieves complete request trajectory by User ID');

// Test Automated Time-Based IP Restriction (DDoS / Anomaly Ban)
$testAttackerIp = '198.51.100.222';
$restrictOk = $traffic->autoRestrictIp($testAttackerIp, 15, 'ddos_flood', 'حمله سیل‌آسای لایه ۷ (DDoS Test)', 'CF-RAY-BURST');
assertTest($restrictOk === true, 'TrafficMonitoringService: Imposes automated time-based restriction on attacking IP');

$remainingBanSec = $traffic->getBanRemainingSeconds($testAttackerIp);
assertTest($remainingBanSec > 800, 'TrafficMonitoringService: Enforces remaining ban countdown timer');

// Test Unban
$unbanOk = $audit->unbanIp($testAttackerIp);
assertTest($unbanOk === true, 'TrafficMonitoringService: Successfully removes temporary IP restriction');
assertTest($traffic->getBanRemainingSeconds($testAttackerIp) === 0, 'TrafficMonitoringService: Confirms zero remaining ban duration post-unban');

// 13. Testing Platform Resilience, CSRF, SMS Sandbox & Error Pages
echo "\n13. Testing Platform Resilience, CSRF, SMS Sandbox & Error Pages:\n";

// CSRF tokens
$csrfToken = SecurityMiddleware::generateCsrfToken();
assertTest(!empty($csrfToken) && strlen($csrfToken) === 64, 'CSRF: Generates secure 64-character token');
assertTest(SecurityMiddleware::validateCsrfToken($csrfToken) === true, 'CSRF: Successfully validates matching CSRF token');
assertTest(SecurityMiddleware::validateCsrfToken('tampered_token_value_xyz') === false, 'CSRF: Rejects forged/tampered CSRF token');

// SMS Sandbox Mock Delivery
$smsService = new SmsService();
$smsResult = $smsService->sendPatternRequest('09120000000', 12345, ['998877'], 'OTP_TEST');
assertTest($smsResult === true, 'SmsService: Mock/Sandbox mode executes without external gateway latency');

// Verify mock delivery log in DB
$checkSmsLog = $pdo->prepare("SELECT * FROM sms_delivery_logs WHERE phone = ? ORDER BY id DESC LIMIT 1");
$checkSmsLog->execute(['09120000000']);
$smsLogEntry = $checkSmsLog->fetch(PDO::FETCH_ASSOC);
assertTest($smsLogEntry !== false && $smsLogEntry['body_id'] == 12345, 'SmsService: Successfully records SMS dispatch history in database');

// Error Pages & .htaccess
assertTest(file_exists(__DIR__ . '/../404.php'), 'Resilience: Custom branded 404 error page exists');
assertTest(file_exists(__DIR__ . '/../500.php'), 'Resilience: Custom branded 500 error page exists');
$htaccessContent = file_get_contents(__DIR__ . '/../.htaccess');
assertTest(strpos($htaccessContent, 'ErrorDocument 404 /404.php') !== false, 'Resilience: .htaccess routes 404 errors to custom Persian page');
assertTest(strpos($htaccessContent, 'ErrorDocument 500 /500.php') !== false, 'Resilience: .htaccess routes 500 errors to custom Persian page');

echo "\n=========================================================\n";
echo "   TEST SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "=========================================================\n";

