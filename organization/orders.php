<?php
require_once 'includes/organization_header.php';

$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Order Fulfillment Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'dispatch_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $trackingCode = trim($_POST['post_tracking_code'] ?? '');
        $carrier = trim($_POST['carrier_name'] ?? 'شرکت ملی پست / پستکس');

        // Multi-tenant authorization check
        $authorized = ($currentUser['role'] === 'admin');
        if (!$authorized && $orderId > 0) {
            $chk = $pdo->prepare("
                SELECT 1 FROM order_items oi
                WHERE oi.order_id = ? AND (oi.seller_id = ? OR oi.seller_id = ?)
                LIMIT 1
            ");
            $chk->execute([$orderId, (int)($currentOrg['user_id'] ?? 0), (int)$currentUser['id']]);
            $authorized = (bool)$chk->fetchColumn();
        }

        if (!$authorized) {
            $message = 'شما مجوز مدیریت یا ارسال این سفارش را ندارید.';
            $messageType = 'error';
        } elseif ($orderId > 0) {
            // BPMS Gateway Check: Verify if an associated prescription exists and is unlocked
            $rxChk = $pdo->prepare("SELECT id, shipping_unlocked, bpms_state FROM prescriptions WHERE order_id = ?");
            $rxChk->execute([$orderId]);
            $linkedRx = $rxChk->fetch(PDO::FETCH_ASSOC);

            if ($linkedRx && (int)$linkedRx['shipping_unlocked'] !== 1) {
                $message = '⚠️ ارسال این مرسوله به دلیل قفل امنیتی BPMS مسدود است: نسخه دارویی این سفارش هنوز توسط داروساز مسئول فنی تأیید (Accept Receipt) نشده است.';
                $messageType = 'error';
            } elseif (!empty($trackingCode)) {
                require_once __DIR__ . '/../includes/OrderLifecycleService.php';
                $lifecycle = new OrderLifecycleService($pdo);
                $transRes = $lifecycle->transition($orderId, 'shipped', 'organization', (int)$currentUser['id'], $carrier, $trackingCode, 'ارسال مرسوله توسط مرکز درمانی');

                if ($transRes['success']) {
                    $pdo->prepare("UPDATE seller_escrow_ledger SET status = 'in_inspection' WHERE order_id = ? AND status = 'pending_delivery'")->execute([$orderId]);
                    $message = "سفارش #PC-{$orderId} با کد رهگیری {$trackingCode} به عنوان ارسال شده ثبت گردید و پیامک رهگیری به خریدار ارسال شد.";
                    $messageType = 'success';
                } else {
                    $message = $transRes['message'] ?? 'خطا در ثبت اطلاعات ارسال.';
                    $messageType = 'error';
                }
            } else {
                $message = 'لطفاً کد رهگیری پستی مرسوله را وارد نمایید.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'dispatch_bpms_prescription') {
        $rxId = (int)($_POST['rx_id'] ?? 0);
        $trackingCode = trim($_POST['post_tracking_code'] ?? '');
        $carrier = trim($_POST['carrier_name'] ?? 'شرکت ملی پست / پستکس');

        $bpms = App::bpms();
        $rx = $bpms->getPrescription($rxId);

        $isOrgRx = ($rx && ((int)$rx['organization_id'] === $orgId || $currentUser['role'] === 'admin'));

        if (!$isOrgRx) {
            $message = 'شما مجوز مدیریت ارسال این نسخه دارویی را ندارید.';
            $messageType = 'error';
        } elseif (!$bpms->isShippingUnlocked($rxId)) {
            $message = '⚠️ ارسال مرسوله مسدود است: نسخه هنوز به تأیید داروساز نرسیده است (قفل امنیتی BPMS فعال است).';
            $messageType = 'error';
        } elseif (empty($trackingCode)) {
            $message = 'لطفاً کد رهگیری پستی مرسوله را وارد نمایید.';
            $messageType = 'error';
        } else {
            $shipped = $bpms->orgMarkShipped($rxId, (int)$currentUser['id'], $trackingCode, $carrier);
            if ($shipped) {
                $message = "✅ مرسوله دارویی نسخه #{$rxId} با کد رهگیری {$trackingCode} با موفقیت به عنوان ارسال‌شده ثبت گردید.";
                $messageType = 'success';
            } else {
                $message = 'خطا در ثبت وضعیت ارسال نسخه.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_order_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowed = ['processing', 'shipped', 'delivered', 'cancelled'];

        // Multi-tenant authorization check
        $authorized = ($currentUser['role'] === 'admin');
        if (!$authorized && $orderId > 0) {
            $chk = $pdo->prepare("
                SELECT 1 FROM order_items oi
                WHERE oi.order_id = ? AND (oi.seller_id = ? OR oi.seller_id = ?)
                LIMIT 1
            ");
            $chk->execute([$orderId, (int)($currentOrg['user_id'] ?? 0), (int)$currentUser['id']]);
            $authorized = (bool)$chk->fetchColumn();
        }

        if (!$authorized) {
            $message = 'شما مجوز ویرایش وضعیت این سفارش را ندارید.';
            $messageType = 'error';
        } elseif ($orderId > 0 && in_array($newStatus, $allowed)) {
            require_once __DIR__ . '/../includes/OrderLifecycleService.php';
            $lifecycle = new OrderLifecycleService($pdo);
            $transRes = $lifecycle->transition($orderId, $newStatus, 'organization', (int)$currentUser['id'], null, null, 'تغییر وضعیت توسط مرکز');
            if ($transRes['success']) {
                $message = "وضعیت سفارش #PC-{$orderId} به‌روزرسانی شد.";
                $messageType = 'success';
            }
        }
    }
}

// Current Filter
$filter = $_GET['filter'] ?? 'all';
$whereClauses = ["1=1"];
$params = [];

if ($filter === 'pending') {
    $whereClauses[] = "o.status IN ('pending_payment', 'processing')";
} elseif ($filter === 'shipped') {
    $whereClauses[] = "o.status = 'shipped'";
} elseif ($filter === 'delivered') {
    $whereClauses[] = "o.status = 'delivered'";
}

// Strict Organization Multi-Tenant Restriction: Only orders containing items belonging to this organization
$orgUserId = (int)($currentOrg['user_id'] ?? 0);
$currUserId = (int)($currentUser['id'] ?? 0);

$whereClauses[] = "(EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND (oi.seller_id = :org_user OR oi.seller_id = :curr_user)))";
$params[':org_user'] = $orgUserId;
$params[':curr_user'] = $currUserId;

$whereSql = implode(' AND ', $whereClauses);

// Fetch orders with comprehensive customer address, postal code and coordinates
$ordersQuery = "
    SELECT o.*, 
           u.name as customer_name, 
           u.phone as customer_phone, 
           u.city as customer_city,
           u.postal_code as customer_postal_code,
           u.address as customer_address,
           u.latitude as customer_lat,
           u.longitude as customer_lng
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE {$whereSql}
    ORDER BY o.id DESC
    LIMIT 50
";
$stmt = $pdo->prepare($ordersQuery);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach items strictly belonging to this organization
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $itemParams = array_merge($orderIds, [$orgUserId, $currUserId]);
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.image_url, p.category 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($ph) AND (oi.seller_id = ? OR oi.seller_id = ?)
    ");
    $itemStmt->execute($itemParams);
    $itemsByOrder = [];
    foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
        $itemsByOrder[$it['order_id']][] = $it;
    }
    foreach ($orders as &$ord) {
        $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
    }
    unset($ord);

    // Attach prescription lock status to each order if exists
    $orderPrescriptions = [];
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $opStmt = $pdo->prepare("SELECT id, order_id, bpms_state, shipping_unlocked, pharmacist_decision FROM prescriptions WHERE order_id IN ($ph)");
    $opStmt->execute($orderIds);
    foreach ($opStmt->fetchAll(PDO::FETCH_ASSOC) as $op) {
        $orderPrescriptions[$op['order_id']] = $op;
    }
} else {
    $orderPrescriptions = [];
}

// Fetch BPMS Clinical Prescriptions assigned to this Organization
$bpmsParams = [];
$bpmsWhere = "1=1";
if ($currentUser['role'] !== 'admin') {
    $bpmsWhere = "p.organization_id = ?";
    $bpmsParams[] = $orgId;
}

$bpmsStmt = $pdo->prepare("
    SELECT p.*,
           u.name as customer_name, u.phone as customer_phone,
           COALESCE(NULLIF(d.name, ''), NULLIF(p.vet_name, ''), '') as doctor_name,
           d.specialty as doctor_specialty,
           COALESCE(NULLIF(p.vet_license_number, ''), NULLIF(d.license_number, ''), '') as doctor_license,
           pet.pet_name, pet.species, pet.breed
    FROM prescriptions p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN doctors d ON p.doctor_id = d.id
    LEFT JOIN pet_health_records pet ON p.pet_id = pet.id
    WHERE {$bpmsWhere}
    ORDER BY FIELD(p.bpms_state, 'pharmacist_approved', 'broadcasted', 'pharmacist_review', 'pharmacist_rejected', 'org_shipped', 'completed'), p.created_at DESC
    LIMIT 60
");
$bpmsStmt->execute($bpmsParams);
$bpmsPrescriptions = $bpmsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate BPMS metrics
$bpmsLockedCount = 0;
$bpmsReadyCount  = 0;
$bpmsShippedCount = 0;
foreach ($bpmsPrescriptions as $bRx) {
    if ((int)$bRx['shipping_unlocked'] === 1 && $bRx['bpms_state'] !== 'org_shipped' && $bRx['bpms_state'] !== 'completed') {
        $bpmsReadyCount++;
    } elseif ((int)$bRx['shipping_unlocked'] === 0 && !in_array($bRx['bpms_state'], ['org_shipped', 'completed'])) {
        $bpmsLockedCount++;
    } elseif ($bRx['bpms_state'] === 'org_shipped') {
        $bpmsShippedCount++;
    }
}

$mainTab = $_GET['tab'] ?? 'orders';

// Quick stats strictly scoped to this organization
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(CASE WHEN o.status IN ('pending_payment', 'processing') THEN 1 ELSE 0 END) as pending_dispatch,
        SUM(CASE WHEN o.status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        COALESCE(SUM(oi.seller_net_amount), 0) as gross_revenue
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id AND (oi.seller_id = :org_user OR oi.seller_id = :curr_user)
");
$statsStmt->execute([
    ':org_user' => $orgUserId,
    ':curr_user' => $currUserId
]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'pending_dispatch' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'gross_revenue' => 0];

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-sky-600 text-3xl">local_shipping</span>
                <span>سفارشات فروشگاهی، دارویی و ارسال کالا</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت مرسولات مشتریان، صدور فاکتور رسمی ماده ۱۶۹ و ثبت کدهای رهگیری پستکس</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="syncPostexNow()" id="syncPostexBtn" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-base">sync</span>
                <span id="syncPostexText">استعلام زنده پستکس</span>
            </button>
            <a href="inventory.php" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-lg">inventory_2</span>
                <span>مدیریت انبار دارو و کالا</span>
            </a>
        </div>
    </div>

    <!-- 3-Step Simple Shipping Guide for Clinic & Petshop Staff -->
    <div class="bg-gradient-to-r from-sky-900 via-indigo-950 to-slate-900 text-white p-5 rounded-3xl shadow-md border border-white/10 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-400 text-2xl">help</span>
                <h3 class="font-black text-sm text-white">راهنمای ساده ۳ مرحله‌ای ارسال و بسته‌بندی سفارشات</h3>
            </div>
            <span class="text-[11px] bg-white/10 px-2.5 py-1 rounded-full text-slate-300 font-bold">ویژه پرسنل کلینیک و پت‌شاپ</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-1">
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۱</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">کپی نشانی یا چاپ برچسب</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">کد پستی و آدرس را کپی کنید یا با ۱ کلیک دکمه «چاپ برچسب مرسوله» را بزنید.</span>
                </div>
            </div>
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۲</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">تحویل به پست / تیپاکس</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">بسته را به مامور پست تحویل داده و قبض یا بارکد رهگیری را تحویل بگیرید.</span>
                </div>
            </div>
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۳</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">ثبت بارکد و ارسال خودکار</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">بارکد را ثبت کنید. پیامک به خریدار و رهگیری هوشمند پستکس خودکار فعال می‌شود!</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">pending</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">در انتظار ارسال</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['pending_dispatch'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">تحویل به ناوگان پست</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['shipped_orders'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">تحویل قطعی شده</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['delivered_orders'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">مجموع درآمد فروش</span>
                <span class="text-lg font-black text-slate-900"><?= number_format((float)$stats['gross_revenue']) ?> <span class="text-[10px] font-bold text-slate-400">تومان</span></span>
            </div>
        </div>
    </div>

    <!-- Main Tabs: Regular Orders vs BPMS Prescriptions -->
    <div class="flex items-center gap-3 border-b border-slate-200 pb-2">
        <a href="orders.php?tab=orders" class="px-5 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 <?= $mainTab !== 'bpms' ? 'bg-sky-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            <span class="material-symbols-outlined text-base">shopping_cart</span>
            <span>سفارشات فروشگاهی و کالا (<?= (int)$stats['total_orders'] ?>)</span>
        </a>
        <a href="orders.php?tab=bpms" class="px-5 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 <?= $mainTab === 'bpms' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            <span class="material-symbols-outlined text-base">medication</span>
            <span>کارتابل مرسولات دارویی BPMS (<?= count($bpmsPrescriptions) ?>)</span>
            <?php if ($bpmsReadyCount > 0): ?>
                <span class="px-2 py-0.5 rounded-full bg-emerald-400 text-white text-[10px] font-bold animate-pulse"><?= $bpmsReadyCount ?> آماده ارسال</span>
            <?php endif; ?>
            <?php if ($bpmsLockedCount > 0): ?>
                <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-900 text-[10px] font-bold">🔒 <?= $bpmsLockedCount ?> قفل</span>
            <?php endif; ?>
        </a>
    </div>

    <?php if ($mainTab === 'bpms'): ?>
    <!-- ═════════════════════════════════════════════════════════
         BPMS CLINICAL PRESCRIPTIONS DISPATCH BOARD
    ═════════════════════════════════════════════════════════ -->
    <div class="space-y-4">
        <div class="p-4 bg-gradient-to-r from-indigo-50 via-blue-50 to-purple-50 rounded-2xl border border-indigo-100 flex items-start gap-3 text-xs text-indigo-950">
            <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined">account_tree</span>
            </div>
            <div>
                <h4 class="font-black text-sm text-indigo-900 mb-0.5">دروازه مدیریت فرایند ارسال اقلام دارویی (BPMS Shipping Gateway)</h4>
                <p class="text-indigo-800 leading-relaxed">
                    نسخه‌های تجویزی پزشکان به صورت خودکار به داروساز ارسال می‌گردند. طبق پروتکل یکپارچه بالینی، <strong>تنها در صورتی که داروساز مسئول فنی نسخه را تأیید نماید (Accept Receipt)، قفل ارسال مرسوله برای کلینیک باز خواهد شد</strong> و کلینیک مجاز به بسته‌بندی و الصاق بارکد پستی خواهد بود.
                </p>
            </div>
        </div>

        <?php if (empty($bpmsPrescriptions)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 text-center py-16 text-slate-400 space-y-3">
                <span class="material-symbols-outlined text-5xl text-slate-300">medication</span>
                <p class="text-sm font-bold text-slate-600">هیچ نسخه دارویی ارجاعی در کارتابل کلینیک ثبت نشده است.</p>
                <p class="text-xs text-slate-400">به محض اینکه پزشک در پنل خود نسخه‌ای را با انتخاب این مرکز ثبت نماید، در این بخش قابل مدیریت خواهد بود.</p>
            </div>
        <?php else: ?>
            <?php foreach ($bpmsPrescriptions as $rx):
                $items = json_decode($rx['items_json'] ?? '[]', true) ?: [];
                $stateLabel = BpmsService::getStateLabelFa($rx['bpms_state'] ?? 'broadcasted');
                $stateBadge = BpmsService::getStateBadgeClass($rx['bpms_state'] ?? 'broadcasted');
                $isUnlocked = ((int)$rx['shipping_unlocked'] === 1);
                $isShipped  = ($rx['bpms_state'] === 'org_shipped');
            ?>
            <div class="bg-white rounded-2xl border <?= $isUnlocked && !$isShipped ? 'border-emerald-300 ring-2 ring-emerald-50' : 'border-slate-200' ?> p-5 sm:p-6 shadow-sm hover:border-slate-300 transition-all space-y-4">
                <!-- Top Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <span class="font-black text-slate-900 font-mono text-sm">#نسخه-<?= $rx['id'] ?></span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-500 flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                            <?= substr($rx['created_at'] ?? '', 0, 10) ?>
                        </span>
                        <span class="text-slate-300">•</span>
                        <span class="font-bold text-slate-700">سرپرست: <?= htmlspecialchars($rx['customer_name'] ?? 'نامشخص') ?></span>
                        <?php if (!empty($rx['customer_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($rx['customer_phone']) ?>" class="text-sky-600 font-mono dir-ltr hover:underline">
                                <?= htmlspecialchars($rx['customer_phone']) ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($rx['pet_name'])): ?>
                            <span class="text-slate-300">•</span>
                            <span class="text-indigo-700 font-bold">🐾 پت: <?= htmlspecialchars($rx['pet_name']) ?> (<?= htmlspecialchars($rx['species'] ?? '') ?>)</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $stateBadge ?>">
                            <?= $stateLabel ?>
                        </span>
                    </div>
                </div>

                <!-- Doctor & Diagnosis -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-100">
                        <p class="text-[10px] font-bold text-blue-700 mb-1">🩺 پزشک معالج صادرکننده</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($rx['doctor_name'] ?? 'نامشخص') ?> - <?= htmlspecialchars($rx['doctor_specialty'] ?? '') ?></p>
                        <?php if (!empty($rx['doctor_license'])): ?><p class="text-[10px] text-slate-500 mt-0.5">شماره نظام: <?= htmlspecialchars($rx['doctor_license']) ?></p><?php endif; ?>
                    </div>
                    <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-100">
                        <p class="text-[10px] font-bold text-amber-700 mb-1">🔬 تشخیص بالینی پزشک</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($rx['diagnosis'] ?? '—') ?></p>
                    </div>
                </div>

                <!-- Doctor Clinical Examination Report -->
                <?php if (!empty($rx['doctor_examination_report'])): ?>
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1">
                    <p class="text-[10px] font-bold text-slate-500 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">clinical_notes</span>
                        <span>گزارش معاینه بالینی پزشک</span>
                    </p>
                    <p class="leading-relaxed"><?= nl2br(htmlspecialchars($rx['doctor_examination_report'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Drug Items -->
                <?php if (!empty($items)): ?>
                <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100">
                    <p class="text-[10px] font-bold text-indigo-700 mb-2">💊 اقلام دارویی نسخه</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <?php foreach ($items as $it): ?>
                        <div class="p-2 bg-white rounded-lg border border-indigo-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($it['name'] ?? '') ?></span>
                                <?php if (!empty($it['dose'])): ?><span class="text-indigo-600 font-mono text-[11px]">(<?= htmlspecialchars($it['dose']) ?>)</span><?php endif; ?>
                            </div>
                            <span class="text-slate-500 text-[11px]">× <?= (int)($it['qty'] ?? 1) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Shipping Lock / Dispatch Section -->
                <?php if (!$isUnlocked): ?>
                    <!-- Locked banner -->
                    <div class="p-4 bg-amber-50 border border-amber-300 rounded-xl text-xs space-y-2">
                        <div class="flex items-center gap-2 font-black text-amber-900">
                            <span class="material-symbols-outlined text-amber-600">lock</span>
                            <span>🔒 قفل فرایندی فعال است — ارسال مرسوله تا پیش از تأیید داروساز غیرمجاز است</span>
                        </div>
                        <p class="text-amber-800">
                            این نسخه در کارتابل مسئول فنی داروخانه در حال بررسی است. به محض تأیید نسخه توسط داروساز (Accept Receipt)، قفل ارسال باز شده و امکان ثبت کد رهگیری پستی فعال خواهد شد.
                        </p>
                        <?php if ($rx['pharmacist_decision'] === 'rejected'): ?>
                            <p class="text-rose-700 font-bold mt-1">❌ نسخه توسط داروساز رد شده است: <?= htmlspecialchars($rx['pharmacist_notes'] ?? 'نیاز به اصلاح دوز') ?></p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isUnlocked && !$isShipped): ?>
                    <!-- Unlocked & Ready for Dispatch -->
                    <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-xs space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2 font-black text-emerald-900">
                                <span class="material-symbols-outlined text-emerald-600">verified</span>
                                <span>✅ نسخه توسط داروساز تأیید شد — آماده بسته‌بندی و ارسال</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-200 text-emerald-900 text-[10px] font-bold">قفل ارسال باز شد</span>
                        </div>

                        <?php if (!empty($rx['pharmacist_notes'])): ?>
                            <p class="text-emerald-800 text-[11px]">یادداشت داروساز: <?= htmlspecialchars($rx['pharmacist_notes']) ?></p>
                        <?php endif; ?>

                        <!-- Dispatch Form -->
                        <form method="POST" class="flex flex-wrap items-center gap-3 pt-2 border-t border-emerald-200">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="dispatch_bpms_prescription">
                            <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">

                            <div class="flex items-center gap-2">
                                <label class="font-bold text-slate-700">کد رهگیری پستی / پستکس:</label>
                                <input type="text" name="post_tracking_code" placeholder="مثال: 184590203001..." required class="px-3 py-2 rounded-xl border border-emerald-300 text-xs font-mono dir-ltr focus:ring-2 focus:ring-emerald-500 outline-none w-52 bg-white">
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="font-bold text-slate-700">حمل‌کننده:</label>
                                <input type="text" name="carrier_name" value="شرکت ملی پست / پستکس" class="px-3 py-2 rounded-xl border border-slate-200 text-xs outline-none w-40 bg-white">
                            </div>

                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition-all">
                                <span class="material-symbols-outlined text-base">local_shipping</span>
                                <span>ثبت ارسال مرسوله دارویی</span>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Shipped -->
                    <div class="p-4 bg-sky-50 border border-sky-200 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 font-black text-sky-900">
                                <span class="material-symbols-outlined text-sky-600">task_alt</span>
                                <span>📦 مرسوله دارویی به ناوگان پست تحویل داده شد</span>
                            </div>
                            <p class="text-sky-800">
                                کد رهگیری پستی: <span class="font-mono font-bold"><?= htmlspecialchars($rx['shipping_tracking_code'] ?? '—') ?></span>
                                <?php if (!empty($rx['shipped_at'])): ?> | تاریخ ارسال: <span dir="ltr"><?= substr($rx['shipped_at'], 0, 16) ?></span><?php endif; ?>
                            </p>
                        </div>
                        <?php if (!empty($rx['shipping_tracking_code'])): ?>
                        <a href="https://tracking.post.ir/?id=<?= urlencode($rx['shipping_tracking_code']) ?>" target="_blank" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold flex items-center gap-1 shrink-0 transition-all text-xs">
                            <span class="material-symbols-outlined text-sm">track_changes</span>
                            <span>استعلام وضعیت از سامانه پست</span>
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ═════════════════════════════════════════════════════════
         REGULAR STORE ORDERS FEED
    ═════════════════════════════════════════════════════════ -->
    <!-- Filters -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
        <a href="orders.php?tab=orders&filter=all" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'all' ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            همه سفارشات (<?= (int)$stats['total_orders'] ?>)
        </a>
        <a href="orders.php?tab=orders&filter=pending" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            در انتظار آماده‌سازی (<?= (int)$stats['pending_dispatch'] ?>)
        </a>
        <a href="orders.php?tab=orders&filter=shipped" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'shipped' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            ارسال شده با رهگیری (<?= (int)$stats['shipped_orders'] ?>)
        </a>
        <a href="orders.php?tab=orders&filter=delivered" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'delivered' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            تحویل موفق به خریدار (<?= (int)$stats['delivered_orders'] ?>)
        </a>
    </div>

    <!-- Orders Feed -->
    <div class="space-y-4">
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 text-center py-16 text-slate-400 space-y-3">
                <span class="material-symbols-outlined text-5xl">shopping_cart_checkout</span>
                <p class="text-sm font-bold">هیچ سفارشی در این بخش وجود ندارد.</p>
                <p class="text-xs text-slate-400">به محض ثبت سفارش مشتریان در پت‌شاپ یا داروخانه، اطلاعات در این بخش قابل مدیریت خواهد بود.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
                <?php
                $linkedRx = $orderPrescriptions[$ord['id']] ?? null;
                $isRxLocked = ($linkedRx && (int)$linkedRx['shipping_unlocked'] !== 1);

                $statusMeta = match($ord['status']) {
                    'pending_payment' => ['title' => 'در انتظار پرداخت', 'bg' => 'bg-amber-50 text-amber-800 border-amber-200'],
                    'processing'      => ['title' => 'در حال بسته‌بندی در انبار', 'bg' => 'bg-blue-50 text-blue-800 border-blue-200'],
                    'shipped'         => ['title' => 'ارسال شده با پستکس / پست', 'bg' => 'bg-indigo-50 text-indigo-800 border-indigo-200'],
                    'delivered'       => ['title' => 'تحویل داده شده', 'bg' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                    'cancelled'       => ['title' => 'لغو شده', 'bg' => 'bg-rose-50 text-rose-800 border-rose-200'],
                    default           => ['title' => $ord['status'], 'bg' => 'bg-slate-100 text-slate-800 border-slate-200'],
                };
                ?>
                <div class="bg-white rounded-2xl border <?= $isRxLocked ? 'border-amber-300' : 'border-slate-200' ?> p-5 sm:p-6 shadow-sm hover:border-slate-300 transition-all space-y-4">
                    <!-- Top Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span class="font-black text-slate-900 font-mono text-sm">#PC-<?= $ord['id'] ?></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-500 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <?= $fmtDate->format(new DateTime($ord['created_at'])) ?>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="font-bold text-slate-700">خریدار: <?= htmlspecialchars($ord['customer_name'] ?? 'مشتری پلتفرم') ?></span>
                            <?php if (!empty($ord['customer_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($ord['customer_phone']) ?>" class="text-sky-600 font-mono dir-ltr hover:underline">
                                    <?= htmlspecialchars($ord['customer_phone']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusMeta['bg'] ?>">
                                <?= $statusMeta['title'] ?>
                            </span>
                            <a href="../actions/generate_invoice.php?order_id=<?= $ord['id'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1 transition-all" title="چاپ فاکتور رسمی">
                                <span class="material-symbols-outlined text-sm">receipt_long</span>
                                <span>فاکتور رسمی</span>
                            </a>
                        </div>
                    </div>

<!-- BPMS Linked Prescription Alert -->
                    <?php if ($linkedRx): ?>
                        <?php if ($isRxLocked): ?>
                            <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs font-bold text-amber-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-amber-600">lock</span>
                                    <span>🔒 قفل امنیتی BPMS فعال است: این سفارش شامل نسخه دارویی #<?= $linkedRx['id'] ?> است و تا پیش از تأیید داروساز، ارسال آن غیرمجاز است.</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-amber-200 text-amber-900 text-[10px] font-bold">در انتظار بررسی داروساز</span>
                            </div>
                        <?php else: ?>
                            <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-bold text-emerald-900 flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">verified</span>
                                <span>✅ نسخه دارویی این سفارش (#<?= $linkedRx['id'] ?>) به تأیید داروساز رسیده است — قفل ارسال باز می‌باشد.</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Shipping Address, Postal Code & Map Location -->
                    <?php
                    $fullAddr = htmlspecialchars($ord['shipping_address'] ?: ($ord['customer_address'] ?: 'نشانی ثبت نشده'));
                    $postalCode = htmlspecialchars($ord['customer_postal_code'] ?: '');
                    $hasCoords = (!empty($ord['customer_lat']) && !empty($ord['customer_lng']));
                    ?>
                    <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-start gap-2 text-xs text-slate-700">
                                <span class="material-symbols-outlined text-sky-600 text-lg flex-shrink-0">location_on</span>
                                <div>
                                    <span class="font-black text-slate-900 block mb-0.5">نشانی پستی تحویل گیرنده:</span>
                                    <span class="leading-relaxed"><?= $fullAddr ?></span>
                                    <?php if (!empty($ord['customer_city'])): ?>
                                        <span class="text-slate-400 mr-1">(شهر: <?= htmlspecialchars($ord['customer_city']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                                <a href="../actions/print_shipping_label.php?order_id=<?= $ord['id'] ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
                                    <span class="material-symbols-outlined text-sm">print</span>
                                    <span>چاپ برچسب کارتن</span>
                                </a>
                            </div>
                        </div>

                        <!-- Postal Code and Map Coordinates Bar -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-200/60 text-xs">
                            <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-900 px-3 py-1 rounded-xl">
                                <span class="material-symbols-outlined text-amber-700 text-base">markunread_mailbox</span>
                                <span class="font-bold">کد پستی ۱۰ رقمی:</span>
                                <span class="font-mono font-black tracking-wider text-sm"><?= $postalCode ?: 'ثبت نشده' ?></span>
                                <?php if ($postalCode): ?>
                                    <button type="button" onclick="copyText('<?= $postalCode ?>', this)" class="mr-1 px-2 py-0.5 rounded bg-amber-200/70 hover:bg-amber-300 text-amber-900 text-[10px] font-bold transition-all" title="کپی کد پستی">
                                        کپی
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasCoords): ?>
                                <a href="https://nshn.ir/?lat=<?= $ord['customer_lat'] ?>&lng=<?= $ord['customer_lng'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 font-bold text-[11px] flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-sm">near_me</span>
                                    <span>مسیریابی در نشان</span>
                                </a>
                                <a href="https://maps.google.com/?q=<?= $ord['customer_lat'] ?>,<?= $ord['customer_lng'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold text-[11px] flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-sm">map</span>
                                    <span>گوگل‌مپ</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <?php if (!empty($ord['items'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <?php foreach ($ord['items'] as $item): ?>
                                <div class="p-3 bg-slate-50/70 border border-slate-100 rounded-xl flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white border border-slate-200 overflow-hidden flex-shrink-0">
                                        <img src="../<?= htmlspecialchars($item['image_url'] ?? 'assets/images/toy-mouse.jpg') ?>" class="w-full h-full object-cover" onerror="this.src='../assets/images/toy-mouse.jpg'">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="text-xs font-bold text-slate-800 truncate" title="<?= htmlspecialchars($item['product_name_snapshot']) ?>">
                                            <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                        </h5>
                                        <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                            <?= (int)$item['quantity'] ?> عدد × <?= number_format((float)$item['price_at_purchase']) ?> تومان
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Dispatch & Tracking Action Box -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <?php if (in_array($ord['status'], ['pending_payment', 'processing'])): ?>
                            <!-- Action: Transition from Processing to Shipped -->
                            <form method="POST" class="flex flex-wrap items-center gap-2 m-0 bg-blue-50/50 p-3 rounded-2xl border border-blue-100">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="dispatch_order">
                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">

                                <span class="text-xs font-black text-slate-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sky-600 text-sm">local_shipping</span>
                                    <span>تحویل بسته:</span>
                                </span>

                                <select name="carrier_name" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold bg-white focus:ring-2 focus:ring-sky-500 outline-none" <?= $isRxLocked ? 'disabled' : '' ?>>
                                    <option value="شرکت ملی پست (پیشتاز)">شرکت ملی پست (پیشتاز)</option>
                                    <option value="پستکس (Postex)">پستکس (Postex)</option>
                                    <option value="تیپاکس (Tipax)">تیپاکس (Tipax)</option>
                                    <option value="چاپار (Chapar)">چاپار (Chapar)</option>
                                    <option value="پیک اختصاصی کلینیک">پیک اختصاصی کلینیک</option>
                                </select>

                                <input type="text" name="post_tracking_code" placeholder="بارکد پستی ۲۴ رقمی..." required class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-sky-500 outline-none w-44 bg-white" <?= $isRxLocked ? 'disabled' : '' ?>>

                                <?php if ($isRxLocked): ?>
                                    <button type="button" disabled class="px-3.5 py-1.5 rounded-lg bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed flex items-center gap-1" title="نسخه هنوز به تأیید داروساز نرسیده است">
                                        <span class="material-symbols-outlined text-sm">lock</span>
                                        <span>ارسال غیرمجاز (قفل BPMS)</span>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                        <span>تغییر به ارسال شد</span>
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <!-- Active Shipped Tracking State -->
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-slate-500 font-bold">رهگیری پستی:</span>
                                <?php if (!empty($ord['post_tracking_code'])): ?>
                                    <a href="https://postex.ir/tracking?tracking_code=<?= urlencode($ord['post_tracking_code']) ?>" target="_blank" class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-bold text-xs border border-indigo-200 flex items-center gap-1 transition-all" title="رهگیری مستقیم مرسوله">
                                        <span class="material-symbols-outlined text-sm">search</span>
                                        <span><?= htmlspecialchars($ord['post_tracking_code']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400">کد پستی ثبت نشده</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500">مبلغ کل سفارش:</span>
                            <span class="font-black text-base text-slate-900 font-mono">
                                <?= number_format((float)$ord['total_amount']) ?> <span class="text-xs font-bold text-slate-500">تومان</span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Client-side Interactive Functions -->
<script>
function copyText(text, btn) {
    if (!navigator.clipboard) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(text);
    }
    const orig = btn.innerText;
    btn.innerText = 'کپی شد!';
    btn.classList.add('bg-emerald-200', 'text-emerald-900');
    setTimeout(() => {
        btn.innerText = orig;
        btn.classList.remove('bg-emerald-200', 'text-emerald-900');
    }, 2000);
}

function syncPostexNow() {
    const btn = document.getElementById('syncPostexBtn');
    const txt = document.getElementById('syncPostexText');
    const origText = txt.innerText;

    btn.disabled = true;
    txt.innerText = 'در حال استعلام از پستکس...';

    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

    const fd = new FormData();
    fd.append('csrf_token', csrfToken);

    fetch('../actions/sync_shipping_action.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'استعلام با موفقیت انجام شد.');
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(err => {
        alert('خطا در برقراری ارتباط با وب‌سرویس استعلام پستکس.');
    })
    .finally(() => {
        btn.disabled = false;
        txt.innerText = origText;
    });
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
