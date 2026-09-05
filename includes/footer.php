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

    <!-- Mobile Bottom Navigation Bar (PWA UX) -->
    <nav class="mobile-bottom-nav">
        <a href="index.php" class="bottom-nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">home</span>
            <span>خانه</span>
        </a>
        <a href="shop.php" class="bottom-nav-link <?php echo ($current_page === 'shop.php' || $current_page === 'pharmacy.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">storefront</span>
            <span>فروشگاه</span>
        </a>
        <a href="cart.php" class="bottom-nav-link <?php echo ($current_page === 'cart.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">shopping_cart</span>
            <?php if (!empty($cart_count) && $cart_count > 0): ?>
                <span class="nav-cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
            <span>سبد خرید</span>
        </a>
        <a href="profile.php#pets" class="bottom-nav-link">
            <span class="material-symbols-outlined">pets</span>
            <span>پت پاسپورت</span>
        </a>
        <a href="profile.php" class="bottom-nav-link <?php echo ($current_page === 'profile.php' || $current_page === 'login.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">account_circle</span>
            <span>حساب من</span>
        </a>
    </nav>

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
