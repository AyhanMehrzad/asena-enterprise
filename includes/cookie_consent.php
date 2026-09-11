<?php
/**
 * includes/cookie_consent.php — Cache & Cookie Storage Allowance Banner & Preferences Modal
 * Bulletproof UX: Prominent Close (X) button, high-contrast action buttons, and responsive bottom clearance
 */
?>
<style>
/* Guaranteed styling & positioning for Cookie Banner (independent of external CSS load order) */
#asena-cookie-banner {
    position: fixed !important;
    z-index: 100000 !important;
    box-shadow: 0 16px 48px -8px rgba(0, 26, 72, 0.28), 0 0 0 1px rgba(0, 26, 72, 0.08) !important;
    box-sizing: border-box !important;
    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
/* Desktop (> 1023px): bottom-left floating card */
@media (min-width: 1024px) {
    #asena-cookie-banner {
        bottom: 24px !important;
        left: 24px !important;
        right: auto !important;
        width: 390px !important;
        max-width: calc(100vw - 48px) !important;
    }
}
/* Mobile & Tablet (<= 1023px): comfortably floats ABOVE the 64px mobile bottom nav */
@media (max-width: 1023px) {
    #asena-cookie-banner {
        bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important;
        left: 12px !important;
        right: 12px !important;
        max-width: 440px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: auto !important;
    }
}
</style>

<!-- Cache & Cookie Consent Banner -->
<div id="asena-cookie-banner" 
     class="bg-white/98 dark:bg-[#001a30]/98 backdrop-blur-2xl border border-slate-200/90 dark:border-slate-700/80 rounded-2xl p-4 sm:p-5 rtl text-right select-none"
     style="display: none; opacity: 0; transform: translateY(20px);"
     role="dialog"
     aria-labelledby="cookie-banner-title"
     aria-describedby="cookie-banner-desc">

    <!-- Top Header Row: Icon + Title + Close (X) Button -->
    <div class="flex items-start justify-between gap-3 mb-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">cookie</span>
            </div>
            <h4 id="cookie-banner-title" class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                ذخیره‌سازی کش و کوکی در آسنا
            </h4>
        </div>
        <!-- Close (X) Dismiss Button -->
        <button type="button" 
                onclick="dismissCookieBanner(event)" 
                class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all cursor-pointer flex-shrink-0 -mt-1 -ml-1 border border-transparent hover:border-rose-200 dark:hover:border-rose-800"
                title="بستن و عدم نمایش"
                aria-label="بستن">
            <span class="material-symbols-outlined text-[19px]">close</span>
        </button>
    </div>

    <!-- Description Body -->
    <p id="cookie-banner-desc" class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-3.5 pr-0.5">
        ما برای بهبود تجربه کاربری، افزایش سرعت بارگذاری صفحات و حفظ سشن سبد خرید شما از حافظه موقت (Cache) و کوکی‌ها استفاده می‌کنیم.
    </p>

    <!-- Action Buttons Row -->
    <div class="flex items-center gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
        <!-- 1. Primary Accept All Button -->
        <button type="button" 
                onclick="acceptAllCookies(event)" 
                class="flex-1 py-2.5 px-3 bg-gradient-to-r from-primary via-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-xs font-bold transition-all active:scale-[0.98] shadow-md shadow-primary/20 flex items-center justify-center gap-1 cursor-pointer">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            <span>موافقم و تایید</span>
        </button>

        <!-- 2. Essential Only (GDPR Instant Reject) -->
        <button type="button" 
                onclick="acceptEssentialOnly(event)" 
                class="py-2.5 px-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl text-xs font-bold transition-all active:scale-[0.98] border border-slate-200 dark:border-slate-700 cursor-pointer whitespace-nowrap">
            فقط ضروری
        </button>

        <!-- 3. Settings Modal Trigger -->
        <button type="button" 
                onclick="openCookieSettings(event)" 
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-primary dark:hover:text-blue-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700 cursor-pointer flex-shrink-0"
                title="تنظیمات دقیق">
            <span class="material-symbols-outlined text-[18px]">tune</span>
        </button>
    </div>
</div>

