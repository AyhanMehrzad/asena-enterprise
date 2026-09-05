<?php
$currentPage = 'security_logs';
require_once 'includes/admin_header.php';
require_once '../includes/App.php';
require_once '../includes/functions.php';
require_once '../includes/TrafficMonitoringService.php';

$auditService = App::securityAudit();
$trafficService = App::traffic();
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
        $threat = trim($_POST['threat_type'] ?? 'manual');

        if (!empty($ip)) {
            $trafficService->autoRestrictIp($ip, $duration, $threat, $reason);
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

// Active Tab & Filters
$activeTab = $_GET['tab'] ?? 'stream';
$filterSeverity = $_GET['severity'] ?? 'all';
$filterAction = $_GET['action_type'] ?? 'all';
$inspectIp = trim($_GET['inspect_ip'] ?? '');
$inspectUser = isset($_GET['inspect_user']) ? (int)$_GET['inspect_user'] : null;

if (!empty($inspectIp) || !empty($inspectUser)) {
    $activeTab = 'investigate';
}

// Data fetching
$trafficMetrics = $trafficService->getTrafficMetrics();
$wafMetrics = $auditService->getSecurityMetrics();
$bannedIps = $auditService->getBannedIps();

// Interaction logs
$recentInteractions = $trafficService->getRecentInteractions(100, $filterAction !== 'all' ? $filterAction : null);

// WAF Security Audit Logs
$wafLogs = $auditService->getRecentEvents(100, $filterSeverity !== 'all' ? $filterSeverity : null);

// Investigation logs if inspecting specific IP or user
$investigationLogs = [];
if (!empty($inspectIp)) {
    $investigationLogs = $trafficService->getRequestHistoryByIp($inspectIp, 150);
} elseif (!empty($inspectUser)) {
    $investigationLogs = $trafficService->getRequestHistoryByUser($inspectUser, 150);
}
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-rose-600 text-3xl">security</span>
                <span>مرکز پایش ترافیک، لاگ جامع تعاملات و دفع حملات DDoS</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">ثبت لحظه‌ای کلیه درخواست‌ها، تحلیل سفر کاربران، دفع حملات لایه ۷ مبتنی بر Cloudflare و اعمال خودکار محدودیت‌های IP</p>
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
                <span class="text-xs text-slate-500 font-bold block">کل درخواست‌ها (۲۴ ساعت)</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($trafficMetrics['requests_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">public</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">IPهای یکتا (۲۴ ساعت)</span>
                <span class="text-2xl font-black text-indigo-600"><?= number_format($trafficMetrics['unique_ips_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">crisis_alert</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">پویش‌های مشکوک و DDoS</span>
                <span class="text-2xl font-black text-amber-600"><?= number_format($trafficMetrics['suspicious_24h'] + $wafMetrics['waf_blocks_24h']) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">shield_lock</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">IPهای مسدود شده جاری</span>
                <span class="text-2xl font-black text-rose-600"><?= number_format($trafficMetrics['active_restrictions']) ?></span>
            </div>
        </div>
    </div>

    <!-- Manual Ban Modal Form (Toggled) -->
    <div id="manual-ban-modal" class="hidden bg-white p-5 rounded-2xl border border-rose-200 shadow-md">
        <h3 class="text-sm font-bold text-rose-800 mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">block</span>
            <span>مسدودسازی دستی و اعمال محدودیت زمانی بر IP</span>
        </h3>
        <form method="POST" action="security_logs.php" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ban_ip">

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">آدرس IP</label>
                <input type="text" name="ip_address" required placeholder="مثال: 198.51.100.42" dir="ltr" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">مدت زمان مسدودی</label>
                <select name="duration_minutes" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="15">۱۵ دقیقه (DDoS Flood Cooldown)</option>
                    <option value="60" selected>۱ ساعت (Scanner Probe)</option>
                    <option value="360">۶ ساعت</option>
                    <option value="1440">۲۴ ساعت (۱ روز)</option>
                    <option value="10080">۷ روز</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نوع تهدید</label>
                <select name="threat_type" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="manual">مسدودسازی دستی</option>
                    <option value="ddos_flood">حمله سیل‌آسا (DDoS Flood)</option>
                    <option value="scanner_probe">پویشگر آسیب‌پذیری</option>
                    <option value="brute_force">تلاش مکرر ورود ناموفق</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">دلیل مسدودسازی</label>
                <input type="text" name="reason" required placeholder="تلاش مکرر نفوذ یا ارسال اسپم" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <button type="submit" class="w-full h-10 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                    اعمال محدودیت IP
                </button>
            </div>
        </form>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
        <a href="security_logs.php?tab=stream" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'stream' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
            <span class="material-symbols-outlined text-base">stream</span>
            <span>جریان زنده تعاملات و درخواست‌ها (Live Requests)</span>
        </a>

        <?php if (!empty($inspectIp) || !empty($inspectUser)): ?>
        <a href="security_logs.php?tab=investigate&inspect_ip=<?= urlencode($inspectIp) ?>&inspect_user=<?= urlencode($inspectUser) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 bg-indigo-600 text-white shadow-sm">
            <span class="material-symbols-outlined text-base">person_search</span>
            <span>بررسی سفر رفتاری: <?= htmlspecialchars($inspectIp ?: "کاربر #{$inspectUser}") ?></span>
        </a>
        <?php endif; ?>

        <a href="security_logs.php?tab=banned" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'banned' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
            <span class="material-symbols-outlined text-base">do_not_disturb_on</span>
            <span>محدودیت‌های فعال و مسدودسازی زمانی (<?= count($bannedIps) ?>)</span>
        </a>

        <a href="security_logs.php?tab=waf" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'waf' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
            <span class="material-symbols-outlined text-base">gpp_maybe</span>
            <span>لاگ هشدارهای امنیتی فایروال WAF</span>
        </a>
    </div>

    <!-- TAB 1: Live Request Stream -->
    <?php if ($activeTab === 'stream'): ?>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm space-y-3 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sky-600">view_timeline</span>
                <h2 class="text-sm font-black text-slate-800">جریان زنده درخواست‌ها و کنش‌های کاربران (Universal Request Stream)</h2>
            </div>
            
            <!-- Filter by action -->
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500">فیلتر نوع کنش:</span>
                <a href="security_logs.php?tab=stream&action_type=all" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterAction === 'all' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">همه</a>
                <a href="security_logs.php?tab=stream&action_type=suspicious_probe" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterAction === 'suspicious_probe' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">پویش‌های مشکوک</a>
                <a href="security_logs.php?tab=stream&action_type=auth" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterAction === 'auth' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">احراز هویت</a>
                <a href="security_logs.php?tab=stream&action_type=checkout" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterAction === 'checkout' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">پرداخت و خرید</a>
                <a href="security_logs.php?tab=stream&action_type=admin_action" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterAction === 'admin_action' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">عملیات ادمین</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3">زمان</th>
                        <th class="p-3">کاربر</th>
                        <th class="p-3">آدرس IP (کلودفلر)</th>
                        <th class="p-3">متد</th>
                        <th class="p-3">مسیر درخواستی (URI)</th>
                        <th class="p-3">نوع کنش</th>
                        <th class="p-3">خلاصه پیلود</th>
                        <th class="p-3">Cloudflare Ray</th>
                        <th class="p-3">اقدام ممیزی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($recentInteractions)): ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">درخواستی در این بازه ثبت نشده است.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentInteractions as $req): 
                            $isSusp = !empty($req['is_suspicious']);
                            $actionColor = match($req['action_type']) {
                                'suspicious_probe' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'auth' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                'checkout' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'admin_action' => 'bg-amber-100 text-amber-800 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                            };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors <?= $isSusp ? 'bg-rose-50/40' : '' ?>">
                            <td class="p-3 text-slate-400 whitespace-nowrap font-mono text-[11px]"><?= htmlspecialchars($req['created_at']) ?></td>
                            <td class="p-3 whitespace-nowrap">
                                <?php if (!empty($req['user_id'])): ?>
                                    <a href="security_logs.php?tab=investigate&inspect_user=<?= (int)$req['user_id'] ?>" class="text-indigo-600 font-bold hover:underline" title="مشاهده تمام درخواست‌های این کاربر">
                                        <?= htmlspecialchars($req['user_name'] ?: "کاربر #{$req['user_id']}") ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400 text-[11px]">مهمان</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-800 whitespace-nowrap dir-ltr text-right">
                                <span><?= htmlspecialchars($req['ip_address']) ?></span>
                                <?php if (!empty($req['cf_country'])): ?>
                                    <span class="mr-1 px-1.5 py-0.5 rounded bg-slate-100 text-[10px] text-slate-600 font-normal"><?= htmlspecialchars($req['cf_country']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-black <?= $req['request_method'] === 'POST' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?>">
                                    <?= htmlspecialchars($req['request_method']) ?>
                                </span>
                            </td>
                            <td class="p-3 font-mono text-slate-700 whitespace-nowrap dir-ltr text-right max-w-xs truncate" title="<?= htmlspecialchars($req['request_uri']) ?>">
                                <?= htmlspecialchars($req['request_uri']) ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black border <?= $actionColor ?>">
                                    <?= htmlspecialchars($req['action_type']) ?>
                                </span>
                            </td>
                            <td class="p-3 max-w-xs truncate text-slate-500 font-mono text-[11px]" title="<?= htmlspecialchars($req['payload_summary'] ?? '') ?>">
                                <?= htmlspecialchars($req['payload_summary'] ?: '-') ?>
                            </td>
                            <td class="p-3 font-mono text-[10px] text-slate-400 whitespace-nowrap dir-ltr text-right">
                                <?= htmlspecialchars($req['cf_ray'] ?: '-') ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <a href="security_logs.php?tab=investigate&inspect_ip=<?= urlencode($req['ip_address']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] transition">
                                    <span class="material-symbols-outlined text-xs">manage_search</span>
                                    <span>سایر درخواست‌ها</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 2: User / IP Journey Investigator -->
    <?php if ($activeTab === 'investigate'): ?>
    <div class="bg-white rounded-2xl border border-indigo-200 overflow-hidden shadow-sm p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-100 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600 text-2xl">person_search</span>
                    <h2 class="text-base font-black text-slate-900">
                        بررسی سوابق رفتاری و سایر درخواست‌ها: 
                        <span class="font-mono text-indigo-600"><?= htmlspecialchars($inspectIp ?: "کاربر شناسه #{$inspectUser}") ?></span>
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">توالی زمانی تمام مسیرها، درخواست‌ها و داده‌های ارسال‌شده توسط این منبع در وب‌سایت</p>
            </div>

            <div class="flex items-center gap-2">
                <?php if (!empty($inspectIp)): ?>
                <form method="POST" action="security_logs.php" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="ban_ip">
                    <input type="hidden" name="ip_address" value="<?= htmlspecialchars($inspectIp) ?>">
                    <input type="hidden" name="duration_minutes" value="60">
                    <input type="hidden" name="threat_type" value="manual">
                    <input type="hidden" name="reason" value="مسدودسازی در بررسی رفتار مشکوک کاربر">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">block</span>
                        <span>مسدودسازی ۱ ساعته این IP</span>
                    </button>
                </form>
                <?php endif; ?>

                <a href="security_logs.php?tab=stream" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    بازگشت به جریان زنده
                </a>
            </div>
        </div>

        <!-- Chronological Request Trail Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-indigo-50/60 text-slate-600 font-bold border-b border-indigo-100">
                    <tr>
                        <th class="p-3">زمان دقیق</th>
                        <th class="p-3">متد</th>
                        <th class="p-3">مسیر کامل (URI)</th>
                        <th class="p-3">نوع عملیات</th>
                        <th class="p-3">داده‌های ارسالی (پیلود پالایش‌شده)</th>
                        <th class="p-3">Cloudflare Ray ID</th>
                        <th class="p-3">وضعیت ناهنجاری</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($investigationLogs)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">هیچ سابقه درخواستی برای این مشخصه یافت نشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($investigationLogs as $req): 
                            $isSusp = !empty($req['is_suspicious']);
                        ?>
                        <tr class="hover:bg-indigo-50/30 transition-colors <?= $isSusp ? 'bg-rose-50/50' : '' ?>">
                            <td class="p-3 text-slate-500 whitespace-nowrap font-mono text-[11px]"><?= htmlspecialchars($req['created_at']) ?></td>
                            <td class="p-3 whitespace-nowrap font-mono font-bold"><?= htmlspecialchars($req['request_method']) ?></td>
                            <td class="p-3 font-mono text-indigo-900 font-bold whitespace-nowrap dir-ltr text-right">
                                <?= htmlspecialchars($req['request_uri']) ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($req['action_type']) ?>
                                </span>
                            </td>
                            <td class="p-3 max-w-md truncate text-slate-600 font-mono text-[11px]" title="<?= htmlspecialchars($req['payload_summary'] ?? '') ?>">
                                <?= htmlspecialchars($req['payload_summary'] ?: 'بدون پارامتر') ?>
                            </td>
                            <td class="p-3 font-mono text-[10px] text-slate-400 whitespace-nowrap dir-ltr text-right">
                                <?= htmlspecialchars($req['cf_ray'] ?: '-') ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <?php if ($isSusp): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        <?= htmlspecialchars($req['suspicion_reason'] ?: 'مشکوک') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">عادی</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 3: Active Restrictions & DDoS Bans -->
    <?php if ($activeTab === 'banned'): ?>
    <div class="bg-white rounded-2xl border border-rose-200 overflow-hidden shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-rose-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600">shield_lock</span>
                <h2 class="text-sm font-black text-slate-900">آدرس‌های IP مسدود شده جاری بر اساس قوانین Cloudflare و فایروال</h2>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800"><?= count($bannedIps) ?> مورد مسدود</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-rose-50/50 text-slate-600 font-bold border-b border-rose-100">
                    <tr>
                        <th class="p-3">آدرس IP</th>
                        <th class="p-3">نوع تهدید (Threat Type)</th>
                        <th class="p-3">دلیل مسدودسازی</th>
                        <th class="p-3">Cloudflare Ray ID</th>
                        <th class="p-3">انقضای مسدودی</th>
                        <th class="p-3">زمان باقی‌مانده</th>
                        <th class="p-3">اقدام</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($bannedIps)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">هیچ آدرس IP در حال حاضر مسدود نمی‌باشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bannedIps as $b): 
                            $remainingSec = max(0, strtotime($b['banned_until']) - time());
                            $remainingMin = ceil($remainingSec / 60);
                        ?>
                        <tr class="hover:bg-rose-50/30 transition-colors">
                            <td class="p-3 font-mono font-black text-slate-900 dir-ltr text-right">
                                <?= htmlspecialchars($b['ip_address']) ?>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                    <?= htmlspecialchars($b['threat_type'] ?? 'manual') ?>
                                </span>
                            </td>
                            <td class="p-3 font-medium text-slate-800"><?= htmlspecialchars($b['reason']) ?></td>
                            <td class="p-3 font-mono text-[11px] text-slate-400 dir-ltr text-right"><?= htmlspecialchars($b['cf_ray'] ?: '-') ?></td>
                            <td class="p-3 font-mono text-[11px] text-slate-500"><?= htmlspecialchars($b['banned_until']) ?></td>
                            <td class="p-3 font-mono font-black text-rose-600">
                                <?= $remainingMin ?> دقیقه
                            </td>
                            <td class="p-3">
                                <form method="POST" action="security_logs.php" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="unban_ip">
                                    <input type="hidden" name="ip_address" value="<?= htmlspecialchars($b['ip_address']) ?>">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                                        رفع مسدودی
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 4: WAF Security Incidents -->
    <?php if ($activeTab === 'waf'): ?>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">gpp_maybe</span>
                <h2 class="text-sm font-black text-slate-900">هشدارهای امنیتی فایروال برنامه کاربردی (WAF Events)</h2>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-slate-500">فیلتر شدت:</span>
                <a href="security_logs.php?tab=waf&severity=all" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterSeverity === 'all' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">همه</a>
                <a href="security_logs.php?tab=waf&severity=critical" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterSeverity === 'critical' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">بحرانی</a>
                <a href="security_logs.php?tab=waf&severity=warning" class="px-2.5 py-1 rounded-lg text-xs font-bold <?= $filterSeverity === 'warning' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">هشدار</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3">زمان</th>
                        <th class="p-3">سطح شدت</th>
                        <th class="p-3">نوع رویداد</th>
                        <th class="p-3">آدرس IP</th>
                        <th class="p-3">مسیر درخواست</th>
                        <th class="p-3">توضیحات و خلاصه پیلود</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($wafLogs)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">هیچ رویدادی با این فیلتر ثبت نشده است.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($wafLogs as $log): 
                            $sevBadge = match($log['severity']) {
                                'critical', 'emergency' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'warning' => 'bg-amber-100 text-amber-800 border-amber-200',
                                default => 'bg-sky-100 text-sky-800 border-sky-200'
                            };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3 text-slate-400 whitespace-nowrap font-mono text-[11px]"><?= htmlspecialchars($log['created_at']) ?></td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black border <?= $sevBadge ?>">
                                    <?= htmlspecialchars(strtoupper($log['severity'])) ?>
                                </span>
                            </td>
                            <td class="p-3 font-bold whitespace-nowrap text-slate-900"><?= htmlspecialchars($log['event_type']) ?></td>
                            <td class="p-3 font-mono text-slate-800 whitespace-nowrap dir-ltr text-right">
                                <a href="security_logs.php?tab=investigate&inspect_ip=<?= urlencode($log['ip_address']) ?>" class="text-indigo-600 hover:underline">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </a>
                            </td>
                            <td class="p-3 font-mono text-slate-500 whitespace-nowrap dir-ltr text-right max-w-xs truncate"><?= htmlspecialchars($log['request_uri'] ?? '/') ?></td>
                            <td class="p-3 max-w-md truncate text-slate-600" title="<?= htmlspecialchars($log['payload_summary'] ?? '') ?>">
                                <?= htmlspecialchars($log['payload_summary'] ?? '') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/admin_footer.php'; ?>
