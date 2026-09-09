<?php
require_once 'includes/pharmacist_header.php';

$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'update_rx_status') {
        $rxId = (int)($_POST['rx_id'] ?? 0);
        $newStatus = trim($_POST['dispensing_status'] ?? '');
        $allowed = ['pending_review', 'preparing', 'ready_for_pickup', 'dispensed', 'cancelled'];
        $notes = trim($_POST['pharmacist_notes'] ?? '');

        if ($rxId > 0 && in_array($newStatus, $allowed)) {
            $stmt = $pdo->prepare("
                UPDATE prescriptions 
                SET dispensing_status = ?,
                    status = CASE WHEN ? = 'dispensed' THEN 'approved' ELSE status END,
                    pharmacist_notes = CASE WHEN ? != '' THEN ? ELSE pharmacist_notes END,
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE id = ?
            ");
            if ($stmt->execute([$newStatus, $newStatus, $notes, $notes, $currentUser['id'], $rxId])) {
                $statusLabels = [
                    'preparing' => 'نسخه به وضعیت «در حال آماده‌سازی و بسته‌بندی» تغییر یافت.',
                    'ready_for_pickup' => 'دارو آماده شد و به صف «آماده تحویل / پیک» منتقل گردید.',
                    'dispensed' => 'دارو با موفقیت به مراجعه‌کننده تحویل و ثبت گردید.',
                    'cancelled' => 'نسخه لغو یا مرجوع شد.'
                ];
                $success = $statusLabels[$newStatus] ?? 'وضعیت نسخه با موفقیت بروزرسانی شد.';
            } else {
                $error = 'خطا در بروزرسانی وضعیت نسخه.';
            }
        }
    } elseif ($action === 'add_medicine') {
        $name = trim($_POST['name'] ?? '');
        $genericName = trim($_POST['generic_name'] ?? '');
        $category = trim($_POST['category'] ?? 'عمومی');
        $price = (int)($_POST['price'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $stock = (int)($_POST['stock'] ?? 10);
        $expiryDate = trim($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year')));
        $batchNumber = trim($_POST['batch_number'] ?? ('BT-' . rand(1000, 9999)));
        $reqRx = isset($_POST['requires_prescription']) ? 1 : 0;
        $reqCold = isset($_POST['requires_cold_chain']) ? 1 : 0;
        $dosage = trim($_POST['dosage_instructions'] ?? '');

        if (!empty($name)) {
            $ins = $pdo->prepare("
                INSERT INTO pharmacy_medicines 
                (organization_id, name, generic_name, category, price, brand, stock, expiry_date, batch_number, requires_prescription, requires_cold_chain, dosage_instructions, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($ins->execute([$orgId, $name, $genericName, $category, $price, $brand, $stock, $expiryDate, $batchNumber, $reqRx, $reqCold, $dosage])) {
                $success = "داروی جدید «{$name}» با موفقیت در انبار داروخانه ثبت شد.";
            } else {
                $error = "خطا در ثبت دارو.";
            }
        } else {
            $error = "لطفاً نام دارو را وارد نمایید.";
        }
    } elseif ($action === 'update_stock') {
        $medId = (int)($_POST['med_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        if ($medId > 0) {
            $upd = $pdo->prepare("UPDATE pharmacy_medicines SET stock = GREATEST(0, stock + ?) WHERE id = ?");
            if ($upd->execute([$delta, $medId])) {
                $success = "موجودی داروی انتخابی بروزرسانی شد.";
            }
        }
    } elseif ($action === 'bpms_start_review') {
        require_once '../includes/App.php';
        $rxId = (int)($_POST['rx_id'] ?? 0);
        if ($rxId > 0) {
            $ok = App::bpms()->pharmacistStartReview($rxId, $currentUser['id']);
            $success = $ok ? "✅ بررسی نسخه #{$rxId} شروع شد. وضعیت فرایند به «در دست بررسی» تغییر یافت." : "خطا یا وضعیت نسخه معتبر نیست.";
            if (!$ok) $error = $success; $success = '';
        }
    } elseif ($action === 'bpms_approve') {
        require_once '../includes/App.php';
        $rxId  = (int)($_POST['rx_id'] ?? 0);
        $notes = trim($_POST['pharmacist_notes'] ?? '');
        if ($rxId > 0) {
            $ok = App::bpms()->pharmacistApprove($rxId, $currentUser['id'], $notes);
            if ($ok) {
                $success = "✅ نسخه #{$rxId} تأیید شد! قفل فرایندی ارسال کلینیک باز شد. کلینیک اکنون می‌تواند مرسوله را ارسال کند.";
            } else {
                $error = "خطا در تأیید نسخه. احتمالاً نسخه قبلاً پردازش شده است.";
            }
        }
    } elseif ($action === 'bpms_reject') {
        require_once '../includes/App.php';
        $rxId   = (int)($_POST['rx_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? 'نسخه معتبر نیست یا اقلام دارویی با گزارش بالینی مطابقت ندارد.');
        if ($rxId > 0) {
            $ok = App::bpms()->pharmacistReject($rxId, $currentUser['id'], $reason);
            if ($ok) {
                $success = "❌ نسخه #{$rxId} رد شد. پزشک باید نسخه را اصلاح و مجدداً صادر نماید.";
            } else {
                $error = "خطا در رد نسخه.";
            }
        }
    }
}

// ── BPMS: Fetch Pending Prescriptions for Pharmacist Review ──────────────────
require_once '../includes/App.php';
$bpms = App::bpms();
$pharmacyId = null; // Single pharmacist linked to a pharmacy store
try {
    $psRow = $pdo->prepare("SELECT id FROM pharmacy_stores WHERE user_id = ?");
    $psRow->execute([$currentUser['id']]);
    $pharmacyId = (int)($psRow->fetchColumn() ?: 0) ?: null;
} catch (Throwable $e) {}

// All prescriptions visible to this pharmacist (their pharmacy or unassigned)
$bpmsPrescriptions = $bpms->getPrescriptionsForPharmacist($pharmacyId, 60);

// Segment by BPMS state
$bpmsPending  = array_filter($bpmsPrescriptions, fn($r) => in_array($r['bpms_state'] ?? '', ['broadcasted', 'pharmacist_review']));
$bpmsApproved = array_filter($bpmsPrescriptions, fn($r) => ($r['bpms_state'] ?? '') === 'pharmacist_approved');
$bpmsRejected = array_filter($bpmsPrescriptions, fn($r) => ($r['bpms_state'] ?? '') === 'pharmacist_rejected');

// Legacy: Fetch Electronic Prescriptions (backward compat)
$rxStmt = $pdo->prepare("
    SELECT p.*, u.name as customer_name, u.phone as customer_phone,
           d.name as doctor_name, d.specialty as doctor_specialty
    FROM prescriptions p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN doctors d ON p.doctor_id = d.id
    ORDER BY p.id DESC
    LIMIT 60
");
$rxStmt->execute();
$allPrescriptions = $rxStmt->fetchAll(PDO::FETCH_ASSOC);

// Prescriptions in queue vs dispensed
$pendingRx = array_filter($allPrescriptions, fn($r) => in_array($r['dispensing_status'] ?? '', ['pending_review', 'preparing', 'ready_for_pickup']));
$dispensedRx = array_filter($allPrescriptions, fn($r) => ($r['dispensing_status'] ?? '') === 'dispensed');

// Fetch Pharmacy Medicines
$medStmt = $pdo->query("SELECT * FROM pharmacy_medicines ORDER BY id DESC");
$medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stock & Expiry Alerts
$lowStockMeds = array_filter($medicines, fn($m) => (int)$m['stock'] <= 5);
$expiringMeds = array_filter($medicines, function($m) {
    if (empty($m['expiry_date'])) return false;
    $diffDays = (strtotime($m['expiry_date']) - time()) / 86400;
    return $diffDays >= 0 && $diffDays <= 60;
});
$alertCount = count($lowStockMeds) + count($expiringMeds);

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
?>

<div class="p-4 md:p-8 max-w-[1440px] mx-auto space-y-6 md:space-y-8">

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-2xl font-bold flex items-center gap-2 border border-emerald-200 shadow-sm animate-pulse text-xs">
            <span class="material-symbols-outlined">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-50 text-rose-800 p-4 rounded-2xl font-bold flex items-center gap-2 border border-rose-200 shadow-sm text-xs">
            <span class="material-symbols-outlined">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- 4 KPI Header Cards matching Doctor Panel layout -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <!-- Card 1: Pending Rx -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">prescriptions</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">نسخه‌های در انتظار آماده‌سازی</p>
                <h3 class="text-2xl font-black text-primary mt-1"><?= count($pendingRx) ?> نسخه</h3>
                <p class="text-[11px] text-indigo-600 font-bold mt-0.5">کارتابل روزانه داروخانه</p>
            </div>
        </div>

        <!-- Card 2: Active Medicines -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">medication</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">کل اقلام دارویی در انبار</p>
                <h3 class="text-2xl font-black text-primary mt-1"><?= count($medicines) ?> قلم دارو</h3>
                <p class="text-[11px] text-emerald-600 font-bold mt-0.5">موجود و قابل توزیع</p>
            </div>
        </div>

        <!-- Card 3: Stock & Expiry Alerts -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">warning</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">هشدارهای انقضا و کسری موجودی</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <h3 class="text-2xl font-black text-amber-600"><?= $alertCount ?> هشدار</h3>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= count($lowStockMeds) ?> کسری، <?= count($expiringMeds) ?> نزدیک به انقضا</p>
            </div>
        </div>

        <!-- Card 4: Dispensed Today -->
        <div class="bg-white p-6 rounded-2xl stat-card-shadow border border-outline-variant/30 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">نسخه‌های تحویل داده شده</p>
                <h3 class="text-2xl font-black text-orange-600 mt-1"><?= count($dispensedRx) ?> تحویل</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">تسویه از طریق حساب بیمارستان</p>
            </div>
        </div>
    </div>

    <!-- Active Section Header matching Doctor Panel layout -->
    <div class="flex items-center justify-between pb-2 border-b border-outline-variant/30">
        <div class="flex items-center gap-3">
            <div id="active-section-icon-bg" class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span id="active-section-icon" class="material-symbols-outlined text-2xl">prescriptions</span>
            </div>
            <div>
                <h2 id="active-section-title" class="text-xl font-black text-primary">کارتابل نسخه‌های الکترونیک</h2>
                <p id="active-section-desc" class="text-xs text-on-surface-variant">بررسی نسخه‌های ارجاعی از پزشکان، تطبیق دوز، آماده‌سازی و تحویل دارو</p>
            </div>
        </div>

        <button type="button" onclick="document.getElementById('addMedModal').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-secondary-container text-white text-xs font-bold shadow-sm hover:opacity-90 transition flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">add</span>
            <span>افزودن داروی جدید</span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB: BPMS CLINICAL REVIEW (Pharmacist Gateway)
    ══════════════════════════════════════════════════ -->
    <div id="bpms-tab" class="tab-content hidden space-y-5">

        <!-- Pending BPMS Review Queue -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-blue-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                        <span class="material-symbols-outlined">hourglass_top</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-indigo-900">نسخه‌های در انتظار تأیید / رد داروساز</h3>
                        <p class="text-[10px] text-indigo-700">تا زمانی که داروساز تأیید نکند، کلینیک قادر به ارسال نیست. 🔒</p>
                    </div>
                </div>
                <span class="text-xs bg-indigo-100 text-indigo-800 rounded-full px-3 py-1 font-bold border border-indigo-200"><?= count($bpmsPending) ?> نسخه</span>
            </div>

            <?php if (empty($bpmsPending)): ?>
            <div class="p-8 text-center text-xs text-slate-400">
                <span class="material-symbols-outlined text-4xl text-slate-200 block mb-2">inbox</span>
                هیچ نسخه‌ای در صف انتظار تأیید BPMS وجود ندارد. ✅
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($bpmsPending as $rx):
                    $items = json_decode($rx['items_json'] ?? '[]', true) ?: [];
                    $stateLabel = BpmsService::getStateLabelFa($rx['bpms_state'] ?? 'broadcasted');
                    $stateBadge = BpmsService::getStateBadgeClass($rx['bpms_state'] ?? 'broadcasted');
                ?>
                <div class="p-5 space-y-4 hover:bg-slate-50/50 transition-colors">
                    <!-- Header row -->
                    <div class="flex flex-col md:flex-row justify-between items-start gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-blue-100 text-indigo-700 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined">description</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-black text-sm text-slate-900">نسخه #<?= $rx['id'] ?></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $stateBadge ?>"><?= $stateLabel ?></span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    👤 بیمار: <strong><?= htmlspecialchars($rx['user_name'] ?? '—') ?></strong>
                                    <?php if ($rx['pet_name']): ?> | 🐾 <strong><?= htmlspecialchars($rx['pet_name']) ?></strong><?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-right text-xs text-slate-400" dir="ltr"><?= substr($rx['created_at'] ?? '', 0, 10) ?></div>
                    </div>

                    <!-- Clinical & Diagnosis -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <p class="text-[10px] font-bold text-blue-700 mb-1">🩺 پزشک صادرکننده</p>
                            <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($rx['doctor_name'] ?? 'نامشخص') ?> - <?= htmlspecialchars($rx['doctor_specialty'] ?? '') ?></p>
                            <?php if (!empty($rx['vet_license_number'])): ?><p class="text-[10px] text-slate-500">نظام: <?= htmlspecialchars($rx['vet_license_number']) ?></p><?php endif; ?>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-100">
                            <p class="text-[10px] font-bold text-amber-700 mb-1">🔬 تشخیص بالینی</p>
                            <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($rx['diagnosis'] ?? '—') ?></p>
                        </div>
                    </div>

                    <?php if (!empty($rx['doctor_examination_report'])): ?>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700">
                        <p class="text-[10px] font-bold text-slate-500 mb-1">📋 گزارش معاینه بالینی پزشک</p>
                        <?= nl2br(htmlspecialchars($rx['doctor_examination_report'])) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($items)): ?>
                    <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100">
                        <p class="text-[10px] font-bold text-indigo-700 mb-2">💊 اقلام دارویی نسخه</p>
                        <div class="space-y-1">
                            <?php foreach ($items as $it): ?>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($it['name'] ?? '') ?></span>
                                <?php if (!empty($it['dose'])): ?><span class="text-indigo-600 font-mono">(<?= htmlspecialchars($it['dose']) ?>)</span><?php endif; ?>
                                <span class="text-slate-500">× <?= (int)($it['qty'] ?? 1) ?> - <?= htmlspecialchars($it['instructions'] ?? '') ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($rx['pet_name'] && ($rx['allergies'] || $rx['chronic_conditions'])): ?>
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-xs">
                        <?php if ($rx['allergies']): ?><p class="text-rose-700">⚠️ <strong>آلرژی:</strong> <?= htmlspecialchars($rx['allergies']) ?></p><?php endif; ?>
                        <?php if ($rx['chronic_conditions']): ?><p class="text-amber-700 mt-0.5">💊 <strong>بیماری مزمن:</strong> <?= htmlspecialchars($rx['chronic_conditions']) ?></p><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- BPMS Action Buttons -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                        <!-- Approve -->
                        <form method="POST" class="space-y-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="bpms_approve">
                            <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">
                            <textarea name="pharmacist_notes" rows="2" placeholder="یادداشت داروساز (اختیاری)..." class="w-full p-2.5 rounded-xl border border-emerald-300 text-xs bg-emerald-50 outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                            <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs flex items-center justify-center gap-2 shadow-md transition-all">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                ✅ تأیید نسخه — باز کردن قفل ارسال کلینیک
                            </button>
                        </form>
                        <!-- Reject -->
                        <form method="POST" class="space-y-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="bpms_reject">
                            <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">
                            <textarea name="rejection_reason" rows="2" placeholder="دلیل رد نسخه (الزامی)..." required class="w-full p-2.5 rounded-xl border border-rose-300 text-xs bg-rose-50 outline-none focus:ring-2 focus:ring-rose-500 resize-none"></textarea>
                            <button type="submit" class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs flex items-center justify-center gap-2 shadow-md transition-all" onclick="return confirm('آیا از رد این نسخه اطمینان دارید؟')">
                                <span class="material-symbols-outlined text-sm">cancel</span>
                                ❌ رد نسخه — نیاز به اصلاح توسط پزشک
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Approved BPMS -->
        <?php if (!empty($bpmsApproved)): ?>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-emerald-100 bg-emerald-50 flex items-center justify-between">
                <h3 class="text-sm font-black text-emerald-800 flex items-center gap-2"><span class="material-symbols-outlined text-emerald-600">check_circle</span> نسخه‌های تأیید شده (آماده ارسال توسط کلینیک)</h3>
                <span class="text-[10px] bg-emerald-100 text-emerald-700 rounded-full px-2 py-1 font-bold"><?= count($bpmsApproved) ?> نسخه</span>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($bpmsApproved as $rx): ?>
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50/50 text-xs">
                    <div>
                        <span class="font-bold text-slate-800">نسخه #<?= $rx['id'] ?></span>
                        <span class="text-slate-500 mr-2"><?= htmlspecialchars($rx['user_name'] ?? '') ?></span>
                        <?php if ($rx['pet_name']): ?><span class="text-indigo-600">🐾 <?= htmlspecialchars($rx['pet_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="text-emerald-700 font-bold"><?= $rx['shipping_unlocked'] ? '🚀 قفل ارسال باز' : '⏳ در انتظار کلینیک' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab 1: Prescriptions Queue (کارتابل نسخ الکترونیک) -->
    <div id="prescriptions-tab" class="tab-content space-y-4">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">inbox</span>
                    نسخه‌های جاری در صف آماده‌سازی
                </h3>
                <span class="text-xs text-slate-400"><?= count($pendingRx) ?> نسخه در جریان</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-4">کد نسخه / تاریخ</th>
                            <th class="p-4">بیمار و سرپرست</th>
                            <th class="p-4">پزشک معالج</th>
                            <th class="p-4">تشخیص بالینی و اقلام دارویی</th>
                            <th class="p-4">وضعیت آماده‌سازی</th>
                            <th class="p-4 text-center">عملیات داروساز</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($pendingRx)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400 font-medium">
                                    هیچ نسخه‌ای در صف انتظار آماده‌سازی قرار ندارد. نسخه‌های ارجاعی از پزشکان بلافاصله در اینجا نمایش داده می‌شوند.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingRx as $rx): 
                                $items = json_decode($rx['items_json'] ?? '[]', true) ?: [];
                                $dispStatus = $rx['dispensing_status'] ?? 'pending_review';
                                $badge = match($dispStatus) {
                                    'pending_review'   => '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">در انتظار بررسی دوز</span>',
                                    'preparing'        => '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 animate-pulse">در حال آماده‌سازی و بسته‌بندی</span>',
                                    'ready_for_pickup' => '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">آماده تحویل حضوری / پیک</span>',
                                    default            => '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">' . htmlspecialchars($dispStatus) . '</span>'
                                };
                            ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-4 font-mono">
                                        <span class="font-bold text-slate-900 block">#RX-<?= $rx['id'] ?></span>
                                        <span class="text-[11px] text-slate-400"><?= $fmtDate->format(new DateTime($rx['created_at'])) ?></span>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($rx['customer_name'] ?? 'سرپرست بیمار') ?></div>
                                        <div class="text-[11px] text-sky-600 font-mono dir-ltr inline-block"><?= htmlspecialchars($rx['customer_phone'] ?? '') ?></div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($rx['vet_name'] ?: ($rx['doctor_name'] ?? 'دامپزشک کشیک')) ?></div>
                                        <span class="text-[10px] text-slate-400">نظام: <?= htmlspecialchars($rx['vet_license_number'] ?? 'ثبت شده') ?></span>
                                    </td>
                                    <td class="p-4 max-w-sm">
                                        <div class="font-bold text-slate-800 text-[11px] mb-1"><?= htmlspecialchars($rx['diagnosis'] ?? 'معاینه بالینی') ?></div>
                                        <?php if (!empty($items)): ?>
                                            <ul class="space-y-0.5 text-[11px] text-slate-600 list-disc pr-3">
                                                <?php foreach ($items as $it): ?>
                                                    <li>
                                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($it['name']) ?></span>
                                                        (<?= (int)($it['qty'] ?? 1) ?> عدد) - <span class="text-slate-500"><?= htmlspecialchars($it['instructions'] ?? '') ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <?php if (!empty($rx['pharmacist_notes'])): ?>
                                            <div class="mt-1 text-[10px] text-amber-700 bg-amber-50 p-1 rounded font-medium">یادداشت داروساز: <?= htmlspecialchars($rx['pharmacist_notes']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <?= $badge ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <?php if ($dispStatus === 'pending_review'): ?>
                                                <form method="POST" class="inline m-0">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="update_rx_status">
                                                    <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">
                                                    <input type="hidden" name="dispensing_status" value="preparing">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] shadow-sm transition">
                                                        تأیید و بسته‌بندی
                                                    </button>
                                                </form>
                                            <?php elseif ($dispStatus === 'preparing'): ?>
                                                <form method="POST" class="inline m-0">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="update_rx_status">
                                                    <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">
                                                    <input type="hidden" name="dispensing_status" value="ready_for_pickup">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-[11px] shadow-sm transition">
                                                        اعلام آماده تحویل
                                                    </button>
                                                </form>
                                            <?php elseif ($dispStatus === 'ready_for_pickup'): ?>
                                                <form method="POST" class="inline m-0">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="update_rx_status">
                                                    <input type="hidden" name="rx_id" value="<?= $rx['id'] ?>">
                                                    <input type="hidden" name="dispensing_status" value="dispensed">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-sm transition">
                                                        تحویل نهایی به بیمار
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Drug Inventory (انبار دارویی و کاتالوگ داروها) -->
    <div id="inventory-tab" class="tab-content space-y-4 hidden">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">inventory_2</span>
                        موجودی انبار اقلام دارویی داروخانه
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">کنترل انقضا، شماره بچ و دوزهای موجود</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1.5 rounded-xl font-bold">
                        <?= count($medicines) ?> قلم دارو
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-4">شناسه / برند</th>
                            <th class="p-4">نام دارو و نام ژنریک</th>
                            <th class="p-4">دسته درمانی</th>
                            <th class="p-4">قیمت واحد</th>
                            <th class="p-4">موجودی انبار</th>
                            <th class="p-4">تاریخ انقضا / بچ</th>
                            <th class="p-4">نوع عرضه</th>
                            <th class="p-4 text-center">ویرایش سریع موجودی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($medicines as $med): 
                            $isLow = (int)$med['stock'] <= 5;
                            $diffDays = !empty($med['expiry_date']) ? (strtotime($med['expiry_date']) - time()) / 86400 : 999;
                            $isExpiring = $diffDays >= 0 && $diffDays <= 60;
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-4 font-mono">
                                    <span class="font-bold text-slate-800 block">#MED-<?= $med['id'] ?></span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($med['brand'] ?? 'عمومی') ?></span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($med['name']) ?></div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5"><?= htmlspecialchars($med['generic_name'] ?? '') ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold">
                                        <?= htmlspecialchars($med['category'] ?? 'عمومی') ?>
                                    </span>
                                </td>
                                <td class="p-4 font-mono font-bold text-slate-800">
                                    <?= number_format($med['price']) ?> تومان
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold <?= $isLow ? 'bg-rose-100 text-rose-800 animate-pulse' : 'bg-emerald-100 text-emerald-800' ?>">
                                        <?= (int)$med['stock'] ?> عدد
                                    </span>
                                    <?php if ($isLow): ?>
                                        <span class="text-[10px] text-rose-600 block font-bold mt-1">هشدار کسری!</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-mono">
                                    <div class="text-[11px] <?= $isExpiring ? 'text-rose-600 font-bold' : 'text-slate-600' ?>">
                                        <?= htmlspecialchars($med['expiry_date'] ?: 'ندارد') ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">بچ: <?= htmlspecialchars($med['batch_number'] ?: '-') ?></div>
                                </td>
                                <td class="p-4">
                                    <?php if (!empty($med['requires_prescription'])): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            فقط با نسخه (Rx)
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            بدون نسخه (OTC)
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($med['requires_cold_chain'])): ?>
                                        <span class="text-[10px] text-sky-600 block font-bold mt-1">❄️ زنجیره سرد یخچال</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <form method="POST" class="inline m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                                            <input type="hidden" name="delta" value="5">
                                            <button type="submit" title="افزایش ۵ عدد" class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center transition">
                                                +5
                                            </button>
                                        </form>
                                        <form method="POST" class="inline m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                                            <input type="hidden" name="delta" value="-1">
                                            <button type="submit" title="کاهش ۱ عدد" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center transition">
                                                -1
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Autoship Refills (تکرار دارو و اتوشیپ مزمن) -->
    <div id="autoship-tab" class="tab-content space-y-4 hidden">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-teal-600">autorenew</span>
                    اشتراک‌های تکرار دارو برای بیماران مزمن (Autoship Chronic Rx)
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">مدیریت تحویل دوره‌ای انسولین، داروهای قلبی، کلیوی و مکمل‌های درمانی حیوانات نیازمند مراقبت مستمر</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-teal-50/60 border border-teal-200 flex items-start gap-3">
                    <span class="material-symbols-outlined text-teal-600 text-2xl">event_repeat</span>
                    <div>
                        <h4 class="text-xs font-bold text-teal-900">توزیع خودکار ماهانه</h4>
                        <p class="text-[11px] text-teal-700 mt-0.5 leading-relaxed">بسته‌های دارویی بیماران دیابتی و قلبی طبق دوره درمان آماده و تحویل پیک یا سرپرست می‌شود.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-sky-50/60 border border-sky-200 flex items-start gap-3">
                    <span class="material-symbols-outlined text-sky-600 text-2xl">ac_unit</span>
                    <div>
                        <h4 class="text-xs font-bold text-sky-900">پایش زنجیره سرد (Cold Chain)</h4>
                        <p class="text-[11px] text-sky-700 mt-0.5 leading-relaxed">انسولین و واکسن‌ها با بسته‌بندی آیس‌پک مخصوص و کنترل دما از داروخانه ترخیص می‌شوند.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200 flex items-start gap-3">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">account_balance</span>
                    <div>
                        <h4 class="text-xs font-bold text-indigo-900">تسویه متمرکز سازمانی</h4>
                        <p class="text-[11px] text-indigo-700 mt-0.5 leading-relaxed">عواید فروش داروخانه تجمیع شده و مستقیماً به حساب شبا رسمی بیمارستان واریز می‌گردد.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 4: Pharmacology Guide & Interactions (راهنمای تداخلات و هشدارها) -->
    <div id="interactions-tab" class="tab-content space-y-4 hidden">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600">sync_problem</span>
                    راهنمای بالینی تداخلات دارویی و منع مصرف دامپزشکی
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">راهنمای مرجع فارماکوکولوژی داروخانه جهت پیشگیری از مسمومیت‌ها و عوارض جانبی</p>
            </div>

            <div class="space-y-3">
                <div class="p-4 rounded-2xl border border-rose-200 bg-rose-50/50 flex items-start gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-xl flex-shrink-0">dangerous</span>
                    <div>
                        <h4 class="text-xs font-bold text-rose-900">استامینوفن (Paracetamol) در گربه‌ها: به شدت سمی و کشنده!</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">گربه‌ها به دلیل کمبود آنزیم گلوکورونیل ترانسفراز قادر به متابولیزه کردن استامینوفن نیستند و دچار متهموگلوبینمی شدید و مرگ می‌شوند. مصرف اکیداً ممنوع.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl border border-amber-200 bg-amber-50/50 flex items-start gap-3">
                    <span class="material-symbols-outlined text-amber-600 text-xl flex-shrink-0">warning</span>
                    <div>
                        <h4 class="text-xs font-bold text-amber-900">تداخل ملوکسیکام (NSAIDs) با کورتیکواستروئیدها (پردنیزولون / دگزامتازون)</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">مصرف همزمان داروهای ضدالتهاب غیراستروئیدی با کورتون‌ها خطر زخم حاد و خونریزی شدید معده را به شدت افزایش می‌دهد. حداقل ۴۸ ساعت فاصله زمانی لازم است.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50/50 flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-600 text-xl flex-shrink-0">info</span>
                    <div>
                        <h4 class="text-xs font-bold text-blue-900">تطبیق دوز انروفلوکساسین در گربه‌ها جهت حفظ بینایی</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">دوز انروفلوکساسین در گربه‌ها نباید از ۵ میلی‌گرم بر کیلوگرم تجاوز کند؛ دوزهای بالاتر ممکن است سبب کوری غیرقابل برگشت (دژنراسیون شبکیه) شود.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 5: History & Dispensing Archive (آرشیو تحویل و سوابق دارویی) -->
    <div id="history-tab" class="tab-content space-y-4 hidden">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span class="material-symbols-outlined text-emerald-600">history</span>
                سوابق نسخه‌های تحویل داده شده به مراجعین
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">کد نسخه</th>
                            <th class="p-3">بیمار</th>
                            <th class="p-3">پزشک صادرکننده</th>
                            <th class="p-3">تشخیص</th>
                            <th class="p-3">تاریخ تحویل</th>
                            <th class="p-3">وضعیت نهایی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($dispensedRx)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                    هنوز نسخه‌ای در آرشیو تحویل ثبت نشده است.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dispensedRx as $drx): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="p-3 font-mono font-bold text-slate-900">#RX-<?= $drx['id'] ?></td>
                                    <td class="p-3 font-bold text-slate-800"><?= htmlspecialchars($drx['customer_name'] ?? 'مراجعه‌کننده') ?></td>
                                    <td class="p-3 text-slate-600"><?= htmlspecialchars($drx['vet_name'] ?: ($drx['doctor_name'] ?? 'پزشک')) ?></td>
                                    <td class="p-3 text-slate-600"><?= htmlspecialchars($drx['diagnosis'] ?? 'معاینه') ?></td>
                                    <td class="p-3 font-mono text-slate-400"><?= htmlspecialchars($drx['reviewed_at'] ?: $drx['created_at']) ?></td>
                                    <td class="p-3">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            تحویل موفق
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Add Medicine -->
<div id="addMedModal" class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">add_circle</span>
                ثبت داروی جدید در انبار داروخانه
            </h3>
            <button onclick="document.getElementById('addMedModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_medicine">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام تجاری دارو *</label>
                    <input type="text" name="name" required placeholder="مثال: قرص فورتکور ۵" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام ژنریک</label>
                    <input type="text" name="generic_name" placeholder="Benazepril..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none dir-ltr">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">دسته دارویی</label>
                    <input type="text" name="category" placeholder="قلبی، آنتی‌بیوتیک..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">قیمت (تومان)</label>
                    <input type="number" name="price" required value="250000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">موجودی اولیه</label>
                    <input type="number" name="stock" required value="20" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاریخ انقضا</label>
                    <input type="date" name="expiry_date" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none dir-ltr">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره بچ (Batch/Lot)</label>
                    <input type="text" name="batch_number" placeholder="BT-2026..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none dir-ltr">
                </div>
            </div>

            <div class="flex items-center gap-6 p-3 rounded-xl bg-slate-50 border border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="requires_prescription" value="1" checked class="rounded text-emerald-600">
                    <span>نیازمند نسخه پزشک (Rx)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="requires_cold_chain" value="1" class="rounded text-sky-600">
                    <span>زنجیره سرد یخچال (Cold Chain)</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">دستور مصرف استاندارد</label>
                <textarea name="dosage_instructions" rows="2" placeholder="مثال: روزانه ۱ قرص همراه با غذا..." class="w-full p-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addMedModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20">
                    ثبت در انبار داروخانه
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById(tabId);
    if (target) target.classList.remove('hidden');

    const navKey = tabId.replace('-tab', '');
    document.querySelectorAll('#pharmacist-sidebar nav a').forEach(a => {
        a.className = "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
    });
    const activeLink = document.getElementById('nav-item-' + navKey);
    if (activeLink) {
        activeLink.className = "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all";
    }

    const headers = {
        'bpms':          { title: 'کارتابل BPMS — تأیید یا رد نسخه‌های پزشک', desc: 'گزارش معاینه بالینی، اقلام دارویی و تأیید/رد برای باز شدن قفل ارسال کلینیک', icon: 'account_tree' },
        'prescriptions': { title: 'کارتابل نسخه‌های الکترونیک', desc: 'بررسی نسخه‌های ارجاعی از پزشکان، تطبیق دوز، آماده‌سازی و تحویل دارو', icon: 'prescriptions' },
        'inventory': { title: 'انبار دارویی و کنترل موجودی', desc: 'مدیریت موجودی داروها، تاریخ انقضا، شماره بچ و قیمت‌گذاری', icon: 'medication' },
        'autoship': { title: 'تکرار دارو و اتوشیپ مزمن', desc: 'توزیع دوره‌ای داروهای بیماران مبتلا به بیماری‌های مزمن و پایش زنجیره سرد', icon: 'autorenew' },
        'interactions': { title: 'راهنمای بالینی و تداخلات دارویی', desc: 'راهنمای مرجع فارماکولوژی، دوزهای مجاز و منع مصرف در سگ و گربه', icon: 'sync_problem' },
        'history': { title: 'آرشیو تحویل و سوابق دارویی', desc: 'سوابق نسخه‌های ترخیص و تحویل داده شده به تفکیک بیمار و پزشک', icon: 'history' }
    };
    if (headers[navKey]) {
        document.getElementById('active-section-title').innerText = headers[navKey].title;
        document.getElementById('active-section-desc').innerText = headers[navKey].desc;
        document.getElementById('active-section-icon').innerText = headers[navKey].icon;
    }
}
</script>

<?php require_once 'includes/pharmacist_footer.php'; ?>
