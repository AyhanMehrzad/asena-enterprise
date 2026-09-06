</main>

<script>
function toggleSellerSidebar() {
    const sidebar = document.getElementById('seller-sidebar');
    const backdrop = document.getElementById('seller-backdrop');
    
    if (sidebar.classList.contains('translate-x-full')) {
        sidebar.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
        setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('opacity-0');
        setTimeout(() => backdrop.classList.add('hidden'), 300);
        document.body.style.overflow = '';
    }
}

function switchSellerTab(tabId) {
    document.querySelectorAll('.seller-tab-content').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById(tabId);
    if (target) {
        target.classList.remove('hidden');
    }

    const key = tabId.replace('-tab', '');
    document.querySelectorAll('.seller-nav-link').forEach(link => {
        link.className = "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
    });
    const activeLink = document.getElementById('seller-nav-' + key);
    if (activeLink) {
        activeLink.className = "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all";
    }

    const url = new URL(window.location);
    url.searchParams.set('tab', key);
    window.history.replaceState({}, '', url);
}

// Check initial tab from URL
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'orders';
    const target = document.getElementById(tab + '-tab');
    if (target) {
        switchSellerTab(tab + '-tab');
    }
});
</script>
</body>
</html>
