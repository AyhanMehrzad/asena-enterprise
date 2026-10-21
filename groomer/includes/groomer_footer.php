    </main>

    <footer class="p-6 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
        سامانه جامع گرومینگ و آرایش حیوانات خانگی آسنا &copy; <?= date('Y') ?> - کلیه حقوق محفوظ است.
    </footer>
</div>

<script>
function toggleGroomerSidebar() {
    var sidebar = document.getElementById('groomer-sidebar');
    var backdrop = document.getElementById('groomer-backdrop');
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