<!-- Cookie & Cache Settings Modal -->
<div id="asena-cookie-modal" class="fixed inset-0 z-[100005] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 rtl text-right select-none">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden border border-slate-200 dark:border-slate-800 animate-fade-in">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/70 dark:bg-slate-800/50">
            <h3 class="font-bold text-primary dark:text-blue-400 flex items-center gap-2 text-sm sm:text-base">
                <span class="material-symbols-outlined text-secondary-container">tune</span>
                تنظیمات پیشرفته حافظه موقت و کوکی‌ها
            </h3>
            <button type="button" onclick="closeCookieSettings(event)" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[19px]">close</span>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-4 max-h-[70vh]">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                می‌توانید سطح ذخیره‌سازی داده‌های موقت، کوکی‌ها و لاگ‌ها را در مرورگر خود مدیریت نمایید:
            </p>

            <!-- Option 1: Essential (Always active) -->
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        کوکی‌ها و کش ضروری (Essential)
                    </h5>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        جهت ورود به حساب، امنیت توکن‌های ضدجعل CSRF و حفظ سبد خرید. (غیرقابل غیرفعال‌سازی)
                    </p>
                </div>
                <input type="checkbox" checked disabled class="rounded border-slate-300 text-primary w-4 h-4 cursor-not-allowed opacity-70 mt-1">
            </div>

            <!-- Option 2: Performance & Static Cache -->
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        حافظه موقت و کش سریع (Performance Cache)
                    </h5>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        ذخیره استایل‌ها، تصاویر و داروها جهت بارگذاری آنی صفحات و مصرف کمتر اینترنت شما.
                    </p>
                </div>
                <input type="checkbox" id="cookie-opt-performance" checked class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer mt-1">
            </div>

            <!-- Option 3: User Preferences -->
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        شخصی‌سازی و ترجیحات (Preferences)
                    </h5>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        به خاطر سپردن فیلترهای فروشگاه، حالت دارک‌مود و آخرین انتخاب‌های کاربر.
                    </p>
                </div>
                <input type="checkbox" id="cookie-opt-preferences" checked class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer mt-1">
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex items-center gap-3">
            <button type="button" onclick="saveCustomCookieSettings(event)" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition-all cursor-pointer">
                ذخیره تنظیمات انتخابی
            </button>
            <button type="button" onclick="acceptAllCookies(event)" class="py-2.5 px-4 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-all cursor-pointer">
                پذیرش همه
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    function initCookieBanner() {
        try {
            if (localStorage.getItem('asena_cookie_consent')) {
                return; // User already made a choice or dismissed
            }
        } catch (e) {}

        const banner = document.getElementById('asena-cookie-banner');
        if (!banner) return;

        banner.style.display = 'block';
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(20px)';

        // Animate in smoothly
        setTimeout(() => {
            banner.style.opacity = '1';
            banner.style.transform = 'translateY(0)';
        }, 120);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }

    // Dismiss on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCookieSettings();
            dismissCookieBanner();
        }
    });
})();

function dismissCookieBanner(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    try {
        localStorage.setItem('asena_cookie_consent', 'dismissed');
        localStorage.setItem('asena_cache_allowed', 'true');
    } catch (err) {}
    hideCookieBanner();
    closeCookieSettings();
    return false;
}

function acceptAllCookies(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    try {
        localStorage.setItem('asena_cookie_consent', 'all');
        localStorage.setItem('asena_cache_allowed', 'true');
    } catch (err) {}
    hideCookieBanner();
    closeCookieSettings();
    return false;
}

function acceptEssentialOnly(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    try {
        localStorage.setItem('asena_cookie_consent', 'essential');
        localStorage.setItem('asena_cache_allowed', 'false');
    } catch (err) {}
    hideCookieBanner();
    closeCookieSettings();
    return false;
}

function saveCustomCookieSettings(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const perf = document.getElementById('cookie-opt-performance')?.checked ?? true;
    const pref = document.getElementById('cookie-opt-preferences')?.checked ?? true;
    try {
        localStorage.setItem('asena_cookie_consent', JSON.stringify({ performance: perf, preferences: pref }));
        localStorage.setItem('asena_cache_allowed', perf ? 'true' : 'false');
    } catch (err) {}
    hideCookieBanner();
    closeCookieSettings();
    return false;
}

function hideCookieBanner() {
    const banner = document.getElementById('asena-cookie-banner');
    if (banner) {
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(20px)';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
}

function openCookieSettings(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const modal = document.getElementById('asena-cookie-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeCookieSettings(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const modal = document.getElementById('asena-cookie-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}
</script>
