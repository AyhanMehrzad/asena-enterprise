/**
 * ASENA Universal Lazy Loader & Image Optimizer
 * 
 * Provides:
 *  1. Automatic native loading="lazy" & decoding="async" enforcement on all images.
 *  2. IntersectionObserver progressive loading for inline & background images.
 *  3. Blur-up & smooth fade-in transitions preventing Cumulative Layout Shift (CLS).
 *  4. MutationObserver support for dynamically injected items (AJAX pagination, filters, carts).
 */
(function() {
    'use strict';

    function initLazyLoading() {
        // 1. Process all existing images
        const images = document.querySelectorAll('img:not([data-lazy-processed])');
        images.forEach(function(img) {
            img.setAttribute('data-lazy-processed', 'true');
            
            // Skip above-the-fold hero images or small icons if marked
            if (img.classList.contains('no-lazy') || img.hasAttribute('data-priority')) {
                return;
            }

            // Enforce browser-native lazy loading and asynchronous decoding
            if (!img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }
            if (!img.hasAttribute('decoding')) {
                img.setAttribute('decoding', 'async');
            }

            // Add smooth entrance classes
            if (!img.complete) {
                img.classList.add('lazy-img-init');
                img.addEventListener('load', function() {
                    img.classList.add('lazy-img-loaded');
                    img.classList.remove('lazy-img-init');
                }, { once: true });
                img.addEventListener('error', function() {
                    // Fallback to placeholder on error
                    if (!img.src.includes('placeholder')) {
                        img.src = 'assets/images/placeholders/placeholder-product.svg';
                    }
                    img.classList.add('lazy-img-loaded');
                    img.classList.remove('lazy-img-init');
                }, { once: true });
            } else {
                img.classList.add('lazy-img-loaded');
            }
        });

        // 2. Process data-bg background images using IntersectionObserver
        if ('IntersectionObserver' in window) {
            const bgObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const bgUrl = target.getAttribute('data-bg');
                        if (bgUrl) {
                            target.style.backgroundImage = "url('" + bgUrl + "')";
                            target.classList.add('bg-loaded');
                            target.removeAttribute('data-bg');
                        }
                        observer.unobserve(target);
                    }
                });
            }, { rootMargin: '100px 0px' });

            document.querySelectorAll('[data-bg]').forEach(function(el) {
                bgObserver.observe(el);
            });
        }
    }

    // Run on DOM ready and load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLazyLoading);
    } else {
        initLazyLoading();
    }
    window.addEventListener('load', initLazyLoading);

    // Dynamic content observer for AJAX loaded elements
    if ('MutationObserver' in window) {
        const domObserver = new MutationObserver(function(mutations) {
            let hasNewImages = false;
            for (let i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes.length > 0) {
                    hasNewImages = true;
                    break;
                }
            }
            if (hasNewImages) {
                initLazyLoading();
            }
        });

        if (document.body) {
            domObserver.observe(document.body, { childList: true, subtree: true });
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                domObserver.observe(document.body, { childList: true, subtree: true });
            });
        }
    }
})();
