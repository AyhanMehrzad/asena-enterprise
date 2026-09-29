/**
 * ASENA Enterprise - Universal Currency & 3-by-3 Number Separator
 * Formats monetary amounts with commas (e.g., 3,455,000) and displays live Persian reading text.
 */
(function() {
    'use strict';

    function toEnglishDigits(str) {
        if (!str && str !== 0) return '';
        const fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        const en = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
        return str.toString().replace(/[۰-۹٠-٩]/g, function(char) {
            const idx = fa.indexOf(char);
            return idx !== -1 ? en[idx] : char;
        });
    }

    function formatNumberWithCommas(value) {
        if (!value && value !== 0) return '';
        const clean = toEnglishDigits(value).replace(/[^\d]/g, '');
        if (!clean) return '';
        return clean.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function numberToPersianWords(num) {
        num = parseInt(num, 10);
        if (isNaN(num) || num <= 0) return '';

        const yekan = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
        const dahgan = ['', 'ده', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
        const dahha = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
        const sadgan = ['', 'یکصد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
        const scales = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];

        function convertChunk(n) {
            const parts = [];
            const c = Math.floor(n / 100);
            const remainder = n % 100;
            const b = Math.floor(remainder / 10);
            const a = remainder % 10;

            if (c > 0) parts.push(sadgan[c]);
            if (b === 1) {
                parts.push(dahha[a]);
            } else {
                if (b > 1) parts.push(dahgan[b]);
                if (a > 0) parts.push(yekan[a]);
            }
            return parts.join(' و ');
        }

        const chunks = [];
        let temp = num;
        let scaleIdx = 0;
        while (temp > 0 && scaleIdx < scales.length) {
            const chunk = temp % 1000;
            if (chunk > 0) {
                const chunkText = convertChunk(chunk);
                if (chunkText) {
                    const suffix = scales[scaleIdx];
                    chunks.unshift(suffix ? chunkText + ' ' + suffix : chunkText);
                }
            }
            temp = Math.floor(temp / 1000);
            scaleIdx++;
        }

        return chunks.join(' و ') + ' تومان';
    }

    function updatePreview(input, rawVal) {
        const previewId = (input.id || input.name) + '_preview';
        let previewEl = document.getElementById(previewId);

        if (!previewEl) {
            // Check if there is an existing preview container or create one
            const parent = input.closest('.relative') || input.parentElement;
            if (parent) {
                previewEl = parent.parentElement ? parent.parentElement.querySelector('[data-currency-preview-for="' + (input.id || input.name) + '"]') : null;
            }
        }

        if (!previewEl) return;

        if (!rawVal || parseInt(rawVal, 10) === 0) {
            previewEl.textContent = '';
            previewEl.style.display = 'none';
            return;
        }

        const formattedFa = parseInt(rawVal, 10).toLocaleString('fa-IR');
        const words = numberToPersianWords(rawVal);
        previewEl.style.display = 'block';
        previewEl.innerHTML = `معادل: <span class="font-mono font-bold">${formattedFa} تومان</span> ${words ? `<span class="opacity-75 font-normal">(${words})</span>` : ''}`;
    }

    function attachInputEvents(input) {
        if (input.dataset.currencyFormatted === 'true') return;
        input.dataset.currencyFormatted = 'true';

        // Format initial value
        if (input.value) {
            const raw = toEnglishDigits(input.value).replace(/[^\d]/g, '');
            input.value = formatNumberWithCommas(raw);
            updatePreview(input, raw);
        }

        input.addEventListener('input', function() {
            const prevVal = this.value;
            const cursorPos = this.selectionStart || 0;
            const digitsBeforeCursor = toEnglishDigits(prevVal.slice(0, cursorPos)).replace(/[^\d]/g, '').length;

            const rawVal = toEnglishDigits(this.value).replace(/[^\d]/g, '');
            const formatted = formatNumberWithCommas(rawVal);
            this.value = formatted;

            // Recalculate cursor position to match digit count
            let newCursorPos = 0;
            let digitCount = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    digitCount++;
                }
                if (digitCount === digitsBeforeCursor) {
                    newCursorPos = i + 1;
                    break;
                }
            }
            if (digitsBeforeCursor === 0) newCursorPos = 0;
            if (digitCount < digitsBeforeCursor) newCursorPos = formatted.length;

            try {
                this.setSelectionRange(newCursorPos, newCursorPos);
            } catch (e) {}

            updatePreview(this, rawVal);
        });

        input.addEventListener('blur', function() {
            const rawVal = toEnglishDigits(this.value).replace(/[^\d]/g, '');
            this.value = formatNumberWithCommas(rawVal);
            updatePreview(this, rawVal);
        });
    }

    function init() {
        const selectors = [
            '.currency-input',
            '[data-currency-input]',
            'input[name="tax_small_trans_threshold"]',
            'input[name="tax_input_credit_amount"]',
            'input[name="free_shipping_threshold_toman"]',
            'input[name="standard_shipping_cost_toman"]',
            'input[name="calculator_price_toman"]',
            'input[name="price"]',
            'input[name="consultation_fee"]',
            'input[name="fee"]',
            '#charge_amount_input'
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(function(input) {
            if (input.type === 'number') {
                input.type = 'text';
                input.setAttribute('inputmode', 'numeric');
            }
            attachInputEvents(input);
        });
    }

    // Auto-init on DOMContentLoaded and immediate execution if ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export to window
    window.CurrencyFormatter = {
        init: init,
        attach: attachInputEvents,
        format: formatNumberWithCommas,
        toWords: numberToPersianWords,
        toEnglishDigits: toEnglishDigits
    };
})();
