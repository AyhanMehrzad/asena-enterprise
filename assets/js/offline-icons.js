/**
 * ASENA Enterprise - Offline SVG Icon Auto-Fallback Engine
 * Ensures 100% icon visibility even when offline or when webfonts fail.
 */
(function() {
    'use strict';

    // Compute SPRITE_URL dynamically based on the script location
    let SPRITE_URL = 'assets/icons/ui/sprite.svg';
    const currentScript = document.currentScript || document.querySelector('script[src*="offline-icons.js"]');
    if (currentScript && currentScript.src) {
        try {
            const scriptUrl = new URL(currentScript.src, window.location.href);
            SPRITE_URL = new URL('../icons/ui/sprite.svg', scriptUrl).href;
        } catch (e) {
            SPRITE_URL = 'assets/icons/ui/sprite.svg';
        }
    }

    // Ensure base styles for .asena-svg-icon exist
    function ensureBaseStyles() {
        if (document.getElementById('asena-svg-icons-base-css')) return;
        const style = document.createElement('style');
        style.id = 'asena-svg-icons-base-css';
        style.textContent = '.asena-svg-icon{width:1.25em;height:1.25em;fill:currentColor;display:inline-block;vertical-align:middle;flex-shrink:0;}';
        if (document.head) document.head.appendChild(style);
    }
    ensureBaseStyles();

    let spriteInjected = false;
    function ensureSpriteInDom() {
        ensureBaseStyles();
        if (spriteInjected || document.getElementById('asena-auto-sprite-container')) return;
        if (!window.fetch) return;
        fetch(SPRITE_URL)
            .then(res => res.ok ? res.text() : null)
            .then(svgText => {
                if (svgText && !document.getElementById('asena-auto-sprite-container')) {
                    const div = document.createElement('div');
                    div.id = 'asena-auto-sprite-container';
                    div.style.display = 'none';
                    div.innerHTML = svgText;
                    document.body.insertBefore(div, document.body.firstChild);
                    spriteInjected = true;
                }
            })
            .catch(() => {});
    }

    function injectSvgIcon(span) {
        if (!span || span.dataset.svgInjected) return;
        const iconName = span.textContent.trim();
        if (!iconName) return;

        span.dataset.svgInjected = 'true';
        span.classList.add('asena-icon-replaced');

        const inDom = document.getElementById(`icon-${iconName}`);
        const iconRef = inDom ? `#icon-${iconName}` : `${SPRITE_URL}#icon-${iconName}`;

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'asena-svg-icon ' + (span.dataset.iconClass || ''));
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');

        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', iconRef);
        use.setAttribute('href', iconRef);

        svg.appendChild(use);
        span.textContent = '';
        span.appendChild(svg);
    }

    function scanAndReplaceAll() {
        ensureSpriteInDom();
        document.querySelectorAll('.material-symbols-outlined:not([data-svg-injected]), .material-icons-round:not([data-svg-injected]), .material-icons:not([data-svg-injected])').forEach(injectSvgIcon);
    }

    // Expose global helper
    window.renderAsenaSvgIcon = function(name, extraClass = '') {
        const inDom = document.getElementById(`icon-${name}`);
        const iconRef = inDom ? `#icon-${name}` : `${SPRITE_URL}#icon-${name}`;
        return `<svg class="asena-svg-icon ${extraClass}" aria-hidden="true" focusable="false"><use href="${iconRef}"></use></svg>`;
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
        document.addEventListener('DOMContentLoaded', () => {
            ensureSpriteInDom();
            checkFontFallback();
        });
    } else {
        ensureSpriteInDom();
        checkFontFallback();
    }

    window.addEventListener('offline', scanAndReplaceAll);
})();
