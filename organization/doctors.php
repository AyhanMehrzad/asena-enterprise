<?php
require_once 'includes/organization_header.php';

$orgService = App::organization();
$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Link or Update Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'link_doctor') {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $roleType = trim($_POST['role_type'] ?? 'doctor');
        $isHead   = isset($_POST['is_head_physician']) ? 1 : 0;
        $days     = trim($_POST['working_days'] ?? 'شنبه تا چهارشنبه');
        $hours    = trim($_POST['working_hours'] ?? '۱۶:۰۰ الی ۲۱:۰۰');

        if ($doctorId > 0) {
            $ok = $orgService->linkDoctor($orgId, $doctorId, $isHead, $days, $hours, $roleType);
            if ($ok) {
                $roleLabel = ($roleType === 'groomer') ? 'گرومر' : (($roleType === 'seller' || $roleType === 'pharmacist') ? 'مسئول فروشگاه/داروخانه' : 'پزشک');
                $message = "همکار با موفقیت در نقش «{$roleLabel}» به کادر این مرکز افزوده شد.";
                $messageType = 'success';
            } else {
                $message = 'خطا در افزودن همکار به مرکز.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'unlink_doctor') {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        if ($doctorId > 0) {
            $del = $pdo->prepare("DELETE FROM organization_doctors WHERE organization_id = ? AND doctor_id = ?");
            if ($del->execute([$orgId, $doctorId])) {
                $message = 'همکار از کادر این مرکز حذف گردید.';
                $messageType = 'success';
            }
        }
    }
}

// Role filter from query
$activeRole = $_GET['role'] ?? 'all';
$affiliatedDoctors = $orgService->getDoctors($orgId, $activeRole);

