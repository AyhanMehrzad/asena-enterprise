<?php
require_once 'includes/db.php';
require_once 'includes/AuthGuard.php';

$user = AuthGuard::requireAuth();
$user_id = (int)$user['id'];
$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Suppress bulky marketing footer and notification banner in operational profile workspace
$hideMarketingFooter = true;
$hideMarketingHeader = true;

$userRole = $user['role'] ?? 'user';

// Determine if current view should be Seller Mode
// Automatically true for seller, organization, organization_manager; or if view=seller in query
$isSeller = in_array($userRole, ['seller', 'organization', 'organization_manager']) 
    || (isset($_GET['view']) && $_GET['view'] === 'seller');

if (isset($_GET['view']) && $_GET['view'] === 'customer') {
    $isSeller = false;
}

// Fetch seller-specific data if in seller mode
$sellerOrders = [];
$sellerPendingCount = 0;
$sellerTotalRevenue = 0;
$sellerProducts = [];

if ($isSeller) {
    // 1. Fetch orders containing items sold by this seller
    $sellerOrdersStmt = $pdo->prepare("
        SELECT oi.*, o.id as order_id, o.shipping_address, o.status as order_status, o.created_at as order_created_at,
               o.post_tracking_code, o.carrier_name, o.escrow_status,
               u.name as customer_name, u.phone as customer_phone
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE oi.seller_id = ? OR ? IN ('admin', 'organization', 'organization_manager')
        ORDER BY o.id DESC
        LIMIT 30
    ");
    $sellerOrdersStmt->execute([$user_id, $userRole]);
    $sellerOrders = $sellerOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sellerOrders as $so) {
        if (in_array($so['order_status'], ['pending_payment', 'processing'])) {
            $sellerPendingCount++;
        }
        $sellerTotalRevenue += (float)($so['price_at_purchase'] * $so['quantity']);
    }

    // 2. Fetch products managed by this seller
    $sellerProdStmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE seller_id = ? OR (? IN ('admin', 'organization', 'organization_manager') AND (seller_id IS NULL OR seller_id = ?))
        ORDER BY id DESC 
        LIMIT 40
    ");
    $sellerProdStmt->execute([$user_id, $userRole, $user_id]);
    $sellerProducts = $sellerProdStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch user pets
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pet documents
$stmt = $pdo->prepare("SELECT d.*, COALESCE(p.name, 'عمومی') as pet_name FROM pet_documents d LEFT JOIN user_pets p ON d.pet_id = p.id WHERE d.user_id = ? ORDER BY d.uploaded_at DESC");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, d.specialty as doctor_specialty, d.image_url as doctor_image 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 4
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order history
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Financial Transcript Aggregations (Customer Purchases & Savings) ───────────
$userFinancialTranscript = [
    'week' => ['total_spent' => 0, 'total_discount' => 0, 'count' => 0],
    'month' => ['total_spent' => 0, 'total_discount' => 0, 'count' => 0],
    'six_months' => ['total_spent' => 0, 'total_discount' => 0, 'count' => 0],
    'year' => ['total_spent' => 0, 'total_discount' => 0, 'count' => 0],
];
try {
    $finStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN total_amount ELSE 0 END) as week_spent,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN COALESCE(discount_amount, 0) ELSE 0 END) as week_discount,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total_amount ELSE 0 END) as month_spent,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN COALESCE(discount_amount, 0) ELSE 0 END) as month_discount,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN total_amount ELSE 0 END) as six_months_spent,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN COALESCE(discount_amount, 0) ELSE 0 END) as six_months_discount,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN 1 END) as six_months_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN total_amount ELSE 0 END) as year_spent,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN COALESCE(discount_amount, 0) ELSE 0 END) as year_discount,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN 1 END) as year_count
        FROM orders
        WHERE user_id = ? AND status NOT IN ('cancelled', 'failed')
    ");
    $finStmt->execute([$user_id]);
    $finRow = $finStmt->fetch(PDO::FETCH_ASSOC);
    if ($finRow) {
        $userFinancialTranscript['week'] = [
            'total_spent' => (int)($finRow['week_spent'] ?? 0),
            'total_discount' => (int)($finRow['week_discount'] ?? 0),
            'count' => (int)($finRow['week_count'] ?? 0)
        ];
        $userFinancialTranscript['month'] = [
            'total_spent' => (int)($finRow['month_spent'] ?? 0),
            'total_discount' => (int)($finRow['month_discount'] ?? 0),
            'count' => (int)($finRow['month_count'] ?? 0)
        ];
        $userFinancialTranscript['six_months'] = [
            'total_spent' => (int)($finRow['six_months_spent'] ?? 0),
            'total_discount' => (int)($finRow['six_months_discount'] ?? 0),
            'count' => (int)($finRow['six_months_count'] ?? 0)
        ];
        $userFinancialTranscript['year'] = [
            'total_spent' => (int)($finRow['year_spent'] ?? 0),
            'total_discount' => (int)($finRow['year_discount'] ?? 0),
            'count' => (int)($finRow['year_count'] ?? 0)
        ];
    }
} catch (Exception $e) {}


// Fetch user subscriptions
$stmt = $pdo->prepare("
    SELECT * FROM user_subscriptions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user prescriptions
$prescriptions = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty, pt.name as pet_name
        FROM prescriptions p
        LEFT JOIN doctors d ON p.doctor_id = d.id
        LEFT JOIN user_pets pt ON p.pet_id = pt.id
        WHERE p.user_id = ?
        ORDER BY p.id DESC
    ");
    $stmt->execute([$user_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("[Profile] Prescriptions query error: " . $e->getMessage());
    $prescriptions = [];
}

// Attach deliveries to each subscription
if (!empty($user_subscriptions)) {
    $sub_ids   = array_column($user_subscriptions, 'id');
    $ph        = implode(',', array_fill(0, count($sub_ids), '?'));
    $delStmt   = $pdo->prepare("SELECT * FROM subscription_deliveries WHERE subscription_id IN ($ph) ORDER BY delivery_month ASC");
    $delStmt->execute($sub_ids);
    $all_dels  = $delStmt->fetchAll(PDO::FETCH_ASSOC);
    $dels_by_sub = [];
    foreach ($all_dels as $del) {
        $dels_by_sub[$del['subscription_id']][] = $del;
    }
    
    foreach ($user_subscriptions as &$sub) {
        $sub['deliveries'] = $dels_by_sub[$sub['id']] ?? [];
    }
}


// Attach order items to each order
if (!empty($orders)) {
    $order_ids   = array_column($orders, 'id');
    $order_ph    = implode(',', array_fill(0, count($order_ids), '?'));
    $itemsStmt   = $pdo->prepare("
        SELECT oi.*, p.image_url, p.category, p.brand, p.target_animal, p.pharmacy_tag, p.is_autoship 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id IN ($order_ph)
    ");
    $itemsStmt->execute($order_ids);
    $all_items   = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    $items_by_order = [];
    foreach ($all_items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
    foreach ($orders as &$order) {
        $order['items'] = $items_by_order[$order['id']] ?? [];
    }
    unset($order);
}

// Fetch active subscriptions
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_subscriptions 
    WHERE user_id = ? AND status = 'active'
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch User Digital Credit Wallet & Transactions ────────────────────────────
$userWalletStmt = $pdo->prepare("SELECT * FROM user_wallets WHERE user_id = ?");
$userWalletStmt->execute([$user_id]);
$userDigitalWallet = $userWalletStmt->fetch(PDO::FETCH_ASSOC);
if (!$userDigitalWallet) {
    $pdo->prepare("INSERT INTO user_wallets (user_id, balance, currency, created_at) VALUES (?, 0, 'IRT', NOW())")->execute([$user_id]);
    $userDigitalWallet = ['id' => (int)$pdo->lastInsertId(), 'user_id' => $user_id, 'balance' => 0, 'currency' => 'IRT'];
}

// Handle User Digital Wallet Charge Action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'charge_user_wallet') {
    csrf_verify();
    $amount = (int)($_POST['amount'] ?? 0);
    if ($amount >= 10000) {
        $upd = $pdo->prepare("UPDATE user_wallets SET balance = balance + ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$amount, $userDigitalWallet['id']]);
        
        $insTx = $pdo->prepare("
            INSERT INTO wallet_transactions (wallet_id, amount, type, description, reference_id, created_at)
            VALUES (?, ?, 'deposit', 'شارژ آنلاین کیف پول اعتباری آسنا', ?, NOW())
        ");
        $refId = 'DEP-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $insTx->execute([$userDigitalWallet['id'], $amount, $refId]);
        
        $_SESSION['profile_success'] = "کیف پول اعتباری شما با موفقیت به مبلغ " . number_format($amount) . " تومان شارژ گردید.";
        header("Location: profile.php#wallet-section");
        exit;
    } else {
        $_SESSION['profile_error'] = "حداقل مبلغ شارژ کیف پول ۱۰,۰۰۰ تومان می‌باشد.";
        header("Location: profile.php#wallet-section");
        exit;
    }
}

$userTxStmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 20");
$userTxStmt->execute([$userDigitalWallet['id']]);
$userWalletTransactions = $userTxStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch Marketplace Wallet, Escrow & Payout Data ───────────────────────────
require_once __DIR__ . '/includes/App.php';
$escrowService = App::escrow();
$wallet = $escrowService->getSellerWallet($user_id);

// Fetch Income breakdown (Orders & Items credited to this seller)
$incomeStmt = $pdo->prepare("
    SELECT l.*, o.post_tracking_code, o.status as order_status, o.created_at as order_created_at,
           oi.product_name_snapshot, oi.quantity, oi.price_at_purchase
    FROM seller_escrow_ledger l
    JOIN orders o ON l.order_id = o.id
    LEFT JOIN order_items oi ON l.order_item_id = oi.id
    WHERE l.seller_id = ?
    ORDER BY l.id DESC
    LIMIT 40
");
$incomeStmt->execute([$user_id]);
$walletIncomes = $incomeStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Outcome breakdown (Weekly Paya Settlement Batches)
$outcomeStmt = $pdo->prepare("
    SELECT b.batch_code, b.created_at as payout_date, b.status as batch_status,
           SUM(l.net_seller_amount) as settled_amount,
           COUNT(l.id) as items_settled_count
    FROM seller_escrow_ledger l
    JOIN seller_payout_batches b ON l.settlement_batch_id = b.id
    WHERE l.seller_id = ?
    GROUP BY b.id, b.batch_code, b.created_at, b.status
    ORDER BY b.created_at DESC
    LIMIT 20
");
$outcomeStmt->execute([$user_id]);
$walletOutcomes = $outcomeStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Seller / Provider Financial Transcript Aggregations ─────────────────────────
$sellerFinancialTranscript = [
    'week' => ['gross_amount' => 0, 'net_earnings' => 0, 'count' => 0],
    'month' => ['gross_amount' => 0, 'net_earnings' => 0, 'count' => 0],
    'six_months' => ['gross_amount' => 0, 'net_earnings' => 0, 'count' => 0],
    'year' => ['gross_amount' => 0, 'net_earnings' => 0, 'count' => 0],
];
try {
    $sFinStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN gross_amount ELSE 0 END) as week_gross,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN net_seller_amount ELSE 0 END) as week_net,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN gross_amount ELSE 0 END) as month_gross,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN net_seller_amount ELSE 0 END) as month_net,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN gross_amount ELSE 0 END) as six_months_gross,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN net_seller_amount ELSE 0 END) as six_months_net,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) THEN 1 END) as six_months_count,

            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN gross_amount ELSE 0 END) as year_gross,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN net_seller_amount ELSE 0 END) as year_net,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY) THEN 1 END) as year_count
        FROM seller_escrow_ledger
        WHERE seller_id = ?
    ");
    $sFinStmt->execute([$user_id]);
    $sFinRow = $sFinStmt->fetch(PDO::FETCH_ASSOC);
    if ($sFinRow) {
        $sellerFinancialTranscript['week'] = [
            'gross_amount' => (int)($sFinRow['week_gross'] ?? 0),
            'net_earnings' => (int)($sFinRow['week_net'] ?? 0),
            'count' => (int)($sFinRow['week_count'] ?? 0)
        ];
        $sellerFinancialTranscript['month'] = [
            'gross_amount' => (int)($sFinRow['month_gross'] ?? 0),
            'net_earnings' => (int)($sFinRow['month_net'] ?? 0),
            'count' => (int)($sFinRow['month_count'] ?? 0)
        ];
        $sellerFinancialTranscript['six_months'] = [
            'gross_amount' => (int)($sFinRow['six_months_gross'] ?? 0),
            'net_earnings' => (int)($sFinRow['six_months_net'] ?? 0),
            'count' => (int)($sFinRow['six_months_count'] ?? 0)
        ];
        $sellerFinancialTranscript['year'] = [
            'gross_amount' => (int)($sFinRow['year_gross'] ?? 0),
            'net_earnings' => (int)($sFinRow['year_net'] ?? 0),
            'count' => (int)($sFinRow['year_count'] ?? 0)
        ];
    }
} catch (Exception $e) {}


// Calculate Next Weekly Payout Date (Every Thursday at 22:00)
$nowTz = new DateTime('now', new DateTimeZone('Asia/Tehran'));
$nextThursday = clone $nowTz;
if ((int)$nowTz->format('N') === 4 && (int)$nowTz->format('H') < 22) {
    $nextThursday->setTime(22, 0, 0);
} else {
    $nextThursday->modify('next thursday')->setTime(22, 0, 0);
}
$diff = $nowTz->diff($nextThursday);
$daysUntilPayout = $diff->days;
$hoursUntilPayout = $diff->h;
$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
$fmtDateTime = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY - HH:mm');
$fmtDateText = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY');
$nextPayoutFormatted = $fmtDateText->format($nextThursday) . ' ساعت ۲۲:۰۰';
?>

<?php require_once 'includes/header.php'; ?>
<!-- Leaflet Map Assets for Address Pinpointing -->
<link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css" />
<script src="assets/vendor/leaflet/leaflet.js"></script>
<style>
    .persian-number {
        font-feature-settings: "ss01", "ss02", "ss03", "ss04";
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    /* Custom Sleek Scrollbar for Sidebar Navigation */
    #profile-sidebar nav {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
    }
    #profile-sidebar nav::-webkit-scrollbar {
        width: 4px;
    }
    #profile-sidebar nav::-webkit-scrollbar-track {
        background: transparent;
    }
    #profile-sidebar nav::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.25);
        border-radius: 9999px;
    }
    #profile-sidebar nav::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.45);
    }

    /* Full-Height Desktop App Shell Ergonomics */
    @media (min-width: 1024px) {
        #profile-sidebar {
            top: 0 !important;
            bottom: 0 !important;
            z-index: 50 !important;
            overflow-y: auto !important;
            overscroll-behavior: contain;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        #profile-main {
            transition: margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            padding-bottom: 8rem !important;
        }
        /* Dynamically adjust the desktop floating header to avoid sidebar collision */
        header.hidden.lg\:block {
            margin-right: 17rem !important;
            margin-left: 2rem !important;
            max-width: none !important;
            width: auto !important;
            transition: margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        body.sidebar-collapsed-shell header.hidden.lg\:block {
            margin-right: 6rem !important;
        }
        #profile-sidebar.sidebar-collapsed {
            width: 5rem !important; /* 80px */
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        #profile-sidebar.sidebar-collapsed .sidebar-header-text,
        #profile-sidebar.sidebar-collapsed .sidebar-label,
        #profile-sidebar.sidebar-collapsed .sidebar-badge {
            display: none !important;
        }
        #profile-sidebar.sidebar-collapsed .sidebar-toggle-container {
            justify-content: center !important;
            margin-bottom: 1.5rem !important;
        }
        #profile-sidebar.sidebar-collapsed .sidebar-user-card {
            justify-content: center !important;
            padding: 0.5rem 0 !important;
            background: transparent !important;
        }
        #profile-sidebar.sidebar-collapsed nav a,
        #profile-sidebar.sidebar-collapsed .sidebar-footer-link {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            gap: 0 !important;
        }
        #profile-sidebar.sidebar-collapsed nav a span.material-symbols-outlined,
        #profile-sidebar.sidebar-collapsed .sidebar-footer-link span.material-symbols-outlined {
            margin: 0 !important;
            font-size: 1.5rem !important;
        }
        #profile-main.sidebar-collapsed {
            margin-right: 5rem !important; /* 80px */
        }
    }
</style>

<!-- Mobile Backdrop -->
<div id="profile-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleProfileSidebar()"></div>

<!-- SideNavBar -->
<aside id="profile-sidebar" class="fixed right-0 top-0 bottom-0 w-64 p-6 flex flex-col bg-surface-container-lowest border-l border-outline-variant z-[70] lg:z-50 transition-transform duration-300 translate-x-full lg:translate-x-0">
<div class="mb-10 flex justify-between items-center sidebar-toggle-container">
<div class="sidebar-header-text">
<?php if ($isSeller): ?>
    <div class="flex items-center gap-2 mb-1">
        <span class="material-symbols-outlined text-emerald-600 text-xl">storefront</span>
        <h2 class="text-base font-black text-primary">پیشخوان فروش و کسب‌وکار</h2>
    </div>
    <p class="text-[11px] text-on-surface-variant font-medium">سامانه محصولات، سفارشات و تسویه</p>
<?php else: ?>
    <h2 class="text-lg font-bold text-primary">پنل کاربری آسنا</h2>
    <p class="text-xs text-on-surface-variant">خدمات جامع سلامت و فروشگاهی پت</p>
<?php endif; ?>
</div>
<button type="button" class="lg:hidden text-on-surface-variant p-1 rounded-lg hover:bg-surface-container-low" onclick="toggleProfileSidebar()" aria-label="بستن منو">
<span class="material-symbols-outlined">close</span>
</button>
<button type="button" id="sidebar-desktop-toggle-btn" class="hidden lg:flex w-8 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface-variant items-center justify-center transition-colors shadow-xs" onclick="toggleDesktopSidebar()" title="تغییر وضعیت منو">
<span id="sidebar-desktop-toggle-icon" class="material-symbols-outlined text-xl">chevron_right</span>
</button>
</div>
<div class="flex items-center gap-3 mb-6 p-2 bg-surface-container-low rounded-xl sidebar-user-card" title="<?php echo htmlspecialchars($user['name'] ?? 'کاربر'); ?>">
<div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center font-bold shrink-0">
            <?php echo mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8'); ?>
        </div>
<div class="overflow-hidden sidebar-label">
<p class="text-sm font-bold truncate text-on-surface"><?php echo htmlspecialchars($user['name'] ?? 'کاربر مهمان'); ?></p>
<p class="text-xs text-on-surface-variant truncate"><?php echo htmlspecialchars($user['phone']); ?></p>
</div>
</div>
<nav class="flex flex-col gap-1 flex-1 min-h-0 overflow-y-auto">
<?php if ($isSeller): ?>
    <!-- Seller Sidebar Navigation Links -->
    <a class="flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold transition-all shadow-md" href="profile.php?view=seller" title="پیشخوان و آمار فروش">
        <span class="material-symbols-outlined text-secondary shrink-0">dashboard</span>
        <span class="text-sm sidebar-label">پیشخوان و آمار فروش</span>
    </a>
    <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#seller-orders-section" title="سفارشات دریافتی مشتریان">
        <span class="material-symbols-outlined text-indigo-600 shrink-0">local_shipping</span>
        <span class="text-sm sidebar-label">سفارشات دریافتی مشتریان</span>
        <?php if ($sellerPendingCount > 0): ?>
            <span class="mr-auto px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 font-bold sidebar-badge"><?= $sellerPendingCount ?></span>
        <?php endif; ?>
    </a>
    <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#seller-products-section" title="مدیریت کاتالوگ و محصولات">
        <span class="material-symbols-outlined text-teal-600 shrink-0">inventory_2</span>
        <span class="text-sm sidebar-label">مدیریت کاتالوگ و محصولات</span>
        <span class="mr-auto px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-700 font-bold sidebar-badge"><?= count($sellerProducts) ?></span>
    </a>
    <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="#wallet-section" title="کیف پول و تسویه‌حساب (Escrow)">
        <span class="material-symbols-outlined text-emerald-600 shrink-0">account_balance_wallet</span>
        <span class="text-sm font-bold text-emerald-800 sidebar-label">کیف پول و تسویه‌حساب</span>
    </a>
    <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all" href="javascript:void(0)" onclick="openBankTab()" title="مشخصات بانکی و شماره شبا">
        <span class="material-symbols-outlined text-amber-600 shrink-0">credit_card</span>
        <span class="text-sm sidebar-label">مشخصات بانکی و شبا</span>
    </a>
    <?php if (in_array($userRole, ['organization', 'organization_manager', 'admin'])): ?>
        <a class="flex items-center gap-3 px-4 py-3 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-xl transition-all font-bold border border-sky-200 mt-2" href="organization/index.php" title="پنل جامع مدیریت مرکز درمانی">
            <span class="material-symbols-outlined shrink-0">local_hospital</span>
            <span class="text-xs sidebar-label">پنل جامع مرکز درمانی</span>
        </a>
    <?php endif; ?>
    <?php if ($userRole === 'admin'): ?>
        <a class="flex items-center gap-3 px-4 py-3 text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all font-bold" href="admin/index.php" title="پنل مدیریت کل سایت">
            <span class="material-symbols-outlined shrink-0">admin_panel_settings</span>
            <span class="text-sm sidebar-label">پنل مدیریت سایت</span>
        </a>
    <?php endif; ?>
<?php else: ?>
    <!-- Normal Pet Owner Sidebar Links (Digikala Architecture) -->
    <a id="sidebar-btn-overview" class="flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold transition-all shadow-md cursor-pointer" onclick="switchCustomerView('overview')" title="پیشخوان">
        <span class="material-symbols-outlined shrink-0">dashboard</span>
        <span class="text-sm sidebar-label">پیشخوان</span>
    </a>
    <a id="sidebar-btn-personal-info" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('personal-info')" title="اطلاعات حساب کاربری">
        <span class="material-symbols-outlined text-primary shrink-0">person</span>
        <span class="text-sm sidebar-label">اطلاعات حساب کاربری</span>
    </a>
    <a id="sidebar-btn-addresses" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('addresses')" title="آدرس‌ها و نشانی">
        <span class="material-symbols-outlined text-rose-600 shrink-0">location_on</span>
        <span class="text-sm sidebar-label">آدرس‌ها و نشانی</span>
    </a>
    <a id="sidebar-btn-pets" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('pets')" title="حیوانات من">
        <span class="material-symbols-outlined text-amber-600 shrink-0">pets</span>
        <span class="text-sm sidebar-label">حیوانات من</span>
    </a>
    <a id="sidebar-btn-appointments" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('appointments')" title="نوبت‌های من">
        <span class="material-symbols-outlined text-teal-600 shrink-0">calendar_month</span>
        <span class="text-sm sidebar-label">نوبت‌های من</span>
    </a>
    <a id="sidebar-btn-orders" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('orders')" title="تاریخچه سفارشات">
        <span class="material-symbols-outlined text-indigo-600 shrink-0">receipt_long</span>
        <span class="text-sm sidebar-label">تاریخچه سفارشات</span>
    </a>
    <a id="sidebar-btn-prescriptions" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('prescriptions')" title="نسخه‌های من">
        <span class="material-symbols-outlined text-emerald-600 shrink-0">medical_services</span>
        <span class="text-sm sidebar-label">نسخه‌های من</span>
        <?php if(!empty($prescriptions)): ?>
        <span class="mr-auto text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-black sidebar-badge"><?= count($prescriptions) ?></span>
        <?php endif; ?>
    </a>
    <a id="sidebar-btn-subscriptions" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('subscriptions')" title="اشتراک‌های فعال">
        <span class="material-symbols-outlined text-orange-600 shrink-0">autorenew</span>
        <span class="text-sm sidebar-label">اشتراک‌های فعال</span>
    </a>
    <a id="sidebar-btn-wallet" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold" onclick="switchCustomerView('wallet')" title="کیف پول اعتباری">
        <span class="material-symbols-outlined text-emerald-600 shrink-0">account_balance_wallet</span>
        <span class="text-sm sidebar-label">کیف پول اعتباری</span>
    </a>
    <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all font-bold" href="wishlist.php" title="علاقه‌مندی‌ها">
        <span class="material-symbols-outlined text-red-500 shrink-0">favorite</span>
        <span class="text-sm sidebar-label">علاقه‌مندی‌ها</span>
    </a>
    <?php if(isset($user['role']) && $user['role'] === 'admin'): ?>
        <a class="flex items-center gap-3 px-4 py-3 text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all font-bold" href="admin/index.php" title="پنل مدیریت کل سایت">
            <span class="material-symbols-outlined shrink-0">admin_panel_settings</span>
            <span class="text-sm sidebar-label">پنل مدیریت سایت</span>
        </a>
    <?php endif; ?>
    <?php if (in_array($userRole, ['seller', 'organization', 'organization_manager', 'admin'])): ?>
        <a class="flex items-center gap-3 px-4 py-3 text-sky-700 bg-sky-50 hover:bg-sky-100 rounded-xl transition-all font-bold border border-sky-200 mt-2" href="profile.php?view=seller" title="سوئیچ به پنل فروشندگان">
            <span class="material-symbols-outlined shrink-0">storefront</span>
            <span class="text-xs sidebar-label">سوئیچ به پنل فروشندگان</span>
        </a>
    <?php endif; ?>
<?php endif; ?>
</nav>
<div class="pt-6 border-t border-outline-variant flex flex-col gap-1">
<a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all sidebar-footer-link" href="user_tickets.php" title="پشتیبانی و تیکت‌ها">
<span class="material-symbols-outlined shrink-0">help</span>
<span class="text-sm sidebar-label">پشتیبانی و تیکت‌ها</span>
</a>
<?php if ($isSeller): ?>
    <a class="flex items-center gap-3 px-4 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition-all text-xs font-bold sidebar-footer-link" href="profile.php?view=customer" title="مشاهده پنل خریدار">
        <span class="material-symbols-outlined text-base shrink-0">person</span>
        <span class="sidebar-label">مشاهده پنل خریدار</span>
    </a>
<?php endif; ?>
<a class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all sidebar-footer-link" href="logout.php" onclick="return confirm('آیا از خروج از حساب کاربری اطمینان دارید؟');" title="خروج از حساب کاربری">
<span class="material-symbols-outlined shrink-0">logout</span>
<span class="text-sm sidebar-label">خروج</span>
</a>
</div>
</aside>
<!-- Main Content -->
<main id="profile-main" class="lg:mr-64 mr-0 mt-16 p-4 md:p-8 min-h-screen transition-all duration-300">
<script>
    if (window.innerWidth >= 1024 && localStorage.getItem('asena_sidebar_collapsed') === '1') {
        document.getElementById('profile-sidebar')?.classList.add('sidebar-collapsed');
        document.getElementById('profile-main')?.classList.add('sidebar-collapsed');
        document.body.classList.add('sidebar-collapsed-shell');
        const icon = document.getElementById('sidebar-desktop-toggle-icon');
        if (icon) icon.innerText = 'chevron_left';
    }
