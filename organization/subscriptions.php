<?php
require_once 'includes/organization_header.php';

$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'reschedule') {
        $subId   = (int)($_POST['subscription_id'] ?? 0);
        $newDate = trim($_POST['new_date'] ?? '');
        if ($subId > 0 && !empty($newDate)) {
            $up = $pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?");
            if ($up->execute([$newDate, $subId])) {
                $message = 'تاریخ نوبت تحویل بعدی بسته اشتراکی با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'toggle_status') {
        $subId  = (int)($_POST['subscription_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if ($subId > 0 && in_array($status, ['active', 'cancelled', 'ended'])) {
            $up = $pdo->prepare("UPDATE user_subscriptions SET status = ? WHERE id = ?");
            if ($up->execute([$status, $subId])) {
                $message = 'وضعیت اشتراک بیمار با موفقیت تغییر یافت.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'create_plan') {
        $name = trim($_POST['plan_name'] ?? '');
        $interval = (int)($_POST['interval_months'] ?? 1);
        $discount = (int)($_POST['discount_percent'] ?? 10);

        if (!empty($name) && $interval > 0) {
            $ins = $pdo->prepare("INSERT INTO autoship_plans (name, interval_months, discount_percent, created_at) VALUES (?, ?, ?, NOW())");
            if ($ins->execute([$name, $interval, $discount])) {
                $message = "پلن اشتراکی جدید «{$name}» با {$discount}٪ تخفیف برای کلینیک ایجاد شد.";
                $messageType = 'success';
            }
        }
    }
}

// Fetch user subscriptions
$stmt = $pdo->query("
    SELECT s.*, u.name as user_name, u.phone as user_phone, u.city as user_city,
           (SELECT name FROM user_pets WHERE user_id = s.user_id LIMIT 1) as pet_name,
           (SELECT type FROM user_pets WHERE user_id = s.user_id LIMIT 1) as pet_type
    FROM user_subscriptions s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.id DESC
");
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$totalActive = 0;
$totalMRR = 0;
$upcomingThisMonth = 0;
$curDate = date('Y-m-d');
$monthEnd = date('Y-m-t');

foreach ($subscriptions as $s) {
    if ($s['status'] === 'active') {
        $totalActive++;
        $totalMRR += (int)$s['amount'];
        if (!empty($s['next_delivery_date']) && $s['next_delivery_date'] >= $curDate && $s['next_delivery_date'] <= $monthEnd) {
            $upcomingThisMonth++;
        }
    }
}

// Fetch available autoship care plans
$plans = $pdo->query("SELECT * FROM autoship_plans ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-indigo-600 text-3xl">event_repeat</span>
                <span>سفارشات دوره‌ای و اشتراک‌های Autoship مراجعین</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت سفارشات تکرارشونده غذاهای رژیمی و درمانی، بسته‌های دوره‌ای انگل‌زدایی و تمدید داروهای بیماران مزمن</p>
        </div>

        <button onclick="document.getElementById('new-plan-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>تعریف پلن Autoship جدید</span>
        </button>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">مشترکین فعال کلینیک</span>
                <span class="material-symbols-outlined text-indigo-600">how_to_reg</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2 font-mono">
                <?= $totalActive ?> <span class="text-xs font-normal text-slate-400">بیمار فعال</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">سفارشات منظم غذا و دارو بدون نیاز به ثبت مجدد</p>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">درآمد تکرارشونده ماهیانه (MRR)</span>
                <span class="material-symbols-outlined text-emerald-500">payments</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2 font-mono">
                <?= number_format($totalMRR) ?> <span class="text-xs font-normal text-slate-400">تومان / ماه</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">جریان نقدی پایدار از تمدید خودکار اشتراک‌ها</p>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">تحویل‌های پیش‌رو این ماه</span>
                <span class="material-symbols-outlined text-amber-500">local_shipping</span>
            </div>
            <div class="text-2xl font-black text-amber-800 mt-2 font-mono">
                <?= $upcomingThisMonth ?> <span class="text-xs font-normal text-slate-400">نوبت ارسال</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">بسته‌های آماده بسته‌بندی در داروخانه کلینیک</p>
        </div>
    </div>

    <!-- New Care Plan Modal -->
    <div id="new-plan-modal" class="hidden bg-white p-6 rounded-3xl border border-indigo-200 shadow-xl space-y-4 animate-fade-in">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">stars</span>
                <h3 class="text-sm font-black text-slate-800">تعریف پلن تکرارشونده و اشتراک مراقبتی جدید</h3>
            </div>
            <button type="button" onclick="document.getElementById('new-plan-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="subscriptions.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_plan">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان پلن مراقبتی *</label>
                <input type="text" name="plan_name" required placeholder="مثال: اشتراک ماهانه غذای درمانی کلیوی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">دوره تکرار تحویل *</label>
                <select name="interval_months" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="1">هر ۱ ماه یکبار (ماهانه)</option>
                    <option value="2">هر ۲ ماه یکبار</option>
                    <option value="3">هر ۳ ماه یکبار (فصلی)</option>
                    <option value="6">هر ۶ ماه یکبار</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">درصد تخفیف Autoship *</label>
                <input type="number" name="discount_percent" value="10" min="0" max="50" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono text-center">
            </div>

            <div class="sm:col-span-3 flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('new-plan-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                    ثبت پلن اشتراکی
                </button>
            </div>
        </form>
    </div>

    <!-- Active Subscriptions Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">receipt_long</span>
                <h3 class="text-sm font-black text-slate-800">لیست مشترکین و سفارش‌های دوره‌ای بیماران</h3>
            </div>
            <span class="text-xs font-bold text-slate-400"><?= count($subscriptions) ?> اشتراک ثبت شده</span>
        </div>

        <?php if (empty($subscriptions)): ?>
            <div class="text-center py-10 text-slate-400 text-xs font-bold">
                هنوز بیماری در اشتراک‌های دوره‌ای داروخانه و کلینیک عضو نشده است.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">کد اشتراک</th>
                            <th class="px-4 py-3">نام بیمار / سرپرست</th>
                            <th class="px-4 py-3">پلن اشتراکی</th>
                            <th class="px-4 py-3">مبلغ دوره</th>
                            <th class="px-4 py-3">تکرار تحویل</th>
                            <th class="px-4 py-3">موعد ارسال بعدی</th>
                            <th class="px-4 py-3">وضعیت</th>
                            <th class="px-4 py-3 text-center">مدیریت نوبت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($subscriptions as $sub): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-mono font-black text-slate-900">#SUB-<?= $sub['id'] ?></td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-900 block"><?= htmlspecialchars($sub['pet_name'] ?? 'پت') ?></span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($sub['user_name']) ?> (<?= htmlspecialchars($sub['user_phone']) ?>)</span>
                                </td>
                                <td class="px-4 py-3 font-bold text-indigo-700"><?= htmlspecialchars($sub['plan_name']) ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900"><?= number_format($sub['amount']) ?> تومان</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <?= ($sub['delivery_frequency'] === '2_weeks') ? 'هر ۲ هفته' : 'ماهانه' ?>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900">
                                    <?= htmlspecialchars($sub['next_delivery_date'] ?? 'نامشخص') ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($sub['status'] === 'active'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">فعال</span>
                                    <?php elseif ($sub['status'] === 'ended'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">پایان یافته</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">لغو شده</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($sub['status'] === 'active'): ?>
                                        <form method="POST" action="subscriptions.php" class="inline-flex items-center gap-1.5 m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reschedule">
                                            <input type="hidden" name="subscription_id" value="<?= (int)$sub['id'] ?>">
                                            <input type="date" name="new_date" min="<?= date('Y-m-d') ?>" value="<?= $sub['next_delivery_date'] ?? date('Y-m-d') ?>" class="px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] transition-colors">
                                                تغییر
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