// Fetch all available specialists in system for selection
$allDoctors = $pdo->query("SELECT id, name, specialty, provider_type FROM doctors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Counts by role
$countAll = count($orgService->getDoctors($orgId, 'all'));
$countDocs = count($orgService->getDoctors($orgId, 'doctor'));
$countGroomers = count($orgService->getDoctors($orgId, 'groomer'));
$countSellers = count($orgService->getDoctors($orgId, 'seller')) + count($orgService->getDoctors($orgId, 'pharmacist'));
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-indigo-600 text-3xl">groups</span>
                <span>کادر پزشکان، گرومرها و پرسنل مرکز</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت تیم چندنقشی شامل دامپزشکان، گرومرها و آرایشگران پت، و مسئولان داروخانه و فروشگاه</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="shifts.php" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base text-indigo-600">schedule</span>
                <span>مدیریت تقویم شیفت‌ها</span>
            </a>

            <button onclick="document.getElementById('add-doctor-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">person_add</span>
                <span>افزودن همکار به کادر</span>
            </button>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Role Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-200">
        <a href="doctors.php?role=all" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= $activeRole === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-sm">group</span>
            <span>همه اعضای کادر (<?= $countAll ?>)</span>
        </a>

        <a href="doctors.php?role=doctor" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= $activeRole === 'doctor' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-sm text-indigo-600">stethoscope</span>
            <span>پزشکان و جراحان (<?= $countDocs ?>)</span>
        </a>

        <a href="doctors.php?role=groomer" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= $activeRole === 'groomer' ? 'bg-pink-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-sm text-pink-600">content_cut</span>
            <span>گرومرها و آرایشگران پت (<?= $countGroomers ?>)</span>
        </a>

        <a href="doctors.php?role=seller" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= $activeRole === 'seller' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-sm text-emerald-600">storefront</span>
            <span>فروشندگان و پرسنل داروخانه (<?= $countSellers ?>)</span>
        </a>
    </div>

    <!-- Add Staff Modal / Form -->
    <div id="add-doctor-modal" class="hidden bg-white p-6 rounded-3xl border border-indigo-200 shadow-xl animate-fade-in">
        <h2 class="text-sm font-black text-indigo-950 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-600">group_add</span>
            <span>افزودن همکار به کادر درمانی / گرومینگ / فروشگاه</span>
        </h2>

        <form method="POST" action="doctors.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="link_doctor">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب همکار از پلتفرم *</label>
                <select name="doctor_id" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="">-- انتخاب کنید --</option>
                    <?php foreach ($allDoctors as $doc): 
                        $docType = $doc['provider_type'] ?? 'doctor';
                        $prefix = ($docType === 'groomer') ? '✂️ گرومر: ' : '🩺 دکتر: ';
                    ?>
                        <option value="<?= $doc['id'] ?>">
                            <?= $prefix ?><?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialty']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نقش در این مرکز *</label>
                <select name="role_type" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                    <option value="doctor">🩺 دامپزشک / جراح متخصص</option>
                    <option value="groomer">✂️ گرومر و استایلیست آرایش پت</option>
                    <option value="seller">🛍️ کارشناس فروشگاه و ملزومات پت</option>
                    <option value="pharmacist">💊 مسئول فنی داروخانه دامپزشکی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">روزهای حضور در مرکز *</label>
                <input type="text" name="working_days" required value="شنبه تا چهارشنبه" placeholder="مثال: شنبه، دوشنبه، چهارشنبه" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ساعات شیفت فعالیت *</label>
                <input type="text" name="working_hours" required value="۱۶:۰۰ الی ۲۱:۰۰" placeholder="مثال: ۰۹:۰۰ الی ۱۴:۰۰" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="flex items-center gap-2 sm:col-span-2 pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_head_physician" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-black text-slate-800">سرپرست بخش / مدیر کادر درمانی یا گرومینگ</span>
                </label>
            </div>

            <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('add-doctor-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                    ثبت در کادر پرسنل
                </button>
            </div>
        </form>
    </div>

    <!-- Staff Roster Cards Grid -->
    <?php if (empty($affiliatedDoctors)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-4xl">groups</span>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">همکاری با این فیلتر در کادر مرکز یافت نشد</h3>
            <p class="text-xs text-slate-500 mb-4">برای افزودن پزشک، گرومر یا فروشنده به کادر مرکز، روی دکمه «افزودن همکار به کادر» کلیک کنید.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($affiliatedDoctors as $doc): 
                $rType = $doc['role_type'] ?? 'doctor';
                $isGr = ($rType === 'groomer');
                $isSel = in_array($rType, ['seller', 'pharmacist']);
            ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div class="space-y-3.5">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl <?= $isGr ? 'bg-pink-50 text-pink-600' : ($isSel ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') ?> flex items-center justify-center font-bold text-lg shrink-0">
                            <span class="material-symbols-outlined"><?= $isGr ? 'content_cut' : ($isSel ? 'storefront' : 'stethoscope') ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h3 class="font-black text-slate-900 text-sm truncate"><?= htmlspecialchars($doc['name']) ?></h3>
                                <?php if (!empty($doc['is_head_physician'])): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">سرپرست بخش</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs <?= $isGr ? 'text-pink-600' : ($isSel ? 'text-emerald-600' : 'text-indigo-600') ?> font-bold block mt-0.5 truncate"><?= htmlspecialchars($doc['specialty']) ?></span>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded-md text-[10px] font-bold <?= $isGr ? 'bg-pink-50 text-pink-700' : ($isSel ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700') ?>">
                                <?= $isGr ? '✂️ گرومر و آرایشگر' : ($isSel ? '🛍️ کادر داروخانه و فروشگاه' : '🩺 پزشک درمانگر') ?>
                            </span>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl text-xs text-slate-600 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">روزهای حضور:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_days'] ?? 'همه روزه') ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">ساعت شیفت:</span>
                            <span class="font-bold text-slate-800 font-mono"><?= htmlspecialchars($doc['working_hours'] ?? 'عصر') ?></span>
                        </div>
                        <?php if (!empty($doc['price'])): ?>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                            <span class="text-slate-400">تعرفه پایه:</span>
                            <span class="font-bold text-indigo-700 font-mono"><?= number_format($doc['price']) ?> تومان</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="shifts.php?doctor_id=<?= (int)$doc['id'] ?>" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        <span>تنظیم شیفت</span>
                    </a>

                    <form method="POST" action="doctors.php" onsubmit="return confirm('آیا از حذف این همکار از کادر مرکز اطمینان دارید؟')">
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
