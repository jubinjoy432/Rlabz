/**
 * Public Internship Listing Page Script
 * Fetches published, active opportunities from admin/api/public_internships.php?action=list
 * Provides live client-side search and filters for Category, Work Mode, and Stipend.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('internshipsLoader');
    const grid = document.getElementById('internshipsGrid');
    const emptyState = document.getElementById('internshipsEmptyState');
    const countText = document.getElementById('internshipsCountText');
    const searchInput = document.getElementById('internshipSearchInput');
    const searchClear = document.getElementById('internshipSearchClear');
    const filterCategory = document.getElementById('filterCategory');
    const filterWorkMode = document.getElementById('filterWorkMode');
    const filterStipend = document.getElementById('filterStipend');
    const filterDeadline = document.getElementById('filterDeadline');
    const filterResetBtn = document.getElementById('filterResetBtn');
    const emptyResetBtn = document.getElementById('emptyResetBtn');

    let allInternships = [];
    let activeCategories = [];

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Helper: Format date
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Helper: Compute dynamic deadline urgency based on days remaining
    function getDeadlineInfo(deadlineStr, apiDaysLeft) {
        if (!deadlineStr) {
            return { label: 'Open', badgeClass: 'badge-open', daysLeft: 999, tag: 'open', icon: 'fa-regular fa-calendar-check' };
        }

        let days = (typeof apiDaysLeft === 'number') ? apiDaysLeft : null;
        if (days === null) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const dl = new Date(deadlineStr);
            dl.setHours(23, 59, 59, 999);
            days = Math.floor((dl.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
        }

        if (days < 0) {
            return {
                label: 'Expired',
                badgeClass: 'badge-expired',
                daysLeft: days,
                tag: 'expired',
                icon: 'fa-solid fa-circle-exclamation'
            };
        } else if (days === 0) {
            return {
                label: 'Expires Today',
                badgeClass: 'badge-urgent',
                daysLeft: 0,
                tag: 'today',
                icon: 'fa-solid fa-hourglass-end'
            };
        } else if (days === 1) {
            return {
                label: '1 Day Left',
                badgeClass: 'badge-urgent',
                daysLeft: 1,
                tag: 'urgent',
                icon: 'fa-solid fa-hourglass-half'
            };
        } else if (days <= 3) {
            return {
                label: `Ending Soon (${days}d left)`,
                badgeClass: 'badge-urgent',
                daysLeft: days,
                tag: 'urgent',
                icon: 'fa-solid fa-hourglass-half'
            };
        } else if (days <= 7) {
            return {
                label: `Closing Soon (${days}d left)`,
                badgeClass: 'badge-warning',
                daysLeft: days,
                tag: 'closing_soon',
                icon: 'fa-solid fa-clock'
            };
        } else if (days <= 14) {
            return {
                label: `${days} days left`,
                badgeClass: 'badge-notice',
                daysLeft: days,
                tag: 'notice',
                icon: 'fa-regular fa-calendar'
            };
        } else {
            return {
                label: `Actively Open (${days}d left)`,
                badgeClass: 'badge-open',
                daysLeft: days,
                tag: 'open',
                icon: 'fa-regular fa-calendar-check'
            };
        }
    }

    // Render single internship card
    function createCardElement(item) {
        const a = document.createElement('a');
        a.className = 'internship-card';
        a.href = `internship-details.html?id=${encodeURIComponent(item.id)}`;

        // Mode badge class
        const modeClass = item.work_mode ? `mode-${item.work_mode.toLowerCase().replace(/[^a-z]/g, '')}` : '';

        // Company Logo or Fallback Icon
        let logoHtml = `<i class="fa-solid fa-building"></i>`;
        if (item.company_logo) {
            logoHtml = `<img src="${escapeHtml(item.company_logo)}" alt="${escapeHtml(item.company_name)} logo" loading="lazy" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\\'fa-solid fa-building\\'></i>';">`;
        }

        // Specs items
        const specs = [];
        if (item.duration) {
            specs.push(`<div class="ic-spec-item"><i class="fa-regular fa-clock"></i> <span>${escapeHtml(item.duration)}</span></div>`);
        }
        if (item.stipend_type) {
            const stipendDisplay = (item.stipend_type === 'Paid' && item.stipend_amount) 
                ? escapeHtml(item.stipend_amount) 
                : escapeHtml(item.stipend_type);
            specs.push(`<div class="ic-spec-item"><i class="fa-solid fa-wallet"></i> <span>${stipendDisplay}</span></div>`);
        }
        if (item.location) {
            specs.push(`<div class="ic-spec-item"><i class="fa-solid fa-location-dot"></i> <span>${escapeHtml(item.location)}</span></div>`);
        }

        const dlInfo = getDeadlineInfo(item.raw_deadline || item.application_deadline, item.days_left);
        const urgentClass = (dlInfo.daysLeft <= 3) ? 'urgent' : '';

        a.innerHTML = `
            <div class="ic-company-row">
                <div class="ic-company-logo">
                    ${logoHtml}
                </div>
                <div class="ic-company-info">
                    <div class="ic-company-name">${escapeHtml(item.company_name)}</div>
                    ${item.company_location ? `<div class="ic-company-location"><i class="fa-solid fa-map-pin"></i> ${escapeHtml(item.company_location)}</div>` : ''}
                </div>
                ${item.work_mode ? `<span class="ic-workmode-badge ${modeClass}">${escapeHtml(item.work_mode)}</span>` : ''}
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                ${item.category ? `<span class="ic-category-pill" style="margin-bottom: 0;">${escapeHtml(item.category)}</span>` : '<span></span>'}
                <span class="deadline-badge ${dlInfo.badgeClass}">
                    <i class="${dlInfo.icon}"></i> ${dlInfo.label}
                </span>
            </div>

            <h3 class="ic-title">${escapeHtml(item.internship_title)}</h3>

            <p class="ic-desc">${escapeHtml(item.short_description || item.description || '')}</p>

            ${specs.length > 0 ? `<div class="ic-specs-row">${specs.join('')}</div>` : ''}

            <div class="ic-footer">
                <div class="ic-deadline ${urgentClass}">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <span>Apply by: <strong>${formatDate(item.application_deadline)}</strong></span>
                </div>
                <span class="ic-view-btn">
                    View Details <i class="fa-solid fa-arrow-right"></i>
                </span>
            </div>
        `;

        return a;
    }

    // Apply filters & search
    function filterAndRender() {
        const q = (searchInput.value || '').trim().toLowerCase();
        const cat = (filterCategory.value || '').trim();
        const mode = (filterWorkMode.value || '').trim();
        const stipend = (filterStipend.value || '').trim();
        const deadline = (filterDeadline ? filterDeadline.value : '').trim();

        // Toggle clear search button
        if (searchClear) {
            if (q.length > 0) {
                searchClear.classList.add('visible');
            } else {
                searchClear.classList.remove('visible');
            }
        }

        const filtered = allInternships.filter(item => {
            // Text search
            if (q.length > 0) {
                const combined = [
                    item.internship_title,
                    item.company_name,
                    item.category,
                    item.short_description,
                    item.location,
                    item.requirements
                ].join(' ').toLowerCase();

                if (!combined.includes(q)) return false;
            }

            // Category
            if (cat && item.category !== cat) return false;

            // Work mode
            if (mode && item.work_mode !== mode) return false;

            // Stipend
            if (stipend && item.stipend_type !== stipend) return false;

            // Deadline Urgency
            if (deadline) {
                const dl = getDeadlineInfo(item.raw_deadline || item.application_deadline, item.days_left);
                if (deadline === 'today' && dl.daysLeft !== 0) return false;
                if (deadline === 'urgent' && (dl.daysLeft < 0 || dl.daysLeft > 3)) return false;
                if (deadline === 'closing_soon' && (dl.daysLeft < 0 || dl.daysLeft > 7)) return false;
                if (deadline === 'month' && (dl.daysLeft < 0 || dl.daysLeft > 30)) return false;
                if (deadline === 'open' && dl.daysLeft <= 7) return false;
            }

            return true;
        });

        // Update count text
        if (filtered.length === 1) {
            countText.innerHTML = `Showing <span>1</span> available opportunity`;
        } else {
            countText.innerHTML = `Showing <span>${filtered.length}</span> of <span>${allInternships.length}</span> opportunities`;
        }

        // Render Cards or Empty State
        grid.innerHTML = '';
        if (filtered.length === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
        } else {
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
            filtered.forEach(item => {
                grid.appendChild(createCardElement(item));
            });
        }
    }

    // Fetch opportunities from backend
    async function loadOpportunities() {
        try {
            const res = await fetch('../admin/api/public_internships.php?action=list');
            const data = await res.json();

            const list = Array.isArray(data.data) ? data.data : (Array.isArray(data.internships) ? data.internships : null);
            if (data.success && list !== null) {
                allInternships = list;
                activeCategories = data.categories || [];

                // Populate categories select
                filterCategory.innerHTML = '<option value="">All Categories</option>';
                activeCategories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    filterCategory.appendChild(opt);
                });

                filterAndRender();
            } else {
                allInternships = [];
                filterAndRender();
            }
        } catch (err) {
            console.error('Error fetching internships:', err);
            countText.textContent = 'Unable to load opportunities.';
            grid.style.display = 'none';
            emptyState.style.display = 'block';
        } finally {
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => { loader.style.display = 'none'; }, 250);
            }
        }
    }

    // Event listeners
    if (searchInput) searchInput.addEventListener('input', filterAndRender);
    if (searchClear) {
        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            filterAndRender();
            searchInput.focus();
        });
    }

    if (filterCategory) filterCategory.addEventListener('change', filterAndRender);
    if (filterWorkMode) filterWorkMode.addEventListener('change', filterAndRender);
    if (filterStipend) filterStipend.addEventListener('change', filterAndRender);
    if (filterDeadline) filterDeadline.addEventListener('change', filterAndRender);

    const resetAction = () => {
        if (searchInput) searchInput.value = '';
        if (filterCategory) filterCategory.value = '';
        if (filterWorkMode) filterWorkMode.value = '';
        if (filterStipend) filterStipend.value = '';
        if (filterDeadline) filterDeadline.value = '';
        filterAndRender();
    };

    if (filterResetBtn) filterResetBtn.addEventListener('click', resetAction);
    if (emptyResetBtn) emptyResetBtn.addEventListener('click', resetAction);

    // Initial load
    loadOpportunities();
});
