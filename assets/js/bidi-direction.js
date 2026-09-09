/**
 * ASENA Global Bi-directional (BiDi) Auto-Direction & Typing Engine
 * Automatically detects English/Latin (LTR) vs Persian/Arabic (RTL) on all inputs, textareas, and chat messages.
 * Fixes punctuation flipping (like '?') and ensures natural typing direction across the platform.
 */
(function() {
    'use strict';

    // Persian/Arabic/Hebrew Unicode range
    const RTL_REGEX = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;
    // Latin / English alphabet regex
    const LTR_REGEX = /[a-zA-Z]/;

    // Detect direction from text
    function detectTextDirection(text) {
        if (!text) return null;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (RTL_REGEX.test(char)) return 'rtl';
            if (LTR_REGEX.test(char)) return 'ltr';
        }
        return null;
    }

    // Apply direction to input/textarea
    function applyInputDirection(el) {
        if (!el || el.type === 'password' || el.type === 'checkbox' || el.type === 'radio' || el.type === 'file') {
            return;
        }
        const val = el.value || '';
        const dir = detectTextDirection(val);
        if (dir === 'ltr') {
            el.setAttribute('dir', 'ltr');
            el.style.textAlign = 'left';
        } else if (dir === 'rtl') {
            el.setAttribute('dir', 'rtl');
            el.style.textAlign = 'right';
        } else {
            if (val.trim() === '') {
                // If empty, remove override to let placeholder inherit naturally
                el.removeAttribute('dir');
                el.style.textAlign = '';
            }
        }
    }

    // Global listener for typing on all inputs and textareas
    document.addEventListener('input', function(e) {
        const target = e.target;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
            applyInputDirection(target);
        }
    }, true);

    document.addEventListener('focusin', function(e) {
        const target = e.target;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
            applyInputDirection(target);
        }
    }, true);

    // Apply dir="auto" to text containers
    function applyAutoDirectionToContainers() {
        const textSelectors = [
            '.chat-message-text',
            '.markdown-body',
            '.ticket-card p',
            '.ticket-message',
            '.bidi-auto',
            '[data-bidi-auto]'
        ];
        document.querySelectorAll(textSelectors.join(', ')).forEach(el => {
            if (!el.hasAttribute('dir')) {
                el.setAttribute('dir', 'auto');
            }
        });

        // Check pre-filled inputs
        document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="checkbox"]):not([type="radio"]), textarea').forEach(el => {
            if (el.value) {
                applyInputDirection(el);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAutoDirectionToContainers);
    } else {
        applyAutoDirectionToContainers();
    }

    // MutationObserver to handle dynamically injected chat bubbles
    const observer = new MutationObserver(function(mutations) {
        for (let mutation of mutations) {
            for (let node of mutation.addedNodes) {
                if (node.nodeType === 1) { // Element
                    if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
                        applyInputDirection(node);
                    }
                    if (node.matches && (node.matches('.chat-message-text, .markdown-body, .bidi-auto') || node.querySelector('.chat-message-text, .markdown-body, .bidi-auto'))) {
                        applyAutoDirectionToContainers();
                    }
                }
            }
        }
    });

    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.body) {
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
    }

    // Utility: Convert Persian/Arabic digits to English digits
    window.toEnglishDigits = function(str) {
        if (!str && str !== 0) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        let res = String(str);
        for (let i = 0; i < 10; i++) {
            res = res.replaceAll(persian[i], String(i)).replaceAll(arabic[i], String(i));
        }
        return res;
    };
})();
