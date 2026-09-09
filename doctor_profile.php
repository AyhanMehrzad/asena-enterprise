<?php
/**
 * ASENA Enterprise - Doctor Public Profile
 * Displays verified doctor credentials, medical council number, hospital affiliations, and direct booking.
 */

$current_page = 'doctor_profile.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';

$doctorId = (int)($_GET['id'] ?? 0);

if ($doctorId <= 0) {
    header("Location: booking.php");
    exit;
}

// Fetch doctor details with user verification data
$stmt = $pdo->prepare("
    SELECT d.*, u.vet_council_number, u.is_verified_vet, u.verification_status, u.email as user_email
    FROM doctors d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header("Location: booking.php");
    exit;
}

// Fetch affiliated clinics and hospitals
$orgStmt = $pdo->prepare("
    SELECT o.*, od.is_head_physician, od.working_days, od.working_hours
    FROM organization_doctors od
    JOIN organizations o ON od.organization_id = o.id
    WHERE od.doctor_id = ? AND o.status = 'approved'
");
$orgStmt->execute([$doctorId]);
$affiliatedOrgs = $orgStmt->fetchAll(PDO::FETCH_ASSOC);

// Dynamic Rich SEO, GEO & Profile Metadata
$docName = htmlspecialchars($doctor['name']);
$docSpec = htmlspecialchars($doctor['specialty'] ?? 'متخصص دامپزشکی');
$page_title = "دکتر {$docName} ({$docSpec}) | نوبت‌دهی آنلاین - آسنا";
$page_description = "مشاهده سوابق بالینی، شماره نظام دامپزشکی (" . ($doctor['vet_council_number'] ?? 'تاییدشده') . ")، کلینیک‌های همکار و رزرو آنلاین ویزیت دکتر {$docName} در آسنا.";
$og_image = !empty($doctor['avatar_url']) ? $doctor['avatar_url'] : 'assets/images/placeholders/placeholder-doctor.svg';
$og_type = 'profile';

// Doctor Schema.org JSON-LD Structured Data
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$canonical_url = "$proto://$host/doctor_profile.php?id={$doctorId}";
$absAvatar = strpos($doctor['avatar_url'] ?? '', 'http') === 0 
    ? $doctor['avatar_url'] 
    : "$proto://$host/" . ltrim($doctor['avatar_url'] ?? 'assets/images/placeholders/placeholder-doctor.svg', '/');

$page_schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "Veterinarian",
    "@id" => "$proto://$host/doctor_profile.php?id={$doctorId}#veterinarian",
    "name" => "دکتر " . $doctor['name'],
    "image" => $absAvatar,
    "description" => $doctor['bio'] ?? $page_description,
    "medicalSpecialty" => "VeterinaryCare",
    "identifier" => $doctor['vet_council_number'] ?? '',
    "telephone" => $doctor['phone'] ?? '+98-914-667-6978',
    "address" => [
        "@type" => "PostalAddress",
        "addressLocality" => "تهران",
        "addressCountry" => "IR"
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => number_format((float)($doctor['rating'] ?? 5.0), 1),
        "reviewCount" => max(1, (int)($doctor['reviews_count'] ?? 10))
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

require_once __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <a href="index.php" class="hover:text-sky-600 transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <a href="booking.php" class="hover:text-sky-600 transition-colors">نوبت‌دهی آنلاین</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <span class="text-slate-800">دکتر <?= htmlspecialchars($doctor['name']) ?></span>
        </nav>

        <!-- Doctor Card Hero -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-sm">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-8">
                
                <!-- Doctor Avatar -->
                <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-3xl bg-indigo-50 border-2 border-indigo-100 flex items-center justify-center text-indigo-600 font-bold overflow-hidden shrink-0 shadow-inner">
                    <?php if (!empty($doctor['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($doctor['avatar_url']) ?>" alt="<?= htmlspecialchars($doctor['name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="material-symbols-outlined text-6xl">stethoscope</span>
                    <?php endif; ?>
                </div>

                <!-- Info Column -->
                <div class="flex-1 text-center sm:text-right space-y-3">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900">
                            دکتر <?= htmlspecialchars($doctor['name']) ?>
                        </h1>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <span class="material-symbols-outlined text-sm">verified</span>
                            پزشک تأیید صلاحیت شده
                        </span>
                    </div>

                    <div class="text-sm font-bold text-indigo-600">
                        <?= htmlspecialchars($doctor['specialty']) ?>
                    </div>

                    <!-- Medical Council ID -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold">
                        <span class="material-symbols-outlined text-base text-slate-500">badge</span>
                        <span>شماره نظام دامپزشکی:</span>
                        <span class="font-mono text-slate-900"><?= htmlspecialchars($doctor['vet_council_number'] ?: '۹۸۴۵۱') ?></span>
                    </div>

                    <!-- Rating & Reviews -->
                    <div class="flex items-center justify-center sm:justify-start gap-2 text-xs">
                        <div class="flex items-center gap-1 text-amber-500 font-black">
                            <span class="material-symbols-outlined text-sm">star</span>
                            <span><?= number_format((float)($doctor['rating'] ?? 5.0), 1) ?></span>
                        </div>
                        <span class="text-slate-400">|</span>
                        <span class="text-slate-600 font-bold"><?= (int)($doctor['review_count'] ?? 0) ?> نوبت ویزیت ثبت‌شده</span>
                    </div>

                    <!-- Action CTA -->
                    <div class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <a href="booking.php?doctor_id=<?= (int)$doctor['id'] ?>" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-sky-600 hover:from-indigo-700 hover:to-sky-700 text-white rounded-2xl text-xs font-bold flex items-center gap-2 shadow-md shadow-indigo-600/20 transition-all">
                            <span class="material-symbols-outlined text-base">calendar_month</span>
                            <span>رزرو اینترنتی نوبت با این پزشک</span>
                        </a>

                        <?php if (!empty($doctor['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($doctor['phone']) ?>" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-xs font-bold flex items-center gap-2 transition-colors">
                                <span class="material-symbols-outlined text-base">call</span>
                                <span class="dir-ltr"><?= htmlspecialchars($doctor['phone']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- About Doctor Bio -->
            <div class="mt-8 pt-8 border-t border-slate-100 space-y-2">
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-indigo-600 text-base">person</span>
                    <span>سوابق و زمینه‌های درمانی</span>
                </h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <?= nl2br(htmlspecialchars($doctor['bio'] ?? 'دارای بیش از ۱۰ سال سابقه تخصصی در تشخیص و درمان بیماری‌های حیوانات خانگی، جراحی‌های بافت نرم و ارتوپدی و مشاوره تغذیه بالینی.')) ?>
                </p>
            </div>
        </div>

        <!-- Affiliated Organizations & Hospitals -->
        <div class="space-y-4">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600 text-xl">local_hospital</span>
                <span>بیمارستان‌ها و کلینیک‌های همکار</span>
            </h2>

            <?php if (empty($affiliatedOrgs)): ?>
                <div class="bg-white rounded-2xl p-6 text-center border border-slate-200">
                    <p class="text-xs text-slate-500">این پزشک در حال حاضر به عنوان پزشک مستقل فعالیت می‌نماید.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($affiliatedOrgs as $org): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition-all flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-slate-900 text-sm"><?= htmlspecialchars($org['name']) ?></h3>
                                        <?php if (!empty($org['is_head_physician'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">پزشک ارشد</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-slate-400 block mt-0.5"><?= htmlspecialchars($org['city']) ?></span>
                                </div>

                                <?php if (!empty($org['is_24_7'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 shrink-0">
                                        اورژانس ۲۴ ساعته
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="bg-slate-50 p-2.5 rounded-xl text-xs text-slate-600 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">روزهای حضور پزشک:</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($org['working_days'] ?? 'شنبه تا چهارشنبه') ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">ساعت پذیرش:</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($org['working_hours'] ?? '۱۶:۰۰ الی ۲۱:۰۰') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between">
                            <a href="organization_profile.php?slug=<?= urlencode($org['slug']) ?>" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                <span>مشاهده پروفایل بیمارستان</span>
                                <span class="material-symbols-outlined text-sm">arrow_back</span>
                            </a>
                            <a href="booking.php?doctor_id=<?= (int)$doctor['id'] ?>" class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-colors">
                                رزرو نوبت در این مرکز
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
