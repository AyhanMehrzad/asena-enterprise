<?php
/**
 * ASENA Enterprise - Visual Site Builder Studio Component
 * Split-screen interactive live customizer for Doctors, Organizations, Pharmacists, and Sellers.
 * Features:
 * - 5 ASENA Capability Tiers Selection (Basic, Standard, Premium, Pharmacy, Enterprise)
 * - Mobile-first responsive studio with bottom mode toggle
 * - Click-to-edit with bidirectional postMessage event bus
 * - Live Iframe Preview with multi-viewport switcher
 * Version: 2.0.0
 */

$tenantService = App::tenantSite();
$site = $tenantService->getOrCreateDefault($builderTenantType, $builderTenantId, $builderTenantInfo ?? []);
$layout = $site['layout']['blocks'] ?? [];
$slug = htmlspecialchars($site['slug']);
$siteTier = $site['site_tier'] ?? 'enterprise';
$previewUrl = "../site.php?slug=" . urlencode($site['slug']) . "&preview=1";
$publicUrl = "../site.php?slug=" . urlencode($site['slug']);
?>

<style>
    /* Collapse and hide dark navy portal sidebars in Studio Mode across all roles */
    #doctor-sidebar,
    #org-sidebar,
    #pharmacist-sidebar,
    #seller-sidebar {
        transform: translateX(100%) !important;
        z-index: 70 !important;
        box-shadow: -4px 0 25px rgba(0, 0, 0, 0.25) !important;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #doctor-sidebar.portal-sidebar-open,
    #org-sidebar.portal-sidebar-open,
    #pharmacist-sidebar.portal-sidebar-open,
    #seller-sidebar.portal-sidebar-open {
        transform: translateX(0) !important;
    }

    /* Zero out right margin on main wrappers across portals */
    main.lg\:mr-64,
    main {
        margin-right: 0 !important;
        padding: 0 !important;
    }

    /* Hide duplicate top app bar from portal layout */
    body > main > header.sticky.top-0,
    body > main > header.flex.justify-between {
        display: none !important;
    }

    /* Studio Responsive Collapsible Sidebar */
    #studio-sidebar {
        transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                    min-width 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                    max-width 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                    opacity 0.25s ease, 
                    transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: width, opacity;
    }

    #studio-sidebar.sidebar-closed {
        width: 0 !important;
        min-width: 0 !important;
        max-width: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
        border-left-width: 0 !important;
        border-right-width: 0 !important;
        overflow: hidden !important;
        visibility: hidden !important;
    }

    #studio-sidebar.sidebar-open {
        width: 100% !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        visibility: visible !important;
    }

    @media (min-width: 1024px) {
        #studio-sidebar.sidebar-open {
            width: 420px !important;
            min-width: 420px !important;
            max-width: 420px !important;
        }
    }

    @media (max-width: 1023px) {
        #studio-sidebar.sidebar-open {
            position: absolute;
            inset: 0;
            z-index: 40;
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="h-[calc(100vh-80px)] flex flex-col bg-slate-100 overflow-hidden relative" id="site-builder-app">
    
    <!-- Studio Top Action Bar -->
    <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between z-30 shrink-0 shadow-sm">
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Portal Navigation Drawer Toggle -->
            <button type="button" onclick="togglePortalSidebar()" class="p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs group" title="مشاهده منوی اصلی پنل کاربری">
                <span class="material-symbols-outlined text-lg text-slate-600 group-hover:text-slate-900">menu</span>
                <span class="hidden md:inline">منوی پنل</span>
            </button>
            <a href="index.php" class="p-2 sm:px-3 sm:py-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 text-xs font-bold transition-all flex items-center gap-1" title="بازگشت به پیشخوان پنل">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                <span class="hidden md:inline">پیشخوان</span>
            </a>

            <div class="h-6 w-[1px] bg-slate-200 hidden sm:block"></div>

            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#001a48] to-[#0a3580] flex items-center justify-center text-white shadow-md shrink-0">
                <span class="material-symbols-outlined text-xl">web</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xs sm:text-sm font-black text-slate-900">استودیوی وب‌سایت‌ساز ابری</h2>
                    <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-bold <?= $site['is_published'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>" id="publish-status-badge">
                        <?= $site['is_published'] ? 'منتشر شده' : 'پیش‌نویس' ?>
                    </span>
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-500 flex items-center gap-1">
                    <span class="hidden sm:inline">آدرس ساب‌دامین:</span>
                    <a href="<?= $publicUrl ?>" target="_blank" class="font-mono text-blue-600 hover:underline flex items-center gap-0.5 truncate max-w-[150px] sm:max-w-xs" id="header-site-url">
                        <span><?= $slug ?>.asena.company</span>
                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Viewport Switcher Controls (Desktop Only) -->
        <div class="hidden lg:flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200/80 text-slate-600">
            <button type="button" onclick="setViewport('desktop')" id="btn-vp-desktop" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-sm flex items-center gap-1 transition-all">
                <span class="material-symbols-outlined text-base">desktop_windows</span>
                <span>دسکتاپ</span>
            </button>
            <button type="button" onclick="setViewport('tablet')" id="btn-vp-tablet" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all">
                <span class="material-symbols-outlined text-base">tablet_mac</span>
                <span>تبلت</span>
            </button>
            <button type="button" onclick="setViewport('mobile')" id="btn-vp-mobile" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all">
                <span class="material-symbols-outlined text-base">smartphone</span>
                <span>موبایل</span>
            </button>
        </div>

        <!-- Action Buttons & Studio Sidebar Toggle -->
        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" onclick="toggleSidebar()" id="btn-toggle-sidebar" class="px-3 sm:px-3.5 py-2 rounded-xl border border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 sm:gap-2 shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base text-emerald-600 transition-transform duration-300" id="sidebar-toggle-icon">tune</span>
                <span id="sidebar-toggle-text">پنل ویرایش محتوا</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold transition-colors" id="sidebar-status-badge">باز</span>
            </button>

            <a href="<?= $publicUrl ?>" target="_blank" class="hidden sm:flex px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition-colors items-center gap-1.5">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>مشاهده سایت</span>
            </a>
            <button type="button" onclick="saveSiteConfig()" id="btn-save-site" class="px-4 sm:px-5 py-2.5 rounded-xl bg-[#fd8100] hover:bg-[#ea580c] text-white text-xs font-black shadow-md shadow-orange-900/20 active:scale-95 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base" id="save-icon">cloud_upload</span>
                <span id="save-text">ذخیره و انتشار</span>
            </button>
        </div>
    </header>

    <!-- Mobile Screen View Switcher (Controls vs Live Preview) -->
    <div class="lg:hidden flex bg-white border-b border-slate-200 text-xs font-bold p-1">
        <button type="button" onclick="closeSidebar()" id="m-btn-preview" class="flex-1 py-2 rounded-xl bg-slate-900 text-white flex items-center justify-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">preview</span>
            <span>پیش‌نمایش زنده</span>
        </button>
        <button type="button" onclick="openSidebar()" id="m-btn-controls" class="flex-1 py-2 rounded-xl text-slate-600 hover:bg-slate-100 flex items-center justify-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">tune</span>
            <span>تنظیمات و محتوا</span>
        </button>
    </div>

    <!-- Main Workspace (Sidebar + Live Preview) -->
    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- Portal Sidebar Backdrop -->
        <div id="portal-backdrop" onclick="togglePortalSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-[65] hidden transition-opacity"></div>

        <!-- Mobile Backdrop for Studio Controls -->
        <div id="sidebar-backdrop" onclick="closeSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-30 lg:hidden hidden transition-opacity"></div>

        <!-- Controls Sidebar (Open on desktop by default with full room) -->
        <aside id="studio-sidebar" class="sidebar-open bg-white border-l border-slate-200 flex flex-col shrink-0 z-30 shadow-2xl overflow-hidden transition-all">
            
            <!-- Sidebar Header with Close Button -->
            <div class="h-14 px-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-base">tune</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-800">پنل تنظیمات و ویرایش</div>
                        <div class="text-[10px] text-slate-400">بلوک‌ها، رنگ و ویژگی‌های اختصاصی</div>
                    </div>
                </div>
                <button type="button" onclick="closeSidebar()" class="px-2.5 py-1.5 rounded-xl bg-slate-200/80 hover:bg-slate-300 text-slate-700 hover:text-slate-900 transition-colors flex items-center gap-1 text-[11px] font-bold" title="بستن پنل و پیش‌نمایش تمام‌صفحه (Esc)">
                    <span>بستن پنل</span>
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>

            <!-- Sidebar Tabs -->
            <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-bold shrink-0">
                <button type="button" onclick="switchSidebarTab('blocks')" id="tab-btn-blocks" class="flex-1 py-3 text-center border-b-2 border-emerald-600 text-emerald-800 bg-white transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-base">widgets</span>
                    <span>بلوک‌ها</span>
                </button>
                <button type="button" onclick="switchSidebarTab('design')" id="tab-btn-design" class="flex-1 py-3 text-center border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-base">palette</span>
                    <span>طراحی و تم</span>
                </button>
                <button type="button" onclick="switchSidebarTab('settings')" id="tab-btn-settings" class="flex-1 py-3 text-center border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-base">layers</span>
                    <span>نسخه و سئو</span>
                </button>
            </div>

            <!-- Tab Content Scrollable Container -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="sidebar-tab-content">
                
                <!-- TAB 1: BLOCKS ACCORDION -->
                <div id="tab-panel-blocks" class="space-y-3">
                    
                    <!-- 1. Hero Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-hero">
                        <button type="button" onclick="toggleAccordion('hero')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">view_carousel</span>
                                <span class="text-xs font-bold text-slate-800">بنر قهرمان اصلی (Hero)</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-hero">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-hero">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">نشان اعتبار یا برچسب بالینی</label>
                                <input type="text" id="input-hero-badge" value="<?= htmlspecialchars($layout['hero']['badge'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تیتر اصلی وب‌سایت</label>
                                <input type="text" id="input-hero-title" value="<?= htmlspecialchars($layout['hero']['title'] ?? $site['site_title']) ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیح کوتاه و ارزش پیشنهادی</label>
                                <textarea id="input-hero-subtitle" rows="3" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none"><?= htmlspecialchars($layout['hero']['subtitle'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">متن دکمه اصلی (CTA)</label>
                                <input type="text" id="input-hero-cta" value="<?= htmlspecialchars($layout['hero']['cta_primary_text'] ?? 'رزرو آنلاین نوبت') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-[11px] font-bold text-slate-700">تصویر شاخص بنر قهرمان (Hero)</label>
                                    <div class="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-xs w-48">
                                        <button type="button" id="hero-img-btn-mode-upload" onclick="switchImageInputMode('hero-img', 'upload')" class="flex-1 py-1 text-[11px] font-bold rounded-lg bg-white text-slate-800 shadow-xs border border-slate-200 transition-all">
                                            <span>📤 آپلود فایل</span>
                                        </button>
                                        <button type="button" id="hero-img-btn-mode-link" onclick="switchImageInputMode('hero-img', 'link')" class="flex-1 py-1 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-800 transition-all">
                                            <span>🔗 درج لینک</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Live Thumbnail Preview -->
                                <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80 mb-2">
                                    <div class="w-16 h-12 rounded-xl bg-slate-200 overflow-hidden border border-slate-300 shrink-0 flex items-center justify-center">
                                        <img id="preview-thumb-hero" src="<?= !empty($layout['hero']['image']) ? (str_starts_with($layout['hero']['image'], 'http') ? htmlspecialchars($layout['hero']['image']) : '../' . ltrim(htmlspecialchars($layout['hero']['image']), '/')) : '../' . ltrim(htmlspecialchars($site['banner_url']), '/') ?>" class="w-full h-full object-cover" onerror="this.src='../assets/images/clinic-banner.jpg'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[11px] font-bold text-slate-700 truncate">تصویر فعال بنر اصلی</div>
                                        <div id="input-hero-image-status" class="flex items-center gap-1 mt-0.5 text-[10px] text-slate-400">امکان آپلود مستقیم یا درج آدرس اینترنتی</div>
                                    </div>
                                </div>

                                <!-- Upload Box -->
                                <div id="hero-img-upload-box" class="space-y-1">
                                    <label class="flex flex-col items-center justify-center p-3.5 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl cursor-pointer bg-white hover:bg-emerald-50/20 transition-all text-center group">
                                        <span class="material-symbols-outlined text-2xl text-slate-400 group-hover:text-emerald-600 mb-1">cloud_upload</span>
                                        <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">انتخاب تصویر از دستگاه یا کشیدن فایل به اینجا</span>
                                        <span class="text-[10px] text-slate-400 mt-0.5">فرمت‌های JPG، PNG، WebP یا SVG (حداکثر ۱۰ مگابایت)</span>
                                        <input type="file" accept="image/*" class="hidden" onchange="handleImageUpload(this, 'input-hero-image', 'preview-thumb-hero')">
                                    </label>
                                </div>

                                <!-- Link Box -->
                                <div id="hero-img-link-box" class="hidden">
                                    <input type="text" id="input-hero-image" value="<?= htmlspecialchars($layout['hero']['image'] ?? $site['banner_url']) ?>" oninput="updateThumbSrc('preview-thumb-hero', this.value)" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr" placeholder="https://example.com/banner.jpg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Bento Facilities Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-bento_facilities">
                        <button type="button" onclick="toggleAccordion('bento_facilities')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">grid_view</span>
                                <span class="text-xs font-bold text-slate-800">بنتو گرید تجهیزات و بخش‌ها</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-bento_facilities">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-bento_facilities">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-bento-enabled" <?= !empty($layout['bento_facilities']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>فعال‌سازی نمایش بنتو گرید تجهیزات</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش تجهیزات</label>
                                <input type="text" id="input-bento-heading" value="<?= htmlspecialchars($layout['bento_facilities']['heading'] ?? 'تجهیزات مدرن و ظرفیت‌های بالینی مرکز') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Telehealth & Consultation Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-telehealth">
                        <button type="button" onclick="toggleAccordion('telehealth')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">videocam</span>
                                <span class="text-xs font-bold text-slate-800">مشاوره آنلاین و تله‌هلث</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-telehealth">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-telehealth">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-telehealth-enabled" <?= !empty($layout['telehealth_launcher']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش بنر مشاوره ویدیویی/صوتی آنلاین</span>
                            </label>
                        </div>
                    </div>

                    <!-- 4. Autoship Recurring Subscription -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-autoship">
                        <button type="button" onclick="toggleAccordion('autoship')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">autorenew</span>
                                <span class="text-xs font-bold text-slate-800">اشتراک دوره‌ای اتوشیپ (Autoship)</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-autoship">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-autoship">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-autoship-enabled" <?= !empty($layout['autoship_showcase']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش بخش سفارش خودکار ادواری (۱۰٪ تخفیف)</span>
                            </label>
                        </div>
                    </div>

                    <!-- 5. About Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-about">
                        <button type="button" onclick="toggleAccordion('about')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">badge</span>
                                <span class="text-xs font-bold text-slate-800">درباره ما و سوابق بالینی</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-about">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-about">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش</label>
                                <input type="text" id="input-about-heading" value="<?= htmlspecialchars($layout['about']['heading'] ?? 'درباره ما') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">متن معرفی کامل</label>
                                <textarea id="input-about-text" rows="5" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none"><?= htmlspecialchars($layout['about']['text'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">شماره نظام دامپزشکی یا پروانه تاسیس</label>
                                <input type="text" id="input-about-vet-council" value="<?= htmlspecialchars($layout['about']['vet_council'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr">
                            </div>
                        </div>
                    </div>

                    <!-- 6. Booking Widget Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-booking">
                        <button type="button" onclick="toggleAccordion('booking')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">calendar_month</span>
                                <span class="text-xs font-bold text-slate-800">ویجت رزرو آنلاین نوبت</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-booking">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-booking">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-booking-enabled" <?= !empty($layout['booking']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>فعال‌سازی بخش نوبت‌دهی آنلاین در صفحه</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش نوبت‌دهی</label>
                                <input type="text" id="input-booking-heading" value="<?= htmlspecialchars($layout['booking']['heading'] ?? 'نوبت‌دهی آنلاین ۲۴ ساعته') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 7. Storefront & Inventory Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-storefront">
                        <button type="button" onclick="toggleAccordion('storefront')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">storefront</span>
                                <span class="text-xs font-bold text-slate-800">ویترین کالاها و داروهای انبار</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-storefront">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-storefront">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-storefront-enabled" <?= !empty($layout['storefront']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش کالاهای موجود در انبار آسنا</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تعداد اقلام نمایشی</label>
                                <select id="input-storefront-limit" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                                    <option value="4" <?= ($layout['storefront']['item_limit'] ?? 6) == 4 ? 'selected' : '' ?>>۴ محصول</option>
                                    <option value="6" <?= ($layout['storefront']['item_limit'] ?? 6) == 6 ? 'selected' : '' ?>>۶ محصول (پیش‌فرض)</option>
                                    <option value="8" <?= ($layout['storefront']['item_limit'] ?? 6) == 8 ? 'selected' : '' ?>>۸ محصول</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Doctors & Specialists Roster Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-doctors_roster">
                        <button type="button" onclick="toggleAccordion('doctors_roster')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">medical_information</span>
                                <span class="text-xs font-bold text-slate-800">کادر پزشکان و متخصصان مقیم</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-doctors_roster">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-doctors_roster">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-doctors-enabled" <?= !empty($layout['doctors_roster']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش کادر پزشکان همکار در وب‌سایت</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش کادر درمان</label>
                                <input type="text" id="input-doctors-heading" value="<?= htmlspecialchars($layout['doctors_roster']['heading'] ?? 'کادر پزشکان و متخصصان مرکز') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیحات کوتاه</label>
                                <input type="text" id="input-doctors-subtitle" value="<?= htmlspecialchars($layout['doctors_roster']['subtitle'] ?? 'دامپزشکان مجرب با پرونده سلامت ابری و امکان نوبت‌دهی آنلاین') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Scientific Articles & Knowledge Base -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-articles">
                        <button type="button" onclick="toggleAccordion('articles')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">menu_book</span>
                                <span class="text-xs font-bold text-slate-800">دانشنامه سلامت و مقالات علمی</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-articles">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-articles">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-articles-enabled" <?= !empty($layout['articles']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش مقالات علمی جهت ارتقای سئو و آموزش مراجعین</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش دانشنامه</label>
                                <input type="text" id="input-articles-heading" value="<?= htmlspecialchars($layout['articles']['heading'] ?? 'دانشنامه سلامت و مقالات علمی دامپزشکی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Loyalty Club Banner -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-loyalty_club">
                        <button type="button" onclick="toggleAccordion('loyalty_club')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">loyalty</span>
                                <span class="text-xs font-bold text-slate-800">باشگاه مشتریان و پاداش وفاداری</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-loyalty_club">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-loyalty_club">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-loyalty-enabled" <?= !empty($layout['loyalty_club']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش بنر اعطای ۵۰ امتیاز پاداش آسنا کلاب</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بنر باشگاه مشتریان</label>
                                <input type="text" id="input-loyalty-heading" value="<?= htmlspecialchars($layout['loyalty_club']['heading'] ?? '۵۰ امتیاز پاداش با هر ثبت نوبت یا خرید آنلاین') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Reviews & Social Proof -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-reviews">
                        <button type="button" onclick="toggleAccordion('reviews')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">rate_review</span>
                                <span class="text-xs font-bold text-slate-800">نظرات و رضایت‌سنجی مراجعین</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-reviews">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-reviews">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-reviews-enabled" <?= !empty($layout['reviews']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش نظرات تأییدشده با نژاد پت</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش نظرات</label>
                                <input type="text" id="input-reviews-heading" value="<?= htmlspecialchars($layout['reviews']['heading'] ?? 'نظرات و بازخورد مراجعین تاییدشده') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 8. Contact & Hours Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-contact">
                        <button type="button" onclick="toggleAccordion('contact')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">pin_drop</span>
                                <span class="text-xs font-bold text-slate-800">تماس، ساعات کاری و آدرس</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-contact">expand_more</span>
                        </button>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-contact">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">نشانی دقیق مراجعه حضوری</label>
                                <textarea id="input-contact-address" rows="2" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none"><?= htmlspecialchars($layout['contact']['address'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">ساعات کاری و پذیرش</label>
                                <input type="text" id="input-contact-hours" value="<?= htmlspecialchars($layout['contact']['hours'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تلفن پذیرش</label>
                                <input type="text" id="input-contact-phone" value="<?= htmlspecialchars($layout['contact']['phone'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تلفن اورژانس ۲۴ ساعته</label>
                                <input type="text" id="input-contact-emergency" value="<?= htmlspecialchars($layout['contact']['emergency_phone'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TAB 2: THEME & BRANDING -->
                <div id="tab-panel-design" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-2">انتخاب پالت رنگی قالب</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'emerald' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="emerald" <?= $site['theme_palette'] === 'emerald' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('emerald')">
                                <span class="w-5 h-5 rounded-full bg-emerald-600 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">سبز درمانی</div>
                                    <div class="text-[10px] text-slate-400">Emerald Medical</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'navy' ? 'border-[#001a48] bg-blue-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="navy" <?= $site['theme_palette'] === 'navy' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('navy')">
                                <span class="w-5 h-5 rounded-full bg-[#001a48] shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">سرمه‌ای آسنا</div>
                                    <div class="text-[10px] text-slate-400">Corporate Navy</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'orange' ? 'border-orange-500 bg-orange-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="orange" <?= $site['theme_palette'] === 'orange' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('orange')">
                                <span class="w-5 h-5 rounded-full bg-orange-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">نارنجی پت‌شاپ</div>
                                    <div class="text-[10px] text-slate-400">Dynamic Orange</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'purple' ? 'border-purple-600 bg-purple-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="purple" <?= $site['theme_palette'] === 'purple' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('purple')">
                                <span class="w-5 h-5 rounded-full bg-purple-600 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">بنفش دارویی</div>
                                    <div class="text-[10px] text-slate-400">Rx Pharmacy</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dual-Mode Logo Upload / Link -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800">لوگوی اختصاصی وب‌سایت</label>
                            <div class="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-xs w-48">
                                <button type="button" id="logo-img-btn-mode-upload" onclick="switchImageInputMode('logo-img', 'upload')" class="flex-1 py-1 text-[11px] font-bold rounded-lg bg-white text-slate-800 shadow-xs border border-slate-200 transition-all">
                                    <span>📤 آپلود فایل</span>
                                </button>
                                <button type="button" id="logo-img-btn-mode-link" onclick="switchImageInputMode('logo-img', 'link')" class="flex-1 py-1 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-800 transition-all">
                                    <span>🔗 درج لینک</span>
                                </button>
                            </div>
                        </div>

                        <!-- Live Thumbnail Preview -->
                        <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80 mb-2">
                            <div class="w-12 h-12 rounded-xl bg-white p-1 border border-slate-300 shrink-0 flex items-center justify-center shadow-xs">
                                <img id="preview-thumb-logo" src="<?= !empty($site['logo_url']) ? (str_starts_with($site['logo_url'], 'http') ? htmlspecialchars($site['logo_url']) : '../' . ltrim(htmlspecialchars($site['logo_url']), '/')) : '../assets/images/logo.png' ?>" class="w-full h-full object-contain" onerror="this.src='../assets/images/logo.png'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-bold text-slate-700 truncate">لوگوی فعال وب‌سایت</div>
                                <div id="input-site-logo-status" class="flex items-center gap-1 mt-0.5 text-[10px] text-slate-400">نمایش در هدر و فوتر وب‌سایت اختصاصی</div>
                            </div>
                        </div>

                        <!-- Upload Box -->
                        <div id="logo-img-upload-box" class="space-y-1">
                            <label class="flex flex-col items-center justify-center p-3.5 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl cursor-pointer bg-white hover:bg-emerald-50/20 transition-all text-center group">
                                <span class="material-symbols-outlined text-2xl text-slate-400 group-hover:text-emerald-600 mb-1">cloud_upload</span>
                                <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">انتخاب لوگو از دستگاه</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">فرمت PNG شفاف، WebP یا SVG (حداکثر ۵ مگابایت)</span>
                                <input type="file" accept="image/*" class="hidden" onchange="handleImageUpload(this, 'input-site-logo', 'preview-thumb-logo')">
                            </label>
                        </div>

                        <!-- Link Box -->
                        <div id="logo-img-link-box" class="hidden">
                            <input type="text" id="input-site-logo" value="<?= htmlspecialchars($site['logo_url']) ?>" oninput="updateThumbSrc('preview-thumb-logo', this.value)" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr" placeholder="https://example.com/logo.png">
                        </div>
                    </div>

                    <!-- Dual-Mode Banner Upload / Link -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800">تصویر شاخص پس‌زمینه (Site Banner)</label>
                            <div class="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-xs w-48">
                                <button type="button" id="banner-img-btn-mode-upload" onclick="switchImageInputMode('banner-img', 'upload')" class="flex-1 py-1 text-[11px] font-bold rounded-lg bg-white text-slate-800 shadow-xs border border-slate-200 transition-all">
                                    <span>📤 آپلود فایل</span>
                                </button>
                                <button type="button" id="banner-img-btn-mode-link" onclick="switchImageInputMode('banner-img', 'link')" class="flex-1 py-1 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-800 transition-all">
                                    <span>🔗 درج لینک</span>
                                </button>
                            </div>
                        </div>

                        <!-- Live Thumbnail Preview -->
                        <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80 mb-2">
                            <div class="w-16 h-12 rounded-xl bg-slate-200 overflow-hidden border border-slate-300 shrink-0 flex items-center justify-center">
                                <img id="preview-thumb-banner" src="<?= !empty($site['banner_url']) ? (str_starts_with($site['banner_url'], 'http') ? htmlspecialchars($site['banner_url']) : '../' . ltrim(htmlspecialchars($site['banner_url']), '/')) : '../assets/images/clinic-banner.jpg' ?>" class="w-full h-full object-cover" onerror="this.src='../assets/images/clinic-banner.jpg'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-bold text-slate-700 truncate">بنر عمومی سایت</div>
                                <div id="input-site-banner-status" class="flex items-center gap-1 mt-0.5 text-[10px] text-slate-400">تصویر عریض برای پس‌زمینه کارت‌ها و اشتراک‌گذاری</div>
                            </div>
                        </div>

                        <!-- Upload Box -->
                        <div id="banner-img-upload-box" class="space-y-1">
                            <label class="flex flex-col items-center justify-center p-3.5 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl cursor-pointer bg-white hover:bg-emerald-50/20 transition-all text-center group">
                                <span class="material-symbols-outlined text-2xl text-slate-400 group-hover:text-emerald-600 mb-1">cloud_upload</span>
                                <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">انتخاب بنر از دستگاه</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">فرمت‌های JPG، PNG، WebP (حداکثر ۱۰ مگابایت)</span>
                                <input type="file" accept="image/*" class="hidden" onchange="handleImageUpload(this, 'input-site-banner', 'preview-thumb-banner')">
                            </label>
                        </div>

                        <!-- Link Box -->
                        <div id="banner-img-link-box" class="hidden">
                            <input type="text" id="input-site-banner" value="<?= htmlspecialchars($site['banner_url']) ?>" oninput="updateThumbSrc('preview-thumb-banner', this.value)" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono" dir="ltr" placeholder="https://example.com/banner.jpg">
                        </div>
                    </div>
                </div>

                <!-- TAB 3: TIERS & SETTINGS -->
                <div id="tab-panel-settings" class="space-y-4 hidden">
                    
                    <!-- Website Tier Selector matching config/tiers.php -->
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1.5 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-amber-500 text-sm">workspace_premium</span>
                            <span>سطح لایسنس و نسخه وب‌سایت</span>
                        </label>
                        <select id="input-site-tier" class="w-full text-xs p-3 rounded-xl border-2 border-indigo-100 bg-indigo-50/30 font-bold text-slate-800 focus:border-indigo-600 focus:outline-none">
                            <option value="basic" <?= $siteTier === 'basic' ? 'selected' : '' ?>>نسخه پایه: نوبت‌دهی و ویترین ضروری (Basic)</option>
                            <option value="standard" <?= $siteTier === 'standard' ? 'selected' : '' ?>>نسخه تجاری: دانشنامه و باشگاه مشتریان (Standard)</option>
                            <option value="premium" <?= $siteTier === 'premium' ? 'selected' : '' ?>>نسخه حرفه‌ای: فول کلینیک، تله‌هلث و اتوشیپ (Premium)</option>
                            <option value="pharmacy" <?= $siteTier === 'pharmacy' ? 'selected' : '' ?>>نسخه تخصصی: داروخانه و نسخه آنلاین (Pharmacy)</option>
                            <option value="enterprise" <?= $siteTier === 'enterprise' ? 'selected' : '' ?>>نسخه اینترپرایز: فول اکوسیستم جامع (Enterprise)</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1 leading-normal">امکانات بلوک‌ها متناسب با نسخه انتخابی شما در پلتفرم فعال و همگام‌سازی می‌گردد.</p>
                        <button type="button" onclick="applySelectedTierPreset()" id="btn-apply-tier-preset" class="mt-2 w-full py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold shadow-sm transition-all flex items-center justify-center gap-1.5 active:scale-95">
                            <span class="material-symbols-outlined text-sm">auto_fix_high</span>
                            <span>اعمال چیدمان و بلوک‌های پیشنهادی این نسخه</span>
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">عنوان اصلی سایت (Site Title)</label>
                        <input type="text" id="input-site-title" value="<?= htmlspecialchars($site['site_title']) ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">شعار یا برچسب تخصص (Tagline)</label>
                        <input type="text" id="input-site-tagline" value="<?= htmlspecialchars($site['site_tagline'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">شناسه و آدرس اختصاصی ساب‌دامین آسنا</label>
                        <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-emerald-600">
                            <span class="px-3 text-xs text-slate-400 font-mono bg-slate-50 border-l border-slate-200">.asena.company</span>
                            <input type="text" id="input-site-slug" value="<?= $slug ?>" oninput="checkSlugLive(this.value)" class="flex-1 text-xs p-2.5 outline-none font-mono text-left font-bold" dir="ltr">
                        </div>
                        <div id="slug-feedback" class="text-[11px] mt-1 font-medium text-emerald-600">✓ این آدرس در شبکه آسنا در دسترس است.</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">توضیحات سئو گوگل (Meta Description)</label>
                        <textarea id="input-site-meta" rows="3" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none"><?= htmlspecialchars($site['meta_description'] ?? '') ?></textarea>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer">
                            <input type="checkbox" id="input-is-published" <?= !empty($site['is_published']) ? 'checked' : '' ?> class="rounded text-emerald-600 w-4 h-4">
                            <span>انتشار عمومی وب‌سایت در اینترنت</span>
                        </label>
                    </div>
                </div>

            </div>
        </aside>

        <!-- Live Preview Sandbox Area (Expansive Canvas) -->
        <main id="studio-preview-main" class="flex-1 bg-slate-200/70 p-2 sm:p-4 lg:p-6 flex items-center justify-center overflow-hidden relative">
            
            <!-- Floating Quick Edit Pill for fast access -->
            <div id="floating-edit-pill" class="absolute top-4 right-4 z-20 flex items-center gap-2.5 bg-slate-900/85 hover:bg-slate-900 text-white text-xs px-4 py-2.5 rounded-2xl shadow-xl backdrop-blur-md border border-white/10 transition-all duration-300 cursor-pointer group active:scale-95" onclick="openSidebar('blocks')">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="material-symbols-outlined text-emerald-400 text-base">edit_note</span>
                <span class="font-bold">ویرایش محتوا و بلوک‌ها</span>
                <span class="text-[11px] text-slate-300 border-r border-white/20 pr-2 mr-1 hidden sm:inline group-hover:text-white transition-colors">یا مستقیم روی هر بخش کلیک کنید</span>
            </div>

            <div id="viewport-wrapper" class="w-full h-full max-w-full bg-white rounded-2xl lg:rounded-3xl shadow-2xl overflow-hidden border border-slate-300/80 transition-all duration-300 relative flex flex-col">
                <!-- Sandbox Browser Header -->
                <div class="h-9 bg-slate-100 border-b border-slate-200 px-4 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    </div>
                    <div class="px-6 py-1 rounded-lg bg-white border border-slate-200/80 text-[11px] font-mono text-slate-500 text-center max-w-md w-full truncate">
                        https://<?= $slug ?>.asena.company
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="document.getElementById('site-preview-iframe').contentWindow.location.reload()" class="p-1 text-slate-400 hover:text-slate-600 rounded transition-colors" title="بارگذاری مجدد پیش‌نمایش">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                        </button>
                    </div>
                </div>

                <!-- Iframe -->
                <iframe id="site-preview-iframe" src="<?= $previewUrl ?>" class="w-full flex-1 border-0"></iframe>
            </div>
        </main>
    </div>
</div>

<script>
// State Management
const currentSiteId = <?= (int)$site['id'] ?>;
const tenantType = '<?= $builderTenantType ?>';
const tenantId = <?= (int)$builderTenantId ?>;
// Studio sidebar is open by default on desktop, and closed on mobile
let isSidebarOpen = window.innerWidth >= 1024;

// Auto-adjust initial mobile vs desktop sidebar state on load
document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth < 1024) {
        closeSidebar();
    }
});

