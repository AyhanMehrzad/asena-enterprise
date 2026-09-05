<?php
$currentPage = 'security_logs';
require_once 'includes/admin_header.php';
require_once '../includes/App.php';
require_once '../includes/functions.php';

$auditService = App::securityAudit();
$message = '';
$messageType = '';

// Handle manual ban or unban
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];
    $adminId = (int)($_SESSION['user_id'] ?? 1);

    if ($action === 'ban_ip') {
        $ip = trim($_POST['ip_address'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 60);
        $reason = trim($_POST['reason'] ?? 'مسدودسازی دستی توسط مدیر سیستم');

        if (!empty($ip)) {
            $auditService->banIp($ip, $duration, $reason, $adminId);
            $message = "آدرس IP {$ip} با موفقیت به مدت {$duration} دقیقه مسدود گردید.";
            $messageType = 'success';
        }
    } elseif ($action === 'unban_ip') {
        $ip = trim($_POST['ip_address'] ?? '');
        if (!empty($ip)) {
            $auditService->unbanIp($ip);
            $message = "آدرس IP {$ip} با موفقیت رفع مسدودی گردید.";
            $messageType = 'success';
        }
    }
}

// Current Filter Parameters
$filterSeverity = $_GET['severity'] ?? 'all';
$logs = $auditService->getRecentEvents(150, $filterSeverity !== 'all' ? $filterSeverity : null);
$bannedIps = $auditService->getBannedIps();
$metrics = $auditService->getSecurityMetrics();
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-rose-600 text-3xl">security</span>
                <span>مرکز پایش امنیت و دفع حملات سایبری (SOC)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">لاگ‌های فایروال برنامه کاربردی (WAF)، تلاش‌های نفوذ، مسدودسازی IP و سوابق ممیزی</p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('manual-ban-modal').classList.toggle('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">block</span>
                <span>مسدودسازی دستی IP</span>
            </button>
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

    <!-- Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">visibility</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">رویدادهای ۲۴ ساعت گذشته</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($metrics['total_events_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">gpp_maybe</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">حملات دفع‌شده WAF</span>
                <span class="text-2xl font-black text-amber-600"><?= number_format($metrics['waf_blocks_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">dangerous</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">هشدارهای بحرانی</span>
                <span class="text-2xl font-black text-rose-600"><?= number_format($metrics['critical_events_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">shield_person</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">IPهای در حال حاضر مسدود</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($metrics['active_bans']) ?></span>
            </div>
        </div>
    </div>

    <!-- Manual Ban Modal Form (Toggled) -->
    <div id="manual-ban-modal" class="hidden bg-white p-5 rounded-2xl border border-rose-200 shadow-md">
        <h3 class="text-sm font-bold text-rose-800 mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">block</span>
            <span>ثبت مسدودسازی آدرس IP</span>
        </h3>
        <form method="POST" action="security_logs.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ban_ip">

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">آدرس IP</label>
                <input type="text" name="ip_address" required placeholder="مثال: 198.51.100.42" dir="ltr" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">مدت زمان مسدودی</label>
                <select name="duration_minutes" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="60">۱ ساعت</option>
                    <option value="360">۶ ساعت</option>
                    <option value="1440">۲۴ ساعت (۱ روز)</option>
                    <option value="10080">۷ روز</option>
                    <option value="525600">۱ سال (دائمی)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">دلیل مسدودسازی</label>
                <input type="text" name="reason" required placeholder="تلاش مکرر نفوذ یا ارسال اسپم" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <button type="submit" class="w-full h-10 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                    اعمال مسدودی IP
                </button>
            </div>
        </form>
    </div>

    <!-- Active Banned IPs Strip (if any) -->
    <?php if (!empty($bannedIps)): ?>
    <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4">
        <h3 class="text-xs font-black text-rose-900 mb-2 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base text-rose-600">do_not_disturb_on</span>
            <span>آدرس‌های IP مسدود شده جاری (<?= count($bannedIps) ?> مورد):</span>
        </h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($bannedIps as $b): ?>
            <div class="bg-white px-3 py-1.5 rounded-xl border border-rose-200 text-xs flex items-center gap-2 shadow-sm">
                <span class="font-mono text-slate-800 font-bold"><?= htmlspecialchars($b['ip_address']) ?></span>
                <span class="text-slate-400 text-[10px]">(تا <?= htmlspecialchars($b['banned_until']) ?>)</span>
                <form method="POST" action="security_logs.php" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="unban_ip">
                    <input type="hidden" name="ip_address" value="<?= htmlspecialchars($b['ip_address']) ?>">
                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-[11px] hover:underline mr-1" title="رفع مسدودی">
                        رفع مسدودی
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Severity Filter Tabs -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold text-slate-500 ml-2">فیلتر شدت رویداد:</span>
        <a href="security_logs.php?severity=all" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterSeverity === 'all' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
            همه رویدادها
        </a>
        <a href="security_logs.php?severity=critical" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterSeverity === 'critical' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
            بحرانی (Critical)
        </a>
        <a href="security_logs.php?severity=warning" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterSeverity === 'warning' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
            هشدارها (Warning)
        </a>
        <a href="security_logs.php?severity=info" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterSeverity === 'info' ? 'bg-sky-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
            اطلاعاتی (Info)
        </a>
    </div>

    <!-- Security Events Stream Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">زمان</th>
                        <th class="p-3.5">سطح شدت</th>
                        <th class="p-3.5">نوع رویداد</th>
                        <th class="p-3.5">آدرس IP</th>
                        <th class="p-3.5">مسیر درخواست</th>
                        <th class="p-3.5">توضیحات و خلاصه پیلود</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">هیچ رویدادی با این فیلتر ثبت نشده است.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $sevBadge = match($log['severity']) {
                                'critical', 'emergency' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'warning' => 'bg-amber-100 text-amber-800 border-amber-200',
                                default => 'bg-sky-100 text-sky-800 border-sky-200'
                            };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5 text-slate-400 whitespace-nowrap font-mono text-[11px]"><?= htmlspecialchars($log['created_at']) ?></td>
                            <td class="p-3.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black border <?= $sevBadge ?>">
                                    <?= htmlspecialchars(strtoupper($log['severity'])) ?>
                                </span>
                            </td>
                            <td class="p-3.5 font-bold whitespace-nowrap text-slate-900"><?= htmlspecialchars($log['event_type']) ?></td>
                            <td class="p-3.5 font-mono text-slate-800 whitespace-nowrap dir-ltr text-right"><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td class="p-3.5 font-mono text-slate-500 whitespace-nowrap dir-ltr text-right max-w-xs truncate"><?= htmlspecialchars($log['request_uri'] ?? '/') ?></td>
                            <td class="p-3.5 max-w-md truncate text-slate-600" title="<?= htmlspecialchars($log['payload_summary'] ?? '') ?>">
                                <?= htmlspecialchars($log['payload_summary'] ?? '') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/admin_footer.php'; ?>
