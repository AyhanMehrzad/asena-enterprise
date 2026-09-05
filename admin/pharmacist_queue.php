<?php
$currentPage = 'pharmacist_queue';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';
require_once '../includes/SmsService.php';

$sms = new SmsService();
$message = '';
$messageType = '';

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $rxId = (int)($_POST['rx_id'] ?? 0);
    $action = $_POST['action'];
    $notes = trim($_POST['pharmacist_notes'] ?? '');
    $reviewerId = (int)($_SESSION['user_id'] ?? 1);

    if ($rxId > 0 && in_array($action, ['approve', 'reject'])) {
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        
        $upStmt = $pdo->prepare("
            UPDATE prescriptions 
            SET status = ?, pharmacist_notes = ?, reviewed_by = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->execute([$newStatus, $notes, $reviewerId, $rxId]);

        // Fetch customer phone to send SMS
        $uStmt = $pdo->prepare("
            SELECT u.phone, u.name as user_name, rx.clinic_name, rx.pet_id 
            FROM prescriptions rx 
            JOIN users u ON rx.user_id = u.id 
            WHERE rx.id = ?
        ");
        $uStmt->execute([$rxId]);
        $rxData = $uStmt->fetch(PDO::FETCH_ASSOC);

        if ($rxData && !empty($rxData['phone'])) {
            $petName = 'پت شما';
            if (!empty($rxData['pet_id'])) {
                $pStmt = $pdo->prepare("SELECT pet_name FROM pet_health_records WHERE id = ?");
                $pStmt->execute([$rxData['pet_id']]);
                $petName = $pStmt->fetchColumn() ?: 'پت شما';
            }

            if ($newStatus === 'approved') {
                $smsText = "آسنا: نسخه ارسالی شما برای {$petName} توسط دکتر داروساز تأیید شد و سفارش شما در صف آماده‌سازی قرار گرفت.";
                $sms->send($rxData['phone'], $smsText);
            } else {
                $smsText = "آسنا: نسخه ارسالی شما برای {$petName} تأیید نشد. علت: {$notes}. جهت راهنمایی وارد حساب کاربری خود شوید.";
                $sms->send($rxData['phone'], $smsText);
            }
        }

        $message = ($newStatus === 'approved') ? 'نسخه با موفقیت تایید و سفارش آزاد شد.' : 'نسخه با موفقیت رد شد و پیامک اطلاع‌رسانی ارسال گردید.';
        $messageType = 'success';
    }
}

// Filter
$filter = $_GET['status'] ?? 'pending';
$allowedFilters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $allowedFilters)) $filter = 'pending';

$whereClause = ($filter === 'all') ? '' : 'WHERE rx.status = ' . $pdo->quote($filter);

