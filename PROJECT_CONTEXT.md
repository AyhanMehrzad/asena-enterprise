# شناسنامه و کانتکس جامع سیستم سازمانی آسنا (ASENA Enterprise)
> **نسخه مستند:** ۱.۰.۰  
> **آخرین به‌روزرسانی:** سپتامبر ۲۰۲۶  
> **وضعیت مخزن:** کامیت و سینک‌شده با `origin/main` در گیت‌هاب  
> **هدف این سند:** ایجاد یک مرجع پایدار، متمرکز و مختصر از تمام فایل‌ها و معماری پروژه تا در شروع هر گفتگو یا ریست کانتکس، هوش مصنوعی سریعاً بدون نیاز به خواندن مجدد ده‌ها فایل در جریان وضعیت قرار گیرد.

---

## ۱. معماری و استک فنی (System Architecture)

- **هسته بک‌اند:** PHP 8.2+ با شیوه ساختاری ماژولار (Modular Monolith)، پایگاه داده MySQL/MariaDB با لایه انتزاع PDO، تراکنش‌های اتمیک (`beginTransaction`, `commit`, `rollBack`) و قفل‌های سطری انبار (`FOR UPDATE`).
- **فرانت‌اند و ظاهر:** Vanilla CSS سفارشی در کنار Tailwind CSS v3، فونت‌های محلی `Vazirmatn` و `Geist`، آیکون‌های محلی Material Symbols، طراحی کاملاً راست‌چین (`dir="rtl" lang="fa"`).
- **رنگ‌های رسمی برند آسنا:** سرمه‌ای تیره سازمانی (`#001a48`) و نارنجی آذرین (`#fd8100` / `#ea580c`).
- **معماری لایسنس و فیچرفلگ‌ها:** کنترل دسترسی قابلیت‌ها از طریق `Feature::has('feature_key')` و فایل `config/tiers.php` با ۵ سطح لایسنس: `basic`، `standard`، `premium`، `pharmacy`، `enterprise`.
- **ابزار خط فرمان:** ابزار اختصاصی `bin/asena` برای بررسی وضعیت ماژول‌ها و ساخت زیپ پکیج نصبی مشتریان (`php bin/asena package --tier=...`).
- **درگاه پرداخت بانکی:** درگاه دوگانه `ZarinPalGateway` در [`includes/gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/gateway.php) با پشتیبانی همزمان از درگاه واقعی زرین‌پال و شبیه‌ساز پرداخت (`mock_payment_gateway.php`).
- **سرویس پیامک:** سامانه ملی‌پیامک (Melipayamak REST & SOAP) با خطوط خدماتی و پترن‌های OTP و سفارشات در [`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php).
- **وب‌اپلیکیشن پیش‌رونده (PWA):** پشتیبانی کامل با `sw.js`، فایل `site.webmanifest` و صفحه آفلاین `offline.html`.

---

## ۲. گزارش و دسته‌بندی فایل‌های پروژه (File Registry)

### ۲.۱. فایل‌های پیکربندی و راه‌انداز هسته
- [`config.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config.php): تشخیص هوشمند لوکال‌هاست (XAMPP) و هاست اشتراکی cPanel با رعایت محدودیت‌های `open_basedir`.
- [`config/tiers.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config/tiers.php): ماتریس تعریف ماژول‌ها و سطوح ۵ گانه لایسنس.
- [`includes/Env.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/Env.php): لودر متغیرهای محیطی `.env`.
- [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php): کانکشن امن PDO، تنظیمات کوکی سشن (SameSite=Lax, HttpOnly).
- [`includes/App.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/App.php): کانتینر مرکزی و دسترسی Singleton به سرویس‌های پلتفرم.
- [`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php): توابع عمومی امنیتی، تاریخ شمسی، اعتبارسنجی CSRF و متادیتای سیستم.
- [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) & [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php): هدر و فوتر سراسری همراه با منوی ریسپانسیو دسکتاپ و نوبار موبایل، همگام با شمارنده سبد خرید.

### ۲.۲. صفحات فروشگاه، سفارشات و خدمات عمومی
- [`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php): لندینگ پیج اصلی آسنا، اسلایدرها، محصولات پرفروش و نوبت‌دهی سریع.
- [`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php): ویترین فروشگاه پت‌شاپ آنلاین با فیلتر دسته‌بندی و مرتب‌سازی.
- [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php): داروخانه تخصصی دامپزشکی، داروهای نسخه‌ای (Rx)، مکمل‌ها و زنجیره سرد.
- [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php): صفحه جزئیات کالا، انتخاب خرید عادی یا اشتراک ادواری اتوشیپ، مشخصات و نظرات.
- [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php): سبد خرید هوشمند تفکیک‌شده (تب کالاهای عادی و تب اتوشیپ)، محاسبه مالیات و تخفیف‌ها.
- [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php): اتصال امن به درگاه پرداخت برای سبد خرید، نوبت کلینیک و بسته‌های پیامک.
- [`mock_payment_gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/mock_payment_gateway.php): درگاه شبیه‌ساز شاپرک جهت تست بدون نیاز به کارت واقعی بانکی.
- [`booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/booking.php): تقویم نوبت‌دهی آنلاین پزشکان متخصص کلینیک با بررسی تایم‌اسلات‌های آزاد.
- [`chat.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/chat.php): تله‌هلث و مشاوره آنلاین هوشمند با دستیار هوش مصنوعی و دامپزشک.
- [`charity.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/charity.php) & [`charity_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/charity_payment.php): کمپین‌های نجات حیوانات حمایتی و واریز دونیشن شفاف.
- [`rewards.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/rewards.php): باشگاه مشتریان آسنا، جوایز وفاداری، گردونه شانس و امتیازات.
- [`wishlist.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/wishlist.php): لیست علاقه‌مندی‌های کاربر.
- [`subscriptions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscriptions.php) & [`subscription_checkout.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscription_checkout.php): پلن‌های عضویت و اتوشیپ.
- [`user_tickets.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/user_tickets.php): ثبت و پیگیری تیکت‌های پشتیبانی مشتریان.
- [`organizations.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organizations.php) & [`organization_profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization_profile.php): دایرکتوری و مشخصات مراکز درمانی و کلینیک‌های عضو.
- [`terms.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/terms.php)، [`privacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/privacy.php)، [`contract_acceptance.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/contract_acceptance.php): شرایط و قوانین حقوقی، حریم خصوصی و گیت قراردادها.

### ۲.۳. احراز هویت و حساب کاربری
- [`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php): ورود با رمز یکبارمصرف پیامکی (OTP)، پسورد یا ثبت‌نام سریع.
- [`register.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/register.php), [`logout.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/logout.php), [`forgot_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/forgot_password.php), [`reset_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/reset_password.php).
- [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php): داشبورد جامع مشتریان با معماری دیجی‌کالا (مشخصات، حیوانات من، نوبت‌ها، تاریخچه سفارشات، کیف‌پول و بخش مدیریت نشانی‌ها با نقشه).
- [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php): تنظیمات جانبی نشانی و رمز عبور.

### ۲.۴. پنل‌های چندنقشی (Multi-Role Portals)
- [`admin/`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/): پنل مدیریت ارشد سامانه (۳۰+ بخش شامل سفارشات، انبار، تراکنش‌های اسکرو، پزشکان، کاربران، تیکت‌ها، تسویه‌حساب‌ها و لاگ‌های امنیتی).
- [`organization/`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization/): پورتال کلینیک‌ها و بیمارستان‌ها (مدیریت پزشکان، نوبت‌ها، شیفت‌بندی، انبار دارویی و مالی).
- [`doctor/`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/): کنسول تخصصی پزشک (برنامه ویزیت‌ها، ثبت پرونده بالینی، راهنمای پزشکی).
- [`pharmacist/`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/): صف بررسی نسخه‌های پزشکی، تأیید اصالت دارو، شرایط زنجیره سرد.
- [`seller/`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/): پنل فروشندگان مارکت‌پلیس (محصولات، سفارشات ارجاعی و کیف‌پول).

### ۲.۵. سرویس‌های اصلی بک‌اند (`includes/`)
- `SmsService.php`: ارسال پیامک‌های اعتبارسنجی، نوبت‌دهی و فاکتور از طریق ملی‌پیامک.
- `MarketplaceEscrowService.php`: امانت‌داری مالی (Escrow) ۷ روزه وجوه فروشندگان تا تحویل قطعی مرسوله.
- `AutoshipService.php`: زمان‌بندی و ایجاد سفارشات ادواری خودکار برای غذای پت و داروها.
- `PostexShippingService.php`, `IranPostService.php`, `ShippingCalculator.php`: استعلام نرخ پست و رهگیری مرسولات.
- `AiContentService.php`: سرویس هوش مصنوعی با اتصال چندمدله AvalAI برای چت و تولید محتوا.
- `SecurityMiddleware.php`, `WafMiddleware.php`, `RateLimiter.php`, `DataSecurityService.php`: دیواره آتش نرم‌افزاری و مقابله با حملات Brute-Force / XSS / SQLi.

### ۲.۶. پردازشگرها و اکشن‌های AJAX (`actions/`)
- [`actions/cart_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/cart_action.php): افزودن، حذف، تغییر تعداد و تغییر مدل خرید (عادی/اتوشیپ) با خروجی استاندارد JSON.
- [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php): وریفای نهایی تراکنش، ثبت فاکتور `#PC-...`، کسر انبار، شارژ امتیاز و ارسال پیامک.
- [`actions/reverse_geocode.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/reverse_geocode.php): تبدیل آنی مختصات جغرافیایی به نشانی استاندارد فارسی.
- سایر اکشن‌ها: `booking_action.php`, `chat_action.php`, `profile_action.php`, `settings_action.php`, `autoship_worker.php`, `generate_invoice.php` و...

### ۲.۷. فایل‌های استاتیک و دارایی‌های بصری (`assets/`)
- [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js): ماژول جاوااسکریپت سراسری برای به‌روزرسانی فوری شمارنده سبد خرید با تأخیر ۰ میلی‌ثانیه‌ای، افکت ذرات و نوتیفیکیشن شیشه‌ای.
- [`assets/css/enterprise-ui.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/enterprise-ui.css): استایل‌های سازمانی، انیمیشن‌های Keyframe سبد خرید و توست‌ها.

---

## ۳. تاریخچه تغییرات اخیر (Change Log)

### نسخه ۱.۰.۱۳ (سپتامبر ۲۰۲۶ - تدوین استاندارد جامع تکالیف مالیاتی ایران، معماری تسویه بازارگاه و بهینه‌سازی قانونی مالیات)
1. **تدوین قانون رسمی استانداردهای مالیاتی و تسویه بازارگاه (`.agents/rules/iranian_tax_and_marketplace_accounting_standards.md`):**
   - **اصل تفکیک هویت کارگزاری از فروش مستقیم:** تثبیت شناسایی درآمد واقعی پلتفرم صرفاً بر مبنای کارمزد خالص ۱۵٪ (Net Commission Accounting طبق استاندارد ۱۵ حسابداری ایران و IFRS 15) و امانی بودن ۸۵٪ سهم تامین‌کنندگان (Fiduciary Escrow Liability) جهت جلوگیری از شناسایی کل گردش مالی درگاه (GMV) به عنوان فروش مشمول مالیات شرکت.
   - **تکالیف مالیات بر ارزش افزوده (۱۰٪ مصوب):** الزام تسلیم اظهارنامه فصلی ظرف ۱۵ روز پس از هر فصل، اعمال ارزش افزوده بر کارمزد پلتفرم، و کسر اعتبار مالیاتی خریدهای سرور، پیامک و تجهیزات.
   - **صورت معاملات فصلی ماده ۱۶۹:** اعمال معافیت ارسال تجمیعی برای معاملات خرد زیر ۵٪ حد نصاب، و الزام دریافت کد ملی/شناسه اقتصادی و کد پستی برای معاملات بزرگ‌تر.
   - **سامانه مودیان و پایانه‌های فروشگاهی:** الزامات صورتحساب الکترونیکی با کلید RSA و شناسه یکتای حافظه مالیاتی.
   - **تسویه‌های پایا و سیاست اتوشیپ:** اتصال مستقیم هر بسته تسویه به صدور فاکتور رسمی B2B کارمزد برای فروشندگان و ثبت ۰ تومان درآمد/مالیات کارمزد در سفارشات اتوشیپ (Autoship 0% Commission).
   - **استراتژی‌های بهینه‌سازی و حداقل‌سازی قانونی مالیات:** بهره‌مندی از معافیت مالیات عملکرد شرکت‌های دانش‌بنیان (ماده ۹ قانون جهش تولید دانش‌بنیان)، هزینه‌های قابل قبول بازاریابی و کدهای تخفیف (ماده ۱۴۸ ق.م.م)، و استهلاک دارایی‌های نامشهود نرم‌افزاری.
2. **به‌روزرسانی دستورالعمل‌های عامل هوشمند (`AGENTS.md`):**
   - الحاق بند ۱۰ به مراجع و قوانین کلیدی سامانه جهت التزام مستمر به استانداردهای مالیاتی و تسویه‌های پایا.
3. **طراحی و استقرار کارت‌های هوشمند مالیاتی در پنل مدیریت (`admin/finance_settings.php` و `admin/index.php`):**
   - پیاده‌سازی شبکه ۴ کارته بنتو (Bento Grid) در کنسول مالی شامل:
     - **کارت ۱ (ارزش افزوده ۱۰٪):** نمایش مجموع مالیات وصولی، کسر اعتبار خریدها (هاست/پیامک)، مانده خالص بدهی به دولت و شمارشگر معکوس روزهای باقیمانده تا پایان مهلت اظهارنامه فصل جاری.
     - **کارت ۲ (معاملات فصلی ماده ۱۶۹):** پایش معاملات خرد زیر ۵٪ حد نصاب (ارسال تجمیعی مصرف‌کننده) در برابر معاملات بزرگ به همراه خروجی خودکار استاندارد CSV ماده ۱۶۹.
     - **کارت ۳ (سامانه مودیان و کارپوشه):** رصد شناسه ۶ رقمی حافظه مالیاتی، شرکت معتمد مالیاتی (TSP) و الگوی صورتحساب کارمزدی مارکت‌پلیس.
     - **کارت ۴ (سپر مالیاتی کارگزاری):** تفکیک گردش کل ناخالص درگاه از درآمد کارمزد ۱۵٪ و نمایش میزان صرفه‌جویی مالیاتی قطعی شرکت.
   - استقرار کارت نظارت اجرایی مالیاتی در داشبورد اصلی ادمین (`admin/index.php`) و دکمه دسترسی مستقیم در کنسول تسویه‌های پایا (`admin/payouts.php`).

