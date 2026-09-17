/**
 * ASENA Enterprise - Unified Notification & Live Social Proof System
 * Handles:
 * 1. Live purchase & booking interaction toasts (Social Proof)
 * 2. PWA installation welcome celebration & Native Web Push permission
 * 3. Interactive Notification Bell center dropdown & unread counter
 * Version: 1.0.0
 */

(function () {
    'use strict';

    // State
    let liveFeed = [];
    let currentFeedIndex = 0;
    let tickerInterval = null;
    let isTickerPaused = false;
    let isPwaMode = false;

    // Detect PWA Standalone Mode
    if (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true ||
        document.referrer.includes('android-app://')
    ) {
        isPwaMode = true;
    }

    // Helper: Resolve dynamic path to notification action endpoint (supporting sub-portals like /doctor/, /admin/)
    function getNotificationApiUrl(query = '') {
        const isSubdir = window.location.pathname.includes('/doctor/') || 
                         window.location.pathname.includes('/pharmacist/') || 
                         window.location.pathname.includes('/admin/');
        const base = isSubdir ? '../actions/notification_action.php' : 'actions/notification_action.php';
        return query ? `${base}?${query}` : base;
    }

    // -----------------------------------------------------------------
    // 1. Live Interaction & Purchase Social Proof Ticker
    // -----------------------------------------------------------------
    function initLiveSocialProof() {
        // Don't show if user previously dismissed for this session
        if (sessionStorage.getItem('asena_ticker_dismissed') === '1') {
            return;
        }

        fetch(getNotificationApiUrl('action=fetch_live_feed&_t=' + Date.now()))
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data && data.success && Array.isArray(data.feed) && data.feed.length > 0) {
                    liveFeed = data.feed;
                    // Start first toast after 4 seconds
                    setTimeout(showNextSocialProofToast, 4000);
                    // Cycle every 24 seconds
                    tickerInterval = setInterval(() => {
                        if (!isTickerPaused) {
                            showNextSocialProofToast();
                        }
                    }, 24000);
                }
            })
            .catch(() => {});
    }

    function createSocialProofContainer() {
        let container = document.getElementById('asenaSocialProofToast');
        if (container) return container;

        container = document.createElement('div');
        container.id = 'asenaSocialProofToast';
        container.className = 'fixed z-[900] transition-all duration-500 transform translate-y-10 opacity-0 pointer-events-none select-none';
        
        // Responsive positioning: Desktop: bottom-right (away from bottom-left chat); Mobile: top-20 (safe from bottom nav!)
        container.innerHTML = `
            <style>
                #asenaSocialProofToast {
                    bottom: 28px;
                    right: 28px;
                    max-width: 360px;
                    width: calc(100vw - 32px);
                }
                @media (max-width: 1023px) {
                    #asenaSocialProofToast {
                        top: 72px;
                        right: 16px;
                        left: 16px;
                        bottom: auto;
                        margin: 0 auto;
                        max-width: 350px;
                    }
                }
                #asenaSocialProofToast.active {
                    transform: translateY(0);
                    opacity: 1;
                    pointer-events: auto;
                }
            </style>
            <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/90 dark:border-slate-700/80 rounded-2xl p-3.5 shadow-2xl shadow-slate-900/10 flex items-center gap-3 relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-primary/5 rounded-full blur-xl pointer-events-none"></div>
                
                <!-- Icon or Product Thumbnail -->
                <div id="spToastIconWrap" class="w-11 h-11 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-primary border border-teal-100 dark:border-teal-800/60 flex items-center justify-center shrink-0 shadow-xs relative overflow-hidden">
                    <span id="spToastIcon" class="material-symbols-outlined text-2xl">shopping_bag</span>
                    <img id="spToastImg" src="" alt="محصول" class="w-full h-full object-cover hidden" />
                </div>

                <!-- Content Text -->
                <div class="flex-1 text-right min-w-0">
                    <div class="flex items-center justify-between gap-1 mb-0.5">
                        <span id="spToastUser" class="text-xs font-black text-slate-800 dark:text-slate-100 truncate">علیرضا ر. از تهران</span>
                        <span id="spToastTime" class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">هم‌اکنون</span>
                    </div>
                    <a id="spToastLink" href="shop.php" class="text-[11px] text-slate-600 dark:text-slate-300 line-clamp-1 hover:text-primary font-medium transition-colors">
                        غذای خشک رویال کنین
                    </a>
                    <div class="flex items-center gap-1.5 mt-1 text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                        <span class="material-symbols-outlined text-[12px]">verified</span>
                        <span id="spToastBadge">خرید تایید شده در آسنا</span>
                    </div>
                </div>

                <!-- Dismiss Button -->
                <button type="button" onclick="window.dismissSocialProofToast(event)" class="w-6 h-6 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 -mt-5 -ml-1 cursor-pointer" title="بستن">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        `;

        document.body.appendChild(container);
        return container;
    }

    function showNextSocialProofToast() {
        if (!liveFeed.length) return;

        const item = liveFeed[currentFeedIndex];
        currentFeedIndex = (currentFeedIndex + 1) % liveFeed.length;

        const toast = createSocialProofContainer();
        const userEl = document.getElementById('spToastUser');
        const timeEl = document.getElementById('spToastTime');
        const linkEl = document.getElementById('spToastLink');
        const iconEl = document.getElementById('spToastIcon');
        const imgEl = document.getElementById('spToastImg');
        const badgeEl = document.getElementById('spToastBadge');

        if (userEl) userEl.textContent = `${item.user_name} از ${item.city}`;
        if (timeEl) timeEl.textContent = item.time_ago || 'لحظاتی پیش';
        if (linkEl) {
            linkEl.textContent = item.item_title;
            linkEl.href = item.item_link || 'shop.php';
        }

        if (item.item_image && imgEl) {
            imgEl.src = item.item_image;
            imgEl.classList.remove('hidden');
            if (iconEl) iconEl.classList.add('hidden');
        } else {
            if (imgEl) imgEl.classList.add('hidden');
            if (iconEl) {
                iconEl.classList.remove('hidden');
                iconEl.textContent = item.icon || 'shopping_bag';
            }
        }

        if (badgeEl) {
            if (item.event_type === 'booking') {
                badgeEl.textContent = 'رزرو نوبت موفق در سامانه';
            } else if (item.event_type === 'prescription') {
                badgeEl.textContent = 'تایید و ارسال نسخه داروخانه';
            } else {
                badgeEl.textContent = 'خرید تایید شده در آسنا';
            }
        }

        // Show
        toast.classList.add('active');

        // Auto hide after 6.5s
        setTimeout(() => {
            toast.classList.remove('active');
        }, 6500);
    }

    window.dismissSocialProofToast = function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const toast = document.getElementById('asenaSocialProofToast');
        if (toast) toast.classList.remove('active');
        sessionStorage.setItem('asena_ticker_dismissed', '1');
        if (tickerInterval) clearInterval(tickerInterval);
    };

    // -----------------------------------------------------------------
    // 2. PWA Installed Celebration & Web Notifications
    // -----------------------------------------------------------------
    function initPwaInstalledHandlers() {
        // App installed native event
        window.addEventListener('appinstalled', () => {
            handlePwaInstalledCelebration();
        });

        // If in standalone mode for the first time
        if (isPwaMode && !localStorage.getItem('asena_pwa_first_launch_reward')) {
            handlePwaInstalledCelebration();
            localStorage.setItem('asena_pwa_first_launch_reward', '1');
        }
    }

    function handlePwaInstalledCelebration() {
        // Notify backend
        fetch(getNotificationApiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=pwa_installed'
        }).catch(() => {});

        // Show celebration toast
        showPwaCelebrationModal();

        // Smoothly request native Web Notification permission if default
        if ('Notification' in window && Notification.permission === 'default') {
            setTimeout(() => {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        triggerNativeWelcomeNotification();
                        subscribeUserToPush();
                    }
                });
            }, 2500);
        } else if ('Notification' in window && Notification.permission === 'granted') {
            triggerNativeWelcomeNotification();
            subscribeUserToPush();
        }
    }

    function triggerNativeWelcomeNotification() {
        if ('serviceWorker' in navigator && 'Notification' in window && Notification.permission === 'granted') {
            navigator.serviceWorker.ready.then(reg => {
                reg.showNotification('🎉 خوش آمدید به اپلیکیشن آسنا!', {
                    body: 'تخفیف ۱۰٪ خرید اول شما با کد PWA-WELCOME فعال شد. دسترسی آفلاین و اعلان‌های آنی برقرار است.',
                    icon: 'assets/images/logo.png',
                    badge: 'assets/images/favicon-32x32.png',
                    data: { url: 'shop.php?coupon=PWA-WELCOME' },
                    vibrate: [200, 100, 200]
                });
            }).catch(() => {});
        }
    }

    function subscribeUserToPush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        navigator.serviceWorker.ready.then(reg => {
            return reg.pushManager.getSubscription().then(sub => {
                if (sub) return sub;
                // Subscribe with application server key if available or standard
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    // If no VAPID is set, standard browser endpoint registration
                }).catch(() => null);
            });
        }).then(subscription => {
            if (subscription) {
                const subJson = subscription.toJSON();
                fetch(getNotificationApiUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'subscribe_pwa',
                        endpoint: subJson.endpoint,
                        keys: subJson.keys,
                        device_type: isPwaMode ? 'pwa_installed' : 'browser'
                    })
                }).catch(() => {});
            }
        }).catch(() => {});
    }

    function showPwaCelebrationModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[100050] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm rtl text-right animate-fade-in';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-slate-100 dark:border-slate-800 text-center relative overflow-hidden">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-400 to-orange-500 text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-500/30 animate-bounce">
                    <span class="material-symbols-outlined text-3xl">celebration</span>
                </div>
                <h3 class="font-black text-base text-slate-800 dark:text-white mb-1">اپلیکیشن آسنا با موفقیت نصب شد!</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">
                    از این پس می‌توانید به شکل فوق‌سریع و بدون نیاز به فیلترشکن از خدمات پزشکی، دارویی و پت‌شاپ آسنا استفاده کنید.
                </p>
                <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 mb-5">
                    <div class="text-[11px] text-amber-800 dark:text-amber-300 font-bold mb-1">هدیه نصب اپلیکیشن: ۱۰٪ تخفیف خرید اول</div>
                    <div class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 px-3 py-1 rounded-xl border border-amber-300 dark:border-amber-700 font-mono font-black text-sm text-slate-900 dark:text-amber-400">
                        <span>PWA-WELCOME</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="shop.php?coupon=PWA-WELCOME" class="flex-1 py-2.5 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-container transition-all shadow-md">
                        خرید با تخفیف
                    </a>
                    <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">
                        بستن
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // -----------------------------------------------------------------
    // 3. Synthesized Web Audio Notification Chime & Haptics
    // -----------------------------------------------------------------
    function playNotificationChime() {
        try {
            if (localStorage.getItem('asena_notif_sound') === 'disabled') return;
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            const now = ctx.currentTime;

            // Note 1: 784 Hz (G5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(784, now);
            gain1.gain.setValueAtTime(0, now);
            gain1.gain.linearRampToValueAtTime(0.12, now + 0.04);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.23);

            // Note 2: 1046.5 Hz (C6)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1046.5, now + 0.12);
            gain2.gain.setValueAtTime(0, now + 0.12);
            gain2.gain.linearRampToValueAtTime(0.14, now + 0.16);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.42);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.43);

            // Haptic vibration feedback (mobile devices)
            if (navigator.vibrate) {
                navigator.vibrate([40, 50, 40]);
            }
        } catch (e) {
            // Autoplay restrictions or unsupported audio context
        }
    }

    window.toggleNotificationSound = function () {
        const current = localStorage.getItem('asena_notif_sound');
        const next = (current === 'disabled') ? 'enabled' : 'disabled';
        localStorage.setItem('asena_notif_sound', next);
        updateSoundIcon();
        if (next === 'enabled') {
            playNotificationChime();
        }
    };

    function updateSoundIcon() {
        const iconEl = document.getElementById('notifSoundIcon');
        if (!iconEl) return;
        const isDisabled = localStorage.getItem('asena_notif_sound') === 'disabled';
        iconEl.textContent = isDisabled ? 'volume_off' : 'volume_up';
        iconEl.className = isDisabled ? 'material-symbols-outlined text-lg text-rose-500' : 'material-symbols-outlined text-lg text-slate-400 hover:text-slate-700';
    }

    // -----------------------------------------------------------------
    // 4. Real-World In-App Floating Glassmorphism Toasts
    // -----------------------------------------------------------------
    function createInAppToastStack() {
        let stack = document.getElementById('asenaInAppToastStack');
        if (stack) return stack;

        if (!document.getElementById('asenaInAppToastStyles')) {
            const style = document.createElement('style');
            style.id = 'asenaInAppToastStyles';
            style.textContent = `
                .asena-inapp-toast {
                    pointer-events: auto;
                    transform: translateY(-24px) scale(0.95);
                    opacity: 0;
                    transition: all 0.38s cubic-bezier(0.16, 1, 0.3, 1);
                }
                .asena-inapp-toast.active {
                    transform: translateY(0) scale(1);
                    opacity: 1;
                }
                .asena-inapp-toast.leave {
                    transform: translateY(-20px) scale(0.92);
                    opacity: 0;
                }
            `;
            document.head.appendChild(style);
        }

        stack = document.createElement('div');
        stack.id = 'asenaInAppToastStack';
        stack.className = 'fixed top-4 right-3 left-3 md:left-auto md:right-6 md:w-[390px] z-[100060] flex flex-col gap-2.5 pointer-events-none transition-all rtl text-right select-none';
        document.body.appendChild(stack);
        return stack;
    }

    window.showInAppNotificationToast = function (notif) {
        showInAppNotificationToast(notif);
    };

    function showInAppNotificationToast(notif) {
        if (!notif || !notif.title) return;
        const stack = createInAppToastStack();

        const toast = document.createElement('div');
        toast.className = 'asena-inapp-toast bg-[#001a48]/95 dark:bg-slate-900/95 text-white backdrop-blur-xl border border-white/10 rounded-2xl p-3.5 shadow-2xl shadow-blue-950/40 flex items-start gap-3 relative overflow-hidden group';

        const iconName = notif.icon || 'notifications';
        const linkUrl = notif.link_url || 'profile.php';

        toast.innerHTML = `
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-[#fd8100] text-white flex items-center justify-center shrink-0 shadow-md shadow-orange-500/20 mt-0.5">
                <span class="material-symbols-outlined text-xl">${iconName}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1 mb-0.5">
                    <span class="text-[10px] font-black tracking-wide text-orange-400">اعلان اختصاصی آسنا</span>
                    <span class="text-[10px] text-white/50">${notif.time_ago || 'هم‌اکنون'}</span>
                </div>
                <h4 class="text-xs font-black text-white line-clamp-1 mb-1">${notif.title}</h4>
                <p class="text-[11px] text-slate-200 line-clamp-2 leading-relaxed mb-2">${notif.message}</p>
                <div class="flex items-center gap-2">
                    <a href="${linkUrl}" onclick="window.markNotificationRead(${notif.id})" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#fd8100] hover:text-orange-300 transition-colors">
                        <span>مشاهده و اقدام</span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>
                </div>
            </div>
            <button type="button" class="w-6 h-6 rounded-full flex items-center justify-center text-white/40 hover:text-white hover:bg-white/10 transition-colors shrink-0 -mt-1 -ml-1 cursor-pointer" title="بستن">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        `;

        const closeBtn = toast.querySelector('button');
        const dismiss = () => {
            toast.classList.remove('active');
            toast.classList.add('leave');
            setTimeout(() => {
                toast.remove();
            }, 380);
        };
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dismiss();
        });

        if (notif.link_url) {
            toast.addEventListener('click', (e) => {
                if (e.target.closest('button') || e.target.closest('a')) return;
                window.markNotificationRead(notif.id);
                window.location.href = notif.link_url;
            });
            toast.style.cursor = 'pointer';
        }

        stack.appendChild(toast);
        // Trigger CSS transition
        void toast.offsetHeight;
        toast.classList.add('active');

        // Chime audio & haptic vibration
        playNotificationChime();

        // Auto dismiss after 7 seconds
        setTimeout(dismiss, 7000);
    }

    // -----------------------------------------------------------------
    // 5. Interactive Notification Center Drawer & Categorized Tabs
    // -----------------------------------------------------------------
    let lastSeenNotificationId = parseInt(sessionStorage.getItem('asena_last_notif_id') || '0', 10);
    let pollTimer = null;
    let isInitialLoad = true;

    function initNotificationBellCenter() {
        // Initial fetch and badge update
        pollNotifications();
        schedulePolling(25000);

        // Hook click on notification bell icons
        document.querySelectorAll('a[href*="notifications"], a[title*="اعلان"], .notification-bell-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                toggleNotificationDrawer();
            });
        });

        // Visibility-aware smart poller
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                schedulePolling(90000);
            } else {
                pollNotifications();
                schedulePolling(25000);
            }
        });
    }

    function schedulePolling(intervalMs) {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollNotifications, intervalMs);
    }

    function pollNotifications() {
        const sinceParam = (lastSeenNotificationId > 0 && !isInitialLoad) ? `&since_id=${lastSeenNotificationId}` : '';
        fetch(getNotificationApiUrl(`action=fetch_user_notifications&is_pwa=${isPwaMode ? 1 : 0}${sinceParam}&_t=` + Date.now()))
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data && data.success) {
                    applyBadgeCount(data.unread_count);

                    if (data.latest_id && data.latest_id > lastSeenNotificationId) {
                        // Pop toasts for newly arrived notifications (not on first cold page boot)
                        if (!isInitialLoad && Array.isArray(data.notifications)) {
                            const newUnreads = data.notifications.filter(n => n.is_read == 0 && n.id > lastSeenNotificationId);
                            newUnreads.slice(0, 2).forEach((n, idx) => {
                                setTimeout(() => showInAppNotificationToast(n), idx * 750);
                            });
                        }
                        lastSeenNotificationId = data.latest_id;
                        sessionStorage.setItem('asena_last_notif_id', String(lastSeenNotificationId));
                    }

                    if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                        if (!window.asenaCachedNotifications) window.asenaCachedNotifications = [];
                        const existingIds = new Set(window.asenaCachedNotifications.map(n => n.id));
                        data.notifications.forEach(n => {
                            if (!existingIds.has(n.id)) {
                                window.asenaCachedNotifications.unshift(n);
                            }
                        });
                    }

                    isInitialLoad = false;
                }
            })
            .catch(() => {});
    }

    function applyBadgeCount(count) {
        document.querySelectorAll('.notification-badge-count, a[title*="اعلان"] .animate-pulse').forEach(el => {
            if (count > 0) {
                el.classList.remove('hidden');
                if (el.tagName === 'SPAN' && el.textContent !== undefined && !el.classList.contains('w-2.5')) {
                    el.textContent = count > 9 ? '+9' : count;
                }
            } else {
                el.classList.add('hidden');
            }
        });
    }

    function createNotificationDrawer() {
        let drawer = document.getElementById('asenaNotificationDrawer');
        if (drawer) return drawer;

        drawer = document.createElement('div');
        drawer.id = 'asenaNotificationDrawer';
        drawer.className = 'fixed inset-0 z-[100020] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-xs rtl text-right select-none';
        drawer.innerHTML = `
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md max-h-[84vh] flex flex-col overflow-hidden border border-slate-200 dark:border-slate-800 animate-fade-in" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl">notifications</span>
                        </div>
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-white">مرکز اعلان‌های آسنا</h3>
                            <p class="text-[10px] text-slate-400">سفارشات، سلامت، گفتگو و جوایز وفاداری</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <!-- Sound Toggle -->
                        <button type="button" id="notifSoundToggleBtn" onclick="window.toggleNotificationSound()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="تنظیمات صدای اعلان">
                            <span class="material-symbols-outlined text-lg" id="notifSoundIcon">volume_up</span>
                        </button>
                        <!-- Mark all read -->
                        <button type="button" onclick="window.markAllNotificationsRead()" class="text-[11px] font-bold text-primary hover:underline px-2 py-1 rounded-lg hover:bg-primary/5 transition-colors" title="خواندن همه">
                            خواندن همه
                        </button>
                        <!-- Close drawer -->
                        <button type="button" onclick="window.toggleNotificationDrawer(false)" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                </div>

                <!-- Categorized Tabs -->
                <div class="flex items-center border-b border-slate-100 dark:border-slate-800 bg-slate-50/40 px-3 pt-2 gap-2 text-xs font-bold overflow-x-auto no-scrollbar">
                    <button type="button" onclick="window.filterDrawerNotifs('all', this)" class="drawer-tab active pb-2 border-b-2 border-primary text-primary whitespace-nowrap">همه</button>
                    <button type="button" onclick="window.filterDrawerNotifs('orders', this)" class="drawer-tab pb-2 border-b-2 border-transparent text-slate-400 hover:text-slate-700 whitespace-nowrap">سفارشات</button>
                    <button type="button" onclick="window.filterDrawerNotifs('health', this)" class="drawer-tab pb-2 border-b-2 border-transparent text-slate-400 hover:text-slate-700 whitespace-nowrap">نوبت و سلامت</button>
                    <button type="button" onclick="window.filterDrawerNotifs('messages', this)" class="drawer-tab pb-2 border-b-2 border-transparent text-slate-400 hover:text-slate-700 whitespace-nowrap">پیام‌ها</button>
                    <button type="button" onclick="window.filterDrawerNotifs('rewards', this)" class="drawer-tab pb-2 border-b-2 border-transparent text-slate-400 hover:text-slate-700 whitespace-nowrap">باشگاه مشتریان</button>
                </div>

                <!-- Notification List Container -->
                <div id="drawerNotifList" class="p-4 overflow-y-auto flex-1 space-y-3 custom-scrollbar min-h-[240px]">
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400 gap-2">
                        <span class="material-symbols-outlined text-4xl animate-spin">sync</span>
                        <span class="text-xs">در حال بارگذاری رویدادها...</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between text-[11px] text-slate-500">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="font-medium text-slate-600 dark:text-slate-300">سرویس همگام‌سازی آنی فعال است</span>
                    </div>
                    <a href="profile.php" class="text-primary font-bold hover:underline">پنل کاربری</a>
                </div>
            </div>
        `;

        drawer.addEventListener('click', () => {
            window.toggleNotificationDrawer(false);
        });

        document.body.appendChild(drawer);
        updateSoundIcon();
        return drawer;
    }

    window.toggleNotificationDrawer = function (forceState) {
        const drawer = createNotificationDrawer();
        const shouldOpen = (forceState !== undefined) ? forceState : drawer.classList.contains('hidden');

        if (shouldOpen) {
            drawer.classList.remove('hidden');
            drawer.classList.add('flex');
            document.body.style.overflow = 'hidden';
            updateSoundIcon();
            renderDrawerNotifications(window.asenaCachedNotifications || []);

            // Refresh latest from backend
            fetch(getNotificationApiUrl(`action=fetch_user_notifications&is_pwa=${isPwaMode ? 1 : 0}&_t=` + Date.now()))
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (data && data.success) {
                        window.asenaCachedNotifications = data.notifications;
                        renderDrawerNotifications(data.notifications);
                        applyBadgeCount(data.unread_count);
                    }
                });
        } else {
            drawer.classList.add('hidden');
            drawer.classList.remove('flex');
            document.body.style.overflow = '';
        }
    };

    function resolveNotificationCategory(type) {
        switch (type) {
            case 'order_status':
                return 'orders';
            case 'appointment':
            case 'pet_health':
            case 'prescription':
                return 'health';
            case 'chat_message':
                return 'messages';
            case 'loyalty_reward':
            case 'purchase_offer':
            case 'pwa_welcome':
            case 'financial':
                return 'rewards';
            default:
                return 'all';
        }
    }

    function renderDrawerNotifications(items, filter = 'all') {
        const listEl = document.getElementById('drawerNotifList');
        if (!listEl) return;

        let filtered = items || [];
        if (filter !== 'all') {
            filtered = filtered.filter(i => {
                const cat = i.category || resolveNotificationCategory(i.type);
                return cat === filter;
            });
        }

        if (!filtered.length) {
            listEl.innerHTML = `
                <div class="text-center py-14 text-slate-400 space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center mx-auto text-slate-400">
                        <span class="material-symbols-outlined text-3xl">notifications_off</span>
                    </div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">اعلانی در این دسته‌بندی یافت نشد</p>
                    <p class="text-[10px] text-slate-400">تمام رویدادهای جدید بلافاصله در این بخش نمایش داده می‌شوند.</p>
                </div>
            `;
            return;
        }

        listEl.innerHTML = filtered.map(n => {
            const isUnread = n.is_read == 0;
            const category = n.category || resolveNotificationCategory(n.type);

            let iconColorClass = 'bg-primary/10 text-primary';
            if (category === 'orders') iconColorClass = 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400';
            else if (category === 'health') iconColorClass = 'bg-teal-50 text-teal-600 dark:bg-teal-950/50 dark:text-teal-400';
            else if (category === 'messages') iconColorClass = 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400';
            else if (category === 'rewards') iconColorClass = 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400';

            return `
                <div class="p-3.5 rounded-2xl border transition-all ${isUnread ? 'bg-primary/5 border-primary/20 dark:bg-primary/10 shadow-xs' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800'} flex items-start gap-3 relative group">
                    <div class="w-10 h-10 rounded-xl ${iconColorClass} flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        <span class="material-symbols-outlined text-xl">${n.icon || 'notifications'}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <h4 class="text-xs font-black text-slate-900 dark:text-white truncate">${n.title}</h4>
                            <span class="text-[10px] text-slate-400 shrink-0">${n.time_ago || formatTimePersian(n.created_at)}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">${n.message}</p>
                        ${n.link_url ? `
                            <div class="mt-2 flex items-center gap-2">
                                <a href="${n.link_url}" onclick="window.markNotificationRead(${n.id})" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline">
                                    <span>مشاهده جزئیات</span>
                                    <span class="material-symbols-outlined text-xs">arrow_back</span>
                                </a>
                            </div>
                        ` : ''}
                    </div>
                    ${isUnread ? `<span class="w-2.5 h-2.5 rounded-full bg-primary shrink-0 mt-1" title="خوانده‌نشده"></span>` : ''}
                </div>
            `;
        }).join('');
    }

    window.filterDrawerNotifs = function (category, btn) {
        document.querySelectorAll('.drawer-tab').forEach(b => {
            b.className = 'drawer-tab pb-2 border-b-2 border-transparent text-slate-400 hover:text-slate-700 whitespace-nowrap';
        });
        btn.className = 'drawer-tab active pb-2 border-b-2 border-primary text-primary font-bold whitespace-nowrap';
        renderDrawerNotifications(window.asenaCachedNotifications || [], category);
    };

    window.markNotificationRead = function (id) {
        // Optimistic UI update
        if (window.asenaCachedNotifications) {
            const found = window.asenaCachedNotifications.find(n => n.id == id);
            if (found) found.is_read = 1;
        }
        fetch(getNotificationApiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_read&id=${id}`
        }).then(() => pollNotifications());
    };

    window.markAllNotificationsRead = function () {
        // Optimistic UI update
        if (window.asenaCachedNotifications) {
            window.asenaCachedNotifications.forEach(n => n.is_read = 1);
            renderDrawerNotifications(window.asenaCachedNotifications);
        }
        applyBadgeCount(0);

        fetch(getNotificationApiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_all_read'
        }).then(() => pollNotifications());
    };

    function formatTimePersian(dateStr) {
        if (!dateStr) return '';
        const diff = Math.max(1, Math.floor((new Date() - new Date(dateStr)) / 1000));
        if (diff < 60) return 'هم‌اکنون';
        if (diff < 3600) return `${Math.floor(diff / 60)} دقیقه پیش`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} ساعت پیش`;
        return `${Math.floor(diff / 86400)} روز پیش`;
    }

    // Initialize on DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initLiveSocialProof();
            initPwaInstalledHandlers();
            initNotificationBellCenter();
        });
    } else {
        initLiveSocialProof();
        initPwaInstalledHandlers();
        initNotificationBellCenter();
    }

})();
