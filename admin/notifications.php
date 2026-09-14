<?php
$currentPage = 'notifications';
require_once 'includes/admin_header.php';
require_once '../includes/PushNotificationService.php';

$pushService = new PushNotificationService($pdo);
$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/functions.php';
    if (function_exists('csrf_verify')) {
        try { csrf_verify(); } catch (Exception $e) {}
    }

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'broadcast') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'purchase_offer');
        $audience = trim($_POST['target_audience'] ?? 'all');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $sendSms = !empty($_POST['send_sms']);
        $targetUserId = !empty($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : null;

        if (empty($title) || empty($body)) {
            $error = 'عنوان اعلان و متن پیام الزامی است.';
        } else {
            $res = $pushService->broadcastCampaign($title, $body, $type, $audience, $linkUrl, $imageUrl, $sendSms, $targetUserId);
            if (!empty($res['success'])) {
                $smsInfo = $sendSms ? " (همچنین به {$res['sms_sent_count']} شماره پیامک ارسال شد)" : "";
                $message = "اعلان با موفقیت برای جامعه هدف ({$audience}) ارسال گردید.{$smsInfo}";
            } else {
                $error = 'خطا در ارسال اعلان: ' . ($res['error'] ?? 'نامشخص');
            }
        }
    } elseif ($postAction === 'delete_notification') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE id = ?");
            $stmt->execute([$notifId]);
            $message = 'اعلان مورد نظر با موفقیت حذف گردید.';
        }
    } elseif ($postAction === 'add_social_proof') {
        $userName = trim($_POST['user_name'] ?? '');
        $city = trim($_POST['city'] ?? 'تهران');
        $eventType = trim($_POST['event_type'] ?? 'purchase');
        $itemTitle = trim($_POST['item_title'] ?? '');
        $itemLink = trim($_POST['item_link'] ?? 'shop.php');
        $minutesAgo = (int)($_POST['minutes_ago'] ?? 5);

        if (!empty($userName) && !empty($itemTitle)) {
            $stmt = $pdo->prepare("INSERT INTO live_social_proof_events (user_name, city, event_type, item_title, item_link, minutes_ago, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$userName, $city, $eventType, $itemTitle, $itemLink, $minutesAgo]);
            $message = 'رویداد تعاملی جدید به لیست سوشال‌پروف اضافه گردید.';
        } else {
            $error = 'نام کاربر و عنوان کالا/خدمت الزامی است.';
        }
    } elseif ($postAction === 'toggle_social_proof') {
        $spId = (int)($_POST['event_id'] ?? 0);
        if ($spId > 0) {
            $stmt = $pdo->prepare("UPDATE live_social_proof_events SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$spId]);
            $message = 'وضعیت نمایش رویداد تعاملی تغییر یافت.';
        }
    } elseif ($postAction === 'delete_social_proof') {
        $spId = (int)($_POST['event_id'] ?? 0);
        if ($spId > 0) {
            $stmt = $pdo->prepare("DELETE FROM live_social_proof_events WHERE id = ?");
            $stmt->execute([$spId]);
            $message = 'رویداد تعاملی با موفقیت حذف شد.';
        }
    }
}

// Fetch Metrics
$totalNotifications = (int)$pdo->query("SELECT COUNT(*) FROM user_notifications")->fetchColumn();
$unreadNotifications = (int)$pdo->query("SELECT COUNT(*) FROM user_notifications WHERE is_read = 0")->fetchColumn();
$pwaSubscribers = (int)$pdo->query("SELECT COUNT(*) FROM pwa_subscriptions WHERE is_active = 1")->fetchColumn();
$socialProofCount = (int)$pdo->query("SELECT COUNT(*) FROM live_social_proof_events WHERE is_active = 1")->fetchColumn();

// Fetch Notifications Log
$stmt = $pdo->query("
    SELECT n.*, u.name as user_name, u.phone as user_phone
    FROM user_notifications n
    LEFT JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 30
");
$notificationsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Social Proof Events
$spStmt = $pdo->query("SELECT * FROM live_social_proof_events ORDER BY id DESC LIMIT 20");
$socialProofEvents = $spStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 lg:p-10 max-w-[1440px] mx-auto space-y-8">

    <!-- Header & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5 mb-1">
                <div class="w-10 h-10 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">campaign</span>
                </div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900 dark:text-white">مرکز اعلان‌های تعاملی و وب‌پوش PWA</h1>
            </div>
            <p class="text-xs lg:text-sm text-slate-500">ارسال نوتیفیکیشن‌های خرید، تخفیف‌های شگفت‌انگیز و مدیریت پاپ‌آپ‌های زنده اپلیکیشن</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>سیستم وب‌پوش و سرویس‌ورکر فعال</span>
            </span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><span class="material-symbols-outlined text-sm">close</span></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 text-rose-800 border border-rose-200 text-xs font-bold flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600 text-lg">error</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800"><span class="material-symbols-outlined text-sm">close</span></button>
        </div>
    <?php endif; ?>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        <!-- 1. Total Notifications -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">notifications</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($totalNotifications) ?></div>
                <div class="text-xs text-slate-400 font-medium mt-0.5">کل اعلان‌های ثبت شده</div>
            </div>
        </div>

        <!-- 2. PWA Subscribers -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">install_mobile</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($pwaSubscribers) ?> دستگاه</div>
                <div class="text-xs text-slate-400 font-medium mt-0.5">مشترکین اپلیکیشن PWA</div>
            </div>
        </div>

        <!-- 3. Social Proof Events -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">dynamic_feed</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($socialProofCount) ?> رویداد</div>
                <div class="text-xs text-slate-400 font-medium mt-0.5">پاپ‌آپ‌های زنده خرید فعال</div>
            </div>
        </div>

        <!-- 4. Unread Messages -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">mark_email_unread</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($unreadNotifications) ?></div>
                <div class="text-xs text-slate-400 font-medium mt-0.5">اعلان‌های در انتظار مشاهده</div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Notification Composer (Right) + Live Preview (Left) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Composer Form (8 cols) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 sm:p-8">
            <div class="flex items-center gap-3 pb-5 mb-6 border-b border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-2xl text-secondary-container">send</span>
                <div>
                    <h2 class="text-base font-black text-slate-800 dark:text-white">ارسال نوتیفیکیشن و پیشنهاد خرید جدید</h2>
                    <p class="text-xs text-slate-400">پیام شما به صورت آنی در زنگوله بالای صفحه و در قالب وب‌پوش PWA به کاربران نمایش داده می‌شود.</p>
                </div>
            </div>

            <form method="POST" action="notifications.php" class="space-y-5" id="campaignForm">
                <input type="hidden" name="action" value="broadcast">
                <?php if (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <?php endif; ?>

                <!-- Row 1: Target Audience & Notification Type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            جامعه هدف (Audience)
                        </label>
                        <select name="target_audience" id="targetAudienceSelect" onchange="toggleTargetUserField(this.value)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white outline-none focus:border-primary transition-all">
                            <option value="all">همه کاربران عادی (وب و اپلیکیشن)</option>
                            <option value="pwa_only">فقط کاربران دارای اپلیکیشن PWA</option>
                            <option value="buyers">خریداران فعال (دارای سابقه سفارش)</option>
                            <option value="user">کاربر خاص (تک‌کاربر بر اساس شناسه)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            نوع اعلان (Type)
                        </label>
                        <select name="type" id="notificationTypeSelect" onchange="updatePreviewIcon(this.value)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white outline-none focus:border-primary transition-all">
                            <option value="purchase_offer">⚡ پیشنهاد خرید و تخفیف شگفت‌انگیز</option>
                            <option value="order_status">📦 وضعیت سفارش و لجستیک</option>
                            <option value="pwa_welcome">🎉 هدیه نصب و وفاداری PWA</option>
                            <option value="pet_health">🩺 یادآوری سلامت و واکسیناسیون</option>
                        </select>
                    </div>
                </div>

                <!-- Single User ID Input (Hidden by default) -->
                <div id="targetUserIdContainer" class="hidden">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        شناسه کاربر هدف (User ID)
                    </label>
                    <input type="number" name="target_user_id" placeholder="مثال: 12" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white outline-none focus:border-primary transition-all">
                </div>

                <!-- Notification Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        عنوان اعلان (Notification Title)
                    </label>
                    <input type="text" name="title" id="inputTitle" oninput="syncPreview()" placeholder="مثال: تخفیف ۳۰٪ غذای خشک گربه رویال کنین" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white outline-none focus:border-primary transition-all">
                </div>

                <!-- Notification Message Body -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        متن اعلان و توضیحات (Message Body)
                    </label>
                    <textarea name="message" id="inputMessage" oninput="syncPreview()" rows="3" placeholder="مثال: فقط تا امشب، با خرید هر ۲ عدد کنسرو شایر، یک بسته تشویقی گربه به صورت رایگان دریافت کنید." required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-xs font-medium text-slate-800 dark:text-white outline-none focus:border-primary transition-all leading-relaxed"></textarea>
                </div>

                <!-- Row 2: Link URL & Optional Image -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            لینک مقصد دکمه (Action URL)
                        </label>
                        <input type="text" name="link_url" id="inputLink" oninput="syncPreview()" placeholder="shop.php یا product_details.php?id=..." value="shop.php" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-medium text-slate-800 dark:text-white outline-none focus:border-primary transition-all text-left" dir="ltr">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            آدرس تصویر شاخص (اختیاری)
                        </label>
                        <input type="text" name="image_url" placeholder="assets/images/..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-xs font-medium text-slate-800 dark:text-white outline-none focus:border-primary transition-all text-left" dir="ltr">
                    </div>
                </div>

                <!-- Melipayamak SMS Fallback Option -->
                <div class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-600 text-2xl">sms</span>
                        <div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">ارسال پیامک همزمان (SMS Fallback)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">در صورت فعال‌بودن، پیام از طریق سامانه ملی‌پیامک نیز به شماره همراه کاربران ارسال خواهد شد.</div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="send_sms" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary-container"></div>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-secondary-container hover:bg-secondary text-white font-black text-sm py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-lg">campaign</span>
                        <span>ارسال و انتشار سراسری اعلان</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Preview Canvas (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-gradient-to-b from-slate-900 to-slate-800 text-white rounded-3xl p-6 shadow-xl border border-slate-800 relative overflow-hidden">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-400 text-lg">preview</span>
                        <span class="text-xs font-bold uppercase tracking-wider">پیش‌نمایش زنده در PWA</span>
                    </div>
                    <span class="text-[10px] text-slate-400">اندروید / iOS</span>
                </div>

                <!-- Mockup Notification Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 space-y-3 shadow-inner">
                    <div class="flex items-center gap-2.5">
                        <div id="prevIconBox" class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm">
                            <span id="prevIcon" class="material-symbols-outlined text-lg">local_fire_department</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] text-white/60 font-medium">اپلیکیشن پیشرو آسنا • هم‌اکنون</div>
                            <h4 id="prevTitle" class="text-xs font-black text-white truncate">تخفیف ۳۰٪ غذای خشک رویال کنین</h4>
                        </div>
                    </div>

                    <p id="prevBody" class="text-[11px] text-white/80 leading-relaxed">
                        فقط تا امشب، با خرید هر ۲ عدد کنسرو شایر، یک بسته تشویقی گربه به صورت رایگان دریافت کنید.
                    </p>

                    <div class="pt-2 flex items-center justify-between border-t border-white/10 text-[10px]">
                        <span class="text-amber-400 font-bold flex items-center gap-1">
                            <span>مشاهده و خرید</span>
                            <span class="material-symbols-outlined text-xs">arrow_back</span>
                        </span>
                        <span id="prevAudienceBadge" class="px-2 py-0.5 rounded-full bg-white/10 text-white/70">همه کاربران</span>
                    </div>
                </div>

                <!-- In-App Toast Mockup Preview -->
                <div class="mt-6 pt-4 border-t border-white/10">
                    <div class="text-[11px] text-slate-400 mb-2 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-teal-400">notifications_active</span>
                        <span>پیش‌نمایش اعلان درون‌برنامه‌ای (In-App Toast)</span>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-slate-200 text-slate-800 dark:text-white flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">shopping_bag</span>
                        </div>
                        <div class="flex-1 min-w-0 text-right">
                            <div class="text-[11px] font-black truncate" id="toastPrevUser">علی ر. از تهران</div>
                            <div class="text-[10px] text-slate-500 truncate" id="toastPrevItem">کنسرو شایر خرید کرد • ۳ دقیقه پیش</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fast Quick-Add Social Proof Event Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-100 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">add_shopping_cart</span>
                    <h3 class="text-xs font-black text-slate-800 dark:text-white">افزودن سریع رویداد خرید زنده (سوشال‌پروف)</h3>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    با افزودن رویدادهای زنده، بازدیدکنندگان در بدو ورود به اپلیکیشن خریدهای لحظه‌ای کاربران دیگر را مشاهده کرده و نرخ تبدیل افزایش می‌یابد.
                </p>

                <form method="POST" action="notifications.php" class="space-y-3">
                    <input type="hidden" name="action" value="add_social_proof">
                    <?php if (function_exists('csrf_token')): ?>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="user_name" placeholder="نام کاربر (مثال: نیما ر.)" required class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white outline-none">
                        <input type="text" name="city" placeholder="شهر (مثال: شیراز)" value="تهران" required class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white outline-none">
                    </div>

                    <input type="text" name="item_title" placeholder="عنوان کالا یا خدمت (مثال: غذای خشک گربه فیت ۳۲)" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white outline-none">

                    <div class="grid grid-cols-2 gap-2">
                        <select name="event_type" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white outline-none">
                            <option value="purchase">🛒 خرید پت‌شاپ</option>
                            <option value="booking">🩺 رزرو ویزیت</option>
                            <option value="prescription">💊 نسخه داروخانه</option>
                            <option value="autoship">🔄 تحویل خودکار</option>
                        </select>
                        <input type="number" name="minutes_ago" placeholder="دقیقه پیش" value="5" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-white outline-none">
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-container text-white text-xs font-bold py-2.5 rounded-xl transition-all shadow-sm">
                        + ثبت رویداد جدید
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom Tables: 1. Sent Broadcast History & 2. Social Proof Stream -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Table 1: Broadcast History -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">history</span>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white">تاریخچه اعلان‌های ارسالی اخیر</h3>
                </div>
                <span class="text-xs text-slate-400 font-bold"><?= count($notificationsList) ?> اعلان</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar flex-1 max-h-[420px]">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <th class="pb-3 font-bold">عنوان و نوع</th>
                            <th class="pb-3 font-bold">جامعه هدف</th>
                            <th class="pb-3 font-bold">تاریخ ارسال</th>
                            <th class="pb-3 font-bold text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($notificationsList)): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">هیچ اعلانی ثبت نشده است.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notificationsList as $notif): ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3">
                                        <div class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($notif['title']) ?></div>
                                        <div class="text-[10px] text-slate-400 line-clamp-1 mt-0.5"><?= htmlspecialchars($notif['message']) ?></div>
                                    </td>
                                    <td class="py-3">
                                        <?php if ($notif['target_audience'] === 'pwa_only'): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-secondary-container/10 text-secondary-container text-[10px] font-bold">فقط PWA</span>
                                        <?php elseif ($notif['target_audience'] === 'buyers'): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold">خریداران</span>
                                        <?php elseif ($notif['target_audience'] === 'user'): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 text-[10px] font-bold">کاربر #<?= $notif['user_id'] ?></span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">همگانی</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 text-[10px] text-slate-400 whitespace-nowrap">
                                        <?= date('Y/m/d H:i', strtotime($notif['created_at'])) ?>
                                    </td>
                                    <td class="py-3 text-center">
                                        <form method="POST" action="notifications.php" onsubmit="return confirm('آیا از حذف این اعلان اطمینان دارید؟');" class="inline">
                                            <input type="hidden" name="action" value="delete_notification">
                                            <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                            <?php if (function_exists('csrf_token')): ?>
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 transition-colors" title="حذف">
                                                <span class="material-symbols-outlined text-base">delete</span>
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

        <!-- Table 2: Active Social Proof Ticker Events -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-500 text-xl">dynamic_feed</span>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white">رویدادهای زنده تعاملی (سوشال‌پروف)</h3>
                </div>
                <span class="text-xs text-slate-400 font-bold"><?= count($socialProofEvents) ?> رویداد</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar flex-1 max-h-[420px]">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <th class="pb-3 font-bold">کاربر و شهر</th>
                            <th class="pb-3 font-bold">رویداد / کالا</th>
                            <th class="pb-3 font-bold text-center">وضعیت</th>
                            <th class="pb-3 font-bold text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($socialProofEvents)): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">هیچ رویداد تعاملی ثبت نشده است.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($socialProofEvents as $sp): ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3">
                                        <div class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($sp['user_name']) ?></div>
                                        <div class="text-[10px] text-slate-400"><?= htmlspecialchars($sp['city']) ?></div>
                                    </td>
                                    <td class="py-3">
                                        <div class="font-bold text-slate-700 dark:text-slate-200 line-clamp-1"><?= htmlspecialchars($sp['item_title']) ?></div>
                                        <div class="text-[10px] text-emerald-600 font-medium"><?= $sp['minutes_ago'] ?> دقیقه پیش</div>
                                    </td>
                                    <td class="py-3 text-center">
                                        <form method="POST" action="notifications.php" class="inline">
                                            <input type="hidden" name="action" value="toggle_social_proof">
                                            <input type="hidden" name="event_id" value="<?= $sp['id'] ?>">
                                            <?php if (function_exists('csrf_token')): ?>
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-all <?= $sp['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' ?>">
                                                <?= $sp['is_active'] ? 'فعال ✓' : 'غیرفعال' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-3 text-center">
                                        <form method="POST" action="notifications.php" onsubmit="return confirm('حذف رویداد؟');" class="inline">
                                            <input type="hidden" name="action" value="delete_social_proof">
                                            <input type="hidden" name="event_id" value="<?= $sp['id'] ?>">
                                            <?php if (function_exists('csrf_token')): ?>
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 transition-colors" title="حذف">
                                                <span class="material-symbols-outlined text-base">delete</span>
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
    </div>
</div>

<script>
function syncPreview() {
    const title = document.getElementById('inputTitle').value || 'عنوان اعلان';
    const message = document.getElementById('inputMessage').value || 'متن اعلان و توضیحات تخفیف...';
    const audience = document.getElementById('targetAudienceSelect').options[document.getElementById('targetAudienceSelect').selectedIndex].text;

    document.getElementById('prevTitle').textContent = title;
    document.getElementById('prevBody').textContent = message;
    document.getElementById('prevAudienceBadge').textContent = audience;
}

function toggleTargetUserField(val) {
    const container = document.getElementById('targetUserIdContainer');
    if (val === 'user') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
    syncPreview();
}

function updatePreviewIcon(type) {
    const iconEl = document.getElementById('prevIcon');
    const boxEl = document.getElementById('prevIconBox');

    if (type === 'purchase_offer') {
        iconEl.textContent = 'local_fire_department';
        boxEl.className = 'w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm';
    } else if (type === 'order_status') {
        iconEl.textContent = 'local_shipping';
        boxEl.className = 'w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm';
    } else if (type === 'pwa_welcome') {
        iconEl.textContent = 'celebration';
        boxEl.className = 'w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-sm';
    } else {
        iconEl.textContent = 'health_and_safety';
        boxEl.className = 'w-8 h-8 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-sm';
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
