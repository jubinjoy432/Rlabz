/**
 * RLabZ Dynamic Statistics Loader
 * Fetches accurate, real-time project metrics directly from the DB
 * Animates numbers and populates the annual completed projects breakdown
 */
(function() {
    function initStatsSection() {
        const section = document.getElementById('rlabzStatsSection');
        if (!section) return;

        const statCompleted = document.getElementById('rlabzStatCompleted');
        const statInDev = document.getElementById('rlabzStatInDev');
        const statTotal = document.getElementById('rlabzStatTotal');
        const statMembers = document.getElementById('rlabzStatMembers');
        const yearGrid = document.getElementById('rlabzYearStatsGrid');

        // Fetch dynamic stats from database API
        fetch('/admin/api/public_stats.php')
            .then(res => {
                if (!res.ok) throw new Error('Network response not ok');
                return res.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Update data-targets with real database values
                    if (statCompleted) statCompleted.setAttribute('data-target', data.completed_projects);
                    if (statInDev) statInDev.setAttribute('data-target', data.in_dev_projects);
                    if (statTotal) statTotal.setAttribute('data-target', data.total_projects);
                    if (statMembers) statMembers.setAttribute('data-target', data.total_members);

                    // Update Year Breakdown from DB
                    if (yearGrid && Array.isArray(data.year_stats) && data.year_stats.length > 0) {
                        yearGrid.innerHTML = data.year_stats.map(row => {
                            const percent = row.total > 0 ? Math.round((row.completed / row.total) * 100) : 0;
                            const totalLabel = row.total === 1 ? '1 Total Project' : `${row.total} Total Projects`;
                            return `
                                <div class="rlabz-year-pill-item">
                                    <div class="rlabz-year-top-row">
                                        <span class="rlabz-year-label">${escapeHtml(row.year)}</span>
                                        <span class="rlabz-year-completed-badge"><i class="fa-solid fa-check"></i> ${row.completed} Completed</span>
                                    </div>
                                    <div class="rlabz-year-progress-track">
                                        <div class="rlabz-year-progress-fill" style="width: ${percent}%;"></div>
                                    </div>
                                    <div class="rlabz-year-bottom-meta">
                                        <span>${row.in_dev} In Progress</span>
                                        <span>${totalLabel}</span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                }
            })
            .catch(err => {
                console.warn('Could not load dynamic stats from public_stats.php, using fallback DB values:', err);
            })
            .finally(() => {
                // Setup Scroll-Triggered Counter Animation
                setupScrollObserver(section);
            });
    }

    function setupScrollObserver(section) {
        let hasAnimated = false;

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !hasAnimated) {
                    hasAnimated = true;
                    obs.unobserve(entry.target);
                    animateCounters();
                }
            });
        }, { threshold: 0.25 });

        observer.observe(section);
    }

    function animateCounters() {
        const counters = document.querySelectorAll('#rlabzStatsSection .rlabz-counter');
        const duration = 1600; // ms

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'), 10) || 0;
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Ease out cubic
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentVal = Math.floor(easeOut * target);

                counter.textContent = currentVal;

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            }

            requestAnimationFrame(updateCounter);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStatsSection);
    } else {
        initStatsSection();
    }
})();
