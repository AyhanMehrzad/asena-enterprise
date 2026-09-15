/**
 * ASENA Enterprise - Universal Live Cart Manager
 * Version: 1.0.0
 * 
 * Features:
 * 1. Zero-latency Optimistic UI cart count incrementing (0ms latency on click)
 * 2. Visual bounce/pop micro-animation on desktop header and mobile bottom bar
 * 3. Haptic vibration & particle burst feedback
 * 4. Automatic background synchronization with server session
 * 5. Non-blocking floating glass toast notifications
 */

(function() {
    'use strict';

    /**
     * Helper to find or dynamically create the header cart badge if missing
     */
    function ensureHeaderCartBadge() {
        let badge = document.getElementById('header-cart-badge');
        if (!badge) {
            const btn = document.getElementById('header-cart-btn') || 
                        document.querySelector('header a[href*="cart.php"]') || 
                        document.querySelector('a[href="cart.php"]');
            if (btn) {
                btn.style.position = 'relative';
                badge = document.createElement('span');
                badge.id = 'header-cart-badge';
                badge.className = 'header-cart-badge cart-badge-count absolute top-0 right-0 bg-secondary-container text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold shadow transition-transform duration-200 hidden';
                badge.textContent = '0';
                btn.appendChild(badge);
            }
        }
        return badge;
    }

    /**
     * Updates all cart badge elements on the page with a specific count
     */
    window.updateCartBadges = function(count, animate = false) {
        ensureHeaderCartBadge();
        const badges = document.querySelectorAll('#header-cart-badge, #mobile-nav-cart-badge, .cart-badge-count, .nav-cart-badge');
        const num = Math.max(0, parseInt(count, 10) || 0);

        badges.forEach(badge => {
            badge.textContent = num;
            if (num > 0) {
                badge.classList.remove('hidden');
                badge.style.display = 'flex';
                if (animate) {
                    badge.classList.remove('cart-badge-pop');
                    void badge.offsetWidth; // Force DOM reflow to re-trigger animation
                    badge.classList.add('cart-badge-pop');
                }
            } else {
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }
        });

        // Header button micro-bump
        if (animate) {
            const cartBtns = document.querySelectorAll('#header-cart-btn, header a[href*="cart.php"]');
            cartBtns.forEach(btn => {
                btn.classList.remove('cart-icon-bump');
                void btn.offsetWidth;
                btn.classList.add('cart-icon-bump');
            });
        }
    };

    /**
     * Immediately and optimistically increments or decrements cart badges (0ms latency)
     */
    window.incrementCartBadges = function(delta = 1) {
        ensureHeaderCartBadge();
        const primaryBadge = document.getElementById('header-cart-badge') || 
                             document.querySelector('.cart-badge-count') || 
                             document.querySelector('.nav-cart-badge');
        
        let current = 0;
        if (primaryBadge && !primaryBadge.classList.contains('hidden') && primaryBadge.style.display !== 'none') {
            current = parseInt(primaryBadge.textContent.trim(), 10) || 0;
        }

        const newCount = Math.max(0, current + delta);
        window.updateCartBadges(newCount, true);

        // Gentle haptic feedback for supported mobile devices
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            try { navigator.vibrate(20); } catch (e) {}
        }

        return newCount;
    };

    /**
     * Generates a playful particle burst around the clicked button
     */
    window.createCartBurst = function(btn) {
        if (!btn || !btn.getBoundingClientRect) return;
        try {
            const rect = btn.getBoundingClientRect();
            const burst = document.createElement('div');
            burst.className = 'cart-burst-container';
            burst.style.left = `${rect.left + rect.width / 2}px`;
            burst.style.top = `${rect.top + rect.height / 2}px`;
            document.body.appendChild(burst);

            const particles = ['🛒', '+1', '🛍️', '✨', '🐾', '🧡'];
            const count = 5;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('span');
                p.className = 'floating-cart-particle';
                p.textContent = particles[i % particles.length];
                const angle = (i / count) * 2 * Math.PI + (Math.random() * 0.4 - 0.2);
                const distance = 28 + Math.random() * 22;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance - 24; // Drift upward
                const rot = (Math.random() * 50 - 25);
                p.style.setProperty('--tx', `${tx}px`);
                p.style.setProperty('--ty', `${ty}px`);
                p.style.setProperty('--rot', `${rot}deg`);
                burst.appendChild(p);
            }

            setTimeout(() => burst.remove(), 800);
        } catch (e) {
            // Silently fall through
        }
    };

    /**
     * Non-blocking modern floating glass toast notification (matches ASENA brand aesthetics)
     */
    window.showCartToast = function(message, type = 'success') {
        let container = document.getElementById('cartToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cartToastContainer';
            container.className = 'cart-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `cart-toast ${type === 'success' ? 'cart-toast-success' : 'cart-toast-error'}`;

        const iconHtml = type === 'success'
            ? '<span class="material-symbols-outlined text-secondary-container text-xl shrink-0">shopping_bag</span>'
            : '<span class="material-symbols-outlined text-red-400 text-xl shrink-0">error</span>';

        const actionLink = type === 'success'
            ? '<a href="cart.php" class="text-xs text-secondary-container hover:underline font-bold whitespace-nowrap transition-colors flex items-center gap-1"><span>سبد خرید</span><span class="material-symbols-outlined text-sm">arrow_left</span></a>'
            : '';

        toast.innerHTML = `
            <div class="flex items-center gap-2.5">
                ${iconHtml}
                <span class="text-xs sm:text-sm font-bold text-white">${message}</span>
            </div>
            ${actionLink}
        `;

        container.appendChild(toast);

        // Auto dismiss after 2.8s
        setTimeout(() => {
            toast.classList.add('cart-toast-fadeout');
            setTimeout(() => toast.remove(), 300);
        }, 2800);
    };

    /**
     * Universal Add To Cart Handler
     */
    window.addToCart = function(btn, productId, type = 'standard', options = {}) {
        const ev = window.event;
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }

        if (!productId || productId <= 0) return;

        // Save original button appearance
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
            btn.disabled = true;
        }

        // ─────────────────────────────────────────────────────────────
        // 1. INSTANT OPTIMISTIC LIVE INCREMENT (0ms Latency)
        // ─────────────────────────────────────────────────────────────
        window.incrementCartBadges(1);
        if (btn) {
            window.createCartBurst(btn);
        }

        // Resolve CSRF token from global variable or meta tag
        const csrfToken = window.ASENA_CSRF_TOKEN || 
                          document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          '';

        const frequency = options.frequency || '1_month';

        const params = new URLSearchParams();
        params.append('action', 'add');
        params.append('ajax', '1');
        params.append('csrf_token', csrfToken);
        params.append('product_id', productId);
        params.append('type', type);
        params.append('frequency', frequency);

        fetch('actions/cart_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        })
        .then(response => {
            if (!response.ok && response.status !== 400 && response.status !== 403) {
                throw new Error('Server returned HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                if (btn) {
                    btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">check_circle</span>';
                    btn.classList.add('bg-status-active');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('bg-status-active');
                        btn.disabled = false;
                    }, 1800);
                }

                // Synchronize exact server-side verified cart count
                if (data.cart_count !== undefined) {
                    window.updateCartBadges(data.cart_count, false);
                }

                window.showCartToast('کالا با موفقیت به سبد خرید اضافه شد 🛒', 'success');
            } else {
                // Rollback optimistic count on failure
                if (data.cart_count !== undefined) {
                    window.updateCartBadges(data.cart_count, false);
                } else {
                    window.incrementCartBadges(-1);
                }

                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
                window.showCartToast(data.message || 'خطا در افزودن به سبد خرید', 'error');
            }
        })
        .catch(err => {
            console.error('[CartManager] Add to cart error:', err);
            // Rollback optimistic count
            window.incrementCartBadges(-1);
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
            window.showCartToast('خطا در برقراری ارتباط با سرور', 'error');
        });
    };

})();
