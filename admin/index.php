<?php
$currentPage = 'dashboard';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Fetch Macro Ecosystem Metrics
$orgCount = (int)$pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
$docCount = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$sellerCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
$totalClearedPayouts = (int)$pdo->query("SELECT COALESCE(SUM(balance_available_for_payout), 0) FROM seller_wallets")->fetchColumn();

// Platform Health & Incident Alerts
$openTicketsCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE mode = 'admin' AND status = 'open'")->fetchColumn();
$inTransitOrdersCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('shipped', 'handed_over', 'out_for_delivery') AND (post_tracking_code IS NOT NULL OR tracking_code IS NOT NULL)")->fetchColumn();
$lowRatingReviewsCount = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE rating <= 2")->fetchColumn();
$pendingVerificationsCount = 0;
try {
    $pendingVerificationsCount = (int)$pdo->query("SELECT COUNT(*) FROM role_applications WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {}

// 1. Multi-Role Support Tickets Stream (Recent 6 tickets with user role & preview)
$stmt = $pdo->prepare("
    SELECT t.id, t.status, t.created_at, t.updated_at, u.name, u.phone, u.role,
           COALESCE(
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1),
               'پیامی ثبت نشده است'
           ) as last_message,
           (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.mode = 'admin'
    ORDER BY (t.status = 'open') DESC, t.updated_at DESC
    LIMIT 6
");
$stmt->execute();
$recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Recent Quality Feedback & Sentiment Radar (Doctors & Products reviews)
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.phone as user_phone, u.role as user_role,
           CASE 
               WHEN r.target_type = 'doctor' THEN d.name
               WHEN r.target_type = 'product' THEN COALESCE(p.name, pm.name)
               ELSE 'نامشخص'
           END as target_name,
           CASE 
               WHEN r.target_type = 'doctor' THEN 'پزشک'
               ELSE 'کالا'
           END as target_type_label
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN doctors d ON r.target_type = 'doctor' AND r.target_id = d.id
    LEFT JOIN products p ON r.target_type = 'product' AND r.target_id = p.id
    LEFT JOIN pharmacy_medicines pm ON r.target_type = 'product' AND r.target_id = pm.id
    ORDER BY (r.rating <= 2) DESC, r.created_at DESC
    LIMIT 6
");
$stmt->execute();
$recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Recent Dispatched Parcels for Logistics Stream (with recipient home location and postal code)
$stmt = $pdo->prepare("
    SELECT o.id, o.post_tracking_code, o.tracking_code, o.carrier_name, o.status, o.total_amount,
           o.shipping_address, o.created_at,
           u.name as user_name, u.phone as user_phone, u.city as user_city, 
           u.postal_code as user_postal_code, u.address as user_home_address,
           (SELECT s.name FROM order_items oi JOIN users s ON oi.seller_id = s.id WHERE oi.order_id = o.id LIMIT 1) as seller_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentShipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 md:p-8 space-y-6 md:space-y-8 max-w-[1600px] mx-auto rtl text-right" dir="rtl">

    <!-- Top Platform Governance KPI Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Organizations -->
        <a href="organizations.php" class="bg-surface-container-lowest p-5 rounded-3xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between hover:border-indigo-400 transition-all group">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">مراکز درمانی و بیمارستان‌ها</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface group-hover:text-indigo-600 transition-colors"><?= number_format($orgCount) ?></span>
                    <span class="text-xs text-indigo-600 font-bold">مرکز</span>
                </div>
                <p class="text-[11px] text-slate-400">بیمارستان‌ها، کلینیک‌ها و درمانگاه‌ها</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">apartment</span>
            </div>
        </a>

        <!-- 2. Doctors -->
        <a href="doctors.php" class="bg-surface-container-lowest p-5 rounded-3xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between hover:border-blue-400 transition-all group">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">پزشکان و متخصصین سراسری</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface group-hover:text-blue-600 transition-colors"><?= number_format($docCount) ?></span>
                    <span class="text-xs text-blue-600 font-bold">پزشک</span>
                </div>
                <p class="text-[11px] text-slate-400">دامپزشکان و جراحان مجاز در پلتفرم</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
        </a>

        <!-- 3. Sellers & Petshops -->
        <a href="sellers.php" class="bg-surface-container-lowest p-5 rounded-3xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between hover:border-amber-400 transition-all group">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">فروشندگان مستقل و پت‌شاپ‌ها</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface group-hover:text-amber-600 transition-colors"><?= number_format($sellerCount) ?></span>
                    <span class="text-xs text-amber-600 font-bold">فروشگاه</span>
                </div>
                <p class="text-[11px] text-slate-400">تامین‌کنندگان کالا با مهلت تضمین ۷ روزه</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
        </a>

        <!-- 4. Paya Payouts & Commission -->
        <a href="payouts.php" class="bg-surface-container-lowest p-5 rounded-3xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between hover:border-emerald-400 transition-all group">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">موجودی آماده تسویه پایا</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-600 group-hover:text-emerald-700 transition-colors"><?= number_format($totalClearedPayouts) ?></span>
                    <span class="text-xs text-slate-400 font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-slate-400">محاسبه‌شده با کسر ۵٪ کارمزد پلتفرم</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
        </a>
    </div>

    <!-- Platform Macro Incident & Quality Alert Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Alert 1: Unresolved Tickets -->
        <a href="tickets.php" class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/80 hover:bg-rose-50 transition-all flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">support_agent</span>
                </div>
                <div>
                    <span class="text-xs text-rose-900 font-black block">تیکت‌های باز و بدون پاسخ</span>
                    <span class="text-[11px] text-rose-700"><?= $openTicketsCount ?> مورد نیازمند رسیدگی مدیر</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-rose-400 group-hover:-translate-x-1 transition-transform text-sm">arrow_back</span>
        </a>

        <!-- Alert 2: In-Transit Parcels -->
        <a href="orders.php" class="p-4 rounded-2xl bg-sky-50/60 border border-sky-200/80 hover:bg-sky-50 transition-all flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">local_shipping</span>
                </div>
                <div>
                    <span class="text-xs text-sky-900 font-black block">مرسولات پستی در مسیر مقصد</span>
                    <span class="text-[11px] text-sky-700"><?= $inTransitOrdersCount ?> بسته با بارکد ۲۴ رقمی</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-sky-400 group-hover:-translate-x-1 transition-transform text-sm">arrow_back</span>
        </a>

        <!-- Alert 3: Quality Complaints / Low Ratings -->
        <a href="reviews.php?rating=low" class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 hover:bg-amber-50 transition-all flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">warning</span>
                </div>
                <div>
                    <span class="text-xs text-amber-950 font-black block">نظرات ضعیف و شکایات (۱-۲ ستاره)</span>
                    <span class="text-[11px] text-amber-800"><?= $lowRatingReviewsCount ?> نظر مربوط به پزشک/کالا</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-amber-500 group-hover:-translate-x-1 transition-transform text-sm">arrow_back</span>
        </a>

        <!-- Alert 4: Pending Verifications -->
        <a href="verifications.php" class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200/80 hover:bg-indigo-50 transition-all flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">verified_user</span>
                </div>
                <div>
                    <span class="text-xs text-indigo-950 font-black block">احراز صلاحیت مدارک پزشکان</span>
                    <span class="text-[11px] text-indigo-700"><?= $pendingVerificationsCount ?> درخواست در انتظار تایید</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-indigo-400 group-hover:-translate-x-1 transition-transform text-sm">arrow_back</span>
        </a>
    </div>

    <!-- Main Split Layout: Center Governance Streams vs Right Quality & Fast Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left / Center 2 Columns: Multi-Role Support & Nationwide Logistics -->
        <div class="lg:col-span-2 space-y-8">

            <!-- 1. Multi-Role Support & Inquiries Stream -->
            <div class="bg-white rounded-3xl stat-card-shadow border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">support_agent</span>
                        </div>
                        <div>
                            <h2 class="font-black text-sm text-slate-900">مرکز تیکتینگ و پیام‌های تفکیک‌شده بر اساس نقش</h2>
                            <p class="text-[11px] text-slate-400">استعلام‌ها و درخواست‌های مراکز درمانی، فروشندگان و خریداران</p>
                        </div>
                    </div>
                    <a href="tickets.php" class="text-xs font-bold text-primary hover:text-secondary-container transition-colors flex items-center gap-1">
                        <span>مشاهده تمام تیکت‌ها</span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($recentTickets)): ?>
                        <div class="p-8 text-center text-slate-400 text-xs">هیچ تیکت فعالی ثبت نشده است.</div>
                    <?php else: ?>
                        <?php foreach ($recentTickets as $t): 
                            $r = $t['role'] ?? 'user';
                            $rBadge = match($r) {
                                'organization' => ['label' => 'مرکز درمانی', 'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'apartment'],
                                'seller'       => ['label' => 'فروشنده', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'storefront'],
                                'doctor'       => ['label' => 'پزشک', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'stethoscope'],
                                'pharmacist'   => ['label' => 'داروساز', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'prescriptions'],
                                default        => ['label' => 'خریدار', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'person']
                            };
                            $isOpen = ($t['status'] === 'open');
                        ?>
                            <div class="p-4 sm:p-5 hover:bg-slate-50/80 transition-colors flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl <?= $rBadge['bg'] ?> border flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-sm"><?= $rBadge['icon'] ?></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <strong class="text-xs font-black text-slate-900"><?= htmlspecialchars($t['name'] ?: 'کاربر ' . $t['phone']) ?></strong>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border <?= $rBadge['bg'] ?>">
                                                <?= $rBadge['label'] ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono" dir="ltr"><?= htmlspecialchars($t['phone']) ?></span>
                                        </div>
                                        <p class="text-xs text-slate-600 line-clamp-1 mt-1">
                                            <?= htmlspecialchars($t['last_message']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                                    <?php if ($isOpen): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                            نیازمند پاسخ
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">بسته شده</span>
                                    <?php endif; ?>

                                    <a href="tickets.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-700 text-xs font-bold transition-all flex items-center gap-1">
                                        <span>پاسخگویی</span>
                                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Nationwide Logistics Stream with Live Postex Tracking Trigger -->
            <div class="bg-white rounded-3xl stat-card-shadow border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">local_shipping</span>
                        </div>
                        <div>
                            <h2 class="font-black text-sm text-slate-900">سفارشات سراسری و رهگیری برخط وب‌سرویس پستکس</h2>
                            <p class="text-[11px] text-slate-400">پایش نشانی دقیق، کد پستی ۱۰ رقمی خریدار و استعلام وضعیت مرسوله</p>
                        </div>
                    </div>
                    <a href="orders.php" class="text-xs font-bold text-primary hover:text-sky-600 transition-colors flex items-center gap-1">
                        <span>مشاهده خط لوله سفارشات</span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($recentShipments)): ?>
                        <div class="p-8 text-center text-slate-400 text-xs">سفارشی اخیراً ثبت نشده است.</div>
                    <?php else: ?>
                        <?php foreach ($recentShipments as $ord): 
                            $bCode = $ord['post_tracking_code'] ?: ($ord['tracking_code'] ?: '');
                            $hasCode = !empty($bCode);
                            $pCode = $ord['user_postal_code'] ?: 'نامشخص';
                            $cText = $ord['user_city'] ?: 'نامشخص';
                            $addrText = $ord['shipping_address'] ?: ($ord['user_home_address'] ?: 'نشانی ثبت نشده');
                        ?>
                            <div class="p-4 sm:p-5 hover:bg-slate-50/80 transition-colors flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="space-y-1.5 min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black font-mono text-primary text-xs" dir="ltr">#ORD-<?= $ord['id'] ?></span>
                                        <span class="text-xs font-black text-slate-900"><?= htmlspecialchars($ord['user_name']) ?></span>
                                        <span class="text-[11px] text-slate-400 font-mono" dir="ltr">📞 <?= htmlspecialchars($ord['user_phone']) ?></span>
                                        <?php if (!empty($ord['seller_name'])): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                فروشنده: <?= htmlspecialchars($ord['seller_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Home Location & Postal Code -->
                                    <div class="text-[11px] text-slate-600 flex items-center gap-2 flex-wrap">
                                        <span class="text-slate-400">📍 شهر <?= htmlspecialchars($cText) ?> - <?= htmlspecialchars($addrText) ?></span>
                                        <span class="font-mono text-indigo-700 font-bold bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200 text-[10px]">
                                            کد پستی: <?= htmlspecialchars($pCode) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Live Tracking Trigger -->
                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <?php if ($hasCode): ?>
                                        <button type="button" 
                                                onclick="openDashboardPostexModal(<?= (int)$ord['id'] ?>, '<?= htmlspecialchars(addslashes($bCode)) ?>')"
                                                class="py-1.5 px-3 bg-sky-600 hover:bg-sky-700 active:scale-95 text-white rounded-xl text-xs font-black flex items-center gap-1.5 shadow-sm transition-all">
                                            <span class="material-symbols-outlined text-sm">search</span>
                                            <span>استعلام آنی پستکس</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-slate-100 text-slate-500">
                                            فاقد بارکد پستی
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right 1 Column: Quality Sentiment Radar & Fast Actions -->
        <div class="space-y-8">
            
            <!-- Quality & Sentiment Radar (Reviews Stream) -->
            <div class="bg-white rounded-3xl stat-card-shadow border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500">rate_review</span>
                        <h2 class="font-black text-sm text-slate-900">رادار کیفیت و نظرات اخیر</h2>
                    </div>
                    <a href="reviews.php" class="text-xs font-bold text-amber-700 hover:underline">
                        کنسول نظرات
                    </a>
                </div>

                <div class="divide-y divide-slate-100 p-2">
                    <?php if (empty($recentReviews)): ?>
                        <div class="p-6 text-center text-slate-400 text-xs">دیدگاهی ثبت نشده است.</div>
                    <?php else: ?>
                        <?php foreach ($recentReviews as $rev): 
                            $isLow = ($rev['rating'] <= 2);
                            $tColor = ($rev['target_type'] === 'doctor') ? 'text-blue-600 bg-blue-50' : 'text-amber-600 bg-amber-50';
                        ?>
                            <div class="p-3 rounded-2xl hover:bg-slate-50 transition-colors space-y-1.5 <?= $isLow ? 'bg-rose-50/30' : '' ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold <?= $tColor ?>">
                                            <?= htmlspecialchars($rev['target_type_label']) ?>
                                        </span>
                                        <strong class="text-xs font-bold text-slate-800 truncate max-w-[130px]"><?= htmlspecialchars($rev['target_name'] ?: '---') ?></strong>
                                    </div>
                                    <div class="flex items-center gap-1 text-xs font-black <?= $isLow ? 'text-rose-600' : 'text-amber-500' ?>">
                                        <span><?= (int)$rev['rating'] ?></span>
                                        <span>★</span>
                                    </div>
                                </div>

                                <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($rev['comment'] ?: 'فقط ثبت امتیاز ستاره‌ای') ?>
                                </p>

                                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1">
                                    <span><?= htmlspecialchars($rev['user_name']) ?></span>
                                    <span><?= date('m/d H:i', strtotime($rev['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                    <a href="reviews.php" class="text-xs font-bold text-primary hover:text-secondary-container">
                        مدیریت و تایید دیدگاه‌های عمومی ↗
                    </a>
                </div>
            </div>

            <!-- Platform Fast Governance Shortcuts -->
            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-3xl p-6 text-white space-y-4 shadow-xl">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400">policy</span>
                    <h3 class="font-black text-sm">میزکار حاکمیت و امور مالی پلتفرم</h3>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    نظارت بر تسویه‌های پایا بانک مرکزی، بررسی تالار ۵ مرکز برتر و تنظیمات سراسری پلتفرم آسنا.
                </p>

                <div class="space-y-2 pt-2">
                    <a href="payouts.php" class="w-full py-2.5 px-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-emerald-400">payments</span>
                            <span>صدور حواله‌های تسویه پایا (۵٪ کارمزد)</span>
                        </span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>

                    <a href="top_performers.php" class="w-full py-2.5 px-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-amber-400">military_tech</span>
                            <span>تالار برگزیدگان (Top 5 مراکز کشور)</span>
                        </span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>

                    <a href="security_logs.php" class="w-full py-2.5 px-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-sky-400">shield</span>
                            <span>مرکز عملیات امنیت و فایروال (SOC)</span>
                        </span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Live Postex Modal for Dashboard -->
<div id="dash-postex-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-100 text-right animate-in fade-in zoom-in-95 duration-200">
        <div class="p-4 border-b border-slate-100 bg-gradient-to-r from-sky-50 to-indigo-50 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-sky-600 text-xl">markunread_mailbox</span>
                <h3 class="font-black text-sm text-slate-900">استعلام برخط پستکس و پست پیشتاز</h3>
            </div>
            <button onclick="closeDashboardPostexModal()" class="text-slate-400 hover:text-slate-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-5 overflow-y-auto space-y-4 custom-scrollbar" id="dash-postex-modal-body">
            <div id="dash-postex-loading" class="py-8 flex flex-col items-center justify-center gap-2 text-slate-400">
                <div class="w-8 h-8 border-4 border-sky-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-bold">در حال دریافت رویدادهای پستی...</span>
            </div>
            <div id="dash-postex-result" class="hidden space-y-3"></div>
        </div>

        <div class="p-3.5 bg-slate-50 border-t border-slate-100 text-left">
            <button onclick="closeDashboardPostexModal()" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition-colors">
                بستن
            </button>
        </div>
    </div>
</div>

<script>
function openDashboardPostexModal(orderId, barcode) {
    const modal = document.getElementById('dash-postex-modal');
    const loading = document.getElementById('dash-postex-loading');
    const result = document.getElementById('dash-postex-result');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loading.classList.remove('hidden');
    result.classList.add('hidden');

    fetch(`../actions/postex_track_action.php?order_id=${encodeURIComponent(orderId)}&tracking_code=${encodeURIComponent(barcode)}`)
        .then(res => res.json())
        .then(data => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');

            if (data.success) {
                let timelineHtml = '';
                if (data.events && data.events.length > 0) {
                    data.events.forEach((e, idx) => {
                        const isLast = (idx === data.events.length - 1);
                        timelineHtml += `
                            <div class="relative group">
                                <div class="absolute -right-[27px] top-1 w-3 h-3 rounded-full ${isLast ? 'bg-emerald-500' : 'bg-sky-500'}"></div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <strong class="text-slate-900 font-bold text-xs">${e.status || e.description}</strong>
                                        <span class="text-[10px] text-slate-400 font-mono">${e.event_time || ''}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">${e.location || ''}</p>
                                </div>
                            </div>
                        `;
                    });
                }

                result.innerHTML = `
                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-slate-500">شماره سفارش:</span>
                            <strong class="font-mono">#ORD-${data.order_id}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">بارکد پستی:</span>
                            <code class="font-mono text-sky-700 font-black dir-ltr">${data.tracking_code}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">گیرنده:</span>
                            <strong>${data.recipient.name} (${data.recipient.phone})</strong>
                        </div>
                        <div class="pt-1 border-t border-slate-200">
                            <span class="text-[11px] text-slate-500 block">نشانی: ${data.recipient.city} - ${data.recipient.address}</span>
                            <span class="font-mono font-bold text-indigo-700 text-[10px] block mt-0.5">کد پستی: ${data.recipient.postal_code}</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl ${data.is_delivered ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-sky-50 text-sky-800 border border-sky-200'} text-xs font-bold flex items-center justify-between">
                        <span>وضعیت: ${data.current_status}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] ${data.is_delivered ? 'bg-emerald-500 text-white' : 'bg-sky-500 text-white'}">${data.is_delivered ? 'تحویل شده' : 'در مسیر'}</span>
                    </div>

                    <div class="pt-2">
                        <h4 class="font-bold text-xs text-slate-800 mb-3">مراحل توزیع پستی:</h4>
                        <div class="relative pr-5 border-r-2 border-sky-200 space-y-3.5 text-xs">
                            ${timelineHtml}
                        </div>
                    </div>
                `;
            } else {
                result.innerHTML = `<div class="p-4 bg-rose-50 text-rose-700 rounded-xl text-xs font-bold">${data.message || 'خطا در استعلام'}</div>`;
            }
        })
        .catch(() => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');
            result.innerHTML = `<div class="p-4 bg-rose-50 text-rose-700 rounded-xl text-xs font-bold">خطا در ارتباط با وب‌سرویس پستکس.</div>`;
        });
}

function closeDashboardPostexModal() {
    const modal = document.getElementById('dash-postex-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
