<?php
require_once 'includes/db.php';

$page_title = 'سیاست حریم خصوصی و امنیت اطلاعات | سامانه جامع آسنا';
$meta_description = 'خط‌مشی حفظ حریم خصوصی، امنیت داده‌های پزشکی حیوانات خانگی و استانداردهای حفاظت از اطلاعات کاربران در پلتفرم جامع آسنا.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">سیاست حریم خصوصی و امنیت داده‌ها</span>
        </nav>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/80 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary via-primary-light to-accent"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        سند رسمی مصوب ۱۴۰۵ | انطباق با قوانین تجارت الکترونیک
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-2">
                        سیاست حفظ حریم خصوصی و امنیت اطلاعات
                    </h1>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">
                        در پلتفرم جامع آسنا (ASENA Enterprise)، حریم خصوصی شما و محرمانگی داده‌های پزشکی و پرونده‌های درمانی حیوانات خانگی، بالاترین اولویت زیرساختی و اخلاقی ماست.
                    </p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-primary/5 flex items-center justify-center text-primary flex-shrink-0 border border-primary/10">
                    <span class="material-symbols-outlined text-3xl">verified_user</span>
                </div>
            </div>
        </div>

        <!-- Content Body -->
        <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-sm border border-slate-200/80 space-y-10 text-slate-700 leading-relaxed text-sm">
            
            <!-- Section 1 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">۱</span>
                    <h2 class="text-lg font-bold text-slate-900">اطلاعاتی که از شما دریافت می‌کنیم</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    سامانه آسنا برای ارائه خدمات رزرو ویزیت کلینیک، ارسال سفارش‌های دارو و پت‌شاپ، و ایجاد پرونده دیجیتال سلامت حیوانات اطلاعات زیر را اخذ و ذخیره می‌کند:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>اطلاعات هویتی و تماسی:</b> نام و نام خانوادگی، شماره تلفن همراه (جهت احراز پیامکی OTP)، آدرس پستی دقیق و کد پستی جهت تحویل بسته‌های پستی.</li>
                    <li><b>شناسنامه و سوابق پزشکی پت:</b> نام حیوان، گونه، نژاد، سن، وزن، سابقه حساسیت‌های دارویی، گزارش‌های تشخیصی و تصاویر آزمایشگاهی بارگذاری‌شده.</li>
                    <li><b>داده‌های تراکنش مالی:</b> مبالغ پرداختی، شماره پیگیری بانکی (کد ارجاع شاپرک) و رسیدهای تراکنش. (توجه: اطلاعات حساس کارت بانکی منحصراً در بستر امن شاپرک مبادله شده و هرگز در سرورهای آسنا ذخیره نمی‌شود).</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 2 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">۲</span>
                    <h2 class="text-lg font-bold text-slate-900">نحوه محافظت و رمزنگاری اطلاعات (Zero-Trust)</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    ما استانداردهای مهندسی مدرن را برای جلوگیری از دسترسی‌های غیرمجاز اعمال کرده‌ایم:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pr-11 mt-3">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2 text-primary font-bold text-xs mb-1">
                            <span class="material-symbols-outlined text-sm text-primary">lock</span>
                            رمزنگاری داده در استراحت (At-Rest)
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            اطلاعات حساس هویتی و سوابق سلامت با الگوریتم متقارن پیشرفته AES-256-GCM رمزگذاری و با کلیدهای یکتا حفاظت می‌گردند.
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2 text-primary font-bold text-xs mb-1">
                            <span class="material-symbols-outlined text-sm text-primary">shield</span>
                            فایروال نرم‌افزاری بومی (WAF)
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            تمامی درخواست‌ها به طور مستمر در برابر تزریق کد (SQLi)، حملات اسکریپتی (XSS) و پویش‌های خودکار امنیتی پایش و مسدود می‌شوند.
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100">

            <!-- Section 3 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">۳</span>
                    <h2 class="text-lg font-bold text-slate-900">حقوق و اختیارات کاربران بر داده‌ها</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    مطابق با اصول استانداردهای بین‌المللی حفاظت از داده‌ها و قوانین حقوق مصرف‌کنندگان، شما اختیارات زیر را در سامانه آسنا دارا می‌باشید:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 pr-11">
                    <li><b>مشاهده و ویرایش:</b> دسترسی آزاد به تمام داده‌های پروفایل، ویرایش مشخصات حیوانات خانگی و تغییر کلمه عبور.</li>
                    <li><b>مدیریت حافظه موقت و کوکی‌ها:</b> امکان رد یا تنظیم دسته‌بندی‌های کوکی از طریق پنل اختصاصی تنظیمات کوکی سایت.</li>
                    <li><b>حق حذف قطعی و پاکسازی حساب کاربری (Right to be Forgotten / GDPR):</b> کاربر می‌تواند در هر زمان با ورود به بخش «تنظیمات امنیت و کلمه عبور» در <a href="profile.php" class="text-primary font-bold underline">پروفایل کاربری</a> خود، با تایید رمز عبور، حساب کاربری و کلیه پرونده‌های سلامت و اسناد پت‌های خود را به صورت خودکار و دائم حذف نماید. سوابق سفارشات مالی گذشته نیز جهت رعایت قوانین مالیاتی به صورت ناشناس نگهداری می‌شوند.</li>
                </ul>
            </section>

            <hr class="border-slate-100">

            <!-- Section 4 -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">۴</span>
                    <h2 class="text-lg font-bold text-slate-900">ارتباط با کارشناسان امنیت و حریم خصوصی</h2>
                </div>
                <p class="text-slate-600 pr-11">
                    در صورت داشتن هرگونه پرسش، ابهام یا گزارش موارد امنیتی، می‌توانید با تیم فنی و امنیت اطلاعات آسنا در تماس باشید:
                </p>
                <div class="p-4 rounded-2xl bg-primary/5 border border-primary/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pr-6 mr-11">
                    <div>
                        <div class="font-bold text-primary text-xs">ایمیل واحد امنیت و حریم خصوصی:</div>
                        <div class="text-xs text-slate-600 font-mono mt-0.5" dir="ltr">security@asena.company</div>
                    </div>
                    <div>
                        <div class="font-bold text-primary text-xs">تلفن پشتیبانی مرکزی:</div>
                        <div class="text-xs text-slate-600 font-mono mt-0.5" dir="ltr">+98 914 667 6978</div>
                    </div>
                </div>
            </section>

        </div>

        <!-- Back to Home Button -->
        <div class="text-center mt-8">
            <a href="index.php" class="inline-flex items-center gap-2 bg-primary text-white text-xs font-bold px-6 py-3 rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-light transition-all">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                <span>بازگشت به صفحه اصلی سامانه</span>
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
