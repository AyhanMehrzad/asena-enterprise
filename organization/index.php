<?php
require_once 'includes/organization_header.php';
require_once dirname(__DIR__) . '/includes/LeaderboardService.php';

$message = '';
$messageType = '';
$orgId = (int)$currentOrg['id'];
$leaderboardService = App::leaderboard();
$isTop5 = $leaderboardService->isTopOrganization($orgId);
$top5Orgs = $leaderboardService->getTop5OrganizationIds();
$orgRank = array_search($orgId, $top5Orgs, true);
$orgRank = ($orgRank !== false) ? ($orgRank + 1) : null;

// 6 Standard Organization Types Map
$orgTypeMap = [
    'hospital'         => ['title' => 'بیمارستان فوق‌تخصصی دامپزشکی', 'icon' => 'local_hospital', 'color' => 'sky', 'badge' => 'bg-sky-50 text-sky-700 border-sky-200'],
    'clinic'           => ['title' => 'کلینیک تخصصی و جراحی', 'icon' => 'medical_services', 'color' => 'indigo', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
    'pharmacy'         => ['title' => 'داروخانه مرجع دامپزشکی', 'icon' => 'medication', 'color' => 'teal', 'badge' => 'bg-teal-50 text-teal-700 border-teal-200'],
    'shelter_charity'  => ['title' => 'پناهگاه و خیریه حمایتی حیوانات', 'icon' => 'volunteer_activism', 'color' => 'amber', 'badge' => 'bg-amber-50 text-amber-800 border-amber-200'],
    'emergency_center' => ['title' => 'مرکز اورژانس شبانه‌روزی ۲۴ ساعته', 'icon' => 'e911_emergency', 'color' => 'rose', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200'],
    'diagnostic_lab'   => ['title' => 'آزمایشگاه و تصویربرداری تشخیصی', 'icon' => 'biotech', 'color' => 'cyan', 'badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200'],
];

// Handle Formal Ticket Request for Organization Type Change
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_type_change') {
    csrf_verify();

    $reqType = trim($_POST['requested_type'] ?? '');
    $justification = trim($_POST['justification'] ?? '');
    $newLicense = trim($_POST['new_license_number'] ?? '');

    if (empty($reqType) || !isset($orgTypeMap[$reqType])) {
        $message = 'لطفاً یک رسته مقصد معتبر را انتخاب فرمایید.';
        $messageType = 'error';
    } elseif ($reqType === ($currentOrg['type'] ?? '')) {
        $message = 'رسته انتخابی با رسته فعلی مرکز یکسان است.';
        $messageType = 'error';
    } elseif (empty($justification) || mb_strlen($justification) < 10) {
        $message = 'لطفاً شرح کامل دلایل و مستندات درخواست تغییر رسته را وارد نمایید (حداقل ۱۰ کاراکتر).';
        $messageType = 'error';
    } else {
        $currTypeLabel = $orgTypeMap[$currentOrg['type']]['title'] ?? $currentOrg['type'];
        $newTypeLabel = $orgTypeMap[$reqType]['title'];

        // 1. Create support ticket to admin
        $insTicket = $pdo->prepare("
            INSERT INTO tickets (user_id, mode, status, created_at, updated_at)
            VALUES (?, 'admin', 'open', NOW(), NOW())
        ");
        $insTicket->execute([$currentUser['id']]);
        $ticketId = (int)$pdo->lastInsertId();

        // 2. Insert structured ticket message
        $ticketBody = "📋 [درخواست رسمی تغییر رسته مرکز درمانی]\n\n"
                    . "نام مرکز: " . $currentOrg['name'] . " (شناسه: #" . $currentOrg['id'] . ")\n"
                    . "رسته فعلی تاییدشده: " . $currTypeLabel . " (" . $currentOrg['type'] . ")\n"
                    . "رسته جدید درخواستی: " . $newTypeLabel . " (" . $reqType . ")\n"
                    . "شماره پروانه استنادی: " . ($newLicense ?: ($currentOrg['license_number'] ?? 'طبق پروانه قبلی')) . "\n\n"
                    . "دلایل و توضیحات متقاضی:\n" . $justification;

        $insMsg = $pdo->prepare("
            INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at)
            VALUES (?, 'user', ?, NOW())
        ");
        $insMsg->execute([$ticketId, $ticketBody]);

        $message = 'تیکت رسمی درخواست تغییر رسته با شماره #' . $ticketId . ' با موفقیت برای مدیریت کلان ارسال شد. نتیجه بررسی از بخش پشتیبانی به اطلاع شما خواهد رسید.';
        $messageType = 'success';
    }
}

// Handle Facility Details & Card Customization Update (Type is IMMUTABLE and cannot be updated directly)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    csrf_verify();

    $name           = trim($_POST['name'] ?? '');
    $managerName    = trim($_POST['manager_name'] ?? '');
    $licenseNumber  = trim($_POST['license_number'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
    $operatingHours = trim($_POST['operating_hours'] ?? '');
    $is247          = isset($_POST['is_24_7']) ? 1 : 0;
    $city           = trim($_POST['city'] ?? 'تهران');
    $address        = trim($_POST['address'] ?? '');
    $instagram      = trim($_POST['instagram'] ?? '');
    $website        = trim($_POST['website'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $facilities     = trim($_POST['facilities'] ?? '');

    // Handle Banner Upload
    $bannerUrl = $currentOrg['banner_url'] ?? '';
    if (!empty($_FILES['banner_file']['name']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $fileMime = @mime_content_type($_FILES['banner_file']['tmp_name']);
        if (in_array($fileMime, $allowed, true) && $_FILES['banner_file']['size'] <= 5 * 1024 * 1024) {
            $ext = strtolower(pathinfo($_FILES['banner_file']['name'], PATHINFO_EXTENSION));
            $bannerName = 'org_banner_' . $orgId . '_' . time() . '.' . $ext;
            $dest = dirname(__DIR__) . '/uploads/organizations/' . $bannerName;
            if (move_uploaded_file($_FILES['banner_file']['tmp_name'], $dest)) {
                $bannerUrl = 'uploads/organizations/' . $bannerName;
            }
        }
    } elseif (!empty($_POST['preset_banner'])) {
        $bannerUrl = trim($_POST['preset_banner']);
    }

    // Handle Logo Upload
    $logoUrl = $currentOrg['logo_url'] ?? '';
    if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $fileMime = @mime_content_type($_FILES['logo_file']['tmp_name']);
        if ((in_array($fileMime, $allowed, true) || str_ends_with($_FILES['logo_file']['name'], '.svg')) && $_FILES['logo_file']['size'] <= 3 * 1024 * 1024) {
            $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $logoName = 'org_logo_' . $orgId . '_' . time() . '.' . $ext;
            $dest = dirname(__DIR__) . '/uploads/organizations/' . $logoName;
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                $logoUrl = 'uploads/organizations/' . $logoName;
            }
        }
    }

    if (!empty($name)) {
        // NOTE: organizations.type is intentionally excluded here to prevent direct tampering
        $up = $pdo->prepare("
            UPDATE organizations 
            SET name = ?, manager_name = ?, license_number = ?, phone = ?, emergency_phone = ?, operating_hours = ?,
                is_24_7 = ?, city = ?, address = ?, instagram = ?, website = ?, description = ?, facilities = ?,
                banner_url = ?, logo_url = ?
            WHERE id = ?
        ");
        if ($up->execute([$name, $managerName, $licenseNumber, $phone, $emergencyPhone, $operatingHours, $is247, $city, $address, $instagram, $website, $description, $facilities, $bannerUrl, $logoUrl, $orgId])) {
            $message = 'کارت و مشخصات مرکز درمانی با موفقیت به‌روزرسانی گردید.';
            $messageType = 'success';
            // Refresh record
            $currentOrg = $pdo->query("SELECT * FROM organizations WHERE id = {$orgId}")->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = 'خطا در ذخیره‌سازی اطلاعات کارت.';
            $messageType = 'error';
        }
    }
}

// Check for active open ticket regarding organization type change
$pendingTicketStmt = $pdo->prepare("
    SELECT t.id, t.created_at, tm.message 
    FROM tickets t
    JOIN ticket_messages tm ON t.id = tm.ticket_id
    WHERE t.user_id = ? AND t.status = 'open' AND tm.message LIKE '%[درخواست رسمی تغییر رسته مرکز درمانی]%'
    ORDER BY t.id DESC LIMIT 1
");
$pendingTicketStmt->execute([$currentUser['id']]);
$activeChangeTicket = $pendingTicketStmt->fetch(PDO::FETCH_ASSOC);

// Compute facility statistics
$docCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_doctors WHERE organization_id = {$orgId}")->fetchColumn();
$invCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_inventory WHERE organization_id = {$orgId}")->fetchColumn();
$rating = (float)($currentOrg['rating'] ?? 5.0);
$reviewCount = (int)($currentOrg['review_count'] ?? 0);
$activeFacilities = !empty($currentOrg['facilities']) ? array_filter(array_map('trim', explode(',', $currentOrg['facilities']))) : [];
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header Banner & Badges -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sky-600 text-3xl">local_hospital</span>
                    <span>استودیو شخصی‌سازی کارت و پیشخوان مرکز</span>
                </h1>
                <?php if ($isTop5): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-gradient-to-r from-amber-500 to-yellow-400 text-slate-900 shadow-md shadow-amber-500/20 animate-pulse">
                        <span class="material-symbols-outlined text-sm">military_tech</span>
                        <span>رتبه #<?= $orgRank ?> کشور (Top 5)</span>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500 mt-1">ویرایش کارت نمایش در دایرکتوری عمومی، آپلود بنر و لوگو، سوئیچ اختیاری اورژانس ۲۴ ساعته و تگ‌های خدمات</p>
        </div>
        
        <div class="flex items-center gap-2 shrink-0">
            <a href="../organization_profile.php?slug=<?= urlencode($currentOrg['slug']) ?>" target="_blank" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>مشاهده عمومی کارت</span>
            </a>
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

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500">کادر و پزشکان همکار</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-900"><?= $docCount ?></span>
                    <span class="text-xs text-sky-600 font-bold">متخصص فعال</span>
                </div>
                <p class="text-[11px] text-slate-400">پزشک، جراح و گرومر</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500">امتیاز مراجعین</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-amber-500"><?= number_format($rating, 1) ?></span>
                    <span class="text-xs text-amber-600 font-bold">★ (<?= $reviewCount ?> نظر)</span>
                </div>
                <p class="text-[11px] text-slate-400">محاسبه در رتبه‌بندی Top 5</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">star</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500">بخش اورژانس ۲۴ ساعته</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-black <?= !empty($currentOrg['is_24_7']) ? 'text-rose-600' : 'text-slate-600' ?>">
                        <?= !empty($currentOrg['is_24_7']) ? 'فعال شبانه‌روزی' : 'غیرفعال (عادی)' ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400"><?= !empty($currentOrg['is_24_7']) ? 'دارای نشان قرمز پالس‌دار' : 'ساعات کاری مندرج' ?></p>
            </div>
            <div class="w-12 h-12 rounded-2xl <?= !empty($currentOrg['is_24_7']) ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">e911_emergency</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500">وضعیت رتبه‌بندی کشوری</span>
                <div class="flex items-baseline gap-2">
                    <?php if ($isTop5): ?>
                        <span class="text-xl font-black text-amber-600">🏆 رتبه #<?= $orgRank ?></span>
                    <?php else: ?>
                        <span class="text-lg font-black text-slate-700">عادی</span>
                    <?php endif; ?>
                </div>
                <p class="text-[11px] text-slate-400"><?= $isTop5 ? 'دارای نشان اختصاصی Top 5' : 'در حال ارزیابی عملکرد' ?></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">trophy</span>
            </div>
        </div>
    </div>

    <!-- MAIN STUDIO: 2-COLUMN LAYOUT (FORM + LIVE CARD PREVIEW) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- COLUMN 1: EDIT FORM (7 COLS) -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sky-600">tune</span>
                    <span>تنظیمات و شخصی‌سازی کارت</span>
                </h2>
                <span class="text-xs text-slate-400">تغییرات بلافاصله در پیش‌نمایش قابل مشاهده است</span>
            </div>

            <form method="POST" action="index.php" enctype="multipart/form-data" id="cardEditForm" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="facilities" id="facilitiesHiddenInput" value="<?= htmlspecialchars($currentOrg['facilities'] ?? '') ?>">

                <!-- 1. Visual Branding: Banner & Logo -->
                <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-200/80">
                    <h3 class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sky-600 text-lg">image</span>
                        <span>تصاویر بنر هدر و لوگوی مرکز</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Banner Uploader -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">تصویر بنر هدر کارت</label>
                            <div class="border-2 border-dashed border-slate-300 hover:border-sky-500 rounded-2xl p-4 text-center cursor-pointer transition-colors bg-white relative group">
                                <input type="file" name="banner_file" id="bannerFileInput" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10">
                                <span class="material-symbols-outlined text-3xl text-slate-400 group-hover:text-sky-600 mb-1">add_photo_alternate</span>
                                <p class="text-xs font-bold text-slate-700">آپلود بنر جدید</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">حداکثر ۵ مگابایت (JPG/PNG/WebP)</p>
                            </div>
                            <?php if (!empty($currentOrg['banner_url'])): ?>
                                <p class="text-[11px] text-emerald-600 font-bold mt-1.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                    <span>بنر اختصاصی فعال است</span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Logo Uploader -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">لوگوی اختصاصی مرکز</label>
                            <div class="border-2 border-dashed border-slate-300 hover:border-sky-500 rounded-2xl p-4 text-center cursor-pointer transition-colors bg-white relative group">
                                <input type="file" name="logo_file" id="logoFileInput" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10">
                                <span class="material-symbols-outlined text-3xl text-slate-400 group-hover:text-sky-600 mb-1">badge</span>
                                <p class="text-xs font-bold text-slate-700">آپلود لوگوی مرکز</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">ابعاد مربع یا گرد (حداکثر ۳ مگابایت)</p>
                            </div>
                            <?php if (!empty($currentOrg['logo_url'])): ?>
                                <p class="text-[11px] text-emerald-600 font-bold mt-1.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                    <span>لوگوی مرکز تنظیم شده است</span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Emergency 24/7 Switch (OPTIONAL TOGGLE) -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border border-rose-200 flex items-center justify-between">
                    <div class="space-y-0.5">
                        <span class="text-xs font-black text-rose-900 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-rose-600">e911_emergency</span>
                            <span>بخش اورژانس ۲۴ ساعته شبانه‌روزی (اختیاری)</span>
                        </span>
                        <p class="text-[11px] text-slate-500">
                            با فعال‌سازی این گزینه، بج قرمز پالس‌دار «اورژانس ۲۴ ساعته» روی کارت دایرکتوری درج خواهد شد.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mr-3">
                        <input type="checkbox" name="is_24_7" id="is247Toggle" value="1" <?= !empty($currentOrg['is_24_7']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-12 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                    </label>
                </div>

                <!-- 3. Smart Facilities / Service Tags Selector -->
                <div class="space-y-3 bg-slate-50 p-5 rounded-2xl border border-slate-200/80">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-slate-800 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sky-600 text-lg">local_offer</span>
                            <span>تگ‌ها و امکانات تخصصی مرکز</span>
                        </label>
                        <span class="text-[11px] text-slate-400">جهت نمایش تگ‌های آبی روی کارت</span>
                    </div>

                    <!-- Active Tags Display (Chips) -->
                    <div id="activeTagsContainer" class="flex flex-wrap gap-1.5 min-h-[36px] p-2.5 bg-white rounded-xl border border-slate-200">
                        <?php foreach ($activeFacilities as $fac): ?>
                            <span class="tag-chip inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                <span><?= htmlspecialchars($fac) ?></span>
                                <button type="button" onclick="removeTag('<?= htmlspecialchars($fac, ENT_QUOTES) ?>')" class="text-sky-400 hover:text-rose-600 font-bold text-xs">✕</button>
                            </span>
                        <?php endforeach; ?>
                        <?php if (empty($activeFacilities)): ?>
                            <span id="noTagsPlaceholder" class="text-xs text-slate-400 py-0.5">هنوز تگی انتخاب نشده است. از تگ‌های پیشنهادی زیر کلیک کنید:</span>
                        <?php endif; ?>
                    </div>

                    <!-- Custom Tag Input -->
                    <div class="flex items-center gap-2">
                        <input type="text" id="customTagInput" placeholder="تایپ تگ جدید و فشردن اینتر..." class="flex-1 h-10 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                        <button type="button" onclick="addCustomTag()" class="h-10 px-4 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shrink-0 transition-colors">
                            + افزودن تگ
                        </button>
                    </div>

                    <!-- Offered Predefined Suggestions -->
                    <div>
                        <span class="text-[11px] text-slate-500 font-bold block mb-1.5">تگ‌های پیشنهادی پرکاربرد (کلیک جهت افزودن):</span>
                        <div class="flex flex-wrap gap-1.5">
                            <?php
                            $suggestedTags = [
                                'کلینیک جراحی و عقیم‌سازی',
                                'بخش قرنطینه و واکسیناسیون',
                                'آزمایشگاه خون و پاتولوژی',
                                'سونوگرافی و رادیولوژی دیجیتال',
                                'دندانپزشکی و جرم‌گیری اولتراسونیک',
                                'بستری و ICU حیوانات',
                                'آرایشگاه و گرومینگ تخصصی',
                                'پت‌شاپ اختصاصی ملزومات',
                                'آمبولانس امدادی ۲۴ ساعته',
                                'پانسیون و مهد حیوانات خانگی',
                                'حیاط‌های بازی و توانبخشی'
                            ];
                            foreach ($suggestedTags as $stag):
                            ?>
                                <button type="button" onclick="addTag('<?= htmlspecialchars($stag, ENT_QUOTES) ?>')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-sky-50 text-slate-600 hover:text-sky-700 border border-slate-200 transition-colors">
                                    + <?= htmlspecialchars($stag) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 4. Basic Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نام رسمی بیمارستان / کلینیک *</label>
                        <input type="text" name="name" id="nameInput" required value="<?= htmlspecialchars($currentOrg['name']) ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs font-bold">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">رسته حقوقی و تخصصی مرکز *</label>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/80">
                                <span class="material-symbols-outlined text-xs">lock</span>
                                <span>غیرقابل تغییر مستقیم</span>
                            </span>
                        </div>
                        
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5">
                                <?php 
                                    $cType = $currentOrg['type'] ?? 'clinic';
                                    $cInfo = $orgTypeMap[$cType] ?? ['title' => 'مرکز درمانی', 'icon' => 'local_hospital', 'color' => 'slate'];
                                ?>
                                <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-sky-600 shadow-sm shrink-0">
                                    <span class="material-symbols-outlined text-xl"><?= $cInfo['icon'] ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-black text-slate-900 block"><?= htmlspecialchars($cInfo['title']) ?></span>
                                    <span class="text-[10px] text-slate-400">ثبت‌شده در هنگام عضویت و احراز پروانه</span>
                                </div>
                            </div>
                            
                            <button type="button" onclick="openTypeChangeModal()" class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-lg text-xs font-bold border border-sky-200 flex items-center gap-1.5 transition-colors shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-sm">mail</span>
                                <span>درخواست تغییر رسته (تیکت)</span>
                            </button>
                        </div>
                        
                        <?php if (!empty($activeChangeTicket)): ?>
                            <div class="mt-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-amber-600 text-sm">schedule</span>
                                    <span>تیکت درخواست تغییر رسته (شماره #<?= $activeChangeTicket['id'] ?>) در صف بررسی مدیریت است.</span>
                                </div>
                                <a href="../chat.php?ticket_id=<?= $activeChangeTicket['id'] ?>" class="text-[11px] font-bold text-sky-700 underline shrink-0">مشاهده تیکت</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نام مدیر / مسئول فنی</label>
                        <input type="text" name="manager_name" id="managerInput" value="<?= htmlspecialchars($currentOrg['manager_name'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره پروانه نظام دامپزشکی</label>
                        <input type="text" name="license_number" id="licenseInput" value="<?= htmlspecialchars($currentOrg['license_number'] ?? '') ?>" placeholder="مثال: پروانه رسمی ۱۴۰۲-۹۸۷۶" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن تماس *</label>
                        <input type="text" name="phone" id="phoneInput" dir="ltr" value="<?= htmlspecialchars($currentOrg['phone'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs text-left font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">خط ویژه اضطراری</label>
                        <input type="text" name="emergency_phone" dir="ltr" value="<?= htmlspecialchars($currentOrg['emergency_phone'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs text-left">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">ساعات پذیرش</label>
                        <input type="text" name="operating_hours" id="hoursInput" value="<?= htmlspecialchars($currentOrg['operating_hours'] ?? 'همه روزه ۸:۰۰ الی ۲۲:۰۰') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">شهر *</label>
                        <input type="text" name="city" id="cityInput" required value="<?= htmlspecialchars($currentOrg['city'] ?? 'تهران') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">اینستاگرام</label>
                        <input type="text" name="instagram" dir="ltr" value="<?= htmlspecialchars($currentOrg['instagram'] ?? '') ?>" placeholder="@username" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس دقیق مرکز *</label>
                    <textarea name="address" id="addressInput" rows="2" required class="w-full p-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs leading-relaxed"><?= htmlspecialchars($currentOrg['address'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">توضیحات کوتاه و رسالت مرکز (نمایش در کارت دایرکتوری)</label>
                    <textarea name="description" id="descInput" rows="3" class="w-full p-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs leading-relaxed"><?= htmlspecialchars($currentOrg['description'] ?? '') ?></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-8 py-3.5 bg-gradient-to-r from-sky-600 to-primary hover:from-sky-700 hover:to-indigo-800 text-white rounded-2xl text-xs font-bold shadow-lg shadow-sky-600/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>ذخیره نهایی کارت و تنظیمات</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- COLUMN 2: LIVE INTERACTIVE CARD PREVIEW (5 COLS - STICKY) -->
        <div class="lg:col-span-5 lg:sticky lg:top-20 space-y-4">
            
            <div class="bg-gradient-to-r from-sky-900 to-indigo-950 p-4 rounded-3xl text-white shadow-md">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400">preview</span>
                    <h3 class="text-xs font-black">پیش‌نمایش زنده کارت (Live Directory Preview)</h3>
                </div>
                <p class="text-[11px] text-slate-300 mt-1">این کارت دقیقاً به همین شکل در دایرکتوری اصلی و نتایج جستجوی مراجعین نمایش داده خواهد شد:</p>
            </div>

            <!-- EXACT PREVIEW CARD MATCHING THE SCREENSHOT -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden transition-all flex flex-col justify-between group relative">
                
                <div>
                    <!-- Header Banner -->
                    <div class="h-40 relative p-4 flex items-start justify-between">
                        <!-- Background with overflow-hidden for rounded top corners -->
                        <div id="previewBanner" class="absolute inset-0 rounded-t-3xl overflow-hidden bg-gradient-to-br from-slate-800 via-sky-950 to-indigo-950 bg-cover bg-center" style="<?= !empty($currentOrg['banner_url']) ? "background-image: url('../" . htmlspecialchars($currentOrg['banner_url']) . "');" : '' ?>">
                            <div class="absolute inset-0 opacity-25 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px] bg-black/40"></div>
                        </div>
                        
                        <!-- Badges Left -->
                        <div class="relative z-10 flex flex-wrap items-center gap-1.5">
                            <span id="previewTypeBadge" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/95 backdrop-blur-md text-slate-800 shadow-sm border border-white/20">
                                <?= match($currentOrg['type'] ?? 'clinic') {
                                    'hospital' => 'بیمارستان فوق‌تخصصی',
                                    'shelter_charity' => 'پناهگاه و نقاهتگاه حمایتی',
                                    'pharmacy' => 'داروخانه مرجع دامپزشکی',
                                    default => 'کلینیک تخصصی و جراحی'
                                } ?>
                            </span>

                            <span id="previewLicenseBadge" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/30 text-sky-200 border border-sky-400/30 <?= !empty($currentOrg['license_number']) ? '' : 'hidden' ?>">
                                پروانه رسمی
                            </span>

                            <?php if ($isTop5): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-amber-400 to-yellow-300 text-slate-900 border border-amber-300 shadow-md">
                                    🏆 ۵ مرکز برتر
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Badges Right: 24/7 or Status -->
                        <div class="relative z-10 flex flex-col items-end gap-1.5">
                            <span id="preview247Badge" class="<?= !empty($currentOrg['is_24_7']) ? '' : 'hidden' ?> inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-lg animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                <span>اورژانس ۲۴ ساعته</span>
                            </span>
                            <span id="previewOpenBadge" class="<?= empty($currentOrg['is_24_7']) ? '' : 'hidden' ?> inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/90 text-white backdrop-blur-md">
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                <span>الان باز است</span>
                            </span>
                        </div>

                        <!-- Logo Overlay (Floating OVER both banner and white card body) -->
                        <div class="absolute -bottom-7 right-6 w-16 h-16 rounded-2xl bg-white shadow-xl border-2 border-white flex items-center justify-center overflow-hidden z-30 p-1.5">
                            <img id="previewLogoImg" src="<?= !empty($currentOrg['logo_url']) ? '../' . htmlspecialchars($currentOrg['logo_url']) : '../assets/images/logo.png' ?>" alt="Logo" class="w-full h-full object-contain">
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6 pt-10 space-y-4 relative z-10">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h2 id="previewName" class="text-base font-black text-slate-900 line-clamp-1">
                                    <?= htmlspecialchars($currentOrg['name']) ?>
                                </h2>
                                <div class="flex items-center gap-1 text-amber-500 text-xs font-black shrink-0 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                    <span class="material-symbols-outlined text-sm">star</span>
                                    <span><?= number_format($rating, 1) ?></span>
                                    <span class="text-[10px] text-slate-400 font-normal">(<?= $reviewCount ?>)</span>
                                </div>
                            </div>
                            
                            <span id="previewManager" class="text-[11px] text-slate-500 font-medium block mt-0.5 <?= !empty($currentOrg['manager_name']) ? '' : 'hidden' ?>">
                                مدیریت: <?= htmlspecialchars($currentOrg['manager_name'] ?? '') ?>
                            </span>

                            <p id="previewDesc" class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($currentOrg['description'] ?? 'ارائه کلیه خدمات درمانی، جراحی، واکسیناسیون و مراقبت‌های بالینی حیوانات خانگی.') ?>
                            </p>
                        </div>

                        <!-- Facilities Quick Tags -->
                        <div id="previewFacilities" class="flex flex-wrap gap-1.5 pt-1">
                            <?php foreach (array_slice($activeFacilities, 0, 3) as $fac): ?>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
                                    <?= htmlspecialchars($fac) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($activeFacilities) > 3): ?>
                                <span class="px-1.5 py-0.5 rounded-lg text-[9px] font-bold bg-sky-50 text-sky-700">
                                    +<?= count($activeFacilities) - 3 ?> دیگر
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Meta Info Strip -->
                        <div class="space-y-2 text-xs text-slate-600 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-slate-400 shrink-0">location_on</span>
                                <span id="previewAddress" class="truncate font-medium text-slate-700">
                                    <?= htmlspecialchars($currentOrg['city']) ?>، <?= htmlspecialchars($currentOrg['address'] ?? '') ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-slate-400 shrink-0">call</span>
                                    <span id="previewPhone" class="dir-ltr font-bold text-slate-800">
                                        <?= htmlspecialchars($currentOrg['phone'] ?? '') ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-lg text-[10px] font-bold">
                                    <span><?= $docCount ?> پزشک</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="material-symbols-outlined text-base text-slate-400 shrink-0">schedule</span>
                                <span id="previewHours" class="truncate">
                                    پذیرش: <?= htmlspecialchars($currentOrg['operating_hours'] ?? 'همه روزه') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Buttons -->
                <div class="p-4 pt-0">
                    <div class="flex items-center gap-2">
                        <a href="tel:<?= htmlspecialchars($currentOrg['phone'] ?? '') ?>" class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition-colors shrink-0">
                            <span class="material-symbols-outlined text-lg">call</span>
                        </a>
                        <span class="flex-1 h-11 bg-slate-100 text-slate-700 font-bold text-xs rounded-2xl flex items-center justify-center gap-1.5 shadow-sm">
                            <span>پروفایل و نوبت‌دهی</span>
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                        </span>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- Real-time Live Preview JavaScript Controller -->
<script>
let activeTags = <?= json_encode($activeFacilities, JSON_UNESCAPED_UNICODE) ?>;

function updateFacilitiesView() {
    const container = document.getElementById('activeTagsContainer');
    const previewContainer = document.getElementById('previewFacilities');
    const hiddenInput = document.getElementById('facilitiesHiddenInput');
    
    hiddenInput.value = activeTags.join(',');

    // Update Form Chips
    if (activeTags.length === 0) {
        container.innerHTML = '<span id="noTagsPlaceholder" class="text-xs text-slate-400 py-0.5">هنوز تگی انتخاب نشده است. از تگ‌های پیشنهادی زیر کلیک کنید:</span>';
    } else {
        container.innerHTML = activeTags.map(tag => `
            <span class="tag-chip inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200 animate-fadeIn">
                <span>${escapeHtml(tag)}</span>
                <button type="button" onclick="removeTag('${escapeHtml(tag)}')" class="text-sky-400 hover:text-rose-600 font-bold text-xs">✕</button>
            </span>
        `).join('');
    }

    // Update Live Preview Card Facilities
    const slice3 = activeTags.slice(0, 3);
    const extraCount = activeTags.length - 3;
    let previewHtml = slice3.map(tag => `
        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
            ${escapeHtml(tag)}
        </span>
    `).join('');

    if (extraCount > 0) {
        previewHtml += `
            <span class="px-1.5 py-0.5 rounded-lg text-[9px] font-bold bg-sky-50 text-sky-700">
                +${extraCount} دیگر
            </span>
        `;
    }
    previewContainer.innerHTML = previewHtml;
}

function addTag(tag) {
    tag = tag.trim();
    if (!tag) return;
    if (!activeTags.includes(tag)) {
        activeTags.push(tag);
        updateFacilitiesView();
    }
}

function removeTag(tag) {
    activeTags = activeTags.filter(t => t !== tag);
    updateFacilitiesView();
}

function addCustomTag() {
    const inp = document.getElementById('customTagInput');
    const val = inp.value.trim();
    if (val) {
        addTag(val);
        inp.value = '';
    }
}

document.getElementById('customTagInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCustomTag();
    }
});

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Live Card Preview Listeners
document.getElementById('nameInput').addEventListener('input', function() {
    document.getElementById('previewName').innerText = this.value || 'نام مرکز درمانی';
});

document.getElementById('managerInput').addEventListener('input', function() {
    const el = document.getElementById('previewManager');
    if (this.value.trim()) {
        el.innerText = 'مدیریت: ' + this.value;
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
});

document.getElementById('licenseInput').addEventListener('input', function() {
    const el = document.getElementById('previewLicenseBadge');
    if (this.value.trim()) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
});

document.getElementById('phoneInput').addEventListener('input', function() {
    document.getElementById('previewPhone').innerText = this.value || '۰۲۱-۸۸۰۰۰۰۰۰';
});

document.getElementById('hoursInput').addEventListener('input', function() {
    document.getElementById('previewHours').innerText = 'پذیرش: ' + (this.value || 'همه روزه');
});

document.getElementById('cityInput').addEventListener('input', updateAddress);
document.getElementById('addressInput').addEventListener('input', updateAddress);

function updateAddress() {
    const city = document.getElementById('cityInput').value || 'تهران';
    const addr = document.getElementById('addressInput').value || '';
    document.getElementById('previewAddress').innerText = city + '، ' + addr;
}

document.getElementById('descInput').addEventListener('input', function() {
    document.getElementById('previewDesc').innerText = this.value || 'ارائه کلیه خدمات درمانی، جراحی و واکسیناسیون...';
});

// 24/7 Toggle Event
document.getElementById('is247Toggle').addEventListener('change', function() {
    const b247 = document.getElementById('preview247Badge');
    const bOpen = document.getElementById('previewOpenBadge');
    if (this.checked) {
        b247.classList.remove('hidden');
        bOpen.classList.add('hidden');
    } else {
        b247.classList.add('hidden');
        bOpen.classList.remove('hidden');
    }
});

// Banner File Live Preview
document.getElementById('bannerFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('previewBanner').style.backgroundImage = `url('${evt.target.result}')`;
        };
        reader.readAsDataURL(file);
    }
});

// Logo File Live Preview
document.getElementById('logoFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('previewLogoImg').src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

function openTypeChangeModal() {
    const modal = document.getElementById('typeChangeModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeTypeChangeModal() {
    const modal = document.getElementById('typeChangeModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
</script>

<!-- ================= MODAL: REQUEST TYPE CHANGE TICKET ================= -->
<div id="typeChangeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto border border-slate-100 animate-scale-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-2xl">assignment_turned_in</span>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base">درخواست رسمی تغییر رسته مرکز</h3>
                    <p class="text-[11px] text-slate-500 font-medium">ارسال مستقیم تیکت به مدیریت کلان همراه با شماره پروانه و مستندات</p>
                </div>
            </div>
            <button type="button" onclick="closeTypeChangeModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="p-6 space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_type_change">

            <!-- Information Alert -->
            <div class="p-4 rounded-2xl bg-sky-50/80 border border-sky-200 text-sky-950 text-xs leading-relaxed flex items-start gap-3">
                <span class="material-symbols-outlined text-sky-600 text-lg shrink-0 mt-0.5">verified_user</span>
                <div>
                    <p class="font-black mb-1">الزامات قانونی و احراز صلاحیت نظام دامپزشکی:</p>
                    <p class="text-sky-800 text-[11px] leading-relaxed">
                        با توجه به تفاوت در حدود صلاحیت، تعرفه‌ها و تعاریف پروانه‌ای (بیمارستان، مرکز اورژانس شبانه‌روزی، کلینیک و داروخانه)، هرگونه تغییر رسته فعالیت منوط به ارائه شماره پروانه جدید و تایید کارشناسان ارشد سامانه آسنا می‌باشد.
                    </p>
                </div>
            </div>

            <!-- Current Type Info -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-600">رسته فعلی تاییدشده مرکز:</span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-white border border-slate-200 text-slate-900 shadow-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-sky-600"><?= $cInfo['icon'] ?? 'local_hospital' ?></span>
                    <span><?= htmlspecialchars($cInfo['title'] ?? 'مرکز درمانی') ?></span>
                </span>
            </div>

            <!-- Target Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-2">
                    رسته جدید درخواستی <span class="text-rose-500">*</span>:
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($orgTypeMap as $tKey => $tVal): ?>
                        <?php if ($tKey === ($currentOrg['type'] ?? '')) continue; ?>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-primary cursor-pointer transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                            <input type="radio" name="requested_type" value="<?= $tKey ?>" required class="text-primary focus:ring-0">
                            <span class="material-symbols-outlined text-lg text-slate-600"><?= $tVal['icon'] ?></span>
                            <span class="text-xs font-black text-slate-800"><?= $tVal['title'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- New License Number -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">
                    شماره پروانه تاسیس / ارتقای جدید (اختیاری):
                </label>
                <input type="text" name="new_license_number" placeholder="مثال: IR-HOSP-9481 یا شماره پیگیری پروانه"
                       class="w-full text-xs font-mono font-bold px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary outline-none transition-all">
            </div>

            <!-- Justification / Reason -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">
                    شرح دلایل و مستندات تغییر رسته <span class="text-rose-500">*</span>:
                </label>
                <textarea name="justification" rows="3" required placeholder="لطفاً تغییرات ساختاری، تجهیزات اضافه‌شده و دلایل نیاز به تغییر رسته را توضیح دهید (حداقل ۱۰ کاراکتر)..."
                          class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary outline-none transition-all leading-relaxed"></textarea>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeTypeChangeModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#002d72] text-white font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">send</span>
                    <span>ثبت و ارسال تیکت به پشتیبانی</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/organization_footer.php'; ?>

