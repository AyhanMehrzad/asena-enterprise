</main>

<!-- Digikala Seller App Style Bottom Navigation Bar -->
<nav class="mobile-bottom-nav lg:hidden" id="sellerBottomNav" role="navigation" aria-label="ناوبری فروشگاه">
    <a href="../index.php" class="bottom-nav-link">
        <span class="material-symbols-outlined">home</span>
        <span>سایت</span>
    </a>
    <button type="button" onclick="switchSellerTab('orders-tab')" class="bottom-nav-link" id="seller-bottom-orders">
        <span class="material-symbols-outlined">local_shipping</span>
        <span>سفارشات</span>
    </button>
    <button type="button" onclick="switchSellerTab('products-tab')" class="bottom-nav-link" id="seller-bottom-products">
        <span class="material-symbols-outlined">inventory_2</span>
        <span>محصولات</span>
    </button>
    <button type="button" onclick="switchSellerTab('wallet-tab')" class="bottom-nav-link" id="seller-bottom-wallet">
        <span class="material-symbols-outlined">account_balance_wallet</span>
        <span>تسویه</span>
    </button>
    <button type="button" onclick="switchSellerTab('settings-tab')" class="bottom-nav-link" id="seller-bottom-settings">
        <span class="material-symbols-outlined">store</span>
        <span>فروشگاه</span>
    </button>
</nav>

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
    
    // Update sidebar links
    document.querySelectorAll('.seller-nav-link').forEach(link => {
        link.className = "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
    });
    const activeLink = document.getElementById('seller-nav-' + key);
    if (activeLink) {
        activeLink.className = "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all";
    }

    // Update mobile top tabs
    document.querySelectorAll('.seller-mobile-tab').forEach(btn => {
        btn.className = "seller-mobile-tab px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold shrink-0 flex items-center gap-1.5";
    });
    const activeMobileBtn = document.getElementById('seller-mobile-btn-' + key);
    if (activeMobileBtn) {
        activeMobileBtn.className = "seller-mobile-tab px-3.5 py-2 rounded-xl bg-secondary-container text-white text-xs font-bold shrink-0 flex items-center gap-1.5 shadow-sm";
    }

    // Update mobile bottom nav
    document.querySelectorAll('#sellerBottomNav .bottom-nav-link').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBottomBtn = document.getElementById('seller-bottom-' + key);
    if (activeBottomBtn) {
        activeBottomBtn.classList.add('active');
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