</script>
<div class="max-w-[1200px] mx-auto space-y-6 md:space-y-8">

<!-- Mobile Header Toggle -->
<div class="lg:hidden flex justify-between items-center bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm mb-3">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center font-bold text-lg">
            <?php echo mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8'); ?>
        </div>
        <div>
            <h1 class="font-bold text-primary text-sm"><?= $isSeller ? 'پیشخوان فروشندگان' : 'پنل کاربری شما' ?></h1>
            <p class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($user['name'] ?: 'کاربر گرامی') ?></p>
        </div>
    </div>
    <button onclick="toggleProfileSidebar()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
        <span class="material-symbols-outlined">menu_open</span>
    </button>
</div>

<!-- Mobile Quick Navigation Carousel / Scrollable Tab Bar (Digikala Standard) -->
<div class="lg:hidden flex items-center gap-2 overflow-x-auto pb-2 -mx-1 px-1 custom-scrollbar text-xs font-bold shrink-0 mb-4 sticky top-16 z-30 bg-surface/90 backdrop-blur-md py-1.5">
    <?php if ($isSeller): ?>
        <a href="profile.php?view=seller" class="px-3.5 py-2 rounded-xl bg-primary text-white shadow-sm shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm">analytics</span>
            <span>پیشخوان فروش</span>
        </a>
        <a href="#seller-orders-section" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-indigo-600">local_shipping</span>
            <span>سفارشات</span>
            <?php if ($sellerPendingCount > 0): ?>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800"><?= $sellerPendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="#seller-products-section" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-teal-600">inventory_2</span>
            <span>محصولات</span>
        </a>
        <a href="#wallet-section" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-emerald-600">account_balance_wallet</span>
            <span>تسویه پایا</span>
        </a>
    <?php else: ?>
        <button type="button" id="mob-tab-btn-overview" onclick="switchCustomerView('overview')" class="px-3.5 py-2 rounded-xl bg-primary text-white shadow-sm shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm">dashboard</span>
            <span>پیشخوان</span>
        </button>
        <button type="button" id="mob-tab-btn-personal-info" onclick="switchCustomerView('personal-info')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-primary">person</span>
            <span>اطلاعات فردی</span>
        </button>
        <button type="button" id="mob-tab-btn-addresses" onclick="switchCustomerView('addresses')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-rose-600">location_on</span>
            <span>آدرس‌ها</span>
        </button>
        <button type="button" id="mob-tab-btn-pets" onclick="switchCustomerView('pets')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-amber-600">pets</span>
            <span>حیوانات من</span>
        </button>
        <button type="button" id="mob-tab-btn-appointments" onclick="switchCustomerView('appointments')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-teal-600">calendar_month</span>
            <span>نوبت‌ها</span>
        </button>
        <button type="button" id="mob-tab-btn-orders" onclick="switchCustomerView('orders')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-indigo-600">receipt_long</span>
            <span>سفارشات</span>
        </button>
        <button type="button" id="mob-tab-btn-prescriptions" onclick="switchCustomerView('prescriptions')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-emerald-600">medical_services</span>
            <span>نسخه‌ها</span>
        </button>
        <button type="button" id="mob-tab-btn-wallet" onclick="switchCustomerView('wallet')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-emerald-600">account_balance_wallet</span>
            <span>کیف پول</span>
        </button>
        <button type="button" id="mob-tab-btn-subscriptions" onclick="switchCustomerView('subscriptions')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm text-orange-600">autorenew</span>
            <span>اشتراک‌ها</span>
        </a>
        <a href="#personal-info" onclick="switchCustomerView('personal-info')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-slate-500">settings</span>
            <span>تنظیمات</span>
        </a>
    <?php endif; ?>
</div>
<?php if ($success): ?>
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white p-6 sm:p-8 rounded-3xl shadow-xl shadow-emerald-700/20 relative overflow-hidden animate-fade-in border border-white/20 mb-8">
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white flex-shrink-0 shadow-inner border border-white/30">
                    <span class="material-symbols-outlined text-3xl text-emerald-100">verified</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-emerald-400/30 text-emerald-100 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-300/30">تراکنش بانکی موفق</span>
                        <span class="text-xs text-emerald-100 font-medium">🎉 سفارش شما با موفقیت ثبت شد</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold leading-snug"><?php echo htmlspecialchars($success); ?></h3>
                </div>
            </div>
            <div class="flex items-center gap-2.5 self-end md:self-center">
                <a href="#orders" onclick="switchCustomerView('orders')" class="bg-white text-emerald-800 hover:bg-emerald-50 px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 active:scale-95">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    مشاهده فاکتور
                </a>
                <a href="pharmacy.php" class="bg-white/15 hover:bg-white/25 text-white px-4 py-2.5 rounded-xl font-bold text-xs transition-all border border-white/20 active:scale-95">
                    داروخانه
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <a href="#personal-info" onclick="switchCustomerView('personal-info')" class="block bg-error/10 text-error p-4 rounded-2xl flex items-center gap-3 border border-error/20 hover:bg-error/20 transition-colors cursor-pointer group mb-6">
        <span class="material-symbols-outlined group-hover:scale-110 transition-transform">error</span>
        <span class="font-bold text-sm flex-1"><?php echo htmlspecialchars($error); ?></span>
        <span class="material-symbols-outlined">chevron_left</span>
    </a>
<?php endif; ?>

<?php if ($isSeller): ?>
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- COMMERCIAL SELLER SUITE (فروشندگان رسمی بازارگاه)                       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Seller Overview Bento -->
<section id="seller-overview-section" class="glass-card rounded-3xl p-6 sm:p-8 border border-outline-variant shadow-lg flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-emerald-50/30">
    <div class="absolute top-0 right-0 w-48 h-48 bg-emerald-500/5 rounded-full -mr-24 -mt-24 pointer-events-none"></div>
    <div class="flex items-center gap-5 relative z-10">
        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-emerald-700 flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-lg shadow-emerald-700/20 flex-shrink-0">
            <?= mb_substr(htmlspecialchars($user['name'] ?? 'ف'), 0, 1, 'UTF-8') ?>
        </div>
        <div>
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black border border-emerald-200 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">verified</span>
                    فروشنده رسمی بازارگاه
                </span>
                <span class="text-xs text-on-surface-variant font-medium">• کارمزد پلتفرم: ۱۵٪ امانی</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900"><?= htmlspecialchars($user['name'] ?? 'فروشگاه شما') ?></h2>
            <p class="text-xs text-on-surface-variant mt-0.5">مدیریت سفارشات دریافتی، صدور فاکتور رسمی و چرخه تسویه هفتگی پنج‌شنبه‌ها</p>
        </div>
    </div>

    <!-- 3 Metrics & Quick Action -->
    <div class="flex items-center gap-3 flex-wrap relative z-10 w-full lg:w-auto">
        <div class="flex-1 sm:flex-none flex flex-col items-center bg-white px-4 py-3 rounded-2xl border border-outline-variant shadow-sm min-w-[110px]">
            <p class="text-[10px] text-slate-500 font-bold mb-1">کل فروش ناخالص</p>
            <p class="text-sm sm:text-base font-black text-slate-900 persian-number">
                <?= number_format($sellerTotalRevenue) ?> <span class="text-[10px] font-bold text-slate-400">تومان</span>
            </p>
        </div>

        <div class="flex-1 sm:flex-none flex flex-col items-center bg-emerald-50/80 px-4 py-3 rounded-2xl border border-emerald-200 shadow-sm min-w-[120px]">
            <p class="text-[10px] text-emerald-800 font-bold mb-1">آماده تسویه (پایا)</p>
            <p class="text-sm sm:text-base font-black text-emerald-900 persian-number">
                <?= number_format($wallet['balance_available_for_payout']) ?> <span class="text-[10px] font-bold text-emerald-700">تومان</span>
            </p>
        </div>

        <div class="flex-1 sm:flex-none flex flex-col items-center bg-amber-50/80 px-4 py-3 rounded-2xl border border-amber-200 shadow-sm min-w-[110px]">
            <p class="text-[10px] text-amber-800 font-bold mb-1">نیازمند ارسال</p>
            <p class="text-sm sm:text-base font-black text-amber-900 persian-number">
                <?= $sellerPendingCount ?> <span class="text-[10px] font-bold text-amber-700">سفارش</span>
            </p>
        </div>

        <button type="button" onclick="document.getElementById('addProductModal').classList.remove('hidden')" class="w-full sm:w-auto bg-primary text-white px-5 py-3 rounded-xl font-bold text-xs hover:bg-primary-hover transition-all active:scale-95 shadow-md flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>افزودن کالا به کاتالوگ</span>
        </button>
    </div>
</section>

<!-- Single Person Seller Identity & Store Profile Card -->
<section id="seller-identity-section" class="bg-white rounded-3xl border border-outline-variant/80 shadow-sm p-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">badge</span>
            </div>
            <div>
                <h3 class="text-sm font-black text-slate-800">مشخصات هویتی فروشنده حقیقی (Single Person Seller)</h3>
                <p class="text-[11px] text-slate-500">احراز هویت فروشنده انفرادی، ثبت کد ملی جهت صورتحساب مالیاتی و تنظیمات فروشگاه</p>
            </div>
        </div>
        <span class="text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 self-start sm:self-auto flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">verified</span>
            فروشنده مستقل حقیقی
        </span>
    </div>

    <form method="POST" action="actions/profile_action.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="seller_update_identity">
        <input type="hidden" name="is_seller_action" value="1">

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">نام فروشگاه / نام تجاری *</label>
            <input type="text" name="store_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-emerald-500 font-bold text-slate-800 bg-slate-50 focus:bg-white transition-all">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">کد ملی ۱۰ رقمی *</label>
            <input type="text" name="national_id" value="<?= htmlspecialchars($user['national_id'] ?? '') ?>" maxlength="10" placeholder="0012345678" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-emerald-500 font-mono text-left dir-ltr bg-slate-50 focus:bg-white transition-all">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">شماره تماس کاری</label>
            <input type="text" readonly value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 text-slate-500 font-mono text-left dir-ltr bg-slate-100 cursor-not-allowed">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">شهر انبار / ارسال</label>
            <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'تهران') ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-emerald-500 font-medium text-slate-800 bg-slate-50 focus:bg-white transition-all">
        </div>

        <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <span class="text-[11px] text-slate-400">
                تسویه مبالغ حاصل از فروش پس از تأیید تحویل پست و انقضای مهلت ۷ روزه، مستقیماً به شماره شبای شما واریز می‌گردد.
            </span>
            <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 self-end sm:self-auto">
                <span class="material-symbols-outlined text-sm">save</span>
                <span>ذخیره مشخصات فروشنده</span>
            </button>
        </div>
    </form>
</section>

    <!-- ─── Marketplace Escrow Wallet & Weekly Payout Section (Sellers only) ───────── -->
