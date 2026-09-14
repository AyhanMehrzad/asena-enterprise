/**
 * ASENA Enterprise - Clean Tabbed Authentication Manager
 */
function switchAuthTab(tabId) {
    // Hide all tab panes
    const panes = document.querySelectorAll('.tab-pane');
    panes.forEach(pane => {
        pane.classList.add('hidden');
        pane.classList.remove('active');
    });

    // Reset tab buttons
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(btn => {
        btn.classList.remove('bg-white', 'text-teal-900', 'shadow-sm', 'font-extrabold');
        btn.classList.add('text-slate-500', 'font-medium');
    });

    // Activate selected pane
    const targetPane = document.getElementById('pane-' + tabId);
    const targetBtn = document.getElementById('tab-' + tabId);
    if (targetPane) {
        targetPane.classList.remove('hidden');
        targetPane.classList.add('active');
    }
    if (targetBtn) {
        targetBtn.classList.add('bg-white', 'text-teal-900', 'shadow-sm', 'font-extrabold');
        targetBtn.classList.remove('text-slate-500', 'font-medium');
    }

    // Update URL parameter without reload
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
}

function togglePasswordVisibility(inputId, btnEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    const icon = btnEl.querySelector('.material-symbols-outlined');
    if (icon) {
        icon.textContent = isPassword ? 'visibility_off' : 'visibility';
    }
}

// Countdown timer handler for OTP
function initOtpCountdown(timerId, btnId, seconds = 120) {
    const timerEl = document.getElementById(timerId);
    const btnEl = document.getElementById(btnId);
    if (!timerEl || !btnEl) return;

    let remaining = seconds;
    btnEl.disabled = true;

    const interval = setInterval(() => {
        remaining--;
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        timerEl.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;

        if (remaining <= 0) {
            clearInterval(interval);
            timerEl.textContent = '00:00';
            btnEl.disabled = false;
            btnEl.classList.remove('opacity-50', 'cursor-not-allowed');
            btnEl.classList.add('text-teal-700', 'hover:underline', 'cursor-pointer');
        }
    }, 1000);
}
