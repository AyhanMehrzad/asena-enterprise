<?php
/**
 * ASENA Enterprise - About Page (صفحه رسمی درباره آسنا و معرفی پلتفرم)
 * Comprehensive introduction of ecosystem, mission, legal compliance, and strategic technology partners
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'درباره آسنا | معرفی پلتفرم هوشمند، مجوزها و شرکای راهبردی';
$meta_description = 'آسنا پلتفرم جامع سلامت، خدمات درمانی و پت‌شاپ آنلاین در ایران. معرفی ماموریت، مجوزهای قانونی، و همکاری راهبردی با شرکت مهندسین مشاور سما شهر خاوران.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50/70 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-12">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">درباره آسنا</span>
        </nav>

        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-[#001a48] via-[#002d72] to-[#1e3a8a] text-white rounded-3xl p-8 sm:p-12 shadow-xl relative overflow-hidden">
            <div class="absolute -left-12 -top-12 w-64 h-64 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -right-12 -bottom-12 w-72 h-72 bg-blue-400/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="max-w-3xl space-y-5 relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 text-xs font-bold px-3.5 py-1.5 rounded-full shadow-xs">
                    <span class="material-symbols-outlined text-sm text-[#fd8100]">verified</span>
                    <span>زیست‌بوم جامع سلامت و خدمات هوشمند حیوانات خانگی</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black leading-tight text-white tracking-tight">
                    آسنا؛ تلفیق تخصص سلامت با فناوری نوین
                </h1>
                <p class="text-slate-200 text-sm sm:text-base leading-relaxed font-normal">
                    ما در آسنا با باور به این‌که پت‌ها عضوی ارزشمند از خانواده‌ها هستند، سامانه‌ای هوشمند، شفاف و یکپارچه ایجاد کرده‌ایم تا دسترسی به خدمات کلینیک‌های دامپزشکی، مشاوره متخصصان، داروخانه تخصصی و ملزومات استاندارد با بالاترین کیفیت در سراسر کشور میسر گردد.
                </p>
                <div class="flex flex-wrap gap-4 pt-2">
                    <div class="flex items-center gap-2 text-xs text-slate-300 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                        <span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span>
                        <span>بیش از ۲۵,۰۰۰ سرپرست پت فعال</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-300 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                        <span class="material-symbols-outlined text-sm text-blue-400">local_hospital</span>
                        <span>شبکه سراسری کلینیک‌ها و دامپزشکان</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Core Pillars -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">medical_services</span>
                </div>
                <h3 class="font-black text-slate-900 text-base">شبکه تخصصی دامپزشکی</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    نظارت دقیق بر صلاحیت بالینی، احراز پروانه اشتغال سازمان نظام دامپزشکی کشور و ارتباط مستقیم با بهترین بیمارستان‌ها و مراکز اورژانس شبانه‌روزی.
                </p>
            </div>

            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">shield</span>
                </div>
                <h3 class="font-black text-slate-900 text-base">تضمین سلامت و حساب امانی</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    حفظ وجوه خریداران در حساب امانی تا ۷ روز پس از تحویل کالا، تضمین اصالت برندها، ارسال زنجیره سرد داروها و کنترل دوره‌ای استانداردهای تغذیه.
                </p>
            </div>

            <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-3 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">volunteer_activism</span>
                </div>
                <h3 class="font-black text-slate-900 text-base">مسئولیت اجتماعی و امداد</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    اختصاص بخشی از درآمد پلتفرم به پویش‌های درمان حیوانات آسیب‌دیده، واکسیناسیون رایگان پناهگاه‌ها و توسعه فرهنگ همزیستی مهربانانه در جامعه.
                </p>
            </div>
        </div>

        <!-- Strategic Technology & Infrastructure Partner Section (سما شهر خاوران) -->
        <div class="bg-gradient-to-br from-white via-slate-50 to-blue-50/40 rounded-3xl border border-slate-200/90 p-7 sm:p-10 shadow-sm space-y-8 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-200/80">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-[11px] font-black px-3 py-1 rounded-full border border-blue-200/60">
                        <span class="material-symbols-outlined text-xs">handshake</span>
                        <span>همکاری راهبردی و زیرساخت فناوری</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        پشتیبانی فنی و مهندسی داده با همراهی شرکت مهندسین مشاور «سما شهر خاوران»
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-2xl">
                        توسعه فناوری‌های زیرساختی، تحلیل داده‌های مکانی (GIS) و پایداری سرویس‌های کلان پلتفرم آسنا با بهره‌گیری از تجربه مهندسی شرکت سما شهر خاوران صورت می‌پذیرد.
                    </p>
                </div>

                <!-- Partner Outbound Link -->
                <div class="shrink-0 flex items-center">
                    <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="group inline-flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-[#001a48] text-white hover:bg-[#002d72] shadow-sm hover:shadow-md transition-all text-xs font-bold">
                        <span>مشاهده وب‌سایت سما شهر</span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-0.5 group-hover:-translate-y-0.5 transition-transform text-[#fd8100]">north_east</span>
                    </a>
                </div>
            </div>

            <!-- Bento Grid of Partner Details -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                
                <!-- Left/Main Bento: Partner Identity & Core Focus (7 cols) -->
                <div class="lg:col-span-7 bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-xs flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-[#001a48] p-2 flex items-center justify-center shadow-xs shrink-0">
                                    <img src="assets/images/partners/samashahr-logo.png" alt="شرکت مهندسین مشاور سما شهر" class="w-full h-auto object-contain">
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-sm sm:text-base">شرکت مهندسین مشاور سما شهر خاوران</h3>
                                    <div class="text-[11px] text-slate-500 font-medium">شماره ثبت: ۵۶۳۰۶ تبریز • شناسه ملی: ۱۴۰۱۱۴۲۵۵۷۸</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-emerald-200/60 shrink-0">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>شریک رسمی</span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            شرکت مهندسین مشاور سما شهر خاوران با سابقه طولانی در حوزه سیستم‌های جامع اطلاعات جغرافیایی (GIS)، شهرسازی، سامانه‌های هوشمند پایش و توسعه نرم‌افزارهای سازمانی مقیاس‌پذیر فعالیت دارد. در چارچوب این همکاری، آسنا از راهکارهای نوین مکانی این مجموعه در دایرکتوری و نقشه مراکز درمانی و اورژانس بهره می‌گیرد.
                        </p>
                    </div>

                    <!-- Synergy Highlights -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 text-right space-y-1">
                            <div class="flex items-center gap-1.5 text-blue-600">
                                <span class="material-symbols-outlined text-base">map</span>
                                <span class="text-xs font-black text-slate-900">سامانه‌های GIS</span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-tight">موقعیت‌یابی و تحلیل شعاع خدمت‌رسانی کلینیک‌ها</p>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 text-right space-y-1">
                            <div class="flex items-center gap-1.5 text-indigo-600">
                                <span class="material-symbols-outlined text-base">cloud_done</span>
                                <span class="text-xs font-black text-slate-900">پایداری سرور</span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-tight">نظارت بر تاب‌آوری و زیرساخت‌های پردازش ابری</p>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 text-right space-y-1">
                            <div class="flex items-center gap-1.5 text-amber-600">
                                <span class="material-symbols-outlined text-base">verified_user</span>
                                <span class="text-xs font-black text-slate-900">استاندارد نرم‌افزار</span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-tight">رعایت استانداردهای نظام صنفی و مهندسی داده</p>
                        </div>
                    </div>
                </div>

                <!-- Right Bento: Official Accreditation & Guild License (5 cols) -->
                <div class="lg:col-span-5 bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-xs flex flex-col justify-between space-y-5">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-base">badge</span>
                                مجوز نظام صنفی رایانه‌ای کشور
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono">شماره: ۱۴۰۱۰۶۰۸</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            پروانه رسمی فعالیت شرکت سما شهر خاوران از سازمان نظام صنفی رایانه‌ای استان آذربایجان شرقی به مدیریت مهندس جمال مهرزاد.
                        </p>
                    </div>

                    <!-- Certificate Preview Card with Modal Trigger -->
                    <div class="relative group cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-slate-100 hover:border-primary transition-all" onclick="openLicenseModal()">
                        <img src="assets/images/partners/samashahr-license.jpg" alt="گواهی نظام صنفی رایانه‌ای سما شهر خاوران" class="w-full h-40 object-cover object-top group-hover:scale-102 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex items-end p-3.5 opacity-90 group-hover:opacity-100 transition-opacity">
                            <div class="flex items-center justify-between w-full text-white">
                                <span class="text-[11px] font-bold flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-[#fd8100]">zoom_in</span>
                                    مشاهده گواهی و استعلام رسمی
                                </span>
                                <span class="text-[10px] bg-white/20 backdrop-blur-xs px-2 py-0.5 rounded-full font-mono">14010608</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500">
                        <span>تاریخ انقضای عضویت: ۱۴۰۶/۰۴/۰۱</span>
                        <button type="button" onclick="openLicenseModal()" class="text-primary hover:underline font-bold inline-flex items-center gap-1">
                            <span>بزرگ‌نمایی تصویر</span>
                            <span class="material-symbols-outlined text-xs">open_in_full</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Mission, Legal Trust & Transparency Section -->
        <div class="bg-white p-8 sm:p-10 rounded-3xl border border-slate-200/90 shadow-sm space-y-6">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">verified_user</span>
                شفافیت قانونی، مجوزها و الزامات صنفی
            </h2>
            <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <p>
                    سامانه جامع آسنا تحت نظارت مراجع قانونی، مرکز توسعه تجارت الکترونیکی (اینماد) و منطبق با قانون تجارت الکترونیک جمهوری اسلامی ایران فعالیت می‌نماید. کلیه فرایندهای پرداخت از طریق درگاه‌های پرداخت متصل به سامانه شاپرک بانک مرکزی و تسویه‌های بین‌بانکی از بستر شبکه پایا و ساتنا صورت می‌پذیرد.
                </p>
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="space-y-1 text-center sm:text-right">
                        <span class="font-bold text-slate-900 block text-xs sm:text-sm">نیاز به ارتباط رسمی یا استعلام دارید؟</span>
                        <span class="text-slate-500 text-[11px] sm:text-xs">همکاران ما در واحد روابط عمومی و پشتیبانی در ساعات کاری پاسخگوی شما هستند.</span>
                    </div>
                    <a href="contact.php" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs shadow-sm hover:bg-primary-light transition flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-sm">phone</span>
                        <span>تماس با ما و مشخصات رسمی</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- License Lightbox Modal -->
<div id="licenseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/75 backdrop-blur-sm p-4 transition-all duration-300 opacity-0 pointer-events-none" onclick="closeLicenseModal(event)">
    <div class="relative bg-white rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-100 transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">verified</span>
                <h3 class="font-black text-slate-900 text-sm sm:text-base">گواهی سازمان نظام صنفی رایانه‌ای - شرکت سما شهر خاوران</h3>
            </div>
            <button type="button" onclick="closeLicenseModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
        <div class="overflow-auto max-h-[75vh] flex justify-center rounded-2xl bg-slate-50 p-2 border border-slate-100">
            <img src="assets/images/partners/samashahr-license.jpg" alt="گواهی نظام صنفی رایانه‌ای شرکت سما شهر خاوران" class="w-full h-auto object-contain rounded-xl shadow-xs">
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 pt-1">
            <span>شماره مجوز نظام صنفی: <strong class="font-mono text-slate-800">14010608</strong> • شناسه ملی: <strong class="font-mono text-slate-800">14011425578</strong></span>
            <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline inline-flex items-center gap-1">
                <span>وب‌سایت رسمی سما شهر</span>
                <span class="material-symbols-outlined text-xs">open_in_new</span>
            </a>
        </div>
    </div>
</div>

<script>
function openLicenseModal() {
    const modal = document.getElementById('licenseModal');
    if (!modal) return;
    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex', 'opacity-100', 'pointer-events-auto');
    const panel = modal.querySelector('div');
    if (panel) {
        panel.classList.remove('scale-95');
        panel.classList.add('scale-100');
    }
}

function closeLicenseModal(e) {
    const modal = document.getElementById('licenseModal');
    if (!modal) return;
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLicenseModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
