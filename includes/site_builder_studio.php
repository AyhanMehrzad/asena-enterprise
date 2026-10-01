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
$layout = !empty($site['layout']['blocks']) && is_array($site['layout']['blocks']) 
    ? $site['layout']['blocks'] 
    : (!empty($site['layout']) && is_array($site['layout']) ? $site['layout'] : []);
$slug = htmlspecialchars($site['slug']);
$siteTier = $site['site_tier'] ?? 'enterprise';
$previewUrl = "../site.php?slug=" . urlencode($site['slug']) . "&preview=1";
$publicUrl = "../site.php?slug=" . urlencode($site['slug']);

$paletteColorDefaults = [
    'emerald' => ['primary' => '#059669', 'secondary' => '#fd8100'],
    'navy' => ['primary' => '#001a48', 'secondary' => '#fd8100'],
    'orange' => ['primary' => '#ea580c', 'secondary' => '#001a48'],
    'purple' => ['primary' => '#7c3aed', 'secondary' => '#ea580c'],
    'aurora' => ['primary' => '#0891b2', 'secondary' => '#001a48']
];
$activePaletteKey = $site['theme_palette'] ?? 'emerald';
$activeDef = $paletteColorDefaults[$activePaletteKey] ?? $paletteColorDefaults['emerald'];
$activePrimaryColor = $layout['theme']['primary_color'] ?? ($site['primary_color'] ?: $activeDef['primary']);
$activeSecondaryColor = $layout['theme']['secondary_color'] ?? ($site['secondary_color'] ?: $activeDef['secondary']);
if (empty($layout['theme']['primary_color']) && $activePaletteKey !== 'navy' && $activePrimaryColor === '#001a48') {
    $activePrimaryColor = $activeDef['primary'];
}
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
                        <span><?= $slug ?>.ir</span>
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

            <!-- Smart Instant Search Bar -->
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 shrink-0">
                <div class="relative">
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-base">search</span>
                    <input type="text" id="studio-quick-search" oninput="handleStudioQuickSearch(this.value)" placeholder="جستجوی سریع فیلد یا بخش (مثلاً: شماره، هیرو، محاسبه‌گر)..." class="w-full pr-9 pl-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium placeholder:text-slate-400 shadow-2xs">
                    <button type="button" onclick="clearStudioQuickSearch()" id="btn-clear-search" class="hidden absolute left-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            </div>

            <!-- Sidebar Tabs -->
            <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-bold shrink-0">
                <button type="button" onclick="switchSidebarTab('quick')" id="tab-btn-quick" class="flex-1 py-3 text-center border-b-2 border-emerald-600 text-emerald-800 bg-white transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-base text-amber-500">bolt</span>
                    <span>ویرایش سریع</span>
                </button>
                <button type="button" onclick="switchSidebarTab('blocks')" id="tab-btn-blocks" class="flex-1 py-3 text-center border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center gap-1">
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
                
                <!-- TAB 0: QUICK EDIT (SIMPLIFIED COCKPIT) -->
                <div id="tab-panel-quick" class="space-y-4">
                    
                    <!-- Direct Click-To-Edit Tip Banner -->
                    <div class="p-3 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-indigo-50 border border-emerald-200/80 shadow-2xs">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0 mt-0.5 animate-pulse">touch_app</span>
                            <div>
                                <div class="text-xs font-black text-slate-800">ویرایش زنده با یک کلیک در پیش‌نمایش</div>
                                <div class="text-[11px] text-slate-600 leading-relaxed mt-0.5">
                                    می‌توانید مستقیماً روی هر متن، تیتر، شماره تماس یا دکمه در تصویر پیش‌نمایش سمت چپ کلیک کنید و درجا بنویسید!
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 1: Identity & Primary Contact -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3 quick-card" data-search-keys="هویت کلینیک عنوان نام تلفن آدرس نشانی شعار تماس">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-600 text-base">domain</span>
                                <span class="text-xs font-bold text-slate-800">مشخصات و تماس اصلی کلینیک</span>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium">پایه</span>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">نام مرکز یا پزشک</label>
                                <input type="text" id="input-quick-site-title" value="<?= htmlspecialchars($site['site_title'] ?? '') ?>" placeholder="مثلاً: کلینیک تخصصی دامپزشکی دکتر علوی" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold text-slate-800 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">شعار و زیرعنوان مرکز</label>
                                <input type="text" id="input-quick-site-tagline" value="<?= htmlspecialchars($site['site_tagline'] ?? '') ?>" placeholder="مرکز مجهز جراحی و سلامت حیوانات خانگی" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-700 transition-all">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700 block mb-1">شماره تماس ثابت/نوبت‌دهی</label>
                                    <input type="text" id="input-quick-contact-phone" value="<?= htmlspecialchars($layout['contact']['phone'] ?? '۰۲۱-۸۸۸۸۹۹۹۹') ?>" dir="ltr" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 text-left font-mono font-bold text-slate-800 transition-all">
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700 block mb-1">تلفن اورژانس ۲۴ ساعته</label>
                                    <input type="text" id="input-quick-emergency-phone" value="<?= htmlspecialchars($layout['emergency_bar']['phone'] ?? ($layout['contact']['emergency_phone'] ?? '۰۹۱۲-۹۹۹-۸۸۷۷')) ?>" dir="ltr" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 text-left font-mono font-bold text-red-700 transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">نشانی پستی کلینیک</label>
                                <input type="text" id="input-quick-contact-address" value="<?= htmlspecialchars($layout['contact']['address'] ?? 'تهران، خیابان ولیعصر، نرسیده به میدان ونک، پلاک ۱۲') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-700 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Hero & Headline (With 1-click clinical preset copy button) -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3 quick-card" data-search-keys="هیرو معرفی تیتر اصلی عکس دکمه بنر اقدام">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-indigo-600 text-base">featured_play_list</span>
                                <span class="text-xs font-bold text-slate-800">بخش معرفی اصلی و سربرگ (هیرو)</span>
                            </div>
                            <button type="button" onclick="applyClinicalCopyTemplate('hero')" class="px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[10px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer" title="پرکردن خودکار با متن استاندارد بالینی">
                                <span class="material-symbols-outlined text-xs">magic_button</span>
                                <span>متن آماده بالینی</span>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">نشان بالای تیتر (بج)</label>
                                <input type="text" id="input-quick-hero-badge" value="<?= htmlspecialchars($layout['hero']['badge'] ?? '🛡️ مرکز تاییدشده شبکه سلامت آتنا • استاندارد طلایی') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">تیتر اصلی چشمگیر</label>
                                <input type="text" id="input-quick-hero-title" value="<?= htmlspecialchars($layout['hero']['title'] ?? 'مراقبت هوشمند و درمان پیشرفته پت شما با پرونده سلامت ابری') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">توضیحات معرفی</label>
                                <textarea id="input-quick-hero-subtitle" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 transition-all"><?= htmlspecialchars($layout['hero']['subtitle'] ?? 'با کادر متخصص دامپزشکی، تجهیزات جراحی مدرن و پرونده ابری سلامت.') ?></textarea>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">متن دکمه نوبت‌دهی / اقدام</label>
                                <input type="text" id="input-quick-hero-cta" value="<?= htmlspecialchars($layout['hero']['cta_primary_text'] ?? 'رزرو آنلاین نوبت و معاینه') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: Safe Brand Colors & Fast Swatches -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3 quick-card" data-search-keys="رنگ تم پالت استایل ظاهر برند بازنشانی">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-600 text-base">palette</span>
                                <span class="text-xs font-bold text-slate-800">رنگ‌های برند و استایل اختصاصی</span>
                            </div>
                            <button type="button" onclick="resetBrandColorsToDefault()" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-[10px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer" title="بازگشت به رنگ‌های پیش‌فرض آتنا">
                                <span class="material-symbols-outlined text-xs">restart_alt</span>
                                <span>بازنشانی</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <input type="color" id="input-quick-primary-color" value="<?= htmlspecialchars($activePrimaryColor) ?>" onchange="handleColorInputChange('primary', this.value)" class="w-8 h-8 rounded-lg border-0 cursor-pointer p-0 bg-transparent shrink-0">
                                <div class="min-w-0">
                                    <div class="text-[11px] font-bold text-slate-700 truncate">رنگ اصلی برند</div>
                                    <div class="text-[10px] text-slate-400 font-mono" id="label-quick-primary-hex"><?= htmlspecialchars($activePrimaryColor) ?></div>
                                </div>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2">
                                <input type="color" id="input-quick-secondary-color" value="<?= htmlspecialchars($activeSecondaryColor) ?>" onchange="handleColorInputChange('secondary', this.value)" class="w-8 h-8 rounded-lg border-0 cursor-pointer p-0 bg-transparent shrink-0">
                                <div class="min-w-0">
                                    <div class="text-[11px] font-bold text-slate-700 truncate">رنگ مکمل / دکمه</div>
                                    <div class="text-[10px] text-slate-400 font-mono" id="label-quick-secondary-hex"><?= htmlspecialchars($activeSecondaryColor) ?></div>
                                </div>
                            </div>
                        </div>
                        <!-- Swatches shortcuts -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <span class="text-[10px] text-slate-400">پالت‌های آماده:</span>
                            <button type="button" onclick="applyColorSwatch('#059669', '#fd8100')" class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200 hover:scale-105 transition-transform cursor-pointer">زمردی</button>
                            <button type="button" onclick="applyColorSwatch('#001a48', '#fd8100')" class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-900 text-[10px] font-bold border border-blue-200 hover:scale-105 transition-transform cursor-pointer">درباری</button>
                            <button type="button" onclick="applyColorSwatch('#7c3aed', '#ea580c')" class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-800 text-[10px] font-bold border border-purple-200 hover:scale-105 transition-transform cursor-pointer">ارغوانی</button>
                            <button type="button" onclick="applyColorSwatch('#0891b2', '#001a48')" class="px-2 py-0.5 rounded-md bg-cyan-50 text-cyan-800 text-[10px] font-bold border border-cyan-200 hover:scale-105 transition-transform cursor-pointer">آرورا</button>
                        </div>
                    </div>

                    <!-- CARD 4: Cost Calculator Section -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3 quick-card" data-search-keys="محاسبه‌گر هزینه تعرفه قیمت جراحی تخفیف آنلاین">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-600 text-base">calculate</span>
                                <span class="text-xs font-bold text-slate-800">محاسبه‌گر آنلاین هزینه درمان</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="applyClinicalCopyTemplate('cost_calculator')" class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer" title="درج متن استاندارد شفافیت مالی">
                                    <span class="material-symbols-outlined text-xs">magic_button</span>
                                    <span>متن آماده</span>
                                </button>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="input-quick-calc-enabled" <?= !empty($layout['cost_calculator']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-amber-600"></div>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">نشان سربرگ</label>
                                <input type="text" id="input-quick-calc-badge" value="<?= htmlspecialchars($layout['cost_calculator']['badge'] ?? 'تعرفه شفاف خدمات درمانی و جراحی') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 text-slate-700 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">تیتر بخش محاسبه‌گر</label>
                                <input type="text" id="input-quick-calc-heading" value="<?= htmlspecialchars($layout['cost_calculator']['heading'] ?? 'برآورد آنلاین و شفاف تعرفه خدمات و جراحی‌های تخصصی') ?>" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-bold text-slate-900 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">زیرعنوان و توضیحات شفافیت تعرفه</label>
                                <textarea id="input-quick-calc-subtitle" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 text-slate-700 transition-all"><?= htmlspecialchars($layout['cost_calculator']['subtitle'] ?? 'گونه حیوان خانگی و خدمات تشخیصی، بالینی یا جراحی مدنظر را انتخاب فرمایید تا تعرفه مصوب رسمی همراه با ۱۰٪ تخفیف رزرو آنلاین برآورد گردد.') ?></textarea>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">درصد تخفیف رزرو اینترنتی (%)</label>
                                <input type="number" id="input-quick-calc-discount" min="0" max="50" value="<?= htmlspecialchars($layout['cost_calculator']['discount_percent'] ?? 10) ?>" class="w-28 px-3 py-1.5 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 font-bold text-slate-800 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- CARD 5: Duty Hours & Active Shift -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3 quick-card" data-search-keys="ساعت کاری شیفت زمان باز بسته فعالیت">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-teal-600 text-base">schedule</span>
                                <span class="text-xs font-bold text-slate-800">ساعات کاری و شیفت فعال</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="input-quick-duty-enabled" <?= !empty($layout['duty_hours']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-teal-600"></div>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">ساعت شروع کار</label>
                                <input type="time" id="input-quick-duty-open-time" value="<?= htmlspecialchars($layout['duty_hours']['open_time'] ?? '08:30') ?>" class="w-full px-3 py-1.5 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono font-bold text-slate-800">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">ساعت پایان کار</label>
                                <input type="time" id="input-quick-duty-close-time" value="<?= htmlspecialchars($layout['duty_hours']['close_time'] ?? '22:30') ?>" class="w-full px-3 py-1.5 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono font-bold text-slate-800">
                            </div>
                        </div>
                    </div>

                    <!-- CARD 6: Emergency Hotline Bar -->
                    <div class="p-3.5 rounded-2xl border border-red-200 bg-red-50/30 shadow-2xs space-y-3 quick-card" data-search-keys="اورژانس شبانه روزی فوری تلفن قرمز تروما">
                        <div class="flex items-center justify-between pb-2 border-b border-red-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-red-600 text-base">e911_emergency</span>
                                <span class="text-xs font-bold text-red-950">نوار اورژانس شبانه‌روزی</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="applyClinicalCopyTemplate('emergency_bar')" class="px-2 py-1 rounded-lg bg-red-100 hover:bg-red-200 text-red-800 border border-red-200 text-[10px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer" title="درج متن آماده اورژانس">
                                    <span class="material-symbols-outlined text-xs">magic_button</span>
                                    <span>متن آماده</span>
                                </button>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="input-quick-emergency-enabled" <?= !empty($layout['emergency_bar']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">تیتر پیام فوری</label>
                                <input type="text" id="input-quick-emergency-headline" value="<?= htmlspecialchars($layout['emergency_bar']['headline'] ?? 'اورژانس شبانه‌روزی و تروما دامپزشکی ۲۴/۷') ?>" class="w-full px-3 py-2 text-xs bg-white border border-red-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 font-bold text-red-950 transition-all">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700 block mb-1">زیرعنوان شرایط پذیرش</label>
                                <input type="text" id="input-quick-emergency-subheadline" value="<?= htmlspecialchars($layout['emergency_bar']['subheadline'] ?? 'پذیرش فوری سوانح، تشنج و مسمومیت‌ها با تجهیزات احیا و ICU') ?>" class="w-full px-3 py-2 text-xs bg-white border border-red-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 text-slate-700 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Link to Advanced Blocks -->
                    <div class="p-3 text-center rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                        <span>نیاز به تنظیمات کامل یا افزودن بخش دارید؟</span>
                        <button type="button" onclick="switchSidebarTab('blocks')" class="text-emerald-700 font-bold hover:underline mr-1 cursor-pointer">
                            رفتن به چینش بلوک‌ها ←
                        </button>
                    </div>

                </div>
                
                <!-- TAB 1: BLOCKS ACCORDION -->
                <div id="tab-panel-blocks" class="space-y-3 hidden">
                    <div class="p-2.5 rounded-xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-2 text-[11px] text-indigo-900 mb-1">
                        <span class="material-symbols-outlined text-indigo-600 text-base">swap_vert</span>
                        <span>با دکمه‌های فلش کنار هر بلوک، اولویت و ترتیب چیدمان بخش‌ها را به آسانی بالا و پایین جابجا کنید.</span>
                    </div>
                    
                    <!-- 0. Emergency Hotline Bar Block -->
                    <div class="border border-red-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-emergency_bar">
                        <div onclick="toggleAccordion('emergency_bar')" class="w-full p-4 flex items-center justify-between bg-red-50 hover:bg-red-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-red-600 text-lg">e911_emergency</span>
                                <span class="text-xs font-bold text-red-950">نوار اورژانس شبانه‌روزی (۲۴/۷)</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-emergency_bar', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-red-200/70 hover:bg-red-300 text-red-800 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-emergency_bar', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-red-200/70 hover:bg-red-300 text-red-800 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-red-400 text-base transition-transform" id="arrow-emergency_bar">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-red-100 hidden" id="content-emergency_bar">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-emergency-enabled" <?= !empty($layout['emergency_bar']['enabled']) ? 'checked' : '' ?> class="rounded text-red-600">
                                <span>نمایش نوار قرمز اورژانس در بالای سایت</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تیتر نوار اورژانس</label>
                                <input type="text" id="input-emergency-headline" value="<?= htmlspecialchars($layout['emergency_bar']['headline'] ?? 'اورژانس ۲۴ ساعته و مراقبت‌های فوری حیوانات خانگی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-red-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیحات خدمات اورژانسی</label>
                                <input type="text" id="input-emergency-subheadline" value="<?= htmlspecialchars($layout['emergency_bar']['subheadline'] ?? 'پذیرش فوری تروما، تصادفات و مسمومیت‌ها با امکانات احیای بالینی پیشرفته') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-red-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">شماره تماس اضطراری</label>
                                <input type="text" id="input-emergency-phone" value="<?= htmlspecialchars($layout['emergency_bar']['phone'] ?? '') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-red-600 focus:outline-none font-mono" dir="ltr" placeholder="021-91000000">
                            </div>
                        </div>
                    </div>

                    <!-- 1. Hero Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-hero">
                        <div onclick="toggleAccordion('hero')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">view_carousel</span>
                                <span class="text-xs font-bold text-slate-800">بنر قهرمان اصلی (Hero)</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-hero', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-hero', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-hero">expand_more</span>
                            </div>
                        </div>
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

                    <!-- 1.5. Shift & Duty Hours Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-duty_hours">
                        <div onclick="toggleAccordion('duty_hours')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">schedule</span>
                                <span class="text-xs font-bold text-slate-800">ویجت شیفت کاری و پذیرش زنده</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-duty_hours', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-duty_hours', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-duty_hours">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-duty_hours">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-duty-enabled" <?= !empty($layout['duty_hours']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش وضعیت باز/بسته بودن شیفت و روزشمار</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">ساعت شروع شیفت</label>
                                    <input type="text" id="input-duty-open-time" value="<?= htmlspecialchars($layout['duty_hours']['open_time'] ?? '08:30') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono text-center" dir="ltr" placeholder="08:30">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">ساعت پایان شیفت</label>
                                    <input type="text" id="input-duty-close-time" value="<?= htmlspecialchars($layout['duty_hours']['close_time'] ?? '22:30') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none font-mono text-center" dir="ltr" placeholder="22:30">
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-duty-24h" <?= !empty($layout['duty_hours']['emergency_open_24h']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>پذیرش اورژانس به صورت ۲۴ ساعته فعال است</span>
                            </label>
                        </div>
                    </div>

                    <!-- Interactive Before/After Comparison Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-before_after">
                        <div onclick="toggleAccordion('before_after')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 text-lg">compare</span>
                                <span class="text-xs font-bold text-slate-800">اسلایدر مقایسه نتایج قبل و بعد</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-before_after', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-before_after', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-before_after">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-before_after">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-ba-enabled" <?= !empty($layout['before_after']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>فعال‌سازی اسلایدر مقایسه نتایج قبل و بعد</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">تیتر بخش</label>
                                <input type="text" id="input-ba-heading" value="<?= htmlspecialchars($layout['before_after']['heading'] ?? 'مقایسه نتایج قبل و بعد از مراقبت تخصصی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیح کوتاه</label>
                                <input type="text" id="input-ba-subtitle" value="<?= htmlspecialchars($layout['before_after']['subtitle'] ?? 'مشاهده تفاوت کیفیت خدمات قبل و بعد از رسیدگی تخصصی و بالینی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">برچسب خدمت / جراحی</label>
                                <input type="text" id="input-ba-service-label" value="<?= htmlspecialchars($layout['before_after']['service_label'] ?? 'جرم‌گیری اولتراسونیک و درمان لثه') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 mb-1">برچسب قبل</label>
                                    <input type="text" id="input-ba-label-before" value="<?= htmlspecialchars($layout['before_after']['label_before'] ?? 'قبل از درمان') ?>" class="w-full text-xs p-2 rounded-lg border border-slate-200 text-center">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 mb-1">برچسب بعد</label>
                                    <input type="text" id="input-ba-label-after" value="<?= htmlspecialchars($layout['before_after']['label_after'] ?? 'پس از درمان') ?>" class="w-full text-xs p-2 rounded-lg border border-slate-200 text-center">
                                </div>
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">آدرس تصویر قبل از درمان (Before Image)</label>
                                <input type="text" id="input-ba-image-before" value="<?= htmlspecialchars($layout['before_after']['image_before'] ?? 'assets/images/presentation-dog.jpg') ?>" class="w-full text-xs p-2 rounded-lg border border-slate-200 font-mono text-left" dir="ltr" placeholder="assets/images/presentation-dog.jpg">
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">آدرس تصویر پس از درمان (After Image)</label>
                                <input type="text" id="input-ba-image-after" value="<?= htmlspecialchars($layout['before_after']['image_after'] ?? 'assets/images/clinic-banner.jpg') ?>" class="w-full text-xs p-2 rounded-lg border border-slate-200 font-mono text-left" dir="ltr" placeholder="assets/images/clinic-banner.jpg">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Bento Facilities Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-bento_facilities">
                        <div onclick="toggleAccordion('bento_facilities')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">grid_view</span>
                                <span class="text-xs font-bold text-slate-800">بنتو گرید تجهیزات و بخش‌ها</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-bento_facilities', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-bento_facilities', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-bento_facilities">expand_more</span>
                            </div>
                        </div>
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



                    <!-- 5. About Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-about">
                        <div onclick="toggleAccordion('about')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">badge</span>
                                <span class="text-xs font-bold text-slate-800">درباره ما و سوابق بالینی</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-about', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-about', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-about">expand_more</span>
                            </div>
                        </div>
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

                    <!-- 5.5. Cost Calculator Block -->
                    <div class="border border-amber-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-cost_calculator">
                        <div onclick="toggleAccordion('cost_calculator')" class="w-full p-4 flex items-center justify-between bg-amber-50/70 hover:bg-amber-100/70 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-amber-600 text-lg">calculate</span>
                                <span class="text-xs font-bold text-amber-950">محاسبه‌گر هوشمند تعرفه خدمات</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-cost_calculator', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-amber-200/70 hover:bg-amber-300 text-amber-800 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-cost_calculator', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-amber-200/70 hover:bg-amber-300 text-amber-800 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-amber-500 text-base transition-transform" id="arrow-cost_calculator">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-amber-100 hidden" id="content-cost_calculator">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-calc-enabled" <?= !empty($layout['cost_calculator']['enabled']) ? 'checked' : '' ?> class="rounded text-amber-600">
                                <span>فعال‌سازی ویجت تخمین آنلاین تعرفه و جراحی</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">نشان یا برچسب بالای عنوان</label>
                                <input type="text" id="input-calc-badge" value="<?= htmlspecialchars($layout['cost_calculator']['badge'] ?? 'تعرفه شفاف خدمات درمانی و جراحی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-amber-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان اصلی بخش تعرفه‌ها</label>
                                <input type="text" id="input-calc-heading" value="<?= htmlspecialchars($layout['cost_calculator']['heading'] ?? 'برآورد آنلاین و شفاف تعرفه خدمات و جراحی‌های تخصصی') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-amber-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیحات و راهنمای مراجعین</label>
                                <textarea id="input-calc-subtitle" rows="2" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-amber-600 focus:outline-none"><?= htmlspecialchars($layout['cost_calculator']['subtitle'] ?? 'گونه حیوان خانگی و خدمات تشخیصی، بالینی یا جراحی مدنظر را انتخاب فرمایید تا تعرفه مصوب رسمی همراه با ۱۰٪ تخفیف رزرو آنلاین برآورد گردد.') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">درصد تخفیف رزرو آنلاین</label>
                                <input type="number" id="input-calc-discount" min="0" max="50" value="<?= htmlspecialchars((string)($layout['cost_calculator']['discount_percent'] ?? 10)) ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-amber-600 focus:outline-none font-mono text-center" dir="ltr">
                            </div>
                        </div>
                    </div>

                    <!-- 6. Booking Widget Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-booking">
                        <div onclick="toggleAccordion('booking')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">calendar_month</span>
                                <span class="text-xs font-bold text-slate-800">ویجت رزرو آنلاین نوبت</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-booking', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-booking', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-booking">expand_more</span>
                            </div>
                        </div>
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
                        <div onclick="toggleAccordion('storefront')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">storefront</span>
                                <span class="text-xs font-bold text-slate-800">ویترین کالاها و داروهای انبار</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-storefront', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-storefront', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-storefront">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-storefront">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-storefront-enabled" <?= !empty($layout['storefront']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش محصولات و کالاهای موجود</span>
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
                        <div onclick="toggleAccordion('doctors_roster')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">medical_information</span>
                                <span class="text-xs font-bold text-slate-800">کادر پزشکان و متخصصان مقیم</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-doctors_roster', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-doctors_roster', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-doctors_roster">expand_more</span>
                            </div>
                        </div>
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



                    <!-- Reviews & Social Proof -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-reviews">
                        <div onclick="toggleAccordion('reviews')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">rate_review</span>
                                <span class="text-xs font-bold text-slate-800">نظرات و رضایت‌سنجی مراجعین</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-reviews', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-reviews', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-reviews">expand_more</span>
                            </div>
                        </div>
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

                    <!-- 7.5. FAQ Accordion Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-faq">
                        <div onclick="toggleAccordion('faq')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">quiz</span>
                                <span class="text-xs font-bold text-slate-800">پرسش‌های متداول (FAQ)</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-faq', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-faq', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-faq">expand_more</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 border-t border-slate-100 hidden" id="content-faq">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" id="input-faq-enabled" <?= !empty($layout['faq']['enabled']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                                <span>نمایش بخش پرسش‌های متداول و راهنمای مراجعین</span>
                            </label>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">عنوان بخش پرسش‌ها</label>
                                <input type="text" id="input-faq-heading" value="<?= htmlspecialchars($layout['faq']['heading'] ?? 'پرسش‌های متداول و راهنمای مراجعین') ?>" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- 8. Contact & Hours Block -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm" id="section-contact">
                        <div onclick="toggleAccordion('contact')" class="w-full p-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors text-right cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-slate-600 text-lg">pin_drop</span>
                                <span class="text-xs font-bold text-slate-800">تماس، ساعات کاری و آدرس</span>
                            </div>
                            <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="moveStudioBlock('section-contact', 'up')" title="انتقال به بالا" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                                </button>
                                <button type="button" onclick="moveStudioBlock('section-contact', 'down')" title="انتقال به پایین" class="w-6 h-6 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-xs">keyboard_arrow_down</span>
                                </button>
                                <span class="material-symbols-outlined text-slate-400 text-base transition-transform" id="arrow-contact">expand_more</span>
                            </div>
                        </div>
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
                                    <div class="text-xs font-bold text-slate-800">کلینیک مینیمال لوکس</div>
                                    <div class="text-[10px] text-slate-400">Luxury Minimal Clinic</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'navy' ? 'border-[#001a48] bg-blue-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="navy" <?= $site['theme_palette'] === 'navy' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('navy')">
                                <span class="w-5 h-5 rounded-full bg-[#001a48] shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">بیمارستانی سرمه‌ای</div>
                                    <div class="text-[10px] text-slate-400">Hospital Corporate Navy</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'orange' ? 'border-orange-500 bg-orange-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="orange" <?= $site['theme_palette'] === 'orange' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('orange')">
                                <span class="w-5 h-5 rounded-full bg-orange-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">همیار پت پویا</div>
                                    <div class="text-[10px] text-slate-400">Vibrant Pet Companion</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'purple' ? 'border-purple-600 bg-purple-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="purple" <?= $site['theme_palette'] === 'purple' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('purple')">
                                <span class="w-5 h-5 rounded-full bg-purple-600 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">مخمل بنفش اشرافی</div>
                                    <div class="text-[10px] text-slate-400">Midnight Velvet Luxury</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all <?= $site['theme_palette'] === 'aurora' ? 'border-cyan-600 bg-cyan-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="theme_palette" value="aurora" <?= $site['theme_palette'] === 'aurora' ? 'checked' : '' ?> class="hidden" onchange="updatePalettePreview('aurora')">
                                <span class="w-5 h-5 rounded-full bg-cyan-600 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">فیروزه‌ای تشخیصی</div>
                                    <div class="text-[10px] text-slate-400">Pure Aurora Cyan</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <?php
                    $paletteColorDefaults = [
                        'emerald' => ['primary' => '#059669', 'secondary' => '#fd8100'],
                        'navy' => ['primary' => '#001a48', 'secondary' => '#fd8100'],
                        'orange' => ['primary' => '#ea580c', 'secondary' => '#001a48'],
                        'purple' => ['primary' => '#7c3aed', 'secondary' => '#ea580c'],
                        'aurora' => ['primary' => '#0891b2', 'secondary' => '#001a48']
                    ];
                    $activePaletteKey = $site['theme_palette'] ?? 'emerald';
                    $activeDef = $paletteColorDefaults[$activePaletteKey] ?? $paletteColorDefaults['emerald'];
                    $activePrimaryColor = $layout['theme']['primary_color'] ?? ($site['primary_color'] ?: $activeDef['primary']);
                    $activeSecondaryColor = $layout['theme']['secondary_color'] ?? ($site['secondary_color'] ?: $activeDef['secondary']);
                    if (empty($layout['theme']['primary_color']) && $activePaletteKey !== 'navy' && $activePrimaryColor === '#001a48') {
                        $activePrimaryColor = $activeDef['primary'];
                    }
                    ?>
                    <!-- Brand Colors Customizer & Safe Harmonizer -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 bg-white shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-800">سفارشی‌سازی رنگ‌های برند و دکمه‌ها</div>
                                <div class="text-[10px] text-slate-400">تنظیم هارمونیک رنگ سازمانی با حفظ کنتراست استاندارد</div>
                            </div>
                            <button type="button" onclick="resetBrandColorsToDefault()" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-800 text-[11px] font-bold transition-all flex items-center gap-1 shadow-2xs cursor-pointer" title="بازنشانی به پالت رسمی">
                                <span class="material-symbols-outlined text-xs">restart_alt</span>
                                <span>بازنشانی رنگ‌ها</span>
                            </button>
                        </div>

                        <!-- Quick Brand Harmonized Swatches -->
                        <div>
                            <div class="text-[10px] font-bold text-slate-500 mb-1.5">پالت‌های آماده و سازگار با هویت برند:</div>
                            <div class="flex flex-wrap gap-1.5" id="brand-swatches-container">
                                <button type="button" onclick="applyColorSwatch('#001a48', '#fd8100')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #001a48;" title="سرمه‌ای رسمی آسنا"></button>
                                <button type="button" onclick="applyColorSwatch('#059669', '#fd8100')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #059669;" title="سبز کلینیک لوکس"></button>
                                <button type="button" onclick="applyColorSwatch('#0891b2', '#001a48')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #0891b2;" title="فیروزه‌ای شفق قطبی"></button>
                                <button type="button" onclick="applyColorSwatch('#ea580c', '#001a48')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #ea580c;" title="نارنجی پویا"></button>
                                <button type="button" onclick="applyColorSwatch('#7c3aed', '#ea580c')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #7c3aed;" title="بنفش مخمل اشرافی"></button>
                                <button type="button" onclick="applyColorSwatch('#2563eb', '#fd8100')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #2563eb;" title="آبی رویال بیمارستانی"></button>
                                <button type="button" onclick="applyColorSwatch('#e11d48', '#001a48')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #e11d48;" title="سرخ مرجانی بالینی"></button>
                                <button type="button" onclick="applyColorSwatch('#0f172a', '#38bdf8')" class="w-7 h-7 rounded-xl border-2 border-white shadow-xs flex items-center justify-center transition-transform hover:scale-110 active:scale-95 cursor-pointer" style="background-color: #0f172a;" title="مشکی آبسیدین"></button>
                            </div>
                        </div>

                        <!-- Granular Pickers -->
                        <div class="grid grid-cols-2 gap-2.5 pt-1 border-t border-slate-100">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 mb-1">رنگ سازمانی و دکمه‌ها</label>
                                <div class="flex items-center gap-1.5 p-1 rounded-xl border border-slate-200 bg-slate-50">
                                    <input type="color" id="input-primary-color" value="<?= htmlspecialchars($activePrimaryColor) ?>" class="w-7 h-7 rounded-lg border-0 cursor-pointer bg-transparent p-0" onchange="handleColorInputChange('primary', this.value)">
                                    <input type="text" id="input-primary-color-hex" value="<?= htmlspecialchars($activePrimaryColor) ?>" maxlength="7" class="w-full text-[11px] font-mono font-bold text-slate-700 bg-transparent border-0 focus:outline-none" dir="ltr" oninput="handleHexInput('primary', this.value)">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 mb-1">رنگ ثانویه و بج‌های ویژه</label>
                                <div class="flex items-center gap-1.5 p-1 rounded-xl border border-slate-200 bg-slate-50">
                                    <input type="color" id="input-secondary-color" value="<?= htmlspecialchars($activeSecondaryColor) ?>" class="w-7 h-7 rounded-lg border-0 cursor-pointer bg-transparent p-0" onchange="handleColorInputChange('secondary', this.value)">
                                    <input type="text" id="input-secondary-color-hex" value="<?= htmlspecialchars($activeSecondaryColor) ?>" maxlength="7" class="w-full text-[11px] font-mono font-bold text-slate-700 bg-transparent border-0 focus:outline-none" dir="ltr" oninput="handleHexInput('secondary', this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ambient Lighting Mode -->
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-2">اتمسفر و نورپردازی محیطی</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex flex-col items-center p-2.5 rounded-2xl border-2 cursor-pointer transition-all text-center <?= ($themeConfig['ambient_mode'] ?? 'atmospheric_glow') === 'atmospheric_glow' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="ambient_mode" value="atmospheric_glow" <?= ($themeConfig['ambient_mode'] ?? 'atmospheric_glow') === 'atmospheric_glow' ? 'checked' : '' ?> class="hidden" onchange="sendLiveUpdate('ambient_mode', 'atmospheric_glow')">
                                <span class="material-symbols-outlined text-emerald-600 text-lg mb-1">blur_on</span>
                                <span class="text-[11px] font-bold text-slate-800">هاله نوری شعاعی</span>
                            </label>
                            <label class="flex flex-col items-center p-2.5 rounded-2xl border-2 cursor-pointer transition-all text-center <?= ($themeConfig['ambient_mode'] ?? '') === 'clean_minimal' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="ambient_mode" value="clean_minimal" <?= ($themeConfig['ambient_mode'] ?? '') === 'clean_minimal' ? 'checked' : '' ?> class="hidden" onchange="sendLiveUpdate('ambient_mode', 'clean_minimal')">
                                <span class="material-symbols-outlined text-slate-500 text-lg mb-1">light_mode</span>
                                <span class="text-[11px] font-bold text-slate-800">مینیمال خالص</span>
                            </label>
                            <label class="flex flex-col items-center p-2.5 rounded-2xl border-2 cursor-pointer transition-all text-center <?= ($themeConfig['ambient_mode'] ?? '') === 'dark_obsidian' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200' ?>">
                                <input type="radio" name="ambient_mode" value="dark_obsidian" <?= ($themeConfig['ambient_mode'] ?? '') === 'dark_obsidian' ? 'checked' : '' ?> class="hidden" onchange="sendLiveUpdate('ambient_mode', 'dark_obsidian')">
                                <span class="material-symbols-outlined text-slate-800 text-lg mb-1">dark_mode</span>
                                <span class="text-[11px] font-bold text-slate-800">آبسیدین تیره</span>
                            </label>
                        </div>
                    </div>

                    <!-- Symbiotic ASENA Trust Anchor -->
                    <div>
                        <label class="flex items-center justify-between p-3 rounded-2xl border border-slate-200 bg-slate-50/50 cursor-pointer">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-600">verified</span>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">بج رسمی عضویت شبکه سلامت آسنا</div>
                                    <div class="text-[10px] text-slate-400">تضمین اعتبار مراجعین و درگاه شاپرک</div>
                                </div>
                            </div>
                            <input type="checkbox" id="input-trust-anchor-toggle" <?= ($themeConfig['trust_anchor'] ?? 'floating_pill') !== 'none' ? 'checked' : '' ?> class="rounded text-emerald-600" onchange="sendLiveUpdate('trust_anchor_toggle', this.checked)">
                        </label>
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
                        <label class="block text-xs font-bold text-slate-800 mb-1">شناسه و آدرس اختصاصی ساب‌دامین</label>
                        <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-emerald-600">
                            <span class="px-3 text-xs text-slate-400 font-mono bg-slate-50 border-l border-slate-200">.ir</span>
                            <input type="text" id="input-site-slug" value="<?= $slug ?>" oninput="checkSlugLive(this.value)" class="flex-1 text-xs p-2.5 outline-none font-mono text-left font-bold" dir="ltr">
                        </div>
                        <div id="slug-feedback" class="text-[11px] mt-1 font-medium text-emerald-600">✓ این آدرس آزاد و در دسترس است.</div>
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
                        https://<?= $slug ?>.ir
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

    <!-- Spotlight Quick-Editor Modal (Appears when clicking Quick Edit on any block preview) -->
    <div id="spotlight-quick-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 flex flex-col max-h-[85vh]">
            <!-- Modal Header -->
            <div class="p-4 sm:px-6 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-lg" id="spotlight-modal-icon">edit_note</span>
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                            <span>ویرایش سریع:</span>
                            <span id="spotlight-modal-title" class="text-emerald-700">معرفی اصلی</span>
                        </div>
                        <div class="text-[11px] text-slate-500">تغییرات شما فوراً در پیش‌نمایش اعمال می‌شود</div>
                    </div>
                </div>
                <button type="button" onclick="closeSpotlightModal()" class="w-8 h-8 rounded-xl bg-slate-200/80 hover:bg-slate-300 text-slate-700 flex items-center justify-center transition-colors cursor-pointer" title="بستن پنجره">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <!-- Modal Dynamic Body -->
            <div class="p-5 overflow-y-auto space-y-4" id="spotlight-modal-body">
                <!-- Dynamically generated fields per block -->
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:px-6 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                <button type="button" id="spotlight-btn-advanced" onclick="goToAdvancedBlockFromSpotlight()" class="text-xs font-bold text-slate-600 hover:text-emerald-700 flex items-center gap-1 transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-sm">tune</span>
                    <span>تنظیمات پیشرفته در سایدبار</span>
                </button>
                <button type="button" onclick="closeSpotlightModal()" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">check</span>
                    <span>تایید و بستن</span>
                </button>
            </div>
        </div>
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
    initLiveStudioBindings();
});

// Real-time zero-refresh live synchronization engine with preview iframe
function sendLiveUpdate(field, value, extra = null) {
    const iframe = document.getElementById('site-preview-iframe');
    if (!iframe) return;

    // 1. Synchronous direct call if contentWindow is accessible (zero latency, exact frame update)
    try {
        if (iframe.contentWindow && typeof iframe.contentWindow.applyLiveFieldUpdate === 'function') {
            iframe.contentWindow.applyLiveFieldUpdate(field, value, extra);
        }
    } catch (e) {
        // Cross-domain fallback
    }

    // 2. Broadcast via standard HTML5 postMessage
    try {
        if (iframe.contentWindow) {
            iframe.contentWindow.postMessage({
                type: 'STUDIO_LIVE_UPDATE',
                field: field,
                value: value,
                extra: extra
            }, '*');
        }
    } catch (e) {}
}

const paletteDefaults = {
    emerald: { primary: '#059669', secondary: '#fd8100' },
    navy: { primary: '#001a48', secondary: '#fd8100' },
    orange: { primary: '#ea580c', secondary: '#001a48' },
    purple: { primary: '#7c3aed', secondary: '#ea580c' },
    aurora: { primary: '#0891b2', secondary: '#001a48' }
};

function updatePalettePreview(palette) {
    const radios = document.querySelectorAll('input[name="theme_palette"]');
    radios.forEach(r => {
        const parent = r.closest('label');
        if (!parent) return;
        if (r.value === palette) {
            r.checked = true;
            if (palette === 'emerald') parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-emerald-600 bg-emerald-50/50';
            else if (palette === 'navy') parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-slate-900 bg-slate-100';
            else if (palette === 'orange') parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-orange-500 bg-orange-50/50';
            else if (palette === 'purple') parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-purple-600 bg-purple-50/50';
            else if (palette === 'aurora') parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-cyan-600 bg-cyan-50/50';
        } else {
            parent.className = 'flex items-center gap-2 p-3 rounded-2xl border-2 cursor-pointer transition-all border-slate-200';
        }
    });

    const def = paletteDefaults[palette] || paletteDefaults.emerald;
    syncColorInputs(def.primary, def.secondary);

    sendLiveUpdate('theme_palette', palette);
    sendLiveUpdate('custom_colors', { primary: def.primary, secondary: def.secondary });
}

function syncColorInputs(primary, secondary) {
    const pInput = document.getElementById('input-primary-color');
    const pHex = document.getElementById('input-primary-color-hex');
    const sInput = document.getElementById('input-secondary-color');
    const sHex = document.getElementById('input-secondary-color-hex');
    const qPInput = document.getElementById('input-quick-primary-color');
    const qSInput = document.getElementById('input-quick-secondary-color');
    const qPHex = document.getElementById('label-quick-primary-hex');
    const qSHex = document.getElementById('label-quick-secondary-hex');

    if (pInput) pInput.value = primary;
    if (pHex) pHex.value = primary;
    if (qPInput) qPInput.value = primary;
    if (qPHex) qPHex.innerText = primary;

    if (sInput) sInput.value = secondary;
    if (sHex) sHex.value = secondary;
    if (qSInput) qSInput.value = secondary;
    if (qSHex) qSHex.innerText = secondary;
}

function applyColorSwatch(primary, secondary) {
    syncColorInputs(primary, secondary);
    sendLiveUpdate('custom_colors', { primary: primary, secondary: secondary });
}

function handleColorInputChange(type, val) {
    if (type === 'primary') {
        const hexEl = document.getElementById('input-primary-color-hex');
        if (hexEl) hexEl.value = val;
        const qP = document.getElementById('input-quick-primary-color');
        if (qP) qP.value = val;
        const qHex = document.getElementById('label-quick-primary-hex');
        if (qHex) qHex.innerText = val;
    } else {
        const hexEl = document.getElementById('input-secondary-color-hex');
        if (hexEl) hexEl.value = val;
        const qS = document.getElementById('input-quick-secondary-color');
        if (qS) qS.value = val;
        const qHex = document.getElementById('label-quick-secondary-hex');
        if (qHex) qHex.innerText = val;
    }
    const prim = document.getElementById('input-primary-color')?.value || '#059669';
    const sec = document.getElementById('input-secondary-color')?.value || '#fd8100';
    sendLiveUpdate('custom_colors', { primary: prim, secondary: sec });
}

function handleHexInput(type, val) {
    val = val.trim();
    if (!val.startsWith('#')) val = '#' + val;
    if (/^#[a-f0-9]{6}$/i.test(val)) {
        if (type === 'primary') {
            const picker = document.getElementById('input-primary-color');
            if (picker) picker.value = val;
            const qP = document.getElementById('input-quick-primary-color');
            if (qP) qP.value = val;
            const qHex = document.getElementById('label-quick-primary-hex');
            if (qHex) qHex.innerText = val;
        } else {
            const picker = document.getElementById('input-secondary-color');
            if (picker) picker.value = val;
            const qS = document.getElementById('input-quick-secondary-color');
            if (qS) qS.value = val;
            const qHex = document.getElementById('label-quick-secondary-hex');
            if (qHex) qHex.innerText = val;
        }
        const prim = document.getElementById('input-primary-color')?.value || '#059669';
        const sec = document.getElementById('input-secondary-color')?.value || '#fd8100';
        sendLiveUpdate('custom_colors', { primary: prim, secondary: sec });
    }
}

function resetBrandColorsToDefault() {
    const paletteEl = document.querySelector('input[name="theme_palette"]:checked');
    const palette = paletteEl ? paletteEl.value : 'emerald';
    const def = paletteDefaults[palette] || paletteDefaults.emerald;
    
    syncColorInputs(def.primary, def.secondary);

    sendLiveUpdate('theme_palette', palette);
    sendLiveUpdate('custom_colors', { primary: def.primary, secondary: def.secondary });
    if (typeof showToast === 'function') {
        showToast('✓ رنگ‌های وب‌سایت به تنظیمات پیش‌فرض پالت برند بازنشانی شدند.', 'success');
    }
}

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
                    statusEl.innerHTML = 'تصویر با موفقیت در فضای ابری ذخیره شد.';
                }, 3000);
            }
            showToast('✓ تصویر با موفقیت بارگذاری شد.', 'success');
            
            // Real-time live preview update without reloading iframe
            if (targetInputId === 'input-site-logo') {
                sendLiveUpdate('site_logo', data.url);
            } else if (targetInputId === 'input-hero-image' || targetInputId === 'input-site-banner') {
                sendLiveUpdate('hero_image', data.url);
                sendLiveUpdate('banner_image', data.url);
            }
            // Auto save silently in background without reloading iframe
            saveSiteConfig(true);
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
    ['quick', 'blocks', 'design', 'settings'].forEach(t => {
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

function handleStudioQuickSearch(q) {
    const query = (q || '').trim().toLowerCase();
    const clearBtn = document.getElementById('btn-clear-search');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', !query);
    }

    if (!query) {
        document.querySelectorAll('.quick-card').forEach(c => c.classList.remove('hidden'));
        document.querySelectorAll('#tab-panel-blocks > div[id^="section-"]').forEach(s => s.classList.remove('hidden'));
        return;
    }

    let matchedQuickCount = 0;
    document.querySelectorAll('.quick-card').forEach(card => {
        const text = ((card.getAttribute('data-search-keys') || '') + ' ' + card.innerText).toLowerCase();
        const matches = text.includes(query);
        card.classList.toggle('hidden', !matches);
        if (matches) matchedQuickCount++;
    });

    let matchedBlockCount = 0;
    document.querySelectorAll('#tab-panel-blocks > div[id^="section-"]').forEach(sec => {
        const text = sec.innerText.toLowerCase();
        const matches = text.includes(query);
        sec.classList.toggle('hidden', !matches);
        if (matches) {
            matchedBlockCount++;
            const blockId = sec.id.replace('section-', '');
            const content = document.getElementById(`content-${blockId}`);
            if (content && content.classList.contains('hidden')) {
                toggleAccordion(blockId);
            }
        }
    });

    if (matchedQuickCount === 0 && matchedBlockCount > 0) {
        switchSidebarTab('blocks');
    }
}

function clearStudioQuickSearch() {
    const input = document.getElementById('studio-quick-search');
    if (input) {
        input.value = '';
        handleStudioQuickSearch('');
        input.focus();
    }
}

const clinicalTemplates = {
    hero: {
        'input-hero-badge': '🛡️ مرکز تاییدشده شبکه سلامت آتنا • استاندارد طلایی بالینی',
        'input-hero-title': 'مرکز تخصصی درمان، جراحی و مراقبت‌های پیشرفته دامپزشکی',
        'input-hero-subtitle': 'ارائه خدمات فوق‌تخصصی تشخیصی، تصویربرداری دیجیتال، جراحی بافت نرم و ارتوپدی با کادر هیئت علمی و پرونده سلامت ابری آتنا.',
        'input-hero-cta': 'رزرو آنلاین نوبت و معاینه تخصصی'
    },
    cost_calculator: {
        'input-calc-badge': 'تعرفه شفاف و مصوب خدمات بالینی و جراحی',
        'input-calc-heading': 'برآورد آنلاین و شفاف هزینه خدمات بالینی و جراحی',
        'input-calc-subtitle': 'گونه حیوان خانگی و خدمات مدنظر را مشخص فرمایید تا تعرفه دقیق مصوب همراه با ۱۰٪ تخفیف ویژه رزرو اینترنتی محاسبه گردد.',
        'input-calc-discount': '10'
    },
    emergency_bar: {
        'input-emergency-headline': 'پذیرش اورژانس و تروما به صورت ۲۴ ساعته شبانه‌روز',
        'input-emergency-subheadline': 'تیم جراحی و مراقبت ویژه (ICU) آماده پذیرش فوری موارد بحرانی و تصادفات'
    },
    before_after: {
        'input-ba-heading': 'نتایج بالینی و مقایسه درمان‌های تخصصی مرکز',
        'input-ba-subtitle': 'مستندات واقعی از فرآیند بهبود مراجعین با بهره‌گیری از پروتکل‌های درمانی مدرن',
        'input-ba-service-label': 'درمان تخصصی و ترمیم زخم بافت نرم',
        'input-ba-label-before': 'قبل از آغاز دوره درمانی',
        'input-ba-label-after': 'بهبودی کامل پس از ۱۴ روز'
    }
};

function applyClinicalCopyTemplate(blockId) {
    const tpl = clinicalTemplates[blockId];
    if (!tpl) return;

    for (const [targetId, val] of Object.entries(tpl)) {
        const input = document.getElementById(targetId);
        if (input) {
            input.value = val;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        const quickId = targetId.replace('input-', 'input-quick-');
        const quickInput = document.getElementById(quickId);
        if (quickInput) {
            quickInput.value = val;
        }
        const spotId = targetId.replace('input-', 'spotlight-');
        const spotInput = document.getElementById(spotId);
        if (spotInput) {
            spotInput.value = val;
        }
    }
    showToast('✓ متن آماده بالینی استاندارد با موفقیت درج شد.', 'success');
}

let currentSpotlightBlock = null;

const spotlightConfigs = {
    hero: {
        title: 'معرفی اصلی و هیرو',
        icon: 'featured_play_list',
        fields: [
            { id: 'hero-badge', targetId: 'input-hero-badge', field: 'hero_badge', label: 'نشان بالای تیتر (بج)', type: 'text' },
            { id: 'hero-title', targetId: 'input-hero-title', field: 'hero_title', label: 'تیتر اصلی چشمگیر', type: 'text' },
            { id: 'hero-subtitle', targetId: 'input-hero-subtitle', field: 'hero_subtitle', label: 'توضیحات معرفی زیر تیتر', type: 'textarea' },
            { id: 'hero-cta', targetId: 'input-hero-cta', field: 'hero_cta', label: 'متن دکمه نوبت‌دهی / اقدام', type: 'text' }
        ],
        hasTemplate: true
    },
    emergency_bar: {
        title: 'نوار اورژانس شبانه‌روزی',
        icon: 'e911_emergency',
        fields: [
            { id: 'emergency-headline', targetId: 'input-emergency-headline', field: 'emergency_headline', label: 'تیتر پیام فوری', type: 'text' },
            { id: 'emergency-subheadline', targetId: 'input-emergency-subheadline', field: 'emergency_subheadline', label: 'زیرعنوان و شرایط پذیرش', type: 'text' },
            { id: 'emergency-phone', targetId: 'input-emergency-phone', field: 'emergency_phone', label: 'تلفن خط ویژه اورژانس', type: 'text' }
        ],
        hasTemplate: true
    },
    cost_calculator: {
        title: 'محاسبه‌گر هزینه خدمات بالینی',
        icon: 'calculate',
        fields: [
            { id: 'calc-badge', targetId: 'input-calc-badge', field: 'calc_badge', label: 'نشان سربرگ', type: 'text' },
            { id: 'calc-heading', targetId: 'input-calc-heading', field: 'calc_heading', label: 'تیتر بخش محاسبه‌گر', type: 'text' },
            { id: 'calc-subtitle', targetId: 'input-calc-subtitle', field: 'calc_subtitle', label: 'زیرعنوان و توضیحات تخفیف', type: 'textarea' },
            { id: 'calc-discount', targetId: 'input-calc-discount', field: 'calc_discount', label: 'درصد تخفیف آنلاین (%)', type: 'number' }
        ],
        hasTemplate: true
    },
    before_after: {
        title: 'مقایسه قبل و بعد از درمان',
        icon: 'compare',
        fields: [
            { id: 'ba-heading', targetId: 'input-ba-heading', field: 'before_after_heading', label: 'تیتر بخش مقایسه', type: 'text' },
            { id: 'ba-subtitle', targetId: 'input-ba-subtitle', field: 'before_after_subtitle', label: 'توضیحات و زیرعنوان', type: 'textarea' },
            { id: 'ba-service-label', targetId: 'input-ba-service-label', field: 'before_after_service_label', label: 'عنوان خدمت بالینی', type: 'text' },
            { id: 'ba-label-before', targetId: 'input-ba-label-before', field: 'before_after_label_before', label: 'برچسب قبل', type: 'text' },
            { id: 'ba-label-after', targetId: 'input-ba-label-after', field: 'before_after_label_after', label: 'برچسب بعد', type: 'text' }
        ],
        hasTemplate: true
    },
    about: {
        title: 'درباره کلینیک و کادر درمان',
        icon: 'info',
        fields: [
            { id: 'about-heading', targetId: 'input-about-heading', field: 'about_heading', label: 'تیتر درباره ما', type: 'text' },
            { id: 'about-text', targetId: 'input-about-text', field: 'about_text', label: 'متن معرفی کلینیک و سوابق', type: 'textarea' },
            { id: 'about-vet-council', targetId: 'input-about-vet-council', field: 'about_vet_council', label: 'شماره نظام دامپزشکی / مجوز', type: 'text' }
        ]
    },
    duty_hours: {
        title: 'ساعات کاری و شیفت فعال',
        icon: 'schedule',
        fields: [
            { id: 'duty-open-time', targetId: 'input-duty-open-time', field: 'duty_hours', label: 'ساعت شروع کار', type: 'time' },
            { id: 'duty-close-time', targetId: 'input-duty-close-time', field: 'duty_hours', label: 'ساعت پایان کار', type: 'time' }
        ]
    },
    contact: {
        title: 'اطلاعات تماس و نشانی',
        icon: 'call',
        fields: [
            { id: 'contact-phone', targetId: 'input-contact-phone', field: 'contact_phone', label: 'شماره تلفن نوبت‌دهی', type: 'text' },
            { id: 'contact-emergency', targetId: 'input-contact-emergency', field: 'contact_emergency', label: 'تلفن اورژانس', type: 'text' },
            { id: 'contact-address', targetId: 'input-contact-address', field: 'contact_address', label: 'نشانی دقیق پستی', type: 'text' },
            { id: 'contact-hours', targetId: 'input-contact-hours', field: 'contact_hours', label: 'ساعات پذیرش حضوری', type: 'text' }
        ]
    },
    doctors_roster: {
        title: 'کادر پزشکان و متخصصان',
        icon: 'group',
        fields: [
            { id: 'doctors-heading', targetId: 'input-doctors-heading', field: 'doctors_heading', label: 'تیتر کادر درمان', type: 'text' },
            { id: 'doctors-subtitle', targetId: 'input-doctors-subtitle', field: 'doctors_subtitle', label: 'زیرعنوان کادر درمان', type: 'textarea' }
        ]
    },
    bento_facilities: {
        title: 'امکانات و ظرفیت‌های بالینی',
        icon: 'grid_view',
        fields: [
            { id: 'bento-heading', targetId: 'input-bento-heading', field: 'bento_heading', label: 'تیتر بخش امکانات', type: 'text' }
        ]
    },
    booking: {
        title: 'سیستم نوبت‌دهی آنلاین',
        icon: 'calendar_month',
        fields: [
            { id: 'booking-heading', targetId: 'input-booking-heading', field: 'booking_heading', label: 'تیتر سیستم نوبت‌دهی', type: 'text' }
        ]
    },
    storefront: {
        title: 'داروخانه و پت‌شاپ آنلاین',
        icon: 'storefront',
        fields: [
            { id: 'storefront-heading', targetId: 'input-storefront-heading', field: 'storefront_heading', label: 'تیتر بخش فروشگاه', type: 'text' }
        ]
    },
    reviews: {
        title: 'نظرات مراجعین',
        icon: 'rate_review',
        fields: [
            { id: 'reviews-heading', targetId: 'input-reviews-heading', field: 'reviews_heading', label: 'تیتر نظرات مراجعین', type: 'text' }
        ]
    },
    faq: {
        title: 'پرسش‌های متداول',
        icon: 'help',
        fields: [
            { id: 'faq-heading', targetId: 'input-faq-heading', field: 'faq_heading', label: 'تیتر پرسش‌های متداول', type: 'text' }
        ]
    }
};

function openSpotlightModal(blockId) {
    currentSpotlightBlock = blockId;
    const cfg = spotlightConfigs[blockId];
    const modal = document.getElementById('spotlight-quick-modal');
    if (!modal) return;

    if (!cfg) {
        openSidebar('blocks');
        const sec = document.getElementById(`section-${blockId}`);
        if (sec) {
            sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
            toggleAccordion(blockId);
        }
        return;
    }

    document.getElementById('spotlight-modal-title').innerText = cfg.title;
    document.getElementById('spotlight-modal-icon').innerText = cfg.icon || 'edit_note';

    let html = '';
    if (cfg.hasTemplate) {
        html += `
            <div class="p-2.5 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-indigo-900">می‌خواهید متن استاندارد بالینی جایگزین شود؟</span>
                <button type="button" onclick="applyClinicalCopyTemplate('${blockId}')" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer">
                    <span class="material-symbols-outlined text-xs">magic_button</span>
                    <span>درج متن نمونه</span>
                </button>
            </div>
        `;
    }

    cfg.fields.forEach(f => {
        const originalInput = document.getElementById(f.targetId);
        const val = originalInput ? originalInput.value : '';

        html += `<div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 block">${f.label}</label>`;

        if (f.type === 'textarea') {
            html += `<textarea id="spotlight-${f.id}" rows="3" oninput="syncSpotlightField('${f.targetId}', '${f.field}', this.value)" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800 transition-all font-medium">${val}</textarea>`;
        } else {
            html += `<input type="${f.type || 'text'}" id="spotlight-${f.id}" value="${val.replace(/"/g, '&quot;')}" oninput="syncSpotlightField('${f.targetId}', '${f.field}', this.value)" class="w-full px-3 py-2 text-xs bg-slate-50 focus:bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800 transition-all font-bold">`;
        }

        html += `</div>`;
    });

    document.getElementById('spotlight-modal-body').innerHTML = html;
    modal.classList.remove('hidden');
}

function closeSpotlightModal() {
    const modal = document.getElementById('spotlight-quick-modal');
    if (modal) modal.classList.add('hidden');
    currentSpotlightBlock = null;
}

function goToAdvancedBlockFromSpotlight() {
    const bId = currentSpotlightBlock;
    closeSpotlightModal();
    if (bId) {
        openSidebar('blocks');
        const sec = document.getElementById(`section-${bId}`);
        if (sec) {
            const content = document.getElementById(`content-${bId}`);
            if (content && content.classList.contains('hidden')) {
                toggleAccordion(bId);
            }
            sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function syncSpotlightField(targetId, fieldKey, val) {
    const originalInput = document.getElementById(targetId);
    if (originalInput) {
        originalInput.value = val;
    }
    const quickId = targetId.replace('input-', 'input-quick-');
    const quickInput = document.getElementById(quickId);
    if (quickInput) {
        quickInput.value = val;
    }

    if (fieldKey === 'duty_hours') {
        const o = document.getElementById('input-duty-open-time')?.value || '08:30';
        const c = document.getElementById('input-duty-close-time')?.value || '22:30';
        sendLiveUpdate('duty_hours', `${o} الی ${c}`);
    } else {
        sendLiveUpdate(fieldKey, val);
    }
}

function toggleAccordion(id) {
    const content = document.getElementById(`content-${id}`);
    const arrow = document.getElementById(`arrow-${id}`);
    if (!content) return;
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
        sendLiveUpdate('scroll_to_block', id);
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

// Click-to-edit and WYSIWYG live bus listener from iframe:
window.addEventListener('message', function(event) {
    if (!event.data) return;

    // Existing BLOCK_CLICKED event: automatically opens sidebar on click and highlights block!
    if (event.data.type === 'BLOCK_CLICKED') {
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

    // Direct WYSIWYG preview inline typing: FIELD_UPDATED_FROM_PREVIEW
    if (event.data.type === 'FIELD_UPDATED_FROM_PREVIEW') {
        const field = event.data.field;
        const val = event.data.value;
        
        const fieldToInputMap = {
            'site_title': 'input-site-title',
            'site_tagline': 'input-site-tagline',
            'contact_phone': 'input-contact-phone',
            'contact_address': 'input-contact-address',
            'contact_hours': 'input-contact-hours',
            'contact_emergency': 'input-contact-emergency',
            'emergency_headline': 'input-emergency-headline',
            'emergency_subheadline': 'input-emergency-subheadline',
            'emergency_phone': 'input-emergency-phone',
            'hero_badge': 'input-hero-badge',
            'hero_title': 'input-hero-title',
            'hero_subtitle': 'input-hero-subtitle',
            'hero_cta': 'input-hero-cta',
            'calc_badge': 'input-calc-badge',
            'calc_heading': 'input-calc-heading',
            'calc_subtitle': 'input-calc-subtitle',
            'before_after_heading': 'input-ba-heading',
            'before_after_subtitle': 'input-ba-subtitle',
            'before_after_service_label': 'input-ba-service-label',
            'before_after_label_before': 'input-ba-label-before',
            'before_after_label_after': 'input-ba-label-after',
            'about_heading': 'input-about-heading',
            'about_text': 'input-about-text',
            'about_vet_council': 'input-about-vet-council',
            'doctors_heading': 'input-doctors-heading',
            'doctors_subtitle': 'input-doctors-subtitle',
            'bento_heading': 'input-bento-heading',
            'booking_heading': 'input-booking-heading',
            'storefront_heading': 'input-storefront-heading',
            'reviews_heading': 'input-reviews-heading',
            'faq_heading': 'input-faq-heading'
        };

        const targetId = fieldToInputMap[field];
        if (targetId) {
            const input = document.getElementById(targetId);
            if (input) input.value = val;
            const quickInput = document.getElementById(targetId.replace('input-', 'input-quick-'));
            if (quickInput) quickInput.value = val;
        }

        const saveBtn = document.getElementById('btn-save-site');
        if (saveBtn) {
            saveBtn.classList.add('ring-2', 'ring-amber-400');
        }
    }

    // Open quick-action modal from preview toolbar: OPEN_QUICK_EDIT_MODAL
    if (event.data.type === 'OPEN_QUICK_EDIT_MODAL') {
        openSpotlightModal(event.data.blockId);
    }

    // Toggle block visibility from preview toolbar: TOGGLE_BLOCK_FROM_PREVIEW
    if (event.data.type === 'TOGGLE_BLOCK_FROM_PREVIEW') {
        const blockId = event.data.blockId;
        const toggleMap = {
            'emergency_bar': 'input-emergency-enabled',
            'duty_hours': 'input-duty-enabled',
            'before_after': 'input-ba-enabled',
            'bento_facilities': 'input-bento-enabled',
            'cost_calculator': 'input-calc-enabled',
            'doctors_roster': 'input-doctors-enabled',
            'booking': 'input-booking-enabled',
            'storefront': 'input-storefront-enabled',
            'reviews': 'input-reviews-enabled',
            'faq': 'input-faq-enabled'
        };
        const toggleId = toggleMap[blockId];
        if (toggleId) {
            const checkbox = document.getElementById(toggleId);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                const quickCheckbox = document.getElementById(toggleId.replace('input-', 'input-quick-'));
                if (quickCheckbox) quickCheckbox.checked = checkbox.checked;
                showToast(`✓ وضعیت نمایش بخش «${blockId}» به‌روزرسانی شد.`, 'success');
            }
        }
    }

    // Reorder block from preview toolbar: MOVE_BLOCK_FROM_PREVIEW
    if (event.data.type === 'MOVE_BLOCK_FROM_PREVIEW') {
        const blockId = event.data.blockId;
        const direction = event.data.direction;
        moveStudioBlock(`section-${blockId}`, direction);
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
                fb.innerText = '✓ این آدرس آزاد و در دسترس است.';
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

    // Extract blocks order from the current DOM order of sections
    const blocksOrder = Array.from(document.querySelectorAll('#tab-panel-blocks > div[id^="section-"]'))
        .map(el => el.id.replace('section-', ''))
        .filter(Boolean);

    // Construct Layout Payload
    const layout = {
        blocks_order: blocksOrder,
        emergency_bar: {
            enabled: document.getElementById('input-emergency-enabled')?.checked || false,
            headline: document.getElementById('input-emergency-headline')?.value || '',
            subheadline: document.getElementById('input-emergency-subheadline')?.value || '',
            phone: document.getElementById('input-emergency-phone')?.value || ''
        },
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
        duty_hours: {
            enabled: document.getElementById('input-duty-enabled')?.checked || false,
            open_time: document.getElementById('input-duty-open-time')?.value || '08:30',
            close_time: document.getElementById('input-duty-close-time')?.value || '22:30',
            emergency_open_24h: document.getElementById('input-duty-24h')?.checked || false
        },
        before_after: {
            enabled: document.getElementById('input-ba-enabled')?.checked || false,
            heading: document.getElementById('input-ba-heading')?.value || 'مقایسه نتایج قبل و بعد از مراقبت تخصصی',
            subtitle: document.getElementById('input-ba-subtitle')?.value || '',
            service_label: document.getElementById('input-ba-service-label')?.value || '',
            label_before: document.getElementById('input-ba-label-before')?.value || 'قبل از درمان',
            label_after: document.getElementById('input-ba-label-after')?.value || 'پس از درمان',
            image_before: document.getElementById('input-ba-image-before')?.value || '',
            image_after: document.getElementById('input-ba-image-after')?.value || ''
        },
        stats_strip: {
            enabled: true
        },
        bento_facilities: {
            enabled: document.getElementById('input-bento-enabled')?.checked || false,
            heading: document.getElementById('input-bento-heading')?.value || 'تجهیزات مدرن و ظرفیت‌های بالینی مرکز'
        },
        cost_calculator: {
            enabled: document.getElementById('input-calc-enabled')?.checked || false,
            badge: document.getElementById('input-calc-badge')?.value || 'تعرفه شفاف خدمات درمانی و جراحی',
            heading: document.getElementById('input-calc-heading')?.value || 'برآورد آنلاین و شفاف تعرفه خدمات و جراحی‌های تخصصی',
            subtitle: document.getElementById('input-calc-subtitle')?.value || 'گونه حیوان خانگی و خدمات تشخیصی، بالینی یا جراحی مدنظر را انتخاب فرمایید تا تعرفه مصوب رسمی همراه با ۱۰٪ تخفیف رزرو آنلاین برآورد گردد.',
            discount_percent: parseInt(document.getElementById('input-calc-discount')?.value || '10')
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

        reviews: {
            enabled: document.getElementById('input-reviews-enabled')?.checked || false,
            heading: document.getElementById('input-reviews-heading')?.value || 'نظرات و بازخورد مراجعین تاییدشده'
        },
        faq: {
            enabled: document.getElementById('input-faq-enabled')?.checked || false,
            heading: document.getElementById('input-faq-heading')?.value || 'پرسش‌های متداول و راهنمای مراجعین'
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

    // Attach blocks wrapper for complete backwards and forwards compatibility
    layout.blocks = Object.assign({}, layout);

    const paletteEl = document.querySelector('input[name="theme_palette"]:checked');
    const selectedPalette = paletteEl ? paletteEl.value : 'emerald';
    const primaryColor = document.getElementById('input-primary-color')?.value || '#059669';
    const secondaryColor = document.getElementById('input-secondary-color')?.value || '#fd8100';

    layout.theme = Object.assign(layout.theme || {}, {
        palette: selectedPalette,
        primary_color: primaryColor,
        secondary_color: secondaryColor,
        ambient_mode: document.querySelector('input[name="ambient_mode"]:checked')?.value || 'atmospheric_glow',
        trust_anchor: document.getElementById('input-trust-anchor-toggle')?.checked ? 'floating_pill' : 'none'
    });

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('tenant_type', tenantType);
    fd.append('tenant_id', tenantId);
    fd.append('site_tier', document.getElementById('input-site-tier')?.value || 'enterprise');
    fd.append('site_title', document.getElementById('input-site-title')?.value || '');
    fd.append('site_tagline', document.getElementById('input-site-tagline')?.value || '');
    fd.append('slug', document.getElementById('input-site-slug')?.value || '');
    fd.append('theme_palette', selectedPalette);
    fd.append('primary_color', primaryColor);
    fd.append('secondary_color', secondaryColor);
    fd.append('logo_url', document.getElementById('input-site-logo')?.value || '');
    fd.append('banner_url', document.getElementById('input-site-banner')?.value || '');
    fd.append('meta_description', document.getElementById('input-site-meta')?.value || '');
    fd.append('is_published', document.getElementById('input-is-published')?.checked ? '1' : '0');
    fd.append('layout', JSON.stringify(layout));

    try {
        const res = await fetch('../actions/site_builder_action.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            // Keep preview intact; only reload if the slug itself was changed
            const iframe = document.getElementById('site-preview-iframe');
            if (iframe) {
                const currentSrc = iframe.getAttribute('src') || '';
                const newSlugParam = `slug=${encodeURIComponent(data.slug)}`;
                if (!currentSrc.includes(newSlugParam)) {
                    iframe.src = `../site.php?slug=${encodeURIComponent(data.slug)}&preview=1`;
                }
            }
            if (!silent) {
                showToast('✓ وب‌سایت اختصاصی شما با موفقیت ذخیره و منتشر شد.', 'success');
            }
        } else {
            if (!silent) {
                showToast('✕ خطا: ' + (data.message || 'مشکلی رخ داد'), 'error');
            }
        }
    } catch (e) {
        if (!silent) {
            showToast('✕ ارتباط با سرور برقرار نشد.', 'error');
        }
    } finally {
        if (!silent && btn && saveIcon && saveText) {
            btn.disabled = false;
            saveIcon.innerText = 'cloud_upload';
            saveIcon.classList.remove('animate-spin');
            saveText.innerText = 'ذخیره و انتشار';
        }
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
            const updateCheckboxAndLive = (inputId, blockKey) => {
                const el = document.getElementById(inputId);
                if (el && b[blockKey] !== undefined) {
                    el.checked = !!b[blockKey].enabled;
                    sendLiveUpdate('block_toggle', el.checked, blockKey);
                }
            };

            updateCheckboxAndLive('input-emergency-enabled', 'emergency_bar');
            updateCheckboxAndLive('input-duty-enabled', 'duty_hours');
            updateCheckboxAndLive('input-bento-enabled', 'bento_facilities');
            updateCheckboxAndLive('input-calc-enabled', 'cost_calculator');
            updateCheckboxAndLive('input-doctors-enabled', 'doctors_roster');
            updateCheckboxAndLive('input-booking-enabled', 'booking');
            updateCheckboxAndLive('input-storefront-enabled', 'storefront');
            updateCheckboxAndLive('input-reviews-enabled', 'reviews');
            updateCheckboxAndLive('input-faq-enabled', 'faq');

            if (document.getElementById('input-storefront-limit') && b.storefront !== undefined) {
                const lim = b.storefront.item_limit || 6;
                document.getElementById('input-storefront-limit').value = lim;
                sendLiveUpdate('storefront_limit', lim);
            }

            showToast(`✓ قالب نسخه «${tier}» اعمال شد.`, 'success');
            // Silently persist in background without reloading iframe
            saveSiteConfig(true);
        } else {
            showToast('✕ خطا در دریافت قالب نسخه', 'error');
        }
    } catch (e) {
        showToast('✕ خطا در ارتباط با سرور', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function getStudioBlocksOrder() {
    return Array.from(document.querySelectorAll('#tab-panel-blocks > div[id^="section-"]'))
        .map(el => el.id.replace('section-', ''))
        .filter(Boolean);
}

function moveStudioBlock(sectionId, direction) {
    const el = document.getElementById(sectionId);
    if (!el) return;
    if (direction === 'up' && el.previousElementSibling) {
        el.parentNode.insertBefore(el, el.previousElementSibling);
    } else if (direction === 'down' && el.nextElementSibling) {
        el.parentNode.insertBefore(el.nextElementSibling, el);
    }
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    el.classList.add('ring-2', 'ring-emerald-500');
    setTimeout(() => el.classList.remove('ring-2', 'ring-emerald-500'), 1000);

    // Live update block ordering in preview iframe immediately without refresh
    const order = getStudioBlocksOrder();
    sendLiveUpdate('reorder_blocks', order);
    showToast('✓ ترتیب بلوک در پیش‌نمایش زنده اعمال شد.', 'success');
}

function initLiveStudioBindings() {
    const bindings = [
        // Basic Info
        { id: 'input-site-title', field: 'site_title', event: 'input' },
        { id: 'input-site-tagline', field: 'site_tagline', event: 'input' },
        { id: 'input-site-logo', field: 'site_logo', event: 'input' },
        { id: 'input-site-banner', field: 'hero_image', event: 'input' },
        { id: 'input-site-meta', field: 'meta_description', event: 'input' },

        // Emergency Bar
        { id: 'input-emergency-enabled', field: 'block_toggle', extra: 'emergency_bar', event: 'change', isCheckbox: true },
        { id: 'input-emergency-headline', field: 'emergency_headline', event: 'input' },
        { id: 'input-emergency-subheadline', field: 'emergency_subheadline', event: 'input' },
        { id: 'input-emergency-phone', field: 'emergency_phone', event: 'input' },

        // Hero
        { id: 'input-hero-badge', field: 'hero_badge', event: 'input' },
        { id: 'input-hero-title', field: 'hero_title', event: 'input' },
        { id: 'input-hero-subtitle', field: 'hero_subtitle', event: 'input' },
        { id: 'input-hero-cta', field: 'hero_cta', event: 'input' },
        { id: 'input-hero-image', field: 'hero_image', event: 'input' },

        // Duty Hours
        { id: 'input-duty-enabled', field: 'block_toggle', extra: 'duty_hours', event: 'change', isCheckbox: true },
        { 
            id: 'input-duty-open-time', 
            event: 'input', 
            custom: () => {
                const o = document.getElementById('input-duty-open-time')?.value || '08:30';
                const c = document.getElementById('input-duty-close-time')?.value || '22:30';
                sendLiveUpdate('duty_hours', `${o} الی ${c}`);
            }
        },
        { 
            id: 'input-duty-close-time', 
            event: 'input', 
            custom: () => {
                const o = document.getElementById('input-duty-open-time')?.value || '08:30';
                const c = document.getElementById('input-duty-close-time')?.value || '22:30';
                sendLiveUpdate('duty_hours', `${o} الی ${c}`);
            }
        },

        // Before/After Block
        { id: 'input-ba-enabled', field: 'block_toggle', extra: 'before_after', event: 'change', isCheckbox: true },
        { id: 'input-ba-heading', field: 'before_after_heading', event: 'input' },
        { id: 'input-ba-subtitle', field: 'before_after_subtitle', event: 'input' },
        { id: 'input-ba-service-label', field: 'before_after_service_label', event: 'input' },
        { id: 'input-ba-label-before', field: 'before_after_label_before', event: 'input' },
        { id: 'input-ba-label-after', field: 'before_after_label_after', event: 'input' },
        { id: 'input-ba-image-before', field: 'before_after_image_before', event: 'input' },
        { id: 'input-ba-image-after', field: 'before_after_image_after', event: 'input' },

        // Bento Facilities
        { id: 'input-bento-enabled', field: 'block_toggle', extra: 'bento_facilities', event: 'change', isCheckbox: true },
        { id: 'input-bento-heading', field: 'bento_heading', event: 'input' },



        // About Block
        { id: 'input-about-heading', field: 'about_heading', event: 'input' },
        { id: 'input-about-text', field: 'about_text', event: 'input' },
        { id: 'input-about-vet-council', field: 'about_vet_council', event: 'input' },

        // Cost Calculator
        { id: 'input-calc-enabled', field: 'block_toggle', extra: 'cost_calculator', event: 'change', isCheckbox: true },
        { id: 'input-calc-badge', field: 'calc_badge', event: 'input' },
        { id: 'input-calc-heading', field: 'calc_heading', event: 'input' },
        { id: 'input-calc-subtitle', field: 'calc_subtitle', event: 'input' },
        { id: 'input-calc-discount', field: 'calc_discount', event: 'input' },

        // Booking
        { id: 'input-booking-enabled', field: 'block_toggle', extra: 'booking', event: 'change', isCheckbox: true },
        { id: 'input-booking-heading', field: 'booking_heading', event: 'input' },

        // Storefront
        { id: 'input-storefront-enabled', field: 'block_toggle', extra: 'storefront', event: 'change', isCheckbox: true },
        { id: 'input-storefront-heading', field: 'storefront_heading', event: 'input' },
        { id: 'input-storefront-limit', field: 'storefront_limit', event: 'change' },

        // Doctors
        { id: 'input-doctors-enabled', field: 'block_toggle', extra: 'doctors_roster', event: 'change', isCheckbox: true },
        { id: 'input-doctors-heading', field: 'doctors_heading', event: 'input' },
        { id: 'input-doctors-subtitle', field: 'doctors_subtitle', event: 'input' },



        // Reviews
        { id: 'input-reviews-enabled', field: 'block_toggle', extra: 'reviews', event: 'change', isCheckbox: true },
        { id: 'input-reviews-heading', field: 'reviews_heading', event: 'input' },

        // FAQ
        { id: 'input-faq-enabled', field: 'block_toggle', extra: 'faq', event: 'change', isCheckbox: true },
        { id: 'input-faq-heading', field: 'faq_heading', event: 'input' },

        // Contact Block
        { id: 'input-contact-address', field: 'contact_address', event: 'input' },
        { id: 'input-contact-hours', field: 'contact_hours', event: 'input' },
        { id: 'input-contact-phone', field: 'contact_phone', event: 'input' },
        { id: 'input-contact-emergency', field: 'contact_emergency', event: 'input' }
    ];

    bindings.forEach(b => {
        const el = document.getElementById(b.id);
        if (!el) return;

        const handler = () => {
            if (typeof b.custom === 'function') {
                b.custom();
                return;
            }
            const val = b.isCheckbox ? el.checked : el.value;
            sendLiveUpdate(b.field, val, b.extra || null);
        };

        el.addEventListener(b.event, handler);
        if (b.event === 'input') {
            el.addEventListener('change', handler);
        }
    });

    // Bidirectional Quick Edit Syncing
    const quickSyncPairs = [
        { quick: 'input-quick-site-title', main: 'input-site-title', field: 'site_title' },
        { quick: 'input-quick-site-tagline', main: 'input-site-tagline', field: 'site_tagline' },
        { quick: 'input-quick-contact-phone', main: 'input-contact-phone', field: 'contact_phone' },
        { quick: 'input-quick-emergency-phone', main: 'input-emergency-phone', field: 'emergency_phone' },
        { quick: 'input-quick-contact-address', main: 'input-contact-address', field: 'contact_address' },
        { quick: 'input-quick-hero-badge', main: 'input-hero-badge', field: 'hero_badge' },
        { quick: 'input-quick-hero-title', main: 'input-hero-title', field: 'hero_title' },
        { quick: 'input-quick-hero-subtitle', main: 'input-hero-subtitle', field: 'hero_subtitle' },
        { quick: 'input-quick-hero-cta', main: 'input-hero-cta', field: 'hero_cta' },
        { quick: 'input-quick-calc-badge', main: 'input-calc-badge', field: 'calc_badge' },
        { quick: 'input-quick-calc-heading', main: 'input-calc-heading', field: 'calc_heading' },
        { quick: 'input-quick-calc-subtitle', main: 'input-calc-subtitle', field: 'calc_subtitle' },
        { quick: 'input-quick-calc-discount', main: 'input-calc-discount', field: 'calc_discount' },
        { quick: 'input-quick-emergency-headline', main: 'input-emergency-headline', field: 'emergency_headline' },
        { quick: 'input-quick-emergency-subheadline', main: 'input-emergency-subheadline', field: 'emergency_subheadline' },
        { quick: 'input-quick-duty-open-time', main: 'input-duty-open-time', custom: () => {
            const o = document.getElementById('input-quick-duty-open-time')?.value || '08:30';
            const c = document.getElementById('input-quick-duty-close-time')?.value || '22:30';
            const mO = document.getElementById('input-duty-open-time');
            if (mO) mO.value = o;
            sendLiveUpdate('duty_hours', `${o} الی ${c}`);
        }},
        { quick: 'input-quick-duty-close-time', main: 'input-duty-close-time', custom: () => {
            const o = document.getElementById('input-quick-duty-open-time')?.value || '08:30';
            const c = document.getElementById('input-quick-duty-close-time')?.value || '22:30';
            const mC = document.getElementById('input-duty-close-time');
            if (mC) mC.value = c;
            sendLiveUpdate('duty_hours', `${o} الی ${c}`);
        }},
        { quick: 'input-quick-calc-enabled', main: 'input-calc-enabled', isCheckbox: true, field: 'block_toggle', extra: 'cost_calculator' },
        { quick: 'input-quick-duty-enabled', main: 'input-duty-enabled', isCheckbox: true, field: 'block_toggle', extra: 'duty_hours' },
        { quick: 'input-quick-emergency-enabled', main: 'input-emergency-enabled', isCheckbox: true, field: 'block_toggle', extra: 'emergency_bar' }
    ];

    quickSyncPairs.forEach(p => {
        const qEl = document.getElementById(p.quick);
        const mEl = document.getElementById(p.main);

        if (qEl && mEl) {
            const onQuickChange = () => {
                if (p.isCheckbox) {
                    mEl.checked = qEl.checked;
                } else {
                    mEl.value = qEl.value;
                }
                if (typeof p.custom === 'function') {
                    p.custom();
                } else {
                    sendLiveUpdate(p.field, p.isCheckbox ? qEl.checked : qEl.value, p.extra || null);
                }
            };
            qEl.addEventListener(p.isCheckbox ? 'change' : 'input', onQuickChange);

            const onMainChange = () => {
                if (p.isCheckbox) {
                    qEl.checked = mEl.checked;
                } else {
                    qEl.value = mEl.value;
                }
            };
            mEl.addEventListener(p.isCheckbox ? 'change' : 'input', onMainChange);
        }
    });

    // Theme Palette radio buttons
    document.querySelectorAll('input[name="theme_palette"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updatePalettePreview(this.value);
            }
        });
    });
}

// Immediate invocation in case DOM is already ready
initLiveStudioBindings();

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