<section id="wallet-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden p-6 md:p-8 space-y-6">
    <!-- Header with Next Payout Countdown Banner -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 border-b border-outline-variant/60 pb-6">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 font-bold text-xs border border-emerald-500/20">
                    <span class="material-symbols-outlined text-sm text-emerald-600">verified_user</span>
                    صندوق امانی بازارگاه و تسویه هفتگی پایا
                </span>
                <span class="text-xs text-on-surface-variant font-medium">• چرخه اتوماتیک بانک مرکزی</span>
            </div>
            <h3 class="text-xl md:text-2xl font-black text-primary mt-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-2xl text-emerald-600">account_balance_wallet</span>
                کیف پول فروشگاهی و برنامه تسویه‌حساب هفتگی
            </h3>
            <p class="text-xs text-on-surface-variant mt-1 max-w-2xl leading-relaxed">
                مبالغ حاصل از فروش پس از استعلام برخط تحویل به مشتری از وب‌سرویس پستکس و سپری شدن ۷ روز مهلت بررسی و تست، هر پنج‌شنبه شب مستقیماً به شماره شبای شما واریز می‌گردد.
            </p>
        </div>

        <!-- Next Payout Pill -->
        <div class="bg-gradient-to-br from-emerald-500/10 via-teal-500/10 to-primary-container/10 border border-emerald-500/20 rounded-2xl p-4 sm:p-5 flex items-center gap-4 flex-shrink-0 shadow-inner">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md flex-shrink-0">
                <span class="material-symbols-outlined text-2xl">event_upcoming</span>
            </div>
            <div>
                <p class="text-[11px] text-on-surface-variant font-bold">موعد تسویه حساب هفتگی بعدی:</p>
                <p class="text-sm font-black text-emerald-950 dark:text-emerald-100 persian-number mt-0.5"><?= $nextPayoutFormatted ?></p>
                <div class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100/60 dark:bg-emerald-900/40 px-2 py-0.5 rounded-full mt-1">
                    <span class="material-symbols-outlined text-xs animate-pulse">timer</span>
                    <span><?= $daysUntilPayout ?> روز و <?= $hoursUntilPayout ?> ساعت تا واریز پایا</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Main Financial Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Card 1: Available for Payout -->
        <div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-emerald-50 via-teal-50/40 to-white border border-emerald-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-800">موجودی آماده تسویه</span>
                    <p class="text-[11px] text-emerald-600/90 mt-0.5">در نوبت واریز پنج‌شنبه جاری</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl md:text-3xl font-black text-emerald-950 persian-number">
                    <?= number_format($wallet['balance_available_for_payout']) ?> <span class="text-xs font-bold text-emerald-700">تومان</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-[11px] text-emerald-700 font-medium">
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                    آماده انتقال به شبا با شناسه حواله پایا
                </div>
            </div>
        </div>

        <!-- Card 2: Pending Escrow -->
        <div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-amber-50 via-yellow-50/40 to-white border border-amber-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-800">موجودی در صندوق امانی</span>
                    <p class="text-[11px] text-amber-600/90 mt-0.5">در حال ارسال یا مهلت ۷ روزه تست</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">lock_clock</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl md:text-3xl font-black text-amber-950 persian-number">
                    <?= number_format($wallet['balance_pending_escrow']) ?> <span class="text-xs font-bold text-amber-700">تومان</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-[11px] text-amber-700 font-medium">
                    <span class="material-symbols-outlined text-xs">shield</span>
                    طبق ماده ۳۷ قانون تجارت الکترونیک
                </div>
            </div>
        </div>

        <!-- Card 3: Settled Lifetime -->
        <div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-blue-50 via-indigo-50/40 to-white border border-blue-200/80 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-blue-800">مجموع تسویه‌شده تا کنون</span>
                    <p class="text-[11px] text-blue-600/90 mt-0.5">واریز موفق به حساب بانکی</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">account_balance</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl md:text-3xl font-black text-blue-950 persian-number">
                    <?= number_format($wallet['balance_settled_lifetime']) ?> <span class="text-xs font-bold text-blue-700">تومان</span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-[11px] text-blue-700 font-medium">
                    <span class="material-symbols-outlined text-xs">verified</span>
                    <?= !empty($wallet['bank_sheba']) ? 'شماره شبا: ' . htmlspecialchars($wallet['bank_sheba']) : 'شماره شبا ثبت نشده است' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Action Bar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
            <button type="button" onclick="toggleWalletDetails()" id="wallet-toggle-btn" class="inline-flex items-center justify-center gap-2 bg-primary text-white hover:bg-primary/90 px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base">query_stats</span>
                <span>گردش حساب (درآمدها و تسویه‌ها)</span>
                <span class="material-symbols-outlined transition-transform duration-300" id="wallet-toggle-arrow">expand_more</span>
            </button>

            <button type="button" onclick="openBankTab()" class="inline-flex items-center justify-center gap-2 bg-emerald-600 text-white hover:bg-emerald-700 px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base">credit_card</span>
                <span>تنظیم شماره کارت و شبای واریز</span>
            </button>
        </div>

        <?php if(empty($wallet['bank_sheba'])): ?>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-amber-600">warning</span>
                شماره شبا ثبت نشده است (جهت تسویه هفتگی تکمیل فرمایید)
            </span>
        <?php else: ?>
            <span class="text-xs text-on-surface-variant font-medium flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                شبای فعال: <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($wallet['bank_sheba']) ?></span>
                <?php if(!empty($wallet['bank_card_number'])): ?>
                    <span class="text-slate-400">|</span>
                    کارت: <span class="font-mono"><?= htmlspecialchars(substr($wallet['bank_card_number'], 0, 4) . '-****-****-' . substr($wallet['bank_card_number'], -4)) ?></span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Collapsible Financial Details Panel -->
    <div id="wallet-details-panel" class="hidden border border-outline-variant rounded-2xl overflow-hidden bg-white shadow-sm mt-4">
        <!-- Tabs Header -->
        <div class="flex border-b border-outline-variant bg-surface-container-low text-xs md:text-sm font-bold">
            <button type="button" onclick="switchWalletTab('income')" id="tab-btn-income" class="flex-1 py-3.5 px-4 flex items-center justify-center gap-2 text-primary border-b-2 border-primary bg-white transition-all">
                <span class="material-symbols-outlined text-lg text-emerald-600">trending_up</span>
                <span>درآمدها و فروش‌ها (Income)</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 persian-number"><?= count($walletIncomes) ?></span>
            </button>
            <button type="button" onclick="switchWalletTab('outcome')" id="tab-btn-outcome" class="flex-1 py-3.5 px-4 flex items-center justify-center gap-2 text-on-surface-variant hover:text-primary transition-all">
                <span class="material-symbols-outlined text-lg text-blue-600">account_balance</span>
                <span>واریزی‌های هفتگی پایا (Outcome)</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-surface-container text-on-surface-variant persian-number"><?= count($walletOutcomes) ?></span>
            </button>
            <button type="button" onclick="switchWalletTab('bank')" id="tab-btn-bank" class="flex-1 py-3.5 px-4 flex items-center justify-center gap-2 text-on-surface-variant hover:text-primary transition-all">
                <span class="material-symbols-outlined text-lg text-amber-600">credit_card</span>
                <span>اطلاعات حساب و شماره شبا</span>
            </button>
        </div>

        <!-- ── TAB 1: Income (Ledger) ── -->
        <div id="tab-content-income" class="p-4 md:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-sm text-slate-800">ریز درآمدهای فروش و وضعیت صندوق امانی</h4>
                    <p class="text-xs text-slate-500 mt-0.5">اقلام فروخته‌شده شما به همراه سهم خالص، کارمزد و آخرین وضعیت استعلام پستی</p>
                </div>
            </div>

            <?php if (empty($walletIncomes)): ?>
                <div class="text-center py-10 border border-dashed border-outline-variant rounded-xl text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-400">receipt_long</span>
                    <p class="text-sm font-bold">هنوز تراکنش فروشی برای حساب شما ثبت نشده است.</p>
                    <p class="text-xs text-slate-400 mt-1">به محض ثبت سفارش مشتری، مبالغ و وضعیت ارسال در این جدول نمایش داده می‌شود.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-outline-variant">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-surface-container-low text-slate-600 font-bold border-b border-outline-variant">
                            <tr>
                                <th class="p-3">سفارش / تاریخ</th>
                                <th class="p-3">عنوان کالا</th>
                                <th class="p-3">مبلغ کل</th>
                                <th class="p-3">کارمزد پلتفرم</th>
                                <th class="p-3">سهم شما (خالص)</th>
                                <th class="p-3">وضعیت ارسال و امانی</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php foreach ($walletIncomes as $inc): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-3 font-bold text-slate-800">
                                        #<?= $inc['order_id'] ?>
                                        <span class="block text-[10px] text-slate-400 font-normal">
                                            <?= $fmtDate->format(new DateTime($inc['order_created_at'] ?? $inc['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-medium text-slate-800 max-w-[200px] truncate" title="<?= htmlspecialchars($inc['product_name_snapshot'] ?? 'کالای بازارگاه') ?>">
                                            <?= htmlspecialchars($inc['product_name_snapshot'] ?? 'کالای بازارگاه') ?>
                                        </div>
                                        <span class="text-[10px] text-slate-400">تعداد: <?= (int)($inc['quantity'] ?? 1) ?> عدد</span>
                                    </td>
                                    <td class="p-3 font-mono font-medium text-slate-700 persian-number">
                                        <?= number_format($inc['gross_amount']) ?> تومان
                                    </td>
                                    <td class="p-3 font-mono text-red-600 persian-number">
                                        -<?= number_format($inc['commission_amount']) ?> تومان
                                    </td>
                                    <td class="p-3 font-mono font-bold text-emerald-700 persian-number">
                                        +<?= number_format($inc['net_seller_amount']) ?> تومان
                                    </td>
                                    <td class="p-3">
                                        <?php if ($inc['status'] === 'settled_in_batch'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] border border-blue-200">
                                                <span class="material-symbols-outlined text-xs">done_all</span>
                                                واریز شده (پایا)
                                            </span>
                                        <?php elseif ($inc['status'] === 'released_to_available'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">
                                                <span class="material-symbols-outlined text-xs">check_circle</span>
                                                آماده تسویه پنج‌شنبه
                                            </span>
                                        <?php else: ?>
                                            <?php if (!empty($inc['delivered_at'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-bold text-[10px] border border-amber-200" title="موعد آزادسازی: <?= $inc['payout_eligible_at'] ?>">
                                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                                    تحویل شد • مهلت تست ۷ روزه
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] border border-slate-200">
                                                    <span class="material-symbols-outlined text-xs">local_shipping</span>
                                                    در حال ارسال پستی
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($inc['post_tracking_code'])): ?>
                                                <a href="https://postex.ir/tracking?tracking_code=<?= urlencode($inc['post_tracking_code']) ?>" target="_blank" class="block text-[10px] text-primary hover:underline font-mono mt-1">
                                                    کد رهگیری: <?= htmlspecialchars($inc['post_tracking_code']) ?> ↗
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── TAB 2: Outcome (Payout Batches) ── -->
        <div id="tab-content-outcome" class="hidden p-4 md:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-sm text-slate-800">تاریخچه واریزی‌های هفتگی پایا (بانک مرکزی)</h4>
                    <p class="text-xs text-slate-500 mt-0.5">گزارش مبالغ واریز شده به حساب شبای بانکی شما در پایان چرخه‌های هفتگی</p>
                </div>
            </div>

            <?php if (empty($walletOutcomes)): ?>
                <div class="text-center py-10 border border-dashed border-outline-variant rounded-xl text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-400">account_balance</span>
                    <p class="text-sm font-bold">هنوز واریز هفتگی برای این حساب ثبت نشده است.</p>
                    <p class="text-xs text-slate-400 mt-1">با آزادسازی مبالغ امانی، اولین واریز در پنج‌شنبه پیش رو انجام خواهد شد.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-outline-variant">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-surface-container-low text-slate-600 font-bold border-b border-outline-variant">
                            <tr>
                                <th class="p-3">شناسه حواله پایا</th>
                                <th class="p-3">تاریخ و ساعت تسویه</th>
                                <th class="p-3">تعداد سفارشات</th>
                                <th class="p-3">مبلغ کل واریزی</th>
                                <th class="p-3">وضعیت تراکنش</th>
                                <th class="p-3 text-center">رسید رسمی</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php foreach ($walletOutcomes as $out): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-3 font-mono font-bold text-primary">
                                        <?= htmlspecialchars($out['batch_code']) ?>
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        <?= $fmtDateTime->format(new DateTime($out['payout_date'])) ?>
                                    </td>
                                    <td class="p-3 text-slate-700 persian-number">
                                        <?= (int)$out['items_settled_count'] ?> قلم سفارش
                                    </td>
                                    <td class="p-3 font-mono font-bold text-emerald-700 text-sm persian-number">
                                        <?= number_format($out['settled_amount']) ?> تومان
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">
                                            <span class="material-symbols-outlined text-xs">check_circle</span>
                                            واریز موفق به شبا
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="actions/generate_payout_receipt.php?batch_code=<?= urlencode($out['batch_code']) ?>" target="_blank" class="inline-flex items-center gap-1 bg-white hover:bg-primary hover:text-white text-primary px-3 py-1.5 rounded-xl font-bold transition-all border border-slate-200 text-[11px] shadow-sm">
                                            <span class="material-symbols-outlined text-xs text-secondary-container">receipt_long</span>
                                            رسید حواله پایا
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── TAB 3: Bank Details & Card Setup ── -->
        <div id="tab-content-bank" class="hidden p-4 md:p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant/30 pb-4">
                <div>
                    <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-base">account_balance</span>
                        مشخصات حساب بانکی و شماره کارت جهت تسویه هفتگی
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5">تسویه حساب‌های هفتگی پایا مستقیماً به شماره شبای ثبت‌شده در این بخش واریز خواهد شد.</p>
                </div>
                <div class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 shrink-0">
                    <span class="material-symbols-outlined text-xs">verified_user</span>
                    تسویه با کارمزد ۱۵٪ پلتفرم
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Visual Debit Card Preview (Shetab Standard) -->
                <div class="lg:col-span-5">
                    <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-xl bg-gradient-to-tr from-slate-900 via-primary to-indigo-900 border border-white/20 aspect-[1.586/1] flex flex-col justify-between">
                        <!-- Background patterns -->
                        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                        <div class="absolute -left-10 -top-10 w-44 h-44 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>

                        <!-- Card Top Bar -->
                        <div class="flex items-center justify-between relative z-10">
                            <span class="text-xs font-bold tracking-wide text-white/90" id="cardPreviewBank"><?= htmlspecialchars($wallet['bank_name'] ?: 'بانک متصل شتاب') ?></span>
                            <span class="text-[11px] font-black uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded text-white/90">SHETAB</span>
                        </div>

                        <!-- Chip & NFC icon -->
                        <div class="flex items-center gap-3 my-2 relative z-10">
                            <div class="w-10 h-7 rounded bg-amber-300/80 border border-amber-400/90 shadow-inner flex items-center justify-center">
                                <div class="w-6 h-4 border border-amber-600/40 rounded-sm"></div>
                            </div>
                            <span class="material-symbols-outlined text-xl text-white/70 rotate-90">wifi</span>
                        </div>

                        <!-- Card Number -->
                        <div class="relative z-10 text-center my-1">
                            <div class="font-mono text-base sm:text-lg tracking-widest font-black text-white drop-shadow dir-ltr" id="cardPreviewNumber">
                                <?= !empty($wallet['bank_card_number']) ? htmlspecialchars(chunk_split($wallet['bank_card_number'], 4, '  ')) : '••••  ••••  ••••  ••••' ?>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="flex items-end justify-between relative z-10 pt-2 border-t border-white/15 text-xs">
                            <div>
                                <span class="text-[9px] text-white/60 block mb-0.5">دارنده حساب:</span>
                                <span class="font-bold text-white tracking-tight" id="cardPreviewHolder"><?= htmlspecialchars($wallet['bank_account_holder'] ?: $user['name'] ?: 'نام صاحب حساب') ?></span>
                            </div>
                            <div class="text-left">
                                <span class="text-[9px] text-white/60 block mb-0.5">شماره شبا (IBAN):</span>
                                <span class="font-mono text-[10px] text-white/90 font-bold dir-ltr" id="cardPreviewSheba">
                                    <?= !empty($wallet['bank_sheba']) ? htmlspecialchars(substr($wallet['bank_sheba'], 0, 8) . '...' . substr($wallet['bank_sheba'], -4)) : 'IR••••' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 text-center mt-2">پیش‌نمایش کارت بانکی شتاب جهت تسویه حواله هفتگی پایا</p>
                </div>

                <!-- Form Section -->
                <div class="lg:col-span-7">
                    <form action="actions/profile_action.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-surface-container-low p-5 rounded-2xl border border-outline-variant">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_bank_details">

                        <!-- Card Number Input -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                <span>شماره کارت ۱۶ رقمی:</span>
                                <span class="text-[10px] font-normal text-emerald-600" id="detectedBankBadge">تشخیص خودکار بانک با پیش‌شماره کارت</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="bank_card_input" name="bank_card_number" value="<?= htmlspecialchars($wallet['bank_card_number'] ?? '') ?>" placeholder="xxxx-xxxx-xxxx-xxxx" maxlength="19" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono text-left focus:ring-2 focus:ring-primary outline-none" dir="ltr" oninput="formatCardInput(this)">
                                <span class="absolute right-3 top-2.5 text-slate-400 material-symbols-outlined text-lg pointer-events-none">credit_card</span>
                            </div>
                        </div>

                        <!-- Bank Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">نام بانک:</label>
                            <input type="text" id="bank_name_input" name="bank_name" value="<?= htmlspecialchars($wallet['bank_name'] ?? '') ?>" placeholder="مثلاً بانک سامان، ملت، ملی..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-primary outline-none" oninput="document.getElementById('cardPreviewBank').innerText = this.value || 'بانک متصل شتاب'">
                        </div>

                        <!-- Account Holder -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی دارنده حساب:</label>
                            <input type="text" id="bank_holder_input" name="bank_account_holder" value="<?= htmlspecialchars($wallet['bank_account_holder'] ?? $user['name'] ?? '') ?>" placeholder="نام صاحب حساب" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-primary outline-none" oninput="document.getElementById('cardPreviewHolder').innerText = this.value || 'نام صاحب حساب'">
                        </div>

                        <!-- Sheba Number -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                شماره شبا (IBAN) بدون فاصله:
                                <span class="text-[10px] text-slate-400 font-normal">(فرمت معتبر: IR به همراه ۲۴ رقم)</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="bank_sheba_input" name="bank_sheba" value="<?= htmlspecialchars($wallet['bank_sheba'] ?? '') ?>" placeholder="IR120120000000001234567890" maxlength="26" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono text-left uppercase focus:ring-2 focus:ring-primary outline-none" dir="ltr" required oninput="updateShebaPreview(this)">
                                <span class="absolute right-3 top-2.5 text-slate-400 material-symbols-outlined text-lg pointer-events-none">account_balance_wallet</span>
                            </div>
                        </div>

                        <div class="md:col-span-2 pt-2">
                            <button type="submit" class="w-full bg-primary text-white hover:bg-primary/90 px-6 py-3 rounded-xl font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">save</span>
                                ذخیره و ثبت رسمی اطلاعات بانکی
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-detect bank from 6-digit BIN prefix and format card number
const bankBins = {
    '603799': 'بانک ملی ایران',
    '610433': 'بانک ملت',
    '621986': 'بانک سامان / بلوبانک',
    '502229': 'بانک پاسارگاد',
    '627412': 'بانک اقتصاد نوین',
    '622106': 'بانک پارسیان',
    '639194': 'بانک پارسیان',
    '589210': 'بانک سپه',
    '627381': 'بانک انصار (سپه)',
    '505416': 'بانک گردشگری',
    '639346': 'بانک سینا',
    '603770': 'بانک کشاورزی',
    '628023': 'بانک مسکن',
    '504706': 'بانک شهر',
    '627760': 'پست بانک ایران',
    '502908': 'بانک توسعه تعاون',
    '589463': 'بانک رفاه کارگران',
    '639607': 'بانک سرمایه',
    '627961': 'بانک صنعت و معدن'
};

function formatCardInput(input) {
    let val = input.value.replace(/\D/g, '');
    let formatted = '';
    for (let i = 0; i < val.length && i < 16; i++) {
        if (i > 0 && i % 4 === 0) formatted += '-';
        formatted += val[i];
    }
    input.value = formatted;

    // Update preview
    document.getElementById('cardPreviewNumber').innerText = formatted || '••••  ••••  ••••  ••••';

    // Check bank BIN (first 6 digits)
    if (val.length >= 6) {
        let bin = val.substring(0, 6);
        let bankName = bankBins[bin] || '';
        if (bankName) {
            document.getElementById('cardPreviewBank').innerText = bankName;
            let bankNameInput = document.getElementById('bank_name_input');
            if (!bankNameInput.value) {
                bankNameInput.value = bankName;
            }
            document.getElementById('detectedBankBadge').innerText = '✔ ' + bankName;
        }
    }
}

function updateShebaPreview(input) {
    let val = input.value.trim().toUpperCase();
    if (!val.startsWith('IR') && val.length > 0) {
        val = 'IR' + val.replace(/[^0-9]/g, '');
        input.value = val;
    }
    document.getElementById('cardPreviewSheba').innerText = val.length > 8 ? (val.substring(0, 8) + '...' + val.slice(-4)) : (val || 'IR••••');
}

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- COMMERCIAL SELLER SUITE: Orders & Product Catalog Management              -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->

<!-- FINANCIAL TRANSCRIPT & SETTLEMENT CLARIFICATION (همکاران / فروشندگان / کلینیک‌ها) -->
<div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm space-y-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-900 text-white flex items-center justify-center shadow-sm">
                <span class="material-symbols-outlined text-xl text-amber-300">analytics</span>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-800 flex items-center gap-2">
                    <span>شفافیت مالی و کارنامه درآمد همکار</span>
                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full">سهم خالص ۸۵٪ همکار</span>
                </h3>
                <p class="text-[11px] text-slate-500">گزارش حجم فروش ناخالص، کارمزد و دریافتی خالص شما در بازه‌های زمانی مختلف</p>
            </div>
        </div>

        <!-- Period Toggle -->
        <div class="inline-flex p-1 bg-slate-100 rounded-2xl text-xs font-bold self-start sm:self-auto" id="sellerFinTabs">
            <button type="button" onclick="switchSellerFin('week')" id="sellerFinTab_week" class="seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer bg-white text-slate-900 shadow-xs font-black">۱ هفته</button>
            <button type="button" onclick="switchSellerFin('month')" id="sellerFinTab_month" class="seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۱ ماه</button>
            <button type="button" onclick="switchSellerFin('six_months')" id="sellerFinTab_six_months" class="seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۶ ماه</button>
            <button type="button" onclick="switchSellerFin('year')" id="sellerFinTab_year" class="seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۱ سال</button>
        </div>
    </div>

    <!-- 4 Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
            <span class="text-[11px] text-slate-500 font-medium block">کل فروش ناخالص:</span>
            <div class="text-base sm:text-lg font-black font-mono text-slate-900" id="sellerGross">
                <?= number_format($sellerFinancialTranscript['week']['gross_amount']) ?> <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>
            </div>
            <span class="text-[10px] text-slate-400 block">ارزش ناخالص اقلام سفارش</span>
        </div>

        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-1">
            <span class="text-[11px] text-emerald-800 font-bold block">سهم خالص همکار (۸۵٪):</span>
            <div class="text-base sm:text-lg font-black font-mono text-emerald-700" id="sellerNet">
                <?= number_format($sellerFinancialTranscript['week']['net_earnings']) ?> <span class="text-xs font-normal text-emerald-600 font-sans">تومان</span>
            </div>
            <span class="text-[10px] text-emerald-600/80 block">واریز قطعی پس از کسر کارمزد</span>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
            <span class="text-[11px] text-slate-500 font-medium block">اقلام سفارشات:</span>
            <div class="text-base sm:text-lg font-black font-mono text-slate-900" id="sellerCount">
                <?= number_format($sellerFinancialTranscript['week']['count']) ?> <span class="text-xs font-normal text-slate-500 font-sans">قلم</span>
            </div>
            <span class="text-[10px] text-slate-400 block">تراکنش‌های فروش ثبت‌شده</span>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
            <span class="text-[11px] text-slate-500 font-medium block">تسویه بعدی پایا:</span>
            <div class="text-xs sm:text-sm font-black text-indigo-800 pt-1">
                پنج‌شنبه ساعت ۲۲:۰۰
            </div>
            <span class="text-[10px] text-slate-400 block">واریز خودکار به شماره شبا</span>
        </div>
    </div>
</div>

<script>
const sellerFinData = <?= json_encode($sellerFinancialTranscript, JSON_UNESCAPED_UNICODE) ?>;
function switchSellerFin(period) {
    ['week', 'month', 'six_months', 'year'].forEach(p => {
        const btn = document.getElementById('sellerFinTab_' + p);
        if (btn) {
            if (p === period) {
                btn.className = 'seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer bg-white text-slate-900 shadow-xs font-black';
            } else {
                btn.className = 'seller-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold';
            }
        }
    });
    const d = sellerFinData[period] || { gross_amount: 0, net_earnings: 0, count: 0 };
    document.getElementById('sellerGross').innerHTML = d.gross_amount.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>';
    document.getElementById('sellerNet').innerHTML = d.net_earnings.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-emerald-600 font-sans">تومان</span>';
    document.getElementById('sellerCount').innerHTML = d.count.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-slate-500 font-sans">قلم</span>';
}
</script>

<!-- SECTION 1: Incoming Customer Sales Orders (سفارشات دریافتی مشتریان) -->
<section id="seller-orders-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden p-6 md:p-8 space-y-6 scroll-mt-24">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/60 pb-5">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-indigo-500/10 text-indigo-700 font-bold text-xs border border-indigo-500/20">
                    <span class="material-symbols-outlined text-xs">local_shipping</span>
                    ارسال مرسولات و انبارداری
                </span>
                <span class="text-xs text-on-surface-variant font-medium">• اتصال به وب‌سرویس پستکس</span>
            </div>
            <h3 class="text-lg md:text-xl font-black text-primary mt-1.5 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">inventory_2</span>
                سفارشات دریافتی مشتریان بازارگاه
            </h3>
            <p class="text-xs text-on-surface-variant mt-1">مدیریت کالاهای خریداری‌شده توسط مشتریان، صدور فاکتور رسمی ماده ۱۶۹ و ثبت کد رهگیری پستی مرسولات</p>
        </div>

        <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 self-start sm:self-auto persian-number">
            <?= count($sellerOrders) ?> قلم سفارش ثبت شده
        </span>
    </div>

    <?php if (empty($sellerOrders)): ?>
        <div class="text-center py-14 border border-dashed border-outline-variant rounded-2xl text-on-surface-variant space-y-2">
            <span class="material-symbols-outlined text-5xl text-slate-300">shopping_bag</span>
            <p class="text-sm font-bold text-slate-700">هنوز سفارشی برای کالاهای شما ثبت نشده است.</p>
            <p class="text-xs text-slate-400 max-w-md mx-auto">به محض خرید مشتریان از فروشگاه یا داروخانه، جزئیات سفارش و نشانی خریدار در این بخش قرار می‌گیرد.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($sellerOrders as $so): ?>
                <?php
                $isPending = in_array($so['order_status'] ?? '', ['pending_payment', 'processing']);
                $isShipped = ($so['order_status'] ?? '') === 'shipped';
                $isDelivered = ($so['order_status'] ?? '') === 'delivered';
                $grossPrice = (float)($so['price_at_purchase'] * $so['quantity']);
                $commission = (float)($so['commission_amount'] ?: ($grossPrice * 0.15));
                $netShare = (float)($so['seller_net_amount'] ?: ($grossPrice - $commission));
                ?>
                <div class="border border-outline-variant/70 rounded-2xl p-5 bg-white shadow-sm hover:border-primary/40 transition-all space-y-4">
                    <!-- Top Metadata -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/30 text-xs">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="font-black text-primary font-mono text-sm">#PC-<?= $so['order_id'] ?></span>
                            <span class="text-outline-variant">•</span>
                            <span class="text-on-surface-variant persian-number flex items-center gap-1 font-mono">
                                <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                <?= !empty($so['order_created_at']) ? $fmtDateText->format(new DateTime($so['order_created_at'])) : '' ?>
                            </span>
                            <span class="text-outline-variant">•</span>
                            <span class="font-bold text-slate-800">خریدار: <?= htmlspecialchars($so['customer_name'] ?? 'مشتری بازارگاه') ?></span>
                            <?php if (!empty($so['customer_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($so['customer_phone']) ?>" class="text-sky-600 font-mono dir-ltr hover:underline">
                                    <?= htmlspecialchars($so['customer_phone']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if ($isPending): ?>
                                <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-[11px] font-bold border border-amber-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    در انتظار ارسال مرسوله
                                </span>
                            <?php elseif ($isShipped): ?>
                                <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-800 text-[11px] font-bold border border-indigo-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    ارسال شده با پست
                                </span>
                            <?php elseif ($isDelivered): ?>
                                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    تحویل موفق به خریدار
                                </span>
                            <?php endif; ?>

                            <a href="actions/generate_invoice.php?order_id=<?= $so['order_id'] ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] flex items-center gap-1 transition-all" title="چاپ فاکتور رسمی">
                                <span class="material-symbols-outlined text-[13px]">receipt_long</span>
                                <span>فاکتور ماده ۱۶۹</span>
                            </a>
                        </div>
                    </div>

                    <!-- Customer Address -->
                    <?php if (!empty($so['shipping_address'])): ?>
                        <div class="text-xs text-slate-600 bg-slate-50/80 p-3 rounded-xl border border-slate-100 flex items-start gap-2">
                            <span class="material-symbols-outlined text-slate-400 text-base flex-shrink-0 mt-0.5">location_on</span>
                            <div>
                                <span class="font-bold text-slate-800">نشانی پستی گیرنده:</span>
                                <span><?= htmlspecialchars($so['shipping_address']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Product & Pricing Row -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-3 bg-surface-container-low/40 rounded-xl border border-outline-variant/30">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white border border-outline-variant/40 flex items-center justify-center text-primary overflow-hidden flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl text-slate-400">package_2</span>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-800"><?= htmlspecialchars($so['product_name_snapshot'] ?? 'کالای بازارگاه') ?></h4>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 font-mono">تعداد: <?= (int)$so['quantity'] ?> عدد × <?= number_format($so['price_at_purchase']) ?> تومان</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-mono">
                            <div>
                                <span class="text-[10px] text-slate-400 block">مبلغ کل ناخالص:</span>
                                <span class="font-bold text-slate-700"><?= number_format($grossPrice) ?> تومان</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-red-500 block">کارمزد پلتفرم (۱۵٪):</span>
                                <span class="font-bold text-red-600">-<?= number_format($commission) ?> تومان</span>
                            </div>
                            <div class="bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
                                <span class="text-[10px] text-emerald-800 block font-bold">سهم خالص شما (۸۵٪):</span>
                                <span class="font-black text-emerald-700 text-sm">+<?= number_format($netShare) ?> تومان</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Dispatch Form -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <form method="POST" action="actions/profile_action.php" class="flex flex-wrap items-center gap-2 m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="seller_dispatch_order">
                            <input type="hidden" name="order_id" value="<?= $so['order_id'] ?>">

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">کد رهگیری پستی / بارنامه:</span>
                                <input type="text" name="post_tracking_code" value="<?= htmlspecialchars($so['post_tracking_code'] ?? '') ?>" placeholder="مثال: 184590203001..." class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-primary outline-none w-48">
                            </div>

                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">local_shipping</span>
                                <span>ثبت ارسال مرسوله</span>
                            </button>
                        </form>

                        <?php if (!empty($so['post_tracking_code'])): ?>
                            <span class="text-[11px] text-slate-500 font-mono bg-slate-100 px-2.5 py-1 rounded-lg">
                                بارنامه فعال: <strong class="text-slate-800"><?= htmlspecialchars($so['post_tracking_code']) ?></strong>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- SECTION 2: Seller Product Catalog & Stock Management (مدیریت کاتالوگ و محصولات) -->
<section id="seller-products-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden p-6 md:p-8 space-y-6 scroll-mt-24">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/60 pb-5">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-teal-500/10 text-teal-700 font-bold text-xs border border-teal-500/20">
                    <span class="material-symbols-outlined text-xs">store</span>
                    کاتالوگ کالاها و انبارداری
                </span>
                <span class="text-xs text-on-surface-variant font-medium">• قیمت‌گذاری و موجودی</span>
            </div>
            <h3 class="text-lg md:text-xl font-black text-primary mt-1.5 flex items-center gap-2">
                <span class="material-symbols-outlined text-teal-600">inventory_2</span>
                مدیریت کاتالوگ و موجودی کالاهای فروشنده
            </h3>
            <p class="text-xs text-on-surface-variant mt-1">ویرایش سریع قیمت، کنترل لحظه‌ای موجودی و عرضه کالاهای جدید در بازارگاه</p>
        </div>

        <button type="button" onclick="document.getElementById('addProductModal').classList.remove('hidden')" class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 self-start sm:self-auto">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>افزودن کالای جدید</span>
        </button>
    </div>

    <?php if (empty($sellerProducts)): ?>
        <div class="text-center py-14 border border-dashed border-outline-variant rounded-2xl text-on-surface-variant space-y-2">
            <span class="material-symbols-outlined text-5xl text-slate-300">inventory</span>
            <p class="text-sm font-bold text-slate-700">هنوز کالایی در کاتالوگ شما ثبت نشده است.</p>
            <p class="text-xs text-slate-400 max-w-md mx-auto">با کلیک بر روی دکمه «افزودن کالای جدید»، اولین محصول خود را برای فروش در بازارگاه ثبت نمایید.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-outline-variant">
            <table class="w-full text-right text-xs">
                <thead class="bg-surface-container-low text-slate-600 font-bold border-b border-outline-variant">
                    <tr>
                        <th class="p-3.5">شناسه / تصویر</th>
                        <th class="p-3.5">عنوان کالا و دسته‌بندی</th>
                        <th class="p-3.5">قیمت فروش (تومان)</th>
                        <th class="p-3.5">موجودی انبار</th>
                        <th class="p-3.5">وضعیت</th>
                        <th class="p-3.5 text-center">ویرایش سریع</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php foreach ($sellerProducts as $sp): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 overflow-hidden flex-shrink-0">
                                        <img src="<?= htmlspecialchars($sp['image_url'] ?? 'assets/images/toy-mouse.jpg') ?>" class="w-full h-full object-cover" onerror="this.src='assets/images/toy-mouse.jpg'">
                                    </div>
                                    <span class="font-mono text-slate-400">#<?= $sp['id'] ?></span>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900 max-w-[220px] truncate" title="<?= htmlspecialchars($sp['name']) ?>">
                                    <?= htmlspecialchars($sp['name']) ?>
                                </div>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($sp['category'] ?? 'سایر') ?> • <?= htmlspecialchars($sp['brand'] ?? '') ?></span>
                            </td>
                            <td class="p-3.5 font-mono font-bold text-slate-800 persian-number">
                                <?= number_format($sp['price']) ?> تومان
                            </td>
                            <td class="p-3.5 font-mono font-bold text-slate-800 persian-number">
                                <?= (int)$sp['stock'] ?> عدد
                            </td>
                            <td class="p-3.5">
                                <?php if ($sp['stock'] > 0): ?>
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">موجود در انبار</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">ناموجود</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <form method="POST" action="actions/profile_action.php" class="flex items-center gap-1.5 m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="seller_update_product">
                                        <input type="hidden" name="is_seller_action" value="1">
                                        <input type="hidden" name="product_id" value="<?= $sp['id'] ?>">

                                        <input type="number" name="price" value="<?= (int)$sp['price'] ?>" title="قیمت جدید" class="w-24 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono dir-ltr" required>
                                        <input type="number" name="stock" value="<?= (int)$sp['stock'] ?>" title="تعداد موجودی" class="w-16 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono dir-ltr" required>

                                        <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors" title="ذخیره قیمت و موجودی">
                                            <span class="material-symbols-outlined text-sm">save</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="actions/profile_action.php" onsubmit="return confirm('آیا از حذف این کالا از کاتالوگ فروشگاه خود اطمینان دارید؟')" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="seller_delete_product">
                                        <input type="hidden" name="is_seller_action" value="1">
                                        <input type="hidden" name="product_id" value="<?= $sp['id'] ?>">
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors" title="حذف کالا">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>


<?php else: ?>
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- ENTERPRISE CUSTOMER SUITE (دیجی‌کالا گرید: ۸ نمای تفکیک‌شده و واکنش‌گرا)  -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 1: Overview (پیشخوان و خلاصه جامع وضعیت کاربری)                     -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-overview" class="customer-view space-y-6">
        <!-- 1. Hero Welcome Bento Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-[#001f5c] to-secondary-container text-white p-6 sm:p-8 shadow-xl border border-white/10">
            <div class="absolute -top-12 -right-12 w-64 h-64 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-primary-container/30 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-inner shrink-0">
                        <?= mb_substr(htmlspecialchars($user['name'] ?? 'ک'), 0, 1, 'UTF-8') ?>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="px-3 py-0.5 rounded-full bg-secondary-container/30 text-white text-xs font-black border border-white/20 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">star</span>
                                کاربر سطح طلایی آسنا
                            </span>
                            <span class="text-xs text-white/80 font-medium persian-number">همراه: <?= htmlspecialchars($user['phone'] ?? '') ?></span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-white"><?= htmlspecialchars($user['name'] ?? 'کاربر محترم') ?> عزیز، خوش آمدید 👋</h2>
                        <p class="text-xs text-white/80 mt-1">
                            <?php if (count($pets) > 0): ?>
                                سرپرست مهربانِ <span class="font-bold text-amber-300"><?= htmlspecialchars(implode(' و ', array_column($pets, 'name'))) ?></span>
                            <?php else: ?>
                                به سامانه جامع سلامت، داروخانه و مراقبت هوشمند حیوانات خانگی آسنا خوش آمدید
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <a href="booking.php" class="px-4 py-2.5 rounded-xl bg-white text-primary hover:bg-slate-100 font-bold text-xs shadow-md transition-all flex items-center gap-1.5 active:scale-95">
                        <span class="material-symbols-outlined text-base text-secondary-container">calendar_month</span>
                        رزرو نوبت پزشک
                    </a>
                    <a href="subscriptions.php" class="px-4 py-2.5 rounded-xl bg-secondary-container hover:bg-[#ea580c] text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 active:scale-95">
                        <span class="material-symbols-outlined text-base">cached</span>
                        اشتراک اتوشیپ
                    </a>
                </div>
            </div>

            <!-- 4 Quick Overview Metrics Strip -->
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-white/15">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-3.5 border border-white/10">
                    <p class="text-[11px] text-white/70 font-bold mb-0.5">موجودی کیف پول</p>
                    <p class="text-base font-black text-white persian-number font-mono">
                        <?= number_format($userDigitalWallet['balance'] ?? 0) ?> <span class="text-[10px] font-normal text-white/70">تومان</span>
                    </p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-3.5 border border-white/10">
                    <p class="text-[11px] text-white/70 font-bold mb-0.5">امتیاز وفاداری</p>
                    <a href="rewards.php" class="text-base font-black text-amber-300 persian-number hover:underline flex items-center gap-1">
                        <?= number_format($user['loyalty_points'] ?? 0) ?> <span class="text-[10px] font-normal text-white/70">امتیاز</span>
                    </a>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-3.5 border border-white/10">
                    <p class="text-[11px] text-white/70 font-bold mb-0.5">پت‌های ثبت شده</p>
                    <p class="text-base font-black text-white persian-number">
                        <?= count($pets) ?> <span class="text-[10px] font-normal text-white/70">حیوان</span>
                    </p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-3.5 border border-white/10">
                    <p class="text-[11px] text-white/70 font-bold mb-0.5">نوبت‌های فعال</p>
                    <p class="text-base font-black text-white persian-number">
                        <?= count($appointments) ?> <span class="text-[10px] font-normal text-white/70">نوبت</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- 1.5. Financial Transcript Clarification Widget (شفافیت گردش مالی خریدهای من) -->
        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#001a48] text-white flex items-center justify-center shadow-sm">
                        <span class="material-symbols-outlined text-xl text-amber-300">receipt_long</span>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-black text-slate-800 flex items-center gap-2">
                            <span>شفافیت و کارنامه گردش مالی خریدهای من</span>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">محرمانگی مالی تضمین‌شده</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">گزارش شفاف مبالغ پرداختی سفارشات و سود حاصل از تخفیف‌ها در بازه‌های زمانی مختلف</p>
                    </div>
                </div>

                <!-- Time Interval Tabs -->
                <div class="inline-flex p-1 bg-slate-100 rounded-2xl text-xs font-bold self-start sm:self-auto" id="custFinTabs">
                    <button type="button" onclick="switchCustFin('week')" id="custFinTab_week" class="cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer bg-white text-slate-900 shadow-xs font-black">۱ هفته</button>
                    <button type="button" onclick="switchCustFin('month')" id="custFinTab_month" class="cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۱ ماه</button>
                    <button type="button" onclick="switchCustFin('six_months')" id="custFinTab_six_months" class="cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۶ ماه</button>
                    <button type="button" onclick="switchCustFin('year')" id="custFinTab_year" class="cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold">۱ سال</button>
                </div>
            </div>

            <!-- 4 Metrics Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[11px] text-slate-500 font-medium block">مجموع پرداختی:</span>
                    <div class="text-base sm:text-lg font-black font-mono text-slate-900" id="custFinSpent">
                        <?= number_format($userFinancialTranscript['week']['total_spent']) ?> <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>
                    </div>
                    <span class="text-[10px] text-slate-400 block">فاکتور نهایی پس از تخفیف</span>
                </div>

                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-1">
                    <span class="text-[11px] text-emerald-800 font-bold block">سود از تخفیف‌های آسنا:</span>
                    <div class="text-base sm:text-lg font-black font-mono text-emerald-700" id="custFinDiscount">
                        <?= number_format($userFinancialTranscript['week']['total_discount']) ?> <span class="text-xs font-normal text-emerald-600 font-sans">تومان</span>
                    </div>
                    <span class="text-[10px] text-emerald-600/80 block">صرفه‌جویی خالص پرداختی</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[11px] text-slate-500 font-medium block">سفارشات موفق:</span>
                    <div class="text-base sm:text-lg font-black font-mono text-slate-900" id="custFinCount">
                        <?= number_format($userFinancialTranscript['week']['count']) ?> <span class="text-xs font-normal text-slate-500 font-sans">سفارش</span>
                    </div>
                    <span class="text-[10px] text-slate-400 block">کالا و خدمات تحویل‌شده</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[11px] text-slate-500 font-medium block">میانگین هر سفارش:</span>
                    <div class="text-base sm:text-lg font-black font-mono text-slate-900" id="custFinAvg">
                        <?= number_format($userFinancialTranscript['week']['count'] > 0 ? round($userFinancialTranscript['week']['total_spent'] / $userFinancialTranscript['week']['count']) : 0) ?> <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>
                    </div>
                    <span class="text-[10px] text-slate-400 block">میانگین ارزش سبد خرید</span>
                </div>
            </div>
        </div>

        <script>
        const custFinData = <?= json_encode($userFinancialTranscript, JSON_UNESCAPED_UNICODE) ?>;
        function switchCustFin(period) {
            ['week', 'month', 'six_months', 'year'].forEach(p => {
                const btn = document.getElementById('custFinTab_' + p);
                if (btn) {
                    if (p === period) {
                        btn.className = 'cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer bg-white text-slate-900 shadow-xs font-black';
                    } else {
                        btn.className = 'cust-fin-tab px-3 py-1.5 rounded-xl transition-all cursor-pointer text-slate-600 hover:text-slate-900 font-bold';
                    }
                }
            });
            const d = custFinData[period] || { total_spent: 0, total_discount: 0, count: 0 };
            document.getElementById('custFinSpent').innerHTML = d.total_spent.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>';
            document.getElementById('custFinDiscount').innerHTML = d.total_discount.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-emerald-600 font-sans">تومان</span>';
            document.getElementById('custFinCount').innerHTML = d.count.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-slate-500 font-sans">سفارش</span>';
            const avg = d.count > 0 ? Math.round(d.total_spent / d.count) : 0;
            document.getElementById('custFinAvg').innerHTML = avg.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-slate-500 font-sans">تومان</span>';
        }
        </script>

        <!-- 2. Digikala-Style Quick Summary Bento (Personal Info & Address) -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Card 1: اطلاعات فردی و شناسنامه کاربری -->
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl">badge</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-primary">اطلاعات هویتی و فردی</h3>
                                <p class="text-[11px] text-on-surface-variant">مشخصات سجلی و ارتباطی حساب کاربری</p>
                            </div>
                        </div>
                        <?php if (!empty($user['national_id'])): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                تکمیل شده
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">warning</span>
                                نیازمند کد ملی
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-surface-container-low/60 p-3 rounded-xl border border-outline-variant/40">
                            <span class="text-[10px] text-on-surface-variant block mb-0.5">نام و نام خانوادگی:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($user['name'] ?? 'ثبت نشده') ?></span>
                        </div>
                        <div class="bg-surface-container-low/60 p-3 rounded-xl border border-outline-variant/40">
                            <span class="text-[10px] text-on-surface-variant block mb-0.5">کد ملی:</span>
                            <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($user['national_id'] ?? 'ثبت نشده') ?></span>
                        </div>
                        <div class="bg-surface-container-low/60 p-3 rounded-xl border border-outline-variant/40">
                            <span class="text-[10px] text-on-surface-variant block mb-0.5">شماره همراه:</span>
                            <span class="font-mono font-bold text-slate-800 dir-ltr text-right"><?= htmlspecialchars($user['phone'] ?? 'ثبت نشده') ?></span>
                        </div>
                        <div class="bg-surface-container-low/60 p-3 rounded-xl border border-outline-variant/40 truncate">
                            <span class="text-[10px] text-on-surface-variant block mb-0.5">پست الکترونیک:</span>
                            <span class="font-mono font-bold text-slate-800 truncate text-[11px]"><?= htmlspecialchars($user['email'] ?? 'ثبت نشده') ?></span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-2 border-t border-outline-variant/40 flex justify-end">
                    <button type="button" onclick="switchCustomerView('personal-info')" class="text-xs font-bold text-primary hover:text-primary-container flex items-center gap-1 group">
                        <span>مشاهده و ویرایش اطلاعات فردی</span>
                        <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </button>
                </div>
            </div>

            <!-- Card 2: نشانی تحویل پیش‌فرض مرسولات -->
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl">location_on</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-primary">نشانی تحویل مرسولات</h3>
                                <p class="text-[11px] text-on-surface-variant">مقصد ارسال داروها، غذاها و بسته‌های پت‌شاپ</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <?= htmlspecialchars($user['city'] ?? 'تبریز') ?>
                        </span>
                    </div>

                    <div class="bg-surface-container-low/60 p-3.5 rounded-xl border border-outline-variant/40 space-y-2">
                        <p class="text-xs font-bold text-slate-800 leading-relaxed">
                            <span class="material-symbols-outlined text-xs text-secondary-container align-middle">home_pin</span>
                            <?= htmlspecialchars($user['address'] ?: 'هنوز نشانی پستی برای این حساب ثبت نشده است.') ?>
                        </p>
                        <div class="flex items-center justify-between text-[11px] text-on-surface-variant pt-2 border-t border-outline-variant/30">
                            <span>کد پستی: <strong class="font-mono text-slate-800"><?= htmlspecialchars($user['postal_code'] ?? 'ثبت نشده') ?></strong></span>
                            <span>موقعیت نقشه: <strong class="text-emerald-700"><?= !empty($user['latitude']) ? 'ثبت روی نقشه ✔' : 'تنظیم نشده' ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-2 border-t border-outline-variant/40 flex justify-end">
                    <button type="button" onclick="switchCustomerView('addresses')" class="text-xs font-bold text-secondary-container hover:underline flex items-center gap-1 group">
                        <span>مدیریت آدرس‌ها و موقعیت نقشه</span>
                        <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Two-Column Activity Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Right Column: Appointments & Orders (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Recent Appointments Preview -->
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-teal-600">calendar_month</span>
                            <h3 class="text-sm font-black text-primary">نوبت‌های ویزیت و مشاوره پیش‌رو</h3>
                        </div>
                        <button type="button" onclick="switchCustomerView('appointments')" class="text-xs font-bold text-primary hover:underline">
                            مشاهده همه (<?= count($appointments) ?>)
                        </button>
                    </div>

                    <?php if (empty($appointments)): ?>
                        <div class="text-center py-6 text-on-surface-variant space-y-2">
                            <p class="text-xs font-bold">شما در حال حاضر نوبت رزرو شده‌ای ندارید.</p>
                            <a href="booking.php" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                <span class="material-symbols-outlined text-sm">add_circle</span> رزرو اولین نوبت آنلاین
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach (array_slice($appointments, 0, 2) as $apt): ?>
                                <div class="p-4 rounded-2xl border border-outline-variant/60 bg-white hover:border-primary/40 transition-all space-y-3 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= htmlspecialchars($apt['doctor_image'] ?? 'assets/images/vet-avatar.jpg') ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200" alt="دکتر">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-xs font-black text-slate-900 truncate"><?= htmlspecialchars($apt['doctor_name']) ?></h4>
                                            <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($apt['doctor_specialty']) ?></p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $apt['status'] === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                            <?= $apt['status'] === 'approved' ? 'تایید شده' : 'در انتظار' ?>
                                        </span>
                                    </div>
                                    <div class="bg-slate-50 p-2.5 rounded-xl text-[11px] font-bold text-slate-700 flex items-center justify-between">
                                        <span><?= $fmtDateText->format(new DateTime($apt['appointment_date'])) ?></span>
                                        <span>ساعت <?= substr($apt['appointment_time'], 0, 5) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Orders Preview -->
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">receipt_long</span>
                            <h3 class="text-sm font-black text-primary">آخرین سفارشات پت‌شاپ و داروخانه</h3>
                        </div>
                        <button type="button" onclick="switchCustomerView('orders')" class="text-xs font-bold text-primary hover:underline">
                            مشاهده همه (<?= count($orders) ?>)
                        </button>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-6 text-on-surface-variant space-y-2">
                            <p class="text-xs font-bold">هنوز سفارشی ثبت نکرده‌اید.</p>
                            <a href="pharmacy.php" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                <span class="material-symbols-outlined text-sm">local_pharmacy</span> مشاهده داروخانه و پت‌شاپ
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($orders, 0, 2) as $ord): ?>
                                <div class="p-4 rounded-2xl border border-outline-variant/60 bg-white hover:border-primary/40 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 font-mono font-bold text-xs">
                                            #<?= $ord['id'] ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800 font-mono">سفارش #PC-<?= $ord['id'] ?></span>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700">
                                                    <?= $ord['status'] === 'delivered' ? 'تحویل داده شده' : 'در حال پردازش' ?>
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400 persian-number"><?= $fmtDateText->format(new DateTime($ord['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-4 text-xs">
                                        <span class="font-black text-emerald-700 persian-number"><?= number_format($ord['total_amount']) ?> تومان</span>
                                        <button type="button" onclick="switchCustomerView('orders')" class="text-primary font-bold text-xs hover:underline">مشاهده جزییات</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Left Column: Pets & Subscriptions Widgets (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Pets Widget -->
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600">pets</span>
                            <h3 class="text-sm font-black text-primary">حیوانات خانگی من</h3>
                        </div>
                        <button type="button" onclick="switchCustomerView('pets')" class="text-xs font-bold text-primary hover:underline">
                            مدیریت
                        </button>
                    </div>

                    <?php if (empty($pets)): ?>
                        <div class="text-center py-4 space-y-2">
                            <p class="text-xs text-on-surface-variant">هنوز حیوانی ثبت نشده است.</p>
                            <button type="button" onclick="document.getElementById('addPetModal').classList.remove('hidden')" class="w-full py-2 bg-primary/10 text-primary text-xs font-bold rounded-xl hover:bg-primary hover:text-white transition-colors">
                                + افزودن حیوان جدید
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2.5">
                            <?php foreach (array_slice($pets, 0, 3) as $pt): ?>
                                <div class="flex items-center gap-3 p-2.5 rounded-xl bg-surface-container-low/50 border border-outline-variant/30">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-lg"><?= $pt['type'] === 'گربه' ? 'cat' : ($pt['type'] === 'سگ' ? 'dog' : 'pets') ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($pt['name']) ?></h4>
                                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($pt['type']) ?> • <?= htmlspecialchars($pt['race'] ?? 'نژاد نامشخص') ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" onclick="switchCustomerView('pets')" class="w-full py-2 border border-dashed border-outline-variant rounded-xl text-xs font-bold text-slate-600 hover:text-primary hover:border-primary transition-colors">
                                مشاهده شناسنامه همه پت‌ها (<?= count($pets) ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Clinical Meal Plans & Health Reports Widget -->
                <?php 
                    $mealPlanDocs = array_filter($documents, function($d) {
                        return str_contains($d['title'] ?? '', 'برنامه غذایی') || str_ends_with($d['file_path'] ?? '', '.html');
                    });
                ?>
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">restaurant</span>
                            <h3 class="text-sm font-black text-primary">رژیم غذایی و کارنامه‌های بالینی</h3>
                        </div>
                        <a href="calculator.php" class="text-xs font-bold text-emerald-700 hover:underline flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-sm">calculate</span>
                            محاسبه‌گر
                        </a>
                    </div>

                    <?php if (empty($mealPlanDocs)): ?>
                        <div class="p-3.5 bg-emerald-50/60 rounded-2xl border border-emerald-100 space-y-2 text-right">
                            <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
                                <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                                <span>محاسبه استاندارد WSAVA برای <?= count($pets) > 0 ? htmlspecialchars($pets[0]['name']) : 'پت شما' ?></span>
                            </div>
                            <p class="text-[11px] text-emerald-800 leading-relaxed">
                                تعیین دقیق کالری روزانه (RER/MER)، گرم غذای خشک، آب آشامیدنی و جدول زمان‌بندی وعده‌ها.
                            </p>
                            <a href="calculator.php" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                                <span class="material-symbols-outlined text-sm">calculate</span>
                                دریافت و صدور کارنامه بالینی
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2.5">
                            <?php foreach (array_slice($mealPlanDocs, 0, 2) as $mpDoc): 
                                $mpUrl = 'view_meal_plan.php?file=' . urlencode(basename($mpDoc['file_path'] ?? ''));
                            ?>
                                <a href="<?= $mpUrl ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 p-3 rounded-2xl bg-emerald-50/50 hover:bg-emerald-50 border border-emerald-100 hover:border-emerald-300 transition-all group">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-lg">restaurant</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <h4 class="text-xs font-black text-slate-900 truncate"><?= htmlspecialchars($mpDoc['pet_name']) ?></h4>
                                            <span class="text-[9px] bg-emerald-200 text-emerald-900 px-1.5 py-0.2 rounded font-bold">نسخه بالینی</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 font-medium truncate mt-0.5"><?= htmlspecialchars($mpDoc['title']) ?></p>
                                    </div>
                                    <span class="material-symbols-outlined text-emerald-600 text-base group-hover:-translate-x-0.5 transition-transform">open_in_new</span>
                                </a>
                            <?php endforeach; ?>
                            <div class="flex items-center gap-2 pt-1">
                                <button type="button" onclick="switchCustomerView('pets')" class="flex-1 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:text-primary hover:border-primary transition-colors text-center">
                                    مشاهده سوابق (<?= count($mealPlanDocs) ?>)
                                </button>
                                <a href="calculator.php" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">add</span>
                                    جدید
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Subscriptions Widget -->
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-orange-600">autorenew</span>
                            <h3 class="text-sm font-black text-primary">اشتراک‌های دوره‌ای اتوشیپ</h3>
                        </div>
                        <button type="button" onclick="switchCustomerView('subscriptions')" class="text-xs font-bold text-secondary-container hover:underline">
                            مدیریت
                        </button>
                    </div>
                    <p class="text-xs text-on-surface-variant leading-relaxed">
                        بسته‌های غذایی و درمانی با ۱۵٪ تخفیف بدون دغدغه اتمام و سر وقت برای شما ارسال می‌شوند.
                    </p>
                    <button type="button" onclick="switchCustomerView('subscriptions')" class="w-full py-2.5 rounded-xl bg-secondary-container hover:bg-[#ea580c] text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">local_shipping</span>
                        <span>مشاهده وضعیت اشتراک‌های فعال</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 2: Personal Info (اطلاعات فردی و شناسنامه کاربری - استاندارد دیجی‌کالا)-->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-personal-info" class="customer-view space-y-6 hidden">
        <!-- Header -->
        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-0.5 rounded-full bg-primary/10 text-primary font-black text-xs border border-primary/20">
                        مرکز هویت و امنیت کاربری
                    </span>
                    <span class="text-xs text-on-surface-variant">• استاندارد دیجی‌کالا و مراجع نظارتی</span>
                </div>
                <h3 class="text-xl font-black text-primary mt-1.5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-2xl text-primary">account_circle</span>
                    اطلاعات فردی و شناسنامه کاربری
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">مشخصات سجلی شما برای صدور فاکتورهای رسمی ماده ۱۶۹ و خدمات بالینی ثبت و محرمانه نگهداری می‌شود.</p>
            </div>
            <button type="button" onclick="openEditPersonalInfoModal()" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 self-start sm:self-auto">
                <span class="material-symbols-outlined text-base">edit</span>
                <span>ویرایش اطلاعات فردی</span>
            </button>
        </div>

        <!-- Digikala 6-Card Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card 1: Full Name -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-bold block mb-1">نام و نام خانوادگی</span>
                        <h4 class="text-base font-black text-slate-900"><?= htmlspecialchars($user['name'] ?? 'ثبت نشده') ?></h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">person</span>
                    </div>
                </div>
                <button type="button" onclick="openEditPersonalInfoModal()" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">edit</span> ویرایش نام
                </button>
            </div>

            <!-- Card 2: National ID -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-bold block mb-1">کد ملی ۱۰ رقمی</span>
                        <h4 class="text-base font-mono font-black text-slate-900"><?= htmlspecialchars($user['national_id'] ?? 'ثبت نشده') ?></h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">badge</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <?php if (!empty($user['national_id'])): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">ثبت در سامانه مودیان</span>
                    <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">نیازمند ثبت کد ملی</span>
                    <?php endif; ?>
                    <button type="button" onclick="openEditPersonalInfoModal()" class="text-xs font-bold text-primary hover:underline">ویرایش</button>
                </div>
            </div>

            <!-- Card 3: Phone Number -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-bold block mb-1">شماره تلفن همراه</span>
                        <h4 class="text-base font-mono font-black text-slate-900 dir-ltr text-right"><?= htmlspecialchars($user['phone'] ?? 'ثبت نشده') ?></h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">smartphone</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">تایید شده با پیامک</span>
                    <span class="text-[11px] text-slate-400">شناسه ورود</span>
                </div>
            </div>

            <!-- Card 4: Email -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <span class="text-xs text-slate-400 font-bold block mb-1">پست الکترونیک (ایمیل)</span>
                        <h4 class="text-sm font-mono font-bold text-slate-900 truncate"><?= htmlspecialchars($user['email'] ?? 'ثبت نشده') ?></h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">mail</span>
                    </div>
                </div>
                <button type="button" onclick="openEditPersonalInfoModal()" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">edit</span> تغییر ایمیل
                </button>
            </div>

            <!-- Card 5: Password -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-bold block mb-1">کلمه عبور حساب کاربری</span>
                        <h4 class="text-base font-mono font-black text-slate-900 tracking-widest">••••••••••••</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">lock</span>
                    </div>
                </div>
                <button type="button" onclick="openChangePasswordModal()" class="text-xs font-bold text-amber-700 hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">key</span> تغییر رمز عبور
                </button>
            </div>

            <!-- Card 6: Bank Sheba & Shetab Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/80 p-5 shadow-sm hover:border-primary/50 transition-all flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <span class="text-xs text-slate-400 font-bold block mb-1">حساب بانکی جهت استرداد وجه</span>
                        <h4 class="text-xs font-mono font-bold text-slate-900 truncate"><?= !empty($wallet['bank_sheba']) ? htmlspecialchars($wallet['bank_sheba']) : 'شماره شبا ثبت نشده' ?></h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">credit_card</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-bold"><?= htmlspecialchars($wallet['bank_name'] ?? 'بانک شتاب') ?></span>
                    <button type="button" onclick="openEditShebaModal()" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">edit</span> ویرایش اطلاعات بانکی
                    </button>
                </div>
            </div>
        </div>

        <!-- Visual Shetab Card Preview Banner -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-white/10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400">credit_card</span>
                    <h4 class="text-base font-black">کارت بانکی عضو شتاب جهت مرجوعی و جوایز باشگاه</h4>
                </div>
                <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
                    در صورت لغو هرگونه سفارش یا واریز جوایز نقدی جشنواره‌های آسنا، مبالغ به صورت پایا مستقیماً به شماره شبای تایید شده زیر واریز می‌گردد.
                </p>
                <div class="flex items-center gap-4 pt-1 text-xs font-mono">
                    <span class="bg-white/10 px-3 py-1.5 rounded-xl border border-white/15">
                        شماره کارت: <?= htmlspecialchars($wallet['bank_card_number'] ?: '••••-••••-••••-••••') ?>
                    </span>
                    <span class="bg-white/10 px-3 py-1.5 rounded-xl border border-white/15">
                        بانک: <?= htmlspecialchars($wallet['bank_name'] ?: 'بانک متصل شتاب') ?>
                    </span>
                </div>
            </div>
            <button type="button" onclick="openEditShebaModal()" class="px-5 py-3 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs shadow-lg transition-all flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-base">add_card</span>
                <span>تنظیم یا تعویض کارت بانکی</span>
            </button>
        </div>

        <!-- Privacy & Danger Zone: Account Deletion & Right to be Forgotten (GDPR) -->
        <div class="bg-red-50/60 border border-red-200/80 rounded-3xl p-6 sm:p-7 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6 transition-all hover:border-red-300">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 shadow-sm">
                        <span class="material-symbols-outlined text-xl">delete_forever</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-red-950">حذف قطعی حساب کاربری و پاکسازی داده‌ها (حق فراموش‌شدن / GDPR)</h4>
                        <span class="text-[10px] text-red-600 font-bold">مطابق ماده ۱۷ آیین‌نامه عمومی حفاظت از داده‌ها و استانداردهای حریم خصوصی</span>
                    </div>
                </div>
                <p class="text-xs text-red-900/80 leading-relaxed max-w-2xl">
                    در صورت تمایل به خروج همیشگی از آسنا، با ثبت درخواست حذف حساب کلیه اطلاعات هویتی، پرونده‌های پزشکی پت‌ها و نشست‌های فعال شما به طور غیرقابل بازگشت پاکسازی شده و سوابق مالی و سفارشات گذشته مطابق قوانین به صورت ناشناس (Anonymized) آرشیو می‌گردند.
                </p>
            </div>
            <button type="button" onclick="openDeleteAccountModal()" class="px-5 py-3 rounded-2xl bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-xs shadow-lg shadow-red-600/25 transition-all flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-base">person_remove</span>
                <span>درخواست حذف حساب و پاکسازی</span>
            </button>
        </div>
    </div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 3: Addresses & Delivery Map (آدرس‌ها و موقعیت مکانی تحویل)          -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-addresses" class="customer-view space-y-6 hidden">
        <!-- Header -->
        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-0.5 rounded-full bg-secondary-container/10 text-secondary-container font-black text-xs border border-secondary-container/20">
                        مدیریت نشانی‌ها و تحویل سفارشات
                    </span>
                    <span class="text-xs text-on-surface-variant">• اتصال به نقشه و وب‌سرویس پستکس</span>
                </div>
                <h3 class="text-xl font-black text-primary mt-1.5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-2xl text-secondary-container">home_pin</span>
                    آدرس‌ها و موقعیت مکانی تحویل
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">سفارشات دارویی، مکمل‌ها و بسته‌های اتوشیپ به نشانی پیش‌فرض زیر تحویل داده می‌شوند.</p>
            </div>
            <button type="button" onclick="document.getElementById('address_city').focus()" class="px-5 py-2.5 rounded-xl bg-secondary-container hover:bg-[#ea580c] text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 self-start sm:self-auto">
                <span class="material-symbols-outlined text-base">edit_location_alt</span>
                <span>ویرایش نشانی پستی</span>
            </button>
        </div>

        <!-- Current Default Address Card -->
        <div class="bg-white rounded-3xl border border-outline-variant p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant/40">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">verified</span>
                    <h4 class="text-sm font-black text-slate-900">آدرس پیش‌فرض تحویل گیرنده</h4>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    پیش‌فرض فعال
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <span class="text-slate-400 font-bold block mb-1">تحویل‌گیرنده:</span>
                    <p class="font-bold text-slate-900"><?= htmlspecialchars($user['name'] ?? '') ?> (<?= htmlspecialchars($user['phone'] ?? '') ?>)</p>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <span class="text-slate-400 font-bold block mb-1">شهر و کد پستی:</span>
                    <p class="font-bold text-slate-900"><?= htmlspecialchars($user['city'] ?? 'تبریز') ?> • کد پستی: <span class="font-mono"><?= htmlspecialchars($user['postal_code'] ?? 'ثبت نشده') ?></span></p>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <span class="text-slate-400 font-bold block mb-1">موقعیت جغرافیایی GPS:</span>
                    <p class="font-bold text-slate-900">
                        <?php if (!empty($user['latitude']) && !empty($user['longitude'])): ?>
                            <span class="text-emerald-700 font-mono"><?= number_format($user['latitude'], 4) ?>, <?= number_format($user['longitude'], 4) ?></span>
                        <?php else: ?>
                            <span class="text-slate-400">روی نقشه تنظیم نشده</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 text-xs">
                <span class="font-bold text-amber-900 block mb-1">نشانی دقیق پستی:</span>
                <p class="text-slate-800 leading-relaxed font-medium">
                    <?= htmlspecialchars($user['address'] ?: 'نشانی پستی هنوز تکمیل نشده است. لطفاً فرم زیر را پر کنید.') ?>
                </p>
            </div>
        </div>

        <!-- Interactive Leaflet Map & Form Container -->
        <div class="bg-white rounded-3xl border border-outline-variant p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <h4 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary-container">pin_drop</span>
                        انتخاب موقعیت دقیق مکانی روی نقشه آنلاین
                    </h4>
                    <p class="text-xs text-slate-500 mt-1">با کلیک یا جابجایی نشانگر روی نقشه، نشانی پستی (خیابان و محله) و شهر به صورت خودکار تکمیل می‌شود.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="locateUserPosition()" class="px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition-all border border-blue-200 flex items-center gap-1.5 self-start sm:self-auto cursor-pointer shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-base">my_location</span>
                        <span>موقعیت مکانی من (GPS)</span>
                    </button>
                </div>
            </div>

            <!-- Quick City & Neighborhood Jumpers -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs scrollbar-none">
                <span class="text-slate-400 font-bold shrink-0 text-[11px]">پرش سریع:</span>
                <button type="button" onclick="jumpMapTo(38.0700, 46.2931, 14, 'تبریز')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">📍 تبریز</button>
                <button type="button" onclick="jumpMapTo(38.0645, 46.3600, 15, 'ولیعصر تبریز')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">ولیعصر</button>
                <button type="button" onclick="jumpMapTo(38.0580, 46.3750, 15, 'ائل‌گلی تبریز')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">ائل‌گلی</button>
                <button type="button" onclick="jumpMapTo(38.0680, 46.3260, 15, 'آبرسان تبریز')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">آبرسان</button>
                <button type="button" onclick="jumpMapTo(35.6892, 51.3890, 13, 'تهران')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">📍 تهران</button>
                <button type="button" onclick="jumpMapTo(35.7800, 51.3700, 14, 'سعادت‌آباد')" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-primary/10 hover:text-primary font-bold transition-all shrink-0 active:scale-95 cursor-pointer">سعادت‌آباد</button>
            </div>

            <!-- Leaflet Map Container with Floating Real-time Address Pill -->
            <div class="relative w-full rounded-2xl overflow-hidden border border-slate-200 shadow-inner">
                <div id="customer-address-map" class="w-full h-80 sm:h-96 z-0"></div>
                
                <!-- Floating Geocoding Live Indicator -->
                <div id="map-address-pill" class="absolute top-3 left-3 right-3 sm:right-3 sm:left-auto max-w-md bg-white/95 backdrop-blur-md px-3.5 py-2.5 rounded-2xl text-xs font-bold text-slate-800 shadow-xl border border-slate-200/90 z-[400] flex items-center gap-2.5 transition-all">
                    <span id="map-pill-icon" class="material-symbols-outlined text-secondary-container text-lg shrink-0">location_on</span>
                    <span id="map-pill-text" class="truncate font-medium">روی نقشه کلیک کنید تا نشانی استخراج شود</span>
                </div>

                <div class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-xl text-[11px] font-bold text-slate-700 shadow-md border border-slate-200 z-[400] pointer-events-none">
                    نشانگر را بکشید یا روی هر نقطه کلیک کنید
                </div>
            </div>

            <!-- Real-time Autofill Alert Banner -->
            <div id="address-autofill-alert" class="hidden p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 animate-fade-in transition-all">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
                    <span id="autofill-alert-msg" class="font-bold">نشانی و شهر از روی نقشه استخراج و در فرم زیر درج شد.</span>
                </div>
                <span class="text-[11px] text-emerald-700 font-medium opacity-90">در صورت تمایل پلاک، طبقه و زنگ را تکمیل نمایید</span>
            </div>

            <!-- Address Update Form -->
            <form method="POST" action="actions/profile_action.php" class="space-y-4 pt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_address">
                <input type="hidden" name="latitude" id="address_latitude" value="<?= htmlspecialchars($user['latitude'] ?? '38.0700') ?>">
                <input type="hidden" name="longitude" id="address_longitude" value="<?= htmlspecialchars($user['longitude'] ?? '46.2931') ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>استان / شهر تحویل گیرنده *</span>
                            <span class="text-[10px] text-emerald-600 font-normal">تنظیم هوشمند از نقشه</span>
                        </label>
                        <input type="text" name="city" id="address_city" value="<?= htmlspecialchars($user['city'] ?? 'تبریز') ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-secondary-container font-medium text-xs text-slate-800 bg-slate-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">کد پستی ۱۰ رقمی (بدون خط تیره) *</label>
                        <input type="text" name="postal_code" id="address_postal_code" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>" maxlength="10" required placeholder="مثلاً: 5138612345" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-secondary-container font-mono text-left dir-ltr text-xs text-slate-800 bg-slate-50 focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">نشانی پستی دقیق (نام خیابان، کوچه، پلاک، طبقه و واحد) *</label>
                        <button type="button" onclick="triggerCurrentLocationGeocode()" class="text-[11px] text-primary hover:underline font-bold flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-xs">sync</span>
                            <span>استخراج مجدد از نشانگر نقشه</span>
                        </button>
                    </div>
                    <textarea name="address" id="address_text" rows="3" required placeholder="مثال: ولیعصر، خیابان توانیر، کوچه مریم، پلاک ۱۴، زنگ ۲" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-secondary-container font-medium text-xs text-slate-800 bg-slate-50 focus:bg-white transition-all leading-relaxed shadow-sm"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400">
                        با ذخیره این نشانی، تمام سفارشات آتی و اتوشیپ به این آدرس ارسال خواهند شد.
                    </span>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-secondary-container hover:bg-[#ea580c] text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>ثبت و به‌روزرسانی نهایی نشانی تحویل</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 4: Pets (حیوانات خانگی، ماشین‌حساب دوز بالینی و مدارک پزشکی)          -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-pets" class="customer-view space-y-6 hidden">
        <div id="pets-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden mb-8 scroll-mt-24">
    <div class="px-6 py-4 border-b border-outline-variant bg-white flex items-center justify-between">
        <h3 class="text-sm font-bold text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">pets</span>
            <span>حیوانات خانگی من</span>
        </h3>
        <button type="button" onclick="document.getElementById('addPetModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary hover:text-white text-primary text-xs font-bold transition-all shadow-sm">
            <span class="material-symbols-outlined text-sm">add_circle</span>
            <span>افزودن حیوان جدید</span>
        </button>
    </div>
    <div class="p-6 space-y-4">
    <?php if(empty($pets)): ?>
        <div class="text-center py-6 border-2 border-dashed border-outline-variant/70 rounded-2xl p-5 bg-slate-50/60 space-y-3">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
                <span class="material-symbols-outlined text-2xl">pets</span>
            </div>
            <div>
                <p class="text-sm font-bold text-on-surface">هنوز حیوان خانگی ثبت نکرده‌اید.</p>
                <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                    با ثبت مشخصات پت، پرونده سلامت فعال شده و نوبت‌گیری پزشک با یک کلیک انجام می‌شود.
                </p>
            </div>
            <button type="button" onclick="document.getElementById('addPetModal').classList.remove('hidden')" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-md active:scale-95">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                <span>افزودن و تکمیل شناسنامه پت</span>
            </button>
        </div>
    <?php else: ?>
        <?php foreach($pets as $pet): ?>
        <div class="p-4 border border-outline-variant rounded-2xl hover:border-primary transition-all bg-white shadow-sm group space-y-3">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-bold transition-transform group-hover:scale-105 shrink-0 mt-0.5">
                    <span class="material-symbols-outlined text-2xl"><?php echo $pet['type'] == 'گربه' ? 'cat' : ($pet['type'] == 'سگ' ? 'dog' : 'pets'); ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="text-sm font-black text-on-surface truncate"><?php echo htmlspecialchars($pet['name']); ?></h4>
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-blue-50 text-blue-700 border border-blue-200"><?php echo htmlspecialchars($pet['type']); ?></span>
                        <?php if(!empty($pet['clinical_verified_at'])): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-0.5" title="پرونده توسط دکتر دامپزشک تایید شده است">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>تأیید بالینی پزشک</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2 mt-1 flex-wrap text-[11px] text-on-surface-variant">
                        <?php if(!empty($pet['race'])): ?>
                            <span>نژاد: <strong><?= htmlspecialchars($pet['race']) ?></strong></span>
                        <?php endif; ?>
                        <?php if(!empty($pet['weight_kg'])): ?>
                            <span class="text-indigo-700 font-bold"> • وزن: <?= $pet['weight_kg'] ?> کیلوگرم</span>
                        <?php endif; ?>
                        <?php if(!empty($pet['gender'])): ?>
                            <span> • جنسیت: <?= htmlspecialchars($pet['gender']) ?></span>
                        <?php endif; ?>
                        <?php if(!empty($pet['age'])): ?>
                            <span> • سن: <?= htmlspecialchars($pet['age']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($pet['microchip_number'])): ?>
                        <div class="text-[10px] text-slate-500 font-mono mt-1">
                            میکروچیپ: <span class="dir-ltr font-bold text-slate-700"><?= htmlspecialchars($pet['microchip_number']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($pet['allergies'])): ?>
                        <div class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-lg mt-1.5">
                            <span class="material-symbols-outlined text-xs text-rose-600">warning</span>
                            <span>آلرژی/حساسیت دارویی: <?= htmlspecialchars($pet['allergies']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" onclick="openEditPetModal(<?php echo $pet['id']; ?>, '<?php echo addslashes(htmlspecialchars($pet['name'])); ?>', '<?php echo addslashes(htmlspecialchars($pet['type'])); ?>', '<?php echo addslashes(htmlspecialchars($pet['race'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($pet['gender'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($pet['age'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars((string)($pet['weight_kg'] ?? ''))); ?>', '<?php echo addslashes(htmlspecialchars($pet['microchip_number'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($pet['allergies'] ?? '')); ?>')" class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-slate-100 transition-colors" title="ویرایش شناسنامه">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                    <form action="actions/profile_action.php" method="POST" onsubmit="return confirm('آیا از حذف این حیوان خانگی اطمینان دارید؟');" class="inline m-0">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_pet">
                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                        <button type="submit" class="p-2 rounded-lg text-on-surface-variant hover:text-error hover:bg-rose-50 transition-colors" title="حذف">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($pet['pending_doctor_proposal'])): 
                $prop = json_decode($pet['pending_doctor_proposal'], true);
                if ($prop):
            ?>
            <!-- Interactive Doctor Consensus Proposal Card -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-amber-200/80">
                    <div class="flex items-center gap-2 text-amber-950 font-black text-xs">
                        <span class="material-symbols-outlined text-amber-600 text-lg">clinical_notes</span>
                        <span>پیشنهاد به‌روزرسانی پرونده بالینی توسط <?= htmlspecialchars($prop['doctor_name'] ?? 'دکتر دامپزشک') ?></span>
                    </div>
                    <span class="text-[10px] text-amber-700 font-mono"><?= htmlspecialchars($prop['proposed_at'] ?? '') ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                    <?php if (!empty($prop['new_weight']) && (float)$prop['new_weight'] != (float)($pet['weight_kg'] ?? 0)): ?>
                    <div class="bg-white p-2.5 rounded-xl border border-amber-200">
                        <span class="text-slate-500 block text-[10px]">تغییر وزن معاینه‌شده:</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-slate-600 line-through"><?= $pet['weight_kg'] ? $pet['weight_kg'] . ' kg' : 'ثبت‌نشده' ?></span>
                            <span class="material-symbols-outlined text-xs text-amber-600">arrow_forward</span>
                            <span class="text-amber-800 font-black text-xs"><?= $prop['new_weight'] ?> کیلوگرم</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($prop['new_allergies'])): ?>
                    <div class="bg-white p-2.5 rounded-xl border border-amber-200">
                        <span class="text-slate-500 block text-[10px]">حساسیت دارویی شناسایی‌شده:</span>
                        <span class="font-bold text-rose-700 block mt-0.5"><?= htmlspecialchars($prop['new_allergies']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($prop['diagnosis'])): ?>
                <div class="text-[11px] bg-white p-2.5 rounded-xl border border-amber-200 text-slate-700 space-y-1">
                    <span class="font-bold text-slate-900 block">تشخیص پزشک معالج:</span>
                    <p class="text-slate-600 leading-relaxed"><?= htmlspecialchars($prop['diagnosis']) ?></p>
                </div>
                <?php endif; ?>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <form action="actions/pet_proposal_action.php" method="POST" class="inline m-0">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="reject_proposal">
                        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors border border-slate-200">رد پیشنهاد</button>
                    </form>
                    <form action="actions/pet_proposal_action.php" method="POST" class="inline m-0">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="accept_proposal">
                        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                        <button type="submit" class="px-4 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-sm transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">check</span>
                            <span>تأیید و به‌روزرسانی شناسنامه</span>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Add New Pet Button/Form Area -->
        <div class="pt-2">
            <button type="button" onclick="document.getElementById('addPetModal').classList.remove('hidden')" class="w-full border-2 border-dashed border-outline-variant text-on-surface-variant py-3 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 hover:bg-white hover:border-primary hover:text-primary transition-all group shadow-sm">
                <span class="material-symbols-outlined group-hover:scale-110 transition-transform text-sm">add_circle</span>
                <span>افزودن حیوان خانگی جدید به شناسنامه</span>
            </button>
        </div>
    <?php endif; ?>
    </div>
</div>



    <?php
    // ══════════════════════════════════════════════════════════════════════════════
    // Unified Pet Medical & Clinical Health Hub Aggregator
    // Combines Calculator Diet Plans, Drug Interactions, Doctor Reports,
    // Pharmacy Prescriptions, and Pet Parent Uploads into an enterprise dashboard.
    // ══════════════════════════════════════════════════════════════════════════════
    $unifiedRecords = [];

    // 1. Documents from pet_documents (Diet plans, Drug reports, Clinic docs, Lab results)
    if (!empty($documents)) {
        foreach ($documents as $doc) {
            $title = $doc['title'] ?? '';
            $path = $doc['file_path'] ?? '';
            $petName = $doc['pet_name'] ?? (!empty($pets) ? $pets[0]['name'] : 'تدی');
            $petId = (int)($doc['pet_id'] ?? 0);
            $uploadedAt = $doc['uploaded_at'] ?? date('Y-m-d H:i:s');
            
            $isDrugReport = str_contains($title, 'تداخل') || str_contains($path, 'drug_report');
            $isMealPlan = str_contains($title, 'برنامه غذایی') || str_contains($path, 'meal_plan');
            $isDoctorClinicDoc = str_contains($path, 'clinical_docs');

            if ($isDrugReport) {
                $cat = 'drugs';
                $catLabel = 'پایش تداخلات دارویی';
                $sourceType = 'interactions';
                $sourceLabel = 'سامانه فارماکولوژی بالینی و تداخل‌سنج دارویی آسنا';
                $sourceBadge = '🔬 پایشگر دارویی';
                $sourceBadgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
                $icon = 'medication';
                $iconColor = 'text-amber-600 bg-amber-500/10 border-amber-200';
                $viewUrl = 'view_drug_report.php?file=' . urlencode(basename($path));
                $canDelete = false;
            } elseif ($isMealPlan) {
                $cat = 'nutrition';
                $catLabel = 'رژیم غذایی بالینی';
                $sourceType = 'calculator';
                $sourceLabel = 'موتور هوش مصنوعی و محاسبه‌گر تغذیه بالینی آسنا (WSAVA & FEDIAF)';
                $sourceBadge = '🤖 محاسبه‌گر تغذیه آسنا';
                $sourceBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                $icon = 'restaurant';
                $iconColor = 'text-emerald-600 bg-emerald-500/10 border-emerald-200';
                $viewUrl = 'view_meal_plan.php?file=' . urlencode(basename($path));
                $canDelete = false;
            } elseif ($isDoctorClinicDoc) {
                $cat = 'clinical';
                $catLabel = 'پرونده کلینیک و بیمارستان';
                $sourceType = 'doctor';
                $sourceLabel = 'مرکز درمانی و دکتر دامپزشک همکار آسنا';
                $sourceBadge = '🩺 درمانگاه / پزشک';
                $sourceBadgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-300';
                $icon = 'medical_services';
                $iconColor = 'text-indigo-600 bg-indigo-500/10 border-indigo-200';
                $viewUrl = htmlspecialchars($path);
                $canDelete = false;
            } else {
                $cat = 'user_uploads';
                $catLabel = 'مدرک و چکاپ سرپرست';
                $sourceType = 'user';
                $sourceLabel = 'بارگذاری شده توسط سرپرست پت';
                $sourceBadge = '👤 سرپرست پت';
                $sourceBadgeClass = 'bg-blue-100 text-blue-800 border-blue-300';
                $icon = 'description';
                $iconColor = 'text-blue-600 bg-blue-500/10 border-blue-200';
                $viewUrl = htmlspecialchars($path);
                $canDelete = true;
            }

            $unifiedRecords[] = [
                'id' => 'doc_' . $doc['id'],
                'raw_id' => $doc['id'],
                'pet_id' => $petId,
                'pet_name' => $petName,
                'title' => $title,
                'category' => $cat,
                'category_label' => $catLabel,
                'source_type' => $sourceType,
                'source_label' => $sourceLabel,
                'source_badge' => $sourceBadge,
                'source_badge_class' => $sourceBadgeClass,
                'icon' => $icon,
                'icon_color' => $iconColor,
                'date' => date('Y/m/d', strtotime($uploadedAt)),
                'time' => date('H:i', strtotime($uploadedAt)),
                'timestamp' => strtotime($uploadedAt),
                'view_url' => $viewUrl,
                'file_path' => $path,
                'can_delete' => $canDelete
            ];
        }
    }

    // 2. Prescriptions (Doctor & Pharmacy records)
    if (!empty($prescriptions)) {
        foreach ($prescriptions as $rx) {
            $vetName = $rx['vet_name'] ?: ($rx['doctor_name'] ?? '');
            $clinicName = $rx['clinic_name'] ?: 'داروخانه تخصصی آسنا';
            $rxPetName = $rx['pet_name'] ?? (!empty($pets) ? $pets[0]['name'] : 'تدی');
            $rxCreatedAt = $rx['created_at'] ?? date('Y-m-d H:i:s');
            $status = $rx['status'] ?? 'pending';
            $statusText = match($status) {
                'approved' => 'نسخه معتبر و تأییدشده داروخانه',
                'rejected' => 'عدم تأیید داروساز',
                'expired' => 'منقضی شده',
                default => 'در حال بررسی توسط داروساز کشیک'
            };
            $statusBadgeClass = match($status) {
                'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'rejected' => 'bg-rose-100 text-rose-800 border-rose-300',
                'expired' => 'bg-slate-100 text-slate-700 border-slate-300',
                default => 'bg-amber-100 text-amber-800 border-amber-300'
            };

            $unifiedRecords[] = [
                'id' => 'rx_' . $rx['id'],
                'raw_id' => $rx['id'],
                'pet_id' => (int)($rx['pet_id'] ?? 0),
                'pet_name' => $rxPetName,
                'title' => 'نسخه دارویی ' . ($clinicName ? ('- ' . $clinicName) : '') . ($vetName ? (' (دکتر ' . $vetName . ')') : ''),
                'category' => 'prescriptions',
                'category_label' => 'نسخه دارویی بالینی',
                'source_type' => 'pharmacy',
                'source_label' => $vetName ? ('دکتر دامپزشک: ' . $vetName . ' • ' . $clinicName) : ('داروخانه تخصصی آسنا • ' . $clinicName),
                'source_badge' => '💊 داروخانه / نسخه پزشک',
                'source_badge_class' => 'bg-purple-100 text-purple-800 border-purple-300',
                'icon' => 'prescriptions',
                'icon_color' => 'text-purple-600 bg-purple-500/10 border-purple-200',
                'date' => date('Y/m/d', strtotime($rxCreatedAt)),
                'time' => date('H:i', strtotime($rxCreatedAt)),
                'timestamp' => strtotime($rxCreatedAt),
                'view_url' => htmlspecialchars($rx['rx_file_url'] ?? '#'),
                'file_path' => $rx['rx_file_url'] ?? '',
                'status_text' => $statusText,
                'status_class' => $statusBadgeClass,
                'notes' => $rx['pharmacist_notes'] ?? '',
                'can_delete' => false
            ];
        }
    }

    // 3. Appointments & Doctor Consultations
    if (!empty($appointments)) {
        foreach ($appointments as $appt) {
            if (!empty($appt['notes']) || ($appt['status'] ?? '') === 'completed') {
                $docName = $appt['doctor_name'] ?? 'دامپزشک معالج';
                $apptDate = ($appt['appointment_date'] ?? date('Y-m-d')) . ' ' . ($appt['appointment_time'] ?? '00:00:00');
                $unifiedRecords[] = [
                    'id' => 'appt_' . $appt['id'],
                    'raw_id' => $appt['id'],
                    'pet_id' => (int)($appt['pet_id'] ?? 0),
                    'pet_name' => $appt['pet_name'] ?? (!empty($pets) ? $pets[0]['name'] : 'تدی'),
                    'title' => 'گزارش ویزیت بالینی و چکاپ: دکتر ' . $docName,
                    'category' => 'clinical',
                    'category_label' => 'ویزیت و مشاوره پزشک',
                    'source_type' => 'doctor',
                    'source_label' => 'دکتر دامپزشک: ' . $docName . ' (' . ($appt['doctor_specialty'] ?? 'دامپزشک') . ')',
                    'source_badge' => '🩺 ویزیت پزشک',
                    'source_badge_class' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                    'icon' => 'stethoscope',
                    'icon_color' => 'text-indigo-600 bg-indigo-500/10 border-indigo-200',
                    'date' => date('Y/m/d', strtotime($apptDate)),
                    'time' => date('H:i', strtotime($apptDate)),
                    'timestamp' => strtotime($apptDate),
                    'view_url' => 'booking.php?view_appt=' . (int)$appt['id'],
                    'file_path' => '',
                    'notes' => $appt['notes'] ?? 'ویزیت و مشاوره انجام شده',
                    'can_delete' => false
                ];
            }
        }
    }

    // Sort by timestamp desc
    usort($unifiedRecords, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

    // Counts for UI filter badges
    $totalCount = count($unifiedRecords);
    $countNutrition = count(array_filter($unifiedRecords, fn($r) => $r['category'] === 'nutrition'));
    $countDrugs = count(array_filter($unifiedRecords, fn($r) => $r['category'] === 'drugs'));
    $countRx = count(array_filter($unifiedRecords, fn($r) => $r['category'] === 'prescriptions'));
    $countClinical = count(array_filter($unifiedRecords, fn($r) => $r['category'] === 'clinical'));
    $countUploads = count(array_filter($unifiedRecords, fn($r) => $r['category'] === 'user_uploads'));
    ?>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- UNIFIED PET MEDICAL & CLINICAL RECORDS HUB (سازمان، داروخانه، پزشک، محاسبه‌گر و سرپرست) -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-md overflow-hidden scroll-mt-24">
        <!-- Main Hub Header -->
        <div class="p-6 bg-gradient-to-r from-slate-900 via-[#001a48] to-slate-900 text-white flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-white shadow-inner">
                        <span class="material-symbols-outlined text-2xl">health_and_safety</span>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                            <span>پرونده یکپارچه سلامت و سوابق پزشکی</span>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-white/15 text-white/90 border border-white/20">
                                <?= count($pets) > 0 ? htmlspecialchars(implode(' و ', array_column($pets, 'name'))) : 'حیوانات خانگی' ?>
                            </span>
                        </h3>
                        <p class="text-xs text-slate-300 font-medium leading-relaxed">
                            پایش متمرکز اسناد درمانی، کارنامه‌های تغذیه، تداخلات دارویی، نسخه‌ها و چکاپ‌های صادرشده توسط پزشکان، داروخانه‌ها و سرپرست
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="document.getElementById('addDocModal').classList.remove('hidden')" class="px-4 py-2.5 bg-primary-container text-white rounded-xl font-bold text-xs hover:shadow-lg transition-all flex items-center gap-1.5 shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>ثبت و آپلود مدرک</span>
                </button>
                <a href="calculator.php" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition-all flex items-center gap-1.5 shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-sm">calculate</span>
                    <span>محاسبه‌گر رژیم غذایی</span>
                </a>
                <a href="interactions.php" class="px-3.5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl font-bold text-xs transition-all flex items-center gap-1.5 shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-sm">medication</span>
                    <span>پایش تداخل دارویی</span>
                </a>
            </div>
        </div>

        <!-- Connected Entities Bar (5 Channels) -->
        <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex items-center gap-3 overflow-x-auto text-[11px] font-bold text-slate-600 no-scrollbar">
            <span class="text-slate-400 font-medium shrink-0 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs text-emerald-600">sync_alt</span>
                کانال‌های متصل به پرونده:
            </span>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xs text-indigo-600">apartment</span>
                <span>کلینیک و بیمارستان</span>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xs text-teal-600">stethoscope</span>
                <span>پزشکان معالج</span>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xs text-purple-600">prescriptions</span>
                <span>داروخانه‌ها</span>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xs text-emerald-600">smart_toy</span>
                <span>هوش مصنوعی و محاسبه‌گر</span>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xs text-blue-600">person</span>
                <span>سرپرست پت</span>
            </div>
        </div>

        <!-- Filter Tabs Bar -->
        <div class="p-6 pb-2 space-y-4">
            <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar" id="docCategoryTabs">
                <button type="button" onclick="filterDocArchive('all', this)" class="doc-tab-btn px-4 py-2 rounded-xl text-xs font-black transition-all bg-primary text-white shadow-md flex items-center gap-1.5 shrink-0">
                    <span>همه سوابق و مدارک</span>
                    <span class="bg-white/20 text-white text-[10px] px-2 py-0.2 rounded-full"><?= $totalCount ?></span>
                </button>

                <button type="button" onclick="filterDocArchive('nutrition', this)" class="doc-tab-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm text-emerald-600">restaurant</span>
                    <span>رژیم‌های غذایی بالینی</span>
                    <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.2 rounded-full font-black"><?= $countNutrition ?></span>
                </button>

                <button type="button" onclick="filterDocArchive('drugs', this)" class="doc-tab-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm text-amber-600">medication</span>
                    <span>پایش تداخلات دارویی</span>
                    <span class="bg-amber-100 text-amber-800 text-[10px] px-2 py-0.2 rounded-full font-black"><?= $countDrugs ?></span>
                </button>

                <button type="button" onclick="filterDocArchive('prescriptions', this)" class="doc-tab-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm text-purple-600">prescriptions</span>
                    <span>نسخه‌ها و داروخانه</span>
                    <span class="bg-purple-100 text-purple-800 text-[10px] px-2 py-0.2 rounded-full font-black"><?= $countRx ?></span>
                </button>

                <button type="button" onclick="filterDocArchive('clinical', this)" class="doc-tab-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm text-indigo-600">medical_information</span>
                    <span>ویزیت و اسناد درمانگاه</span>
                    <span class="bg-indigo-100 text-indigo-800 text-[10px] px-2 py-0.2 rounded-full font-black"><?= $countClinical ?></span>
                </button>

                <button type="button" onclick="filterDocArchive('user_uploads', this)" class="doc-tab-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm text-blue-600">description</span>
                    <span>مدارک و آزمایشات سرپرست</span>
                    <span class="bg-blue-100 text-blue-800 text-[10px] px-2 py-0.2 rounded-full font-black"><?= $countUploads ?></span>
                </button>
            </div>

            <!-- Pet Filter Chips (if multiple pets) -->
            <?php if (count($pets) > 1): ?>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-500 pt-1">
                    <span class="text-[11px] text-slate-400">فیلتر پت:</span>
                    <button type="button" onclick="filterByPet('all', this)" class="pet-filter-chip px-3 py-1 rounded-lg bg-primary/10 text-primary font-black border border-primary/20">همه پت‌ها</button>
                    <?php foreach ($pets as $pt): ?>
                        <button type="button" onclick="filterByPet('<?= (int)$pt['id'] ?>', this)" class="pet-filter-chip px-3 py-1 rounded-lg bg-white text-slate-700 border border-slate-200 hover:border-primary"><?= htmlspecialchars($pt['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Records List Body -->
        <div class="p-6 pt-2 space-y-3" id="docRecordsListContainer">
            <?php if (empty($unifiedRecords)): ?>
                <!-- Clean Empty State with Action Buttons -->
                <div class="text-center py-12 px-6 bg-slate-50/70 rounded-3xl border border-dashed border-slate-300 space-y-4">
                    <div class="w-16 h-16 rounded-3xl bg-primary/10 text-primary flex items-center justify-center mx-auto shadow-inner">
                        <span class="material-symbols-outlined text-3xl">clinical_notes</span>
                    </div>
                    <div class="max-w-md mx-auto space-y-1">
                        <h4 class="text-base font-black text-slate-900">پرونده پزشکی و درمانی آماده ثبت مدارک است</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            هنوز سندی در پرونده سلامت ثبت نشده است. شما می‌توانید آزمایش‌ها، گواهی واکسن یا شناسنامه پت را آپلود فرمایید یا با محاسبه‌گر هوشمند تغذیه، رژیم بالینی استاندارد WSAVA صادر نمایید.
                        </p>
                    </div>
                    <div class="flex items-center justify-center gap-3 flex-wrap pt-2">
                        <button type="button" onclick="document.getElementById('addDocModal').classList.remove('hidden')" class="px-5 py-2.5 bg-primary text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 hover:bg-primary-hover">
                            <span class="material-symbols-outlined text-sm">upload_file</span>
                            <span>آپلود اولین مدرک پت</span>
                        </button>
                        <a href="calculator.php" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">calculate</span>
                            <span>صدور رایگان جدول رژیم غذایی</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($unifiedRecords as $rec): ?>
                    <div class="doc-record-row group p-4 sm:p-5 bg-white hover:bg-slate-50/80 rounded-2xl border border-slate-200/90 hover:border-primary/40 shadow-sm hover:shadow-md transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                         data-category="<?= htmlspecialchars($rec['category']) ?>"
                         data-pet-id="<?= (int)$rec['pet_id'] ?>">
                        
                        <!-- Right: Icon & Core Details -->
                        <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-2xl <?= $rec['icon_color'] ?> border flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-2xl"><?= htmlspecialchars($rec['icon']) ?></span>
                            </div>

                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-black text-slate-900 truncate">
                                        <?= htmlspecialchars($rec['title']) ?>
                                    </h4>
                                    <!-- Source Badge -->
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border <?= $rec['source_badge_class'] ?>">
                                        <?= htmlspecialchars($rec['source_badge']) ?>
                                    </span>
                                    <?php if (!empty($rec['status_text'])): ?>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?= $rec['status_class'] ?>">
                                            <?= htmlspecialchars($rec['status_text']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-3 flex-wrap text-[11px] text-slate-500 font-medium">
                                    <span class="text-primary font-bold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">pets</span>
                                        <?= htmlspecialchars($rec['pet_name']) ?>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1 font-mono text-slate-600">
                                        <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                        <?= htmlspecialchars($rec['date']) ?> (<?= htmlspecialchars($rec['time']) ?>)
                                    </span>
                                    <span>•</span>
                                    <span class="text-slate-500 truncate max-w-[280px]">
                                        صادرکننده: <?= htmlspecialchars($rec['source_label']) ?>
                                    </span>
                                </div>

                                <?php if (!empty($rec['notes'])): ?>
                                    <p class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded-xl border border-slate-200/60 line-clamp-1">
                                        <span class="font-bold text-slate-700">توضیحات:</span> <?= htmlspecialchars($rec['notes']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Left: Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <?php if (!empty($rec['view_url']) && $rec['view_url'] !== '#'): ?>
                                <a href="<?= $rec['view_url'] ?>" target="_blank" rel="noopener noreferrer" class="px-3.5 py-2 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-sm">
                                    <span>مشاهده و بررسی</span>
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($rec['file_path'])): ?>
                                <a href="<?= htmlspecialchars($rec['file_path']) ?>" download class="p-2 rounded-xl text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200" title="دانلود فایل">
                                    <span class="material-symbols-outlined text-lg">download</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($rec['can_delete']): ?>
                                <form action="actions/profile_action.php" method="POST" onsubmit="return confirm('آیا از حذف این مدرک از پرونده سلامت اطمینان دارید؟');" class="inline m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="doc_id" value="<?= (int)$rec['raw_id'] ?>">
                                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-error hover:bg-rose-50 transition-colors border border-transparent hover:border-rose-200" title="حذف مدرک">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Tab Filter Empty State (Hidden by default, shown by JS if filtered tab is empty) -->
            <div id="docFilterEmptyState" class="hidden text-center py-10 px-4 bg-slate-50 rounded-2xl border border-dashed border-slate-300 space-y-3">
                <span class="material-symbols-outlined text-slate-400 text-3xl">filter_list_off</span>
                <p class="text-xs font-bold text-slate-700">مدرکی در این دسته‌بندی برای پت ثبت نشده است.</p>
                <div class="flex items-center justify-center gap-2 pt-1">
                    <button type="button" onclick="filterDocArchive('all', document.querySelector('.doc-tab-btn'))" class="px-3.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition-all">
                        نمایش همه سوابق
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer: Full Archive Export -->
        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                <span class="material-symbols-outlined text-base text-emerald-600">verified_user</span>
                <span>کلیه مدارک بالینی دارای تأییدیه اصالت دیجیتال و کد رهگیری هستند.</span>
            </div>
            <a href="download_all.php" class="w-full sm:w-auto px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all shadow-sm">
                <span class="material-symbols-outlined text-sm">download</span>
                <span>دریافت پرونده کامل سلامت (فایل فشرده ZIP)</span>
            </a>
        </div>
    </div>

    <!-- Client-side Interactive Filter Script -->
    <script>
    function filterDocArchive(category, el) {
        document.querySelectorAll('.doc-tab-btn').forEach(btn => {
            btn.classList.remove('bg-primary', 'text-white', 'shadow-md');
            btn.classList.add('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');
        });
        if (el) {
            el.classList.remove('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');
            el.classList.add('bg-primary', 'text-white', 'shadow-md');
        }

        const items = document.querySelectorAll('.doc-record-row');
        let visibleCount = 0;
        items.forEach(row => {
            const rowCat = row.getAttribute('data-category');
            if (category === 'all' || rowCat === category) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyBox = document.getElementById('docFilterEmptyState');
        if (emptyBox) {
            if (visibleCount === 0 && items.length > 0) {
                emptyBox.classList.remove('hidden');
            } else {
                emptyBox.classList.add('hidden');
            }
        }
    }

    function filterByPet(petId, el) {
        document.querySelectorAll('.pet-filter-chip').forEach(btn => {
            btn.classList.remove('bg-primary/10', 'text-primary', 'font-black', 'border-primary/20');
            btn.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        if (el) {
            el.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            el.classList.add('bg-primary/10', 'text-primary', 'font-black', 'border-primary/20');
        }

        const items = document.querySelectorAll('.doc-record-row');
        items.forEach(row => {
            const rowPet = row.getAttribute('data-pet-id');
            if (petId === 'all' || rowPet === petId) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 5: Appointments (نوبت‌های مشاوره و ویزیت دامپزشکی)                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-appointments" class="customer-view space-y-6 hidden">
        <div class="flex items-center justify-between bg-surface-container-lowest rounded-3xl border border-outline-variant p-6 shadow-sm">
            <div>
                <h3 class="text-lg font-black text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-teal-600">calendar_month</span>
                    نوبت‌های رزرو شده و مشاوره‌های آنلاین
                </h3>
                <p class="text-xs text-on-surface-variant mt-1">مدیریت نوبت‌های ویزیت، اتاق مشاوره ویدیویی و پرونده پزشکان</p>
            </div>
            <a href="booking.php" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-hover transition-all shadow-md flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">add_circle</span>
                رزرو نوبت جدید
            </a>
        </div>
        <!-- Upcoming Appointments (High Fidelity) -->
<div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
<h3 class="text-lg font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined">medical_information</span>
                            نوبت‌های پیش رو
                        </h3>
<button class="text-sm font-bold text-primary-container hover:underline">مشاهده همه</button>
</div>
<div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
<?php if (empty($appointments)): ?>
    <div class="col-span-full text-center py-8 text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">calendar_month</span>
        <p class="font-bold">شما هیچ نوبت رزرو شده‌ای ندارید.</p>
        <a href="booking.php" class="text-primary-container hover:underline text-sm mt-2 inline-block">برای رزرو نوبت کلیک کنید</a>
    </div>
<?php else: ?>
    <?php foreach ($appointments as $apt): ?>
        <div class="group border border-outline-variant p-5 rounded-2xl flex flex-col gap-4 hover:border-primary-container hover:shadow-xl transition-all duration-300">
        <div class="flex gap-4">
        <div class="relative">
        <img alt="<?php echo htmlspecialchars($apt['doctor_name']); ?>" class="w-16 h-16 rounded-xl object-cover" src="<?php echo htmlspecialchars($apt['doctor_image'] ?? 'assets/images/placeholders/placeholder-doctor.svg'); ?>"/>
        <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-status-active border-2 border-white rounded-full"></span>
        </div>
        <div class="flex-1">
        <div class="flex justify-between items-start">
        <h4 class="text-base font-bold text-primary"><?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
        <div class="flex items-center gap-0.5 text-status-warning">
        <span class="text-[10px] bg-primary-container/10 text-primary-container px-2 py-0.5 rounded-full font-bold"><?php echo htmlspecialchars($apt['status'] == 'pending' ? 'در انتظار' : 'تایید شده'); ?></span>
        </div>
        </div>
        <p class="text-xs text-on-surface-variant font-medium"><?php echo htmlspecialchars($apt['doctor_specialty']); ?></p>
        <?php if (!empty($apt['pet_type'])): ?>
            <div class="mt-2 flex gap-1">
            <span class="text-[10px] bg-secondary/10 text-secondary px-2 py-0.5 rounded-full font-bold">حیوان: <?php echo htmlspecialchars($apt['pet_type']); ?></span>
            </div>
        <?php endif; ?>
        </div>
        </div>
        <div class="bg-surface-container-low p-3 rounded-xl flex justify-between items-center persian-number text-xs font-bold">
        <div class="flex items-center gap-1.5 text-on-surface-variant">
        <span class="material-symbols-outlined text-base">calendar_today</span>
        <span><?php 
            echo $fmtDateText->format(new DateTime($apt['appointment_date'])); 
        ?></span>
        </div>
        <div class="flex items-center gap-1.5 text-on-surface-variant">
        <span class="material-symbols-outlined text-base">schedule</span>
        <span>ساعت <?php echo substr($apt['appointment_time'], 0, 5); ?></span>
        </div>
        </div>
        <?php if ($apt['status'] == 'approved'): ?>
            <button class="w-full bg-primary-container text-white py-2.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:brightness-110 transition-all mb-2">
                <span class="material-symbols-outlined text-lg">videocam</span>
                ورود به اتاق مشاوره
            </button>
            <button type="button" onclick="openRatingModal('doctor', <?= $apt['doctor_id'] ?>, 'دکتر <?= addslashes($apt['doctor_name']) ?>')" class="w-full bg-secondary-container/10 border border-secondary-container/30 text-secondary-container py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 hover:bg-secondary-container hover:text-white transition-all mb-2">
                <span class="material-symbols-outlined text-base">star</span>
                ثبت نظر و امتیاز به پزشک (+۵ امتیاز)
            </button>
            <div class="grid grid-cols-2 gap-2 mb-2">
                <a href="actions/calendar_export.php?id=<?= $apt['id'] ?>&format=google" target="_blank" class="bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 py-2 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                    <span class="material-symbols-outlined text-base">event</span>
                    تقویم گوگل
                </a>
                <a href="actions/calendar_export.php?id=<?= $apt['id'] ?>&format=ics" class="bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 py-2 px-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                    <span class="material-symbols-outlined text-base">download</span>
                    فایل تقویم (.ics)
                </a>
            </div>
        <?php else: ?>
            <div class="mb-2">
                <button onclick="document.getElementById('clinic_addr_<?= $apt['id'] ?>').classList.toggle('hidden')" class="w-full border-2 border-primary-container text-primary-container py-2 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:bg-primary-container hover:text-white transition-all">
                <span class="material-symbols-outlined text-lg">map</span>
                                                مشاهده آدرس کلینیک
                </button>
                <div id="clinic_addr_<?= $apt['id'] ?>" class="hidden mt-2 p-3 bg-surface-container-low text-on-surface text-sm rounded-lg border border-outline-variant/30 text-center font-bold">
                    تهران، ونک، خیابان ملاصدرا، پلاک ۱۲، کلینیک دامپزشکی پت‌شاپ
                </div>
            </div>
        <?php endif; ?>
        
        <?php if(in_array($apt['status'], ['pending', 'در انتظار', 'approved'])): ?>
            <form action="actions/profile_action.php" method="POST" onsubmit="return confirm('آیا از لغو این نوبت اطمینان دارید؟');" class="m-0">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="cancel_appointment">
                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                <button type="submit" class="w-full bg-error/10 text-error py-2.5 rounded-xl text-sm font-bold hover:bg-error hover:text-white transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">cancel</span>
                    لغو نوبت
                </button>
            </form>
        <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</div>

    </div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 6: Orders (تاریخچه سفارشات و فاکتورهای رسمی)                       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-orders" class="customer-view space-y-6 hidden">
        <div id="orders-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden scroll-mt-24">
    <div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-surface-container-low to-transparent">
        <h3 class="text-base font-bold text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary-container">shopping_bag</span>
            سفارشات و فاکتورهای من
        </h3>
        <span class="text-xs font-bold text-on-surface-variant bg-white px-3 py-1 rounded-full border border-outline-variant/40 persian-number"><?= count($orders) ?> سفارش ثبت شده</span>
    </div>

    <div class="p-6 space-y-6">
        <?php if(empty($orders)): ?>
            <div class="text-center py-12 text-on-surface-variant space-y-3">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-surface-container flex items-center justify-center text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-3xl">remove_shopping_cart</span>
                </div>
                <p class="font-bold text-sm">شما تاکنون هیچ سفارشی ثبت نکرده‌اید.</p>
                <a href="pharmacy.php" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary-container transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm">local_pharmacy</span>
                    مشاهده داروخانه و پت‌شاپ
                </a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <?php 
                $status_bg = 'bg-blue-50 text-blue-800 border-blue-200';
                $status_dot = 'bg-blue-600';
                $status_label = 'در حال پردازش';
                
                switch($order['status']) {
                    case 'pending_payment': 
                        $status_bg = 'bg-amber-50 text-amber-800 border-amber-200'; 
                        $status_dot = 'bg-amber-500';
                        $status_label = 'در انتظار پرداخت'; 
                        break;
                    case 'processing': 
                        $status_bg = 'bg-blue-50 text-blue-800 border-blue-200'; 
                        $status_dot = 'bg-blue-600';
                        $status_label = 'در حال پردازش در انبار'; 
                        break;
                    case 'shipped': 
                        $status_bg = 'bg-indigo-50 text-indigo-800 border-indigo-200'; 
                        $status_dot = 'bg-indigo-600';
                        $status_label = 'تحویل به پیک و پست'; 
                        break;
                    case 'delivered': 
                        $status_bg = 'bg-emerald-50 text-emerald-800 border-emerald-200'; 
                        $status_dot = 'bg-emerald-600';
                        $status_label = 'تحویل داده شده'; 
                        break;
                    case 'cancelled': 
                        $status_bg = 'bg-red-50 text-red-800 border-red-200'; 
                        $status_dot = 'bg-red-600';
                        $status_label = 'لغو شده'; 
                        break;
                }
                ?>
                <!-- Single Order Card -->
                <div class="border border-outline-variant/60 rounded-2xl p-5 bg-white hover:border-primary/40 transition-all space-y-4 shadow-sm hover:shadow-md">
                    <!-- Order Top Meta Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-outline-variant/30">
                        <div class="flex flex-wrap items-center gap-2.5 text-xs">
                            <span class="font-bold text-primary font-mono text-sm">#PC-<?= $order['id'] ?></span>
                            <span class="text-outline-variant">•</span>
                            <span class="text-on-surface-variant persian-number flex items-center gap-1 font-mono">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <?= $fmtDateText->format(new DateTime($order['created_at'])) ?>
                            </span>
                            <?php if (!empty($order['gateway_ref_id'])): ?>
                                <span class="text-outline-variant">•</span>
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-md font-mono text-[11px] border border-slate-200" title="شماره پیگیری پرداخت">
                                    کد رهگیری: <?= htmlspecialchars($order['gateway_ref_id']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-3 self-end sm:self-auto">
                            <!-- Official Digital Receipt Link -->
                            <a href="order_receipt.php?order_id=<?= $order['id'] ?>" target="_blank" class="text-primary bg-primary/5 hover:bg-primary/10 px-3 py-1 rounded-full text-xs font-bold transition-all flex items-center gap-1 border border-primary/20 shadow-2xs">
                                <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                                <span>رسید رسمی</span>
                            </a>

                            <!-- Status Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $status_bg ?>">
                                <span class="w-2 h-2 rounded-full <?= $status_dot ?> animate-pulse"></span>
                                <?= $status_label ?>
                            </span>
                            
                            <!-- Cancel Button if applicable -->
                            <?php if(in_array($order['status'], ['pending_payment', 'processing'])): ?>
                                <form action="actions/profile_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از لغو این سفارش اطمینان دارید؟');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="text-error hover:bg-error/10 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors">
                                        لغو سفارش
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Grid inside Order -->
                    <?php if (!empty($order['items'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach($order['items'] as $item): ?>
                                <?php 
                                    $is_pharma = (str_contains($item['category'] ?? '', 'دارو') || str_contains($item['category'] ?? '', 'مکمل') || !empty($item['pharmacy_tag']));
                                    $item_img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'assets/images/toy-mouse.jpg';
                                ?>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-surface-container-low/40 border border-outline-variant/30 hover:bg-surface-container-low transition-colors">
                                    <a href="product_details.php?id=<?= $item['product_id'] ?>" class="w-14 h-14 rounded-xl overflow-hidden bg-white shrink-0 border border-outline-variant/40 block hover:opacity-90 transition-opacity">
                                        <img src="<?= $item_img ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>">
                                    </a>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                            <?php if($is_pharma): ?>
                                                <span class="bg-secondary-container/15 text-secondary-container px-1.5 py-0.5 rounded text-[10px] font-bold">💊 دارویی</span>
                                            <?php else: ?>
                                                <span class="bg-primary/10 text-primary px-1.5 py-0.5 rounded text-[10px] font-bold">🛍️ پت‌شاپ</span>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($item['is_autoship'])): ?>
                                                <span class="bg-status-active/15 text-status-active px-1.5 py-0.5 rounded text-[10px] font-bold">🔄 تحویل دوره‌ای</span>
                                            <?php endif; ?>
                                        </div>

                                        <a href="product_details.php?id=<?= $item['product_id'] ?>" class="text-xs font-bold text-primary hover:text-primary-container transition-colors truncate block">
                                            <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                        </a>

                                        <div class="mt-1 flex items-center justify-between gap-2 flex-wrap text-xs">
                                            <span class="text-on-surface-variant font-mono text-[11px]">
                                                <?= $item['quantity'] ?> عدد × <?= number_format($item['price_at_purchase']) ?> تومان
                                            </span>
                                            <button type="button" onclick="openRatingModal('product', <?= $item['product_id'] ?>, '<?= addslashes($item['product_name_snapshot']) ?>')" class="text-[11px] font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">star</span>
                                                امتیاز به کالا
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Amazon.com Benchmark: 8-Stage Visual Fulfillment Stepper -->
                    <?php
                    $stages = [
                        'pending_payment' => ['title' => 'ثبت اولیه', 'step' => 1, 'icon' => 'receipt'],
                        'paid'            => ['title' => 'پرداخت شد', 'step' => 2, 'icon' => 'credit_card'],
                        'confirmed'       => ['title' => 'تأیید سفارش', 'step' => 3, 'icon' => 'verified'],
                        'picking'         => ['title' => 'انبارداری', 'step' => 4, 'icon' => 'inventory_2'],
                        'packed'          => ['title' => 'بسته‌بندی', 'step' => 5, 'icon' => 'package_2'],
                        'handed_over'     => ['title' => 'تحویل به ناوگان', 'step' => 6, 'icon' => 'local_shipping'],
                        'out_for_delivery'=> ['title' => 'در مسیر توزیع', 'step' => 7, 'icon' => 'electric_moped'],
                        'delivered'       => ['title' => 'تحویل شد', 'step' => 8, 'icon' => 'task_alt']
                    ];
                    $currentStageKey = $order['status'] ?? 'pending_payment';
                    $currentStepNum = $stages[$currentStageKey]['step'] ?? 1;
                    if ($order['status'] === 'shipped') $currentStepNum = 6;
                    if ($order['status'] === 'processing') $currentStepNum = 4;
                    ?>
                    <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-200/60 my-3">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-3">
                            <span class="flex items-center gap-1 text-primary">
                                <span class="material-symbols-outlined text-base text-secondary-container">timeline</span>
                                رهگیری لحظه‌ای مراحل ارسال (Amazon Fulfillment)
                            </span>
                            <span class="text-slate-500 font-normal text-[11px]">مرحله <?= $currentStepNum ?> از ۸</span>
                        </div>
                        <div class="overflow-x-auto pb-2">
                            <div class="flex items-center justify-between min-w-[540px] relative px-2">
                                <?php foreach($stages as $key => $meta): 
                                    $isDone = ($meta['step'] < $currentStepNum);
                                    $isActive = ($meta['step'] === $currentStepNum);
                                ?>
                                <div class="flex flex-col items-center text-center relative z-10 w-16">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-sm
                                        <?= $isDone ? 'bg-emerald-600 text-white' : ($isActive ? 'bg-primary text-white ring-4 ring-primary/20 scale-110 animate-pulse' : 'bg-white text-slate-400 border border-slate-300') ?>">
                                        <span class="material-symbols-outlined text-[15px]"><?= $meta['icon'] ?></span>
                                    </div>
                                    <span class="text-[10px] font-bold mt-1.5 leading-tight <?= $isActive ? 'text-primary' : ($isDone ? 'text-emerald-700' : 'text-slate-400') ?>">
                                        <?= $meta['title'] ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Footer with Total Amount & Tax Invoice Button -->
                    <div class="flex flex-col sm:flex-row items-center justify-between pt-3 border-t border-outline-variant/20 gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <a href="actions/generate_invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-primary hover:text-white text-slate-700 text-xs font-bold transition-all shadow-sm border border-slate-200">
                                <span class="material-symbols-outlined text-sm text-secondary-container">receipt_long</span>
                                مشاهده فاکتور رسمی ماده ۱۶۹ (قانون مالیات)
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-on-surface-variant">مبلغ کل پرداختی:</span>
                            <span class="font-bold text-sm text-primary font-mono">
                                <span class="text-base text-emerald-700"><?= number_format($order['total_amount']) ?></span> تومان
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW: Prescriptions (نسخه‌های دارویی و پیگیری تایید داروساز)           -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-prescriptions" class="customer-view space-y-6 hidden">
        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-outline-variant flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-surface-container-low to-transparent">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">medical_services</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-primary">نسخه‌های من و تأییدیه داروساز</h3>
                        <p class="text-[11px] text-on-surface-variant">پیگیری مراحل بررسی، قیمت‌گذاری و ارسال داروهای تجویزی</p>
                    </div>
                </div>
                <a href="pharmacy.php#prescriptionModal" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>ارسال نسخه جدید</span>
                </a>
            </div>

            <div class="p-4 md:p-6 space-y-4">
                <?php if(empty($prescriptions)): ?>
                    <div class="text-center py-12 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
                            <span class="material-symbols-outlined text-3xl">prescriptions</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-primary">تاکنون نسخه‌ای ثبت نکرده‌اید</h4>
                            <p class="text-xs text-on-surface-variant max-w-md mx-auto mt-1 leading-relaxed">
                                تصویر یا فایل نسخه پزشک را در داروخانه آنلاین آسنا بارگذاری فرمایید تا پس از تأیید داروسازان، اقلام دارویی آماده تحویل یا ارسال با زنجیره سرد گردند.
                            </p>
                        </div>
                        <a href="pharmacy.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-secondary-container text-white rounded-xl text-xs font-bold hover:bg-[#ea580c] transition-all shadow-md">
                            <span class="material-symbols-outlined text-sm">local_pharmacy</span>
                            <span>ورود به داروخانه و ارسال نسخه</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($prescriptions as $rx): 
                            $rxDispensing = $rx['dispensing_status'] ?? 'pending_review';
                            $rxStatus = $rx['status'] ?? 'pending';
                            
                            $badgeLabel = 'در انتظار بررسی داروساز';
                            $badgeStyle = 'bg-amber-50 text-amber-800 border-amber-200';
                            $badgeIcon = 'schedule';

                            if ($rxDispensing === 'preparing') {
                                $badgeLabel = 'در حال آماده‌سازی دارو';
                                $badgeStyle = 'bg-blue-50 text-blue-800 border-blue-200';
                                $badgeIcon = 'inventory';
                            } elseif ($rxDispensing === 'ready_for_pickup') {
                                $badgeLabel = 'آماده تحویل / پیک';
                                $badgeStyle = 'bg-purple-50 text-purple-800 border-purple-200';
                                $badgeIcon = 'local_shipping';
                            } elseif ($rxDispensing === 'dispensed' || $rxStatus === 'approved') {
                                $badgeLabel = 'تأیید و تحویل شده';
                                $badgeStyle = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                $badgeIcon = 'check_circle';
                            } elseif ($rxDispensing === 'cancelled' || $rxStatus === 'rejected') {
                                $badgeLabel = 'رد شده / لغو شده';
                                $badgeStyle = 'bg-rose-50 text-rose-800 border-rose-200';
                                $badgeIcon = 'cancel';
                            }
                        ?>
                        <div class="bg-white rounded-2xl border border-outline-variant/40 p-4 md:p-5 shadow-sm space-y-3 hover:border-emerald-300 transition-all">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-mono font-black text-xs text-primary bg-surface-container px-2.5 py-1 rounded-lg">
                                        <?= htmlspecialchars($rx['tracking_code'] ?: ('RX-' . $rx['id'])) ?>
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant block mt-1">
                                        ثبت: <?= htmlspecialchars(substr($rx['created_at'], 0, 10)) ?>
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border flex items-center gap-1 <?= $badgeStyle ?>">
                                    <span class="material-symbols-outlined text-xs"><?= $badgeIcon ?></span>
                                    <span><?= $badgeLabel ?></span>
                                </span>
                            </div>

                            <div class="text-xs space-y-1 text-slate-700 bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/20">
                                <?php if (!empty($rx['pet_name'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">حیوان خانگی:</span>
                                    <span class="font-bold text-primary"><?= htmlspecialchars($rx['pet_name']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($rx['clinic_name'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">کلینیک / بیمارستان:</span>
                                    <span class="font-medium"><?= htmlspecialchars($rx['clinic_name']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($rx['vet_name'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">پزشک صادرکننده:</span>
                                    <span class="font-medium"><?= htmlspecialchars($rx['vet_name']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($rx['pharmacist_notes'])): ?>
                            <div class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-xl text-[11px] text-emerald-950">
                                <span class="font-bold text-emerald-900 block mb-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">notes</span>
                                    دستور دکتر داروساز:
                                </span>
                                <p class="leading-relaxed"><?= nl2br(htmlspecialchars($rx['pharmacist_notes'])) ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="pt-2 flex items-center gap-2 border-t border-outline-variant/20">
                                <?php if (!empty($rx['rx_file_url'])): ?>
                                <a href="<?= htmlspecialchars($rx['rx_file_url']) ?>" target="_blank" class="flex-1 py-2 px-3 bg-surface-container hover:bg-surface-container-high rounded-xl text-center text-xs font-bold text-primary transition-all flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    <span>مشاهده تصویر نسخه</span>
                                </a>
                                <?php endif; ?>
                                <a href="pharmacy.php" class="py-2 px-3 bg-secondary-container/10 hover:bg-secondary-container hover:text-white rounded-xl text-center text-xs font-bold text-secondary-container transition-all flex items-center justify-center gap-1" title="داروخانه">
                                    <span class="material-symbols-outlined text-sm">storefront</span>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 7: Subscriptions (برنامه‌های اشتراک هوشمند و اتوشیپ)               -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->

    <div id="view-subscriptions" class="customer-view space-y-6 hidden">
        <!-- Subscriptions (Visual Autoship Widget) -->
<div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-gradient-to-r from-surface-container-low to-transparent">
        <h3 class="text-sm font-bold text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary-container">cached</span>
            اشتراک‌های فعال
        </h3>
        <span class="text-[11px] font-bold text-secondary-container bg-secondary-container/10 px-2 py-0.5 rounded-full border border-secondary-container/20 persian-number"><?= count($subscriptions) ?> فعال</span>
    </div>
    <div class="p-5 space-y-4">
        <?php if(empty($subscriptions)): ?>
            <div class="text-center py-4 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">all_inclusive</span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    با اشتراک‌های دوره‌ای، محصولات پت با <strong>۱۵٪ تخفیف مداوم</strong> و <strong>ارسال منظم</strong> تامین می‌شوند.
                </p>
                <a href="subscriptions.php" class="w-full inline-flex items-center justify-center gap-2 bg-secondary-container hover:bg-[#ea580c] text-white py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    خرید و فعال‌سازی اشتراک
                </a>
            </div>
        <?php else: ?>
            <?php foreach($subscriptions as $sub): ?>
                <div class="p-3.5 border border-outline-variant/60 rounded-2xl flex items-center gap-3 hover:border-secondary-container/50 transition-all group bg-white shadow-sm">
                    <div class="w-12 h-12 bg-secondary-container/15 text-secondary-container rounded-xl flex items-center justify-center shrink-0 border border-secondary-container/20">
                        <span class="material-symbols-outlined text-2xl">all_inclusive</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <h4 class="text-xs font-bold text-primary truncate"><?= htmlspecialchars($sub['plan_name']) ?></h4>
                            <span class="bg-status-active/15 text-status-active text-[10px] font-bold px-2 py-0.2 rounded-full shrink-0">فعال</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant font-mono"><?= number_format($sub['amount']) ?> تومان</p>
                        <div class="mt-1 flex items-center justify-between text-[10px]">
                            <span class="text-secondary-container font-bold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">calendar_month</span>
                                نوبت: <?= $sub['next_delivery_date'] ? $fmtDateText->format(new DateTime($sub['next_delivery_date'])) : 'به‌زودی' ?>
                            </span>
                            <a href="#subscriptions-section" class="text-primary hover:underline font-bold">مدیریت</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="subscriptions.php" class="w-full border border-dashed border-outline-variant hover:border-secondary-container text-on-surface-variant hover:text-secondary-container py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all bg-surface-container-low/30 hover:bg-white">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                افزودن اشتراک جدید
            </a>
        <?php endif; ?>
    </div>
</div>

        <div id="subscriptions-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden mb-8 scroll-mt-24">
<div class="px-6 py-5 border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-secondary-container/10 via-primary-container/5 to-transparent">
<h3 class="text-base font-bold text-primary flex items-center gap-2">
<span class="material-symbols-outlined text-secondary-container">event_repeat</span>
برنامه‌های اشتراک هوشمند و Autoship
</h3>
<a href="subscriptions.php" class="text-xs font-bold text-secondary-container hover:underline bg-white px-3 py-1.5 rounded-xl border border-secondary-container/30 shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-sm">add_circle</span>
خرید پلن جدید
</a>
</div>
<div class="overflow-x-auto">
<table class="w-full text-right text-sm">
<thead>
<tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
<th class="px-6 py-4">پلن اشتراک</th>
<th class="px-6 py-4">مبلغ (تومان)</th>
<th class="px-6 py-4">وضعیت</th>
<th class="px-6 py-4">تاریخ خرید</th>
<th class="px-6 py-4">زمان ارسال بعدی</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/50">
<?php if (empty($user_subscriptions)): ?>
    <tr>
        <td colspan="5" class="px-6 py-8 text-center text-on-surface-variant font-bold">شما در حال حاضر اشتراک فعالی ندارید.</td>
    </tr>
<?php else: ?>
    <?php foreach ($user_subscriptions as $sub): ?>
    <tr class="hover:bg-surface-container-low/50 transition-colors group">
        <td class="px-6 py-4 font-bold text-primary"><?php echo htmlspecialchars($sub['plan_name']); ?></td>
        <td class="px-6 py-4 font-bold persian-number"><?php echo number_format($sub['amount']); ?></td>
        <td class="px-6 py-4">
            <span class="px-3 py-1 text-xs font-bold rounded-full <?php
                if($sub['status'] == 'active') echo 'bg-primary-container/20 text-primary-container';
                elseif($sub['status'] == 'ended') echo 'bg-surface-variant text-on-surface-variant';
                elseif($sub['status'] == 'cancelled') echo 'bg-error/20 text-error';
                else echo 'bg-surface-container text-on-surface';
            ?>">
                <?php 
                    $status_map = ['active'=>'فعال', 'ended'=>'پایان یافته', 'cancelled'=>'لغو شده'];
                    echo $status_map[$sub['status']] ?? $sub['status']; 
                ?>
            </span>
        </td>
        <td class="px-6 py-4 text-on-surface-variant persian-number text-xs" dir="ltr">
            <?php echo $fmtDateTime->format(new DateTime($sub['created_at'])); ?>
        </td>
        <td class="px-6 py-4 font-bold text-secondary-container persian-number">
            <?php echo $sub['next_delivery_date'] ? $fmtDateText->format(new DateTime($sub['next_delivery_date'])) : 'نامشخص'; ?>
        </td>
    </tr>
    <?php if ($sub['status'] === 'active'): ?>
    <!-- User Self-Service Action Bar (Reschedule / Postpone / Cancel) -->
    <tr class="bg-surface-container-low/30 border-b border-outline-variant/20">
        <td colspan="5" class="px-6 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="font-bold text-on-surface-variant">مدیریت نوبت تحویل:</span>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Reschedule Form -->
                    <form action="actions/subscription_action.php" method="POST" class="inline-flex items-center gap-1 m-0">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reschedule_delivery">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <input type="date" name="new_date" min="<?php echo date('Y-m-d'); ?>" required class="p-1 px-2 rounded-lg border border-outline-variant text-xs outline-none bg-white">
                        <button type="submit" class="bg-primary text-white px-2.5 py-1 rounded-lg font-bold hover:bg-primary-container transition-all">تغییر تاریخ</button>
                    </form>
                    
                    <!-- Skip / Postpone -->
                    <form action="actions/subscription_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از به تعویق انداختن این نوبت ارسال به مدت ۳۰ روز اطمینان دارید؟');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="skip_delivery">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <input type="hidden" name="skip_days" value="30">
                        <button type="submit" class="bg-surface-container-high hover:bg-surface-container-highest text-primary px-3 py-1 rounded-lg font-bold transition-all border border-outline-variant/40">به تعویق انداختن (+۳۰ روز)</button>
                    </form>

                    <!-- Cancel Anytime -->
                    <form action="actions/subscription_action.php" method="POST" class="inline m-0" onsubmit="return confirm('آیا از لغو این اشتراک اطمینان دارید؟');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="cancel_subscription">
                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                        <button type="submit" class="text-error hover:bg-error/10 px-2.5 py-1 rounded-lg font-bold transition-colors">لغو اشتراک</button>
                    </form>
                </div>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($sub['deliveries'])): ?>
    <tr class="bg-surface-container-lowest border-b border-outline-variant/30">
        <td colspan="5" class="p-4">
            <div class="space-y-2 pl-4 max-w-2xl text-right">
                <h4 class="font-bold text-primary text-xs mb-2">زمان‌بندی ارسال‌ها:</h4>
                <?php foreach($sub['deliveries'] as $del): ?>
                    <div class="flex flex-col md:flex-row md:items-center justify-between bg-surface-container-low border border-outline-variant/20 p-2.5 rounded-lg text-xs gap-3">
                        <div class="flex items-center gap-3">
                            <span class="font-black text-on-surface-variant persian-number bg-white px-2 py-1 rounded-md shadow-sm">ماه <?php echo $del['delivery_month']; ?></span>
                            <span class="text-outline persian-number"><?php echo $del['scheduled_date'] ? $fmtDateText->format(new DateTime($del['scheduled_date'])) : ''; ?></span>
                            
                            <?php 
                            $statusText = '';
                            switch($del['status']) {
                                case 'pending': $statusText = '<span class="text-status-warning bg-status-warning/10 px-2 py-0.5 rounded-full font-bold">آماده‌سازی نوبت</span>'; break;
                                case 'processing': $statusText = '<span class="text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full font-bold">در حال پردازش انبار</span>'; break;
                                case 'shipped': $statusText = '<span class="text-primary-fixed bg-primary-fixed-dim/20 px-2 py-0.5 rounded-full font-bold">ارسال شده</span>'; break;
                                case 'delivered': $statusText = '<span class="text-status-active bg-status-active/10 px-2 py-0.5 rounded-full font-bold">تحویل داده شده</span>'; break;
                                case 'not_received': $statusText = '<span class="text-error bg-error/10 px-2 py-0.5 rounded-full font-bold">گزارش عدم دریافت</span>'; break;
                            }
                            echo $statusText;
                            ?>

                            <?php if (($del['payment_status'] ?? 'paid') === 'paid'): ?>
                                <span class="text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-bold text-[10px] inline-flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                    تسویه شده
                                </span>
                            <?php else: ?>
                                <span class="text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full font-bold text-[10px] inline-flex items-center gap-0.5" title="پیش از موعد ارسال پیامک پرداخت ارسال می‌شود">
                                    <span class="material-symbols-outlined text-[12px]">payments</span>
                                    پرداخت ماهانه در موعد (پیامک یادآوری)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($del['status'] === 'shipped'): ?>
                            <div class="flex items-center gap-2 bg-secondary-container/30 px-3 py-1.5 rounded-lg">
                                <span class="font-bold text-primary mr-2">بسته این ماه را دریافت کردید؟</span>
                                <form action="actions/subscription_action.php" method="POST" class="m-0 inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="confirm_delivery">
                                    <input type="hidden" name="delivery_id" value="<?php echo $del['id']; ?>">
                                    <button type="submit" name="received" value="1" class="bg-status-active text-white px-3 py-1 rounded-md shadow-sm hover:opacity-90 font-bold transition-opacity">بله</button>
                                    <button type="submit" name="received" value="0" class="bg-error text-white px-3 py-1 rounded-md shadow-sm hover:opacity-90 font-bold transition-opacity">خیر</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

    </div>

    
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- VIEW 8: Wallet (کیف پول اعتباری دیجیتال و تراکنش‌ها)                    -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="view-wallet" class="customer-view space-y-6 hidden">
        <section id="wallet-section" class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-sm overflow-hidden p-6 md:p-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/60 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-800 font-bold text-xs border border-indigo-500/20">
                    <span class="material-symbols-outlined text-sm text-indigo-600">account_balance_wallet</span>
                    کیف پول دیجیتال اعتباری آسنا
                </span>
                <span class="text-xs text-on-surface-variant font-medium">• پرداخت سریع ۱-کلیکه و تمدید خودکار اشتراک</span>
            </div>
            <h3 class="text-xl md:text-2xl font-black text-primary mt-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-2xl text-indigo-600">wallet</span>
                مدیریت اعتبار و کیف پول دیجیتال
            </h3>
            <p class="text-xs text-on-surface-variant mt-1 max-w-2xl leading-relaxed">
                با شارژ کیف پول خود، می‌توانید بدون نیاز به ورود به درگاه بانکی در هر سفارش، محصولات مورد نیاز پت خود را با ۱ کلیک خریداری کرده و هزینه تمدید دوره‌ای اشتراک‌های درمانی (اتوشیپ) را به صورت خودکار پرداخت کنید.
            </p>
        </div>

        <button type="button" onclick="document.getElementById('chargeWalletModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-5 py-3 rounded-2xl shadow-md shadow-indigo-600/20 flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>افزایش موجودی و شارژ کیف پول</span>
        </button>
    </div>

    <!-- 3 User Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="rounded-2xl p-5 bg-gradient-to-br from-indigo-50 to-white border border-indigo-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-800">موجودی فعلی کیف پول</span>
                <span class="material-symbols-outlined text-indigo-600">payments</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-indigo-900 mt-2 font-mono">
                <?= number_format($userDigitalWallet['balance'] ?? 0) ?> <span class="text-xs font-normal text-slate-500">تومان</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 pt-2 border-t border-indigo-100">آماده برای خرید کالا، خدمات و تمدید خودکار اشتراک</p>
        </div>

        <div class="rounded-2xl p-5 bg-gradient-to-br from-teal-50 to-white border border-teal-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-teal-800">اتصال به تمدید خودکار اشتراک‌ها (اتوشیپ)</span>
                <span class="material-symbols-outlined text-teal-600">autorenew</span>
            </div>
            <div class="text-sm font-bold text-teal-900 mt-2 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-500 animate-pulse"></span>
                <span>پرداخت خودکار فعال</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 pt-2 border-t border-teal-100">در سررسید اشتراک، بسته پت بدون معطلی آماده و ارسال می‌شود</p>
        </div>

        <div class="rounded-2xl p-5 bg-gradient-to-br from-amber-50 to-white border border-amber-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-800">خرید سریع ۱-کلیکه</span>
                <span class="material-symbols-outlined text-amber-600">bolt</span>
            </div>
            <div class="text-sm font-bold text-amber-900 mt-2">
                ثبت سفارش بدون نیاز به رمز پویا
            </div>
            <p class="text-[11px] text-slate-500 mt-3 pt-2 border-t border-amber-100">در سبد خرید، گزینه «پرداخت با کیف پول اعتباری» فعال است</p>
        </div>
    </div>

    <!-- Transactions History -->
    <div class="space-y-3">
        <h4 class="text-sm font-black text-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-500 text-lg">receipt_long</span>
            گردش تراکنش‌های کیف پول شما
        </h4>
        <div class="overflow-x-auto border border-outline-variant/40 rounded-2xl">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">کد رهگیری</th>
                        <th class="p-3.5">نوع تراکنش</th>
                        <th class="p-3.5">شرح تراکنش</th>
                        <th class="p-3.5">مبلغ</th>
                        <th class="p-3.5">تاریخ و ساعت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($userWalletTransactions)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                                هنوز تراکنشی در کیف پول شما ثبت نشده است. با کلیک بر روی «افزایش موجودی»، کیف پول خود را شارژ نمایید.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userWalletTransactions as $tx): 
                            $isDeposit = in_array($tx['type'], ['deposit', 'cashback', 'refund']);
                        ?>
                            <tr class="hover:bg-slate-50/70">
                                <td class="p-3.5 font-mono text-slate-500"><?= htmlspecialchars($tx['reference_id'] ?: ('TX-' . $tx['id'])) ?></td>
                                <td class="p-3.5">
                                    <?php if ($isDeposit): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">واریز / شارژ اعتبار</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">کسر / پرداخت سفارش</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5"><?= htmlspecialchars($tx['description']) ?></td>
                                <td class="p-3.5 font-mono font-bold <?= $isDeposit ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= $isDeposit ? '+' : '-' ?><?= number_format($tx['amount']) ?> تومان
                                </td>
                                <td class="p-3.5 font-mono text-slate-400"><?= htmlspecialchars($tx['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


    </div>

<?php endif; ?>
</div>
</main>

<!-- Modal for Quick Wallet Charge -->
<div id="chargeWalletModal" class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">account_balance_wallet</span>
                شارژ آنلاین کیف پول اعتباری آسنا
            </h3>
            <button onclick="document.getElementById('chargeWalletModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="profile.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="charge_user_wallet">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">مبلغ شارژ (تومان)</label>
                <input type="number" name="amount" id="charge_amount_input" min="10000" step="10000" required value="200000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 font-mono outline-none">
            </div>
            <!-- Quick Preset Pills -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="document.getElementById('charge_amount_input').value=100000" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700">۱۰۰ هزار</button>
                <button type="button" onclick="document.getElementById('charge_amount_input').value=250000" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700">۲۵۰ هزار</button>
                <button type="button" onclick="document.getElementById('charge_amount_input').value=500000" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700">۵۰۰ هزار</button>
                <button type="button" onclick="document.getElementById('charge_amount_input').value=1000000" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700">۱ میلیون</button>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('chargeWalletModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700">انصراف</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md">پرداخت و شارژ آنی</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Edit Personal Info -->
<div id="editPersonalInfoModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 relative animate-fade-in">
        <button type="button" onclick="closeEditPersonalInfoModal()" class="absolute top-5 left-5 text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">manage_accounts</span>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">ویرایش اطلاعات فردی و هویتی</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">ثبت مشخصات دقیق مطابق با کارت ملی و شناسنامه</p>
            </div>
        </div>

        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_personal_info">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">کد ملی ۱۰ رقمی (بدون خط تیره)</label>
                <input type="text" name="national_id" maxlength="10" value="<?= htmlspecialchars($user['national_id'] ?? '') ?>" placeholder="مثال: 0012345678" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr text-left focus:ring-2 focus:ring-primary outline-none">
                <span class="text-[10px] text-slate-400 mt-1 block">جهت صدور فاکتور رسمی ماده ۱۶۹ قانون مالیات</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">پست الکترونیک (ایمیل)</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="example@mail.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr text-left focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeEditPersonalInfoModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-md">
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Change Password -->
<div id="changePasswordModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 relative animate-fade-in">
        <button type="button" onclick="closeChangePasswordModal()" class="absolute top-5 left-5 text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">lock_reset</span>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">تغییر کلمه عبور</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">برای ارتقای امنیت حساب کاربری خود، از کلمه عبور پیچیده استفاده نمایید.</p>
            </div>
        </div>

        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_personal_info">
            <input type="hidden" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            <input type="hidden" name="national_id" value="<?= htmlspecialchars($user['national_id'] ?? '') ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">کلمه عبور فعلی *</label>
                <input type="password" name="current_password" required placeholder="رمز فعلی حساب" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500 outline-none dir-ltr text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">کلمه عبور جدید (حداقل ۶ کاراکتر) *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="رمز عبور جدید" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500 outline-none dir-ltr text-left">
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-md">
                    به‌روزرسانی رمز عبور
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Edit Sheba & Bank Details -->
<div id="editShebaModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 relative animate-fade-in">
        <button type="button" onclick="closeEditShebaModal()" class="absolute top-5 left-5 text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">credit_card</span>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">تنظیم اطلاعات حساب و شماره شبا</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">جهت استرداد وجوه، لغو سفارشات و واریز جوایز باشگاه مشتریان</p>
            </div>
        </div>

        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_bank_details">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>شماره کارت ۱۶ رقمی شتاب</span>
                    <span id="cust_detected_bank" class="text-[10px] text-emerald-600 font-bold"></span>
                </label>
                <input type="text" name="bank_card_number" id="cust_bank_card_input" maxlength="19" value="<?= htmlspecialchars($wallet['bank_card_number'] ?? '') ?>" placeholder="xxxx-xxxx-xxxx-xxxx" oninput="formatCustomerCardInput(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr text-left focus:ring-2 focus:ring-teal-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام بانک صادرکننده</label>
                <input type="text" name="bank_name" id="cust_bank_name_input" value="<?= htmlspecialchars($wallet['bank_name'] ?? '') ?>" placeholder="مثال: بانک سامان، ملی، ملت..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی صاحب حساب</label>
                <input type="text" name="bank_account_holder" value="<?= htmlspecialchars($wallet['bank_account_holder'] ?? $user['name'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-teal-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره شبا (IBAN) بدون فاصله *</label>
                <input type="text" name="bank_sheba" id="cust_bank_sheba_input" required maxlength="26" value="<?= htmlspecialchars($wallet['bank_sheba'] ?? '') ?>" placeholder="IR120120000000001234567890" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr text-left uppercase focus:ring-2 focus:ring-teal-500 outline-none">
                <span class="text-[10px] text-slate-400 mt-1 block">شروع با IR به همراه ۲۴ رقم</span>
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeEditShebaModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-md">
                    ذخیره اطلاعات بانکی
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Delete Account & GDPR Privacy Anonymization -->
<div id="deleteAccountModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 relative animate-fade-in border border-red-100">
        <button type="button" onclick="closeDeleteAccountModal()" class="absolute top-5 left-5 text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-3 border-b border-red-100 pb-4">
            <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <h3 class="text-base font-black text-red-950">تأیید حذف قطعی و پاکسازی حساب کاربری</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">این عملیات غیرقابل بازگشت است و تمام دسترسی‌های شما بلافاصله قطع خواهد شد.</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 space-y-2">
            <div class="font-bold flex items-center gap-1.5 text-amber-950">
                <span class="material-symbols-outlined text-base">info</span>
                فرآیند پاکسازی اطلاعات مطابق آیین‌نامه GDPR و حفظ حریم خصوصی:
            </div>
            <ul class="list-disc list-inside space-y-1 text-[11px] text-amber-900/90 leading-relaxed pr-1">
                <li>حذف کامل پرونده‌های پزشکی، واکسیناسیون و مدارک حیوانات خانگی شما</li>
                <li>ناشناس‌سازی (Anonymization) کامل سوابق تراکنش‌ها و سفارشات مالی</li>
                <li>انقضا و ابطال کلیه نشست‌های فعال و نشست جاری در تمامی دستگاه‌ها</li>
                <li>حذف نام کاربری، شماره موبایل، ایمیل و کدملی از سیستم</li>
            </ul>
        </div>

        <form action="actions/profile_action.php" method="POST" class="space-y-4" onsubmit="return confirmAccountDeletion(this);">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_account">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">کلمه عبور فعلی جهت احراز اصالت هویت *</label>
                <input type="password" name="confirm_password" required placeholder="رمز عبور حساب کاربری" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-red-500 outline-none dir-ltr text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">برای تایید نهایی عبارت <span class="text-red-600 font-black font-mono">DELETE</span> را تایپ کنید *</label>
                <input type="text" name="confirmation_text" id="delete_confirm_text" required placeholder="DELETE" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono tracking-widest text-center focus:ring-2 focus:ring-red-500 outline-none dir-ltr">
            </div>

            <div class="pt-2 flex justify-end gap-2.5 border-t border-slate-100">
                <button type="button" onclick="closeDeleteAccountModal()" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all shadow-md shadow-red-600/20">
                    حذف قطعی و خروج از سامانه
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Product Modal for Sellers -->
<div id="addProductModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 relative animate-fade-in">
        <button onclick="document.getElementById('addProductModal').classList.add('hidden')" class="absolute top-5 left-5 text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">add_shopping_cart</span>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900">ثبت کالای جدید در کاتالوگ فروشگاه</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">محصول شما پس از ثبت، برای سفارش‌دهی خریداران در دسترس خواهد بود.</p>
            </div>
        </div>

        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="seller_add_product">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان کالا *</label>
                <input type="text" name="name" required placeholder="مثال: غذای خشک گربه عقیم‌شده رویال کنین ۲ کیلوگرم" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">دسته‌بندی کالا *</label>
                    <select name="category" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none bg-white">
                        <option value="غذای خشک و تر">غذای خشک و تر</option>
                        <option value="مکمل و ویتامین">مکمل و ویتامین</option>
                        <option value="دارویی و درمانی">دارویی و درمانی</option>
                        <option value="بهداشتی و مراقبت">بهداشتی و مراقبت</option>
                        <option value="لوازم جانبی و اسباب‌بازی">لوازم جانبی و اسباب‌بازی</option>
                        <option value="سایر ملزومات">سایر ملزومات</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">برند / سازنده</label>
                    <input type="text" name="brand" placeholder="مثال: Royal Canin یا مفید" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">قیمت اصلی (تومان) *</label>
                    <input type="number" name="price" required placeholder="مثال: 450000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">قیمت با تخفیف (تومان)</label>
                    <input type="number" name="discount_price" placeholder="اختیاری" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">موجودی انبار *</label>
                    <input type="number" name="stock" value="10" min="1" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-primary outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس تصویر کالا (اختیاری)</label>
                <input type="text" name="image_url" placeholder="assets/images/... یا لینک تصویر" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs dir-ltr focus:ring-2 focus:ring-primary outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">توضیحات و مشخصات کالا</label>
                <textarea name="description" rows="2" placeholder="توضیحات مختصر در رابطه با کالا، نحوه مصرف یا وزن..." class="w-full p-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-all shadow-md">
                    ذخیره و ثبت کالا
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Pet Modal -->
<div id="addPetModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <button onclick="document.getElementById('addPetModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">ثبت شناسنامه حیوان جدید</h2>
        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_pet">
            <div>
                <label class="block text-sm font-bold mb-1">نام حیوان *</label>
                <input type="text" name="pet_name" required placeholder="مثال: لئو" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نوع حیوان *</label>
                <select name="pet_type" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                    <option value="">انتخاب کنید...</option>
                    <option value="سگ">سگ</option>
                    <option value="گربه">گربه</option>
                    <option value="پرنده">پرنده</option>
                    <option value="جونده">جونده</option>
                    <option value="سایر">سایر</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">نژاد</label>
                    <input type="text" name="pet_race" placeholder="مثال: پرشین" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">وزن (کیلوگرم)</label>
                    <input type="number" step="0.1" min="0.1" max="150" name="weight_kg" placeholder="مثال: ۴.۵" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">جنسیت</label>
                    <select name="pet_gender" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                        <option value="">نامشخص</option>
                        <option value="نر">نر</option>
                        <option value="ماده">ماده</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">سن</label>
                    <input type="text" name="pet_age" placeholder="مثال: ۲ سال" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">شماره میکروچیپ (اختیاری)</label>
                <input type="text" name="microchip_number" placeholder="مثال: 900115000123456" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm font-mono text-left dir-ltr">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">حساسیت‌ها و آلرژی‌های دارویی/غذایی</label>
                <input type="text" name="allergies" placeholder="مثال: پنی‌سیلین، گوشت مرغ..." class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold mt-4 hover:bg-primary transition-colors">ثبت مشخصات شناسنامه</button>
        </form>
    </div>
</div>

<!-- Edit Pet Modal -->
<div id="editPetModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <button onclick="document.getElementById('editPetModal').classList.add('hidden')" class="absolute top-4 left-4 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined">close</span></button>
        <h2 class="text-xl font-bold text-primary mb-6">ویرایش شناسنامه حیوان خانگی</h2>
        <form action="actions/profile_action.php" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_pet">
            <input type="hidden" name="pet_id" id="edit_pet_id" value="">
            <div>
                <label class="block text-sm font-bold mb-1">نام حیوان *</label>
                <input type="text" name="pet_name" id="edit_pet_name" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">نوع حیوان *</label>
                <select name="pet_type" id="edit_pet_type" required class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                    <option value="">انتخاب کنید...</option>
                    <option value="سگ">سگ</option>
                    <option value="گربه">گربه</option>
                    <option value="پرنده">پرنده</option>
                    <option value="جونده">جونده</option>
                    <option value="سایر">سایر</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">نژاد</label>
                    <input type="text" name="pet_race" id="edit_pet_race" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">وزن (کیلوگرم)</label>
                    <input type="number" step="0.1" min="0.1" max="150" name="weight_kg" id="edit_pet_weight" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">جنسیت</label>
                    <select name="pet_gender" id="edit_pet_gender" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                        <option value="">نامشخص</option>
                        <option value="نر">نر</option>
                        <option value="ماده">ماده</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">سن</label>
                    <input type="text" name="pet_age" id="edit_pet_age" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">شماره میکروچیپ</label>
                <input type="text" name="microchip_number" id="edit_pet_microchip" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm font-mono text-left dir-ltr">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1">حساسیت‌ها و آلرژی‌های دارویی/غذایی</label>
                <input type="text" name="allergies" id="edit_pet_allergies" class="w-full border border-outline-variant rounded-lg p-2 focus:ring-2 focus:ring-primary-container outline-none text-sm">
            </div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold mt-4 hover:bg-primary transition-colors">ذخیره تغییرات شناسنامه</button>
        </form>
    </div>
</div>

<!-- Add Document Modal -->
<div id="addDocModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl relative border border-slate-100">
        <button onclick="document.getElementById('addDocModal').classList.add('hidden')" class="absolute top-6 left-6 text-slate-400 hover:text-error transition-colors p-1 rounded-full hover:bg-slate-100">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">upload_file</span>
            </div>
            <div>
                <h3 class="text-lg font-black text-primary">ثبت و بایگانی سند پزشکی جدید</h3>
                <p class="text-xs text-slate-500">افزودن برگه آزمایش، گواهی واکسن، شناسنامه یا نسخه به پرونده سلامت پت</p>
            </div>
        </div>

        <form action="actions/profile_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="upload_document">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">حیوان خانگی مربوطه <span class="text-error">*</span></label>
                    <select name="pet_id" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-xs font-bold">
                        <option value="">-- انتخاب پت --</option>
                        <?php foreach($pets as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>" <?= count($pets) === 1 ? 'selected' : '' ?>><?php echo htmlspecialchars($pet['name']); ?> (<?= htmlspecialchars($pet['type'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">دسته‌بندی و نوع سند <span class="text-error">*</span></label>
                    <select name="doc_category" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-xs font-bold">
                        <option value="آزمایشگاه و بیوشیمی">آزمایش خون و بیوشیمی (Lab)</option>
                        <option value="واکسیناسیون و هاری">گواهی واکسیناسیون و هاری</option>
                        <option value="سونوگرافی و رادیولوژی">سونوگرافی و تصویربرداری</option>
                        <option value="نسخه درمانی و دارو">نسخه پزشک خارجی</option>
                        <option value="شناسنامه و میکروچیپ">شناسنامه و میکروچیپ</option>
                        <option value="جراحی و ترخیص">برگه جراحی و ترخیص</option>
                        <option value="سایر مدارک بالینی">سایر مدارک سلامت</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان مدرک (توصیف کوتاه) <span class="text-error">*</span></label>
                <input type="text" name="doc_title" required placeholder="مثال: آزمایش چکاپ سالیانه تدی، واکسن ۹ گانه" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب فایل مدارک (PDF، عکس JPG یا PNG) <span class="text-error">*</span></label>
                <div class="border-2 border-dashed border-slate-200 hover:border-primary rounded-2xl p-4 bg-slate-50 text-center transition-colors">
                    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary-hover file:cursor-pointer cursor-pointer">
                    <p class="text-[10px] text-slate-400 mt-2">حداکثر حجم مجاز: ۱۰ مگابایت • تصاویر با کیفیت بالا جهت خوانایی بالینی</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-xl font-bold text-xs hover:bg-primary-hover transition-colors shadow-md flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">cloud_upload</span>
                    <span>بارگذاری و ثبت نهایی در پرونده</span>
                </button>
                <button type="button" onclick="document.getElementById('addDocModal').classList.add('hidden')" class="px-5 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold text-xs hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Floating Chat Button -->
<a href="chat.php" class="floating-chat-btn fixed bottom-[calc(76px+env(safe-area-inset-bottom,0px))] left-4 sm:left-6 md:bottom-8 md:left-8 w-14 h-14 bg-primary-container text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-[950] group" title="پشتیبانی آنلاین آسنا" aria-label="پشتیبانی آنلاین آسنا">
<span class="material-symbols-outlined text-[28px]">chat_bubble</span>
<span class="absolute left-16 bg-white text-primary-container px-4 py-2 rounded-xl shadow-xl border border-outline-variant font-bold text-sm opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
        پشتیبانی آنلاین آسنا
    </span>
</a>
<!-- Rating & Review Micro-Modal (Non-intrusive) -->
<div id="ratingModal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button onclick="closeRatingModal()" class="absolute top-6 left-6 text-on-surface-variant hover:text-error transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">rate_review</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-primary line-clamp-1" id="ratingModalTitle">ثبت نظر و امتیاز</h3>
                <p class="text-xs text-status-active font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    خریدار تاییدشده • ۵ امتیاز وفاداری هدیه
                </p>
            </div>
        </div>

        <form id="ratingForm" onsubmit="submitRating(event)" class="space-y-4">
            <input type="hidden" id="ratingTargetType" name="target_type" value="product">
            <input type="hidden" id="ratingTargetId" name="target_id" value="0">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <!-- Interactive 5-Star Selector -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">امتیاز شما به این مورد:</label>
                <div class="flex items-center justify-center gap-2 py-2 text-status-warning" id="starSelector">
                    <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" onclick="setRatingStars(<?php echo $i; ?>)" class="star-btn transition-transform hover:scale-125 focus:outline-none cursor-pointer" data-val="<?php echo $i; ?>">
                        <span class="material-symbols-outlined text-3xl select-none" id="star-icon-<?php echo $i; ?>">star</span>
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="ratingScore" name="rating" value="5">
                <p class="text-center text-xs text-on-surface-variant font-bold mt-1" id="ratingScoreText">عالی (۵ ستاره)</p>
            </div>

            <!-- Comment Box -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-2">تجربه یا نظر شما (اختیاری):</label>
                <textarea name="comment" id="ratingComment" rows="3" placeholder="کیفیت، اثربخشی، بسته‌بندی یا نحوه پاسخگویی..." class="w-full text-xs p-3 border border-outline-variant rounded-xl outline-none focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <button type="submit" id="ratingSubmitBtn" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm hover:bg-primary-container transition-all flex items-center justify-center gap-2">
                <span>ثبت امتیاز و دریافت ۵ امتیاز هدیه</span>
            </button>
        </form>
    </div>
</div>

<script>
    function openRatingModal(type, id, title) {
        document.getElementById('ratingTargetType').value = type;
        document.getElementById('ratingTargetId').value = id;
        document.getElementById('ratingModalTitle').textContent = 'امتیاز به ' + title;
        setRatingStars(5);
        document.getElementById('ratingComment').value = '';
        document.getElementById('ratingModal').classList.remove('hidden');
    }

    function closeRatingModal() {
        document.getElementById('ratingModal').classList.add('hidden');
    }

    function setRatingStars(score) {
        document.getElementById('ratingScore').value = score;
        const labels = { 1: 'خیلی ضعیف (۱ ستاره)', 2: 'ضعیف (۲ ستاره)', 3: 'متوسط (۳ ستاره)', 4: 'خوب (۴ ستاره)', 5: 'عالی (۵ ستاره)' };
        document.getElementById('ratingScoreText').textContent = labels[score] || (score + ' ستاره');
        
        for (let i = 1; i <= 5; i++) {
            const icon = document.getElementById('star-icon-' + i);
            if (i <= score) {
                icon.textContent = 'star';
                icon.classList.add('text-status-warning');
                icon.classList.remove('text-outline-variant');
            } else {
                icon.textContent = 'star';
                icon.classList.remove('text-status-warning');
                icon.classList.add('text-outline-variant');
            }
        }
    }

    async function submitRating(e) {
        e.preventDefault();
        const btn = document.getElementById('ratingSubmitBtn');
        const form = document.getElementById('ratingForm');
        const formData = new FormData(form);
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> در حال ثبت...';
        
        try {
            const resp = await fetch('actions/review_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.status === 'success') {
                alert(data.message);
                closeRatingModal();
                location.reload();
            } else {
                alert(data.message || 'خطا در ثبت امتیاز');
            }
        } catch (err) {
            alert('خطا در برقراری ارتباط با سرور.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>ثبت امتیاز و دریافت ۵ امتیاز هدیه</span>';
        }
    }

    function openEditPetModal(id, name, type, race, gender, age, weight, microchip, allergies) {
        document.getElementById('edit_pet_id').value = id;
        document.getElementById('edit_pet_name').value = name;
        document.getElementById('edit_pet_type').value = type;
        document.getElementById('edit_pet_race').value = race || '';
        document.getElementById('edit_pet_gender').value = gender || '';
        document.getElementById('edit_pet_age').value = age || '';
        document.getElementById('edit_pet_weight').value = weight || '';
        document.getElementById('edit_pet_microchip').value = microchip || '';
        document.getElementById('edit_pet_allergies').value = allergies || '';
        document.getElementById('editPetModal').classList.remove('hidden');
    }

    window.addEventListener('load', () => {
        document.querySelectorAll('.glass-card, .rounded-2xl').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.6s cubic-bezier(0.22, 1, 0.36, 1)';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    function toggleProfileSidebar() {
        const sidebar = document.getElementById('profile-sidebar');
        const backdrop = document.getElementById('profile-backdrop');
        
        if (sidebar.classList.contains('translate-x-full')) {
            sidebar.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            if (window.innerWidth < 1024) {
                document.body.style.overflow = 'hidden';
            }
        } else {
            sidebar.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        }
    }

    function toggleDesktopSidebar() {
        const sidebar = document.getElementById('profile-sidebar');
        const main = document.getElementById('profile-main');
        const icon = document.getElementById('sidebar-desktop-toggle-icon');
        if (!sidebar || !main) return;

        const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        main.classList.toggle('sidebar-collapsed', isCollapsed);
        document.body.classList.toggle('sidebar-collapsed-shell', isCollapsed);

        if (icon) {
            // In RTL, chevron_left points away from edge (expand); chevron_right points toward edge (collapse)
            icon.innerText = isCollapsed ? 'chevron_left' : 'chevron_right';
        }

        try {
            localStorage.setItem('asena_sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {
            console.warn('Failed to store sidebar state:', e);
        }
    }

    // Sidebar Wheel Event Chaining: Seamlessly propagate scroll to main page when sidebar is at bounds
    document.addEventListener('DOMContentLoaded', () => {
        const pSidebar = document.getElementById('profile-sidebar');
        if (pSidebar) {
            pSidebar.addEventListener('wheel', (e) => {
                const nav = pSidebar.querySelector('nav');
                if (!nav) return;
                const isNavScrollable = nav.scrollHeight > nav.clientHeight;
                if (!isNavScrollable) {
                    window.scrollBy({ top: e.deltaY, behavior: 'auto' });
                } else {
                    const atTop = nav.scrollTop <= 0 && e.deltaY < 0;
                    const atBottom = nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 1 && e.deltaY > 0;
                    if (atTop || atBottom) {
                        window.scrollBy({ top: e.deltaY, behavior: 'auto' });
                    }
                }
            }, { passive: true });
        }
    });

    // ─── Marketplace Escrow Wallet Interactions ──────────────────────────────────
    function toggleWalletDetails() {
        const panel = document.getElementById('wallet-details-panel');
        const arrow = document.getElementById('wallet-toggle-arrow');
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
            panel.classList.add('hidden');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }
    }

    function switchWalletTab(tab) {
        // Hide all tab contents
        ['income', 'outcome', 'bank'].forEach(t => {
            const content = document.getElementById('tab-content-' + t);
            const btn = document.getElementById('tab-btn-' + t);
            if (content) content.classList.add('hidden');
            if (btn) {
                btn.classList.remove('border-b-2', 'border-primary', 'bg-white', 'text-primary');
                btn.classList.add('text-on-surface-variant');
            }
        });

        // Show active tab
        const activeContent = document.getElementById('tab-content-' + tab);
        const activeBtn = document.getElementById('tab-btn-' + tab);
        if (activeContent) activeContent.classList.remove('hidden');
        if (activeBtn) {
            activeBtn.classList.add('border-b-2', 'border-primary', 'bg-white', 'text-primary');
            activeBtn.classList.remove('text-on-surface-variant');
        }
    }

    function openBankTab() {
        const panel = document.getElementById('wallet-details-panel');
        if (panel.classList.contains('hidden')) {
            toggleWalletDetails();
        }
        switchWalletTab('bank');
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }


    // ─── Digikala-Grade Customer View Switcher ─────────────────────────────────────
    const validCustomerTabs = ['overview', 'personal-info', 'addresses', 'pets', 'appointments', 'orders', 'prescriptions', 'subscriptions', 'wallet'];

    function switchCustomerView(tabName) {
        if (!validCustomerTabs.includes(tabName)) {
            tabName = 'overview';
        }

        // 1. Hide all customer views
        document.querySelectorAll('.customer-view').forEach(el => {
            el.classList.add('hidden');
        });

        // 2. Show targeted view
        const targetView = document.getElementById('view-' + tabName);
        if (targetView) {
            targetView.classList.remove('hidden');
        }

        // 3. Update desktop sidebar styles
        validCustomerTabs.forEach(t => {
            const btn = document.getElementById('sidebar-btn-' + t);
            if (btn) {
                if (t === tabName) {
                    btn.className = 'flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold transition-all shadow-md cursor-pointer';
                } else {
                    btn.className = 'flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-low rounded-xl transition-all cursor-pointer font-bold';
                }
            }
        });

        // 4. Update mobile carousel button styles
        validCustomerTabs.forEach(t => {
            const mBtn = document.getElementById('mob-tab-btn-' + t);
            if (mBtn) {
                if (t === tabName) {
                    mBtn.className = 'px-3.5 py-2 rounded-xl bg-primary text-white shadow-sm shrink-0 flex items-center gap-1.5 cursor-pointer';
                } else {
                    mBtn.className = 'px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-primary shrink-0 flex items-center gap-1.5 cursor-pointer';
                }
            }
        });

        // 5. Update URL hash without scrolling
        if (history.replaceState) {
            history.replaceState(null, null, '#' + tabName);
        } else {
            location.hash = '#' + tabName;
        }

        // 6. If addresses tab, initialize or invalidate Leaflet map size
        if (tabName === 'addresses') {
            setTimeout(() => {
                if (!customerMap) {
                    initCustomerAddressMap();
                } else {
                    customerMap.invalidateSize();
                }
            }, 80);
        }

        // Scroll top on mobile if needed
        if (window.innerWidth < 1024) {
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }
    }

    // ─── Leaflet Map & Smart Address Geocoding for Customer ─────────────────────
    let customerMap = null;
    let customerMarker = null;
    let geocodeAbortCtrl = null;

    function initCustomerAddressMap() {
        const mapContainer = document.getElementById('customer-address-map');
        if (!mapContainer || customerMap) return;

        let initialLat = parseFloat(document.getElementById('address_latitude').value) || 38.0700;
        let initialLng = parseFloat(document.getElementById('address_longitude').value) || 46.2931;

        customerMap = L.map('customer-address-map', {
            zoomControl: true,
            attributionControl: false
        }).setView([initialLat, initialLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(customerMap);

        // Interactive Marker
        customerMarker = L.marker([initialLat, initialLng], {
            draggable: true
        }).addTo(customerMap);

        // When user finishes dragging the marker -> Auto Fill Address
        customerMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateLatLngInputs(pos.lat, pos.lng);
            reverseGeocodeAndFillAddress(pos.lat, pos.lng);
        });

        // When user clicks anywhere on the map -> Move Marker & Auto Fill Address
        customerMap.on('click', function(e) {
            customerMarker.setLatLng(e.latlng);
            updateLatLngInputs(e.latlng.lat, e.latlng.lng);
            reverseGeocodeAndFillAddress(e.latlng.lat, e.latlng.lng);
        });

        // If address text is currently empty, auto-resolve default coordinates
        const currentAddr = (document.getElementById('address_text')?.value || '').trim();
        if (!currentAddr) {
            reverseGeocodeAndFillAddress(initialLat, initialLng, false);
        } else {
            const pillText = document.getElementById('map-pill-text');
            if (pillText) {
                pillText.textContent = currentAddr.length > 50 ? currentAddr.substring(0, 50) + '...' : currentAddr;
            }
        }
    }

    function updateLatLngInputs(lat, lng) {
        const latInput = document.getElementById('address_latitude');
        const lngInput = document.getElementById('address_longitude');
        if (latInput) latInput.value = Number(lat).toFixed(6);
        if (lngInput) lngInput.value = Number(lng).toFixed(6);
    }

    /**
     * Automatically queries the reverse geocoding API and fills the address, city, and postal code
     */
    async function reverseGeocodeAndFillAddress(lat, lng, highlight = true) {
        const pillText = document.getElementById('map-pill-text');
        const pillIcon = document.getElementById('map-pill-icon');
        const addressTextarea = document.getElementById('address_text');
        const cityInput = document.getElementById('address_city');
        const postalInput = document.getElementById('address_postal_code');
        const alertBanner = document.getElementById('address-autofill-alert');

        if (pillText) {
            pillText.innerHTML = '<span class="inline-flex items-center gap-1.5 text-secondary-container"><span class="material-symbols-outlined text-sm animate-spin">sync</span> در حال استخراج آدرس از نقشه...</span>';
        }

        if (geocodeAbortCtrl) {
            geocodeAbortCtrl.abort();
        }
        geocodeAbortCtrl = new AbortController();

        try {
            let data = null;

            // 1. Primary: Server-side Persian geocoding endpoint
            try {
                const response = await fetch(`actions/reverse_geocode.php?lat=${lat}&lng=${lng}`, {
                    signal: geocodeAbortCtrl.signal
                });
                if (response.ok) {
                    const resJson = await response.json();
                    if (resJson && resJson.status === 'success' && resJson.data) {
                        data = resJson.data;
                    }
                }
            } catch (srvErr) {
                if (srvErr.name === 'AbortError') return;
                console.warn('[Geocode] Server endpoint fallback:', srvErr);
            }

            // 2. Secondary fallback: Direct OpenStreetMap Nominatim with Persian language
            if (!data) {
                const directUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=fa`;
                const directResp = await fetch(directUrl, { signal: geocodeAbortCtrl.signal });
                if (directResp.ok) {
                    const dJson = await directResp.json();
                    if (dJson && dJson.address) {
                        const a = dJson.address;
                        const c = a.city || a.town || a.village || a.county || 'تبریز';
                        const road = a.road || a.pedestrian || a.residential || '';
                        const hood = a.neighbourhood || a.suburb || a.quarter || '';
                        const formatted = [hood, road ? (road.startsWith('خیابان') ? road : 'خیابان ' + road) : '']
                            .filter(Boolean).join('، ') || dJson.display_name;
                        data = {
                            city: c.replace(/^(شهرستان|شهر|بخش)\s+/g, '').trim(),
                            formatted_address: formatted,
                            postal_code: (a.postcode || '').replace(/[^0-9]/g, '').slice(0, 10)
                        };
                    }
                }
            }

            if (data && data.formatted_address) {
                // Auto fill the Address Textarea!
                if (addressTextarea) {
                    addressTextarea.value = data.formatted_address;
                    if (highlight) {
                        addressTextarea.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50/30', 'transition-all');
                        setTimeout(() => {
                            addressTextarea.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50/30');
                        }, 1800);
                    }
                }

                // Auto fill the City input
                if (cityInput && data.city) {
                    cityInput.value = data.city;
                }

                // Auto fill the Postal Code if currently empty
                if (postalInput && data.postal_code && !postalInput.value.trim()) {
                    postalInput.value = data.postal_code;
                }

                // Update Map Floating Pill
                if (pillText) {
                    pillText.textContent = data.formatted_address;
                }
                if (pillIcon) {
                    pillIcon.textContent = 'check_circle';
                    pillIcon.classList.remove('text-secondary-container');
                    pillIcon.classList.add('text-emerald-600');
                    setTimeout(() => {
                        pillIcon.textContent = 'location_on';
                        pillIcon.classList.remove('text-emerald-600');
                        pillIcon.classList.add('text-secondary-container');
                    }, 2000);
                }

                // Bind Marker Popup
                if (customerMarker) {
                    customerMarker.bindPopup(`
                        <div style="font-family: 'Vazirmatn', sans-serif; text-align: right; direction: rtl; padding: 4px; font-size: 12px;">
                            <b style="color: #001a48;">موقعیت انتخابی شما:</b>
                            <p style="margin: 4px 0 0 0; color: #334155; font-weight: bold;">${data.formatted_address}</p>
                            <span style="font-size: 10px; color: #16a34a; display: block; margin-top: 4px;">✔ نشانی در فرم ثبت شد</span>
                        </div>
                    `).openPopup();
                }

                // Show success notification banner
                if (alertBanner) {
                    alertBanner.classList.remove('hidden');
                }
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[Geocoding] Error:', err);
            if (pillText) {
                pillText.textContent = 'نقطه انتخاب شد (لطفاً جزئیات نشانی را در کادر زیر تکمیل فرمایید)';
            }
        }
    }

    /**
     * Trigger re-geocoding from current marker position
     */
    function triggerCurrentLocationGeocode() {
        if (!customerMarker) return;
        const pos = customerMarker.getLatLng();
        reverseGeocodeAndFillAddress(pos.lat, pos.lng, true);
    }

    /**
     * Quick Jump to specific city/neighborhood and auto-fill address
     */
    function jumpMapTo(lat, lng, zoom = 14, name = '') {
        if (!customerMap || !customerMarker) {
            initCustomerAddressMap();
        }
        if (customerMap && customerMarker) {
            customerMap.flyTo([lat, lng], zoom, { duration: 1.0 });
            customerMarker.setLatLng([lat, lng]);
            updateLatLngInputs(lat, lng);
            reverseGeocodeAndFillAddress(lat, lng, true);
        }
    }

    /**
     * Locate user with GPS, center map, move marker, and auto-fill address
     */
    function locateUserPosition() {
        if (!navigator.geolocation) {
            alert('مرورگر شما از قابلیت موقعیت‌یابی GPS پشتیبانی نمی‌کند.');
            return;
        }

        const pillText = document.getElementById('map-pill-text');
        if (pillText) {
            pillText.innerHTML = '<span class="inline-flex items-center gap-1.5 text-blue-600"><span class="material-symbols-outlined text-sm animate-spin">sync</span> در حال دریافت مختصات GPS شما...</span>';
        }

        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            if (!customerMap) initCustomerAddressMap();
            if (customerMap && customerMarker) {
                customerMap.flyTo([lat, lng], 16, { duration: 1.2 });
                customerMarker.setLatLng([lat, lng]);
                updateLatLngInputs(lat, lng);
                reverseGeocodeAndFillAddress(lat, lng, true);
            }
        }, err => {
            alert('دسترسی به موقعیت مکانی انجام نشد یا توسط مرورگر مسدود گردید.');
            if (pillText) {
                pillText.textContent = 'نشانگر را جابجا کنید تا نشانی استخراج شود';
            }
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    // ─── Modal Helpers ────────────────────────────────────────────────────────────
    function openEditPersonalInfoModal() {
        document.getElementById('editPersonalInfoModal').classList.remove('hidden');
    }
    function closeEditPersonalInfoModal() {
        document.getElementById('editPersonalInfoModal').classList.add('hidden');
    }

    function openChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.remove('hidden');
    }
    function closeChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.add('hidden');
    }

    function openEditShebaModal() {
        document.getElementById('editShebaModal').classList.remove('hidden');
    }
    function closeEditShebaModal() {
        document.getElementById('editShebaModal').classList.add('hidden');
    }

    function openDeleteAccountModal() {
        document.getElementById('deleteAccountModal').classList.remove('hidden');
    }
    function closeDeleteAccountModal() {
        document.getElementById('deleteAccountModal').classList.add('hidden');
    }
    function confirmAccountDeletion(form) {
        const text = document.getElementById('delete_confirm_text').value.trim();
        if (text.toUpperCase() !== 'DELETE') {
            alert('لطفاً جهت تأیید حذف حساب، عبارت DELETE را وارد فرمایید.');
            return false;
        }
        return confirm('آیا از حذف قطعی و دائمی حساب کاربری خود و پاکسازی اطلاعات اطمینان کامل دارید؟ این عملیات غیرقابل بازگشت است.');
    }

    function formatCustomerCardInput(input) {
        let val = input.value.replace(/\D/g, '');
        let formatted = '';
        for (let i = 0; i < val.length && i < 16; i++) {
            if (i > 0 && i % 4 === 0) formatted += '-';
            formatted += val[i];
        }
        input.value = formatted;

        if (val.length >= 6) {
            let bin = val.substring(0, 6);
            if (typeof bankBins !== 'undefined' && bankBins[bin]) {
                const bName = bankBins[bin];
                document.getElementById('cust_detected_bank').innerText = '✔ ' + bName;
                const bInput = document.getElementById('cust_bank_name_input');
                if (bInput && !bInput.value) bInput.value = bName;
            }
        }
    }

    // Auto-detect view from URL Param (tab) or URL Hash on page load and hashchange
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        const rawHash = window.location.hash.replace('#', '').trim();
        const activeTab = urlTab || rawHash;
        if (activeTab && validCustomerTabs.includes(activeTab)) {
            switchCustomerView(activeTab);
        }
    });

    window.addEventListener('hashchange', () => {
        const rawHash = window.location.hash.replace('#', '').trim();
        if (rawHash && validCustomerTabs.includes(rawHash)) {
            switchCustomerView(rawHash);
        }
    });

</script>
<?php require_once 'includes/footer.php'; ?>
