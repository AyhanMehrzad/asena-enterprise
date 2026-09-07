<?php
require_once 'includes/db.php';
if (!Feature::has('clinic_booking')) {
    header('Location: index.php');
    exit;
}
require_once 'includes/header.php';

// Fetch doctors
try {
    $stmt = $pdo->query("SELECT * FROM doctors ORDER BY rating DESC");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $doctors = [];
}

// Fetch user's pet data if logged in
$user_pets = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, race FROM user_pets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // ignore
    }
}

// Fetch booked and blocked slots for the next 14 days
$booked_slots = [];
try {
    $stmt = $pdo->query("SELECT doctor_id, appointment_date, appointment_time FROM appointments WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND status != 'cancelled'");
    $appts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($appts as $appt) {
        $docId = $appt['doctor_id'];
        $date = $appt['appointment_date'];
        $time = substr($appt['appointment_time'], 0, 5); // '09:00'
        if (!isset($booked_slots[$docId])) {
            $booked_slots[$docId] = [];
        }
        if (!isset($booked_slots[$docId][$date])) {
            $booked_slots[$docId][$date] = [];
        }
        $booked_slots[$docId][$date][] = $time;
    }

    // Merge doctor blocked slots (e.g. phone bookings, surgical blocks, leaves)
    $bStmt = $pdo->query("SELECT doctor_id, block_date, start_time, end_time FROM doctor_blocked_slots WHERE block_date >= CURDATE() AND block_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)");
    $blocks = $bStmt->fetchAll(PDO::FETCH_ASSOC);
    $allPossibleSlots = ['08:00', '08:45', '09:00', '09:30', '09:45', '10:00', '10:15', '10:30', '11:00', '11:15', '11:45', '12:00', '12:30', '13:00', '16:00', '16:30', '16:45', '17:00', '17:30', '18:00', '18:15', '18:30', '19:00', '19:30', '19:45', '20:00', '20:30', '21:00'];
    foreach ($blocks as $blk) {
        $docId = $blk['doctor_id'];
        $date = $blk['block_date'];
        $sTime = substr($blk['start_time'] ?? '00:00', 0, 5);
        $eTime = substr($blk['end_time'] ?? '23:59', 0, 5);
        
        if (!isset($booked_slots[$docId])) {
            $booked_slots[$docId] = [];
        }
        if (!isset($booked_slots[$docId][$date])) {
            $booked_slots[$docId][$date] = [];
        }

        foreach ($allPossibleSlots as $slot) {
            if ($sTime === '00:00' && $eTime === '23:59') {
                $booked_slots[$docId][$date][] = $slot;
            } elseif ($slot >= $sTime && $slot <= $eTime) {
                $booked_slots[$docId][$date][] = $slot;
            } elseif ($slot === $sTime) {
                $booked_slots[$docId][$date][] = $slot;
            }
        }
        $booked_slots[$docId][$date] = array_values(array_unique($booked_slots[$docId][$date]));
    }
} catch (PDOException $e) {
    // ignore
}
$booked_slots_json = json_encode($booked_slots);
?>

<!-- Include page-specific styles -->
<link rel="stylesheet" href="assets/css/booking.css">
<style>
    /* Add specific styles for selected states */
    .doctor-card.selected {
        border-color: #fd8100 !important;
        background-color: #f9f9f9 !important;
    }
    .date-card.selected {
        border-color: #fd8100 !important;
        background-color: #eeeeee !important;
    }
    .time-btn.selected {
        background-color: #002d72 !important;
        color: white !important;
        border-color: #002d72 !important;
    }
