<?php
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$escrowService = App::escrow();
$postService = App::iranPost();

// ── 1. Handle Paya File Download ─────────────────────────────────────────────
if (isset($_GET['download_batch'])) {
    $batchId = (int)$_GET['download_batch'];
    $stmt = $pdo->prepare("SELECT batch_code, paya_export_content FROM seller_payout_batches WHERE id = ?");
    $stmt->execute([$batchId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($batch && !empty($batch['paya_export_content'])) {
        header('Content-Type: text/tab-separated-values; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $batch['batch_code'] . '.txt"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Iranian Excel/Notepad compatibility
        echo $batch['paya_export_content'];
        exit;
    }
}

$flashMessage = '';
$flashType = 'info';

// ── 2. Handle Admin Actions ──────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    SecurityMiddleware::validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'];

    if ($action === 'sync_post') {
        $postexService = App::postex();
        $postexRes = $postexService->syncInTransitParcels();
        $iranPostRes = $postService->syncInTransitShipments();

        $totalChecked = ($postexRes['checked_count'] ?? 0) + ($iranPostRes['checked_count'] ?? 0);
        $totalDelivered = ($postexRes['delivered_count'] ?? 0) + ($iranPostRes['delivered_count'] ?? 0);

        $flashMessage = "استعلام وب‌سرویس‌های پستی (Postex & Iran Post) با موفقیت انجام شد: {$totalChecked} مرسوله بررسی شد، {$totalDelivered} مرسوله تحویل شده ثبت گردید و مهلت ۷ روزه بازگشت کالا آغاز شد.";
        $flashType = 'success';
    } elseif ($action === 'release_matured') {
        $relRes = $escrowService->releaseMaturedEscrow();
        $flashMessage = "آزادسازی وجوه: {$relRes['released_count']} قلم کالا به مبلغ " . number_format($relRes['total_amount']) . " تومان به موجودی آماده تسویه افزوده شد.";
        $flashType = 'success';
    } elseif ($action === 'weekly_payout') {
        $payoutRes = $escrowService->generateWeeklyPayoutBatch((int)$_SESSION['user_id']);
        if ($payoutRes['success']) {
            $flashMessage = "بسته تسویه تجمیعی پایا برای کلیه فروشندگان با موفقیت صادر شد! کد پیگیری: {$payoutRes['batch_code']} | مبلغ کل: " . number_format($payoutRes['total_amount']) . " تومان برای {$payoutRes['seller_count']} فروشنده.";
            $flashType = 'success';
            $latestBatchId = $payoutRes['batch_id'] ?? null;
            $latestBatchCode = $payoutRes['batch_code'] ?? null;
        } else {
            $flashMessage = $payoutRes['message'];
            $flashType = 'error';
        }
    } elseif ($action === 'single_payout') {
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $rawAmount = trim($_POST['custom_amount'] ?? '');
        $customAmount = null;
        if ($rawAmount !== '') {
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            if ($cleaned !== '') {
                $customAmount = (int)$cleaned;
            }
        }
        $payoutRes = $escrowService->generateSingleSellerPayout($sellerId, (int)$_SESSION['user_id'], $customAmount);
        if ($payoutRes['success']) {
            $flashMessage = "حواله تسویه انفرادی برای «{$payoutRes['seller_name']}» با موفقیت صادر شد! شناسه پایا: {$payoutRes['batch_code']} | مبلغ: " . number_format($payoutRes['total_amount']) . " تومان.";
            $flashType = 'success';
            $latestBatchId = $payoutRes['batch_id'] ?? null;
            $latestBatchCode = $payoutRes['batch_code'] ?? null;
        } else {
            $flashMessage = $payoutRes['message'];
            $flashType = 'error';
        }
    }
}

// ── 3. Fetch Platform Escrow Metrics & Tables ───────────────────────────────
$metrics = $escrowService->getEscrowMetrics();

// Eligible Sellers ready for weekly payout
$eligibleStmt = $pdo->query("
    SELECT w.*, u.name as seller_name, u.phone as seller_phone, u.email as seller_email,
           o.name as org_name, o.id as org_id, o.type as org_type
    FROM seller_wallets w
    JOIN users u ON w.seller_id = u.id
    LEFT JOIN organizations o ON o.user_id = u.id
    WHERE w.balance_available_for_payout > 0
    ORDER BY w.balance_available_for_payout DESC
");
$eligibleSellers = $eligibleStmt->fetchAll(PDO::FETCH_ASSOC);
$totalEligibleCount = count($eligibleSellers);
$totalEligibleAmount = array_sum(array_column($eligibleSellers, 'balance_available_for_payout'));

// Active Escrow Ledger
$ledgerStmt = $pdo->query("
    SELECT l.*, o.post_tracking_code, o.status as order_status, u.name as seller_name
    FROM seller_escrow_ledger l
    JOIN orders o ON l.order_id = o.id
    JOIN users u ON l.seller_id = u.id
    ORDER BY l.id DESC
    LIMIT 50
");
$ledgerItems = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

// Count of active unverified post shipments
$unverifiedPostCount = (int)$pdo->query("
    SELECT COUNT(*) FROM orders 
    WHERE post_tracking_code IS NOT NULL AND post_delivery_verified = 0
")->fetchColumn();

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="space-y-6">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#1E293B] p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 dark:text-white">تسویه حساب بازارگاه و وجوه امانی (Escrow Engine)</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">مدیریت حساب متمرکز شرکت، احراز تحویل وب‌سرویس پست ایران، مهلت ۷ روزه تست و صدور حواله پایا</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Finance Settings Link -->
            <a href="finance_settings.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700 text-xs font-bold shadow-sm transition border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-sm text-[#fd8100]">credit_card</span>
                <span>تنظیمات کارت بانکی و خزانه‌داری</span>
            </a>

            <!-- 1-Click Post Sync -->
            <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                <input type="hidden" name="action" value="sync_post">
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">sync</span>
                    <span>استعلام آنی پست ایران</span>
                </button>
            </form>

            <!-- 1-Click Matured Escrow Release -->
            <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                <input type="hidden" name="action" value="release_matured">
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">lock_open</span>
                    <span>آزادسازی وجوه منقضی (۷ روز)</span>
                </button>
            </form>

            <!-- 1-Click Weekly Payout Batch -->
            <form method="POST" class="inline" onsubmit="return confirm('آیا از صدور بسته تسویه حساب تجمیعی همگی (<?= $totalEligibleCount ?> فروشنده به مبلغ <?= number_format($totalEligibleAmount) ?> تومان) در یک مرحله اطمینان دارید؟');">
                <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                <input type="hidden" name="action" value="weekly_payout">
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition <?= $totalEligibleCount === 0 ? 'opacity-60 cursor-not-allowed' : '' ?>" <?= $totalEligibleCount === 0 ? 'disabled' : '' ?>>
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span>صدور بسته تسویه تجمیعی (همه فروشندگان)</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Flash Alert -->
    <?php if ($flashMessage): ?>
        <div class="p-4 rounded-xl text-xs font-bold flex flex-col sm:flex-row sm:items-center justify-between gap-3 <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-lg"><?= $flashType === 'success' ? 'check_circle' : 'error' ?></span>
                <span><?= htmlspecialchars($flashMessage) ?></span>
            </div>
            <?php if (!empty($latestBatchCode) && !empty($latestBatchId)): ?>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="?download_batch=<?= (int)$latestBatchId ?>" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs inline-flex items-center gap-1.5 transition shadow-2xs">
                        <span class="material-symbols-outlined text-xs">download</span>
                        <span>دانلود فایل پایا (.txt)</span>
                    </a>
                    <a href="../actions/generate_payout_receipt.php?batch_code=<?= urlencode($latestBatchCode) ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold text-xs inline-flex items-center gap-1.5 transition shadow-2xs">
                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                        <span>مشاهده و چاپ رسید رسمی پایا</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">امانت در حساب شرکت (مهلت ۷ روزه)</span>
                <span class="material-symbols-outlined text-amber-500">lock_clock</span>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white mt-2">
                <?= number_format($metrics['total_pending_escrow']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <div class="text-[11px] text-amber-600 font-bold mt-1">
                <?= $metrics['active_in_inspection_count'] ?> سفارش در انتظار پایان مهلت تست
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">آماده تسویه چرخه پایا</span>
                <span class="material-symbols-outlined text-emerald-500">account_balance_wallet</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2">
                <?= number_format($totalEligibleAmount) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <div class="text-[11px] text-emerald-600 font-bold mt-1">
                <?= $totalEligibleCount ?> فروشنده / مرکز واجد شرایط تسویه
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">مجموع تسویه شده (پایا)</span>
                <span class="material-symbols-outlined text-blue-500">check_circle</span>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white mt-2">
                <?= number_format($metrics['total_lifetime_settled']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <div class="text-[11px] text-blue-600 font-bold mt-1">
                تسویه شده از طریق حواله پایا بانک مرکزی
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">مرسولات پستی در جریان استعلام</span>
                <span class="material-symbols-outlined text-indigo-500">local_shipping</span>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white mt-2">
                <?= $unverifiedPostCount ?> <span class="text-xs font-normal text-slate-400">بسته پستی</span>
            </div>
            <div class="text-[11px] text-indigo-600 font-bold mt-1">
                رهگیری خودکار با بارکد ۲۴ رقمی
            </div>
        </div>
    </div>

    <!-- Section 1: Eligible Sellers Ready For Payout (Bulk & Individual) -->
    <div class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">credit_card</span>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-800 dark:text-white">مراکز و فروشندگان آماده تسویه پایا (بانک مرکزی)</h2>
                    <p class="text-[11px] text-slate-400">امکان تسویه تجمیعی همگی با یک دکمه یا تسویه انفرادی هر فروشنده به دلخواه مدیریت</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <?= $totalEligibleCount ?> فروشنده آماده (<?= number_format($totalEligibleAmount) ?> تومان)
                </span>
                <?php if ($totalEligibleCount > 0): ?>
                    <!-- ALL-IN-ONE BULK PAYOUT BUTTON -->
                    <form method="POST" class="inline" onsubmit="return confirm('آیا از صدور تسویه حساب تجمیعی همگی (<?= $totalEligibleCount ?> فروشنده به مبلغ <?= number_format($totalEligibleAmount) ?> تومان) در یک مرحله اطمینان دارید؟');">
                        <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="weekly_payout">
                        <button type="submit" class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm hover:shadow-md transition">
                            <span class="material-symbols-outlined text-sm">payments</span>
                            <span>⚡ تسویه تجمیعی همگی با یک کلیک</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3">فروشنده / مرکز</th>
                        <th class="px-4 py-3">شماره تماس</th>
                        <th class="px-4 py-3">نام بانک</th>
                        <th class="px-4 py-3">صاحب حساب</th>
                        <th class="px-4 py-3">شماره شبا (IBAN)</th>
                        <th class="px-4 py-3">مبلغ آماده تسویه</th>
                        <th class="px-4 py-3">وضعیت شبا</th>
                        <th class="px-4 py-3 text-center">عملیات تسویه انفرادی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <?php if (empty($eligibleSellers)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                در حال حاضر هیچ فروشنده‌ای دارای موجودی تسویه‌پذیر آزادشده نیست.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eligibleSellers as $s): 
                            $isValidSheba = !empty($s['bank_sheba']) && preg_match('/^IR\d{24}$/i', $s['bank_sheba']);
                            $cleanSheba = !empty($s['bank_sheba']) ? $s['bank_sheba'] : 'IR000000000000000000000000';
                            $cleanBank = !empty($s['bank_name']) ? $s['bank_name'] : 'بانک متصل شبا';
                            $cleanHolder = !empty($s['bank_account_holder']) ? $s['bank_account_holder'] : $s['seller_name'];
                            $displayName = !empty($s['org_name']) ? $s['org_name'] : $s['seller_name'];
                        ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        <?= htmlspecialchars($displayName) ?>
                                    </div>
                                    <?php if (!empty($s['org_name'])): ?>
                                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-[12px]">apartment</span>
                                            <span>مرکز درمانی (مالک: <?= htmlspecialchars($s['seller_name']) ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-[10px] text-slate-400 font-semibold flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-[12px]">storefront</span>
                                            <span>پت‌شاپ / فروشنده کالا</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px]"><?= htmlspecialchars($s['seller_phone'] ?? '-') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($cleanBank) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($cleanHolder) ?></td>
                                <td class="px-4 py-3 font-mono text-[11px] dir-ltr text-left">
                                    <div class="flex items-center gap-1.5">
                                        <span><?= htmlspecialchars($cleanSheba) ?></span>
                                        <button type="button" onclick="copySheba('<?= htmlspecialchars($cleanSheba) ?>')" class="text-slate-400 hover:text-slate-600 p-0.5" title="کپی شماره شبا">
                                            <span class="material-symbols-outlined text-xs">content_copy</span>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-black text-emerald-600 text-sm">
                                    <?= number_format($s['balance_available_for_payout']) ?> تومان
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($isValidSheba): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">شبا معتبر</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">شبا ناقص</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Instant Full Settle Form -->
                                        <form method="POST" class="inline" onsubmit="return confirm('آیا از تسویه حساب کل موجودی به مبلغ <?= number_format($s['balance_available_for_payout']) ?> تومان برای «<?= htmlspecialchars(addslashes($displayName)) ?>» اطمینان دارید؟');">
                                            <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="single_payout">
                                            <input type="hidden" name="seller_id" value="<?= (int)$s['seller_id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold text-[11px] inline-flex items-center gap-1 transition shadow-2xs" title="تسویه کل موجودی این فروشنده در قالب حواله پایا">
                                                <span class="material-symbols-outlined text-xs">done_all</span>
                                                <span>تسویه کل</span>
                                            </button>
                                        </form>

                                        <!-- Custom Amount Payout Button (Modal Trigger) -->
                                        <button type="button" 
                                            onclick="openSinglePayoutModal(<?= (int)$s['seller_id'] ?>, '<?= htmlspecialchars(addslashes($displayName)) ?>', <?= (int)$s['balance_available_for_payout'] ?>, '<?= htmlspecialchars(addslashes($cleanBank)) ?>', '<?= htmlspecialchars(addslashes($cleanSheba)) ?>', '<?= htmlspecialchars(addslashes($cleanHolder)) ?>')"
                                            class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-[11px] inline-flex items-center gap-1 transition shadow-2xs"
                                            title="تسویه با مبلغ دلخواه مدیریت">
                                            <span class="material-symbols-outlined text-xs">tune</span>
                                            <span>مبلغ دلخواه...</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Active Escrow & Iran Post Tracking Ledger -->
    <div class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">inventory</span>
                <h2 class="text-sm font-black text-slate-800 dark:text-white">دفتر کل وجوه امانی، استعلام پست و مهلت ۷ روزه</h2>
            </div>
            <span class="text-xs text-slate-400">۵۰ رکورد اخیر</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3">سفارش</th>
                        <th class="px-4 py-3">فروشنده</th>
                        <th class="px-4 py-3">بارکد ۲۴ رقمی پست ایران</th>
                        <th class="px-4 py-3">مبلغ ناخالص</th>
                        <th class="px-4 py-3">کارمزد شرکت</th>
                        <th class="px-4 py-3">سهم خالص فروشنده</th>
                        <th class="px-4 py-3">وضعیت تحویل</th>
                        <th class="px-4 py-3">موعد آزادسازی (۷ روز)</th>
                        <th class="px-4 py-3">وضعیت وجه</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <?php if (empty($ledgerItems)): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                هیچ تراکنش امانی ثبت نشده است.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ledgerItems as $item): 
                            $isDelivered = !empty($item['delivered_at']);
                            $eligibleTime = !empty($item['payout_eligible_at']) ? strtotime($item['payout_eligible_at']) : null;
                            $now = time();
                            $daysLeft = $eligibleTime ? max(0, ceil(($eligibleTime - $now) / 86400)) : 7;
                        ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">#<?= $item['order_id'] ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($item['seller_name']) ?></td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    <?php if (!empty($item['post_tracking_code'])): ?>
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            <?= htmlspecialchars($item['post_tracking_code']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">بدون بارکد پستی</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono"><?= number_format($item['gross_amount']) ?> تومان</td>
                                <td class="px-4 py-3 font-mono text-rose-500"><?= number_format($item['commission_amount']) ?> تومان</td>
                                <td class="px-4 py-3 font-mono font-bold text-emerald-600"><?= number_format($item['net_seller_amount']) ?> تومان</td>
                                <td class="px-4 py-3">
                                    <?php if ($isDelivered): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">تحویل شده (تأیید پست)</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">در جریان ارسال</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($item['status'] === 'held_in_escrow'): ?>
                                        <?php if ($isDelivered): ?>
                                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 font-bold text-[10px]">
                                                <?= $daysLeft ?> روز باقی‌مانده تا آزادسازی
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[10px]">پس از تحویل پست</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[10px]"><?= $item['payout_eligible_at'] ?: '-' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    switch ($item['status']) {
                                        case 'held_in_escrow':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">امانت شرکت</span>';
                                            break;
                                        case 'released_to_available':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">آزاد شده (آماده تسویه)</span>';
                                            break;
                                        case 'settled_in_batch':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">تسویه شده (پایا)</span>';
                                            break;
                                        default:
                                            echo htmlspecialchars($item['status']);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Central Bank Paya Settlement Batches Archive -->
    <div class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">history_edu</span>
                <h2 class="text-sm font-black text-slate-800 dark:text-white">بسته‌های تسویه هفتگی صادر شده (حواله‌های پایا)</h2>
            </div>
            <span class="text-xs text-slate-400">بانک مرکزی جمهوری اسلامی ایران</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3">کد پیگیری بسته</th>
                        <th class="px-4 py-3">تاریخ و ساعت صدور</th>
                        <th class="px-4 py-3">مبلغ کل تسویه</th>
                        <th class="px-4 py-3">تعداد فروشندگان</th>
                        <th class="px-4 py-3">وضعیت حواله</th>
                        <th class="px-4 py-3">دانلود فایل تب‌بندی پایا</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <?php if (empty($metrics['recent_batches'])): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                هنوز هیچ بسته تسویه هفتگی صادر نشده است.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($metrics['recent_batches'] as $b): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-mono font-black text-slate-900 dark:text-white"><?= htmlspecialchars($b['batch_code']) ?></td>
                                <td class="px-4 py-3 font-mono text-[11px]"><?= $b['created_at'] ?></td>
                                <td class="px-4 py-3 font-black text-emerald-600"><?= number_format($b['total_payout_amount']) ?> تومان</td>
                                <td class="px-4 py-3">
                                    <?php if ($b['seller_count'] == 1 || strpos($b['batch_code'], 'SNGL') !== false): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">تسویه انفرادی (۱ مرکز)</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">تسویه تجمیعی (<?= $b['seller_count'] ?> فروشنده)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">تکمیل شده</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="?download_batch=<?= $b['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                                            <span class="material-symbols-outlined text-xs">download</span>
                                            <span>فایل پایا (.txt)</span>
                                        </a>
                                        <a href="../actions/generate_payout_receipt.php?batch_code=<?= urlencode($b['batch_code']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold transition text-xs border border-emerald-200">
                                            <span class="material-symbols-outlined text-xs">receipt_long</span>
                                            <span>رسید حواله پایا</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4: Automated Payout Protocols & Integration Guide (اتوماسیون تسویه) -->
    <div class="bg-gradient-to-br from-slate-900 via-primary to-[#001f4f] rounded-2xl p-6 text-white shadow-md border border-white/10 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/15 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400">
                    <span class="material-symbols-outlined text-2xl">auto_mode</span>
                </div>
                <div>
                    <h3 class="font-black text-sm text-white">راهنمای فعال‌سازی اتوماسیون تسویه حساب خودکار (Automated Payout Engine)</h3>
                    <p class="text-xs text-white/70 mt-0.5">پروتکل‌های واریز خودکار هفتگی بدون نیاز به کلیک ادمین منطبق با شبکه بانکی کشور</p>
                </div>
            </div>
            <span class="text-xs font-bold bg-emerald-500/20 text-emerald-300 px-3 py-1 rounded-full border border-emerald-400/30 shrink-0">
                نرخ کارمزد فعال: ۵٪ پلتفرم
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5 mb-2">
                    <span class="material-symbols-outlined text-base">schedule</span>
                    روش ۱: زمان‌بندی خودکار با CronJob (پیشنهادی سرور)
                </div>
                <p class="text-white/80 leading-relaxed">
                    با تعریف کران‌جاب زیر، هر پنج‌شنبه راس ساعت ۲۲:۰۰ تسویه حساب تمام فروشندگان واجد شرایط صادر و پیامک واریز با کد پیگیری برای آن‌ها ارسال می‌شود:
                </p>
                <div class="mt-2.5 bg-black/40 p-2.5 rounded-lg font-mono text-emerald-300 select-all dir-ltr text-left overflow-x-auto">
                    0 22 * * 4 php <?= htmlspecialchars(defined('ASENA_ROOT') ? ASENA_ROOT : dirname(__DIR__)) ?>/bin/asena escrow:weekly-payout
                </div>
            </div>

            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                <div class="font-bold text-teal-300 flex items-center gap-1.5 mb-2">
                    <span class="material-symbols-outlined text-base">api</span>
                    روش ۲: وب‌سرویس واریز مستقیم شبا (Open Banking API)
                </div>
                <p class="text-white/80 leading-relaxed">
                    با اتصال کلید API بانکداری باز (زرین‌پال تسویه پایا / وندار / به پرداخت ملت)، مبالغ تسویه مستقیماً بدون نیاز به آپلود فایل TXT به شماره شبای فروشندگان واریز می‌گردد.
                </p>
                <div class="mt-2.5 flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-300 font-mono text-[11px]">ZarinPal Payout API: آماده</span>
                    <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-300 font-mono text-[11px]">Vandar Settlement: آماده</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Single Seller Payout Modal (تنظیم تسویه انفرادی) -->
<div id="singlePayoutModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#1E293B] rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-800 space-y-5">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900 dark:text-white">صدور حواله پایا انفرادی</h3>
                    <p id="spmSellerTitle" class="text-xs text-slate-500 dark:text-slate-400">مرکز / فروشنده: -</p>
                </div>
            </div>
            <button type="button" onclick="closeSinglePayoutModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>

        <!-- Target Seller Bank Info Box -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-2 text-xs">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-bold">بانک مقصد و صاحب حساب:</span>
                <span id="spmBankAndHolder" class="font-bold text-slate-800 dark:text-white">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-bold">شماره شبا رسمی پایا:</span>
                <span id="spmSheba" class="font-mono font-black text-slate-900 dark:text-white dir-ltr text-left tracking-wider text-[11px]">-</span>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700">
                <span class="text-slate-500 font-bold">کل موجودی آماده تسویه:</span>
                <span id="spmAvailableBalance" class="font-black text-emerald-600 text-sm">۰ تومان</span>
            </div>
        </div>

        <!-- Payout Form -->
        <form id="singlePayoutForm" method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
            <input type="hidden" name="action" value="single_payout">
            <input type="hidden" id="spmSellerId" name="seller_id" value="">

            <!-- Mode Radio Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">روش تعیین مبلغ تسویه:</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        <input type="radio" name="payout_mode" value="full" checked onchange="togglePayoutMode(this.value)" class="text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <div class="font-black text-slate-800 dark:text-white">تسویه کامل (۱۰۰٪)</div>
                            <div class="text-[10px] text-slate-400">کل موجودی آزاد شده</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        <input type="radio" name="payout_mode" value="custom" onchange="togglePayoutMode(this.value)" class="text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <div class="font-black text-slate-800 dark:text-white">مبلغ دلخواه</div>
                            <div class="text-[10px] text-slate-400">تسویه با مبلغ دلخواه مدیریت</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Custom Amount Input Container -->
            <div id="spmCustomAmountBox" class="hidden space-y-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">مبلغ مد نظر برای تسویه (تومان):</label>
                <div class="relative">
                    <input type="text" id="spmCustomAmountInput" name="custom_amount" placeholder="مثال: ۱,۵۰۰,۰۰۰"
                        oninput="formatNumberInput(this)"
                        class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-black text-slate-900 dark:text-white focus:border-emerald-500 outline-none">
                    <span class="absolute left-3 top-3 text-xs text-slate-400 font-bold">تومان</span>
                </div>

                <!-- Quick Percentage Chips -->
                <div class="flex items-center gap-1.5 pt-1">
                    <span class="text-[11px] text-slate-400 font-bold">میانبرها:</span>
                    <button type="button" onclick="setPayoutPercentage(0.25)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition">۲۵٪</button>
                    <button type="button" onclick="setPayoutPercentage(0.50)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition">۵۰٪</button>
                    <button type="button" onclick="setPayoutPercentage(0.75)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition">۷۵٪</button>
                    <button type="button" onclick="setPayoutPercentage(1.00)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition">۱۰۰٪ کامل</button>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 text-[11px] text-emerald-800 dark:text-emerald-300 leading-relaxed">
                💡 کارمزد زیرساخت ۵٪ آسنا پیش‌تر کسر گردیده است. مبلغ انتخابی، ۱۰۰٪ به صورت حواله پایا بانک مرکزی به شبای فروشنده منتقل خواهد شد.
            </div>

            <!-- Form Buttons -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeSinglePayoutModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm hover:shadow-md transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span>تایید و صدور حواله پایا</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let spmCurrentMaxAvailable = 0;

function openSinglePayoutModal(sellerId, sellerName, maxAvailable, bankName, bankSheba, holderName) {
    spmCurrentMaxAvailable = parseInt(maxAvailable) || 0;
    document.getElementById('spmSellerId').value = sellerId;
    document.getElementById('spmSellerTitle').innerText = 'مرکز / فروشنده: ' + sellerName;
    document.getElementById('spmBankAndHolder').innerText = bankName + ' (' + holderName + ')';
    document.getElementById('spmSheba').innerText = bankSheba;
    document.getElementById('spmAvailableBalance').innerText = spmCurrentMaxAvailable.toLocaleString() + ' تومان';

    const radios = document.getElementsByName('payout_mode');
    for (let r of radios) {
        if (r.value === 'full') r.checked = true;
    }
    document.getElementById('spmCustomAmountBox').classList.add('hidden');
    document.getElementById('spmCustomAmountInput').value = '';

    document.getElementById('singlePayoutModal').classList.remove('hidden');
}

function closeSinglePayoutModal() {
    document.getElementById('singlePayoutModal').classList.add('hidden');
}

function togglePayoutMode(mode) {
    const customBox = document.getElementById('spmCustomAmountBox');
    const customInput = document.getElementById('spmCustomAmountInput');
    if (mode === 'custom') {
        customBox.classList.remove('hidden');
        if (!customInput.value) {
            customInput.value = spmCurrentMaxAvailable.toLocaleString();
        }
        customInput.focus();
    } else {
        customBox.classList.add('hidden');
        customInput.value = '';
    }
}

function setPayoutPercentage(pct) {
    const amount = Math.floor(spmCurrentMaxAvailable * pct);
    document.getElementById('spmCustomAmountInput').value = amount.toLocaleString();
}

function formatNumberInput(el) {
    let val = el.value.replace(/[^\d]/g, '');
    if (val) {
        let num = parseInt(val, 10);
        if (num > spmCurrentMaxAvailable) {
            num = spmCurrentMaxAvailable;
        }
        el.value = num.toLocaleString();
    } else {
        el.value = '';
    }
}

function copySheba(sheba) {
    if (sheba && sheba !== '-') {
        navigator.clipboard.writeText(sheba).then(() => {
            alert('شماره شبا در کلیپ‌بورد کپی شد:\n' + sheba);
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