$stmt = $pdo->prepare("
    SELECT rx.*, u.name as user_name, u.phone as user_phone, 
           p.pet_name, p.species, p.breed, p.weight_kg,
           rev.name as reviewer_name
    FROM prescriptions rx
    JOIN users u ON rx.user_id = u.id
    LEFT JOIN pet_health_records p ON rx.pet_id = p.id
    LEFT JOIN users rev ON rx.reviewed_by = rev.id
    {$whereClause}
    ORDER BY rx.created_at DESC
");
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 rtl text-right" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">medical_services</span>
                پنل بررسی و تایید نسخه الکترونیک (Rx Verification Queue)
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">
                استاندارد داروخانه تخصصی دامپزشکی Chewy Pharmacy — تایید اصالت نسخه و دوز قبل از ارسال داروهای تجویزی
            </p>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2 bg-surface-container p-1 rounded-xl">
            <a href="?status=pending" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo $filter === 'pending' ? 'bg-primary text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                در انتظار بررسی
            </a>
            <a href="?status=approved" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo $filter === 'approved' ? 'bg-status-active text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                تایید شده
            </a>
            <a href="?status=rejected" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo $filter === 'rejected' ? 'bg-error text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                رد شده
            </a>
            <a href="?status=all" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo $filter === 'all' ? 'bg-secondary text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                همه
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-xl mb-6 flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
            <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
            <span class="font-bold text-sm"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Prescriptions Grid / Cards -->
    <?php if (empty($prescriptions)): ?>
        <div class="bg-white rounded-2xl p-12 text-center border border-outline-variant/20 shadow-sm">
            <span class="material-symbols-outlined text-outline text-6xl mb-3">inventory_2</span>
            <h3 class="text-lg font-bold text-on-surface">نسخه‌ای با این وضعیت یافت نشد</h3>
            <p class="text-sm text-on-surface-variant mt-1">کلیه نسخه‌های مشتریان بررسی و پردازش شده‌اند.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($prescriptions as $rx): ?>
                <div class="bg-white rounded-2xl p-5 border border-outline-variant/20 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="flex items-start justify-between pb-3 mb-3 border-b border-outline-variant/10">
                            <div>
                                <span class="text-xs font-bold text-on-surface-variant">شناسه نسخه: #<?php echo $rx['id']; ?></span>
                                <h4 class="font-bold text-base text-on-surface mt-0.5">
                                    پت: <?php echo htmlspecialchars($rx['pet_name'] ?: 'ثبت نشده'); ?>
                                    <span class="text-xs text-primary font-normal">(<?php echo htmlspecialchars($rx['species'] ?? 'نامشخص'); ?> - <?php echo htmlspecialchars($rx['breed'] ?? ''); ?>)</span>
                                </h4>
                            </div>
                            <?php
                            $statusBadges = [
                                'pending'  => '<span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">schedule</span> در انتظار بررسی</span>',
                                'approved' => '<span class="bg-green-100 text-green-800 text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">verified</span> تایید شده</span>',
                                'rejected' => '<span class="bg-red-100 text-red-800 text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1"><span class="material-symbols-outlined text-sm">cancel</span> رد شده</span>',
                            ];
                            echo $statusBadges[$rx['status']] ?? '';
                            ?>
                        </div>

                        <!-- Customer & Doctor Info -->
                        <div class="grid grid-cols-2 gap-3 text-xs mb-4">
                            <div class="bg-surface-container-low p-2.5 rounded-lg">
                                <span class="text-on-surface-variant block mb-1">صاحب پت (مشتری):</span>
                                <strong class="text-on-surface"><?php echo htmlspecialchars($rx['user_name']); ?></strong>
                                <span class="block text-on-surface-variant mt-0.5" dir="ltr"><?php echo htmlspecialchars($rx['user_phone']); ?></span>
                            </div>
                            <div class="bg-surface-container-low p-2.5 rounded-lg">
                                <span class="text-on-surface-variant block mb-1">کلینیک و دامپزشک:</span>
                                <strong class="text-on-surface"><?php echo htmlspecialchars($rx['clinic_name'] ?: 'ذکر نشده'); ?></strong>
                                <span class="block text-on-surface-variant mt-0.5">دکتر: <?php echo htmlspecialchars($rx['vet_name'] ?: 'ذکر نشده'); ?> (ن.د: <?php echo htmlspecialchars($rx['vet_license_number'] ?: '-'); ?>)</span>
                            </div>
                        </div>

                        <!-- Weight & Clinical flags -->
                        <?php if (!empty($rx['weight_kg'])): ?>
                            <div class="mb-4 inline-flex items-center gap-1.5 text-xs bg-blue-50 text-blue-800 px-3 py-1 rounded-lg border border-blue-200">
                                <span class="material-symbols-outlined text-sm">scale</span>
                                وزن پت: <strong><?php echo htmlspecialchars($rx['weight_kg']); ?> کیلوگرم</strong>
                            </div>
                        <?php endif; ?>

                        <!-- Prescription File Preview -->
                        <div class="mb-4">
                            <span class="text-xs text-on-surface-variant block mb-1.5 font-bold">فایل نسخه بارگذاری‌شده:</span>
                            <div class="rounded-xl overflow-hidden border border-outline-variant/30 bg-surface-container flex items-center justify-center p-2 relative group max-h-48">
                                <?php if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $rx['rx_file_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($rx['rx_file_url']); ?>" alt="نسخه پزشک" class="max-h-44 object-contain rounded-lg">
                                    <a href="../<?php echo htmlspecialchars($rx['rx_file_url']); ?>" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold text-xs gap-1.5">
                                        <span class="material-symbols-outlined">zoom_in</span> مشاهده اندازه کامل
                                    </a>
                                <?php else: ?>
                                    <a href="../<?php echo htmlspecialchars($rx['rx_file_url']); ?>" target="_blank" class="py-6 flex flex-col items-center gap-2 text-primary font-bold text-sm">
                                        <span class="material-symbols-outlined text-4xl">description</span>
                                        مشاهده و دانلود فایل PDF نسخه
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($rx['pharmacist_notes'])): ?>
                            <div class="bg-gray-50 p-3 rounded-lg text-xs text-on-surface border border-gray-200 mb-4">
                                <span class="font-bold block mb-1">یادداشت داروساز:</span>
                                <?php echo nl2br(htmlspecialchars($rx['pharmacist_notes'])); ?>
                                <?php if (!empty($rx['reviewer_name'])): ?>
                                    <span class="text-[10px] text-gray-500 block mt-1">توسط: <?php echo htmlspecialchars($rx['reviewer_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <?php if ($rx['status'] === 'pending'): ?>
                        <div class="pt-3 border-t border-outline-variant/10 flex flex-col gap-2">
                            <form method="POST" class="flex gap-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rx_id" value="<?php echo $rx['id']; ?>">
                                <input type="text" name="pharmacist_notes" placeholder="یادداشت داروساز (اختیاری)..." class="flex-1 text-xs border border-outline-variant/40 rounded-xl px-3 py-2 bg-surface-container-low focus:bg-white focus:outline-primary">
                                <button type="submit" name="action" value="approve" class="bg-status-active hover:bg-green-700 text-white font-bold text-xs px-4 py-2 rounded-xl flex items-center gap-1 shadow-sm transition-colors">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    تأیید نسخه
                                </button>
                                <button type="submit" name="action" value="reject" class="bg-error hover:bg-red-700 text-white font-bold text-xs px-3 py-2 rounded-xl flex items-center gap-1 shadow-sm transition-colors" onclick="return confirm('آیا از رد کردن این نسخه مطمئن هستید؟')">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    رد نسخه
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
