    <!-- Footer -->
    <footer class="bg-surface-container-low border border-outline-variant/30 rounded-[2rem] md:rounded-[3rem] mt-16 md:mt-24 w-[96%] max-w-[1600px] mx-auto overflow-hidden">
        <div class="flex flex-col lg:flex-row-reverse justify-between px-6 lg:px-10 py-10 lg:py-16 gap-10 lg:gap-16">
            <div class="flex flex-col gap-6 lg:w-1/3 text-center lg:text-right items-center lg:items-start">
                <a href="index.php" class="flex items-center gap-3 group" dir="ltr">
                    <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform duration-200">
                    <h3 class="text-3xl font-bold text-primary group-hover:text-secondary-container transition-colors">ASENA</h3>
                </a>
                <p class="text-sm text-on-surface-variant leading-relaxed">اولین اکوسیستم هوشمند مراقبت از حیوانات خانگی. تلفیقی از تخصص پزشکی، تکنولوژی روز و عشق به حیوانات.</p>
                <div class="flex gap-4">
                    <a class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-primary hover:bg-primary-container hover:text-white transition-colors" href="#">
                        <span class="material-symbols-outlined">share</span>
                    </a>
                    <a class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-primary hover:bg-primary-container hover:text-white transition-colors" href="#">
                        <span class="material-symbols-outlined">mail</span>
                    </a>
                    <a class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-primary hover:bg-primary-container hover:text-white transition-colors" href="#">
                        <span class="material-symbols-outlined">call</span>
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10 lg:gap-12 flex-1 text-center sm:text-right">
                <div class="flex flex-col gap-4 lg:gap-5">
                    <h4 class="font-bold text-lg text-primary">فروشگاه</h4>
                    <nav class="flex flex-col gap-3">
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="shop.php">غذای سگ و گربه</a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="shop.php">لوازم بهداشتی</a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="shop.php">اسباب‌بازی</a>
                    </nav>
                </div>
                <div class="flex flex-col gap-5">
                    <h4 class="font-bold text-lg text-primary">خدمات درمانی</h4>
                    <nav class="flex flex-col gap-3">
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="#">رزرو ویزیت</a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="#">مشاوره هوشمند</a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors" href="#">واکسیناسیون</a>
                    </nav>
                </div>
                <div class="flex flex-col gap-5">
                    <h4 class="font-bold text-lg text-primary">پایگاه دانش و راهنما</h4>
                    <nav class="flex flex-col gap-3">
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors flex items-center gap-1.5" href="knowledge_base.php">
                            <span class="material-symbols-outlined text-[16px] text-primary">auto_stories</span>
                            پایگاه دانش و مقالات تخصصی
                        </a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors flex items-center gap-1.5" href="knowledge_base.php?article=vaccination-schedule-dogs-cats">
                            <span class="material-symbols-outlined text-[16px] text-primary">vaccines</span>
                            جدول واکسیناسیون سگ و گربه
                        </a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors flex items-center gap-1.5" href="knowledge_base.php?article=how-autoship-works-guide">
                            <span class="material-symbols-outlined text-[16px] text-primary">autorenew</span>
                            راهنمای تحویل خودکار (Autoship)
                        </a>
                        <a class="text-sm text-on-surface-variant hover:text-secondary-container transition-colors flex items-center gap-1.5" href="knowledge_base.php?article=pet-poisoning-emergency-guide">
                            <span class="material-symbols-outlined text-[16px] text-primary">emergency</span>
                            راهنمای مسمومیت حیوانات
                        </a>
                    </nav>
                </div>
        </div>
    </footer>

    <!-- Copyright Under Everything -->
    <div class="w-full flex justify-center pb-8 pt-4 mt-4" dir="ltr">
        <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-sm text-on-surface-variant/80">
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[1.1rem]">copyright</span>
                <span><?php echo date('Y'); ?> ASENA. All rights reserved.</span>
            </div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-outline-variant/50"></div>
            <div class="flex items-center gap-2">
                <span>All copyrights belong to</span>
                <a href="https://ayhanmehrzad.pro/" target="_blank" class="group flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all duration-300 shadow-sm hover:shadow-md font-medium text-xs tracking-wide">
                    ayhanmehrzad.pro
                    <span class="material-symbols-outlined text-[14px] transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5">north_east</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Autoship Web Worker Trigger (Poor Man's Cron) -->
    <script>
        // Trigger the autoship worker asynchronously. 
        // It checks its own lock file so it only actually runs once a day.
        fetch('actions/autoship_worker.php', { method: 'POST' }).catch(() => {});
    </script>

    <!-- Digikala-Style Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav" id="mobileBottomNavBar" role="navigation" aria-label="ناوبری اصلی موبایل">
        <!-- 1. Home -->
        <a href="index.php" class="bottom-nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">home</span>
            <span>خانه</span>
        </a>

        <!-- 2. Categories (Triggers Bottom Sheet Drawer) -->
        <a href="javascript:void(0)" onclick="openMobileCategoriesSheet()" class="bottom-nav-link <?php echo in_array($current_page, ['shop.php', 'pharmacy.php', 'organizations.php']) ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">grid_view</span>
            <span>دسته‌بندی‌ها</span>
        </a>

        <!-- 3. Fast Booking (With Special Accent) -->
        <a href="booking.php" onclick="if(document.getElementById('timeReservationSection')){document.getElementById('timeReservationSection').scrollIntoView({behavior:'smooth', block:'start'});return false;}" class="bottom-nav-link <?php echo ($current_page === 'booking.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">calendar_month</span>
            <span>نوبت‌دهی</span>
        </a>

        <!-- 4. Cart with Dynamic Badge -->
        <a href="cart.php" class="bottom-nav-link <?php echo ($current_page === 'cart.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">shopping_cart</span>
            <?php if (!empty($cart_count) && $cart_count > 0): ?>
                <span class="nav-cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
            <span>سبد خرید</span>
        </a>
    </nav>

    <!-- Digikala-Style Mobile Categories Bottom Sheet & Backdrop -->
    <div id="mobileCategoriesBackdrop" class="mobile-sheet-backdrop" onclick="closeMobileCategoriesSheet()"></div>
    <div id="mobileCategoriesSheet" class="mobile-bottom-sheet" role="dialog" aria-modal="true" aria-labelledby="sheetTitle">
        <!-- Drag Handle -->
        <div class="sheet-drag-handle"></div>

        <!-- Sheet Header -->
        <div class="flex items-center justify-between px-6 py-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-2xl">category</span>
                <h3 id="sheetTitle" class="text-sm font-black text-slate-800">دسته‌بندی خدمات و محصولات آسنا</h3>
            </div>
            <button type="button" onclick="closeMobileCategoriesSheet()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Category Grid Container (Scrollable) -->
        <div class="p-5 overflow-y-auto custom-scrollbar flex-1 space-y-4 max-h-[65vh]">
            <div class="grid grid-cols-2 gap-3">
                <!-- 1. Pet Shop -->
                <a href="shop.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50/60 border border-amber-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">pets</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-amber-700 transition-colors">پت‌شاپ و تغذیه</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">غذای خشک، کنسرو، اسباب‌بازی و خاک</div>
                    </div>
                </a>

                <!-- 2. Pharmacy -->
                <a href="pharmacy.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">medication</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-blue-700 transition-colors">داروخانه دامپزشکی</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">واکسن، مکمل، ضد انگل و زنجیره سرد</div>
                    </div>
                </a>

                <!-- 3. Vet Appointments -->
                <a href="booking.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-50/60 border border-teal-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">calendar_month</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-teal-700 transition-colors">نوبت‌دهی پزشکان</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">جراحی، داخلی، سونوگرافی و ویزیت</div>
                    </div>
                </a>

                <!-- 4. Hospitals & 24/7 -->
                <a href="organizations.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-rose-50 to-red-50/60 border border-rose-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">local_hospital</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-rose-700 transition-colors">بیمارستان‌های ۲۴ ساعته</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">مراکز اورژانس شبانه‌روزی و ICU</div>
                    </div>
                </a>

                <!-- 5. Grooming & Spa -->
                <a href="booking.php?service=grooming" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-50/60 border border-pink-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">content_cut</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-pink-700 transition-colors">گرومینگ و آرایش پت</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">اصلاح مو، شستشو، اسپا و پدیکور</div>
                    </div>
                </a>

                <!-- 6. Autoship -->
                <a href="subscriptions.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-orange-50 to-amber-50/60 border border-orange-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">autorenew</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-orange-700 transition-colors">تحویل خودکار Autoship</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">ارسال ماهانه غذا با ۱۵٪ تخفیف دائمی</div>
                    </div>
                </a>

                <!-- 7. Knowledge Base -->
                <a href="knowledge_base.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-sky-50 to-blue-50/60 border border-sky-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">auto_stories</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-sky-700 transition-colors">دانشنامه و مقالات</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">جدول واکسن و راهنمای نگهداری پت</div>
                    </div>
                </a>

                <!-- 8. Pet Charity -->
                <a href="charity.php" onclick="closeMobileCategoriesSheet()" class="p-3.5 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50/60 border border-emerald-200/60 hover:shadow-md transition-all flex flex-col gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">volunteer_activism</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-emerald-700 transition-colors">خیریه و امداد حیوانات</div>
                        <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">پویش درمان حیوانات بی‌پناه</div>
                    </div>
                </a>
            </div>

            <!-- Fast View All Link -->
            <div class="pt-2">
                <a href="shop.php" onclick="closeMobileCategoriesSheet()" class="w-full bg-primary text-white py-3 px-4 rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-md hover:bg-primary-container transition-all">
                    <span>مشاهده کل کاتالوگ فروشگاه آسنا</span>
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Sheet Controllers -->
    <script>
    function openMobileCategoriesSheet() {
        const backdrop = document.getElementById('mobileCategoriesBackdrop');
        const sheet = document.getElementById('mobileCategoriesSheet');
        if (backdrop && sheet) {
            backdrop.classList.add('active');
            sheet.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileCategoriesSheet() {
        const backdrop = document.getElementById('mobileCategoriesBackdrop');
        const sheet = document.getElementById('mobileCategoriesSheet');
        if (backdrop && sheet) {
            backdrop.classList.remove('active');
            sheet.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    </script>

    <!-- Floating PWA Install Prompt Banner -->
    <div id="pwaInstallBanner" class="pwa-install-banner">
        <div class="pwa-logo-box flex items-center justify-center">
            <img src="assets/images/logo.png" alt="آسنا" class="w-7 h-7 object-contain">
        </div>
        <div class="flex-1 text-right">
            <div class="text-sm font-bold text-slate-800">نصب اپلیکیشن آسنا</div>
            <div class="text-xs text-slate-500">دسترسی سریع‌تر، آفلاین و یادآوری واکسن</div>
        </div>
        <button id="pwaInstallBtn" class="px-3.5 py-1.5 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-hover transition-colors shadow-sm">
            نصب
        </button>
        <button id="pwaDismissBtn" class="text-slate-400 hover:text-slate-600 p-1">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>

    <script>
    // PWA Install Prompt Logic
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const dismissed = localStorage.getItem('asena_pwa_dismissed');
        if (!dismissed) {
            setTimeout(() => {
                const banner = document.getElementById('pwaInstallBanner');
                if (banner) banner.classList.add('show');
            }, 3000);
        }
    });

    document.getElementById('pwaInstallBtn')?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            document.getElementById('pwaInstallBanner')?.classList.remove('show');
        }
    });

    document.getElementById('pwaDismissBtn')?.addEventListener('click', () => {
        document.getElementById('pwaInstallBanner')?.classList.remove('show');
        localStorage.setItem('asena_pwa_dismissed', '1');
    });
    </script>

    <?php require_once __DIR__ . '/cookie_consent.php'; ?>
</body>
</html>
