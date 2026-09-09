/**
 * ASENA Enterprise - Offline SVG Icon Auto-Fallback Engine
 * Ensures 100% icon visibility even when offline or when webfonts fail.
 */
(function() {
    'use strict';

    const SPRITE_URL = '/assets/icons/ui/sprite.svg';

    function injectSvgIcon(span) {
        if (!span || span.dataset.svgInjected) return;
        const iconName = span.textContent.trim();
        if (!iconName) return;

        span.dataset.svgInjected = 'true';
        span.classList.add('asena-icon-replaced');

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'asena-svg-icon ' + (span.dataset.iconClass || ''));
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');

        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', `${SPRITE_URL}#icon-${iconName}`);
        use.setAttribute('href', `${SPRITE_URL}#icon-${iconName}`);

        svg.appendChild(use);
        span.textContent = '';
        span.appendChild(svg);
    }

    function scanAndReplaceAll() {
        document.querySelectorAll('.material-symbols-outlined:not([data-svg-injected]), .material-icons-round:not([data-svg-injected])').forEach(injectSvgIcon);
    }

    // Expose global helper
    window.renderAsenaSvgIcon = function(name, extraClass = '') {
        return `<svg class="asena-svg-icon ${extraClass}" aria-hidden="true" focusable="false"><use href="${SPRITE_URL}#icon-${name}"></use></svg>`;
    };

    window.replaceIconsWithSvg = scanAndReplaceAll;

    // Check if webfont fails or offline
    function checkFontFallback() {
        if (!navigator.onLine) {
            scanAndReplaceAll();
            return;
        }

        if (document.fonts && document.fonts.check) {
            if (!document.fonts.check('24px "Material Symbols Outlined"')) {
                document.fonts.ready.then(() => {
                    if (!document.fonts.check('24px "Material Symbols Outlined"')) {
                        scanAndReplaceAll();
                    }
                }).catch(() => scanAndReplaceAll());
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkFontFallback);
    } else {
        checkFontFallback();
    }

    window.addEventListener('offline', scanAndReplaceAll);
})();
