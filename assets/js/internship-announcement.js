/**
 * Standalone Floating Internship Announcement Script
 * Ensures the floating orange announcement tab is visible across public pages
 * (such as projects.html or index.html if the 3D robot is inactive),
 * perfectly matching the reference screenshot.
 *
 * Rules:
 *  - Hidden when 0 published/available internships.
 *  - Shows "Internship Opportunities" (if 1) or "Internship Opportunities (N)" (if > 1).
 *  - Dismissible via close 'x' icon for current page view (reappears on page refresh).
 *  - Opens public/internships.html on click.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', initAnnouncement);

    function initAnnouncement() {
        // If robot-popup is already active on the page and visible, let robot handle it
        if (document.getElementById('robot-popup')) {
            return;
        }

        const isPublicDir = window.location.pathname.includes('/public/');
        const apiPath = isPublicDir ? '../admin/api/public_internships.php?action=count' : 'admin/api/public_internships.php?action=count';
        const targetUrl = isPublicDir ? 'internships.html' : 'public/internships.html';

        fetch(apiPath)
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success || data.count <= 0) {
                    return;
                }

                // Check again in case robot-popup appeared during fetch
                if (document.getElementById('robot-popup')) {
                    return;
                }

                const labelText = data.label || (data.count === 1 ? 'Internship Opportunities' : `Internship Opportunities (${data.count})`);

                const btn = document.createElement('div');
                btn.id = 'rlabz-floating-internship-btn';
                btn.className = 'rlabz-floating-internship-btn';
                btn.setAttribute('role', 'button');
                btn.setAttribute('tabindex', '0');
                btn.setAttribute('aria-label', `${labelText}. Click to view opportunities`);
                btn.innerHTML = `
                    <span class="internship-btn-label">${labelText}</span>
                    <button type="button" class="internship-btn-close" aria-label="Dismiss announcement">&times;</button>
                `;

                // Add to page
                document.body.appendChild(btn);

                // Interactions
                btn.addEventListener('click', (e) => {
                    if (e.target.closest('.internship-btn-close')) {
                        e.stopPropagation();
                        e.preventDefault();
                        btn.classList.add('dismissed');
                        setTimeout(() => btn.remove(), 300);
                        return;
                    }
                    window.location.href = targetUrl;
                });
            })
            .catch(err => {
                // Fail silently if network/database error
                console.warn('Could not load internship announcement count:', err);
            });
    }
})();
