    </main>

    <footer class="p-6 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
        سامانه جامع هتل و پانسیون حیوانات خانگی آسنا &copy; <?= date('Y') ?> - کلیه حقوق محفوظ است.
    </footer>
</div>

<script>
function togglePansionSidebar() {
    var sidebar = document.getElementById('pansion-sidebar');
    var backdrop = document.getElementById('pansion-backdrop');
    if (sidebar.classList.contains('translate-x-full')) {
        sidebar.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
        setTimeout(function() { backdrop.classList.remove('opacity-0'); }, 10);
    } else {
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('opacity-0');
        setTimeout(function() { backdrop.classList.add('hidden'); }, 300);
    }
}
</script>
</body>
</html>
