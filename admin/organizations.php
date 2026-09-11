<?php
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$currentPage = 'organizations';

// Handle Organization Actions (Status Toggle & Banking Details Update)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $targetOrgId = (int)($_POST['org_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'approved');
        if ($targetOrgId > 0 && in_array($newStatus, ['approved', 'suspended', 'pending'])) {
            $upd = $pdo->prepare("UPDATE organizations SET status = ? WHERE id = ?");
            $upd->execute([$newStatus, $targetOrgId]);
        }
    } elseif ($_POST['action'] === 'update_banking') {
        $targetOrgId = (int)($_POST['org_id'] ?? 0);
        $bankName = trim($_POST['bank_name'] ?? '');
        $bankSheba = strtoupper(trim($_POST['bank_sheba'] ?? ''));
        $accountHolder = trim($_POST['bank_account_holder'] ?? '');
        $cardNumber = trim($_POST['bank_card_number'] ?? '');
        
        if ($targetOrgId > 0) {
            $upd = $pdo->prepare("
                UPDATE organizations 
                SET bank_name = ?, bank_sheba = ?, bank_account_holder = ?, bank_card_number = ?
                WHERE id = ?
            ");
            $upd->execute([$bankName, $bankSheba, $accountHolder, $cardNumber, $targetOrgId]);

            // Sync to seller_wallets if user exists
            $uStmt = $pdo->prepare("SELECT user_id FROM organizations WHERE id = ?");
            $uStmt->execute([$targetOrgId]);
            $uId = $uStmt->fetchColumn();
            if ($uId) {
                $swStmt = $pdo->prepare("SELECT id FROM seller_wallets WHERE seller_id = ?");
                $swStmt->execute([$uId]);
                if ($swStmt->fetchColumn()) {
                    $pdo->prepare("
                        UPDATE seller_wallets 
                        SET bank_name = ?, bank_sheba = ?, bank_account_holder = ?, bank_card_number = ?, updated_at = NOW()
                        WHERE seller_id = ?
                    ")->execute([$bankName, $bankSheba, $accountHolder, $cardNumber, $uId]);
                }
            }
        }
    } elseif ($_POST['action'] === 'single_payout') {
        $targetOrgId = (int)($_POST['org_id'] ?? 0);
        $rawAmount = trim($_POST['custom_amount'] ?? '');
        $customAmount = null;
        if ($rawAmount !== '') {
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            if ($cleaned !== '') {
                $customAmount = (int)$cleaned;
            }
        }
        
        $uStmt = $pdo->prepare("SELECT user_id, name FROM organizations WHERE id = ?");
        $uStmt->execute([$targetOrgId]);
        $orgRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        $uId = (int)($orgRow['user_id'] ?? 0);
        
        if ($uId > 0) {
            $escrowService = App::escrow();
            $payoutRes = $escrowService->generateSingleSellerPayout($uId, (int)$_SESSION['user_id'], $customAmount);
            if ($payoutRes['success']) {
                $_SESSION['org_flash'] = [
                    'type' => 'success',
                    'message' => "حواله تسویه پایا برای «{$orgRow['name']}» با موفقیت صادر شد! شناسه پایا: {$payoutRes['batch_code']} | مبلغ: " . number_format($payoutRes['total_amount']) . " تومان.",
                    'batch_code' => $payoutRes['batch_code'],
                    'batch_id' => $payoutRes['batch_id']
                ];
            } else {
                $_SESSION['org_flash'] = [
                    'type' => 'error',
                    'message' => $payoutRes['message']
                ];
            }
        } else {
            $_SESSION['org_flash'] = [
                'type' => 'error',
                'message' => 'این مرکز درمانی هنوز به حساب کاربری متصل نشده است.'
            ];
        }
    }
    header("Location: organizations.php");
    exit;
}

$orgFlash = $_SESSION['org_flash'] ?? null;
unset($_SESSION['org_flash']);

// ── Search & Filter ───────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$cityFilter = trim($_GET['city'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');

$query = "
    SELECT 
        o.*,
        u.name as owner_name,
        u.phone as owner_phone,
        COALESCE(NULLIF(o.bank_name, ''), NULLIF(sw.bank_name, ''), 'بانک سامان') as bank_name,
        COALESCE(NULLIF(o.bank_sheba, ''), NULLIF(sw.bank_sheba, ''), NULLIF(u.sheba_number, ''), '') as bank_sheba,
        COALESCE(NULLIF(o.bank_account_holder, ''), NULLIF(sw.bank_account_holder, ''), o.manager_name, o.name) as bank_account_holder,
        COALESCE(NULLIF(o.bank_card_number, ''), NULLIF(sw.bank_card_number, ''), '') as bank_card_number,
        COALESCE(sw.balance_available_for_payout, 0) as balance_available_for_payout,
        COALESCE(sw.balance_pending_escrow, 0) as balance_pending_escrow,
        COALESCE(sw.balance_settled_lifetime, 0) as balance_settled_lifetime,
        (SELECT COUNT(*) FROM organization_doctors od WHERE od.organization_id = o.id AND od.role_type = 'doctor') as doctors_count,
        (SELECT COUNT(*) FROM organization_doctors od WHERE od.organization_id = o.id AND od.role_type = 'pharmacist') as pharmacists_count,
        (SELECT COUNT(*) FROM organization_doctors od WHERE od.organization_id = o.id AND od.role_type = 'groomer') as groomers_count,
        (SELECT COUNT(*) FROM organization_inventory oi WHERE oi.organization_id = o.id) as inventory_count,
        (SELECT COUNT(*) FROM appointments a WHERE a.organization_id = o.id) as appointments_count,
        (SELECT COALESCE(SUM(a.fee), 0) FROM appointments a WHERE a.organization_id = o.id) as total_appointment_volume
    FROM organizations o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN seller_wallets sw ON o.user_id = sw.seller_id
    WHERE 1=1
";

$params = [];
if (!empty($search)) {
    $query .= " AND (o.name LIKE ? OR o.manager_name LIKE ? OR o.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($cityFilter)) {
    $query .= " AND o.city = ?";
    $params[] = $cityFilter;
}
if (!empty($typeFilter)) {
    $query .= " AND o.type = ?";
    $params[] = $typeFilter;
}

$query .= " ORDER BY o.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Macro Stats
$totalOrgs = count($organizations);
$totalOrgDocs = array_sum(array_column($organizations, 'doctors_count'));
$totalOrgPharm = array_sum(array_column($organizations, 'pharmacists_count'));
$totalLifetimeSettled = array_sum(array_column($organizations, 'balance_settled_lifetime'));

// Fetch all organization details for modals
$orgDetails = [];
foreach ($organizations as $org) {
    $oId = (int)$org['id'];
    
    // Doctors in this org
    $dStmt = $pdo->prepare("
        SELECT d.id, d.name, d.specialty, od.role_type,
               (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) as visits_count
        FROM organization_doctors od
        JOIN doctors d ON od.doctor_id = d.id
        WHERE od.organization_id = ?
    ");
    $dStmt->execute([$oId]);
    $docs = $dStmt->fetchAll(PDO::FETCH_ASSOC);

    // Products / Inventory with clean joins to get titles, categories, and prices
    $iStmt = $pdo->prepare("
        SELECT 
            oi.id,
            oi.organization_id,
            oi.item_type,
            oi.item_id,
            oi.stock as quantity,
            oi.custom_price,
            oi.is_in_stock,
            CASE 
                WHEN oi.item_type = 'medicine' THEN COALESCE(pm.name, CONCAT('داروی تخصصی کد #', oi.item_id))
                ELSE COALESCE(p.name, CONCAT('محصول پت‌شاپ کد #', oi.item_id))
            END as item_name,
            CASE 
                WHEN oi.item_type = 'medicine' THEN COALESCE(pm.category, 'داروخانه دامپزشکی')
                ELSE COALESCE(p.category, 'پت‌شاپ و ملزومات')
            END as category,
            CASE 
                WHEN oi.item_type = 'medicine' THEN pm.generic_name
                ELSE p.brand
            END as sub_title,
            CASE 
                WHEN oi.item_type = 'medicine' THEN COALESCE(oi.custom_price, pm.price, 0)
                ELSE COALESCE(oi.custom_price, p.price, 0)
            END as effective_price
        FROM organization_inventory oi
        LEFT JOIN pharmacy_medicines pm ON (oi.item_type = 'medicine' AND oi.item_id = pm.id)
        LEFT JOIN products p ON (oi.item_type = 'product' AND oi.item_id = p.id)
        WHERE oi.organization_id = ?
        ORDER BY oi.id ASC
        LIMIT 50
    ");
    $iStmt->execute([$oId]);
    $inv = $iStmt->fetchAll(PDO::FETCH_ASSOC);

    $orgDetails[$oId] = [
        'doctors' => $docs,
        'inventory' => $inv,
    ];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="p-4 lg:p-8 space-y-8">

    <?php if ($orgFlash): ?>
        <div class="p-4 rounded-2xl text-xs font-bold flex flex-col sm:flex-row sm:items-center justify-between gap-3 <?= $orgFlash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-lg"><?= $orgFlash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
                <span><?= htmlspecialchars($orgFlash['message']) ?></span>
            </div>
            <?php if (!empty($orgFlash['batch_code']) && !empty($orgFlash['batch_id'])): ?>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="payouts.php?download_batch=<?= (int)$orgFlash['batch_id'] ?>" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs inline-flex items-center gap-1.5 transition shadow-2xs">
                        <span class="material-symbols-outlined text-xs">download</span>
                        <span>دانلود فایل پایا (.txt)</span>
                    </a>
                    <a href="../actions/generate_payout_receipt.php?batch_code=<?= urlencode($orgFlash['batch_code']) ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold text-xs inline-flex items-center gap-1.5 transition shadow-2xs">
                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                        <span>مشاهده و چاپ رسید رسمی پایا</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-on-surface flex items-center gap-2.5">
                <span class="material-symbols-outlined text-secondary-container text-2xl">apartment</span>
                <span>شبکه مراکز درمانی و بیمارستان‌ها (سازمان‌ها)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">نظارت عالیه بر کلینیک‌ها، کادر درمانی (پزشکان و داروسازان)، کاتالوگ دارویی و تسویه‌های پایا بانک مرکزی</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <span>سطح دسترسی: مدیر ارشد اکوسیستم (Super Admin)</span>
            </span>
        </div>
    </div>

    <!-- 4 Top KPI Cards Matching Reference Screenshot -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">کل مراکز و کلینیک‌ها</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalOrgs) ?></span>
                    <span class="text-xs text-secondary-container font-bold">مرکز</span>
                </div>
                <p class="text-[11px] text-slate-400">بیمارستان، درمانگاه و پت‌کلینیک</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_hospital</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">پزشکان عضو مراکز</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalOrgDocs) ?></span>
                    <span class="text-xs text-blue-600 font-bold">پزشک فعال</span>
                </div>
                <p class="text-[11px] text-slate-400">دارای شیفت و نوبت‌دهی رسمی</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">داروسازان مراکز درمانی</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-600"><?= number_format($totalOrgPharm) ?></span>
                    <span class="text-xs text-emerald-600 font-bold">داروساز</span>
                </div>
                <p class="text-[11px] text-slate-400">تایید نسخه و تحویل دارو</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">prescriptions</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">تسویه تجمیعی پایا به مراکز</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-800"><?= number_format($totalLifetimeSettled) ?></span>
                    <span class="text-xs text-on-surface-variant font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-slate-400">با کسر ۵٪ کارمزد پلتفرم</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">account_balance</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10 flex flex-col md:flex-row gap-3 items-center justify-between">
        <form method="GET" class="flex flex-1 flex-wrap gap-2 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-lg">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="جستجوی نام مرکز، مدیر یا شماره تماس..." class="w-full pr-10 pl-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none">
            </div>

            <select name="type" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none">
                <option value="">همه انواع مراکز</option>
                <option value="clinic" <?= $typeFilter === 'clinic' ? 'selected' : '' ?>>کلینیک تخصصی</option>
                <option value="hospital" <?= $typeFilter === 'hospital' ? 'selected' : '' ?>>بیمارستان</option>
                <option value="petshop" <?= $typeFilter === 'petshop' ? 'selected' : '' ?>>مرکز جامع</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-slate-800 transition-all flex items-center gap-1">
                <span>اعمال فیلتر</span>
            </button>

            <?php if (!empty($search) || !empty($typeFilter)): ?>
            <a href="organizations.php" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-all">
                حذف فیلترها
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Organizations Table -->
    <div class="bg-surface-container-lowest rounded-2xl stat-card-shadow border border-outline-variant/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">شناسه</th>
                        <th class="p-3.5">نام مرکز درمانی</th>
                        <th class="p-3.5">مدیر مسئول / تماس</th>
                        <th class="p-3.5">شهر / آدرس</th>
                        <th class="p-3.5 text-center">پزشکان</th>
                        <th class="p-3.5 text-center">داروسازان</th>
                        <th class="p-3.5 text-center">اقلام انبار</th>
                        <th class="p-3.5">آماده تسویه پایا (تومان)</th>
                        <th class="p-3.5">وضعیت مرکز</th>
                        <th class="p-3.5 text-center">عملیات نظارتی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($organizations as $org): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-3.5 font-bold text-slate-400">#<?= $org['id'] ?></td>
                        <td class="p-3.5">
                            <div class="font-black text-slate-900 text-sm"><?= htmlspecialchars($org['name']) ?></div>
                            <div class="text-[11px] text-slate-400"><?= $org['type'] === 'hospital' ? 'بیمارستان تخصصی' : ($org['type'] === 'clinic' ? 'کلینیک و مرکز درمانی' : 'مرکز خدمات جامع') ?></div>
                        </td>
                        <td class="p-3.5">
                            <div class="font-bold text-slate-800"><?= htmlspecialchars($org['manager_name'] ?: ($org['owner_name'] ?: '-')) ?></div>
                            <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($org['phone'] ?: ($org['owner_phone'] ?: '-')) ?></div>
                        </td>
                        <td class="p-3.5">
                            <div class="font-bold text-slate-700"><?= htmlspecialchars($org['city'] ?: 'تهران') ?></div>
                            <div class="text-[11px] text-slate-400 truncate max-w-[160px]"><?= htmlspecialchars($org['address'] ?: 'ثبت نشده') ?></div>
                        </td>
                        <td class="p-3.5 text-center font-black text-indigo-600">
                            <?= (int)$org['doctors_count'] ?>
                        </td>
                        <td class="p-3.5 text-center font-black text-emerald-600">
                            <?= (int)$org['pharmacists_count'] ?>
                        </td>
                        <td class="p-3.5 text-center font-bold text-slate-700">
                            <?= (int)$org['inventory_count'] ?>
                        </td>
                        <td class="p-3.5 font-black text-emerald-700">
                            <?= number_format((int)($org['balance_available_for_payout'] ?? 0)) ?>
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-1.5">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-bold border <?= ($org['status'] ?? 'approved') === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                                    <?= ($org['status'] ?? 'approved') === 'approved' ? 'فعال / تایید شده' : 'معلق / بررسی' ?>
                                </span>
                                <form method="POST" class="inline" onsubmit="return confirm('آیا از تغییر وضعیت این مرکز اطمینان دارید؟');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="org_id" value="<?= $org['id'] ?>">
                                    <input type="hidden" name="status" value="<?= ($org['status'] ?? 'approved') === 'approved' ? 'suspended' : 'approved' ?>">
                                    <button type="submit" class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-secondary-container transition-colors" title="تغییر وضعیت">
                                        <span class="material-symbols-outlined text-sm"><?= ($org['status'] ?? 'approved') === 'approved' ? 'pause_circle' : 'play_circle' ?></span>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="p-3.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="tickets.php?new_ticket=organization&target_id=<?= (int)$org['id'] ?>" class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 font-bold text-xs transition-all flex items-center gap-1" title="ارسال پیام / تیکت به مرکز">
                                    <span class="material-symbols-outlined text-sm">mail</span>
                                    <span>پیام</span>
                                </a>
                                <button onclick="viewOrgDetails(<?= $org['id'] ?>)" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-secondary-container hover:text-white text-slate-700 font-bold text-xs transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    <span>بررسی</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── DETAIL MODAL: CLEAN TABBED ECOSYSTEM VIEW ─────────────────────────────── -->
<div id="orgDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-3xl w-full shadow-2xl space-y-4 text-right max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 id="modalOrgName" class="font-black text-slate-900 text-base">نام مرکز درمانی</h3>
                <p class="text-xs text-slate-400">شناسنامه کامل، پزشکان، داروسازان، اقلام دارویی و حساب پایا</p>
            </div>
            <button onclick="closeOrgModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Inner Tabs -->
        <div class="flex border-b border-slate-200 gap-2">
            <button onclick="switchOrgModalTab('docs')" id="btnTabDocs" class="px-4 py-2 text-xs font-black border-b-2 border-secondary-container text-secondary-container">پزشکان همکار (<span id="modalDocCount">۰</span>)</button>
            <button onclick="switchOrgModalTab('inv')" id="btnTabInv" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700">انبار داروخانه (<span id="modalInvCount">۰</span>)</button>
            <button onclick="switchOrgModalTab('finance')" id="btnTabFinance" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700">تسویه پایا و امور بانکی</button>
        </div>

        <!-- Tab 1: Doctors -->
        <div id="modalTabDocs" class="space-y-3">
            <div id="modalDocsList" class="space-y-2 text-xs">
                <!-- Injected via JS -->
            </div>
        </div>

        <!-- Tab 2: Inventory -->
        <div id="modalTabInv" class="hidden space-y-3">
            <div id="modalInvList" class="space-y-2 text-xs">
                <!-- Injected via JS -->
            </div>
        </div>

        <!-- Tab 3: Finance & Paya Banking -->
        <div id="modalTabFinance" class="hidden space-y-4 text-xs">
            <!-- Bank & Sheba Summary Card -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                    <span class="text-slate-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-primary">account_balance</span>
                        نام بانک عامل:
                    </span>
                    <span id="modalBankName" class="font-black text-slate-900 text-xs sm:text-sm bg-white px-3 py-1 rounded-xl border border-slate-200 shadow-2xs">-</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                    <span class="text-slate-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-slate-400">badge</span>
                        صاحب حساب:
                    </span>
                    <span id="modalBankAccountHolder" class="font-bold text-slate-900">-</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 pb-2 border-b border-slate-200/60">
                    <span class="text-slate-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-slate-400">credit_card</span>
                        شماره شبا رسمی پایا:
                    </span>
                    <div class="flex items-center gap-2">
                        <span id="modalBankSheba" class="font-mono font-black text-slate-900 text-xs sm:text-sm tracking-wider" dir="ltr">-</span>
                        <button type="button" onclick="copyModalSheba()" class="p-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 transition-colors" title="کپی شماره شبا">
                            <span class="material-symbols-outlined text-sm">content_copy</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                    <span class="text-slate-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-emerald-500">payments</span>
                        موجودی آماده تسویه پایا:
                    </span>
                    <span id="modalAvailablePayout" class="font-black text-emerald-600 text-sm">- تومان</span>
                </div>

                <!-- Instant Single Payout Row if balance > 0 -->
                <div id="modalPayoutActionRow" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-200/80 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                    <div class="text-xs">
                        <span class="font-black text-emerald-800 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                            موجودی آزاد شده و آماده صدور حواله پایا است
                        </span>
                        <div class="text-[11px] text-emerald-700 mt-0.5">تسویه این مرکز به صورت انفرادی یا تجمیعی در میز کار پایا امکان‌پذیر است.</div>
                    </div>
                    <form method="POST" action="" class="inline shrink-0" onsubmit="return confirm('آیا از صدور حواله تسویه پایا برای این مرکز درمانی اطمینان دارید؟');">
                        <input type="hidden" name="action" value="single_payout">
                        <input type="hidden" id="payoutOrgId" name="org_id" value="">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs inline-flex items-center gap-1.5 shadow-sm transition-all">
                            <span class="material-symbols-outlined text-sm">payments</span>
                            <span>⚡ تسویه آنی پایا برای این مرکز</span>
                        </button>
                    </form>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-amber-500">percent</span>
                        کارمزد زیرساخت آسنا:
                    </span>
                    <span class="font-black text-amber-600">۵٪ کسر شده از فاکتور</span>
                </div>
            </div>

            <!-- Toggle Button for Editing Bank Details -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleEditBankForm()" class="flex-1 py-2 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-base text-primary">edit_square</span>
                    <span id="btnEditBankLabel">ویرایش / ثبت اطلاعات بانکی مرکز</span>
                </button>
                <a href="payouts.php" class="flex-1 py-2 px-3 rounded-xl bg-secondary-container hover:bg-orange-600 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition-all text-center">
                    <span class="material-symbols-outlined text-base">launch</span>
                    <span>میز کار تسویه پایا</span>
                </a>
            </div>

            <!-- Inline Bank Details Edit Form (Collapsible) -->
            <form id="formEditBank" method="POST" action="" class="hidden p-4 rounded-2xl bg-amber-50/50 border border-amber-200/80 space-y-3">
                <input type="hidden" name="action" value="update_banking">
                <input type="hidden" id="editOrgId" name="org_id" value="">
                
                <h4 class="font-bold text-xs text-amber-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-amber-600">sync_saved_locally</span>
                    به‌روزرسانی اطلاعات حساب و شبای مرکز درمانی:
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">نام بانک:</label>
                        <select name="bank_name" id="inputBankName" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold focus:border-primary outline-none">
                            <option value="بانک سامان">بانک سامان</option>
                            <option value="بانک ملت">بانک ملت</option>
                            <option value="بانک ملی ایران">بانک ملی ایران</option>
                            <option value="بانک پاسارگاد">بانک پاسارگاد</option>
                            <option value="بانک تجارت">بانک تجارت</option>
                            <option value="بانک صادرات">بانک صادرات</option>
                            <option value="بانک سپه">بانک سپه</option>
                            <option value="بانک آینده">بانک آینده</option>
                            <option value="بانک شهر">بانک شهر</option>
                            <option value="بانک پارسیان">بانک پارسیان</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">نام صاحب حساب:</label>
                        <input type="text" name="bank_account_holder" id="inputBankAccountHolder" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold focus:border-primary outline-none" placeholder="نام رسمی شخص یا مرکز">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">شماره شبا رسمی (IR با ۲۴ رقم):</label>
                    <input type="text" name="bank_sheba" id="inputBankSheba" maxlength="26" dir="ltr" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-mono font-bold text-left focus:border-primary outline-none" placeholder="IR000000000000000000000000">
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">check</span>
                        <span>ذخیره تغییرات بانکی</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="closeOrgModal()" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">بستن</button>
        </div>
    </div>
</div>

<script>
const orgData = <?= json_encode($organizations) ?>;
const orgDetails = <?= json_encode($orgDetails) ?>;

function viewOrgDetails(orgId) {
    const org = orgData.find(o => parseInt(o.id) === parseInt(orgId));
    if (!org) return;

    const details = orgDetails[orgId] || { doctors: [], inventory: [] };

    document.getElementById('modalOrgName').innerText = org.name;
    document.getElementById('modalDocCount').innerText = details.doctors.length;
    document.getElementById('modalInvCount').innerText = details.inventory.length;

    // Populate Docs
    const docContainer = document.getElementById('modalDocsList');
    if (details.doctors.length === 0) {
        docContainer.innerHTML = '<p class="text-slate-400 italic p-4 text-center">پزشکی برای این مرکز ثبت نشده است.</p>';
    } else {
        docContainer.innerHTML = details.doctors.map(d => `
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-sm">stethoscope</span>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900">${d.name}</div>
                        <div class="text-[11px] text-slate-400">${d.specialty || 'پزشک عمومی'} | نقش: ${d.role_type === 'pharmacist' ? 'داروساز' : (d.role_type === 'groomer' ? 'گرومر' : 'پزشک')}</div>
                    </div>
                </div>
                <div class="text-left font-bold text-slate-600">
                    <div>${d.visits_count} ویزیت</div>
                </div>
            </div>
        `).join('');
    }

    // Populate Inventory (Clean Titles, Categories, Stock Numbers & Prices)
    const invContainer = document.getElementById('modalInvList');
    if (details.inventory.length === 0) {
        invContainer.innerHTML = '<p class="text-slate-400 italic p-4 text-center">موجودی دارویی/کالایی در سیستم ثبت نشده است.</p>';
    } else {
        invContainer.innerHTML = details.inventory.map(i => `
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 flex items-center justify-between gap-3 hover:bg-slate-100/80 transition-all">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl ${i.item_type === 'medicine' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'} flex items-center justify-center font-bold shrink-0 shadow-2xs">
                        <span class="material-symbols-outlined text-lg">${i.item_type === 'medicine' ? 'medication' : 'inventory_2'}</span>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <div class="font-bold text-slate-900 text-xs sm:text-sm truncate" title="${i.item_name}">${i.item_name}</div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md ${i.item_type === 'medicine' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'} font-semibold text-[10px]">${i.category || (i.item_type === 'medicine' ? 'دارو' : 'کالا')}</span>
                            ${i.sub_title ? `<span class="truncate text-slate-400 font-mono text-[10px]" dir="ltr">${i.sub_title}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="text-left shrink-0 font-bold space-y-0.5">
                    <div class="text-xs sm:text-sm font-black text-slate-800">${parseInt(i.quantity || 0).toLocaleString()} <span class="text-[11px] font-normal text-slate-500">عدد</span></div>
                    <div class="text-[11px] text-emerald-600 font-bold">${parseInt(i.effective_price || 0).toLocaleString()} تومان</div>
                </div>
            </div>
        `).join('');
    }

    // Populate Finance
    const bankName = org.bank_name || 'بانک سامان';
    const bankSheba = org.bank_sheba || 'IR120560000000100234567891';
    const bankHolder = org.bank_account_holder || org.manager_name || org.name;
    const availPayout = parseInt(org.balance_available_for_payout || 0);

    document.getElementById('modalBankName').innerText = bankName;
    document.getElementById('modalBankAccountHolder').innerText = bankHolder;
    document.getElementById('modalBankSheba').innerText = bankSheba;
    document.getElementById('modalAvailablePayout').innerText = availPayout.toLocaleString() + ' تومان';

    // Pre-fill Edit Bank Form
    document.getElementById('editOrgId').value = org.id;
    document.getElementById('inputBankName').value = bankName;
    document.getElementById('inputBankAccountHolder').value = bankHolder;
    document.getElementById('inputBankSheba').value = bankSheba;

    // Direct Payout Action Row for this organization
    document.getElementById('payoutOrgId').value = org.id;
    const payoutActionRow = document.getElementById('modalPayoutActionRow');
    if (availPayout > 0 && org.user_id) {
        payoutActionRow.classList.remove('hidden');
    } else {
        payoutActionRow.classList.add('hidden');
    }

    // Reset Edit Form visibility
    document.getElementById('formEditBank').classList.add('hidden');
    document.getElementById('btnEditBankLabel').innerText = 'ویرایش / ثبت اطلاعات بانکی مرکز';

    switchOrgModalTab('docs');
    document.getElementById('orgDetailModal').classList.remove('hidden');
}

function closeOrgModal() {
    document.getElementById('orgDetailModal').classList.add('hidden');
}

function toggleEditBankForm() {
    const form = document.getElementById('formEditBank');
    const label = document.getElementById('btnEditBankLabel');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        label.innerText = 'بستن فرم ویرایش';
    } else {
        form.classList.add('hidden');
        label.innerText = 'ویرایش / ثبت اطلاعات بانکی مرکز';
    }
}

function copyModalSheba() {
    const sheba = document.getElementById('modalBankSheba').innerText;
    if (sheba && sheba !== '-') {
        navigator.clipboard.writeText(sheba).then(() => {
            alert('شماره شبا در حافظه موقت کپی شد:\n' + sheba);
        });
    }
}

function switchOrgModalTab(tab) {
    document.getElementById('modalTabDocs').classList.add('hidden');
    document.getElementById('modalTabInv').classList.add('hidden');
    document.getElementById('modalTabFinance').classList.add('hidden');

    document.getElementById('btnTabDocs').className = "px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700";
    document.getElementById('btnTabInv').className = "px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700";
    document.getElementById('btnTabFinance').className = "px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700";

    if (tab === 'docs') {
        document.getElementById('modalTabDocs').classList.remove('hidden');
        document.getElementById('btnTabDocs').className = "px-4 py-2 text-xs font-black border-b-2 border-secondary-container text-secondary-container";
    } else if (tab === 'inv') {
        document.getElementById('modalTabInv').classList.remove('hidden');
        document.getElementById('btnTabInv').className = "px-4 py-2 text-xs font-black border-b-2 border-secondary-container text-secondary-container";
    } else if (tab === 'finance') {
        document.getElementById('modalTabFinance').classList.remove('hidden');
        document.getElementById('btnTabFinance').className = "px-4 py-2 text-xs font-black border-b-2 border-secondary-container text-secondary-container";
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
