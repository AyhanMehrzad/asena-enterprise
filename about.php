<?php
/**
 * ASENA Enterprise - About Us Page (صفحه رسمی درباره ما و معرفی پلتفرم آسنا)
 * Compliant with Iranian E-Commerce Development Center (Enamad / اینماد)
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'درباره ما | معرفی اکوسیستم جامع خدمات حیوانات خانگی آسنا';
$meta_description = 'آسنا پلتفرم یکپارچه سلامت، درمان، پت‌شاپ و مراقبت هوشمند از حیوانات خانگی در ایران. معرفی اهداف، تیم تخصصی، مجوزها و زیرساخت‌های فناوری.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-10">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">درباره ما</span>
        </nav>

        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-[#001a48] via-[#002d72] to-[#1e3a8a] text-white rounded-3xl p-8 sm:p-12 shadow-lg relative overflow-hidden">
            <div class="max-w-2xl space-y-4 relative z-10">
                <span class="inline-flex items-center gap-1.5 bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-bold px-3 py-1 rounded-full">
                    <span class="material-symbols-outlined text-xs">pets</span>
                    پیشگام فناوری‌های سلامت و دامپزشکی هوشمند در ایران
                </span>
                <h1 class="text-3xl sm:text-4xl font-black leading-tight">
                    آسنا؛ پل ارتباطی میان عاشقان پت، دامپزشکان و کلینیک‌های معتبر
                </h1>
                <p class="text-slate-200 text-sm leading-relaxed">
                    ما در آسنا باور داریم که حیوانات خانگی عضوی از خانواده ما هستند. هدف ما فراهم‌آوردن زیرساختی جامع، شفاف، امن و دانش‌بنیان برای ارائه بالاترین کیفیت خدمات درمانی، تغذیه استاندارد و آرامش خاطر سرپرستان پت در سراسر کشور است.
                </p>
            </div>
        </div>

        <!-- 3 Key Pillars -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">medical_services</span>
                </div>
                <h3 class="font-bold text-slate-900 text-base">شبکه تخصصی دامپزشکی</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    احراز هویت و همکاری با برترین بیمارستان‌های تخصصی ۲۴ ساعته، کلینیک‌ها، آزمایشگاه‌ها و متخصصین دارای پروانه معتبر نظام دامپزشکی.
                </p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">shield</span>
                </div>
                <h3 class="font-bold text-slate-900 text-base">حساب امانی و تضمین اصالت</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    حفظ وجوه پرداختی خریداران تا ۷ روز کاری طبق قانون تجارت الکترونیک، تضمین تاریخ انقضا و سلامت محصولات پت‌شاپ و داروخانه.
                </p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">volunteer_activism</span>
                </div>
                <h3 class="font-bold text-slate-900 text-base">مسئولیت اجتماعی و امداد</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    اختصاص بخشی از درآمد پلتفرم به درمان حیوانات آسیب‌دیده، واکسیناسیون رایگان پناهگاه‌ها و توسعه پویش‌های خیریه حمایتی.
                </p>
            </div>
        </div>

        <!-- Mission & Transparency -->
        <div class="bg-white p-8 sm:p-10 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">verified_user</span>
                شفافیت قانونی، مجوزها و الزامات صنفی
            </h2>
            <div class="space-y-4 text-xs text-slate-600 leading-relaxed">
                <p>
                    سامانه جامع آسنا تحت نظارت مراجع قانونی، مرکز توسعه تجارت الکترونیکی (اینماد) و منطبق با قانون تجارت الکترونیک جمهوری اسلامی ایران فعالیت می‌نماید. کلیه فرایندهای پرداخت از طریق درگاه‌های پرداخت متصل به سامانه شاپرک و تسویه‌های بین‌بانکی از بستر پایا و ساتنا بانک مرکزی جمهوری اسلامی ایران صورت می‌پذیرد.
                </p>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <span class="font-bold text-slate-900 block text-xs">نیاز به ارتباط یا استعلام رسمی دارید؟</span>
                        <span class="text-slate-500 text-[11px]">دفتر مرکزی آسنا در تهران همه روزه در ساعات کاری پذیرای شماست.</span>
                    </div>
                    <a href="contact.php" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs shadow-sm hover:bg-primary-light transition flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">phone</span>
                        <span>صفحه تماس و مشخصات پستی</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
