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
        const prevBtn = document.getElementById('rlabzAnnualPrevBtn');
        const nextBtn = document.getElementById('rlabzAnnualNextBtn');
        const pageIndicator = document.getElementById('rlabzAnnualPageIndicator');

        const apiPath = window.location.pathname.includes('/public/')
            ? '../admin/api/public_stats.php'
            : 'admin/api/public_stats.php';

        let allYearStats = [];
        let currentYearPage = 0;
        const YEARS_PER_PAGE = 8;

        // Attach buttons listeners outside the fetch callback to ensure they always work
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const totalPages = Math.max(1, Math.ceil(allYearStats.length / YEARS_PER_PAGE));
                if (totalPages > 1) {
                    currentYearPage = (currentYearPage > 0) ? currentYearPage - 1 : totalPages - 1;
                    renderYearCards();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const totalPages = Math.max(1, Math.ceil(allYearStats.length / YEARS_PER_PAGE));
                if (totalPages > 1) {
                    currentYearPage = (currentYearPage < totalPages - 1) ? currentYearPage + 1 : 0;
                    renderYearCards();
                }
            });
        }

        // Fetch dynamic stats from database API
        fetch(apiPath)
            .then(res => {
                if (!res.ok) throw new Error('Network response not ok');
                return res.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Update data-targets and text with real database values
                    if (statCompleted) {
                        statCompleted.setAttribute('data-target', data.completed_projects);
                        statCompleted.textContent = data.completed_projects;
                    }
                    if (statInDev) {
                        statInDev.setAttribute('data-target', data.in_dev_projects);
                        statInDev.textContent = data.in_dev_projects;
                    }
                    if (statTotal) {
                        statTotal.setAttribute('data-target', data.total_projects);
                        statTotal.textContent = data.total_projects;
                    }
                    if (statMembers) {
                        statMembers.setAttribute('data-target', data.total_members);
                        statMembers.textContent = data.total_members;
                    }

                    allYearStats = Array.isArray(data.year_stats) ? data.year_stats : [];
                    renderYearCards();
                } else {
                    setStatsZero();
                }
            })
            .catch(err => {
                console.warn('Could not load dynamic stats from public_stats.php:', err);
                setStatsZero();
            })
            .finally(() => {
                setupScrollObserver(section);
            });

        function renderYearCards() {
            if (!yearGrid) return;

            if (!allYearStats || allYearStats.length === 0) {
                yearGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 2rem; font-size: 0.95rem;">
                        <i class="fa-solid fa-database" style="margin-right: 0.5rem; opacity: 0.7;"></i> No annual data available.
                    </div>
                `;
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
                if (pageIndicator) pageIndicator.textContent = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(allYearStats.length / YEARS_PER_PAGE));
            if (currentYearPage >= totalPages) currentYearPage = totalPages - 1;
            if (currentYearPage < 0) currentYearPage = 0;

            const startIndex = currentYearPage * YEARS_PER_PAGE;
            const displayedYears = allYearStats.slice(startIndex, startIndex + YEARS_PER_PAGE);

            yearGrid.innerHTML = displayedYears.map(row => {
                const percent = row.total > 0 ? Math.round((row.completed / row.total) * 100) : 0;
                const totalLabel = row.total === 1 ? '1 Total Project' : `${row.total} Total Projects`;
                const inProgressLabel = `${row.in_dev} In Progress`;

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
                            <span>${inProgressLabel}</span>
                            <span>${totalLabel}</span>
                        </div>
                    </div>
                `;
            }).join('');

            // Update navigation controls
            if (totalPages > 1) {
                if (prevBtn) prevBtn.disabled = false;
                if (nextBtn) nextBtn.disabled = false;
                if (pageIndicator) pageIndicator.textContent = `Page ${currentYearPage + 1} of ${totalPages}`;
            } else {
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
                if (pageIndicator) pageIndicator.textContent = '';
            }
        }

        function setStatsZero() {
            if (statCompleted) {
                statCompleted.setAttribute('data-target', '0');
                statCompleted.textContent = '0';
            }
            if (statInDev) {
                statInDev.setAttribute('data-target', '0');
                statInDev.textContent = '0';
            }
            if (statTotal) {
                statTotal.setAttribute('data-target', '0');
                statTotal.textContent = '0';
            }
            if (statMembers) {
                statMembers.setAttribute('data-target', '0');
                statMembers.textContent = '0';
            }
            if (yearGrid) {
                yearGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.95rem;">
                        <i class="fa-solid fa-database" style="margin-right: 0.5rem; opacity: 0.7;"></i> No statistics available at this moment.
                    </div>
                `;
            }
        }
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
