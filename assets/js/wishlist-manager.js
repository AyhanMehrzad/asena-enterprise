/**
 * ASENA Enterprise - Universal Wishlist Manager
 * Provides:
 * 1. Zero-latency Optimistic UI toggling (no reload needed)
 * 2. Delightful Heart Pop & Particle Burst micro-animations
 * 3. Non-blocking Floating Toast alerts
 * 4. Automatic background synchronization with server
 */

window.toggleWishlist = function(btn, productId, event) {
    const ev = event || window.event;
    if (ev) {
        ev.preventDefault();
        ev.stopPropagation();
    }

    if (!btn || !productId) return;

    // Find the icon element
    const icon = btn.querySelector('.material-symbols-outlined') || btn.querySelector('span') || btn;
    
    // Determine current state
    const currentVariation = icon.style.fontVariationSettings || '';
    const isCurrentlyActive = currentVariation.includes("'FILL' 1") || 
                              btn.classList.contains('active-wishlist') || 
                              icon.style.color === 'rgb(220, 38, 38)' || 
                              icon.style.color === '#dc2626' || 
                              icon.style.color === '#e11d48';

    const willBeActive = !isCurrentlyActive;

    // Save previous state in case we need to roll back
    const previousVariation = icon.style.fontVariationSettings;
    const previousColor = icon.style.color;
    const previousClass = btn.className;

    // ──────────────────────────────────────────────────────────────────────────
    // 1. INSTANT OPTIMISTIC UI UPDATE (0ms Latency)
    // ──────────────────────────────────────────────────────────────────────────
    if (willBeActive) {
        icon.style.fontVariationSettings = "'FILL' 1";
        icon.style.color = '#e11d48';
        btn.classList.add('text-rose-600', 'active-wishlist');
        btn.classList.remove('text-slate-400', 'text-on-surface');

        // Trigger Pop Animation on icon
        icon.classList.remove('heart-pop-anim', 'heart-unpop-anim');
        void icon.offsetWidth; // Trigger reflow
        icon.classList.add('heart-pop-anim');

        // Trigger Particle Burst around button
        createHeartBurst(btn);

        // Trigger Alert Toast
        showWishlistToast('به لیست علاقه‌مندی‌ها اضافه شد ❤️', 'added');

        // Optional device haptic feedback
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            try { navigator.vibrate(20); } catch (e) {}
        }
    } else {
        icon.style.fontVariationSettings = "'FILL' 0";
        icon.style.color = 'inherit';
        btn.classList.remove('text-rose-600', 'active-wishlist');
        btn.classList.add('text-slate-400');

        // Trigger Unpop animation
        icon.classList.remove('heart-pop-anim', 'heart-unpop-anim');
        void icon.offsetWidth;
        icon.classList.add('heart-unpop-anim');

        // Trigger Alert Toast
        showWishlistToast('از لیست علاقه‌مندی‌ها حذف شد', 'removed');
    }

    // Update any wishlist badges on the page
    updateWishlistBadges(willBeActive ? 1 : -1);

    // ──────────────────────────────────────────────────────────────────────────
    // 2. BACKGROUND SERVER SYNC
    // ──────────────────────────────────────────────────────────────────────────
    fetch('actions/wishlist_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Confirm final state from server
            const finalActive = data.in_wishlist !== undefined ? data.in_wishlist : (data.action === 'added');
            if (finalActive) {
                icon.style.fontVariationSettings = "'FILL' 1";
                icon.style.color = '#e11d48';
                btn.classList.add('text-rose-600', 'active-wishlist');
            } else {
                icon.style.fontVariationSettings = "'FILL' 0";
                icon.style.color = 'inherit';
                btn.classList.remove('text-rose-600', 'active-wishlist');
            }
            if (data.count !== undefined) {
                setWishlistBadgesExact(data.count);
            }
        } else {
            // Revert state on server error
            icon.style.fontVariationSettings = previousVariation;
            icon.style.color = previousColor;
            btn.className = previousClass;
            updateWishlistBadges(willBeActive ? -1 : 1);
            showWishlistToast(data.message || 'خطا در ثبت علاقه‌مندی', 'error');
        }
    })
    .catch(err => {
        console.error('Wishlist error:', err);
        // Revert on network failure
        icon.style.fontVariationSettings = previousVariation;
        icon.style.color = previousColor;
        btn.className = previousClass;
        updateWishlistBadges(willBeActive ? -1 : 1);
        showWishlistToast('خطا در اتصال به اینترنت. لطفاً مجدداً تلاش کنید.', 'error');
    });
};

