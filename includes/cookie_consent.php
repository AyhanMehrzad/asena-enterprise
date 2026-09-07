<?php
/**
 * includes/cookie_consent.php — Cache & Cookie Storage Allowance Banner & Preferences Modal
 */
?>
<!-- Cache & Cookie Consent Banner -->
<div id="asena-cookie-banner" 
     class="fixed bottom-20 md:bottom-6 left-4 md:left-6 right-4 md:right-auto md:max-w-sm z-[99999] bg-white/95 dark:bg-[#001f31]/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-2xl rtl text-right"
     style="display: none;">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 mt-0.5">
            <span class="material-symbols-outlined text-2xl text-primary">cookie</span>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-xs md:text-sm text-primary dark:text-white mb-1">ذخیره‌سازی کش و کوکی در آسنا</h4>
            <p class="text-[11px] text-slate-600 dark:text-gray-300 leading-relaxed">
                ما برای بهبود تجربه کاربری، افزایش سرعت بارگذاری صفحات و حفظ سشن سبد خرید شما از حافظه موقت (Cache) و کوکی‌ها استفاده می‌کنیم.
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
        <button type="button" 
                onclick="acceptAllCookies(event)" 
                class="flex-1 py-2 px-3 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition-all active:scale-[0.98] shadow-md shadow-primary/10 cursor-pointer">
            موافقم و ذخیره
        </button>
        <button type="button" 
                onclick="openCookieSettings(event)" 
                class="py-2 px-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-primary rounded-xl text-xs font-bold transition-all border border-slate-200 dark:border-slate-700 cursor-pointer">
            تنظیمات
        </button>
    </div>
</div>

<!-- Cookie & Cache Settings Modal -->
<div id="asena-cookie-modal" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 rtl text-right">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden border border-outline-variant/30 animate-fade-in">
        <div class="px-6 py-4 border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-bold text-primary flex items-center gap-2 text-base">
                <span class="material-symbols-outlined text-secondary-container">tune</span>
                تنظیمات حافظه موقت و کوکی‌ها
            </h3>
            <button type="button" onclick="closeCookieSettings(event)" class="text-on-surface-variant hover:text-error transition-colors cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-4 max-h-[70vh]">
            <p class="text-xs text-on-surface-variant leading-relaxed">
                می‌توانید نحوه ذخیره‌سازی داده‌های موقت و کوکی‌ها را در مرورگر خود مدیریت نمایید:
            </p>

            <!-- Option 1: Essential (Always active) -->
            <div class="p-3.5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-primary flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        کوکی‌ها و کش ضروری (Essential)
                    </h5>
                    <p class="text-[11px] text-on-surface-variant mt-1 leading-normal">
                        جهت ورود به حساب، امنیت توکن‌های CSRF و حفظ سبد خرید. (غیرقابل غیرفعال‌سازی)
                    </p>
                </div>
                <input type="checkbox" checked disabled class="rounded border-outline-variant text-primary w-4 h-4 cursor-not-allowed opacity-70 mt-1">
            </div>

            <!-- Option 2: Performance & Static Cache -->
            <div class="p-3.5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-primary flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        حافظه موقت و کش سریع (Performance Cache)
                    </h5>
                    <p class="text-[11px] text-on-surface-variant mt-1 leading-normal">
                        ذخیره استایل‌ها، تصاویر و داروها جهت بارگذاری آنی صفحات و مصرف کمتر اینترنت.
                    </p>
                </div>
                <input type="checkbox" id="cookie-opt-performance" checked class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer mt-1">
            </div>

            <!-- Option 3: User Preferences -->
            <div class="p-3.5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest flex items-start justify-between gap-3">
                <div>
                    <h5 class="font-bold text-xs text-primary flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        شخصی‌سازی و ترجیحات (Preferences)
                    </h5>
                    <p class="text-[11px] text-on-surface-variant mt-1 leading-normal">
                        به خاطر سپردن فیلترهای فروشگاه، تم نمایشی و پت‌های انتخاب شده کاربر.
                    </p>
                </div>
                <input type="checkbox" id="cookie-opt-preferences" checked class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer mt-1">
            </div>
        </div>

        <div class="p-4 border-t border-outline-variant/30 bg-surface-container-lowest flex gap-3">
            <button type="button" onclick="saveCustomCookieSettings(event)" class="flex-1 py-2.5 bg-primary text-white rounded-lg text-xs font-bold hover:bg-primary/90 transition-all cursor-pointer">
                ذخیره تنظیمات انتخابی
            </button>
            <button type="button" onclick="acceptAllCookies(event)" class="py-2.5 px-4 bg-secondary-container text-white rounded-lg text-xs font-bold hover:opacity-90 transition-all cursor-pointer">
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
                return; // User already made a choice
            }
        } catch (e) {}

        const banner = document.getElementById('asena-cookie-banner');
        if (!banner) return;

        banner.style.display = 'block';
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(16px)';
        banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';

        // Animate in smoothly
        setTimeout(() => {
            banner.style.opacity = '1';
            banner.style.transform = 'translateY(0)';
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }
})();

function acceptAllCookies(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
    }
    try {
        localStorage.setItem('asena_cookie_consent', 'all');
        localStorage.setItem('asena_cache_allowed', 'true');
    } catch (err) {}
    hideCookieBanner();
    closeCookieSettings();
    return false;
}

function saveCustomCookieSettings(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
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
        banner.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(16px)';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 260);
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