### نسخه ۱.۰.۱۲ (سپتامبر ۲۰۲۶ - یکپارچه‌سازی وب‌سرویس نمایه آنی Google Indexing API v3، پروتکل IndexNow و بازتولید نقشه سایت)
1. **وب‌سرویس نمایه آنی گوگل (Google Search Indexing API v3):**
   - توسعه ماژول مستقل و بدون وابستگی [`includes/GoogleIndexingService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/GoogleIndexingService.php) با پیاده‌سازی امضای دیجیتال RS256 بر پایه OpenSSL، تولید توکن JWT و دریافت Access Token از `https://oauth2.googleapis.com/token`.
   - ایجاد حساب سرویس `asena-indexing@asena-platform.iam.gserviceaccount.com` در Google Cloud Console و اعطای نقش Owner در سرچ کنسول دامنه `https://asena.company/`.
   - ارسال مستقیم درخواست‌های ایندکسینگ به اندپوینت رسمی `https://indexing.googleapis.com/v3/urlNotifications:publish` با پشتیبانی از فال‌بک خودکار پراکسی محلی جهت مقابله با محدودیت‌های شبکه.
2. **پروتکل ایندکسینگ فوری بینگ و موتورهای هوش مصنوعی (IndexNow Protocol):**
   - پیاده‌سازی سرویس [`includes/IndexNowService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/IndexNowService.php) جهت ارسال بلادرنگ تغییرات و صفحات جدید به بینگ، یاندکس و کاوشگرهای هوش مصنوعی (ChatGPT Search / Copilot) از طریق `https://api.indexnow.org/indexnow`.
   - ایجاد و استقرار کلید تایید هویت دامنه در فایل روت [`4a8f921e5c0840b59f3d9b62a71d87e2.txt`](file:///opt/lampp/htdocs/asena/asena-enterprise/4a8f921e5c0840b59f3d9b62a71d87e2.txt).
3. **ابزار خط فرمان ایندکس دسته‌جمعی و حل مسئله صفحات ایندکس‌نشده (Batch Indexing CLI):**
   - توسعه اسکریپت اجرایی [`bin/batch_index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/batch_index.php) و ارسال موفق ۸۵ آدرس هسته و صفحات در انتظار (Discovered - currently not indexed) به هر دو پروتکل IndexNow و Google Indexing API با وضعیت HTTP 200 و HTTP 202.
4. **به‌روزرسانی و غنی‌سازی نقشه سایت (`sitemap.xml`):**
   - به‌روزرسانی تاریخ‌های آخرین تغییر (`lastmod`) به ۲۰۲۶-۰۹-۲۳ و همگام‌سازی ۵۷۵ مسیر و تصویر کلیدی سامانه.
5. **رفع خطای فقدان client_id در ورود با گوگل (Google OAuth Missing Client ID Fix):**
   - اصلاح تاب‌آوری متد `Env::get()` در [`includes/Env.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/Env.php) جهت بازیابی مقادیر از `$_ENV` و `$_SERVER` در صورت غیرفعال بودن `putenv()` در سرورهای اشتراکی هاست پارس‌پک.
   - اعمال شناسه کلاینت سازمانی و سکرت فال‌بک در [`includes/config.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/config.php) و رفع خطای `Error 400: invalid_request` در صفحه ورود `login.php`.

### نسخه ۱.۰.۱۲ (سپتامبر ۲۰۲۶ - صفحه رسمی درباره آسنا، ادغام شریک راهبردی سما شهر خاوران و دریافت درگاه پرداخت شاپرک زرین‌پال)
1. **صفحه رسمی درباره آسنا و معرفی شریک راهبردی و زیرساختی (شرکت مهندسین مشاور سما شهر خاوران):**
   - بازطراحی ساختارمند و شکیل صفحه [`about.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/about.php) منطبق با استانداردهای بصری و پالت سازمانی آسنا (`#001a48` و `#fd8100`) و گرید مدرن بنتو.
   - معرفی سنجیده و باوقار همکاری فنی و زیرساختی با شرکت مهندسین مشاور سما شهر خاوران (`https://samashahr.ir/`) به عنوان بازوی فناوری در تحلیل داده‌های مکانی (GIS کلینیک‌ها و اورژانس)، نظارت بر تاب‌آوری سرورها و استانداردهای نظام صنفی.
   - نمایش گواهی رسمی پروانه فعالیت سازمان نظام صنفی رایانه‌ای کشور (شناسه ملی ۱۴۰۱۱۴۲۵۵۷۸ و شماره مجوز ۱۴۰۱۰۶۰۸) همراه با لایت‌باکس تعاملی بزرگ‌نمایی و اعتبارسنجی سند.
   - اتصال هوشمند لینک‌ها در ناوبری ابزارهای هدر [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php)، دراور موبایل و ستون خدمات مشتریان فوتر [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php).
2. **اتوماسیون ثبت درگاه و اتصال به شبکه پرداخت زرین‌پال شاپرک (Zarinpal Terminal Integration):**
   - پیشبرد اتوماسیون ثبت ترمینال در پنل زرین‌پال، استخراج و اعتبارسنجی آی‌پی سرور پروداکشن پارس‌پک (`193.36.85.51`) و تکمیل قرارداد الکترونیک.
   - اخذ و اتصال کد پذیرنده رسمی (Merchant ID: `92df0362-5e43-4565-b027-4c5b621ac1ab`) به تنظیمات درگاه در `.env`.
   - ارتقای تاب‌آوری لودر متغیرهای محیطی در [`includes/gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/gateway.php) جهت بازیابی بلادرنگ کلید مرچنت.
3. **تاب‌آوری پایگاه داده توسعه محلی (SQLite Local Fallback):**
   - ایجاد فال‌بک شفاف SQLite در [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php) جهت تضمین پایداری تست‌های فرانت‌اند و بارگذاری صفحات در محیط توسعه بدون وابستگی الزامی به دیمن محلی MySQL.

### نسخه ۱.۰.۱۱ (سپتامبر ۲۰۲۶ - یکپارچه‌سازی رابط کاربری هدر، تراز خودکار اسکیمای نسخه ۳ دیتابیس)
1. **بهینه‌سازی رابط کاربری و حذف دکمه تکراری هدر:**
   - حذف دکمه زائد پیلی شکل «حساب کاربری» در هدر دسکتاپ [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) برای کاربران عادی و اتکای کامل بر آیکون دایره‌ای استاندارد کاربر (`person`) در کنار سبد خرید و زنگوله اعلانات.
   - پاکسازی فایل قدیمی `issues.txt` پس از تایید صحت و سلامت زیرساخت‌های OTP، OAuth و سرویس‌های هوش مصنوعی.
2. **تراز خودکار ساختار دیتابیس نسخه ۳ (`.schema_aligned_v3`):**
   - استقرار گیت خوددرمانی در [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php) برای ایجاد آنی جدول `payment_discrepancy_logs` و ستون‌های `item_source` در جدول `order_items` در پایگاه‌داده پروداکشن بدون نیاز به اجرای دستی کوئری.

### نسخه ۱.۰.۱۰ (سپتامبر ۲۰۲۶ - موتور پشتیبان‌گیری خودکار دیتابیس و بهینه‌سازی کران‌جاب‌های سرور پارس‌پک)
1. **موتور پشتیبان‌گیری خودکار و تاب‌آور دیتابیس (Enterprise DB Backup Engine):**
   - پیاده‌سازی اسکریپت جامع [`scripts/backup_db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/scripts/backup_db.php) با استراتژی دوگانه: روش پرسرعت `mysqldump` همراه با فشرده‌سازی استریم مستقیم gzip و متد جایگزین (Fallback) استخراج کامل ساختار و داده‌های جداول از طریق PDO در پکت‌های ۵۰۰ تایی.
   - ذخیره‌سازی خارج از روت وب در مسیر امن `/home/asencomp/backups/` با دسترسی محدود `0700` و مسدودسازی مستقیم وب از طریق `.htaccess`.
   - اعمال سیاست پاکسازی خودکار دوره‌ای (Auto-pruning) برای نسخه‌های پشتیبان قدیمی‌تر از ۱۴ روز.
   - به‌روزرسانی اسکریپت شل [`scripts/backup.sh`](file:///opt/lampp/htdocs/asena/asena-enterprise/scripts/backup.sh) جهت تفویض به موتور PHP و تطبیق متغیرهای محیطی با مشخصات دیتابیس پروداکشن.
2. **ارتقای پردازشگر کران‌جاب سفارشات ادواری (Autoship Worker CLI/Cron Hardening):**
   - بهینه‌سازی کامل [`actions/autoship_worker.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/autoship_worker.php) برای اجرای مستقل بدون وابستگی به وب در سطح خط فرمان لینوکس (CLI Crontab).
   - افزودن فلگ `--force` برای اجرای دستی در زمان تست و تفکیک خروجی‌های استاندارد هدر HTTP و لاگ‌های ترمینال.

### نسخه ۱.۰.۹ (سپتامبر ۲۰۲۶ - برطرف‌سازی آسیب‌پذیری‌های امنیتی بحرانی، رفع تداخل کاتالوگ، مغایرت‌گیری پرداخت و پیاده‌سازی استانداردهای Chewy)
1. **برطرف‌سازی آسیب‌پذیری‌های امنیتی بحرانی و گیت‌های احراز هویت (Security Hardening):**
   - انتقال کامل `App::boot()` و `AuthGuard::requireRole('admin')` به سطر دوم پیش از هرگونه پردازش متد POST و بررسی توکن CSRF در [`admin/user_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/user_details.php) و [`admin/subscriptions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/subscriptions.php) جهت انسداد دسترسی غیرمجاز و ترفیع دسترسی (Privilege Escalation).
   - استانداردسازی و امن‌سازی آپلود فایل‌ها با اعتبارسنجی قطعی MIME (`mime_content_type`) و تولید اسامی هش‌شده تصادفی رمزنگاری‌شده (`bin2hex(random_bytes(10))`) در [`admin/user_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/user_details.php)، [`admin/clinic_management.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/clinic_management.php) و [`organization/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization/index.php) جهت جلوگیری کامل از اجرای کد دلخواه (RCE) و بای‌پس پسوند دوگانه.
   - رفع نشت اطلاعاتی و آسیب‌پذیری IDOR در [`actions/generate_payout_receipt.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/generate_payout_receipt.php) از طریق حذف فال‌بک ناامن به اولین رکورد شبا و مسدودسازی با خطای HTTP 403 Forbidden.
   - تبدیل کوئری‌های خام متنی در [`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php) به دستورات آماده پارامتریک PDO.
2. **رفع تداخل شناسه کاتالوگ پت‌شاپ و داروخانه (Catalog ID Collision Fix):**
   - تفکیک کامل شناسه اقلام خرده‌فروشی و داروهای تخصصی در سشن سبد خرید با پیشوندهای یکتای `prod_{id}` و `med_{id}` در [`actions/cart_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/cart_action.php)، [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php)، [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php) و ثبت نهایی در [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php).
   - افزودن ستون و ایندکس ساختاری `item_source` به جدول `order_items` و کسر دقیق موجودی از جدول مرتبط (`products` یا `pharmacy_medicines`).
   - تعبیه سقف امنیتی سفارش به ازای هر آیتم (`min($stock, 50)`).
3. **مدیریت ریسک کسر وجه و ثبت خودکار مغایرت مالی (Payment Reconciliation & Support Ticket):**
   - ایجاد جدول حسابرسی `payment_discrepancy_logs` و الحاق لایه‌های کچ در [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php).
   - در صورت بروز استثنا یا اتمام موجودی پس از بازگشت موفق از شاپرک/زرین‌پال، تراکنش مالی و کد رهگیری مشتری ثبت مغایرت شده و به صورت خودکار یک تیکت اولویت‌دار پشتیبانی مالی جهت استرداد آنی وجه ایجاد می‌گردد.
4. **همگامی و پیاده‌سازی قابلیت‌های استاندارد Chewy.com در آسنا:**
   - **تایید مستقیم نسخه الکترونیک با پزشک معالج (Direct Vet Authorization Flow):** افزودن قابلیت انتخاب پزشک همکار آسنا در مودال داروخانه [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php) و پردازش در [`actions/prescription_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/prescription_action.php) بدون نیاز به کاغذ و برودکست مستقیم به کارتابل پزشک.
   - **کنترل‌های سلف‌سرویس مشترکین اتوشیپ (Autoship Self-Service Controls):** افزودن اکشن‌های «ارسال فوری (Ship Now)»، «توقف موقت (Pause)»، «فعال‌سازی مجدد (Resume)»، «تغییر دوره تحویل (Swap Frequency)» و «تعویق نوبت» در [`actions/subscription_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/subscription_action.php) و داشبورد پروفایل سرپرست [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php).
   - **موتور هوشمند پایش و هشدار آلرژی پت (Pet Allergy Collision Detection):** توسعه تابع سراسری `checkItemAllergyWarning` در [`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php) و نمایش نشان هشدار حساسیت بر اساس پرونده سلامت حیوان خانگی در کارت‌های فروشگاه [`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php)، داروخانه [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php) و صفحه کالا [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php).
5. **کنترل همزمانی تسک پس‌زمینه اتوشیپ (Concurrency Locking):**
   - پیاده‌سازی قفل فایل غیرمسدودکننده انحصاری `flock(LOCK_EX | LOCK_NB)` در [`actions/autoship_worker.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/autoship_worker.php) جهت جلوگیری از Race Condition بازدیدهای همزمان در نیمه‌شب.

### نسخه ۱.۰.۸ (سپتامبر ۲۰۲۶ - تطبیق فرمول مالیاتی کالا و حمل، مهلت ارسال ۲۴ ساعته تامین‌کننده و محاسبه چندحاملی پستی)
1. **فرمول مالیات بر ارزش افزوده قانونی بر مجموع کالا و کرایه حمل:**
   - اعمال دقیق فرمول مالیاتی مصوب بر مجموع ارزش ناخالص کالاها و کرایه پستی: `(Product Subtotal + Shipping Cost) + 10% VAT` در سبد خرید [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php)، درگاه شاپرک [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php)، ثبت نهایی سفارش [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php) و صدور فاکتور رسمی [`actions/generate_invoice.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/generate_invoice.php).
   - حفظ حریم مالی و پورسانت داخلی ۱۵٪ آسنا و ۸۵٪ فروشنده مطابق Rule 7 (بدون افشا در فاکتور مشتری).
2. **انتخاب چندحاملی لجستیک کشوری و تخمین هوشمند اولین زمان تحویل:**
   - افزودن کارت انتخاب حامل بین **پست پیشتاز سراسری (شرکت ملی پست)** و **تیپاکس اکسپرس (تحویل سریع درب منزل)** با محاسبه وزن کل مرسوله، اضافه وزن و سقف ارسال رایگان کشوری.
   - نمایش شفاف **سریع‌ترین زمان ممکن تحویل** بر اساس استانداردهای ترانزیت پستی و تقویم شمسی (`jdate`).
3. **الزام و پایش مهلت ارسال ۲۴ ساعته تامین‌کنندگان (Seller Dispatch SLA):**
   - تعبیه نشان و هشدار مهلت ارسال مرسوله ظرف ۲۴ ساعت کاری در پرتال فروشندگان [`seller/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/index.php) و سفارشات داروخانه/کلینیک [`organization/orders.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization/orders.php).

### نسخه ۱.۰.۷ (سپتامبر ۲۰۲۶ - پیکربندی انبارداری، سقف ارسال رایگان مرسولات سراسری و نماد اعتماد الکترونیکی)
1. **پیکربندی انبارداری، هزینه حمل و سقف ارسال رایگان مرسولات:**
   - تعبیه سقف خرید برای ارسال رایگان (`free_shipping_threshold_toman`، پیش‌فرض ۶۰۰,۰۰۰ تومان) و هزینه ثابت ارسال زیر سقف (`standard_shipping_cost_toman`، پیش‌فرض ۴۹,۰۰۰ تومان) در پنل مدیریت مالی [`admin/finance_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/finance_settings.php).
   - طراحی و استقرار نوار پیشرفت تعاملی درصد تا ارسال رایگان سراسری به همراه محاسبه خودکار کرایه در سبد خرید [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php) و فرآیند پرداخت شاپرک [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php).
   - اختصاص ارسال ۱۰۰٪ رایگان دائمی به سفارشات ادواری Autoship جهت ترغیب سرپرستان پت.
2. **پشتیبانی سراسری، تلفن ثابت رسمی و الزامات اینماد:**
   - افزودن فیلد داینامیک شماره تلفن ثابت پشتیبانی سراسری (`support_phone_fixed`) در تنظیمات مدیریت.
   - نمایش پویا در فوتر سراسری [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php) و صفحه رسمی تماس و شکایات [`contact.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/contact.php) منطبق با الزامات اینماد صمت.
3. **آماده‌سازی بسته APK و انتشار در کافه‌بازار و مایکت:**
   - اتصال دامنه و وب‌مانیفست آسنا به ارزیابی PWABuilder جهت استخراج خروجی Trusted Web Activity (TWA) برای انتشار در استورهای ایرانی اندروید.

### نسخه ۱.۰.۶ (سپتامبر ۲۰۲۶ - یکپارچه‌سازی وب‌سرویس‌های Map.ir، استعلام هندسه تور پستی و ارتقای نقشه پروفایل)
1. **وب‌سرویس استعلام هندسه محدوده توزیع پستی (Map.ir Tour Area API):**
   - اتصال به اندپوینت‌های رسمی `https://map.ir/geo-data/postalcodes/{postalcode}/tour-geom` و `tour-bbox` در [`includes/MapService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/MapService.php).
   - پیاده‌سازی متد `MapService::lookupPostalCodeTour($postalCode)`: استخراج رأس‌های چندضلعی (Polygon)، محاسبه سنتروئید هندسی و ریورس‌ژئوکدینگ معکوس سنتروئید جهت بازیابی محله واقعی (مانند کوی فیروز تبریز) و نام خیابان (مانند کار پیشه).
   - رفع مشکل تاخیر و تایم‌اوت cURL در لایه‌های شبکه از طریق فعال‌سازی اجباری `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` با زمان پاسخ زیر ۱۵۰ میلی‌ثانیه.
2. **اصلاح قواعد اعتبارسنجی کد پستی ده رقمی ایران:**
   - حذف قاعده ساختگی "وجود بیش از ۳ رقم یکسان متوالی" از `validatePostalCode` در `MapService.php`، `actions/postal_code_lookup.php` و کلاینت فرانت‌اند جهت پشتیبانی کامل از کدهای پستی معتبر ملی ایران مانند `5173833334`.
3. **موتور جستجوی زنده معابر و اماکن (Map.ir Place Search):**
   - افزودن متد `MapService::searchPlaces($text, $lat, $lng)` در `MapService.php` با اتصال به `https://map.ir/search/v2`.
   - توسعه اکشن اختصاصی [`actions/map_search_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/map_search_action.php) با فیلتر شهری و بایاس موقعیت مکانی.
   - تعبیه نوار جستجوی زنده معابر در نقشه پروفایل [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php) همراه با منوی هوشمند تکمیل خودکار و پرش نرم به معبر یا مکان انتخابی.
4. **بازطراحی نقشه پروفایل مطابق با هویت بصری رسمی Map.ir:**
   - حذف کنترل‌های پیش‌فرض لایف‌لت و جایگزینی با دکمه‌های شیشه‌ای مدرن شناور (+, -, مکان‌یاب GPS و فوکوس روی محدوده تور پستی).
   - تعبیه نشان رسمی واتربرک برند مپ (`Map.ir Logo Badge`) با لینک مستقیم.
   - طراحی پین اختصاصی شناور سازمانی (Pulsing SVG Pin) در رنگ‌های سرمه‌ای `#001a48` و نارنجی `#fd8100` به همراه پالس راداری روی نقطه لمس.
   - ترسیم خودکار پلی‌گون محدوده گشت پستی (`L.polygon`) به محض ورود کدپستی، جایگزینی متون تکراری پیش‌فرض با آدرس استاندارد معبر و محله، و راهنمایی سرپرست برای تنظیم پین روی درب منزل.

### نسخه ۱.۰.۵ (سپتامبر ۲۰۲۶ - ساده‌سازی محاسبه‌گر، معافیت مالیاتی با لینک خیریه، فیلد وضعیت بالینی پت با هوش مصنوعی و سلب مسئولیت پزشکی در گزارش‌ها)
1. **پاکسازی و خلوت‌سازی تب حیوانات در پروفایل (`profile.php`):**
   - حذف کامل فرم حجیم و سنگین محاسبه‌گر دوز و تغذیه (`dosage-calc-section`) از تب حیوانات جهت سبکی داشبورد و ارجاع استاندارد به صفحه اختصاصی `calculator.php` بر اساس اصل Progressive Disclosure.
2. **ساده‌سازی محاسبه‌گر تغذیه و عدم استفاده از درگاه تجاری جهت معافیت مالیاتی (`calculator.php` و `admin/finance_settings.php`):**
   - حذف قفل‌های ساختگی، پیش‌نمایش‌های تارشده و قیمت‌های خط‌خورده تبلیغاتی؛ ارائه پیش‌نمایش شفاف، زنده و محاسبه بلادرنگ مقادیر MER، گرم غذای روزانه، آب مصرفی و سهم هر وعده.
   - جایگزینی بنرهای شلوغ چندخطی با یک دکمه اقدام واحد و پاکیزه («صدور و ذخیره در پرونده سلامت» / «پرداخت حمایت خیریه و صدور برنامه غذایی»).
   - افزودن تنظیمات لینک مستقیم حمایت خیریه (`calculator_charity_link`) در پنل مدیریت مالی جهت هدایت واریزها به نهاد حمایتی و معافیت کامل از مالیات بر ارزش افزوده و مالیات عملکرد ناشی از فاکتور تجاری درگاه.
3. **تعبیه فیلد شرح وضعیت بالینی، علائم و آلرژی بیمار به همراه پردازش هوش مصنوعی (`calculator.php`, `interactions.php`, `includes/MealPlanGenerator.php`, `includes/DrugInteractionService.php`, `includes/DrugReportGenerator.php`):**
   - افزودن فیلد متنی اختصاصی شرح وضعیت، علائم، سوابق جراحی و حساسیت‌ها در هر دو سامانه محاسبه‌گر تغذیه و پایش تداخلات دارویی.
   - ارسال مستقیم یادداشت به پرامپت هوش مصنوعی (AvalAI / Gemini) و دریافت تحلیل اختصاصی انطباق رژیم یا سازگاری داروها با وضعیت پت.
   - نمایش کارت اختصاصی ارزیابی بالینی هوش مصنوعی در کارنامه‌های نهایی HTML تولیدشده برای پرونده سلامت.
4. **درج متن دقیق و رسمی سلب مسئولیت پزشکی و هشدار سلامت در تمام گزارش‌ها و صفحات:**
   - استقرار متن رسمی سلب مسئولیت: «سلب مسئولیت پزشکی و هشدار سلامت: این ابزار صرفاً جنبه محاسبات تغذیه و شاخص بدنی دارد. تجویز هرگونه دارو، قرص ضدانگل، قطره ضدکک یا واکسیناسیون باید منحصراً توسط دکتر دامپزشک پس از معاینه بالینی حضوری انجام پذیرد. مصرف خودسرانه داروهای انسانی برای پتها خطر مسمومیت مرگبار دارد.» در تمام کارنامه‌های تغذیه (`MealPlanGenerator.php`)، کارنامه‌های تداخل دارویی (`DrugReportGenerator.php`)، سرویس دارویی (`DrugInteractionService.php`) و صفحات کاربری `calculator.php` و `interactions.php`.

### نسخه ۱.۰.۴ (سپتامبر ۲۰۲۶ - سامانه پایش تداخلات دارویی، پولی‌سازی محاسبه‌گر بالینی و ادغام کارنامه‌های سلامت در پروفایل)
1. **سامانه پایش هوشمند تداخلات دارویی پت (`includes/DrugInteractionService.php` و `interactions.php`):**
   - توسعه موتور دوگانه فارماکولوژی دامپزشکی (AvalAI / Gemini API + ماتریس آفلاین بیش از ۵۰ قاعده بالینی تداخلات دارویی).
   - تفکیک لاجیک پنل همکاران به `partner_interactions.php` و استقرار رابط کاربری اختصاصی بررسی تداخلات در `interactions.php` و `drug_interactions.php`.
   - ایجاد اندپوینت AJAX در `actions/ai_drug_analysis.php` و ثبت کارنامه در `actions/save_drug_report.php`.
2. **پولی‌سازی و تنظیمات مالی محاسبه‌گر تغذیه بالینی:**
   - افزودن کلیدهای فعال‌سازی (`calculator_is_paid`) و قیمت (`calculator_price_toman`) در `admin/finance_settings.php`.
   - انطباق گردش کار `calculator.php` با فلوهای رایگان و درگاه پرداخت آنلاین (`actions/initiate_meal_plan_payment.php` و `actions/complete_payment.php`).
3. **ادغام و نمایش کارنامه‌های رژیم بالینی در پروفایل کاربر (`profile.php`):**
   - اصلاح شِمای ذخیره‌سازی در `pet_documents` با ستون‌های استاندارد (`pet_id`, `user_id`, `title`, `file_name`, `file_path`).
   - تعبیه ویجت کارت کارنامه‌های بالینی در تب پیشخوان (`#view-overview`) و تب حیوانات خانگی (`#view-pets`).
   - اتصال به نمایشگر اختصاصی رژیم غذایی در `view_meal_plan.php` با قابلیت چاپ، مشاهده نسخه بالینی و QR اصالت.
4. **طراحی و استقرار هاب یکپارچه پرونده سلامت و سوابق پزشکی پت (`profile.php` و `actions/profile_action.php`):**
   - اتصال و تجمیع ۵ کانال اصلی پرونده: سازمان‌ها و کلینیک‌ها، پزشکان معالج، داروخانه‌ها، محاسبه‌گر هوش مصنوعی و مدارک سرپرست.
   - تفکیک و فیلتر تب‌های تخصصی (رژیم‌های بالینی، تداخلات دارویی، نسخه‌های داروخانه، ویزیت‌های پزشک، مدارک سرپرست).
   - نشان اختصاصی صادرکننده، متاداده‌های زمان‌بندی و دکمه‌های بررسی بالینی، چاپ و دانلود مستقیم.
   - مودال استاندارد بارگذاری با تفکیک نوع سند بالینی و قابلیت حذف ایمن با گارد IDOR.
5. **ارتقای جامع و استانداردسازی کارنامه بالینی تداخلات دارویی پت (`includes/DrugReportGenerator.php` و `view_drug_report.php`):**
   - طراحی و پیاده‌سازی کلاس مولد کارنامه دارویی سازمانی مطابق با الگوهای فارماکوپیای بالینی دامپزشکی (Plumb's & BSAVA).
   - ساختاردهی پیشرفته گزارش به همراه نوار اقدام بالا (چاپ و PDF، بازگشت به پرونده)، استریپ رسمی سربرگ با شناسه امنیتی، نوار مشخصات و بیولوژی پت.
   - سنجشگر چندسطحی وضعیت بالینی و ایمنی نسخه (بحرانی 🔴، احتیاط 🟠، تداخل متوسط 🟡 و سازگار 🟢)، ۴ ویجت متریک‌های کلیدی تجویز، و شناسنامه تفکیکی داروها.
   - کارت‌های تحلیلی تداخلات جفتی با تشریح مکانیسم‌های فارماکوکینتیک، علائم هشداردهنده بالینی و پروتکل شستشو (Washout Period).
   - جدول ساعات مصرف روزانه و فواصل ایمن داروها، توصیه‌های بالینی به دامپزشک و سرپرست، محل مهر رسمی دامپزشک معالج، مسئول فنی داروخانه و QR کد اعتبارسنجی.
   - افزودن نمایشگر اختصاصی و تاب‌آور `view_drug_report.php` و اتصال مستقیم دکمه‌های مشاهده در پروفایل کاربر.

### نسخه ۱.۰.۲ (سپتامبر ۲۰۲۶ - نگارش قبلی)
1. **رفع خطای انقضای اطلاعات سفارش در درگاه (`actions/complete_payment.php`):**
   - مقداردهی متغیر `$pending` از سشن قبل از شروط گیت ۳.
   - لود کردن توابع کمکی `functions.php` جهت جلوگیری از خطای ناشناخته بودن متدها.
   - ذخیره صریح `user_id` در سشن `pending_order` در [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php) برای مقابله با تغییر نشست‌ها.
   - رول‌بک اتمیک دیتابیس با بلوک سراسری `catch (Throwable $e)`.
2. **شمارنده بدون تأخیر سبد خرید در هدر (Optimistic Live Cart Counter):**
   - ایجاد ماژول مستقل [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js) و انیمیشن‌های جهش در هدر و نوبار موبایل.
   - اتصال کلیه دکمه‌های افزودن به سبد خرید در `shop.php`, `index.php`, `pharmacy.php`, `product_details.php` به این متد.
3. **انتخاب موقعیت روی نقشه و تکمیل خودکار آدرس (Reverse Geocoding):**
   - توسعه اندپوینت سبک [`actions/reverse_geocode.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/reverse_geocode.php) جهت استخراج فارسی شهر، محله، خیابان و کدپستی از روی مختصات.
   - ارتقای نقشه تعاملی در [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php) و [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php)؛ با درگ پین یا کلیک روی نقشه، فیلد نشانی، شهر و کدپستی خودکار پر می‌شوند.
   - اضافه شدن دکمه GPS، چیپ شناور نشانی و کلیدهای پرش سریع به تبریز و تهران.
4. **سینک و پوش گیت‌هاب:**
   - حل تعارض‌های ادغام در `config.php` و `includes/header.php` و انتشار موفق کامیت‌های `130af37` و `98bde81` در شاخه `main`.

### نسخه ۱.۰.۳ (سپتامبر ۲۰۲۶ - رفع جامع ممیزی فنی ۱۶ صفحه‌ای و امن‌سازی سرور)
1. **امن‌سازی ورود تست و ربات‌ها (C-1 & H-2):**
   - حفظ دسترسی `auto_login.php` صرفاً برای فاز تست کاربری طبق دستور کارفرما، با اعمال هدر و تگ متای ضد ایندکس `noindex, nofollow, noarchive`.
   - مسدودسازی دسترسی خزنده‌های گوگل و بینگ به فایل ورود خودکار و فولدر `includes/` در [`robots.txt`](file:///opt/lampp/htdocs/asena/asena-enterprise/robots.txt).
   - اصلاح نگاشت نقش داروخانه (`pharmacy` به `pharmacist`)، فعال‌سازی وضعیت تایید (`approved`) و تایید خودکار قراردادها در ورود سریع.
2. **اصلاح کوئری‌ها و سرویس BPMS نسخ دارویی (C-2 & C-3):**
   - اصلاح ستون‌های نامعتبر پزشک و جوین جدول سازمان‌ها در [`includes/BpmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/BpmsService.php).
   - مجهزسازی پنل پزشک ([`doctor/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/index.php)) و داروخانه ([`pharmacist/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/index.php)) به بلوک‌های تاب‌آور `try/catch` با کوئری‌های جایگزین جهت جلوگیری از Fatal Error.
3. **همگام‌سازی جداول اعلانات و نسخه‌ها (C-4):**
   - نگارش مایگریشن پایگاه‌داده [`database/migrations/14_user_notifications_and_prescriptions_alignment.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/14_user_notifications_and_prescriptions_alignment.sql) جهت ایجاد جدول‌های `user_notifications`، `pwa_subscriptions` و `live_social_proof_events`.
   - ایجاد بلوک خودترمیم `CREATE TABLE IF NOT EXISTS` و مدیریت خطای PDO در پنل مدیریت اعلانات ([`admin/notifications.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/notifications.php)).
4. **رفع تداخل و بازگشت بی‌پایان توابع افزودن به سبد خرید (C-5):**
   - اصلاح ماژول [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js) و اکسپورت تابع سراسری `window.cartManagerAddToCart` با خواندن خودکار نوع خرید (عادی/اتوشیپ).
   - رفع بازتعریف توابع و همگام‌سازی دکمه‌های خرید در [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php)، [`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php)، [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php) و [`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php).
5. **سخت‌سازی امنیتی و جلوگیری از دسترسی مستقیم به هسته (C-6 & H-5):**
   - ایجاد فایل [`includes/.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/.htaccess) برای انسداد هرگونه فراخوانی مستقیم فایلهای پوشه هسته.
   - اعمال گارد امنیتی بررسی `SCRIPT_FILENAME` در [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php)، [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php).
   - خاموش کردن نمایش خطاهای حساس سمت سرور (`display_errors = 0`)، فعال‌سازی لاگ سرور، حذف هدر شناسایی فناوری (`X-Powered-By`) و بافرینگ خروجی سراسری `ob_start()`.
6. **رفع خطای هدرهای ارسال شده در علاقه‌مندی‌ها (H-1):**
   - اصلاح ترتیب بررسی نشست احراز هویت و ریدایرکت قبل از ارسال هدرهای HTML در [`wishlist.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/wishlist.php).
7. **یکپارچه‌سازی امور مالی، کارمزدها و زمان‌بندی تسویه‌حساب (H-3 & H-4):**
   - استانداردسازی کارمزد پلتفرم روی **۱۵ درصد** و سهم خالص ۸۵ درصدی در پنل‌های فروشنده و ادمین ([`seller/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/index.php)، [`admin/finance_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/finance_settings.php)، [`admin/payouts.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/payouts.php) و سایر بخش‌ها).
   - هماهنگ‌سازی زمان تسویه پایا به **پنج‌شنبه‌ها ساعت ۹:۰۰ صبح** در سراسر متون سیستم.
8. **ناوبری هوشمند هدر و دسترسی سریع به پنل‌های کاربری (H-7):**
   - اضافه شدن دکمه‌های هوشمند هدایت به پنل اختصاصی نقش‌های فعال (مدیر، پزشک، کلینیک، داروخانه، فروشنده و پروفایل مشتری) در هدر دسکتاپ و دراور منوی موبایل در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php).
9. **اصلاح آمار و فیلترهای مدیریت کاربران (M-2):**
   - تراز کردن شمارنده‌های کارت‌های KPI با فیلترهای نقش در [`admin/user_management.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/user_management.php) به طوری که مجموع نقش‌ها دقیقاً برابر کل کاربران باشد.
10. **پاکسازی داده‌های آزمایشی و اصلاح Enum دیتابیس (H-6 & M-3):**
    - جایگزینی داده‌های آزمایشی ('تستر آسنا' به 'فروشگاه تجهیزات پت‌پارس') در فایل [`asena_enterprise_host_ready.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/asena_enterprise_host_ready.sql).
    - اضافه کردن مقدار `'pharmacy'` به تعریف فیلد `role` در جدول `users` دامپ پایگاه داده.
11. **سئو و عناوین داینامیک صفحات (M-5):**
    - افزودن تایتل‌های بهینه‌سازی شده فارسی به `$seo_defaults` در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) برای کلیه صفحات داخلی.
12. **پاکسازی فایل‌های زائد و منسوخ پروژه (Clean-up):**
    - حذف فایل‌های متنی سرگردان (`5162730.txt`, `5271122.txt`).
    - حذف اسکریپت‌های تست و موقت روت (`test_uri.php`, `setup_db.php`, `server_ip.php`, `test_sms.php`, `actions/test_sms.php`).
    - حذف اسکریپت‌های مایگریشن مجزای قدیمی در پوشه اکشن‌ها (`actions/migrate_tickets.php`, `actions/migrate_pharmacy_features.php`, `actions/migrate_charity.php`, `bin/apply_migration_12.php`, `bin/apply_migration_13.php`) به دلیل تجمیع رسمی در مایگریشن‌های چهارده‌گانه [`database/migrations/`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/).
13. **ارتقای امنیتی لایه‌های فرانت‌اند، ریت‌لیمیتر و کامپایل استاتیک استایل‌ها:**
    - صف‌بندی هوشمند بنرهای موبایل در [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php) و [`includes/cookie_consent.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/cookie_consent.php) جهت ممانعت از تداخل بنر کوکی با پاپ‌آپ PWA.
    - دوطرفه‌سازی کنترل نرخ ارسال پیامک در [`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php) (بررسی همزمان سقف IP و شماره تلفن کاربر برای پیشگیری از حملات بات‌نت به درگاه پیامک).
    - پاکسازی هدرهای افشاگر `X-Powered-By` و منسوخ `X-XSS-Protection` در سطح آپاچی در [`.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/.htaccess).
    - غنی‌سازی فال‌بک‌های سئو برای صفحات کلینیک‌ها، پزشکان و محصولات در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php).
    - راه‌اندازی فایل پیکربندی [`tailwind.config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/tailwind.config.js)، اسکریپت [`bin/build-css`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/build-css) و تولید استایل کامپایل‌شده سبک ۲۰۰ کیلوبایتی [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
14. **بازگردانی پالت رنگی رسمی سرمه‌ای تیره سازمانی (Navy Blue & Orange) مطابق نسخه کلینیکال Premium:**
    - بازنشانی کدهای رنگ در [`assets/js/tailwind-config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/tailwind-config.js) و [`tailwind.config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/tailwind.config.js) به سرمه‌ای سازمانی آسنا (`primary: #001a48`، `primary-container: #002d72`)، آکسان‌های نارنجی آذرین (`secondary-container: #fd8100`) و رنگ‌های وابسته بر اساس مستندات [`PROJECT_GUIDELINES.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_GUIDELINES.md).
    - به‌روزرسانی متغیرهای روت در [`assets/css/enterprise-ui.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/enterprise-ui.css) جهت اعمال کنتراست عمیق سرمه‌ای.
    - بیلد و بازتولید فایل نهایی استایل پروداکشن [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
15. **همگام‌سازی و انتشار روی شاخه `master` گیت‌هاب جهت استقرار خودکار سی‌پنل (cPanel Deployment):**
    - مخزن Git™ Version Control در سی‌پنل هاست روی شاخه `master` تنظیم شده بود، در حالی که کامیت‌های اخیر روی شاخه `main` ارسال شده بودند.
    - کلیه تغییرات اصلاحی ممیزی، تم رنگی سرمه‌ای و امن‌سازی در پوشه `asena.company` بر روی شاخه `master` تجمیع گردیده و با کامیت `1cb9537` به مخزن گیت‌هاب `AyhanMehrzad/asena.company` پوش شد.
    - قابلیت دریافت مستقیم از طریق دکمه «Update from Remote» و اعمال آنی روی هاست از طریق «Deploy HEAD Commit» در پنل سی‌پنل فعال گردید.
16. **رفع خطاهای ۵۰۰ آنلاین، بازتعریف توابع جلالی و تاب‌آوری سراسری احراز هویت (Online Reliability & Self-Healing Fixes):**
    - **رفع خطای ۵۰۰ صفحه اشتراک‌ها ([`subscriptions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscriptions.php)):** بازنویسی کوئری‌های محصولات و داروخانه با بلوک‌های حفاظتی `try/catch` و مقادیر پیش‌فرض؛ رفع خطای عدم وجود ستون‌های `autoship_min_months_stock`، `organization_id` و جوین‌های اضافی.
    - **رفع خطای ۵۰۰ تقویم یادداشت‌های ادمین ([`admin/calendar_notes.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/calendar_notes.php)):** افزودن گاردهای `if (!function_exists(...))` برای توابع تبدیل تاریخ شمسی `gregorian_to_jalali` و `jalali_to_gregorian` جهت ممانعت از Fatal Error بازتعریف توابع `jdf.php`.
    - **رفع خطای ۵۰۰ و قفل سراسری پنل‌های نقشی در گیت قرارداد ([`includes/ContractService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/ContractService.php) & [`includes/AuthGuard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AuthGuard.php)):** افزودن متد خودترمیم `ensureSchema()` جهت ایجاد جدول `contract_acceptances`؛ ایمن‌سازی کوئری کش با `try/catch` روی ستون `contract_accepted_version`؛ بای‌پس امن سشن و رفتار Fail-Open در محیط‌های آزمایشی.
    - **همگام‌سازی ورود خودکار سریع ([`auto_login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/auto_login.php)):** ثبت فوری مقدار نسخه قرارداد در نشست سشن کاربر و ایمن‌سازی دستورات درج در جدول `users`.
    - **تزریق مکانیسم خودترمیم خودکار پایگاه داده در داشبورد مدیریت ([`admin/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/index.php)):** افزودن روال خودکار ایجاد جدول `contract_acceptances` و افزودن تک‌تک ۱۱ ستون ضروری پایگاه داده به محض ورود مدیر به پیشخوان، بدون نیاز به اجرای دستی یا بروز خطای سینتکس در phpMyAdmin.
17. **رفع سفیدی صفحه فروشگاه و داروخانه و خطای ۵۰۰ تیکت‌های کاربر (Fix Blank Pages & 500 in Shop, Pharmacy, and Tickets):**
    - **رفع سفیدی بادی فروشگاه ([`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php)):** تشخیص پویا و اتصال شرطی ستون‌های `organization_id` و `seller_id` در محصولات؛ اجرای کوئری در بلوک ایمن `try/catch` با بازگشت امن به کوئری ساده در صورت تفاوت اسکیما، تا صفحه کالاها هیچ‌گاه سفید نشود.
    - **رفع سفیدی بادی داروخانه تخصصی ([`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php)):** حذف جوین اجباری روی `organization_id` و `seller_id` در جدول `pharmacy_medicines` و جایگزینی با جوین‌های شرطی و بلوک `try/catch`.
    - **رفع خطای ۵۰۰ صفحه تیکت‌های کاربر ([`user_tickets.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/user_tickets.php)):** ایمن‌سازی جوین جدول `organizations` با بررسی وجود ستون `organization_id` در جدول `tickets`، ایمن‌سازی دستورات بروزرسانی خودکار وضعیت و شمارنده تب‌ها با `try/catch`.
    - **سخت‌سازی تله‌هلث و چت ([`chat.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/chat.php)):** ایمن‌سازی مشابه برای جوین اطلاعات مرکز درمانی با جدول تیکت‌ها.
18. **رفع یافته‌های گزارش ممیزی کدهای سیستمی و مالی (Audit Report Fixes - Sections 4 & 5):**
    - **رفع هشدار ارسال هدر در لیست علاقه‌مندی‌ها ([`wishlist.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/wishlist.php)):** افزودن بافر خروجی صریح `ob_start()` در خط نخست جهت پیشگیری از خطای هدر در ریدایرکت سشن لاگین.
    - **همگام‌سازی کارمزد پلتفرم مارکت‌پلیس ([`includes/MarketplaceEscrowService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/MarketplaceEscrowService.php)):** افزایش نرخ ثابت `DEFAULT_COMMISSION_RATE` از ۵.۰۰ به ۱۵.۰۰ درصد (۱۵٪) جهت تطابق کامل با پنل مالی و تسویه حساب فروشندگان.
    - **حذف اسکریپت ران‌تایم CDN تیلویند در پروداکشن ([`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و صفحات احراز هویت):** حذف اسکریپت تفسیری `assets/js/tailwindcss-cdn.js` و جایگزینی آن با باندل سبک کامپایل‌شده استاتیک [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css) در هدر سراسری، لاگین، ثبت‌نام، بازیابی رمز و صفحات تعاملی؛ اجرای مجدد اسکریپت کامپایل [`bin/build-css`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/build-css).
    - **ورود خودکار ([`auto_login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/auto_login.php)):** مطابق درخواست اختصاصی کاربر دست‌نخورده باقی ماند.
19. **اصلاح پالت رنگی صفحه لاگین، رفع سفیدی داشبورد پزشک و پایدارسازی ورود خودکار نقشی (Login Brand Colors, Doctor Blank Page & Auto-Login RBAC Fix):**
    - **اصلاح پالت رنگی و برندینگ لاگین ([`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php)، [`assets/css/login.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/login.css)، [`assets/js/login.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/login.js)):** جایگزینی کامل تم کله‌غازی و سبز با رنگ‌های رسمی برند آسنا (سرمه‌ای تیره سازمانی `#001a48`، کانتینر `#002d72` و نارنجی آذرین `#fd8100`) در هیرو، بنتوکارت‌ها، دکمه‌ها، تب‌ها و اینپوت‌ها؛ اضافه شدن کشوی بازشونده انتخاب سریع نقش با ۱ کلیک؛ ثبت و انتقال امن پارامتر `return_url`.
    - **رفع سفیدی بادی و خطای دیتابیس پنل پزشک ([`doctor/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/index.php)):** ایجاد خودکار جدول `pharmacy_stores` در بدو ورود؛ مجهز کردن کوئری دراپ‌داون داروخانه‌ها به بلوک مقاوم `try/catch` و فال‌بک ایمن به جدول `users` جهت پیشگیری از خطای Fatal Error و سفید شدن پیشخوان زیر تاپ‌بار.
    - **مایگریشن جدول داروخانه‌ها ([`database/migrations/15_create_pharmacy_stores_and_role_alignment.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/15_create_pharmacy_stores_and_role_alignment.sql)):** تعریف ساختار رسمی جدول `pharmacy_stores` و هماهنگ‌سازی Enum نقش‌های جدول `users`.
    - **پایدارسازی ورود خودکار و رفع خطای عدم احراز سطح دسترسی ([`auto_login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/auto_login.php) & [`includes/AuthGuard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AuthGuard.php)):** رفع باگ خالی بودن نقش `()` در پیام عدم دسترسی؛ همگام‌سازی و ترمیم خودکار نقش کاربر در پایگاه‌داده؛ مقداردهی کلیدهای نشست از جمله `user_name`؛ پشتیبانی و ریدایرکت خودکار به `return_url`؛ معادل‌سازی دسترسی‌های `pharmacist` و `pharmacy` در گارد احراز هویت.
    - **بیلد استاتیک استایل‌ها:** کامپایل مجدد [`bin/build-css`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/build-css) و انتشار باندل به‌روزرسانی‌شده [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
20. **سازگاری با اینترنت ایران، خنثی‌سازی خطای CDN و اصلاح هدرهای HTTP/3 و رفع مسدودی بدون فیلترشکن (Iran Network & CDN Anti-Censorship Hardening):**
    - **حذف و پاک‌سازی هدرهای Alt-Svc ([`.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/.htaccess) & [`includes/SecurityMiddleware.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SecurityMiddleware.php)):** جلوگیری از ارسال پیشنهاد پروتکل مسدود شده‌ی HTTP/3 (QUIC بر روی پورت UDP 443) به مرورگرها توسط سرور/کلودفلر که منجر به قطعی و تایم‌اوت در داخل ایران می‌شد.
    - **تشخیص آی‌پی واقعی کلاینت و رفع قفل اشتراکی کلودفلر ([`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php)):** افزودن تابع جامع `get_client_ip()` و تطبیق `check_rate_limit()` جهت پیشگیری از مسدودی دسته‌جمعی کاربران ایرانسل/همراه‌اول روی NAT و پروکسی لبه کلودفلر؛ افزایش حد مجاز به ۱۵ درخواست در ۲ دقیقه برای CGNAT اپراتورها.
    - **به‌روزرسانی فرم‌های احراز هویت ([`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php)، [`register.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/register.php)، [`reset_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/reset_password.php)، [`forgot_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/forgot_password.php)، [`contract_acceptance.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/contract_acceptance.php)):** مهاجرت کامل از `$_SERVER['REMOTE_ADDR']` به `get_client_ip()`.
    - **سپر دفاعی کلودفلر و ترافیک مانیتورینگ ([`includes/TrafficMonitoringService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/TrafficMonitoringService.php)):** افزودن متد `isCdnProxyIp()` و ممنوعیت مسدودسازی خودکار بازه‌های آی‌پی کلودفلر و پروکسی‌های داخلی؛ افزایش ترش‌هولد Flood به ۱۲۰ درخواست جهت سازگاری با درخواست‌های همزمان مرورگرها.
    - **بومی‌سازی درگاه شبیه‌ساز ([`mock_payment_gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/mock_payment_gateway.php)):** جایگزینی `cdn.tailwindcss.com` با استایل لوکال کامپایل‌شده.
21. **ارتقای جامع PWA، تبدیل فرمت تصاویر به WebP، حذف ۳.۳ مگابایت CDN ران‌تایم و استقلال کامل شبکه (PWA Supercharging, WebP Migration & Performance Hardening):**
    - **استقلال کامل شبکه ایران و دانلود بومی Leaflet ([`assets/vendor/leaflet/`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/vendor/leaflet/)):** دانلود کتابخانه پایدار Leaflet 1.9.4 همراه با تمام فایل‌های CSS و آیکون‌های پین مارکر و جایگزینی کامل با دامنه مسدود `unpkg.com` در [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php) و [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php)؛ اصلاح لینک‌های نوبار موبایل در تنظیمات حساب کاربری.
    - **حذف کامل CDN تفسیری تیلویند از تمامی پنل‌های نقشی و اداری:** حذف `tailwindcss-cdn.js` و بلاک‌های ۶۰ خطی اسکریپت کانفیگ از هدرهای مدیریت ارشد ([`admin/includes/admin_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/includes/admin_header.php))، پزشکان ([`doctor/includes/doctor_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/includes/doctor_header.php))، کلینیک‌ها ([`organization/includes/organization_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization/includes/organization_header.php))، داروسازان ([`pharmacist/includes/pharmacist_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/includes/pharmacist_header.php))، فروشندگان ([`seller/includes/seller_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/includes/seller_header.php))، داشبورد ([`admin/dashboard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/dashboard.php))، ویرایشگر وبلاگ ([`blog_editor.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/blog_editor.php)) و ورود خودکار ([`auto_login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/auto_login.php)) و جایگزینی با لینک مستقیم استایل کامپایل‌شده استاتیک [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
    - **ارتقای PWA، مانیفست و سرویس‌ورکر ([`site.webmanifest`](file:///opt/lampp/htdocs/asena/asena-enterprise/site.webmanifest)، [`sw.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/sw.js) و [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php)):** افزودن متاتگ مدرن `mobile-web-app-capable`، افزودن ۴ میانبر صفحه اصلی موبایل (نوبت‌دهی، فروشگاه، داروخانه، پروفایل) با آیکون‌های استاندارد، پیش‌کش کردن استایل‌ها، فونت‌ها و اسکریپت‌های حیاتی در سرویس‌ورکر `sw.js` و ارتقای نسخه کش به `asena-enterprise-v1.0.5`.
    - **تبدیل تصاویر به فرمت فوق‌سریع WebP و رفع خطای ۴۰۴ آواتار پزشکان:** تولید نسخه‌های فشرده باکیفیت `.webp` برای تمام تصاویر هیرو، محصولات و بنرها؛ ایجاد فایل‌های پیش‌فرض [`assets/images/vet-hero.png`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/images/vet-hero.png) و `vet-hero.webp` و تزریق گارد فال‌بک `onerror` در کارت‌های پزشکان در [`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php) و [`booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/booking.php) جهت رساندن خطاهای ۴۰۴ کنسول به صفر.
    - **اصلاح چرخه پرداخت نوبت و سشن کاربر ([`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)، [`actions/settings_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/settings_action.php) و [`actions/profile_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/profile_action.php)):** جلوگیری از ثبت سفارش فاکتور خالی در جدول `orders` در زمان پرداخت نوبت‌های پزشکی و ذخیره مستقیم کد پیگیری بانکی در `appointments.tracking_code`؛ همگام‌سازی بلادرنگ `$_SESSION['user_name']` هنگام ویرایش نام کاربر.
    - **ارتقای UX نوبت‌دهی و کلینیک‌ها ([`booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/booking.php) و [`admin/doctors.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/doctors.php)):** اولویت‌بندی و اختصاص نشان ویژه «پزشک مستقر در این مرکز» هنگام انتخاب کلینیک مقصد و افزودن دکمه میانبر مدیریت پزشکان در پنل ادمین.
    - **یکپارچه‌سازی دامپ دیتابیس مستر ([`asena_enterprise_host_ready.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/asena_enterprise_host_ready.sql)):** الحاق کامل ساختار جداول مایگریشن‌های ۱۲ تا ۱۵ (`contract_acceptances`, `user_notifications`, `pwa_subscriptions`, `live_social_proof_events`, `pharmacy_stores`).
22. **استقرار کامل پروداکشن در سی‌پنل، همگام‌سازی دیتابیس آنلاین و دسترس‌پذیری موبایل (Production Deployment, DB Sync & Mobile A11y):**
    - **استقرار روی سرور اصلی با Git™ Version Control سی‌پنل:** پول آخرین تغییرات از مخزن گیت‌هاب و اجرای استقرار کامل روی شاخه `master` هاست واقعی پارس‌پک (`asena.company`).
    - **تأیید و همگام‌سازی دیتابیس هاست در phpMyAdmin:** تأیید وجود ۱۲ جدول سازمانی و ستون‌های مربوط به مایگریشن‌های ۱۲ تا ۱۵ در `asencomp_asena_db`؛ اصلاح نشانی تصاویر پزشکان در جدول `doctors` به `assets/images/vet-hero.webp` و رفع قطعی خطاهای ۴۰۴ تصاویر در فرانت‌اند.
    - **بهینه‌سازی دسترس‌پذیری و سئو برای لایت‌هاوس ([`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php)، [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php)):** افزودن صریح `aria-label` به دکمه‌های پیجینیشن اسلایدر هیرو، اسلایدر وزن و اینپوت‌های جستجو؛ تبدیل تگ‌های `a` دارای `javascript:void(0)` به دکمه‌های استاندارد دسترس‌پذیر `button` و لینک‌های کراول‌پذیر برای ناوبری باتوم شیت موبایل؛ ثبت امتیاز ۹۶ در Accessibility و ۹۶ در Best Practices لایت‌هاوس روی موبایل.


### نسخه ۱.۰.۴ (سپتامبر ۲۰۲۶ - تله‌هلث هوشمند و چت پزشک، پنل داروساز و پکیج‌سازی چندسطحی)
23. **تله‌هلث هوشمند بالینی با محافظت در برابر مزاحمت و اطلاع‌رسانی پیامکی (Telehealth Doctor Consultation & Anti-Spam Gatekeeper):**
    - **گیت محافظتی ویزیت ۷ روز اخیر:** بیمار تنها در صورتی مجاز به آغاز چت یا ارسال پیام به دامپزشک است که در بازه ۷ روز گذشته نوبت ویزیت تاییدشده با همان پزشک داشته باشد (`actions/chat_action.php`). در غیر این صورت، پیام مودبانه عدم دسترسی به همراه دکمه فراخوان رزرو نوبت نمایش داده می‌شود.
    - **سیستم پیامک هشدار به پزشک:** ارسال خودکار پیامک هشدار به شماره همراه پزشک هنگام دریافت پیام جدید بیمار از طریق متد جدید `sendDoctorTelehealthAlert()` در [`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) با تراتل هوشمند ۱۵ دقیقه‌ای (`tickets.last_notified_at`) جهت جلوگیری از اسپم پیامکی پزشک.
    - **کنسول تله‌هلث پزشک و خاتمه مشاوره بالینی:** ایجاد صفحه مدرن و واکنش‌گرای دوپنله [`doctor/telehealth.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/telehealth.php) با قابلیت مشاهده پرونده گفتگوها، چیپ‌های راهنمای سریع دارویی/بالینی و مودال خاتمه مشاوره بالینی («پایان مشاوره بالینی») با درج توصیه‌های ترخیص و دارویی (`resolution_notes`) و بستن دسترسی ارسال پیام توسط بیمار؛ افزودن منوی مشاوره آنلاین به هدر پزشکان در [`doctor/includes/doctor_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/includes/doctor_header.php).
    - **ارتقای صفحه چت و رزرو نوبت:** اضافه شدن کارت وضعیت تله‌هلث با پزشک و نمایش توصیه‌های ترخیص پزشک در [`chat.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/chat.php)؛ افزودن دکمه چت آنلاین به کارت پزشکان در [`booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/booking.php) با ارزیابی ایجکسی شرط ویزیت.
    - **مایگریشن پایگاه‌داده ۱۶:** نگارش و اجرای مایگریشن [`database/migrations/16_telehealth_and_doctor_chat.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/16_telehealth_and_doctor_chat.sql) در دیتابیس لایو سرور `asencomp_asena_db` جهت افزودن فیلدهای `doctor_id`, `closed_by`, `resolution_notes`, `last_notified_at` به جدول `tickets`، گسترش Enum فرستنده در `ticket_messages` و افزودن `tracking_code` به `prescriptions`.
24. **پنل داروساز، آپلود آنلاین نسخه و پیگیری مشتری (Pharmacist Panel & Prescription Workflow):**
    - **آپلود ایجکس نسخه:** فرم آپلود نسخه در [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php) به صورت ایجکس به [`actions/prescription_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/prescription_action.php) با انتخاب حیوان خانگی، آپلود تصویر نسخه، ایجاد کد پیگیری یکتا (`RX-XXXXXX`) و کارت تأیید زیبا و بدون رفرش متصل شد.
25. **موتور پکیج‌سازی چندسطحی تجاری (Multi-Tier Packaging via CLI):**
    - **به‌روزرسانی نصاب خودکار در `bin/asena`:** اصلاح اسکریپت ساخت توزیع (`packageDistribution`) جهت بارگذاری و اولویت‌دهی خودکار به دامپ استاندارد [`asena_enterprise_host_ready.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/asena_enterprise_host_ready.sql) و تولید پکیج‌های زیپ بدون نقص با حجم بهینه (~11.7 MB) آماده استقرار با یک کلیک در cPanel/DirectAdmin برای سطوح پنج‌گانه لایسنس (`basic`, `standard`, `premium`, `pharmacy`, `enterprise`).
26. **پیاده‌سازی ماندگاری ورود با مرا به خاطر بسپار در ورود پیامکی (Remember Me in OTP & Password Login):**
    - **کوکی امن ۳۰ روزه:** توسعه متدهای `AuthGuard::setRememberCookie()`, `AuthGuard::attemptRememberLogin()`, و `AuthGuard::clearRememberCookie()` در [`includes/AuthGuard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AuthGuard.php) با توکن امضاشده مبتنی بر HMAC-SHA256 (`asena_remember`) و تمدید خودکار نشست و کوکی مرورگر تا ۳۰ روز.
    - **بررسی خودکار در بارگذاری سشن:** فعال‌سازی بازیابی ورود پایدار در [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php) در صورت اتمام کوکی نشست موقت.
    - **پاکسازی در خروج:** پاکسازی توکن پایدار در [`logout.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/logout.php).
    - **رابط کاربری ورود:** افزودن چک‌باکس شکیل «مرا به خاطر بسپار (ورود پایدار تا ۳۰ روز)» در هر دو مرحله ورود پیامکی (مرحله شماره موبایل و مرحله کد ۶ رقمی) و فرم‌های ثبت‌نام در [`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php).
27. **رفع قفل لودینگ دکمه ارسال و مکالمه پیوسته هوش مصنوعی (Fix AI Chat Loading Freeze & Continuous Speaking):**
    - **رفع قفل اسپینر دکمه ارسال ([`assets/js/paw-loader.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/paw-loader.js)):** ممانعت از افزودن اسپینر و غیرفعال‌سازی دکمه در فرم‌های ایجکسی و فرم چت (`#chat-form`, `data-ajax`, `data-no-spinner`) که عامل اصلی قفل شدن دکمه ارسال در موبایل بود.
    - **مکالمه پیوسته بدون وقفه ([`chat.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/chat.php)):** تخصیص شناسه یکتای پیام موقت (`tempId`)، رندر فوری پاسخ دریافتی هوش مصنوعی بدون نیاز به وقفه، فوکوس آنی مجدد روی فیلد متن جهت ادامه تایپ سریع و باز ماندن کیبورد موبایل، و عدم قفل دکمه ارسال برای پیام‌های رگباری کاربر.
    - **ارتقای لحن و استمرار مکالمه هوش مصنوعی لئو ([`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php)):** اصلاح پرامپت سیستمی لئو جهت پاسخگویی صمیمانه، همدلانه و پیوسته به احوالپرسی‌ها و زبان‌های مختلف (فارسی و انگلیسی مثل Hi how re you) به جای تکرار پیام رباتیک مسدودکننده.

28. **همگام‌سازی خودکار و منعطف پایگاه‌داده با مای‌اس‌کیوال ۸ و رفع خطای ۵۰۰ اکشن چت (MySQL 8.0 Compatible Self-Healing Schema Alignment):**
    - **اصلاح سازگاری سینتکس مایگریشن ۱۶ ([`database/migrations/16_telehealth_and_doctor_chat.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/16_telehealth_and_doctor_chat.sql)):** حذف `IF NOT EXISTS` از دستورات `ALTER TABLE` که در مای‌اس‌کیوال ۸.۰ سرور باعث خطای سینتکس ۱۰۶۴ و عدم ایجاد ستون‌های ضروری تله‌هلث می‌شد.
    - **مکانیزم خودترمیمی اسکیما در رانتایم ([`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php)):** افزودن تابع `ensure_chat_telehealth_schema()` جهت بررسی خودکار ستون‌های `doctor_id`, `closed_by`, `resolution_notes`, `last_notified_at` در جدول `tickets`، ستون `tracking_code` در `prescriptions` و Enum های `ticket_messages`، به همراه استفاده از `SELECT *` منعطف و قرار دادن پردازش ارسال پیام در بلوک `try ... catch` برای جلوگیری کامل از هرگونه خطای ۵۰۰ سرور.

29. **بازطراحی اصولی پنل پیامک و پیاده‌سازی اعلان‌های پیامکی تله‌هلث پزشکان و پیام‌های کاربران (SMS Operations Dashboard & Dual-Way Chat Notifications):**
    - **حذف منطق نامربوط فروش پکیج و سود پیامکی در پنل ادمین ([`admin/sms_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/sms_settings.php)):** اصلاح بنیادین صفحه به عنوان داشبورد عملیاتی و پایش رویدادها (نه فروشگاه بسته پیامک به خود سیستم)؛ حذف فیلدهای نامربوط پکیج‌های ۱۰۰/۵۰۰/۱۰۰۰ عددی، قیمت هر پیامک و جدول تحلیل حاشیه سود.
    - **افزودن کارت‌های وضعیت و متغیرهای عملیاتی:** اتصال مستقیم به API Key، نام کاربری، رمز، خط اختصاصی و سوئیچ حالت شبیه‌ساز (Sandbox)؛ نمایش مانده اعتبار ریالی ملی‌پیامک؛ تنظیمات اعلان‌های پیامکی تله‌هلث و چت با دکمه‌های فعال/غیرفعال‌سازی.
    - **مدیریت داینامیک شناسه‌های الگو (Body IDs):** امکان ویرایش و ذخیره مستقیم شناسه‌های الگو در پایگاه‌داده بدون نیاز به تغییر کد در ۱۰ سناریوی حیاتی از جمله تله‌هلث و چت کاربر.
    - **سیستم پیامک اعلان تله‌هلث به پزشک ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php)):** ارسال پیامک اطلاع‌رسانی پیام جدید بیمار به شماره پزشک معالج از طریق پترن با فال‌بک خط مستقیم با لینک مستقیم ورود به تله‌هلث و تراتل ۱۵ دقیقه‌ای (`last_notified_at`).
    - **سیستم پیامک اعلان پیام به همه کاربران ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php)):** متد جدید `sendUserChatMessageAlert()` جهت ارسال پیامک به بیمار/کاربر هنگام دریافت پاسخ از پزشک معالج (`doctor_send`)، مدیریت سیستم (`admin_send`) یا کلینیک/سازمان (`org_send`) با تراتل آنتی‌اسپم ۱۵ دقیقه‌ای (`last_user_notified_at`).
    - **ابزار تست زنده پیامک:** پشتیبانی از تست ۶ سناریوی پیامکی شامل تله‌هلث، چت کاربر، کد OTP، سفارش و نوبت.

30. **تکمیل جامع صفحات شماره پیامک کلیه نقش‌ها و توسعه چرخه اعلان‌های پیامکی پلتفرم (Multi-Role SMS Profile Settings & Comprehensive Lifecycle Alerts):**
    - **ویرایش شماره پیامک فروشندگان پت‌شاپ ([`seller/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/index.php)):** رفع قفل فیلد شماره همراه که قبلاً به صورت `disabled` بود؛ افزودن فرم ذخیره شماره تماس مدیر فروشگاه و نام فروشگاه به همراه نرمال‌سازی شماره و ذخیره در `users.phone` و `seller_profiles.phone` با توکن CSRF و پیام‌های راهنما.
    - **صفحه تنظیمات و شماره پیامک داروسازان ([`pharmacist/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/index.php) و [`pharmacist/includes/pharmacist_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/includes/pharmacist_header.php)):** افزودن تب جدید «اطلاعات تماس و پیامک» به منوی داروساز؛ امکان ویرایش و ذخیره شماره همراه مسئول فنی داروخانه برای دریافت پیامک‌ها؛ به‌روزرسانی متد `switchTab` و ناوبری بر اساس URL.
    - **پیامک آماده‌سازی نسخه به بیمار ([`pharmacist/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/index.php) و [`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php)):** ارسال خودکار پیامک اطلاع‌رسانی به شماره همراه بیمار با کد پیگیری نسخه هنگام تغییر وضعیت نسخه به «آماده تحویل / پیک» (`ready_for_pickup`).
    - **پیامک سفارش جدید به فروشندگان ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):** پیاده‌سازی متد `sendSellerNewOrderAlert()` با استفاده از الگوی مصوب ۵۳۵۲۸۶ ملی‌پیامک و فال‌بک خط مستقیم جهت اعلان لحظه‌ای به فروشندگان پت‌شاپ پس از پرداخت سفارش توسط خریدار.
    - **پیامک رزرو نوبت به مدیریت کلینیک‌ها ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):** متد `sendClinicNewAppointmentAlert()` جهت اطلاع‌رسانی نوبت‌های رزروشده برای پزشکان وابسته به کلینیک به شماره تماس مرکز.
    - **به‌روزرسانی تنظیمات و ابزار تست ادمین ([`admin/sms_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/sms_settings.php)):** افزودن سناریوی تست پیامک سفارش فروشنده به دراپ‌داون تست و ثبت در کاتالوگ الگوها.
31. **تست عمیق مرورگر کروم، رفع باگ سشن نوتیفیکیشن‌ها و تایید الگوهای تله‌هلث و چت (Chrome E2E Verification, Session Bridge & Pattern Approvals):**
    - **رفع خطای رانتایم سشن و نوتیفیکیشن‌ها ([`actions/notification_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/notification_action.php) و [`includes/session.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/session.php)):** رفع خطای ۵۰۰ ناشی از عدم وجود فایل `includes/session.php` که در کنسول مرورگر خطای `SyntaxError: Unexpected token '<'` ایجاد می‌کرد؛ ایجاد بریج سازگاری `session.php` و افزودن پارامتر ابطال کش (`&_t=`) در `assets/js/notification-system.js`.
    - **تایید رسمی الگوهای ملی‌پیامک در پنل وب‌سرویس خدماتی:** الگوی ۵۳۸۹۰۴ (تله‌هلث پزشک) و الگوی ۵۳۸۹۲۴ (پیام کاربر و بیمار) هر دو به وضعیت «تایید شده» تغییر یافتند و متدهای `sendDoctorTelehealthAlert` و `sendUserChatMessageAlert` اکنون مستقیماً با سرعت بالا از طریق خط خدماتی اشتراکی ارسال می‌شوند.
    - **راستی‌آزمایی زنده در مرورگر کروم (DevTools MCP):** تست موفق و بررسی فرم‌های ثبت شماره تماس در تمامی نقش‌های پزشک، کلینیک، فروشنده، داروساز و مدیر بدون هیچ‌گونه باگ یا خطای کنسول، و استقرار موفق روی cPanel Production.
32. **رفع خطای ۵۰۰ در تأیید کد پیامکی ورود (Fix HTTP 500 on OTP Code Verification):**
    - **ریشه‌یابی و رفع Fatal TypeError در `login.php` و `includes/AuthGuard.php`:** در فرآیند ورود پیامکی (`verify_otp`) و ورود با رمز عبور (`login_password`)، پرس‌وجوی دریافت اطلاعات کاربر ستون `phone` را واکشی نمی‌کرد. در نتیجه `$user['phone']` برابر `null` شده و ارسال آن به پارامتر نوع‌داده‌دار `string $phone` در متد `AuthGuard::setRememberCookie()` در محیط PHP 8.1 سرور خطای مرگبار `TypeError` و در نتیجه خطای HTTP 500 را ایجاد می‌کرد.
    - **ایمن‌سازی و تاب‌آوری کوکی ورود پایدار ([`includes/AuthGuard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AuthGuard.php)):** تبدیل پارامتر `$phone` به حالت نال‌پذیر `?string $phone = null`، افزودن استعلام خودکار شماره از دیتابیس در صورت فقدان و قرار دادن کل فرآیند ایجاد کوکی در بلوک محافظتی `try ... catch`.
    - **تاب‌آوری فرم ورود پیامکی و ثبت‌نام خودکار ([`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php)):** افزودن ستون `phone` به کوئری‌های کاربری، تزریق فال‌بک امن `$user['phone'] ?? $phone`، ایمن‌سازی کوئری درج کاربر جدید و قرارگیری کل بلوک ورود در `try ... catch` جهت تضمین صددرصدی عدم بروز خطای ۵۰۰.
33. **رفع کامل خطای ۵۰۰ در مرحله ارسال کد پیامکی (Resolve Undefined Method 500 in send_otp):**
    - **ریشه‌یابی و رفع Fatal Error فراخوانی متد تعریف‌نشده:** در اکشن `send_otp`، متد `generateOtp()` از شیء `SmsService` فراخوانی شده بود که وجود نداشت و منجر به خطای `Fatal error: Call to undefined method SmsService::generateOtp()` و در نتیجه خطای HTTP 500 می‌شد.
    - **پیاده‌سازی متد و تاب‌آوری امنیتی ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php)):** متد رسمی و ایمن `SmsService::generateOtp(int $length = 6)` به کلاس اضافه گردید و در `login.php` تولید کد با `random_int(100000, 999999)` انجام و کل بلوک `send_otp` در `try ... catch` ایمن‌سازی شد.
    - **راستی‌آزمایی زنده در تب کروم کاربر با شماره 09146676978:** شماره موبایل کاربر در تب زنده کروم وارد و دکمه ارسال پیامک زده شد؛ کد ۶ رقمی بلافاصله پیامک گردید و صفحه به زیبایی به فرم ورود کد ۶ رقمی با تایمر شمارش معکوس و فوکوس خودکار هدایت شد.
34. **رفع جامع ۸ باگ سیستمی، همگام‌سازی سشن رمز عبور و اصلاح اسکیما و امنیت (Comprehensive Audit & 8 Core System Fixes):**
    - **رفع خروج ناخواسته پس از تغییر رمز عبور ([`actions/settings_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/settings_action.php)):** پس از تغییر کلمه عبور در تنظیمات، کلید `$_SESSION['password_hash']` با مقدار هش جدید همگام نمی‌شد که سبب می‌شد گاردهای امنیتی هدر سیستم فرض را بر سرقت سشن گذاشته و کاربر را به اجبار لاگ‌اوت کنند؛ اکنون هش سشن فوراً به‌روزرسانی شده، شناسه نشست بازتولید (`session_regenerate_id(true)`) و کوکی ورود ماندگار در صورت فعال بودن با هش جدید تمدید می‌شود.
    - **اصلاح مغایرت انوم و ثبت‌نام مشتریان عادی ([`register.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/register.php)):** رفع خطای MySQL Strict Mode ناشی از درج مقدار غیرمجاز `'customer'` در ستون `users.role` (انوم مجاز دیتابیس: `user`, `admin`, `doctor`, `organization`, `pharmacist`, `seller`, `pharmacy`)؛ تنظیم نقش به `'user'`، همگام‌سازی متغیرهای سشن، تنظیم نسخه قرارداد به `v2.0-2026` و ثبت رسمی در لجر قراردادها.
    - **رفع خطای ۵۰۰ در پنل فروشندگان جدید ([`seller/includes/seller_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/includes/seller_header.php)):** تصحیح نام ستون‌های کوئری ایجاد کیف پول فروشنده از `cleared_balance` و `in_escrow_balance` به ستون‌های واقعی اسکیما `balance_pending_escrow` و `balance_available_for_payout`.
    - **رفع خطای دیتابیس در حذف حساب کاربری فروشنده ([`actions/profile_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/profile_action.php)):** اصلاح نام ستون‌های کیف پول فروشنده در کوئری بررسی مانده حساب و بهبود خواندن نقش از سشن (`user_role`).
    - **استاتیک‌سازی متد ارسال پیامک و رفع خطای رزرواسیون لندینگ ([`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php) و [`actions/process_landing_booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/process_landing_booking.php)):** تبدیل متد `send()` به `public static function send()` با هدایت هوشمند به `App::sms()->sendDirectSms()` و پشتیبانی از هر دو حالت فراخوانی استاتیک و شیء؛ ارتقای اکشن رزرو به دریافت خطاهای `\Throwable`.
    - **اصلاح ناوبری و جلوگیری از تداخل شناسه کالاهای مرتبط ([`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php)):** الصاق خودکار `&type=pharmacy` به لینک‌های اقلام مرتبط دارویی جهت پیشگیری از تداخل با جدول محصولات پت‌شاپ؛ اصلاح لینک «مشاهده همه محصولات این دسته» به صفحه داروخانه و افزودن هندلرهای فال‌بک `onerror` به تصاویر.
    - **جلوگیری از تکثیر کارت‌های پزشکان چندمرکزی در پنل ادمین ([`admin/doctors.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/doctors.php)):** افزودن `GROUP BY d.id` و تجمیع اسامی مراکز با `GROUP_CONCAT` جهت جلوگیری از تکرار پزشکانی که در چند کلینیک یا بیمارستان فعالیت دارند و اصلاح آمار ماکرو.
    - **سخت‌سازی امنیتی و افزودن توکن ضد جعل CSRF در مدیریت سازمان‌ها ([`admin/organizations.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/organizations.php)):** اعمال اعتبارسنجی `csrf_verify()` و تزریق فیلدهای پنهان `csrf_field()` به هر ۳ فرم تغییر وضعیت مرکز، تسویه فوری پایا و ویرایش اطلاعات بانکی.

35. **ارتقای جامع و استانداردسازی سیستم اعلان‌های واقعی پلتفرم (Real-World Multi-Channel Notification Architecture):**
    - **سرویس سینگلتون اعلان‌ها و رویدادهای دامنه ([`includes/PushNotificationService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/PushNotificationService.php) و [`includes/App.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/App.php)):** افزودن متد سینگلتون `App::notifications()` و متدهای اختصاصی انتشار رویدادهای کسب‌وکار واقعی شامل `notifyOrderPlaced`, `notifyOrderStatusChanged`, `notifyAppointmentBooked`, `notifyAppointmentRescheduled`, `notifyAppointmentCompleted`, `notifyPrescriptionStatus`, `notifyChatMessageReceived`, `notifyLoyaltyPointsEarned` و `notifyPayoutIssued` با فرمت‌دهی فارسی و تعیین آیکون‌ها و دسته‌بندی‌های استاندارد.
    - **اتصال رویدادهای واقعی دامنه در فرآیندهای سیستم:**
      - تغییر وضعیت سفارشات و کد رهگیری مرسولات در [`includes/OrderLifecycleService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/OrderLifecycleService.php).
      - تغییر زمان نوبت (Reschedule) و ثبت پرونده سلامت/خاتمه ویزیت توسط پزشک در [`doctor/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/index.php).
      - تغییر وضعیت و آماده‌سازی نسخه توسط داروساز در [`pharmacist/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/index.php).
      - ارسال پیام در تله‌هلث و چت پشتیبانی توسط پزشک، مدیریت یا بیمار در [`actions/chat_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/chat_action.php).
      - پرداخت موفق سفارش و رزرو نوبت در [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php).
      - پاداش ماهانه ورود به سامانه و امتیاز وفاداری در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php).
    - **بهبود API نوتیفیکیشن‌ها ([`actions/notification_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/notification_action.php)):** پشتیبانی از پارامتر `since_id`، بازگردانی `latest_id` و الصاق خودکار دسته‌بندی موضوعی (`category`) و زمان‌سنج فارسی (`time_ago`) به تک‌تک اعلان‌ها.
    - **توست‌های شیشه‌ای شناور اختصاصی با صوت ترکیبی Web Audio ([`assets/js/notification-system.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/notification-system.js)):**
      - پیاده‌سازی متد `playNotificationChime()` با سنتز امواج سینوسی هارمونیک دولایه (G5: 784Hz سپس C6: 1046.5Hz) با استفاده از `AudioContext` بدون نیاز به دانلود هیچ‌گونه فایل MP3 خارجی و کارکرد در حالت آفلاین؛ به همراه پالس ویبره لمسی هپتیک (`navigator.vibrate`).
      - ایجاد توست شناور گلس‌مورفیسم `showInAppNotificationToast()` اختصاصی برای اعلان‌های شخصی کاربر (سفارشات، پیام‌ها، نسخه‌ها) تفکیک‌شده از تیکر سوشال‌پروف عمومی.
      - پولینگ هوشمند باتری‌محور (۲۵ ثانیه در تب فعال، ۹۰ ثانیه در تب پس‌زمینه و رفرش آنی با فعال‌شدن تب).
      - بازطراحی دراور اعلان‌ها با تب‌های ۵‌گانه مدرن («همه»، «سفارشات»، «نوبت و سلامت»، «پیام‌ها»، «باشگاه مشتریان»)، سوئیچ خاموش/روشن کردن صدای اعلان در هدر دراور با ذخیره در `localStorage`، به‌روزرسانی آپتیمیستیک (Optimistic UI) و خواندن همه بدون لودینگ.
      - اتصال زنگوله اعلان به پنل پزشکان در [`doctor/includes/doctor_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/includes/doctor_header.php) و [`doctor/includes/doctor_footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/includes/doctor_footer.php).

36. **تدوین و استقرار قانون استانداردهای طراحی لندینگ‌پیج در سطح انترپرایز ([`.agents/rules/enterprise_landing_page_ui_ux.md`](file:///.agents/rules/enterprise_landing_page_ui_ux.md)):**
    - تحلیل و بنچ‌مارک پلتفرم‌های جهانی (Stripe, Linear, Chewy, Apple) و استخراج الگوی ۷ پرده‌ای روایت بصری (The 7-Beat Narrative).
    - تبدیل بخش هیرو از تبلیغ یک محصول منفرد به ارزش پیشنهادی مقتدر پلتفرم جامع سلامت و درمان با دو دکمه اقدام هدفمند، ویجت تعاملی زنده و نوار اثبات اجتماعی (Social Proof Strip).
    - معماری شبکه نامتقارن Bento Grid جهت معرفی تفکیک‌شده ۳ رکن اصلی آسنا (دامپزشکی و تله‌هلث، داروخانه زنجیره سرد و اشتراک خودکار Autoship مدل Chewy).
    - به‌روزرسانی رفرنس‌های حاکمیتی در [`AGENTS.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/AGENTS.md) و [`PROJECT_GUIDELINES.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_GUIDELINES.md).

37. **پیاده‌سازی موتور جامع کدهای تخفیف انترپرایز و پوشش مالی از کارمزد پلتفرم (Enterprise Promo Code & Margin Absorption Engine):**
    - **قانون جامع مالی و حفظ محرمانگی کارمزد پلتفرم ([`.agents/rules/marketplace_promo_and_financial_rules.md`](file:///.agents/rules/marketplace_promo_and_financial_rules.md) و [`AGENTS.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/AGENTS.md)):** تثبیت قانون سهم ۱۵٪ کارمزد پلتفرم به عنوان توافق خصوصی B2B با مراکز و تأمین‌کنندگان؛ منع اکید هرگونه اشاره به درصد کارمزد پلتفرم در دید مشتریان؛ اعمال مالیات بر ارزش افزوده ۱۰٪ مصوب قانونی صرفاً بر مازاد خالص مشمول مالیات پس از کسر تخفیف؛ و اصل مصونیت درآمدی تأمین‌کنندگان (تأمین ۱۰۰٪ هزینه کدهای تخفیف از محل کارمزد ۱۵٪ پلتفرم و تضمین دریافت ۸۵٪ سهم ناخالص توسط فروشنده/پزشک/کلینیک).
    - **مایگریشن دیتابیس کدهای تخفیف و لاگ استفاده ([`database/migrations/18_enterprise_promo_engine.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/18_enterprise_promo_engine.sql)):** ارتقای جدول `promo_codes` با فیلدهای سقف تخفیف درصدی (`max_discount_amount`)، حداقل سبد خرید (`min_order_amount`)، محدودیت تعداد کل و کاربر، محدودیت سفارش اول؛ ایجاد جدول `promo_code_usages` جهت ثبت ردگیری اتمیک و جلوگیری از دابل‌اسپندینگ.
    - **سرویس منطق کسب‌وکار پروموشن‌ها ([`includes/PromoCodeService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/PromoCodeService.php) و [`includes/App.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/App.php)):** ایجاد سرویس کدهای تخفیف با متد سینگلتون `App::promo()` جهت اعتبارسنجی همه‌جانبه، محاسبه مالیات ۱۰٪ پس از تخفیف، ثبت اتمیک ردگیری مصرف، و مدیریت CRUD ادمین.
    - **تعدیل لجر و اسکروی هفتگی تأمین‌کنندگان ([`includes/MarketplaceEscrowService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/MarketplaceEscrowService.php)):** ثبت هزینه بازاریابی و کد تخفیف به صورت منفی از محل کارمزد پلتفرم آسنا در `platform_ledger_entries` جهت همخوانی دوبل‌انتری حسابداری بدون ریالی کسر از کیف‌پول تأمین‌کننده.
    - **رابط کاربری سبد خرید و اکشن ایجکس ([`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php) و [`actions/promo_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/promo_action.php)):** افزودن نوار مدرن اعمال کد تخفیف با کلید CSRF، نمایش ریل‌تایم نشان سبز سود تخفیف، محاسبه مجدد مالیات ۱۰٪ و مبلغ نهایی بدون ریفرش صفحه، و ذخیره امن در سشن.
    - **اتصال جریان پرداخت زرین‌پال و ثبت فاکتور ([`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php) و [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):** انتقال مبلغ تخفیف‌خورده به درگاه رسمی زرین‌پال، ذخیره کد و تخفیف در سفارش، ثبت در لجر استفاده، و هدایت خریدار به رسید رسمی فاکتور.
    - **رسید رسمی دیجیتال پرداخت خریدار ([`order_receipt.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/order_receipt.php)):** صفحه استاندارد فاکتور الکترونیک و رسید پرداخت با شناسه پیگیری زرین‌پال، اقلام، تخفیف، مالیات ۱۰٪، استایل‌های پرینت `@media print` و لینک‌دهی در پروفایل کاربر و پنل ادمین.
38. **تکمیل پنل اختصاصی مدیریت، ویرایش، پایان‌دهی آنی و ارسال اعلان همگانی کدهای تخفیف (Admin Promo Suite, Instant Termination & Multi-Channel Broadcast):**
    - **مدیریت، ویرایش و پایان‌دهی آنی ([`admin/promo_codes.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/promo_codes.php)):**
      - پیاده‌سازی فرم یکپارچه ایجاد و ویرایش کدهای تخفیف با پشتیبانی از کدهای درصدی/ثابت، تعیین سقف ریالی، حداقل سفارش، سقف دفعات، سفارش اول و بازه تاریخی.
      - افزودن دکمه پایان دادن فوری (`end_promo` / `endPromoCode()`) که فوراً وضعیت را غیرفعال کرده و تاریخ انقضا را به زمان جاری تغییر می‌دهد.
      - حذف ایمن کدها با حفظ تاریخچه لجر (در صورت داشتن تراکنش قبلی، سیستم کد را غیرفعال می‌کند تا رفرنس حسابداری مخدوش نشود).
    - **ارسال اعلان هوشمند و پیامک به کاربران سامانه (`broadcast_promo` / `broadcastPromoNotification()`):**
      - مودال اختصاصی ارسال کمپین اطلاع‌رسانی با پیش‌نویس متن ترغیب‌کننده و انتخاب جامعه هدف (همه کاربران، خریداران قبلی، یا کاربران وب‌اپلیکیشن PWA).
      - ایجاد خودکار رکورد در `user_notifications` و ارسال همزمان پیامک با وب‌سرویس ملی‌پیامک (`SmsService`) در صورت فعال بودن تیک پیامک.
    - **لینک‌دهی هوشمند و فعال‌سازی خودکار کوپن در فروشگاه و سبد خرید ([`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php) و [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php)):**
      - پشتیبانی از پارامتر `coupon=CODE` در لینک نوتیفیکیشن‌ها؛ ذخیره در سشن و نمایش بنر اطلاع‌رسانی بالای کاتالوگ فروشگاه.
39. **مهندسی ساده‌سازی فراگیر اپلیکیشن و استقرار معماری افشای تدریجی (App-Wide Simplification & Progressive Disclosure Architecture):**
    - **تدوین استاندارد حاکمیتی ساده‌سازی ([`.agents/rules/progressive_disclosure_and_app_simplification.md`](file:///.agents/rules/progressive_disclosure_and_app_simplification.md) و [`AGENTS.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/AGENTS.md)):**
      - تصویب اصل تفکیک «ویترین از کارگاه» (Showcase vs. Workshop).
      - ممنوعیت قرار دادن ابزارهای سنگین نرم‌افزاری چندمرحله‌ای (فرم‌های ۴۰۰ خطی، تقویم‌های رزرو، کاتالوگ‌های خام دارویی) مستقیماً در اسکرول صفحه اصلی.
      - استقرار معماری اطلاعات ۳ سطحی: (۱) جریان اصلی مصرف‌کننده، (۲) هاب‌ها و ابزارهای تخصصی مجزا، (۳) پرتال همکاران و متخصصین (B2B).
    - **ایجاد صفحه مستقل ابزار محاسبه‌گر کالری و تغذیه پت ([`calculator.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/calculator.php)):**
      - استخراج فرمول‌های استاندارد FEDIAF و WSAVA از صفحه اصلی به یک صفحه تخصصی مستقل با سئو قوی، انتخاب گونه سگ/گربه، اسلایدر وزن، سن و فعالیت، پیشنهاد گرم روزانه غذای خشک و آب مصرفی، راهنمای علمی، و پیوند به پت‌شاپ و تداخل دارویی.
    - **بازطراحی و کاهش ۹۰ درصدی حجم صفحه اصلی ([`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php)):**
      - کاهش خطوط کد از ۳,۴۳۱ خط (۲۵۱ کیلوبایت) به حدود ۴۳۰ خط (۲۶ کیلوبایت) با حفظ کامل زیبایی و عملکرد فوق‌سریع.
      - هیروی اقدام‌محور با جعبه جستجوی فوری ۳ تبِ نوبت‌دهی دامپزشک، پت‌شاپ و داروخانه.
      - بنتوگرید ۳ ستون خدمات، اسپات‌لایت ۴ پزشک برتر با رزرو فوری، پرفروش‌ترین‌های پت‌شاپ، تیزر کارت‌های ۳‌گانه ابزارهای سلامت، نظرات خریداران و بنر تریاژ اورژانس ۲۴ ساعته.
    - **ساماندهی سراسری ناوبری و هدر و فوتر ([`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php)):**
      - ارتقای منوی دراپ‌داون دسکتاپ به **«ابزارها و خدمات»** شامل محاسبه‌گر کالری، تداخل‌سنج دارویی، مراکز درمانی، تحویل خودکار Autoship، دانشنامه، خیریه و باشگاه مشتریان (هیچ ابزاری پنهان نیست، اما دسته‌بندی‌شده است).
      - افزودن ابزارها به منوی باتم‌شیت موبایل و ساماندهی ستون‌های فوتر سراسری به همراه فال‌بک‌های داینامیک سئو.
40. **طراحی و پیاده‌سازی سه‌بعدی جعبه‌ابزار سلامت، رندرهای اختصاصی ۳D و استقرار پروداکشن (3D Health Tools Suite, Interactive Perspective Tilt & Production Deployment):**
    - **تولید دارایی‌های بصری رندر سه‌بعدی (3D Rendered Assets):**
      - تولید و بهینه‌سازی ۳ تصویر سه‌بعدی فوق‌پیشرفته در فرمت کم‌حجم و پرسرعت WebP (هرکدام ~30KB) در [`assets/images/`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/images):
        - [`assets/images/tool-calculator-3d.webp`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/images/tool-calculator-3d.webp): دستگاه دیجیتال سه‌بعدی محاسبه کالری با کاسه طلایی غذا و متر نواری.
        - [`assets/images/tool-drug-3d.webp`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/images/tool-drug-3d.webp): شیشه داروی دامپزشکی، گوشی پزشکی، کپسول درخشان و سپر دارویی سبز ۳ بعدی.
        - [`assets/images/tool-autoship-3d.webp`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/images/tool-autoship-3d.webp): جعبه هدیه سورپرایز دوره‌ای Autoship پر از بسته‌های غذا و رد دود موشکی.
    - **ارتقای کارت‌های تعاملی با افکت زاویه و ژیروسکوپ سه‌بعدی ([`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php)):**
      - اعمال استایل‌های گلس‌مورفیسم غنی و گرادیان‌های باکنتراست عمیق با فونت‌های فوق‌واضح سفید (`#ffffff`) برای رفع کامل کم‌رنگی متون در نمایشگرهای باکیفیت.
      - پیاده‌سازی تعامل چرخش سه‌بعدی با ماوس (`perspective(1000px) rotateX(...) rotateY(...) translateY(-8px)`) بدون هیچ کتابخانه سنگین جانبی.
      - دکمه‌های ۳ بعدی برجسته با سایه‌های عمقی و نشان‌های تاییدیه معتبر جهانی (FEDIAF، پایش فارماکولوژی، و مدل Chewy).
    - **ارتقای بخش نظرات و بنر تریاژ اورژانس ۲۴ ساعته:**
      - ارتقای کارت‌های نظرات با بچ ستاره‌های طلایی متالیک و کنتراست فوق‌العاده بالا برای متن‌های نقل قول و آواتارهای برجسته ۳ بعدی.
      - بازطراحی بنر تریاژ اورژانس با بیکن راداری پالسی و نورپردازی پس‌زمینه زرشکی-سرمه‌ای عمیق.
41. **اصلاح و استانداردسازی تیترها، فاصله‌گذاری اسکرول لنگری و تایپوگرافی فارسی (Refined Section Titles, Anchor Scroll Offset & Semantic Hierarchy):**
    - **اصلاح عنوان و تگ جعبه‌ابزار سلامت ([`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php)):**
      - رفع تکرار عنوان در بج بالای بخش: تغییر از عبارت تکراری «جعبه‌ابزار پیشرفته محاسباتی و هوشمند» به «سامانه‌های هوشمند مراقبت و محاسبات بالینی».
      - اضافه کردن `scroll-mt-28 lg:scroll-mt-32 pt-6` به تگ `<section id="health-tools">` جهت رفع کامل مشکل هم‌پوشانی هدر چسبان (Sticky Header) هنگام کلیک روی لینک یا هدایت مستقیم مرورگر به لنگر `#health-tools`.
    - **بهینه‌سازی و هماهنگ‌سازی عناوین کارت‌های سه‌بعدی:**
      - یکدست‌سازی طول و وزن عناوین کارت‌ها: `محاسبه‌گر کالری و تغذیه پت`، `پایشگر هوشمند تداخلات دارویی`، و `اشتراک تحویل خودکار <span dir="ltr">(Autoship)</span>` با تفکیک جهت متن LTR برای واژه انگلیسی جهت جلوگیری از اعوجاج در RTL.
    - **استانداردسازی تگ‌های هدینگ و جلوگیری از برش حروف کشیده فارسی:**
      - اعمال ارتفاع خط متناسب `leading-snug py-1` بر روی تمام عناوین اصلی صفحات برای جلوگیری از برش سرکش‌ها و تنوین‌های فونت وزیرمتن در مرورگرهای مختلف.
      - ارتقای تگ بخش نظرات از `<h3>` به تگ استاندارد معنایی `<h2>` جهت ایجاد ساختار سلسله‌مراتبی بی‌نقص سئو در کل صفحه.

42. **ارتقای جامع PWA، کارنامه تغذیه بالینی و BMI پت، و ابزار شفافیت گردش مالی کاربران و همکاران (PWA Overhaul, Pet Clinical Nutrition Report & Financial Transcript):**
    - **ارتقا و نوسازی وب‌اپلیکیشن پیش‌رونده ([`sw.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/sw.js) و [`site.webmanifest`](file:///opt/lampp/htdocs/asena/asena-enterprise/site.webmanifest)):**
      - ارتقای کش سرویس‌ورکر به نسخه `v1.2.0` و پیش‌کش دارایی‌های تصویری سه‌بعدی (`tool-*-3d.webp`).
      - افزودن شناسه یکتای `id: "/"` و `display_override` و میانبر اختصاصی محاسبه‌گر تغذیه به مانیفست اپلیکیشن.
      - پیاده‌سازی گوش‌به‌زنگ نسخه جدید (`pwa:update-available`) و توست شیشه‌ای اطلاع‌رسانی با دکمه به‌روزرسانی آنی.
    - **توسعه سرویس ارزش‌افزا و کارنامه تغذیه بالینی پت ([`calculator.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/calculator.php) و [`actions/save_nutrition_report.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/save_nutrition_report.php)):**
      - افزودن سنجش شاخص وضعیت بدنی (Body Condition Score 1-9) و تخمین وزن ایده‌آل هدف بر اساس استانداردهای جهانی WSAVA و FEDIAF.
      - محاسبه سقف مجاز تشویقی (قانون ۱۰٪)، جدول زمان‌بندی ۷ روزه تغییر تدریجی جیره جهت پیشگیری از شوک گوارشی.
      - پیاده‌سازی مودال و نسخه چاپی کارنامه رسمی تغذیه بالینی با مهر نظام دامپزشکی، هولوگرام دیجیتال، بارکد اصالت‌سنجی و امکان ثبت مستقیم در پرونده سلامت الکترونیک پت (`actions/save_nutrition_report.php`).
    - **کارنامه شفافیت مالی خریدهای من و درآمد همکاران ([`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php)):**
      - ایجاد ویجت سبک و فشرده گردش مالی در پیشخوان مشتریان با تفکیک بازه‌های ۱ هفته، ۱ ماه، ۶ ماه و ۱ سال (مجموع پرداختی، سود از تخفیف‌های آسنا، سفارشات موفق و میانگین فاکتور بدون افشای کارمزد پلتفرم مطابق قانون شماره ۷).
      - ایجاد ویجت کارنامه شفافیت فروش و تسویه‌حساب همکاران فروشگاهی و کلینیکی با نمایش فروش ناخالص، سهم خالص دریافتی (۸۵٪) و وضعیت واریز پایا.
    - **کامپایل پروداکشن Tailwind CSS:** بازسازی موفقیت‌آمیز استایل‌ها در `assets/css/tailwind.output.css` با دستور `./bin/build-css`.

43. **پیاده‌سازی سامانه پایش تداخلات دارویی و منع مصرف پت و اتصال چرخه پرداخت محاسبه‌گر بالینی (Pet Drug Interaction Checker & Calculator Monetization Flow):**
    - **سامانه هوشمند تداخل‌سنج دارویی و فارماکوپیای بالینی ([`interactions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/interactions.php)، [`drug_interactions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/drug_interactions.php) و [`includes/DrugInteractionService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/DrugInteractionService.php)):**
      - توسعه موتور دوگانه فارماکولوژی دامپزشکی بر پایه رفرنس‌های جهانی Plumb's و BSAVA با ترکیب هوش مصنوعی زنده (مدل‌های AvalAI / Gemini) و موتور قوانین آفلاین با بیش از ۵۰ قاعده بحرانی (منع مصرف کورتون + NSAID، سمیت کشنده استامینوفن و پرمترین در گربه، جهش ژنتیکی MDR1 در نژادهای کالی و شپرد، کلاتاسیون فلوروکینولون‌ها با سوکرالفات، سمیت شنوایی آمینوگلیکوزیدها با فوروزماید، و سندرم سروتونین ترامادول).
      - جستجوی بلادرنگ در میان ۸۰۰+ قلم داروی کاتالوگ داروخانه دامپزشکی (`pharmacy_medicines`) با اتوکامپلیت ایجکس در [`actions/ai_drug_analysis.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/ai_drug_analysis.php).
      - طراحی رابط کاربری بنتو با سنجشگر بصری سطح خطر (بحرانی 🔴، احتیاط 🟠، تداخل متوسط 🟡 و ایمن 🟢)، جدول ساعات فاصله زمانی مصرف داروها، و امکان ثبت مستقیم در پرونده پزشکی پت با شناسه اختصاصی (`actions/save_drug_report.php`) و نسخه چاپی بدون نیاز به ابزار خارجی.
      - تفکیک پاکیزه پرتال مالی و پیامک همکاران به نشانی اختصاصی [`partner_interactions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/partner_interactions.php) و به‌روزرسانی ریدایرکت‌ها در پنل‌های پزشک، فروشنده و کلینیک.
    - **اتصال چرخه پرداخت و درآمدزایی محاسبه‌گر بالینی ([`calculator.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/calculator.php)، [`actions/initiate_meal_plan_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/initiate_meal_plan_payment.php) و [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):**
      - پشتیبانی کامل از سوییچ پولی/رایگان در پنل ادمین (`admin/finance_settings.php`).
      - در حالت خاموش (رایگان)، صدور آنی کارنامه و فایل جدول برنامه غذایی به صورت هدیه آسنا ثبت می‌گردد.
      - در حالت روشن (پولی)، دکمه صدور مبلغ تعیین‌شده را نمایش داده و پس از تایید لاگین، تراکنش در جدول `payment_transactions` ثبت و کاربر به درگاه بانکی فعال هدایت می‌شود.
      - پس از پرداخت موفق، اکشن `complete_payment.php` به صورت اتمیک فایل رسمی جدول برنامه غذایی را با `MealPlanGenerator` تولید، در پرونده سلامت پت (`pet_documents`) و `user_pets` آرشیو، و کاربر را به صفحه نمایش کارنامه هدایت می‌نماید.

44. **ساده‌سازی بنیادین و بازطراحی مدرن پایشگر تداخلات دارویی از حالت داشبورد پیچیده به رابط بالینی روشن و ارگونومیک (Drug Interaction Checker UI Simplification & Progressive Disclosure):**
    - **حذف پوسته شلوغ و تیره کاکپیت هواپیما ([`interactions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/interactions.php)):**
      - جایگزینی ظرف تیره ۲ ستونه با کادر تمیز و روشن بالینی در مرکز صفحه (`bg-white rounded-3xl border border-slate-200 shadow-xl max-w-4xl mx-auto`).
      - تبدیل سوییچر گونه‌ها به تب‌های خلوت و سریع (سگ، گربه، اسب، پرنده، اگزوتیک).
      - کانون توجه قرار گرفتن نوار جستجوی هوشمند داروها با اتوکامپلیت ایجکس و چیپ‌های خلوت داروی انتخابی با امکان حذف سریع.
      - جایگزینی هشدارهای رنگی بزرگ سناریوها با چیپ‌های متنی ملایم برای تست سریع.
      - اعمال اصل افشای تدریجی (Progressive Disclosure - Rule 8): انتقال فیلدهای غیرضروری نام، نژاد، وزن، ۸ چیپ بیماری‌های زمینه‌ای و باکس علائم به دراور بازشونده اختیاری («مشخصات تکمیلی، وزن، بیماری‌های زمینه و یادداشت بالینی»).
      - ایجاد تک‌دکمه فراخوان شاخص سرمه‌ای سازمانی («شروع پایش و تحلیل بالینی تداخلات دارویی»).
      - نمایش هوشمند نتایج تنها پس از اجرای تحلیل با بنر تمیز خروجی، کارت‌های تداخل، موارد منع مصرف و برنامه زمانی با پالت آرام‌بخش و رسمی، و تثبیت متن سلب مسئولیت قانونی در پایین صفحه.
45. **ارگونومی شِل پنل کاربری، سایدبار تاشوی دسکتاپ و پنهان‌سازی هوشمند فوتر بازاریابی در داشبوردها (App Shell Ergonomics, Collapsible Desktop Sidebar & Workspace Footer Suppression):**
    - **پنهان‌سازی فوتر بازاریابی در فضاهای کاری عملیاتی ([`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php) و [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php)):**
      - افزودن شرط `<?php if (empty($hideMarketingFooter)): ?>` به بلوک ۵ ستونه فوتر و کپی‌رایت عمومی بدون ایجاد اختلال در اسکریپت‌ها، توست‌های شناور، سرویس‌ورکر PWA و نوبار موبایل.
      - فعال‌سازی پرچم `$hideMarketingFooter = true;` در پنل کاربری `profile.php`، برطرف‌سازی دائمی معضل همپوشانی و بلاک‌شدن فوتر توسط سایدبار فیکس راست، و آزادسازی کامل فضای عمودی کاربر.
    - **طراحی سایدبار تاشوی دسکتاپ با ریل آیکون‌ها و ذخیره‌سازی وضعیت ([`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php)):**
      - پیاده‌سازی سوییچر جمع‌شونده دسکتاپ (`toggleDesktopSidebar()`) با کلید فشرده هدر سایدبار و ترنزیشن روان CSS میان حالت عریض (`w-64` / `lg:mr-64`) و حالت فشرده ریل آیکون (`w-20` / `margin-right: 5rem`).
      - ذخیره‌سازی خودکار وضعیت ترجیحی کاربر در `localStorage` و اعمال آنی پیش از رندر جهت پیشگیری از پرش محتوا (FOUC).
      - مجهزسازی کلیه پیوندهای ناوبری به برچسب‌های `.sidebar-label`، تولتیپ‌های استاندارد فارسی (`title`) جهت سهولت دسترسی در حالت فشرده، و حفظ ۱۰۰٪ سازگاری با منوی کشویی موبایل (`toggleProfileSidebar()`).
    - **تدوین استاندارد جامع ارگونومی پنل‌ها در مستند قوانین ([`.agents/rules/enterprise_user_profile_ux.md`](file:///.agents/rules/enterprise_user_profile_ux.md)):**
      - الحاق بخش ۵ مستند قوانین با محوریت استانداردهای معماری پنل‌های وب‌اپلیکیشن (Full-Height App Shell، پالت میانبرهای سریع `Ctrl+K`، اکشن‌بار شناور چسبان، ریل آیکون و بازخورد بدون انسداد).

46. **اصلاح نهایی ارگونومی شِل سایدبار، رفع تداخل با هدر سایت و زیباسازی اسکرول‌بار ([`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php)):**
    - **حذف بنر شناور تبلیغاتی در پنل (`$hideMarketingHeader`):** پنهان‌سازی هوشمند نوار نوتیفیکیشن شگفت‌انگیز در فضای کاربری پروفایل بدون دستکاری سایر صفحات.
    - **تمام‌ارتفاع شدن سایدبار (`top-0 bottom-0`):** تبدیل سایدبار به پنل استاندارد فول هایت و نمایش ۱۰۰٪ شفاف عنوان پنل و دکمه چورون جمع‌کننده بدون هیچ‌گونه همپوشانی.
    - **فاصله‌گذاری پویای هدر سایت (`margin-right` متناسب با باز/بسته بودن سایدبار):** جلوگیری قطعی از تصادم کپسول هدر با سایدبار در تمام رزولوشن‌های دسکتاپ.
    - **اسکرول‌بار ظریف و مدرن ۴ پیکسلی:** حذف اسکرول‌بار خاکستری قدیمی مرورگر و جایگزینی با اسکرول‌بار نیمه‌شفاف و شیک اختصاصی.

47. **ارتقای استانداردهای تجربه کاربری PWA، واکنش‌گرایی اندروید و انعطاف‌پذیری آفلاین (PWA & Android Responsive UX Standards):**
    - **تدوین قاعده رسمی ارگونومی موبایل و PWA ([`.agents/rules/pwa_and_android_responsive_ux.md`](file:///.agents/rules/pwa_and_android_responsive_ux.md)):**
      - ثبت استاندارد رسمی ارگونومی شست (تمرکز اقدامات روی ۴۰٪ پایینی نمایشگر)، حداقل ابعاد لمسی ۴۸×۴۸dp، حذف تاخیر ۳۰۰ میلی‌ثانیه‌ای تاچ با `touch-action: manipulation` و افشای تدریجی فرم‌ها.
      - اضافه شدن بند ۹ به مستند مادر [`AGENTS.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/AGENTS.md).
    - **ارتقای شورت‌کات‌های مانیفست اندروید ([`site.webmanifest`](file:///opt/lampp/htdocs/asena/asena-enterprise/site.webmanifest)):**
      - بهینه‌سازی لانچرهای سریع صفحه اصلی به ۴ ابزار پرکاربرد: پرونده سلامت حیوانات، تداخل‌سنج داروها، محاسبه‌گر کالری و رژیم غذایی، و نوبت‌دهی آنلاین کلینیک.
    - **نوار وضعیت هوشمند شبکه و ارتقای کش ([`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php) و [`sw.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/sw.js)):**
      - پیاده‌سازی نوار ظریف وضعیت آفلاین (`#offline-status-strip`) با پایش خودکار رویدادهای `online`/`offline` شبکه بدون مسدود کردن فرم‌ها یا پاک شدن اطلاعات کاربر.
      - افزودن ژست لمسی کشیدن به پایین (Swipe-down to dismiss) برای باتم‌شیت‌ها (`.mobile-bottom-sheet`) مشابه اپلیکیشن‌های بومی اندروید.
      - ارتقای کش سرویس‌ورکر به نسخه `v1.3.0`.
48. **اصلاح بنیادین ارگونومی اسکرول دسکتاپ و تاچ‌پد لینوکس در پنل کاربری (App Shell Scroll Ergonomics & Touchpad Resolution):**
    - **تفکیک `touch-action` از بدنه و رفع قفل اسکرول لینوکس ([`assets/css/enterprise-ui.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/enterprise-ui.css)):**
      - حذف `touch-action: manipulation` از تگ‌های `body` و `html` و محدودسازی دقیق آن به المان‌های تعاملی (`button, input, select, textarea, a, .btn`)؛ این تداخل عامل اصلی قفل شدن ژست اسکرول دو انگشتی تاچ‌پد در مرورگر کروم روی سیستم‌عامل لینوکس بود.
      - تنظیم اسکرول قطعی ریشه سند روی `html { overflow-y: scroll; scroll-behavior: smooth; }` جهت پایداری ناوبری و عدم پرش عرض صفحه.
    - **زنجیره‌سازی هوشمند اسکرول سایدبار (Sidebar Wheel Event Chaining) در [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php):**
      - افزودن لیسنر اختصاصی چرخ ماوس به سایدبار (`#profile-sidebar`) جهت انتقال خودکار اسکرول به صفحه اصلی در صورت عدم نیاز سایدبار به اسکرول یا رسیدن به انتهای لیست، تا کاربر در صورت قرار گرفتن نشانگر ماوس روی فضای سایدبار هرگز احساس گیر کردن نکند.
      - افزودن کلاس `min-h-0` به منوی ناوبری سایدبار جهت انقباض استاندارد فلکس‌باکس و دسترسی همیشگی به دکمه‌های فوتر سایدبار (پشتیبانی و خروج).
      - افزایش فاصله انتهای فضای کاربری (`padding-bottom: 8rem`) برای ایجاد حاشیه تنفس دیداری و پیمایش آزادانه کارت‌های زیرین پنل.
      - محدودسازی قفل اسکرول بدنه (`document.body.style.overflow = 'hidden'`) صرفاً به صفحات کوچک موبایل (`window.innerWidth < 1024`).

49. **یکپارچه‌سازی نقشه ملی مپ (Map.ir)، اعتبارسنجی الگوریتمی کد پستی ۱۰ رقمی و تعیین خودکار محدوده مکانی منزل (Map.ir Integration & Iranian Postal Intelligence):**
    - **سرویس مرکزی نقشه و اطلاعات جغرافیایی ایران ([`includes/MapService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/MapService.php)):**
      - تعریف تایل‌های رستر نقشه شیوه مپ (`https://map.ir/shiveh/xyz/1.0.0/Shiveh:Shiveh@EPSG:3857@png/{z}/{x}/{y}.png`) با کلید دسترسی فعال JWT.
      - اتصال به سرویس معکوس آدرس‌یابی مپ (`https://map.ir/reverse`) با متادیتای باکیفیت فارسی (استان، شهر، محله، معبر اصلی و نشانی استاندارد پستی) و فال‌بک خودکار به Nominatim.
      - دایرکتوری غنی پیش‌شماره‌های پستی ۳۱ استان و شهرهای کشور همراه با مختصات مرکز ثقل جغرافیایی هر ناحیه.
    - **اعتبارسنجی الگوریتمی دقیق کد پستی ایران در فرانت‌اند و بک‌اند ([`actions/postal_code_lookup.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/postal_code_lookup.php)، [`actions/profile_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/profile_action.php) و [`actions/settings_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/settings_action.php)):**
      - تبدیل خودکار ارقام فارسی/عربی به انگلیسی و حذف کاراکترهای نامعتبر.
      - انطباق ۱۰۰٪ با استاندارد رسمی شرکت ملی پست: ۵ رقم اول (کد رهسپاری) بدون ۰ و ۲ (`[13-9]{5}`) و ۵ رقم دوم (کد توزیع ساختمان/واحد) از تمامی ارقام ۰ تا ۹ (`[0-9]{5}`) بدون محدودیت‌های اشتباهی در تکرار ارقام پلاک، و رد توالی‌های فیک ۱۰ رقم یکسان یا کدهای آزمایشی نظیر ۱۲۳۴۵۶۷۸۹۰.
      - جلوگیری قطعی در لایه کنترلر بک‌اند از ذخیره هرگونه کد پستی ساختگی و نامعتبر.
    - **جایگزینی تایل‌های OpenStreetMap با نقشه مپ و ارگونومی تعیین موقعیت در پروفایل ([`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php) و [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php)):**
      - نمایش نقشه ملی Map.ir در تب نشانی‌های پروفایل با وضوح بالا، نام‌گذاری معابر به زبان فارسی و نشانگر تعاملی.
      - پایش بلادرنگ ورودی کد پستی در فرانت‌اند: شمارشگر زنده ارقام، نشانگر وضعیت در حال استعلام، بج وضعیت اعتبار، و پرواز نرم دوربین نقشه (`flyTo`) به محدوده کد پستی کاربر همراه با پیشنهاد قرار دادن پین روی درب ورودی منزل.
      - همگام‌سازی استخراج آدرس مپ و کد پستی هنگام جابجایی یا کلیک روی نشانگر نقشه.
    - **به‌روزرسانی خط‌مشی امنیت محتوا ([`includes/SecurityMiddleware.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SecurityMiddleware.php)):**
      - مجازسازی دامنه‌های `https://map.ir` و `https://*.map.ir` در هدر `connect-src` استاندارد CSP.

50. **پیکربندی کران‌جاب‌های پارس‌پک و موتور پشتیبان‌گیری خودکار دیتابیس (ParsPack Cron Jobs & Resilient Database Backup Engine):**
    - **موتور پشتیبان‌گیری پایگاه‌داده ([`scripts/backup_db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/scripts/backup_db.php)):** پیاده‌سازی معماری دوگانه بک‌آپ (mysqldump فشرده با فال‌بک استریم PDO در پکت‌های ۵۰۰ تایی)، پوشه امن با دسترسی 0700 و `.htaccess`، و پاکسازی خودکار آرشیوهای بیش از ۱۴ روز.
    - **ارتقای اسکریپت شل ([`scripts/backup.sh`](file:///opt/lampp/htdocs/asena/asena-enterprise/scripts/backup.sh)):** ارجاع به موتور جدید PHP با پشتیبانی از سیستم‌عامل‌های لینوکسی.
    - **ثبت دو کران‌جاب در پنل هاست پارس‌پک:** بک‌آپ روزانه شب‌ها ساعت ۳ بامداد (`0 3 * * *`) و پردازشگر اتوشیپ شب‌ها ساعت ۰۰:۳۰ بامداد (`30 0 * * *`).

51. **پاکسازی رابط کاربری و رفع فایل‌های قدیمی (UI Header Cleanup & Artifact Maintenance):**
    - **حذف دکمه تکراری «حساب کاربری» در هدر ([`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php)):** حذف پیل باتن اضافی و یکپارچه‌سازی کامل ناوبری کاربر در هدر و منوی کناری.
    - **حذف فایل منسوخ `issues.txt`:** به دلیل صحت کامل عملکرد سیستم OTP، ورود پیامکی، احراز هویت دوطرفه و مدل‌های هوش مصنوعی.

52. **تست جامع سناریومحور تمامی نقش‌ها، رفع باگ درگاه شبیه‌ساز و خودترمیمی اسکیما v5 (Comprehensive Scenario QA, Mock Gateway Fix & Schema v5):**
    - **رفع باگ کوئری استرینگ درگاه شبیه‌ساز ([`mock_payment_gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/mock_payment_gateway.php) و [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):** تشخیص وجود کاراکتر `?` در URL کال‌بک و افزودن صحیح پارامترها با `&`؛ تجزیه تاب‌آور شناسه‌های `Authority`, `authority`, و `tx` در پردازش بازگشت پرداخت بانکی.
    - **خودترمیمی دیتابیس v5 ([`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php)):** اجرای خودکار مایگریشن‌های ۱۸ و ۱۹ در لایو سرور؛ ایجاد خودکار جدول `promo_codes`, `promo_code_usages`, ستون‌های سفارشات و `reserved_stock`.
    - **درج پویا و مقاوم سفارشات (Dynamic Column Introspection):** بازنویسی استعلام درج در جدول `orders` در [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php) و [`includes/AutoshipService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AutoshipService.php) با بررسی بلادرنگ ستون‌های موجود در جدول، جهت پیشگیری از خطاهای عدم وجود فیلد در دیتابیس‌های مختلف.
    - **ثبت سفارشات واقعی و صدور فاکتور رسمی:** تست موفق سفارش عادی #PC-8 و سفارش اتوشیپ #PC-9 با صدور فاکتور الکترونیک رسمی ماده ۱۶۹ در `order_receipt.php`.
53. **تثبیت سیاست مالی اتوشیپ بدون کارمزد و تسویه ۱۰۰٪ درآمد به تأمین‌کننده (Autoship Zero-Commission Policy & 100% Seller Payout):**
    - **سیاست حاکمیتی مالی ([`.agents/rules/marketplace_promo_and_financial_rules.md`](file:///.agents/rules/marketplace_promo_and_financial_rules.md)):** اضافه شدن بند ۲.۱ مبنی بر اینکه تخفیف ۱۵٪ ارائه‌شده به خریدار در سفارشات تحویل دوره‌ای خودکار (Autoship)، ۱۰۰٪ از محل انصراف شرکت آسنا از سهم کارمزد پلتفرم تأمین می‌شود. شرکت آسنا هیچ‌گونه کارمزدی (۰ تومان) در اتوشیپ کسر نمی‌کند، فروشنده ۱۰۰٪ عایدی حاصل از فروش کالا را دریافت می‌نماید، و کاربر صرفاً هزینه محصول تخفیف‌خورده + هزینه ارسال پستی + ۱۰٪ مالیات مصوب قانونی را می‌پردازد.
    - **موتور اسکرو و تسویه وجوه ([`includes/MarketplaceEscrowService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/MarketplaceEscrowService.php)):** تنظیم نرخ کارمزد روی `commission_rate = 0.00` و `commission_amount = 0` برای سفارشات اتوشیپ، و واریز ۱۰۰٪ مبلغ به سهم فروشنده (`net_seller_amount = gross_amount`).
    - **ثبت سفارشات اتوشیپ ([`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php)):** ثبت پایدار فیلدهای `order_type = 'autoship'` و `is_autoship = 1` در رکورد سفارش جهت همگام‌سازی بی‌نقص با موتور اسکرو.
    - **خودترمیمی دیتابیس نسخه ۶ ([`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php)):** افزودن فیلدهای مربوطه به جداول `orders` و `order_items` و تعدیل عطف‌به‌ماسبق سفارش اتوشیپ قبلی #9 (تبدیل کارمزد از ۲۰۸,۲۵۰ تومان به ۰ و اعاده ۲,۰۸۲,۵۰۰ تومان کامل به سهم خالص فروشنده).
55. **اصلاح مسیریابی و پاکسازی پروفایل، حذف پسوند php از آدرس‌ها و سامانه هوشمند اعتبارسنجی پروانه و دانشنامه پزشکان با هوش مصنوعی (Clean URLs, Profile Routing Fixes & AI Doctor License Verification):**
    - **اصلاح ریدایرکت‌های پروفایل و پاکسازی کدهای منسوخ ([`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php)):**
      - پاکسازی بیش از ۴۰۰ خط کد HTML/JS مرده و تبدیل فایل به یک روتر هوشمند و سبک جهت انتقال روان کاربر به تب مشخص شده در کوئری‌استرینگ (`?tab=...#...`).
      - اصلاح لینک‌های ریدایرکت کسری نشانی در [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php) و [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php) از `profile_settings.php` به صورت مستقیم به `profile.php?tab=addresses#addresses`.
      - اصلاح لینک تنظیمات در [`rewards.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/rewards.php) به `profile.php?tab=personal-info#personal-info`.
    - **رفع باگ ارسال فرم‌ها در لودر پنجه ([`assets/js/paw-loader.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/paw-loader.js)):**
      - به تعویق انداختن `submitBtn.disabled = true` با `setTimeout(..., 0)` تا مانع از ارسال مقادیر نام و دکمه کلیک‌شده در بدنه درخواست‌های POST نشود.
    - **حذف پسوند `.php` و بهینه‌سازی مسیرها (Clean Extensionless URLs در [`.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/.htaccess)):**
      - پیاده‌سازی ریدایرکت استاندارد ۳۰۱ خارجی برای متدهای GET و HEAD از آدرس‌های دارای پسوند به آدرس‌های تمیز، با مستثنی‌سازی دقیق پوشه‌های `/actions/` و `/api/` و درخواست‌های POST.
      - بازنویسی داخلی هوشمند با پرچم `QSA` برای حفظ پارامترهای ارسالی در کلیه صفحات و زیرپوشه‌ها (`/admin/`, `/doctor/`, `/organization/`, `/pharmacist/`, `/seller/`).
    - **موتور اعتبارسنجی هوشمند پروانه و دانشنامه پزشکان با هوش مصنوعی چندوجهی ([`includes/DoctorVerificationService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/DoctorVerificationService.php)):**
      - توسعه سرویس مستقل بررسی بینایی (AI Vision) مدارک، دانشنامه‌ها و کارت‌های نظام دامپزشکی متقاضیان عضویت.
      - استخراج مشخصات پزشک، شماره نظام دامپزشکی، دانشگاه صادرکننده، مقطع تحصیلی و انطباق با قواعد سازمان نظام دامپزشکی جمهوری اسلامی ایران (IRVC).
      - مکانیزم جایگزین هوشمند (Heuristic Rule-Based Fallback) بر اساس ماتریس دانشکده‌های دامپزشکی معتبر ایران و فرمت استاندارد شماره نظام.
      - صدور نمره اطمینان (۰ تا ۱۰۰)، وضعیت اعتبارسنجی (`verified`, `needs_review`, `rejected`) و گزارش مدیریتی فارسی.
    - **یکپارچه‌سازی با ثبت‌نام و هیئت ممیزی ادمین ([`includes/RoleVerificationService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/RoleVerificationService.php) و [`admin/verifications.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/verifications.php)):**
      - اجرای خودکار ارزیابی هوش مصنوعی به محض بارگذاری مدارک پزشک در `submitApplication()`.
      - افزودن اکشن استعلام مجدد هوش مصنوعی به صورت On-Demand در پنل مدیریت.
      - طراحی کارت بصری لوکس وضعیت هوش مصنوعی همراه با بج رنگی، نمره اطمینان، متن گزارش کارشناسی و جدول مشخصات استخراج‌شده در کنار فرم تأیید ۱-کلیکی.
    - **خودترمیمی دیتابیس نسخه ۷ ([`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php)):**
      - استقرار گیت خودکار ایجاد ستون‌های `ai_status`, `ai_confidence`, `ai_report`, `ai_data_json`, `ai_verified_at` در جدول `role_applications`.
    - **مجموعه آزمون خودکار ([`tests/test_ai_license_verification.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/tests/test_ai_license_verification.php)):**
      - اجرای ۱۵ تست موفق شامل بررسی فرمت شماره نظام، الگوریتم هوش مصنوعی، ریدایرکت آدرس‌ها و سلامت اسکریپت‌ها.

56. **مهاجرت پیوندهای سراسری تمپلیت‌ها به آدرس‌های بدون پسوند (Template Navigation Clean URLs Migration):**
    - **هدر سراسری دسکتاپ و موبایل ([`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php)):**
      - تبدیل کلیه لینک‌های ناوبری اصلی، منوی دراپ‌داون ابزارها، اکشن فرم‌های جستجو، دکمه ورود/پروفایل، سبد خرید و منوی کشویی موبایل از فرمت `.php` به فرمت مدرن و تمیز بدون پسوند (`shop`, `booking`, `calculator`, `interactions`, `organizations`, `subscriptions`, `knowledge_base`, `charity`, `rewards`, `profile`, `login`, `cart`, `./`).
      - حفظ سازگاری کامل وضعیت تب فعال (`$current_page`) بر مبنای `PHP_SELF`.
    - **فوتر سراسری، باتم‌شیت و نوار ناوبری ۵ تبِ موبایل ([`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php)):**
      - ارتقای پیوندهای ستون‌های چهارگانه فوتر، دسته‌بندی‌های سریع باتم‌شیت دیجی‌کالایی و تبار پایین موبایل به آدرس‌های بدون پسوند.
    - **صفحه اصلی و کارت‌های تعاملی ([`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php)):**
      - تبدیل فرم‌های هیرو (`booking`, `shop`)، پیوندهای ستون‌های سه‌گانه بنتوگرید، دکمه‌های رزرو پزشکان و کارت‌های سه‌بعدی ابزارهای سلامت به پیوندهای تمیز بدون پسوند.
    - **راستی‌آزمایی جامع شبکه (Network Verification):**
      - تست موفقیت‌آمیز تمامی روت‌های تمیز (`shop`, `booking`, `cart`, `calculator`, `interactions`, `subscriptions`, `organizations`, `knowledge_base`, `charity`, `contact`, `about`, `terms`, `privacy`) با دریافت کد HTTP 200 روی سرور عملیاتی.

57. **اصلاح روت داروخانه، بایگانی خودکار پوشه دمو قدیمی و یکپارچه‌سازی کامل پیوندهای تمیز داروخانه (Pharmacy Clean URL Routing & Legacy Standalone Neutralization):**
    - **ریشه‌یابی و خنثی‌سازی تعارض پوشه فیزیکی سرور:**
      - مشخص گردید پوشه دمو مستقل و قدیمی `pharmacy/` که در کامیت‌های اولیه از گیت حذف شده بود، به دلیل رفتار دستور `cp -rf` در سی‌پنل همچنان روی سرور فیزیکی باقی مانده بود و وب‌سرور لایت‌اسپید با تشخیص دایرکتوری فیزیکی، درخواست‌های `/pharmacy` را به `/pharmacy/` ریدایرکت کرده و وب‌اپلیکیشن مستقل دمو با پوسته سبز را اجرا می‌نمود.
    - **مکانیزم بایگانی خودکار در هسته پیکربندی ([`config.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config.php)):**
      - اضافه شدن قطعه کد خودترمیمی جهت تغییر نام و بایگانی آنی پوشه فیزیکی `pharmacy/` به `.legacy_pharmacy_archived_<timestamp>` و حذف فایل `index.php` قدیمی به محض اجرای هر درخواست PHP در وب‌سرور.
    - **پاکسازی در فرایند استقرار سی‌پنل ([`.cpanel.yml`](file:///opt/lampp/htdocs/asena/asena-enterprise/.cpanel.yml)):**
      - درج تسک پیش‌فرض حذف دائمی `/bin/rm -rf $DEPLOYPATH/pharmacy/ 2>/dev/null || true` پیش از رونوشت فایل‌ها.
    - **هدایت اجباری و بازنویسی مسیر در [`.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/.htaccess):**
      - افزودن قواعد صریح در ابتدای فایل جهت هدایت ۳۰۱ از `/pharmacy/` به `/pharmacy` و بازنویسی داخلی امن به `pharmacy.php`.
    - **مهاجرت سراسری کلیه پیوندهای داروخانه به آدرس بدون پسوند (`pharmacy`):**
      - به‌روزرسانی هدر (`includes/header.php`)، فوتر (`includes/footer.php`)، صفحه اصلی (`index.php`)، فروشگاه (`shop.php`)، صفحه محصول (`product_details.php`)، سبد خرید (`cart.php`)، داشبورد (`profile.php`)، پایش تداخلات (`interactions.php`)، نمایه مراکز (`organization_profile.php`)، جستجوی زنده (`actions/live_search.php`)، نقشه سایت (`sitemap.php`) و صفحه اختصاصی داروخانه (`pharmacy.php`).

58. **پایش و ارزیابی پنل کاربری AvalAI، بهینه‌سازی مدل‌ها به ارزان‌ترین پلن‌های موجود (Nano & Flash-Lite) و پیاده‌سازی معماری آبشاری مدل‌های کاملاً رایگان (AvalAI Panel Audit & Multi-Provider Free AI Integration):**
    - **بررسی و تست زنده حساب کاربری AvalAI از طریق پنل کروم (`09146676978`):**
      - ورود و پایش بخش‌های پیشخان، مدل‌ها، کلید‌های API، موجودی و بسته‌های اعتباری.
      - احراز کلید فعال `asena.company` با پسوند `ehsl` مطابق با کلید ثبت‌شده در `.env`.
      - مشخص گردید موجودی تومانی و واحدی حساب در حال حاضر ۰ تومان است؛ بررسی فنی و تست مستقیم با API نشان داد که گیت‌وی AvalAI حتی برای مدل‌های پایه و ارزان، در صورت موجودی صفر خطای `quota_exceeded` بازمی‌گرداند و کارکرد آن منوط به داشتن حداقل موجودی در کیف‌پول است.
    - **بهینه‌سازی تنظیمات به ارزان‌ترین مدل‌های ممکن در پلتفرم AvalAI ([`.env`](file:///opt/lampp/htdocs/asena/asena-enterprise/.env)):**
      - تغییر مدل‌های چت و متون به `gpt-5-nano` (با هزینه ۰.۰۵ واحد به ازای ۱ میلیون توکن؛ کمترین هزینه در کل پلتفرم معادل حدود ۱۱ تومان برای ۱ میلیون توکن).
      - تغییر مدل مقالات به `deepseek-v4.1-flash` (۰.۱۵ واحد).
      - تغییر مدل بینایی مدارک و دانشنامه‌ها به `gemini-2.5-flash-lite` (۰.۱۰ واحد با پشتیبانی کامل از پردازش تصویر) در [`includes/DoctorVerificationService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/DoctorVerificationService.php).
    - **معماری آبشاری ۴ لایه برای بهره‌برداری از هوش مصنوعی کاملاً رایگان ($0 بدون نیاز به کارت بانکی یا شارژ):**
      - ۱. لایه نخست: مدل‌های بینایی کاملاً رایگان OpenRouter نظیر `qwen/qwen-2.5-vl-72b-instruct:free` (مدل بینایی علی‌بابا/کیوون مدنظر کاربر) و `google/gemini-2.0-flash-exp:free`.
      - ۲. لایه دوم: سهمیه روزانه ۱۵۰۰ درخواست کاملاً رایگان Google AI Studio با کلید `GEMINI_API_KEY`.
      - ۳. لایه سوم: پردازشگر ابری رایگان Groq Cloud با مدل بینایی `llama-3.2-11b-vision`.
      - ۴. لایه چهارم: گیت‌وی AvalAI در صورت شارژ حساب.
      - ۵. لایه پنجم (هسته پایدار): موتور آفلاین اعتبارسنجی نظام دامپزشکی ایران (IRVC Heuristics) با ضریب خطای صفر و بدون وابستگی به اینترنت یا هزینه خارجی.

---




## ۴. پروتکل ثبت تغییرات آینده (Maintenance Rule)
> **دستورالعمل برای هوش مصنوعی در ادامه کار:**  
> هر زمان که فایل جدیدی ایجاد یا فایلی ویرایش شد:
> ۱. بلافاصله تغییر انجام‌شده را با ذکر نام فایل و دلیل فنی، به انتهای بخش **۳. تاریخچه تغییرات اخیر (Change Log)** در همین فایل ([`PROJECT_CONTEXT.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_CONTEXT.md)) اضافه کن.  
> ۲. نیاز به نگهداری تاریخچه طولانی در پنجره پرامپت نیست؛ هر زمان کانتکس پر یا ریست شد، با استناد به این فایل می‌توان کار را بدون اتلاف وقت ادامه داد.

