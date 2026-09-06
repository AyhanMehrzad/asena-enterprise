<?php
require_once 'includes/organization_header.php';

$message = '';
$messageType = '';

// Handle Facility Details Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    csrf_verify();

    $name           = trim($_POST['name'] ?? '');
    $type           = trim($_POST['type'] ?? 'clinic');
    $phone          = trim($_POST['phone'] ?? '');
    $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
    $operatingHours = trim($_POST['operating_hours'] ?? '');
    $is247          = isset($_POST['is_24_7']) ? 1 : 0;
    $city           = trim($_POST['city'] ?? 'تهران');
    $address        = trim($_POST['address'] ?? '');
    $instagram      = trim($_POST['instagram'] ?? '');
    $website        = trim($_POST['website'] ?? '');
    $description    = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        $up = $pdo->prepare("
            UPDATE organizations 
            SET name = ?, type = ?, phone = ?, emergency_phone = ?, operating_hours = ?,
                is_24_7 = ?, city = ?, address = ?, instagram = ?, website = ?, description = ?
            WHERE id = ?
        ");
        if ($up->execute([$name, $type, $phone, $emergencyPhone, $operatingHours, $is247, $city, $address, $instagram, $website, $description, $currentOrg['id']])) {
            $message = 'اطلاعات مرکز درمانی با موفقیت به‌روزرسانی گردید.';
            $messageType = 'success';
            // Refresh record
            $currentOrg = $pdo->query("SELECT * FROM organizations WHERE id = " . (int)$currentOrg['id'])->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = 'خطا در به‌روزرسانی اطلاعات.';
            $messageType = 'error';
        }
    }
}

// Compute facility statistics
$orgId = (int)$currentOrg['id'];
$docCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_doctors WHERE organization_id = {$orgId}")->fetchColumn();
$invCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_inventory WHERE organization_id = {$orgId}")->fetchColumn();
$rating = (float)($currentOrg['rating'] ?? 5.0);
$reviewCount = (int)($currentOrg['review_count'] ?? 0);
?>

