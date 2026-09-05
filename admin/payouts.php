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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    SecurityMiddleware::validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'];

    if ($action === 'sync_post') {
        $syncRes = $postService->syncInTransitShipments();
        $flashMessage = "استعلام پست انجام شد: {$syncRes['checked_count']} مرسوله بررسی شد، {$syncRes['delivered_count']} مرسوله تحویل شده ثبت گردید و مهلت ۷ روزه آغاز شد.";
        $flashType = 'success';
    } elseif ($action === 'release_matured') {
        $relRes = $escrowService->releaseMaturedEscrow();
        $flashMessage = "آزادسازی وجوه: {$relRes['released_count']} قلم کالا به مبلغ " . number_format($relRes['total_amount']) . " تومان به موجودی آماده تسویه افزوده شد.";
        $flashType = 'success';
    } elseif ($action === 'weekly_payout') {
        $payoutRes = $escrowService->generateWeeklyPayoutBatch((int)$_SESSION['user_id']);
        if ($payoutRes['success']) {
            $flashMessage = "دسته تسویه پایا با موفقیت صادر شد! کد پیگیری: {$payoutRes['batch_code']} | مبلغ کل: " . number_format($payoutRes['total_amount']) . " تومان برای {$payoutRes['seller_count']} فروشنده.";
            $flashType = 'success';
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
    SELECT w.*, u.name as seller_name, u.phone as seller_phone, u.email as seller_email
    FROM seller_wallets w
    JOIN users u ON w.seller_id = u.id
    WHERE w.balance_available_for_payout > 0
    ORDER BY w.balance_available_for_payout DESC
");
$eligibleSellers = $eligibleStmt->fetchAll(PDO::FETCH_ASSOC);

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
            <form method="POST" class="inline" onsubmit="return confirm('آیا از صدور بسته تسویه حساب هفتگی پایا برای فروشندگان اطمینان دارید؟');">
                <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
                <input type="hidden" name="action" value="weekly_payout">
                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span>صدور بسته تسویه هفتگی (پایا)</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Flash Alert -->
    <?php if ($flashMessage): ?>
        <div class="p-4 rounded-xl text-xs font-bold flex items-center gap-3 <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <span class="material-symbols-outlined"><?= $flashType === 'success' ? 'check_circle' : 'error' ?></span>
            <span><?= htmlspecialchars($flashMessage) ?></span>
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
                <span class="text-xs font-bold text-slate-500">آماده تسویه چرخه هفتگی</span>
                <span class="material-symbols-outlined text-emerald-500">account_balance_wallet</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2">
                <?= number_format($metrics['total_available_payout']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <div class="text-[11px] text-emerald-600 font-bold mt-1">
                <?= $metrics['eligible_sellers_count'] ?> فروشنده واجد شرایط تسویه
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

    <!-- Section 1: Eligible Sellers Ready For Weekly Payout -->
    <div class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">credit_card</span>
                <h2 class="text-sm font-black text-slate-800 dark:text-white">فروشندگان آماده در چرخه تسویه هفتگی (حواله پایا)</h2>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-700">
                <?= count($eligibleSellers) ?> فروشنده
            </span>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    <?php if (empty($eligibleSellers)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                در حال حاضر هیچ فروشنده‌ای دارای موجودی تسویه‌پذیر آزادشده نیست.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eligibleSellers as $s): 
                            $isValidSheba = !empty($s['bank_sheba']) && preg_match('/^IR\d{24}$/i', $s['bank_sheba']);
                        ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($s['seller_name']) ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px]"><?= htmlspecialchars($s['seller_phone'] ?? '-') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($s['bank_name'] ?: 'ثبت نشده') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($s['bank_account_holder'] ?: $s['seller_name']) ?></td>
                                <td class="px-4 py-3 font-mono text-[11px] dir-ltr text-left">
                                    <?= htmlspecialchars($s['bank_sheba'] ?: 'IR000000000000000000000000') ?>
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
                                <td class="px-4 py-3"><?= $b['seller_count'] ?> فروشنده</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">تکمیل شده</span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="?download_batch=<?= $b['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                                        <span class="material-symbols-outlined text-xs">download</span>
                                        <span>فایل پایا (.txt)</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