function togglePortalSidebar() {
    const sidebar = document.getElementById('doctor-sidebar') || 
                    document.getElementById('org-sidebar') || 
                    document.getElementById('pharmacist-sidebar') || 
                    document.getElementById('seller-sidebar');
    const backdrop = document.getElementById('portal-backdrop');
    if (!sidebar) return;
    if (sidebar.classList.contains('portal-sidebar-open')) {
        sidebar.classList.remove('portal-sidebar-open');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    } else {
        sidebar.classList.add('portal-sidebar-open');
        if (backdrop) backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function updateThumbSrc(thumbId, url) {
    const thumb = document.getElementById(thumbId);
    if (!thumb) return;
    if (!url) {
        thumb.src = '../assets/images/clinic-banner.jpg';
        return;
    }
    thumb.src = url.startsWith('http') ? url : ('../' + url.replace(/^\//, ''));
}

async function handleImageUpload(fileInput, targetInputId, previewImgId) {
    if (!fileInput.files || !fileInput.files[0]) return;
    const file = fileInput.files[0];

    const targetInput = document.getElementById(targetInputId);
    const previewImg = document.getElementById(previewImgId);
    const statusEl = document.getElementById(targetInputId + '-status');

    if (statusEl) {
        statusEl.innerHTML = '<span class="material-symbols-outlined text-xs animate-spin text-emerald-600">sync</span> <span class="text-[11px] text-emerald-600 font-bold">در حال بارگذاری فایل...</span>';
    }

    const fd = new FormData();
    fd.append('action', 'upload_asset');
    fd.append('file', file);
    fd.append('tenant_type', tenantType);

    try {
        const res = await fetch('../actions/site_builder_action.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            targetInput.value = data.url;
            if (previewImg) {
                previewImg.src = data.full_url || ('../' + data.url);
            }
            if (statusEl) {
                statusEl.innerHTML = '<span class="material-symbols-outlined text-xs text-emerald-600">check_circle</span> <span class="text-[11px] text-emerald-600 font-bold">با موفقیت بارگذاری شد.</span>';
                setTimeout(() => { 
                    statusEl.innerHTML = 'تصویر با موفقیت در فضای ابری آسنا ذخیره شد.';
                }, 3000);
            }
            showToast('✓ تصویر با موفقیت بارگذاری شد. در حال ذخیره...', 'success');
            // Auto save and update preview iframe
            await saveSiteConfig();
        } else {
            if (statusEl) {
                statusEl.innerHTML = `<span class="material-symbols-outlined text-xs text-rose-500">error</span> <span class="text-[11px] text-rose-500 font-bold">${data.message}</span>`;
            }
            showToast('✕ ' + data.message, 'error');
        }
    } catch (e) {
        if (statusEl) {
            statusEl.innerHTML = '<span class="material-symbols-outlined text-xs text-rose-500">error</span> <span class="text-[11px] text-rose-500 font-bold">خطا در ارتباط با سرور</span>';
        }
        showToast('✕ خطا در بارگذاری تصویر', 'error');
    }
}

function switchImageInputMode(fieldKey, mode) {
    const uploadBox = document.getElementById(`${fieldKey}-upload-box`);
    const linkBox = document.getElementById(`${fieldKey}-link-box`);
    const btnUpload = document.getElementById(`${fieldKey}-btn-mode-upload`);
    const btnLink = document.getElementById(`${fieldKey}-btn-mode-link`);

    if (!uploadBox || !linkBox) return;

    if (mode === 'upload') {
        uploadBox.classList.remove('hidden');
        linkBox.classList.add('hidden');
        btnUpload.className = 'flex-1 py-1 text-[11px] font-bold rounded-lg bg-white text-slate-800 shadow-xs border border-slate-200 transition-all';
        btnLink.className = 'flex-1 py-1 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-800 transition-all';
    } else {
        uploadBox.classList.add('hidden');
        linkBox.classList.remove('hidden');
        btnLink.className = 'flex-1 py-1 text-[11px] font-bold rounded-lg bg-white text-slate-800 shadow-xs border border-slate-200 transition-all';
        btnUpload.className = 'flex-1 py-1 text-[11px] font-bold rounded-lg text-slate-500 hover:text-slate-800 transition-all';
    }
}

function toggleSidebar() {
    if (isSidebarOpen) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function openSidebar(tab = null) {
    isSidebarOpen = true;
    const sidebar = document.getElementById('studio-sidebar');
    const toggleText = document.getElementById('sidebar-toggle-text');
    const toggleIcon = document.getElementById('sidebar-toggle-icon');
    const toggleBadge = document.getElementById('sidebar-status-badge');
    const floatingPill = document.getElementById('floating-edit-pill');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mBtnControls = document.getElementById('m-btn-controls');
    const mBtnPreview = document.getElementById('m-btn-preview');

    if (sidebar) {
        sidebar.classList.remove('sidebar-closed');
        sidebar.classList.add('sidebar-open');
    }

    if (toggleText) toggleText.innerText = 'بستن پنل ویرایش';
    if (toggleIcon) toggleIcon.innerText = 'close';
    if (toggleBadge) {
        toggleBadge.innerText = 'باز';
        toggleBadge.className = 'text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold transition-colors';
    }
    if (floatingPill) {
        floatingPill.classList.add('opacity-0', 'pointer-events-none');
    }
    if (backdrop) {
        backdrop.classList.remove('hidden');
    }
    if (mBtnControls && mBtnPreview) {
        mBtnControls.className = 'flex-1 py-2 rounded-xl bg-slate-900 text-white flex items-center justify-center gap-1.5 transition-all';
        mBtnPreview.className = 'flex-1 py-2 rounded-xl text-slate-600 hover:bg-slate-100 flex items-center justify-center gap-1.5 transition-all';
    }

    if (tab) {
        switchSidebarTab(tab);
    }
}

function closeSidebar() {
    isSidebarOpen = false;
    const sidebar = document.getElementById('studio-sidebar');
    const toggleText = document.getElementById('sidebar-toggle-text');
    const toggleIcon = document.getElementById('sidebar-toggle-icon');
    const toggleBadge = document.getElementById('sidebar-status-badge');
    const floatingPill = document.getElementById('floating-edit-pill');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mBtnControls = document.getElementById('m-btn-controls');
    const mBtnPreview = document.getElementById('m-btn-preview');

    if (sidebar) {
        sidebar.classList.remove('sidebar-open');
        sidebar.classList.add('sidebar-closed');
    }

    if (toggleText) toggleText.innerText = 'ویرایش محتوا و بلوک‌ها';
    if (toggleIcon) toggleIcon.innerText = 'tune';
    if (toggleBadge) {
        toggleBadge.innerText = 'بسته';
        toggleBadge.className = 'text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-normal transition-colors';
    }
    if (floatingPill) {
        floatingPill.classList.remove('opacity-0', 'pointer-events-none');
    }
    if (backdrop) {
        backdrop.classList.add('hidden');
    }
    if (mBtnControls && mBtnPreview) {
        mBtnPreview.className = 'flex-1 py-2 rounded-xl bg-slate-900 text-white flex items-center justify-center gap-1.5 transition-all';
        mBtnControls.className = 'flex-1 py-2 rounded-xl text-slate-600 hover:bg-slate-100 flex items-center justify-center gap-1.5 transition-all';
    }
}

function switchMobileStudioView(mode) {
    if (mode === 'preview') {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function switchSidebarTab(tab) {
    ['blocks', 'design', 'settings'].forEach(t => {
        const p = document.getElementById(`tab-panel-${t}`);
        const b = document.getElementById(`tab-btn-${t}`);
        if (p) p.classList.add('hidden');
        if (b) b.className = 'flex-1 py-3 text-center border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center gap-1';
    });
    const activePanel = document.getElementById(`tab-panel-${tab}`);
    const activeBtn = document.getElementById(`tab-btn-${tab}`);
    if (activePanel) activePanel.classList.remove('hidden');
    if (activeBtn) activeBtn.className = 'flex-1 py-3 text-center border-b-2 border-emerald-600 text-emerald-800 bg-white transition-all flex items-center justify-center gap-1';
}

function toggleAccordion(id) {
    const content = document.getElementById(`content-${id}`);
    const arrow = document.getElementById(`arrow-${id}`);
    if (!content) return;
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
        content.classList.add('hidden');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
}

function setViewport(size) {
    const wrapper = document.getElementById('viewport-wrapper');
    ['desktop', 'tablet', 'mobile'].forEach(s => {
        const btn = document.getElementById(`btn-vp-${s}`);
        if (btn) btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all';
    });
    const activeBtn = document.getElementById(`btn-vp-${size}`);
    if (activeBtn) activeBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-sm flex items-center gap-1 transition-all';

    if (size === 'mobile') {
        wrapper.style.maxWidth = '390px';
    } else if (size === 'tablet') {
        wrapper.style.maxWidth = '768px';
    } else {
        wrapper.style.maxWidth = '100%';
    }
}

// Click-to-edit listener from iframe: automatically opens sidebar on click and highlights block!
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'BLOCK_CLICKED') {
        const blockId = event.data.blockId;
        openSidebar('blocks');
        const section = document.getElementById(`section-${blockId}`);
        if (section) {
            const content = document.getElementById(`content-${blockId}`);
            if (content && content.classList.contains('hidden')) {
                toggleAccordion(blockId);
            }
            setTimeout(() => {
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                section.classList.add('ring-2', 'ring-emerald-500');
                setTimeout(() => section.classList.remove('ring-2', 'ring-emerald-500'), 1500);
            }, 100);
        }
    }
});

// Close sidebar on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isSidebarOpen) {
        closeSidebar();
    }
});

let slugTimer = null;
function checkSlugLive(val) {
    clearTimeout(slugTimer);
    slugTimer = setTimeout(async () => {
        const fd = new FormData();
        fd.append('action', 'check_slug');
        fd.append('slug', val);
        fd.append('site_id', currentSiteId);
        try {
            const res = await fetch('../actions/site_builder_action.php', { method: 'POST', body: fd });
            const data = await res.json();
            const fb = document.getElementById('slug-feedback');
            if (data.available) {
                fb.className = 'text-[11px] mt-1 font-medium text-emerald-600';
                fb.innerText = '✓ این آدرس در شبکه آسنا در دسترس است.';
            } else {
                fb.className = 'text-[11px] mt-1 font-medium text-red-600';
                fb.innerText = '✕ این شناسه قبلاً ثبت شده یا غیرمجاز است.';
            }
        } catch (e) {}
    }, 400);
}

async function saveSiteConfig() {
    const btn = document.getElementById('btn-save-site');
    const saveIcon = document.getElementById('save-icon');
    const saveText = document.getElementById('save-text');

    btn.disabled = true;
    saveIcon.innerText = 'sync';
    saveIcon.classList.add('animate-spin');
    saveText.innerText = 'در حال ذخیره و انتشار...';

    // Construct Layout Payload
    const layout = {
        header: {
            show_phone: true,
            phone: document.getElementById('input-contact-phone')?.value || ''
        },
        hero: {
            enabled: true,
            badge: document.getElementById('input-hero-badge')?.value || '',
            title: document.getElementById('input-hero-title')?.value || '',
            subtitle: document.getElementById('input-hero-subtitle')?.value || '',
            cta_primary_text: document.getElementById('input-hero-cta')?.value || '',
            image: document.getElementById('input-hero-image')?.value || ''
        },
        stats_strip: {
            enabled: true
        },
        bento_facilities: {
            enabled: document.getElementById('input-bento-enabled')?.checked || false,
            heading: document.getElementById('input-bento-heading')?.value || 'تجهیزات مدرن و ظرفیت‌های بالینی مرکز'
        },
        telehealth_launcher: {
            enabled: document.getElementById('input-telehealth-enabled')?.checked || false
        },
        autoship_showcase: {
            enabled: document.getElementById('input-autoship-enabled')?.checked || false
        },
        about: {
            enabled: true,
            heading: document.getElementById('input-about-heading')?.value || 'درباره ما',
            text: document.getElementById('input-about-text')?.value || '',
            vet_council: document.getElementById('input-about-vet-council')?.value || ''
        },
        doctors_roster: {
            enabled: document.getElementById('input-doctors-enabled')?.checked || false,
            heading: document.getElementById('input-doctors-heading')?.value || 'کادر پزشکان و متخصصان مرکز',
            subtitle: document.getElementById('input-doctors-subtitle')?.value || 'دامپزشکان مجرب با پرونده سلامت ابری و امکان نوبت‌دهی آنلاین'
        },
        booking: {
            enabled: document.getElementById('input-booking-enabled')?.checked || false,
            heading: document.getElementById('input-booking-heading')?.value || 'نوبت‌دهی آنلاین'
        },
        storefront: {
            enabled: document.getElementById('input-storefront-enabled')?.checked || false,
            item_limit: parseInt(document.getElementById('input-storefront-limit')?.value || '6')
        },
        articles: {
            enabled: document.getElementById('input-articles-enabled')?.checked || false,
            heading: document.getElementById('input-articles-heading')?.value || 'دانشنامه سلامت و مقالات علمی دامپزشکی'
        },
        loyalty_club: {
            enabled: document.getElementById('input-loyalty-enabled')?.checked || false,
            heading: document.getElementById('input-loyalty-heading')?.value || '۵۰ امتیاز پاداش با هر ثبت نوبت یا خرید آنلاین'
        },
        reviews: {
            enabled: document.getElementById('input-reviews-enabled')?.checked || false,
            heading: document.getElementById('input-reviews-heading')?.value || 'نظرات و بازخورد مراجعین تاییدشده'
        },
        contact: {
            enabled: true,
            address: document.getElementById('input-contact-address')?.value || '',
            hours: document.getElementById('input-contact-hours')?.value || '',
            phone: document.getElementById('input-contact-phone')?.value || '',
            emergency_phone: document.getElementById('input-contact-emergency')?.value || ''
        },
        sticky_mobile_bar: {
            enabled: true
        }
    };

    const paletteEl = document.querySelector('input[name="theme_palette"]:checked');
    const selectedPalette = paletteEl ? paletteEl.value : 'emerald';

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('tenant_type', tenantType);
    fd.append('tenant_id', tenantId);
    fd.append('site_tier', document.getElementById('input-site-tier')?.value || 'enterprise');
    fd.append('site_title', document.getElementById('input-site-title')?.value || '');
    fd.append('site_tagline', document.getElementById('input-site-tagline')?.value || '');
    fd.append('slug', document.getElementById('input-site-slug')?.value || '');
    fd.append('theme_palette', selectedPalette);
    fd.append('logo_url', document.getElementById('input-site-logo')?.value || '');
    fd.append('banner_url', document.getElementById('input-site-banner')?.value || '');
    fd.append('meta_description', document.getElementById('input-site-meta')?.value || '');
    fd.append('is_published', document.getElementById('input-is-published')?.checked ? '1' : '0');
    fd.append('layout', JSON.stringify(layout));

    try {
        const res = await fetch('../actions/site_builder_action.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const iframe = document.getElementById('site-preview-iframe');
            iframe.src = `../site.php?slug=${encodeURIComponent(data.slug)}&preview=1&v=${Date.now()}`;
            showToast('✓ وب‌سایت اختصاصی شما با موفقیت به‌روزرسانی و منتشر شد.', 'success');
        } else {
            showToast('✕ خطا: ' + (data.message || 'مشکلی رخ داد'), 'error');
        }
    } catch (e) {
        showToast('✕ ارتباط با سرور برقرار نشد.', 'error');
    } finally {
        btn.disabled = false;
        saveIcon.innerText = 'cloud_upload';
        saveIcon.classList.remove('animate-spin');
        saveText.innerText = 'ذخیره و انتشار';
    }
}

async function applySelectedTierPreset() {
    const tier = document.getElementById('input-site-tier')?.value || 'enterprise';
    const btn = document.getElementById('btn-apply-tier-preset');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span><span>در حال اعمال قالب نسخه...</span>';

    try {
        const fd = new FormData();
        fd.append('action', 'apply_preset');
        fd.append('tier', tier);
        fd.append('tenant_type', tenantType);

        const res = await fetch('../actions/site_builder_action.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success && data.layout && data.layout.blocks) {
            const b = data.layout.blocks;
            if (document.getElementById('input-bento-enabled') && b.bento_facilities !== undefined) {
                document.getElementById('input-bento-enabled').checked = !!b.bento_facilities.enabled;
            }
            if (document.getElementById('input-telehealth-enabled') && b.telehealth_launcher !== undefined) {
                document.getElementById('input-telehealth-enabled').checked = !!b.telehealth_launcher.enabled;
            }
            if (document.getElementById('input-autoship-enabled') && b.autoship_showcase !== undefined) {
                document.getElementById('input-autoship-enabled').checked = !!b.autoship_showcase.enabled;
            }
            if (document.getElementById('input-doctors-enabled') && b.doctors_roster !== undefined) {
                document.getElementById('input-doctors-enabled').checked = !!b.doctors_roster.enabled;
            }
            if (document.getElementById('input-articles-enabled') && b.articles !== undefined) {
                document.getElementById('input-articles-enabled').checked = !!b.articles.enabled;
            }
            if (document.getElementById('input-loyalty-enabled') && b.loyalty_club !== undefined) {
                document.getElementById('input-loyalty-enabled').checked = !!b.loyalty_club.enabled;
            }
            if (document.getElementById('input-reviews-enabled') && b.reviews !== undefined) {
                document.getElementById('input-reviews-enabled').checked = !!b.reviews.enabled;
            }
            if (document.getElementById('input-storefront-limit') && b.storefront !== undefined) {
                document.getElementById('input-storefront-limit').value = b.storefront.item_limit || 6;
            }

            showToast(`✓ بلوک‌های اختصاصی نسخه «${tier}» اعمال شد. در حال به‌روزرسانی پیش‌نمایش...`, 'success');
            await saveSiteConfig();
        }
    } catch (e) {
        showToast('✕ خطا در دریافت قالب نسخه', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 left-6 z-50 px-5 py-3 rounded-2xl text-xs font-bold text-white shadow-2xl backdrop-blur-md transition-all flex items-center gap-2 ${type === 'success' ? 'bg-emerald-600/95' : 'bg-red-600/95'}`;
    toast.innerHTML = `<span class="material-symbols-outlined text-base">${type === 'success' ? 'check_circle' : 'error'}</span><span>${msg}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}
</script>
