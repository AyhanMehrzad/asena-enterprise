/**
 * ASENA Enterprise - Universal Collapsible Panel Sidebar Controller
 * Provides smooth desktop sidebar collapsing (64 -> 20 / 80px) with icon rail,
 * localStorage persistence, tooltip accessibility, and seamless mobile drawer support.
 */

function togglePanelDesktopSidebar() {
    const sidebar = document.querySelector('.panel-sidebar') || document.querySelector('aside[id$="-sidebar"]');
    const main = document.querySelector('.panel-main-content') || document.querySelector('div.lg\\:mr-64') || document.getElementById('panel-main-content');
    const icons = document.querySelectorAll('.sidebar-desktop-toggle-icon, #sidebar-desktop-toggle-icon');
    
    if (!sidebar) return;

    const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
    if (main) {
        main.classList.toggle('sidebar-collapsed', isCollapsed);
    }
    document.body.classList.toggle('panel-sidebar-collapsed-shell', isCollapsed);

    icons.forEach(icon => {
        // In RTL, chevron_left expands (points away from edge), chevron_right collapses (points towards edge)
        icon.innerText = isCollapsed ? 'chevron_left' : 'chevron_right';
    });

    try {
        localStorage.setItem('asana_panel_sidebar_collapsed', isCollapsed ? '1' : '0');
    } catch (e) {
        console.warn('Failed to save panel sidebar collapsed state:', e);
    }
}

// Hydrate sidebar state immediately upon DOM ready
document.addEventListener('DOMContentLoaded', function() {
    try {
        const isCollapsed = localStorage.getItem('asana_panel_sidebar_collapsed') === '1';
        if (isCollapsed && window.innerWidth >= 1024) {
            const sidebar = document.querySelector('.panel-sidebar') || document.querySelector('aside[id$="-sidebar"]');
            const main = document.querySelector('.panel-main-content') || document.querySelector('div.lg\\:mr-64') || document.getElementById('panel-main-content');
            const icons = document.querySelectorAll('.sidebar-desktop-toggle-icon, #sidebar-desktop-toggle-icon');
            
            if (sidebar) sidebar.classList.add('sidebar-collapsed');
            if (main) main.classList.add('sidebar-collapsed');
            document.body.classList.add('panel-sidebar-collapsed-shell');
            icons.forEach(icon => { icon.innerText = 'chevron_left'; });
        }
    } catch (e) {}

    // Sidebar Wheel Event Chaining for smooth user navigation
    const sidebars = document.querySelectorAll('.panel-sidebar, aside[id$="-sidebar"]');
    sidebars.forEach(s => {
        s.addEventListener('wheel', (e) => {
            const nav = s.querySelector('nav');
            if (!nav) return;
            const isNavScrollable = nav.scrollHeight > nav.clientHeight;
            if (!isNavScrollable) {
                window.scrollBy({ top: e.deltaY, behavior: 'auto' });
            } else {
                const atTop = nav.scrollTop <= 0 && e.deltaY < 0;
                const atBottom = nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 1 && e.deltaY > 0;
                if (atTop || atBottom) {
                    window.scrollBy({ top: e.deltaY, behavior: 'auto' });
                }
            }
        }, { passive: true });
    });
});
