/**
 * ASENA Enterprise - Tabbed Authentication Manager (Mobile & Android Optimized)
 * Follows ASENA Brand Design Standards (Primary: #001a48, Accent: #fd8100)
 */
function switchAuthTab(tabId) {
    if (!tabId) return;

    // 1. Hide all tab panes
    var panes = document.querySelectorAll('.tab-pane');
    for (var i = 0; i < panes.length; i++) {
        panes[i].classList.add('hidden');
        panes[i].classList.remove('active');
    }

    // 2. Reset all tab buttons
    var tabs = document.querySelectorAll('.tab-btn');
    for (var j = 0; j < tabs.length; j++) {
        tabs[j].classList.remove('bg-white', 'text-primary', 'shadow-sm', 'font-black');
        tabs[j].classList.add('text-slate-500', 'font-medium');
    }

    // 3. Show target pane & highlight target button
    var targetPane = document.getElementById('pane-' + tabId);
    var targetBtn = document.getElementById('tab-' + tabId);
    if (targetPane) {
        targetPane.classList.remove('hidden');
        targetPane.classList.add('active');
    }
    if (targetBtn) {
        targetBtn.classList.add('bg-white', 'text-primary', 'shadow-sm', 'font-black');
        targetBtn.classList.remove('text-slate-500', 'font-medium');
    }

    // 4. Update browser URL without reloading
    try {
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url.toString());
        }
    } catch (e) {
        // Fallback for older browsers
    }
}

function togglePasswordVisibility(inputId, btnEl) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    if (btnEl) {
        var icon = btnEl.querySelector('.material-symbols-outlined');
        if (icon) {
            icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        }
    }
}

function initOtpCountdown(timerId, btnId, seconds) {
    seconds = typeof seconds === 'number' ? seconds : 120;
    var timerEl = document.getElementById(timerId);
    var btnEl = btnId ? document.getElementById(btnId) : null;
    if (!timerEl) return;

    var remaining = seconds;
    if (btnEl) btnEl.disabled = true;

    var interval = setInterval(function() {
        remaining--;
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;
        timerEl.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;

        if (remaining <= 0) {
            clearInterval(interval);
            timerEl.textContent = '00:00';
            if (btnEl) {
                btnEl.disabled = false;
                btnEl.classList.remove('opacity-50', 'cursor-not-allowed');
                btnEl.classList.add('text-primary', 'hover:text-secondary-container', 'hover:underline', 'cursor-pointer');
            }
        }
    }, 1000);
}

// Bind both click and touch events for Android/iOS responsiveness
function bindAuthTabs() {
    var tabBtns = document.querySelectorAll('.tab-btn');
    for (var i = 0; i < tabBtns.length; i++) {
        (function(btn) {
            var handleAction = function(e) {
                var tab = btn.getAttribute('data-tab');
                if (tab) {
                    if (e && e.preventDefault) e.preventDefault();
                    switchAuthTab(tab);
                }
            };
            btn.onclick = handleAction;
            btn.addEventListener('touchend', function(e) {
                handleAction(e);
            }, { passive: false });
        })(tabBtns[i]);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindAuthTabs);
} else {
    bindAuthTabs();
}