/**
 * Creates floating mini-heart particles bursting out from the button
 */
function createHeartBurst(btn) {
    try {
        const rect = btn.getBoundingClientRect();
        const burstContainer = document.createElement('div');
        burstContainer.className = 'heart-burst-container';
        burstContainer.style.position = 'fixed';
        burstContainer.style.left = `${rect.left + rect.width / 2}px`;
        burstContainer.style.top = `${rect.top + rect.height / 2}px`;
        burstContainer.style.pointerEvents = 'none';
        burstContainer.style.zIndex = '999999';
        document.body.appendChild(burstContainer);

        const particles = ['❤️', '💖', '✨', '🐾', '💕', '🧡'];
        const count = 5;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('span');
            p.className = 'floating-heart-particle';
            p.textContent = particles[i % particles.length];
            const angle = (i / count) * 2 * Math.PI + (Math.random() * 0.4 - 0.2);
            const distance = 28 + Math.random() * 22;
            const tx = Math.cos(angle) * distance;
            const ty = Math.sin(angle) * distance - 20; // drift upward
            const rot = (Math.random() * 60 - 30);
            p.style.setProperty('--tx', `${tx}px`);
            p.style.setProperty('--ty', `${ty}px`);
            p.style.setProperty('--rot', `${rot}deg`);
            burstContainer.appendChild(p);
        }

        setTimeout(() => burstContainer.remove(), 800);
    } catch (e) {
        console.error('Burst error:', e);
    }
}

/**
 * Shows non-blocking modern floating toast notification
 */
function showWishlistToast(message, type = 'added') {
    let container = document.getElementById('wishlistToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'wishlistToastContainer';
        container.className = 'wishlist-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `wishlist-toast ${type === 'added' ? 'wishlist-toast-added' : (type === 'removed' ? 'wishlist-toast-removed' : 'border-right-4 border-amber-500')}`;

    const iconHtml = type === 'added' 
        ? '<span class="material-symbols-outlined text-rose-500 text-xl shrink-0 animate-pulse" style="font-variation-settings: \'FILL\' 1;">favorite</span>'
        : (type === 'removed' 
            ? '<span class="material-symbols-outlined text-slate-400 text-xl shrink-0">heart_broken</span>'
            : '<span class="material-symbols-outlined text-amber-400 text-xl shrink-0">info</span>');

    const actionLink = type === 'added' 
        ? '<a href="wishlist.php" class="text-xs text-rose-400 hover:text-rose-300 font-bold whitespace-nowrap transition-colors flex items-center gap-1"><span>مشاهده لیست</span><span class="material-symbols-outlined text-sm">arrow_left</span></a>'
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
        toast.classList.add('wishlist-toast-fadeout');
        setTimeout(() => toast.remove(), 300);
    }, 2800);
}

/**
 * Updates UI wishlist counters
 */
function updateWishlistBadges(delta) {
    document.querySelectorAll('.wishlist-count-badge').forEach(badge => {
        let count = parseInt(badge.textContent.trim(), 10) || 0;
        count = Math.max(0, count + delta);
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    });
}

function setWishlistBadgesExact(total) {
    document.querySelectorAll('.wishlist-count-badge').forEach(badge => {
        badge.textContent = total;
        if (total > 0) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    });
}