<div class="p-6 max-w-5xl mx-auto space-y-6">

    <!-- Header Title -->
    <div>
        <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
            <span class="material-symbols-outlined text-sky-600 text-3xl">local_hospital</span>
            <span>مدیریت و پیشخوان مرکز درمانی</span>
        </h1>
        <p class="text-xs text-slate-500 mt-1">مشخصات عمومی، اطلاعات تماس، شیفت‌های کاری و وضعیت اورژانس ۲۴ ساعته</p>
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

    <!-- Metric Cards Matching Reference Screenshot -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">پزشکان و کادر همکار</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= $docCount ?></span>
                    <span class="text-xs text-secondary-container font-bold">متخصص</span>
                </div>
                <p class="text-[11px] text-slate-400">پزشک، داروساز و گرومر</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">اقلام فعال در داروخانه</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= $invCount ?></span>
                    <span class="text-xs text-blue-600 font-bold">قلم دارو</span>
                </div>
                <p class="text-[11px] text-slate-400">موجودی انبار و ثبت نسخه</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-500/10 text-teal-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">medication</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">امتیاز رضایت مراجعین</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-amber-500"><?= number_format($rating, 1) ?></span>
                    <span class="text-xs text-amber-500 font-bold">★ ۵.۰</span>
                </div>
                <p class="text-[11px] text-slate-400">بر اساس بازخورد بیماران</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">star</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">وضعیت بخش اورژانس</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-black <?= !empty($currentOrg['is_24_7']) ? 'text-rose-600' : 'text-slate-600' ?>">
                        <?= !empty($currentOrg['is_24_7']) ? '۲۴ ساعته فعال' : 'شیفت روزانه' ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400"><?= !empty($currentOrg['is_24_7']) ? 'پذیرش شبانه‌روزی فوری' : 'ساعات کاری مندرج' ?></p>
            </div>
            <div class="w-12 h-12 rounded-2xl <?= !empty($currentOrg['is_24_7']) ? 'bg-rose-500/10 text-rose-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">e911_emergency</span>
            </div>
        </div>
    </div>

    <!-- Quick Management Action Tiles -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="appointments.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 group-hover:bg-sky-600 text-sky-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">calendar_month</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">نوبت‌دهی و پذیرش</h3>
                    <p class="text-[11px] text-slate-400">ثبت نوبت، پزشکان و گرومرها</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-sky-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="shifts.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 group-hover:bg-blue-600 text-blue-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">more_time</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">مدیریت زمان و شیفت‌ها</h3>
                    <p class="text-[11px] text-slate-400">تقویم کاری و ثبت مرخصی</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-blue-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="doctors.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-purple-600 text-purple-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">group</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">کادر درمانی و گرومرها</h3>
                    <p class="text-[11px] text-slate-400">پزشکان، گرومرها و داروسازان</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-purple-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="subscriptions.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 group-hover:bg-amber-600 text-amber-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">autorenew</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">اشتراک‌ها و اتوشیپ</h3>
                    <p class="text-[11px] text-slate-400">تکرار خودکار غذا و دارو</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-amber-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="orders.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">local_shipping</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">سفارشات فروش و پست</h3>
                    <p class="text-[11px] text-slate-400">کدهای رهگیری و مرسولات</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-indigo-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="inventory.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 group-hover:bg-teal-600 text-teal-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">medication</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">داروخانه و کالاها</h3>
                    <p class="text-[11px] text-slate-400">قیمت‌گذاری و انبار</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-teal-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>

        <a href="wallet.php" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition-all group flex items-center justify-between col-span-1 sm:col-span-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 group-hover:bg-emerald-600 text-emerald-600 group-hover:text-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-800">مدیریت مالی، کارمزد ۵٪ پلتفرم و تسویه پایا</h3>
                    <p class="text-[11px] text-slate-400">تسویه هفتگی پنج‌شنبه‌ها به شماره شبا</p>
                </div>
            </div>
            <span class="material-symbols-outlined text-slate-400 group-hover:text-emerald-600 group-hover:-translate-x-1 transition-all text-lg">arrow_back</span>
        </a>
    </div>

    <!-- Edit Profile Form -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-sky-600">edit_square</span>
            <span>ویرایش مشخصات و اطلاعات مرکز</span>
        </h2>

        <form method="POST" action="index.php" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام رسمی بیمارستان یا کلینیک *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($currentOrg['name']) ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع مرکز درمانی *</label>
                    <select name="type" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-sm bg-white">
                        <option value="hospital" <?= ($currentOrg['type'] ?? '') === 'hospital' ? 'selected' : '' ?>>بیمارستان فوق‌تخصصی</option>
                        <option value="clinic" <?= ($currentOrg['type'] ?? '') === 'clinic' ? 'selected' : '' ?>>کلینیک تخصصی و جراحی</option>
                        <option value="polyclinic" <?= ($currentOrg['type'] ?? '') === 'polyclinic' ? 'selected' : '' ?>>پلی‌کلینیک درمانی</option>
                        <option value="emergency_center" <?= ($currentOrg['type'] ?? '') === 'emergency_center' ? 'selected' : '' ?>>مرکز اورژانس شبانه‌روزی ۲۴ ساعته</option>
                        <option value="diagnostic_lab" <?= ($currentOrg['type'] ?? '') === 'diagnostic_lab' ? 'selected' : '' ?>>آزمایشگاه و تصویربرداری</option>
                        <option value="shelter" <?= ($currentOrg['type'] ?? '') === 'shelter' ? 'selected' : '' ?>>پناهگاه و نقاهتگاه حمایتی</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن ثابت *</label>
                    <input type="text" name="phone" dir="ltr" value="<?= htmlspecialchars($currentOrg['phone'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-sm text-left">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تلفن خط ویژه اورژانس</label>
                    <input type="text" name="emergency_phone" dir="ltr" value="<?= htmlspecialchars($currentOrg['emergency_phone'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-sm text-left">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">ساعات کاری و پذیرش</label>
                    <input type="text" name="operating_hours" value="<?= htmlspecialchars($currentOrg['operating_hours'] ?? '۹ الی ۲۲') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-sm">
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black text-rose-900 block">بخش اورژانس ۲۴ ساعته شبانه‌روزی</span>
                    <span class="text-[11px] text-slate-500">آیا این مرکز دارای پذیرش اورژانس در طول شب و روزهای تعطیل است؟</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_24_7" value="1" <?= !empty($currentOrg['is_24_7']) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شهر *</label>
                    <input type="text" name="city" required value="<?= htmlspecialchars($currentOrg['city'] ?? 'تهران') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 focus:border-sky-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شناسه اینستاگرام (مثال: @clinic_name)</label>
                    <input type="text" name="instagram" dir="ltr" value="<?= htmlspecialchars($currentOrg['instagram'] ?? '') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس دقیق مرکز *</label>
                <textarea name="address" rows="2" required class="w-full p-3 rounded-xl border border-slate-300 focus:border-sky-500 text-sm"><?= htmlspecialchars($currentOrg['address'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">درباره مرکز، تاریخچه و امکانات تخصصی</label>
                <textarea name="description" rows="3" class="w-full p-3 rounded-xl border border-slate-300 focus:border-sky-500 text-sm"><?= htmlspecialchars($currentOrg['description'] ?? '') ?></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-8 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-2xl text-xs font-bold shadow-md shadow-sky-600/20 transition-all">
                    ذخیره تغییرات مشخصات مرکز
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
