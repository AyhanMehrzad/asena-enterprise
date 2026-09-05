<?php
require_once 'includes/organization_header.php';

$orgService = App::organization();
$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Link or Update Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'link_doctor') {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $isHead   = isset($_POST['is_head_physician']) ? 1 : 0;
        $days     = trim($_POST['working_days'] ?? 'شنبه تا چهارشنبه');
        $hours    = trim($_POST['working_hours'] ?? '۱۶:۰۰ الی ۲۱:۰۰');

        if ($doctorId > 0) {
            $ok = $orgService->linkDoctor($orgId, $doctorId, $isHead, $days, $hours);
            if ($ok) {
                $message = 'پزشک با موفقیت به مرکز اضافه گردید و در لیست پزشکان همکار قرار گرفت.';
                $messageType = 'success';
            } else {
                $message = 'خطا در افزودن پزشک به مرکز.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'unlink_doctor') {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        if ($doctorId > 0) {
            $del = $pdo->prepare("DELETE FROM organization_doctors WHERE organization_id = ? AND doctor_id = ?");
            if ($del->execute([$orgId, $doctorId])) {
                $message = 'پزشک از کادر این مرکز حذف گردید.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch affiliated doctors
$affiliatedDoctors = $orgService->getDoctors($orgId);

// Fetch all available doctors in system for selection
$allDoctors = $pdo->query("SELECT id, name, specialty FROM doctors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-indigo-600 text-3xl">stethoscope</span>
                <span>کادر پزشکان همکار و شیفت‌های درمانی</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت پزشکان همکار، انتصاب پزشک ارشد، زمان‌بندی روزهای حضور و ساعات ویزیت</p>
        </div>

        <button onclick="document.getElementById('add-doctor-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">person_add</span>
            <span>افزودن پزشک به کادر درمان</span>
        </button>
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

    <!-- Add Doctor Modal / Form -->
    <div id="add-doctor-modal" class="hidden bg-white p-6 rounded-3xl border border-indigo-200 shadow-lg">
        <h2 class="text-sm font-black text-indigo-950 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-600">group_add</span>
            <span>انتخاب پزشک و تنظیم شیفت‌های ویزیت</span>
        </h2>

        <form method="POST" action="doctors.php" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="link_doctor">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب پزشک از پلتفرم *</label>
                <select name="doctor_id" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="">-- انتخاب کنید --</option>
                    <?php foreach ($allDoctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>">
                            دکتر <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialty']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">روزهای حضور در این مرکز *</label>
                <input type="text" name="working_days" required value="شنبه تا چهارشنبه" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ساعت شیفت ویزیت *</label>
                <input type="text" name="working_hours" required value="۱۶:۰۰ الی ۲۱:۰۰" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="flex items-center gap-2 pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_head_physician" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-black text-slate-800">پزشک ارشد / رئیس بخش جراحی</span>
                </label>
            </div>

            <div class="sm:col-span-2 flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('add-doctor-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                    ثبت در کادر پزشکان
                </button>
            </div>
        </form>
    </div>

    <!-- Doctors Roster List -->
    <?php if (empty($affiliatedDoctors)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-4xl">stethoscope</span>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">هنوز پزشکی به این مرکز منتسب نشده است</h3>
            <p class="text-xs text-slate-500 mb-4">با کلیک بر روی دکمه «افزودن پزشک به کادر درمان»، همکاران خود را مشخص کنید.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($affiliatedDoctors as $doc): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg shrink-0">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-black text-slate-900 text-sm truncate">دکتر <?= htmlspecialchars($doc['name']) ?></h3>
                                <?php if (!empty($doc['is_head_physician'])): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">پزشک ارشد</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-indigo-600 font-bold block mt-0.5 truncate"><?= htmlspecialchars($doc['specialty']) ?></span>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-xl text-xs text-slate-600 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">روزهای حضور:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_days'] ?? 'همه روزه') ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">شیفت ویزیت:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_hours'] ?? 'عصر') ?></span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="../doctor_profile.php?id=<?= (int)$doc['id'] ?>" target="_blank" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                        <span>مشاهده رزومه</span>
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>

                    <form method="POST" action="doctors.php" onsubmit="return confirm('آیا از حذف این پزشک از کادر مرکز اطمینان دارید؟')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unlink_doctor">
                        <input type="hidden" name="doctor_id" value="<?= (int)$doc['id'] ?>">
                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            <span>حذف از کادر</span>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
