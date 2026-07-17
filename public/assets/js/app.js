(() => {
    'use strict';

    const path = window.location.pathname;
    document.querySelectorAll('a[href]').forEach((link) => {
        const href = link.getAttribute('href');
        if (href && href !== '/' && (path === href || path.startsWith(`${href}/`))) {
            link.setAttribute('aria-current', 'page');
        } else if (href === '/' && path === '/') {
            link.setAttribute('aria-current', 'page');
        }
    });

    const navToggle = document.querySelector('[data-nav-toggle]');
    const navigation = document.querySelector('[data-navigation]');
    if (navToggle && navigation) {
        navToggle.addEventListener('click', () => {
            const open = navToggle.getAttribute('aria-expanded') !== 'true';
            navToggle.setAttribute('aria-expanded', String(open));
            navigation.classList.toggle('is-open', open);
        });
    }

    const adminToggle = document.querySelector('[data-admin-toggle]');
    const adminSidebar = document.querySelector('[data-admin-sidebar]');
    const adminOverlay = document.querySelector('[data-admin-overlay]');
    const setAdminMenu = (open) => {
        if (!adminToggle || !adminSidebar || !adminOverlay) return;
        adminToggle.setAttribute('aria-expanded', String(open));
        adminSidebar.classList.toggle('is-open', open);
        adminOverlay.classList.toggle('is-open', open);
    };
    adminToggle?.addEventListener('click', () => setAdminMenu(adminToggle.getAttribute('aria-expanded') !== 'true'));
    adminOverlay?.addEventListener('click', () => setAdminMenu(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setAdminMenu(false);
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm') || 'Xác nhận thực hiện thao tác này?')) {
                event.preventDefault();
            }
        });
    });
})();
