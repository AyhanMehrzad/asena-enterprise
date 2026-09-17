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

---


## ۴. پروتکل ثبت تغییرات آینده (Maintenance Rule)
> **دستورالعمل برای هوش مصنوعی در ادامه کار:**  
> هر زمان که فایل جدیدی ایجاد یا فایلی ویرایش شد:
> ۱. بلافاصله تغییر انجام‌شده را با ذکر نام فایل و دلیل فنی، به انتهای بخش **۳. تاریخچه تغییرات اخیر (Change Log)** در همین فایل ([`PROJECT_CONTEXT.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_CONTEXT.md)) اضافه کن.  
> ۲. نیاز به نگهداری تاریخچه طولانی در پنجره پرامپت نیست؛ هر زمان کانتکس پر یا ریست شد، با استناد به این فایل می‌توان کار را بدون اتلاف وقت ادامه داد.
