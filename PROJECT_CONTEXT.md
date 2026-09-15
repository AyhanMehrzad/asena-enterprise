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

### نسخه ۱.۰.۲ (سپتامبر ۲۰۲۶ - آخرین نسخه فعال)
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

---

## ۴. پروتکل ثبت تغییرات آینده (Maintenance Rule)
> **دستورالعمل برای هوش مصنوعی در ادامه کار:**  
> هر زمان که فایل جدیدی ایجاد یا فایلی ویرایش شد:
> ۱. بلافاصله تغییر انجام‌شده را با ذکر نام فایل و دلیل فنی، به انتهای بخش **۳. تاریخچه تغییرات اخیر (Change Log)** در همین فایل ([`PROJECT_CONTEXT.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_CONTEXT.md)) اضافه کن.  
> ۲. نیاز به نگهداری تاریخچه طولانی در پنجره پرامپت نیست؛ هر زمان کانتکس پر یا ریست شد، با استناد به این فایل می‌توان کار را بدون اتلاف وقت ادامه داد.
