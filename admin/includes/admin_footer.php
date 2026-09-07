    <!-- Admin Mobile Bottom Navigation Bar (Digikala style) -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-white dark:bg-tertiary border-t border-outline-variant/30 flex items-center justify-around h-16 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] px-2 pb-[env(safe-area-inset-bottom)]" role="navigation" aria-label="ناوبری مدیر">
        <a href="index.php" class="flex flex-col items-center justify-center flex-1 py-1 transition-colors <?= ($activeKey === 'dashboard') ? 'text-secondary-container font-bold' : 'text-outline hover:text-primary' ?>">
            <span class="material-symbols-outlined text-2xl" <?= ($activeKey === 'dashboard') ? "style=\"font-variation-settings: 'FILL' 1;\"" : "" ?>>dashboard</span>
            <span class="text-[10px] mt-0.5 font-bold">پیشخوان</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center justify-center flex-1 py-1 transition-colors <?= ($activeKey === 'orders') ? 'text-secondary-container font-bold' : 'text-outline hover:text-primary' ?>">
            <span class="material-symbols-outlined text-2xl" <?= ($activeKey === 'orders') ? "style=\"font-variation-settings: 'FILL' 1;\"" : "" ?>>local_shipping</span>
            <span class="text-[10px] mt-0.5 font-bold">سفارشات</span>
        </a>
        <a href="inventory.php" class="flex flex-col items-center justify-center flex-1 py-1 transition-colors <?= ($activeKey === 'inventory') ? 'text-secondary-container font-bold' : 'text-outline hover:text-primary' ?>">
            <span class="material-symbols-outlined text-2xl" <?= ($activeKey === 'inventory') ? "style=\"font-variation-settings: 'FILL' 1;\"" : "" ?>>inventory_2</span>
            <span class="text-[10px] mt-0.5 font-bold">انبارداری</span>
        </a>
        <a href="verifications.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-colors <?= ($activeKey === 'verifications') ? 'text-secondary-container font-bold' : 'text-outline hover:text-primary' ?>">
            <span class="material-symbols-outlined text-2xl" <?= ($activeKey === 'verifications') ? "style=\"font-variation-settings: 'FILL' 1;\"" : "" ?>>verified_user</span>
            <?php if (!empty($pendingVerificationsCount) && $pendingVerificationsCount > 0): ?>
                <span class="absolute top-1 right-3.5 w-4 h-4 bg-rose-500 text-white text-[9px] font-black rounded-full flex items-center justify-center"><?= $pendingVerificationsCount ?></span>
            <?php endif; ?>
            <span class="text-[10px] mt-0.5 font-bold">احرازها</span>
        </a>
        <button type="button" onclick="toggleAdminSidebar()" class="flex flex-col items-center justify-center flex-1 py-1 text-outline hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-2xl">menu</span>
            <span class="text-[10px] mt-0.5 font-bold">بیشتر</span>
        </button>
    </nav>
    <div class="lg:hidden h-16"></div>
</main>
<script>
    // Search bar focus effect
    const searchInput = document.querySelector('input[type="text"]');
    if(searchInput) {
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('ring-2', 'ring-primary-container/20');
        });
        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('ring-2', 'ring-primary-container/20');
        });
    }
</script>
</body>
</html>