</style>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[70vh]">
    <!-- Breadcrumb -->
    <div class="text-label-sm text-on-surface-variant mb-6">
        <a href="index.php" class="hover:underline">خانه</a> > 
        <a href="booking.php" class="text-on-surface">رزرو نوبت</a>
    </div>

    <?php if(isset($_SESSION['booking_error'])): ?>
        <div class="bg-error/10 text-error p-4 rounded-xl mb-6 text-body-md font-bold text-center border border-error/20">
            <?php 
                echo htmlspecialchars($_SESSION['booking_error']); 
                unset($_SESSION['booking_error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Progressive Stepper -->
    <div class="flex items-center justify-center mb-12">
        <div class="flex items-center w-full max-w-3xl">
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center font-bold z-10">۱</div>
                <span class="mt-2 text-label-lg font-label-lg text-primary">انتخاب پزشک</span>
            </div>
            <div class="flex-auto border-t-2 border-primary-container mx-2 -mt-7"></div>
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold z-10" id="step-2-indicator">۲</div>
                <span class="mt-2 text-label-lg font-label-lg text-on-surface-variant" id="step-2-text">انتخاب زمان</span>
            </div>
            <div class="flex-auto border-t-2 border-surface-variant mx-2 -mt-7"></div>
            <div class="flex flex-col items-center flex-1 relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold z-10" id="step-3-indicator">۳</div>
                <span class="mt-2 text-label-lg font-label-lg text-on-surface-variant" id="step-3-text">تأیید نهایی</span>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <form action="actions/booking_action.php" method="POST" id="booking-form" class="flex flex-col lg:flex-row-reverse gap-8">
        <?php echo csrf_field(); ?>
        
        <!-- Hidden Inputs to store selections -->
        <input type="hidden" name="doctor_id" id="input_doctor_id" value="">
        <input type="hidden" name="appointment_date" id="input_date" value="">
        <input type="hidden" name="appointment_time" id="input_time" value="">

        <!-- Left Side: Main Selection -->
        <div class="flex-grow space-y-10 min-w-0">
            <!-- Doctor & Specialist Selection Section -->
            <section>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">health_and_safety</span>
                            <span>۱. انتخاب متخصص (پزشک یا گرومر)</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">دامپزشکان عمومی، متخصصان جراحی، و آرایشگران حرفه‌ای پت (گرومینگ و اسپا)</p>
                    </div>
                </div>

                <!-- Live Search & Category Filter Toolbar -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm mb-6 space-y-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                        <input type="text" id="specialist-search" oninput="applySpecialistFilters()" 
                               placeholder="جستجوی نام پزشک یا گرومر، تخصص، کلینیک یا خدمات (مثلاً: آرایشگر، ارتوپدی، گره‌زدایی، اورژانس)..."
                               class="w-full h-12 pr-11 pl-10 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-xs font-medium bg-slate-50 focus:bg-white transition-all">
                        <button type="button" onclick="document.getElementById('specialist-search').value=''; applySpecialistFilters();" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined text-lg">cancel</span>
                        </button>
                    </div>

                    <!-- Filter Tabs / Chips -->
                    <div class="flex items-center justify-between gap-2 flex-wrap pt-1 border-t border-slate-100">
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1" id="filter-tabs">
                            <button type="button" onclick="setSpecialistFilter('all', this)" class="specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-indigo-600 text-white shadow-sm flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">view_agenda</span>
                                <span>همه متخصصین</span>
                            </button>
                            <button type="button" onclick="setSpecialistFilter('doctor', this)" class="specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-indigo-600">stethoscope</span>
                                <span>دامپزشکان و جراحان</span>
                            </button>
                            <button type="button" onclick="setSpecialistFilter('groomer', this)" class="specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-pink-600">content_cut</span>
                                <span>گرومرها و آرایشگران پت</span>
                            </button>
                            <button type="button" onclick="setSpecialistFilter('emergency', this)" class="specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-rose-600">emergency</span>
                                <span>اورژانس شبانه‌روزی</span>
                            </button>
                        </div>

                        <span id="matching-count-badge" class="text-[11px] font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                            <?= count($doctors) ?> متخصص آماده نوبت‌دهی
                        </span>
                    </div>
                </div>

                <!-- No Results State -->
                <div id="no-specialists-found" class="hidden text-center py-12 bg-white rounded-2xl border border-dashed border-slate-300 p-8 space-y-2">
                    <span class="material-symbols-outlined text-4xl text-slate-400">search_off</span>
                    <h3 class="text-sm font-bold text-slate-700">متخصصی با این مشخصات یافت نشد</h3>
                    <p class="text-xs text-slate-400">لطفاً عبارت دیگری را جستجو کرده یا فیلتر دسته‌بندی را تغییر دهید.</p>
                </div>

                <!-- Doctors & Groomers Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[640px] overflow-y-auto pr-2 custom-scrollbar" id="doctors-list">
                    
                    <?php foreach($doctors as $index => $doctor): 
                        $isGroomer = ($doctor['provider_type'] ?? '') === 'groomer';
                        $isEmergency = !empty($doctor['is_emergency']);
                    ?>
                    <div class="doctor-card w-full bg-white border border-slate-200 rounded-2xl p-4 hover:shadow-xl hover:border-indigo-300 transition-all duration-300 group cursor-pointer hover:-translate-y-1 relative overflow-hidden flex flex-col justify-between"
                         data-id="<?php echo $doctor['id']; ?>"
                         data-name="<?php echo htmlspecialchars($doctor['name']); ?>"
                         data-type="<?php echo htmlspecialchars($doctor['provider_type'] ?? 'doctor'); ?>"
                         data-emergency="<?php echo $isEmergency ? '1' : '0'; ?>"
                         data-clinic="<?php echo htmlspecialchars($doctor['clinic_name'] ?? 'مرکز تخصصی آسنا'); ?>"
                         data-image="<?php echo htmlspecialchars($doctor['image_url'] ?: 'assets/images/presentation-dog.jpg'); ?>"
                         data-price="<?php echo $doctor['price']; ?>"
                         data-specialty="<?php echo htmlspecialchars($doctor['specialty']); ?>"
                         data-bio="<?php echo htmlspecialchars($doctor['bio'] ?? ''); ?>"
                         data-schedule="<?php echo htmlspecialchars($doctor['schedule_info'] ?? '{}'); ?>"
                         data-services="<?php echo htmlspecialchars($doctor['services_json'] ?? '[]'); ?>"
                         data-tags="<?php echo htmlspecialchars($doctor['tags'] ?? ''); ?>"
                         onclick="selectDoctor(this)">
                        
                        <div>
                            <div class="relative mb-3.5 overflow-hidden rounded-xl">
                                <img class="w-full h-44 object-cover transform group-hover:scale-105 transition-transform duration-500" src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'assets/images/presentation-dog.jpg'); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>"/>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                
                                <!-- Role Badge -->
                                <div class="absolute top-2 right-2">
                                    <?php if ($isEmergency): ?>
                                        <span class="bg-rose-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full flex items-center gap-1 shadow-md">
                                            <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span>
                                            اورژانس ۲۴/۷
                                        </span>
                                    <?php elseif ($isGroomer): ?>
                                        <span class="bg-pink-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full flex items-center gap-1 shadow-md">
                                            <span class="material-symbols-outlined text-xs">content_cut</span>
                                            گرومر و آرایشگر پت
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-indigo-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full flex items-center gap-1 shadow-md">
                                            <span class="material-symbols-outlined text-xs">stethoscope</span>
                                            پزشک متخصص
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Clinic Badge -->
                                <?php if (!empty($doctor['clinic_name'])): ?>
                                    <div class="absolute bottom-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-lg flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs text-sky-400">local_hospital</span>
                                        <span><?= htmlspecialchars($doctor['clinic_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-1.5">
                                <h3 class="text-base font-black text-slate-800 group-hover:text-indigo-600 transition-colors"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                                <p class="text-xs font-bold <?= $isGroomer ? 'text-pink-700 bg-pink-50' : 'text-indigo-700 bg-indigo-50' ?> inline-block px-2.5 py-0.5 rounded-lg"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                
                                <?php if(!empty($doctor['bio'])): ?>
                                    <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed mt-1">
                                        <?= htmlspecialchars($doctor['bio']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if(!empty($doctor['tags'])): ?>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    <?php foreach(array_slice(explode(',', $doctor['tags']), 0, 3) as $tg): ?>
                                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md font-medium">#<?php echo trim(htmlspecialchars($tg)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between pt-2">
                                    <div class="flex items-center gap-1 text-amber-500">
                                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                        <span class="text-xs font-black text-slate-800"><?php echo $doctor['rating']; ?></span>
                                        <span class="text-[11px] text-slate-400 mr-0.5">
                                            (<?php echo $doctor['review_count']; ?> نظر)
                                        </span>
                                    </div>

                                    <div class="text-xs font-black text-indigo-700 font-mono">
                                        <?= number_format($doctor['price']) ?> <span class="text-[10px] text-slate-400 font-normal">تومان</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="w-full mt-4 border-2 border-indigo-100 bg-indigo-50/60 text-indigo-700 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-600 hover:border-indigo-600 hover:text-white transition-all shadow-sm select-btn flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-sm"><?= $isGroomer ? 'content_cut' : 'event_available' ?></span>
                            <span><?= $isGroomer ? 'انتخاب گرومر و رزرو اصلاح' : 'انتخاب پزشک و رزرو نوبت' ?></span>
                        </button>
                    </div>
                    <?php endforeach; ?>

                </div>
            </section>
            
            <!-- Scheduling Interface -->
            <section class="space-y-6">
                <h2 class="text-title-lg font-title-lg text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-500">calendar_month</span>
                    ۲. انتخاب تاریخ و زمان
                </h2>
                
                <!-- Date Picker -->
                <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar" id="dates-list">
                    <div class="text-on-surface-variant text-sm py-4 px-2">لطفاً ابتدا یک پزشک را انتخاب کنید تا روزهای حضور نمایش داده شود.</div>
                </div>
                
                <!-- Time Slots Grid -->
                <div class="space-y-4 hidden" id="times-list">
                    <!-- Dynamic time slots will be generated here by JS -->
                </div>
            </section>

            <!-- Pet Information & Purpose Section -->
            <section class="space-y-6 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/50">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-title-lg font-title-lg text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">pets</span>
                        اطلاعات بیمار و علت مراجعه
                    </h2>
                </div>
                <p class="text-body-md text-on-surface-variant mb-4">لطفاً مشخصات حیوان و علت مراجعه به پزشک را مشخص کنید.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if(count($user_pets) > 0): ?>
                    <div class="col-span-1 md:col-span-2 space-y-2 mb-2">
                        <div class="flex items-center justify-between">
                            <label class="text-label-md font-bold text-on-surface-variant">انتخاب از حیوانات ثبت شده شما</label>
                            <a href="profile.php#pets-section" target="_blank" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <span>مدیریت حیوانات خانگی در پروفایل</span>
                            </a>
                        </div>
                        <select class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container bg-white text-sm" onchange="if(this.value){ const p = JSON.parse(this.value); document.getElementById('pet_type').value = p.type; document.getElementById('pet_race').value = p.race; checkFormCompleteness(); }">
                            <option value="">-- انتخاب کنید یا اطلاعات را به صورت دستی وارد کنید --</option>
                            <?php foreach($user_pets as $pet): ?>
                                <option value='<?php echo json_encode(["type" => $pet["type"], "race" => $pet["race"]]); ?>'><?php echo htmlspecialchars($pet['name'] . ' (' . $pet['type'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="col-span-1 md:col-span-2 p-3.5 rounded-2xl bg-blue-50/60 border border-blue-200/60 flex items-center justify-between text-xs mb-2">
                        <div class="flex items-center gap-2 text-slate-700">
                            <span class="material-symbols-outlined text-primary text-base">pets</span>
                            <span>حیوان خانگی در پروفایل خود ثبت نکرده‌اید؟</span>
                        </div>
                        <a href="profile.php#pets-section" target="_blank" class="px-3 py-1.5 rounded-xl bg-primary text-white font-bold hover:bg-primary-hover transition-colors flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-outlined text-sm">add_circle</span>
                            <span>ثبت و پرونده‌سازی در پروفایل</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant">نوع حیوان</label>
                        <div class="relative">
                            <select name="pet_type" id="pet_type" required class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors pr-4 pl-10" onchange="checkFormCompleteness()">
                                <option value="">انتخاب کنید...</option>
                                <option value="سگ">سگ</option>
                                <option value="گربه">گربه</option>
                                <option value="پرنده">پرنده</option>
                                <option value="جونده">جونده (خرگوش، همستر و...)</option>
                                <option value="خزنده">خزنده</option>
                                <option value="سایر">سایر</option>
                            </select>
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant">نژاد (اختیاری)</label>
                        <input type="text" name="pet_race" id="pet_race" value="" placeholder="مثال: پرشین، ژرمن و..."
                               class="w-full h-12 px-4 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors" />
                    </div>

                    <!-- Purpose of Visit -->
                    <div class="col-span-1 md:col-span-2 space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant flex items-center gap-1">
                            <span class="material-symbols-outlined text-primary text-sm">medical_services</span>
                            علت و هدف مراجعه (سرویس مورد نظر)
                        </label>
                        <div class="relative">
                            <select name="visit_purpose" id="visit_purpose" class="w-full h-12 px-4 appearance-none rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors pr-4 pl-10">
                                <option value="معاینه عمومی و چکاپ دوره ای">معاینه عمومی و چکاپ دوره ای</option>
                                <option value="واکسیناسیون و انگل‌زدایی جامع">واکسیناسیون و انگل‌زدایی جامع</option>
                                <option value="دندانپزشکی و جرم‌گیری تخصصی">دندانپزشکی و جرم‌گیری تخصصی</option>
                                <option value="مشاوره و جراحی‌های تخصصی">مشاوره و جراحی‌های تخصصی</option>
                                <option value="مشاوره تغذیه، رشد و رژیم درمانی">مشاوره تغذیه، رشد و رژیم درمانی</option>
                                <option value="کاشت میکروچیپ و صدور شناسنامه بین‌المللی">کاشت میکروچیپ و صدور شناسنامه بین‌المللی</option>
                            </select>
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <!-- Initial Pet Symptoms / Notes -->
                    <div class="col-span-1 md:col-span-2 space-y-2">
                        <label class="text-label-md font-bold text-on-surface-variant flex items-center gap-1">
                            <span class="material-symbols-outlined text-primary text-sm">edit_note</span>
                            توضیحات و شرح حال اولیه پت (اختیاری)
                        </label>
                        <textarea name="pet_notes" id="pet_notes" rows="2" placeholder="اگر حیوان خانگی شما علائم خاصی دارد یا نکته‌ای برای پزشک دارید بنویسید..." class="w-full p-3 rounded-lg border border-outline-variant focus:border-primary-container focus:ring-1 focus:ring-primary-container bg-white text-sm text-on-surface transition-colors resize-none outline-none"></textarea>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Right Side: Sticky Summary Sidebar -->
        <aside class="w-full lg:w-[400px] shrink-0">
            <div class="sticky top-28 bg-white rounded-3xl border border-slate-200 shadow-2xl shadow-indigo-900/5 space-y-7 p-7 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-full h-2 bg-gradient-to-r from-indigo-500 via-pink-500 to-amber-500"></div>
                <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-pink-500 text-2xl">receipt_long</span>
                    <span>خلاصه رزرو نوبت</span>
                </h2>

                <div class="space-y-6">
                    <!-- Selected Doctor/Groomer Summary Card -->
                    <div id="summary-doctor" class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-100 rounded-2xl opacity-50 transition-all duration-300 group">
                        <img id="summary-doctor-img" class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-md transition-transform group-hover:scale-105" src="assets/images/presentation-dog.jpg" alt="متخصص"/>
                        <div class="min-w-0 flex-1">
                            <p id="summary-role-label" class="text-[11px] font-bold text-slate-400 mb-0.5">متخصص انتخابی</p>
                            <h4 id="summary-doctor-name" class="text-base font-black text-slate-900 truncate">متخصص را انتخاب کنید</h4>
                            <span id="summary-doctor-spec" class="text-xs text-indigo-600 font-bold block truncate">---</span>
                        </div>
                    </div>
                    
                    <!-- Details List -->
                    <div class="space-y-3.5 text-xs">
                        <div class="flex justify-between items-center py-1">
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="material-symbols-outlined text-base text-indigo-500">calendar_today</span>
                                <span>تاریخ مراجعه</span>
                            </div>
                            <span id="summary-date" class="text-slate-800 font-bold">انتخاب نشده</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="material-symbols-outlined text-base text-indigo-500">schedule</span>
                                <span>ساعت شیفت</span>
                            </div>
                            <span id="summary-time" class="text-slate-800 font-bold">انتخاب نشده</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="material-symbols-outlined text-base text-indigo-500">location_on</span>
                                <span>مرکز / سالن</span>
                            </div>
                            <span id="summary-clinic" class="text-slate-800 font-bold truncate max-w-[200px]">شعبه مرکزی آسنا</span>
                        </div>
                    </div>
                    
                    <!-- Transparent Fee & Platform Interest Breakdown -->
                    <div class="border-t border-slate-200 pt-5 space-y-3">
                        <div class="bg-indigo-50/60 p-4 rounded-2xl border border-indigo-100/80 space-y-2.5">
                            <div class="flex justify-between items-center text-xs text-slate-600">
                                <span>تعرفه پایه خدمت / ویزیت:</span>
                                <span id="summary-base-fee" class="font-mono font-bold text-slate-800">--- تومان</span>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-emerald-800 bg-emerald-100/60 px-2.5 py-1.5 rounded-xl">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                                    <span>سهم پلتفرم (۵٪ تضمین و پیامک):</span>
                                </span>
                                <span id="summary-commission" class="font-mono font-black text-emerald-900">شامل در تعرفه</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-indigo-100/80">
                                <span class="text-sm font-black text-slate-900">مبلغ نهایی پرداخت:</span>
                                <div class="text-right flex items-baseline gap-1">
                                    <span id="summary-price" class="text-xl font-black text-indigo-700 tracking-tight">---</span>
                                    <span class="text-xs font-bold text-slate-500">تومان</span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" id="submit-btn" disabled class="w-full bg-slate-100 text-slate-400 py-3.5 rounded-xl text-base font-bold transition-all duration-300 flex justify-center items-center gap-2 cursor-not-allowed">
                            لطفا فرم را تکمیل کنید
                        </button>
                        
                        <div class="flex items-center justify-center gap-2 text-[11px] text-slate-400 font-medium">
                            <span class="material-symbols-outlined text-sm text-emerald-500">check_circle</span>
                            <span>امکان لغو رایگان تا ۲۴ ساعت قبل از موعد</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </form>
</main>

<script>
    // Global Booked Slots from PHP
    const bookedSlots = <?php echo $booked_slots_json; ?>;
    
    // Booking interactive state
    let selectedDoctorId = null;
    let selectedDoctorSchedule = null;
    let selectedDate = null;
    let selectedTime = null;
    let currentCategoryFilter = 'all';

    const daysMap = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    const daysFa = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

    // Category Filter Handler
    function setSpecialistFilter(category, btn) {
        currentCategoryFilter = category;
        document.querySelectorAll('.specialist-filter-btn').forEach(b => {
            b.className = "specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 flex items-center gap-1.5";
        });
        btn.className = "specialist-filter-btn px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-indigo-600 text-white shadow-sm flex items-center gap-1.5";
        applySpecialistFilters();
    }

    // Live Search and Category Filter combined
    function applySpecialistFilters() {
        const query = (document.getElementById('specialist-search').value || '').trim().toLowerCase();
        const cards = document.querySelectorAll('.doctor-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const specialty = (card.dataset.specialty || '').toLowerCase();
            const clinic = (card.dataset.clinic || '').toLowerCase();
            const bio = (card.dataset.bio || '').toLowerCase();
            const tags = (card.dataset.tags || '').toLowerCase();
            const type = (card.dataset.type || 'doctor').toLowerCase();
            const isEmergency = card.dataset.emergency === '1';

            // Match query
            const matchesQuery = !query || name.includes(query) || specialty.includes(query) || clinic.includes(query) || bio.includes(query) || tags.includes(query);

            // Match category
            let matchesCategory = true;
            if (currentCategoryFilter === 'doctor') {
                matchesCategory = (type === 'doctor');
            } else if (currentCategoryFilter === 'groomer') {
                matchesCategory = (type === 'groomer');
            } else if (currentCategoryFilter === 'emergency') {
                matchesCategory = isEmergency;
            }

            if (matchesQuery && matchesCategory) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // Toggle No Results View
        const noResults = document.getElementById('no-specialists-found');
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }

        // Update badge
        const badge = document.getElementById('matching-count-badge');
        if (badge) {
            badge.textContent = visibleCount + ' متخصص آماده نوبت‌دهی';
        }
    }

    function selectDoctor(card) {
        // Reset all
        document.querySelectorAll('.doctor-card').forEach(c => {
            c.classList.remove('selected', 'border-indigo-600', 'ring-2', 'ring-indigo-500/30');
            const btn = c.querySelector('.select-btn');
            const isGr = (c.dataset.type === 'groomer');
            btn.className = "w-full mt-4 border-2 border-indigo-100 bg-indigo-50/60 text-indigo-700 py-2.5 rounded-xl text-xs font-bold hover:bg-indigo-600 hover:border-indigo-600 hover:text-white transition-all shadow-sm select-btn flex items-center justify-center gap-1";
            btn.innerHTML = `<span class="material-symbols-outlined text-sm">${isGr ? 'content_cut' : 'event_available'}</span><span>${isGr ? 'انتخاب گرومر و رزرو اصلاح' : 'انتخاب پزشک و رزرو نوبت'}</span>`;
        });
        
        // Select this
        card.classList.add('selected', 'border-indigo-600', 'ring-2', 'ring-indigo-500/30');
        const activeBtn = card.querySelector('.select-btn');
        activeBtn.className = "w-full mt-4 bg-indigo-600 text-white py-2.5 rounded-xl text-xs font-bold transition-all shadow-md select-btn flex items-center justify-center gap-1";
        activeBtn.innerHTML = `<span class="material-symbols-outlined text-sm">check_circle</span><span>انتخاب شد</span>`;
        
        selectedDoctorId = card.dataset.id;
        document.getElementById('input_doctor_id').value = selectedDoctorId;
        
        // Parse Schedule
        try {
            selectedDoctorSchedule = JSON.parse(card.dataset.schedule || "{}");
        } catch(e) {
            selectedDoctorSchedule = {};
        }
        
        // Update summary
        const isGroomer = (card.dataset.type === 'groomer');
        document.getElementById('summary-doctor').classList.remove('opacity-50');
        document.getElementById('summary-doctor-name').textContent = card.dataset.name;
        document.getElementById('summary-doctor-img').src = card.dataset.image;
        document.getElementById('summary-doctor-spec').textContent = card.dataset.specialty;
        document.getElementById('summary-role-label').textContent = isGroomer ? 'گرومر و استایلیست انتخابی' : 'پزشک متخصص انتخابی';
        document.getElementById('summary-clinic').textContent = card.dataset.clinic || 'مرکز تخصصی ونک';
        
        // Format fee breakdown
        const priceNum = parseInt(card.dataset.price, 10) || 0;
        const commNum = Math.round(priceNum * 0.05);
        document.getElementById('summary-base-fee').textContent = new Intl.NumberFormat('fa-IR').format(priceNum) + ' تومان';
        document.getElementById('summary-commission').textContent = new Intl.NumberFormat('fa-IR').format(commNum) + ' تومان (شامل در تعرفه)';
        document.getElementById('summary-price').textContent = new Intl.NumberFormat('fa-IR').format(priceNum);
        
        // Reset Date & Time
        selectedDate = null;
        selectedTime = null;
        document.getElementById('input_date').value = "";
        document.getElementById('input_time').value = "";
        document.getElementById('summary-date').textContent = "انتخاب نشده";
        document.getElementById('summary-time').textContent = "انتخاب نشده";
        
        // Parse Services & Populate visit_purpose dropdown intelligently
        const purposeSelect = document.getElementById('visit_purpose');
        if (purposeSelect) {
            purposeSelect.innerHTML = '';
            let services = [];
            try {
                services = JSON.parse(card.dataset.services || "[]");
            } catch(e) { services = []; }

            if (Array.isArray(services) && services.length > 0) {
                services.forEach(srv => {
                    const opt = document.createElement('option');
                    opt.value = srv.name || srv.title || srv;
                    opt.textContent = `${srv.name || srv.title || srv} ${srv.duration ? '(' + srv.duration + ')' : ''}`;
                    purposeSelect.appendChild(opt);
                });
            } else if (isGroomer) {
                const groomingDefaults = [
                    'کوپ فانتزی و آرایش قیچی ژورنالی (۶۰ دقیقه)',
                    'شستشوی نرم‌کننده، اسپا و حمام معطر (۴۵ دقیقه)',
                    'گره‌زدایی تخصصی بدون کچلی (۶۰ دقیقه)',
                    'کوتاهی ناخن، بهداشت گوش و تخلیه کیسه مقعدی (۲۰ دقیقه)',
                    'پکیج کامل گرومینگ، شستشو و اسپا VIP (۹۰ دقیقه)'
                ];
                groomingDefaults.forEach(srv => {
                    const opt = document.createElement('option');
                    opt.value = srv;
                    opt.textContent = srv;
                    purposeSelect.appendChild(opt);
                });
            } else {
                const medicalDefaults = [
                    'معاینه عمومی و چکاپ دوره ای (۳۰ دقیقه)',
                    'واکسیناسیون و انگل‌زدایی جامع (۲۰ دقیقه)',
                    'دندانپزشکی و جرم‌گیری تخصصی (۴۵ دقیقه)',
                    'مشاوره و جراحی‌های تخصصی (۶۰ دقیقه)',
                    'ویزیت اورژانسی و مراقبت‌های ویژه (فوری)',
                    'کاشت میکروچیپ و صدور شناسنامه بین‌المللی (۲۰ دقیقه)'
                ];
                medicalDefaults.forEach(srv => {
                    const opt = document.createElement('option');
                    opt.value = srv;
                    opt.textContent = srv;
                    purposeSelect.appendChild(opt);
                });
            }
        }
        
        renderDates();
        updateStepper();
        checkFormCompleteness();
    }

    function renderDates() {
        const datesList = document.getElementById('dates-list');
        const timesList = document.getElementById('times-list');
        datesList.innerHTML = '';
        timesList.innerHTML = '';
        timesList.classList.add('hidden');
        
        if (Object.keys(selectedDoctorSchedule).length === 0) {
            datesList.innerHTML = '<div class="text-on-surface-variant text-sm py-4 px-2">این پزشک هنوز برنامه حضور خود را ثبت نکرده است.</div>';
            return;
        }

        let addedCount = 0;
        let d = new Date();
        
        // Check next 14 days
        for (let i = 0; i < 14; i++) {
            d.setDate(d.getDate() + 1); // start from tomorrow
            let dayIndex = d.getDay();
            let dayKey = daysMap[dayIndex];
            
            if (selectedDoctorSchedule[dayKey]) {
                // Doctor is available on this day
                addedCount++;
                let displayDay = daysFa[dayIndex];
                let displayNum = d.getDate();
                let monthStr = (d.getMonth() + 1).toString().padStart(2, '0');
                let dayStr = d.getDate().toString().padStart(2, '0');
                let valueDate = d.getFullYear() + "-" + monthStr + "-" + dayStr; // YYYY-MM-DD format for DB
                
                let card = document.createElement('div');
                card.className = "date-card flex-shrink-0 w-20 h-24 glass-card rounded-lg flex flex-col items-center justify-center cursor-pointer border-2 border-transparent hover:bg-surface-container transition-colors";
                card.dataset.date = valueDate;
                card.dataset.daykey = dayKey;
                card.dataset.display = displayDay + "، " + displayNum;
                card.onclick = function() { selectDate(this); };
                
                card.innerHTML = `
                    <span class="text-label-sm font-label-sm text-on-surface-variant">${displayDay}</span>
                    <span class="text-headline-md font-headline-md text-on-surface">${displayNum}</span>
                `;
                datesList.appendChild(card);
            }
        }
        
        if (addedCount === 0) {
            datesList.innerHTML = '<div class="text-on-surface-variant text-sm py-4 px-2">پزشک در ۲ هفته آینده حضور ندارد.</div>';
        }
    }

    function selectDate(card) {
        document.querySelectorAll('.date-card').forEach(c => c.classList.remove('selected', 'border-b-4'));
        card.classList.add('selected', 'border-b-4');
        
        selectedDate = card.dataset.date;
        document.getElementById('input_date').value = selectedDate;
        document.getElementById('input_date').dispatchEvent(new Event('change'));
        
        document.getElementById('summary-date').textContent = card.dataset.display;
        document.getElementById('summary-date').classList.remove('text-on-surface-variant');
        
        // Reset Time
        selectedTime = null;
        document.getElementById('input_time').value = "";
        document.getElementById('summary-time').textContent = "انتخاب نشده";
        
        renderTimes(card.dataset.daykey, selectedDate);
        
        updateStepper();
        checkFormCompleteness();
    }

    function generateTimeSlots(start, end) {
        if (!start || !end) return [];
        let slots = [];
        let curr = new Date(`1970-01-01T${start}:00`);
        let endT = new Date(`1970-01-01T${end}:00`);
        
        while (curr < endT) {
            let h = curr.getHours().toString().padStart(2, '0');
            let m = curr.getMinutes().toString().padStart(2, '0');
            slots.push(`${h}:${m}`);
            curr.setMinutes(curr.getMinutes() + 45);
        }
        return slots;
    }

    function renderTimes(dayKey, dateStr) {
        const timesList = document.getElementById('times-list');
        timesList.innerHTML = '';
        timesList.classList.remove('hidden');
        
        let daySched = selectedDoctorSchedule[dayKey];
        if (!daySched) return;
        
        let morningSlots = (daySched.m_active !== false) ? generateTimeSlots(daySched.m_start, daySched.m_end) : [];
        let afternoonSlots = (daySched.a_active !== false) ? generateTimeSlots(daySched.a_start, daySched.a_end) : [];
        
        let bookedForDate = (bookedSlots[selectedDoctorId] && bookedSlots[selectedDoctorId][dateStr]) ? bookedSlots[selectedDoctorId][dateStr] : [];

        let buildSection = (title, icon, slots, label) => {
            if (slots.length === 0) return '';
            let html = `
                <div class="flex items-center gap-2 text-on-surface-variant ${title.includes('عصر') ? 'pt-4' : ''}">
                    <span class="material-symbols-outlined text-[20px]">${icon}</span>
                    <span class="text-label-lg font-label-lg">${title}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mt-2">
            `;
            
            slots.forEach(time => {
                let isBooked = bookedForDate.includes(time);
                if (isBooked) {
                    html += `<button type="button" class="py-2 px-2 rounded-lg bg-[#fd8100]/10 text-[#fd8100] border border-[#fd8100]/40 text-body-md font-body-md cursor-not-allowed text-center opacity-80 flex flex-col items-center justify-center gap-1" disabled>
                                <span>${time}</span>
                                <span class="text-[10px] font-bold">رزرو شده</span>
                             </button>`;
                } else {
                    html += `<button type="button" class="time-btn py-3 px-4 rounded-lg bg-white border border-outline-variant text-body-md font-body-md hover:border-primary transition-colors text-center flex items-center justify-center"
                                data-time="${time}" onclick="selectTime(this, '${time}', '${label}')">${time}</button>`;
                }
            });
            
            html += `</div>`;
            return html;
        };
        
        if (morningSlots.length > 0) {
            timesList.innerHTML += buildSection(`صبح (${daySched.m_start} - ${daySched.m_end})`, 'light_mode', morningSlots, 'صبح');
        } else if (daySched.m_active === false) {
            timesList.innerHTML += `<div class="text-on-surface-variant text-sm py-2 px-2 opacity-80"><span class="material-symbols-outlined text-[16px] align-text-bottom ml-1">info</span>پزشک در شیفت صبح حضور ندارد.</div>`;
        }

        if (afternoonSlots.length > 0) {
            timesList.innerHTML += buildSection(`بعد از ظهر (${daySched.a_start} - ${daySched.a_end})`, 'wb_sunny', afternoonSlots, 'عصر');
        } else if (daySched.a_active === false) {
            timesList.innerHTML += `<div class="text-on-surface-variant text-sm py-2 px-2 opacity-80"><span class="material-symbols-outlined text-[16px] align-text-bottom ml-1">info</span>پزشک در شیفت عصر حضور ندارد.</div>`;
        }
    }

    function selectTime(btn, timeValue, label) {
        document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        
        selectedTime = timeValue; // We'll store HH:MM in DB
        document.getElementById('input_time').value = selectedTime;
        
        document.getElementById('summary-time').textContent = timeValue + ' ' + label;
        document.getElementById('summary-time').classList.remove('text-on-surface-variant');
        
        updateStepper();
        checkFormCompleteness();
    }

    function updateStepper() {
        if (selectedDoctorId) {
            document.getElementById('step-2-indicator').classList.replace('bg-surface-container-high', 'bg-primary-container');
            document.getElementById('step-2-indicator').classList.replace('text-on-surface-variant', 'text-white');
            document.getElementById('step-2-text').classList.replace('text-on-surface-variant', 'text-primary');
        }
        if (selectedDoctorId && selectedDate && selectedTime) {
            document.getElementById('step-3-indicator').classList.replace('bg-surface-container-high', 'bg-primary-container');
            document.getElementById('step-3-indicator').classList.replace('text-on-surface-variant', 'text-white');
            document.getElementById('step-3-text').classList.replace('text-on-surface-variant', 'text-primary');
        }
    }

    function checkFormCompleteness() {
        const submitBtn = document.getElementById('submit-btn');
        const petType = document.getElementById('pet_type').value;
        
        if (selectedDoctorId && selectedDate && selectedTime && petType) {
            submitBtn.disabled = false;
            submitBtn.className = "w-full bg-[#f97316] text-white py-4 rounded-xl text-title-lg font-title-lg font-bold hover:bg-[#ea580c] transition-colors shadow-lg shadow-secondary-container/20 flex justify-center items-center gap-2";
            submitBtn.textContent = "تأیید و ادامه پرداخت";
        } else {
            submitBtn.disabled = true;
            submitBtn.className = "w-full bg-surface-container-high text-on-surface-variant py-4 rounded-xl text-title-lg font-title-lg font-bold transition-all flex justify-center items-center gap-2 cursor-not-allowed";
            submitBtn.textContent = "لطفا فرم را تکمیل کنید";
        }
    }

    <?php if (!empty($_GET['doctor_id'])): ?>
    window.addEventListener('DOMContentLoaded', () => {
        const targetDoc = document.querySelector(`.doctor-card[data-id="<?= (int)$_GET['doctor_id'] ?>"]`);
        if (targetDoc) {
            targetDoc.click();
            targetDoc.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    <?php endif; ?>
</script>

<script src="assets/js/booking.js"></script>

<?php include 'includes/footer.php'; ?>