<?php
require_once 'includes/db.php';

$page_title = 'قوانین و شرایط استفاده از خدمات | سامانه جامع آسنا';
$meta_description = 'قوانین، شرایط و مقررات حاکم بر رزرو نوبت دامپزشکی، خرید محصولات پت‌شاپ، داروخانه و امانت‌داری مالی در سامانه جامع آسنا.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">قوانین و شرایط خدمات</span>
        </nav>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/80 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-accent via-primary-light to-primary"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <span class="material-symbols-outlined text-xs text-accent">gavel</span>
                        شرایط و ضوابط عمومی پلتفرم آسنا اینترپرایز
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-2">
                        قوانین و مقررات استفاده از خدمات
                    </h1>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">
                        استفاده از خدمات نوبت‌دهی کلینیک، پت‌شاپ آنلاین، خدمات دارویی و تله‌هلث آسنا، به منزله مطالعه و پذیرش کامل مفاد این سند توافق‌نامه است.
                    </p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 border border-accent/20">
                    <span class="material-symbols-outlined text-3xl">description</span>
                </div>
            </div>
        </div>

        <!-- Content Body -->
        <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-sm border border-slate-200/80 space-y-10 text-slate-700 leading-relaxed text-sm">
            
            <!-- Section 1 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۱</span>
                    <h2 class="text-lg font-bold text-slate-900">تعاریف و نقش‌های سامانه</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    در این توافق‌نامه اصطلاحات زیر در معانی مشخص‌شده به کار می‌روند:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>آسنا (ASENA):</b> پلتفرم جامع نرم‌افزاری و زیرساخت ارتباطی یکپارچه میان صاحبان پت، متخصصین دامپزشکی، کلینیک‌ها، داروخانه‌ها و فروشگاه‌ها.</li>
                    <li><b>کاربر / سرپرست پت:</b> هر شخص حقیقی که از طریق شماره همراه خود در سامانه عضو شده و اقدام به دریافت خدمات یا خرید کالا می‌نماید.</li>
                    <li><b>مراکز و متخصصین درمانی:</b> بیمارستان‌ها، کلینیک‌ها و دامپزشکان دارای پروانه فعالیت رسمی از سازمان نظام دامپزشکی کشور که هویت آنها در آسنا تایید شده است.</li>
                    <li><b>فروشندگان مجاز:</b> تامین‌کنندگان و پت‌شاپ‌های دارای مجوز صنفی که محصولات استاندارد و باکیفیت را در بستر مارکت‌پلیس آسنا عرضه می‌کنند.</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 2 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۲</span>
                    <h2 class="text-lg font-bold text-slate-900">ضوابط رزرو، لغو و استرداد نوبت‌های درمانی</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    جهت احترام به وقت متخصصین و سایر بیماران نیازمند به درمان:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>زمان حضور:</b> سرپرستان گرامی موظفند حداقل ۱۵ دقیقه پیش از ساعت رزرو شده در محل کلینیک حضور داشته باشند.</li>
                    <li><b>قوانین لغو نوبت:</b> لغو نوبت تا ۴ ساعت پیش از موعد مقرر، بدون کسر هزینه و با استرداد ۱۰۰٪ وجه به کیف‌پول کاربر انجام می‌شود. در صورت لغو در کمتر از ۴ ساعت، ۱۰٪ از مبلغ ویزیت بابت جبران خسارت نوبت مسدودشده کسر خواهد شد.</li>
                    <li><b>لغو توسط پزشک یا کلینیک:</b> در صورت بروز شرایط اورژانسی در مرکز، کلینیک موظف است زمان جایگزین تعیین کرده یا ۱۰۰٪ وجه را بلافاصله مسترد نماید.</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 3 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۳</span>
                    <h2 class="text-lg font-bold text-slate-900">امانت‌داری مالی ۷ روزه (Escrow) و بازگشت کالا</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    آسنا برای تضمین امنیت و اصالت کالاهای ارسالی، سیستم امانت‌داری وجه را پیاده‌سازی نموده است:
                </p>
                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 mr-11 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-xs text-amber-900">
                        <span class="material-symbols-outlined text-sm text-accent">verified</span>
                        مهلت تست و بازرسی ۷ روزه طبق قانون تجارت الکترونیک
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        مبلغ پرداختی شما پس از تحویل مرسوله پستی تا ۷ روز کاری در حساب امانی آسنا نزد بانک مرکزی نگهداری می‌شود. تنها پس از تایید سلامت کالا یا پایان مهلت ۷ روزه، وجه به حساب فروشنده تسویه خواهد شد.
                    </p>
                </div>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11 mt-3">
                    <li><b>کالاهای مشمول مرجوعی:</b> کالاهایی که دارای مغایرت با اطلاعات سایت، آسیب‌دیدگی فیزیکی در حین حمل‌ونقل یا تاریخ انقضای منقضی‌شده باشند.</li>
                    <li><b>اقلام غیرقابل مرجوعی:</b> مکمل‌های دارویی و غذاهای کنسروی بازشده یا مصرف‌شده به دلایل پروتکل‌های بهداشتی دامپزشکی قابل عودت نمی‌باشند.</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 4 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۴</span>
                    <h2 class="text-lg font-bold text-slate-900">مسئولیت‌های پزشکی و مشاوره‌ای</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    مشاوره‌های تله‌هلث و چت متنی راهنمای اولیه بوده و جایگزین اقدامات فوری و جراحی‌های اورژانسی بالینی نیستند. در شرایط تهدیدکننده حیات، انتقال سریع حیوان به نزدیک‌ترین بیمارستان دامپزشکی شبانه‌روزی الزامی است.
                </p>
            </section>

            <hr class="border-slate-100">

            <!-- Section 5 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۵</span>
                    <h2 class="text-lg font-bold text-slate-900">روش‌های ارسال، هزینه‌ها و بازه زمانی تحویل سفارش‌ها</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    ارسال مرسولات پت‌شاپ و داروخانه آنلاین آسنا از طریق درگاه‌های لجستیکی استاندارد (پستکس، پست پیشتاز جمهوری اسلامی ایران و ناوگان پیک درون‌شهری) انجام می‌گیرد:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>زمان پردازش:</b> سفارش‌های ثبت‌شده ظرف ۲۴ تا ۴۸ ساعت کاری پردازش و تحویل شرکت پست می‌گردند.</li>
                    <li><b>کد رهگیری:</b> پس از تحویل به پست، کد رهگیری ۲۴ رقمی پستی از طریق پیامک برای خریدار ارسال شده و در بخش «پیگیری سفارشات» پروفایل قابل استعلام است.</li>
                    <li><b>هزینه ارسال:</b> کرایه حمل بر مبنای وزن و مسافت طبق تعرفه مصوب پستی در پیش‌فاکتور محاسبه و شفاف درج می‌شود.</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 6 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۶</span>
                    <h2 class="text-lg font-bold text-slate-900">رویه ثبت، پیگیری و رسیدگی به شکایات (الزامات اینماد)</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    آسنا متعهد به پاسخگویی شفاف و رسیدگی سریع به درخواست‌ها و شکایات کاربران گرامی است:
                </p>
                <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 mr-11 space-y-2">
                    <p class="text-xs text-blue-900 leading-relaxed">
                        <b>نحوه ثبت شکایت:</b> کاربران می‌توانند شکایات خود را از طریق تیکت ۲۴ ساعته در پنل کاربری، تماس با تلفن پشتیبانی یا ارسال ایمیل ثبت فرمایند.
                    </p>
                    <p class="text-xs text-blue-800 leading-relaxed">
                        <b>مهلت رسیدگی:</b> واحد پشتیبانی و امور مشتریان حداکثر ظرف ۲۴ الی ۴۸ ساعت کاری با شاکی تماس حاصل نموده و موضوع را بررسی و رفع اثر می‌نماید. در صورت عدم حصول توافق، مراجع نظارتی و صنفی قانونی ذی‌صلاح مرجع داوری نهایی خواهند بود.
                    </p>
                </div>
            </section>

            <hr class="border-slate-100">

            <!-- Section 7 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs">۷</span>
                    <h2 class="text-lg font-bold text-slate-900">اطلاعات رسمی تماس و پشتیبانی</h2>
                </div>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>پشتیبانی تلفنی:</b> ۰۲۱-۹۱۰۰۰۰۰۰ (روزهای کاری از ساعت ۹ الی ۱۸)</li>
                    <li><b>ایمیل امور مشتریان:</b> support@asena.company</li>
                    <li><b>سامانه پشتیبانی آنلاین:</b> ۲۴ ساعته از طریق بخش «پشتیبانی و تیکت‌ها» در پنل کاربری</li>
                </ul>
            </section>

        </div>

        <!-- Back to Home Button -->
        <div class="text-center mt-8">
            <a href="index.php" class="inline-flex items-center gap-2 bg-primary text-white text-xs font-bold px-6 py-3 rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-light transition-all">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                <span>تایید و بازگشت به صفحه اصلی</span>
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
