<?php
$currentPage = 'verifications';
require_once 'includes/admin_header.php';
require_once '../includes/App.php';
require_once '../includes/functions.php';

$roleVerificationService = App::roleVerification();
$message = '';
$messageType = '';

// Process 1-Click Verification Decisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $appId = (int)($_POST['application_id'] ?? 0);
    $action = $_POST['action'];
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    $reviewerId = (int)($_SESSION['user_id'] ?? 1);

    if ($appId > 0) {
        if ($action === 'approve') {
            $ok = $roleVerificationService->approveApplication($appId, $reviewerId, $adminNotes);
            if ($ok) {
                $message = 'درخواست احراز هویت با موفقیت تأیید شد. نقش کاربر ارتقا یافت و پیامک تبریک ارسال گردید.';
                $messageType = 'success';
            } else {
                $message = 'خطا در تایید درخواست احراز هویت.';
                $messageType = 'error';
            }
        } elseif ($action === 'reject') {
            if (empty($rejectionReason)) {
                $rejectionReason = 'عدم انطباق یا نقص در مدارک بارگذاری شده.';
            }
            $ok = $roleVerificationService->rejectApplication($appId, $reviewerId, $rejectionReason);
            if ($ok) {
                $message = 'درخواست با موفقیت رد شد و پیامک حاوی دلیل برای متقاضی ارسال گردید.';
                $messageType = 'success';
            } else {
                $message = 'خطا در رد درخواست.';
                $messageType = 'error';
            }
        }
    }
}

// Current Filter Parameters
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? 'pending';

$applications = $roleVerificationService->getApplications(
    !empty($filterRole) ? $filterRole : null,
    !empty($filterStatus) ? $filterStatus : 'pending'
);

