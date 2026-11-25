/**
 * RLabZ Theme Controller (Dark / Light Mode)
 * Applies theme preference to public project & internship pages and admin panel
 * (Excludes the main homepage as requested).
 */

(function () {
    'use strict';

    // 1. Determine Initial Theme
    const savedTheme = localStorage.getItem('rlabz_theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const currentTheme = savedTheme ? savedTheme : (prefersDark ? 'dark' : 'light');

    // Apply immediately to document element to avoid flashing
    if (currentTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-mode');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.remove('dark-mode');
    }

    function updateLogos(isDark) {
        const logoImgs = document.querySelectorAll('.nav-logo img.logo-image');
        logoImgs.forEach(img => {
            const src = img.getAttribute('src');
            if (!src) return;
            if (isDark) {
                img.src = src.replace('rlabz-logo-dark.png', 'rlabz-logo-white.png');
            } else {
                img.src = src.replace('rlabz-logo-white.png', 'rlabz-logo-dark.png');
            }
        });
    }

    // When DOM loads, apply to body, sync buttons, initialize navbar and back to top
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(currentTheme, false);
        initThemeButtons();
        updateLogos(currentTheme === 'dark');
        initUniversalNavbar();
        initBackToTop();
    });

    function applyTheme(theme, save = true) {
        const isDark = (theme === 'dark');
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('dark-mode');
            if (document.body) document.body.classList.add('dark-mode');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.classList.remove('dark-mode');
            if (document.body) document.body.classList.remove('dark-mode');
        }

        if (save) {
            localStorage.setItem('rlabz_theme', theme);
        }

        // Update all toggle button icons
        const buttons = document.querySelectorAll('.theme-toggle-btn');
        buttons.forEach(btn => {
            btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            btn.innerHTML = isDark 
                ? '<i class="fa-solid fa-sun"></i>' 
                : '<i class="fa-solid fa-moon"></i>';
        });

        // Sync all Uiverse animated theme switch checkboxes
        const checkboxes = document.querySelectorAll('.theme-switch-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = isDark;
        });

        // Update logos to official dark/light asset
        updateLogos(isDark);

        // Dispatch custom event if any charts or components need to re-render
        window.dispatchEvent(new CustomEvent('rlabz-theme-changed', { detail: { theme } }));
    }

    function toggleTheme() {
        const isCurrentlyDark = document.documentElement.classList.contains('dark-mode') || 
                                (document.body && document.body.classList.contains('dark-mode'));
        const nextTheme = isCurrentlyDark ? 'light' : 'dark';
        applyTheme(nextTheme, true);
    }

    function initThemeButtons() {
        const buttons = document.querySelectorAll('.theme-toggle-btn');
        const isDark = document.documentElement.classList.contains('dark-mode') || 
                       (document.body && document.body.classList.contains('dark-mode'));

        buttons.forEach(btn => {
            btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            btn.innerHTML = isDark 
                ? '<i class="fa-solid fa-sun"></i>' 
                : '<i class="fa-solid fa-moon"></i>';
            btn.removeEventListener('click', toggleTheme);
            btn.addEventListener('click', toggleTheme);
        });

        // Initialize and bind Uiverse checkbox switches
        const checkboxes = document.querySelectorAll('.theme-switch-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = isDark;
            cb.removeEventListener('change', handleCheckboxChange);
            cb.addEventListener('change', handleCheckboxChange);
        });
    }

    function handleCheckboxChange(e) {
        applyTheme(e.target.checked ? 'dark' : 'light', true);
    }

    function initUniversalNavbar() {
        const navLinksContainer = document.querySelector('.nav-links-container');
        const links = document.querySelectorAll('.nav-link');
        const pill = document.querySelector('.sliding-pill');

        if (navLinksContainer && links.length && pill) {
            function updatePill(link) {
                if (!link) return;
                const linkRect = link.getBoundingClientRect();
                const containerRect = navLinksContainer.getBoundingClientRect();
                if (linkRect.width > 0) {
                    pill.style.width = `${linkRect.width}px`;
                    pill.style.left = `${linkRect.left - containerRect.left}px`;
                    navLinksContainer.classList.add('pill-ready');
                }
            }

            const getActiveLink = () => document.querySelector('.nav-link.active') || links[0];
            let activeLink = getActiveLink();

            // Set initial position
            setTimeout(() => {
                activeLink = getActiveLink();
                updatePill(activeLink);
            }, 50);

            // Recheck after fonts/styles load
            setTimeout(() => {
                activeLink = getActiveLink();
                updatePill(activeLink);
            }, 300);

            window.addEventListener('resize', () => {
                updatePill(getActiveLink());
            });

            links.forEach(link => {
                link.addEventListener('mouseenter', () => updatePill(link));
                link.addEventListener('click', () => {
                    links.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    activeLink = link;
                    updatePill(link);
                });
            });

            navLinksContainer.addEventListener('mouseleave', () => {
                activeLink = getActiveLink();
                updatePill(activeLink);
            });
        }

        // Universal Mobile Menu Toggle
        const menuToggleBtn = document.querySelector('.menu-toggle-btn');
        if (menuToggleBtn && navLinksContainer && !menuToggleBtn.dataset.themePillBound) {
            menuToggleBtn.dataset.themePillBound = '1';
            menuToggleBtn.addEventListener('click', () => {
                menuToggleBtn.classList.toggle('open');
                navLinksContainer.classList.toggle('open');
            });

            links.forEach(link => {
                link.addEventListener('click', () => {
                    menuToggleBtn.classList.remove('open');
                    navLinksContainer.classList.remove('open');
                });
            });
        }
    }

    function initBackToTop() {
        const btn = document.getElementById('backToTop') || document.querySelector('.rlabz-back-to-top');
        if (!btn || btn.dataset.bttBound) return;
        btn.dataset.bttBound = '1';

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(() => {
                    if (window.scrollY > 300) {
                        btn.classList.add('visible');
                    } else {
                        btn.classList.remove('visible');
                    }
                    ticking = false;
                });
            }
        }, { passive: true });

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.lenis && typeof window.lenis.scrollTo === 'function') {
                window.lenis.scrollTo(0, { duration: 1.2 });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // Global helper
    window.toggleRLabZTheme = toggleTheme;
    window.applyRLabZTheme = applyTheme;
    window.initUniversalNavbar = initUniversalNavbar;
    window.initBackToTop = initBackToTop;
})();
