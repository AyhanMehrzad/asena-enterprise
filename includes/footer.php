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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10 flex-1 text-center sm:text-right">
                <div class="flex flex-col gap-4">
                    <h4 class="font-bold text-base text-primary">فروشگاه و خدمات</h4>
                    <nav class="flex flex-col gap-2.5">
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="shop.php">غذای خشک و کنسرو</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="pharmacy.php">داروخانه و مکمل‌ها</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="booking.php">نوبت‌دهی کلینیک و گرومینگ</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="subscriptions.php">سفارش خودکار Autoship</a>
                    </nav>
                </div>
                <div class="flex flex-col gap-4">
                    <h4 class="font-bold text-base text-primary">قوانین و امور مشتریان</h4>
                    <nav class="flex flex-col gap-2.5">
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="about.php">درباره ما و مجوزها</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors font-bold text-primary" href="contact.php">تماس با ما و ثبت شکایات</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="terms.php">قوانین و شرایط خدمات (مهلت ۷ روزه)</a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors" href="privacy.php">سیاست حفظ حریم خصوصی</a>
                    </nav>
                </div>
                <div class="flex flex-col gap-4">
                    <h4 class="font-bold text-base text-primary">پایگاه دانش دامپزشکی</h4>
                    <nav class="flex flex-col gap-2.5">
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors flex items-center justify-center sm:justify-start gap-1" href="knowledge_base.php">
                            <span class="material-symbols-outlined text-[15px] text-primary">auto_stories</span>
                            مقالات و راهنمای سلامت
                        </a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors flex items-center justify-center sm:justify-start gap-1" href="knowledge_base.php?article=vaccination-schedule-dogs-cats">
                            <span class="material-symbols-outlined text-[15px] text-primary">vaccines</span>
                            جدول واکسیناسیون پت
                        </a>
                        <a class="text-xs text-on-surface-variant hover:text-secondary-container transition-colors flex items-center justify-center sm:justify-start gap-1" href="charity.php">
                            <span class="material-symbols-outlined text-[15px] text-emerald-600">volunteer_activism</span>
                            خیریه و درمان حیوانات
                        </a>
                    </nav>
                </div>

                <!-- Enamad & Trust Badges Column -->
                <div class="flex flex-col gap-3 items-center sm:items-start">
                    <h4 class="font-bold text-base text-primary">مجوزها و نماد اعتماد</h4>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        دارای نماد اعتماد الکترونیکی از وزارت صمت و پروتکل امن SSL
                    </p>
                    
                    <div class="flex items-center gap-3 pt-1">
                        <?php 
                        $enamadHtml = ($pdo instanceof PDO) ? get_setting($pdo, 'enamad_html_code', '') : '';
                        if (!empty($enamadHtml)): 
                            echo $enamadHtml;
                        else:
                        ?>
                        <!-- Official Enamad Badge Slot -->
                        <a href="https://enamad.ir" target="_blank" rel="noopener noreferrer" class="w-20 h-24 p-2 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col items-center justify-between text-center group hover:border-primary transition-all cursor-pointer" title="نماد اعتماد الکترونیکی مرکز توسعه تجارت الکترونیکی">
                            <div class="w-10 h-10 flex items-center justify-center mt-1">
                                <svg class="w-8 h-8 text-primary group-hover:scale-105 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                            <span class="text-[9px] font-black text-slate-700 group-hover:text-primary transition-colors leading-tight">نماد اعتماد الکترونیکی</span>
                            <span class="text-[8px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded-full">اینماد تاییدشده</span>
                        </a>

                        <!-- Samandehi Badge Slot -->
                        <a href="https://samandehi.ir" target="_blank" rel="noopener noreferrer" class="w-20 h-24 p-2 bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col items-center justify-between text-center group hover:border-[#fd8100] transition-all cursor-pointer" title="نشان ملی ثبت رسانه‌های دیجیتال (ساماندهی)">
                            <div class="w-10 h-10 flex items-center justify-center mt-1">
                                <svg class="w-8 h-8 text-[#fd8100] group-hover:scale-105 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="m4.93 4.93 4.24 4.24"/>
                                    <path d="m14.83 9.17 4.24-4.24"/>
                                    <path d="m14.83 14.83 4.24 4.24"/>
                                    <path d="m9.17 14.83-4.24 4.24"/>
                                    <circle cx="12" cy="12" r="4"/>
                                </svg>
                            </div>
                            <span class="text-[9px] font-black text-slate-700 group-hover:text-[#fd8100] transition-colors leading-tight">نشان ساماندهی</span>
                            <span class="text-[8px] text-blue-600 font-bold bg-blue-50 px-1.5 py-0.5 rounded-full">رسانه دیجیتال</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
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

    <!-- Digikala-Style 5-Tab Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav" id="mobileBottomNavBar" role="navigation" aria-label="ناوبری اصلی موبایل">
        <!-- 1. خانه (Home) -->
        <a href="index.php" class="bottom-nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">home</span>
            <span>خانه</span>
        </a>

        <!-- 2. دسته‌بندی‌ها (Categories Sheet) -->
        <a href="javascript:void(0)" onclick="openMobileCategoriesSheet()" class="bottom-nav-link <?php echo in_array($current_page, ['shop.php', 'pharmacy.php']) ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">grid_view</span>
            <span>دسته‌بندی‌ها</span>
        </a>

        <!-- 3. سبد خرید (Cart with Live Counter Badge) -->
        <a href="cart.php" class="bottom-nav-link <?php echo ($current_page === 'cart.php') ? 'active' : ''; ?>">
            <div class="relative flex items-center justify-center">
                <span class="material-symbols-outlined">shopping_cart</span>
                <?php if (!empty($cart_count) && $cart_count > 0): ?>
                    <span class="nav-cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </div>
            <span>سبد خرید</span>
        </a>

        <!-- 4. خدمات و پزشکان (Services / Booking) -->
        <a href="booking.php" class="bottom-nav-link <?php echo in_array($current_page, ['booking.php', 'organizations.php']) ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">medical_services</span>
            <span>خدمات پزشکان</span>
        </a>

        <!-- 5. آسنای من (My Asena / Profile) -->
        <a href="<?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'; ?>" class="bottom-nav-link <?php echo in_array($current_page, ['profile.php', 'profile_settings.php', 'login.php', 'rewards.php', 'wishlist.php']) ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">person</span>
            <span>آسنای من</span>
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
    <div id="pwaInstallBanner" class="pwa-install-banner" role="dialog" aria-label="پیشنهاد نصب اپلیکیشن آسنا">
        <div class="pwa-logo-box flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-2xl">pets</span>
        </div>
        <div class="flex-1 text-right min-w-0">
            <div class="text-sm font-black text-slate-800 leading-tight truncate">نصب اپلیکیشن آسنا</div>
            <div class="text-[11px] text-slate-500 mt-0.5 truncate">دسترسی سریع‌تر، آفلاین و یادآوری واکسن</div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <button id="pwaInstallBtn" type="button" onclick="handlePwaInstallAction(event)" class="px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-white text-xs font-black transition-all shadow-sm active:scale-95 flex items-center gap-1 cursor-pointer">
                <span>نصب</span>
            </button>
            <button id="pwaDismissBtn" type="button" onclick="dismissPwaBanner(event)" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 active:scale-90 transition-all cursor-pointer" title="بستن" aria-label="بستن">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    </div>

    <!-- PWA Install Guide Modal (For iOS / Desktop / Manual Install) -->
    <div id="pwaInstallGuideModal" class="fixed inset-0 z-[10050] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm rtl text-right" onclick="if(event.target === this) closePwaInstallGuide();">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 transform transition-all animate-fade-in flex flex-col">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-teal-50/50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-primary shadow-sm">
                        <span class="material-symbols-outlined text-2xl">pets</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 text-sm">راهنمای نصب اپلیکیشن آسنا</h3>
                        <p class="text-[11px] text-slate-500">نصب نسخه وب پیشرو (PWA) بدون نیاز به دانلود از استور</p>
                    </div>
                </div>
                <button type="button" onclick="closePwaInstallGuide()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Platform Tabs -->
            <div class="px-5 pt-3 pb-1 bg-slate-50/60 border-b border-slate-100 flex gap-2">
                <button type="button" id="pwaTabBtnIos" onclick="switchPwaTab('ios')" class="flex-1 py-2 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs border border-slate-200">
                    <span class="material-symbols-outlined text-base">phone_iphone</span>
                    <span>آیفون (iOS)</span>
                </button>
                <button type="button" id="pwaTabBtnAndroid" onclick="switchPwaTab('android')" class="flex-1 py-2 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:bg-white/60">
                    <span class="material-symbols-outlined text-base">phone_android</span>
                    <span>اندروید</span>
                </button>
                <button type="button" id="pwaTabBtnDesktop" onclick="switchPwaTab('desktop')" class="flex-1 py-2 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:bg-white/60">
                    <span class="material-symbols-outlined text-base">laptop_mac</span>
                    <span>کامپیوتر</span>
                </button>
            </div>

            <!-- Tab Content: iOS Safari -->
            <div id="pwaTabContentIos" class="p-5 space-y-3.5">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۱</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        در نوار پایین مرورگر <strong>سافاری (Safari)</strong>، دکمه اشتراک‌گذاری 
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-white border border-slate-200 text-primary font-bold mx-1">
                            <span class="material-symbols-outlined text-sm align-middle">ios_share</span> Share
                        </span>
                        را لمس نمایید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۲</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        منوی باز شده را به پایین اسکرول کرده و گزینه 
                        <strong class="text-primary font-bold">«Add to Home Screen»</strong> 
                        (افزودن به صفحه اصلی) را انتخاب کنید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۳</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        در بالای صفحه، دکمه <strong>«Add»</strong> یا <strong>«افزودن»</strong> را لمس کنید تا آیکون اپلیکیشن به صفحه اصلی گوشی شما اضافه شود.
                    </div>
                </div>
            </div>

            <!-- Tab Content: Android -->
            <div id="pwaTabContentAndroid" class="p-5 space-y-3.5 hidden">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۱</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        روی منوی <strong>سه نقطه (⋮)</strong> در بالای صفحه مرورگر کروم یا سامسونگ اینترنت ضربه بزنید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۲</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        گزینه <strong>«نصب برنامه» (Install app)</strong> یا <strong>«افزودن به صفحه اصلی»</strong> را لمس نمایید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۳</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        در پنجره تأیید ظاهر شده، دکمه <strong>«نصب» (Install)</strong> را انتخاب کنید.
                    </div>
                </div>
            </div>

            <!-- Tab Content: Desktop -->
            <div id="pwaTabContentDesktop" class="p-5 space-y-3.5 hidden">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۱</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        در نوار آدرس مرورگر (سمت راست آدرس سایت)، روی آیکون <strong>نصب برنامه (Install <span class="material-symbols-outlined text-sm align-middle">install_desktop</span>)</strong> کلیک فرمایید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۲</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        همچنین می‌توانید از منوی سه نقطه (⋮) مرورگر، گزینه <strong>«Install ASENA...»</strong> را انتخاب نمایید.
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-teal-50/60 border border-teal-100/80">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">۳</span>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        با تأیید نصب، اپلیکیشن آسنا به شکل نرم‌افزار مستقل، فوق‌سریع و بدون نوار آدرس اجرا خواهد شد.
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                <div class="text-[11px] text-slate-500 flex items-center gap-1">
                    <span class="material-symbols-outlined text-primary text-base">verified</span>
                    <span>بدون اشغال حجم و به‌روزرسانی آنی</span>
                </div>
                <button type="button" onclick="closePwaInstallGuide()" class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-container transition-all active:scale-95 shadow-sm cursor-pointer">
                    متوجه شدم و بستن
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification for PWA Events -->
    <div id="pwaToast" class="fixed top-6 left-1/2 -translate-x-1/2 z-[10060] hidden max-w-sm px-4 py-2.5 rounded-2xl bg-slate-900/95 text-white text-xs font-bold shadow-2xl backdrop-blur-md flex items-center gap-2.5 transition-all duration-300 pointer-events-none">
        <span id="pwaToastIcon" class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span>
        <span id="pwaToastMsg">پیام سیستم</span>
    </div>

    <script>
    // PWA Install Prompt & Dismiss Logic
    let deferredPwaPrompt = null;

    function isPwaStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || 
               window.navigator.standalone === true || 
               document.referrer.includes('android-app://');
    }

    function isPwaDismissed() {
        try {
            return localStorage.getItem('asena_pwa_dismissed') === '1' || 
                   sessionStorage.getItem('asena_pwa_dismissed') === '1';
        } catch (e) {
            return false;
        }
    }

    function showPwaToast(message, type = 'success') {
        const toast = document.getElementById('pwaToast');
        const msg = document.getElementById('pwaToastMsg');
        const icon = document.getElementById('pwaToastIcon');
        if (!toast || !msg) return;

        msg.textContent = message;
        if (type === 'success') {
            icon.textContent = 'check_circle';
            icon.className = 'material-symbols-outlined text-emerald-400 text-lg';
        } else if (type === 'info') {
            icon.textContent = 'info';
            icon.className = 'material-symbols-outlined text-sky-400 text-lg';
        } else {
            icon.textContent = 'warning';
            icon.className = 'material-symbols-outlined text-amber-400 text-lg';
        }

        toast.classList.remove('hidden');
        toast.style.opacity = '1';
        toast.style.transform = 'translate(-50%, 0)';

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, -10px)';
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3500);
    }

    function displayPwaBanner() {
        if (isPwaStandalone() || isPwaDismissed()) return;
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) {
            banner.classList.remove('hidden', 'closing');
            banner.style.display = 'flex';
            // Force browser reflow to ensure smooth transition
            void banner.offsetWidth;
            banner.classList.add('show');
        }
    }

    function dismissPwaBanner(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) {
            banner.classList.remove('show');
            banner.classList.add('closing');
            setTimeout(() => {
                banner.classList.add('hidden');
                banner.style.setProperty('display', 'none', 'important');
            }, 350);
        }
        try {
            localStorage.setItem('asena_pwa_dismissed', '1');
            sessionStorage.setItem('asena_pwa_dismissed', '1');
        } catch (err) {}
    }

    async function handlePwaInstallAction(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (isPwaStandalone()) {
            showPwaToast('اپلیکیشن آسنا هم‌اکنون روی دستگاه شما نصب و در حال اجرا است.', 'info');
            dismissPwaBanner();
            return;
        }

        if (deferredPwaPrompt) {
            try {
                deferredPwaPrompt.prompt();
                const choice = await deferredPwaPrompt.userChoice;
                if (choice && choice.outcome === 'accepted') {
                    showPwaToast('در حال نصب اپلیکیشن آسنا...', 'success');
                    dismissPwaBanner();
                }
                deferredPwaPrompt = null;
                return;
            } catch (err) {
                console.warn('[PWA] Prompt error:', err);
            }
        }

        // Native prompt unavailable -> open guide modal!
        openPwaInstallGuide();
    }

    function openPwaInstallGuide() {
        const modal = document.getElementById('pwaInstallGuideModal');
        if (!modal) return;

        // Auto-detect OS / browser
        const ua = navigator.userAgent || '';
        const isIos = /iPhone|iPad|iPod/i.test(ua);
        const isAndroid = /Android/i.test(ua);

        if (isIos) {
            switchPwaTab('ios');
        } else if (isAndroid) {
            switchPwaTab('android');
        } else {
            switchPwaTab('desktop');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closePwaInstallGuide() {
        const modal = document.getElementById('pwaInstallGuideModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        dismissPwaBanner();
    }

    function switchPwaTab(platform) {
        const tabs = ['ios', 'android', 'desktop'];
        tabs.forEach(t => {
            const btn = document.getElementById('pwaTabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            const content = document.getElementById('pwaTabContent' + t.charAt(0).toUpperCase() + t.slice(1));
            if (t === platform) {
                if (btn) {
                    btn.className = 'flex-1 py-2 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs border border-slate-200';
                }
                if (content) content.classList.remove('hidden');
            } else {
                if (btn) {
                    btn.className = 'flex-1 py-2 px-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:bg-white/60';
                }
                if (content) content.classList.add('hidden');
            }
        });
    }

    // Capture browser install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPwaPrompt = e;
        setTimeout(displayPwaBanner, 2000);
    });

    // Installed listener
    window.addEventListener('appinstalled', () => {
        deferredPwaPrompt = null;
        showPwaToast('اپلیکیشن آسنا با موفقیت نصب شد!', 'success');
        dismissPwaBanner();
    });

    // Initial page load check
    document.addEventListener('DOMContentLoaded', () => {
        if (!isPwaStandalone() && !isPwaDismissed()) {
            setTimeout(displayPwaBanner, 3000);
        }
    });

    // Close guide on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('pwaInstallGuideModal');
            if (modal && !modal.classList.contains('hidden')) {
                closePwaInstallGuide();
            }
        }
    });
    </script>
    
    <!-- Universal Wishlist Manager (Instant Optimistic UI + Micro-Animations + Toast Alerts) -->
    <script src="assets/js/wishlist-manager.js?v=<?php echo time(); ?>"></script>

    <?php require_once __DIR__ . '/cookie_consent.php'; ?>
</body>
</html>