// Statistics counts
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM role_applications WHERE status = 'pending'")->fetchColumn();
$totalApproved = (int)$pdo->query("SELECT COUNT(*) FROM role_applications WHERE status = 'approved'")->fetchColumn();
$totalRejected = (int)$pdo->query("SELECT COUNT(*) FROM role_applications WHERE status = 'rejected'")->fetchColumn();
$totalDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalOrgs = (int)$pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header & Statistics Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-sky-600 text-3xl">verified_user</span>
                <span>میز ممیزی و احراز صلاحیت مشاغل و مراکز</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">بررسی اسناد هویتی، پروانه‌های پزشکی، مدارک تأسیس بیمارستان‌ها و اعطای نقش‌های تخصصی</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="verifications.php?status=pending" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $filterStatus === 'pending' ? 'bg-sky-600 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                در انتظار بررسی (<?= $totalPending ?>)
            </a>
            <a href="verifications.php?status=approved" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $filterStatus === 'approved' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                تأیید شده‌ها (<?= $totalApproved ?>)
            </a>
            <a href="verifications.php?status=all" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $filterStatus === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                همه موارد
            </a>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">در صف ممیزی</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalPending) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">تأیید صلاحیت شده</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalApproved) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">پزشکان فعال</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalDoctors) ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_hospital</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">مراکز و کلینیک‌ها</span>
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalOrgs) ?></span>
            </div>
        </div>
    </div>

    <!-- Role Filter Tabs -->
    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold text-slate-500 ml-2">فیلتر نقش:</span>
        
        <a href="verifications.php?status=<?= $filterStatus ?>&role=" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= empty($filterRole) ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'text-slate-600 hover:bg-slate-100' ?>">
            همه نقش‌ها
        </a>
        <a href="verifications.php?status=<?= $filterStatus ?>&role=doctor" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterRole === 'doctor' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-600 hover:bg-slate-100' ?>">
            دامپزشکان (Doctor)
        </a>
        <a href="verifications.php?status=<?= $filterStatus ?>&role=organization" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterRole === 'organization' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'text-slate-600 hover:bg-slate-100' ?>">
            مراکز درمانی و بیمارستان‌ها
        </a>
        <a href="verifications.php?status=<?= $filterStatus ?>&role=pharmacist" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterRole === 'pharmacist' ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'text-slate-600 hover:bg-slate-100' ?>">
            داروسازان (Pharmacist)
        </a>
        <a href="verifications.php?status=<?= $filterStatus ?>&role=supplier" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all <?= $filterRole === 'supplier' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'text-slate-600 hover:bg-slate-100' ?>">
            تأمین‌کنندگان (Wholesale)
        </a>
    </div>

    <!-- Applications List -->
    <?php if (empty($applications)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-sm">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl">inbox</span>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">هیچ درخواستی در این بخش یافت نشد</h3>
            <p class="text-xs text-slate-500">تمامی مدارک بررسی شده‌اند یا هنوز درخواستی ثبت نشده است.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($applications as $app): 
                $badgeStyle = match($app['applied_role']) {
                    'doctor' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                    'organization' => 'bg-rose-100 text-rose-800 border-rose-200',
                    'pharmacist' => 'bg-teal-100 text-teal-800 border-teal-200',
                    'supplier' => 'bg-amber-100 text-amber-800 border-amber-200',
                    default => 'bg-slate-100 text-slate-800 border-slate-200'
                };
                $rolePersian = match($app['applied_role']) {
                    'doctor' => 'پزشک دامپزشک',
                    'organization' => 'مرکز درمانی / بیمارستان',
                    'pharmacist' => 'داروساز دامپزشکی',
                    'supplier' => 'تأمین‌کننده و پخش عمده',
                    default => $app['applied_role']
                };
            ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm hover:border-slate-300 transition-all">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    
                    <!-- Left/Main Applicant Info -->
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="text-lg font-black text-slate-900"><?= htmlspecialchars($app['full_name']) ?></span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $badgeStyle ?>">
                                <?= $rolePersian ?>
                            </span>
                            <span class="text-xs text-slate-400">ثبت: <?= htmlspecialchars($app['created_at']) ?></span>
                            
                            <?php if ($app['status'] === 'approved'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check</span> تأیید شده
                                </span>
                            <?php elseif ($app['status'] === 'rejected'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">close</span> رد شده
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">hourglass_empty</span> در انتظار ممیزی
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs text-slate-600 bg-slate-50/70 p-3.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-slate-400 block font-medium">شماره تماس / موبایل:</span>
                                <span class="font-bold text-slate-800 dir-ltr inline-block"><?= htmlspecialchars($app['phone']) ?></span>
                            </div>
                            <?php if (!empty($app['license_number'])): ?>
                            <div>
                                <span class="text-slate-400 block font-medium">شماره نظام / پروانه:</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($app['license_number']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($app['specialty'])): ?>
                            <div>
                                <span class="text-slate-400 block font-medium">تخصص:</span>
                                <span class="font-bold text-indigo-700"><?= htmlspecialchars($app['specialty']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($app['organization_name'])): ?>
                            <div>
                                <span class="text-slate-400 block font-medium">نام مجموعه / مرکز:</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($app['organization_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($app['city'])): ?>
                            <div>
                                <span class="text-slate-400 block font-medium">شهر و نشانی:</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($app['city']) ?> - <?= htmlspecialchars($app['address'] ?? '') ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($app['instagram']) || !empty($app['website'])): ?>
                            <div>
                                <span class="text-slate-400 block font-medium">ارتباطات مجازی:</span>
                                <span class="font-bold text-sky-700 dir-ltr inline-block">
                                    <?= htmlspecialchars($app['instagram']) ?> <?= htmlspecialchars($app['website']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Documents Preview Strip -->
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <span class="text-xs font-bold text-slate-500">مدارک قانونی پیوست:</span>
                            
                            <?php if (!empty($app['degree_document_url'])): ?>
                                <a href="../<?= htmlspecialchars($app['degree_document_url']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">school</span>
                                    <span>مشاهده کارت نظام / مدرک تحصیلی</span>
                                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($app['license_document_url'])): ?>
                                <a href="../<?= htmlspecialchars($app['license_document_url']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">description</span>
                                    <span>مشاهده پروانه تأسیس / فعالیت</span>
                                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                </a>
                            <?php endif; ?>

                            <?php if (empty($app['degree_document_url']) && empty($app['license_document_url'])): ?>
                                <span class="text-xs text-slate-400 italic">مدرک فایلی پیوست نشده است.</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($app['admin_notes'])): ?>
                            <div class="text-xs text-slate-600 bg-amber-50/70 p-2.5 rounded-lg border border-amber-200/60">
                                <span class="font-bold text-amber-800">یادداشت کارشناس ممیزی:</span>
                                <span><?= htmlspecialchars($app['admin_notes']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['rejection_reason'])): ?>
                            <div class="text-xs text-rose-700 bg-rose-50/70 p-2.5 rounded-lg border border-rose-200/60">
                                <span class="font-bold text-rose-900">علت رد درخواست:</span>
                                <span><?= htmlspecialchars($app['rejection_reason']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Side: 1-Click Action Forms -->
                    <?php if ($app['status'] === 'pending'): ?>
                        <div class="lg:w-80 shrink-0 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <span class="text-xs font-bold text-slate-700 block text-center">تصمیم‌گیری احراز صلاحیت</span>
                            
                            <!-- Approve Form -->
                            <form method="POST" action="verifications.php" class="space-y-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                
                                <input type="text" name="admin_notes" placeholder="یادداشت تأیید (اختیاری)" class="w-full h-8 px-2.5 rounded-lg border border-slate-300 text-xs">
                                
                                <button type="submit" onclick="return confirm('آیا از تایید این مدرک و ارتقای نقش کاربر اطمینان دارید؟')" class="w-full h-10 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 transition-all">
                                    <span class="material-symbols-outlined text-[18px]">verified</span>
                                    <span>تأیید صلاحیت و ارتقای نقش</span>
                                </button>
                            </form>

                            <!-- Reject Toggle Form -->
                            <form method="POST" action="verifications.php" class="space-y-2 pt-2 border-t border-slate-200">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                
                                <input type="text" name="rejection_reason" required placeholder="دلیل رد (پیامک می‌شود)" class="w-full h-8 px-2.5 rounded-lg border border-slate-300 text-xs">

                                <button type="submit" onclick="return confirm('آیا از رد این درخواست اطمینان دارید؟ پیامک حاوی دلیل ارسال خواهد شد.')" class="w-full h-9 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                    <span>رد درخواست و ارسال پیامک</span>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/admin_footer.php'; ?>
