/**
 * Projects Gallery Page — JavaScript
 * Handles search, filtering, card rendering, and numbered pagination
 */

(function () {
    'use strict';

    const ITEMS_PER_PAGE = 9;
    let currentPage = 1;
    let filteredProjects = [];
    let activeFilters = {
        year: [],
        tech: [],
        category: [],
        status: []
    };
    let searchQuery = '';

    // DOM Elements
    const grid = document.getElementById('projectsGrid');
    const searchInput = document.getElementById('projectsSearchInput');
    const searchClear = document.getElementById('projectsSearchClear');
    const resultsCount = document.getElementById('projectsResultsCount');
    const activeFiltersContainer = document.getElementById('activeFiltersContainer');
    const pagination = document.getElementById('projectsPagination');
    const pgPrev = document.getElementById('pgPrev');
    const pgNext = document.getElementById('pgNext');
    const pgPageNumbers = document.getElementById('pgPageNumbers');

    // Initialize when data is ready
    let initialized = false;
    window.addEventListener('projectsLoaded', () => {
        if (!initialized) {
            initialized = true;
            init();
        }
    });
    if (window.RLABZ_PROJECTS && window.RLABZ_PROJECTS.length > 0 && !initialized) {
        initialized = true;
        init();
    }

    function init() {
        if (!grid) return;

        populateFilterDropdowns();
        setupSearch();
        setupFilterDropdowns();
        applyFilters();

        if (pgPrev) pgPrev.addEventListener('click', () => goToPage(currentPage - 1));
        if (pgNext) pgNext.addEventListener('click', () => goToPage(currentPage + 1));
    }

    // ---- Populate Filter Dropdowns ----
    function populateFilterDropdowns() {
        populateDropdown('yearFilterMenu', getProjectYears(), 'year');
        populateDropdown('techFilterMenu', getProjectTechnologies(), 'tech');
        populateDropdown('categoryFilterMenu', getProjectCategories(), 'category');
        populateDropdown('statusFilterMenu', getProjectStatuses(), 'status');
    }

    function populateDropdown(menuId, items, filterType) {
        const menu = document.getElementById(menuId);
        if (!menu) return;

        menu.innerHTML = '';
        items.forEach(item => {
            const option = document.createElement('div');
            option.className = 'filter-option';
            option.setAttribute('data-value', item);
            option.setAttribute('data-filter-type', filterType);
            option.innerHTML = `
                <span class="check-icon"><i class="fa-solid fa-check"></i></span>
                <span>${item}</span>
            `;
            option.addEventListener('click', () => toggleFilter(filterType, item, option));
            menu.appendChild(option);
        });
    }

    // ---- Search ----
    function setupSearch() {
        if (!searchInput) return;

        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.trim().toLowerCase();
            if (searchClear) searchClear.classList.toggle('visible', searchQuery.length > 0);
            applyFilters();
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchQuery = '';
                searchClear.classList.remove('visible');
                applyFilters();
            });
        }
    }

    // ---- Filter Dropdowns ----
    function setupFilterDropdowns() {
        document.querySelectorAll('.filter-dropdown-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                const wasOpen = menu.classList.contains('open');

                // Close all
                document.querySelectorAll('.filter-dropdown-menu').forEach(m => m.classList.remove('open'));
                document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('active'));

                if (!wasOpen) {
                    menu.classList.add('open');
                    btn.classList.add('active');
                }
            });
        });

        // Close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.filter-dropdown-menu').forEach(m => m.classList.remove('open'));
            document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('active'));
        });

        // Prevent menu click from closing
        document.querySelectorAll('.filter-dropdown-menu').forEach(menu => {
            menu.addEventListener('click', (e) => e.stopPropagation());
        });
    }

    function toggleFilter(type, value, optionEl) {
        const idx = activeFilters[type].indexOf(value);
        if (idx === -1) {
            activeFilters[type].push(value);
            optionEl.classList.add('selected');
        } else {
            activeFilters[type].splice(idx, 1);
            optionEl.classList.remove('selected');
        }
        applyFilters();
    }

    function removeFilter(type, value) {
        const idx = activeFilters[type].indexOf(value);
        if (idx !== -1) {
            activeFilters[type].splice(idx, 1);
        }
        // Update dropdown UI
        document.querySelectorAll(`[data-filter-type="${type}"][data-value="${value}"]`)
            .forEach(el => el.classList.remove('selected'));
        applyFilters();
    }

    function clearAllFilters() {
        Object.keys(activeFilters).forEach(key => {
            activeFilters[key] = [];
        });
        document.querySelectorAll('.filter-option').forEach(el => el.classList.remove('selected'));
        if (searchInput) searchInput.value = '';
        searchQuery = '';
        if (searchClear) searchClear.classList.remove('visible');
        applyFilters();
    }

    // ---- Apply Filters & Render ----
    function applyFilters() {
        filteredProjects = RLABZ_PROJECTS.filter(project => {
            // Search
            if (searchQuery) {
                const searchable = [
                    project.title,
                    project.shortDescription,
                    project.year,
                    ...project.tech,
                    ...(project.team || []).map(t => t.name),
                    project.category || '',
                    project.department || ''
                ].join(' ').toLowerCase();

                if (!searchable.includes(searchQuery)) return false;
            }

            // Year filter
            if (activeFilters.year.length > 0 && !activeFilters.year.includes(project.year)) return false;

            // Tech filter
            if (activeFilters.tech.length > 0) {
                const hasMatch = project.tech.some(t => activeFilters.tech.includes(t));
                if (!hasMatch) return false;
            }

            // Category filter
            if (activeFilters.category.length > 0 && !activeFilters.category.includes(project.category)) return false;

            // Status filter
            if (activeFilters.status.length > 0 && !activeFilters.status.includes(project.status)) return false;

            return true;
        });

        currentPage = 1;
        renderPage();
        renderActiveFilters();
        updateResultsCount();
    }

    function totalPages() {
        return Math.max(1, Math.ceil(filteredProjects.length / ITEMS_PER_PAGE));
    }

    function goToPage(page) {
        const total = totalPages();
        if (page < 1 || page > total) return;
        currentPage = page;
        renderPage();
        updateResultsCount();
        // Scroll to top of grid
        if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderPage() {
        if (!grid) return;
        grid.innerHTML = '';

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = Math.min(start + ITEMS_PER_PAGE, filteredProjects.length);
        const toShow = filteredProjects.slice(start, end);

        if (toShow.length === 0) {
            grid.innerHTML = `
                <div class="projects-no-results">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No projects found</h3>
                    <p>Try adjusting your search or filters to find what you're looking for.</p>
                </div>
            `;
        } else {
            toShow.forEach((project, index) => {
                const card = createCardElement(project, index);
                grid.appendChild(card);
            });
        }

        renderPagination();
    }

    function renderPagination() {
        if (!pagination || !pgPageNumbers) return;

        const total = totalPages();

        // Hide pagination if only one page
        if (total <= 1) {
            pagination.style.display = 'none';
            return;
        }
        pagination.style.display = 'flex';

        // Prev / Next state
        if (pgPrev) pgPrev.disabled = currentPage === 1;
        if (pgNext) pgNext.disabled = currentPage === total;

        // Build page number buttons with ellipsis
        pgPageNumbers.innerHTML = '';
        const pages = getPageRange(currentPage, total);

        pages.forEach(p => {
            if (p === '...') {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pg-ellipsis';
                ellipsis.textContent = '…';
                pgPageNumbers.appendChild(ellipsis);
            } else {
                const btn = document.createElement('button');
                btn.className = 'pg-page-btn pg-num' + (p === currentPage ? ' active' : '');
                btn.textContent = p;
                btn.setAttribute('aria-label', `Page ${p}`);
                if (p === currentPage) btn.setAttribute('aria-current', 'page');
                btn.addEventListener('click', () => goToPage(p));
                pgPageNumbers.appendChild(btn);
            }
        });
    }

    // Returns an array of page numbers with ellipsis for large page counts
    function getPageRange(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        const pages = [];
        pages.push(1);
        if (current > 3) pages.push('...');
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
            pages.push(i);
        }
        if (current < total - 2) pages.push('...');
        pages.push(total);
        return pages;
    }

    function createCardElement(project, index) {
        const card = document.createElement('a');
        card.href = `project-details.html?id=${project.id}`;
        card.className = 'project-gallery-card';
        card.style.animationDelay = `${index * 0.06}s`;

        const statusClass = project.status === 'Deployed' ? 'status-deployed' :
            project.status === 'In Development' ? 'status-in-development' : 'status-completed';

        const techTags = project.tech.slice(0, 4).map(t =>
            `<span class="pg-card-tag">${t}</span>`
        ).join('');
        const extraTechCount = project.tech.length - 4;
        const techExtra = extraTechCount > 0 ? `<span class="pg-card-tag">+${extraTechCount}</span>` : '';

        card.innerHTML = `
            <div class="pg-card-image">
                <img src="${project.thumbnail}" alt="${project.title}" loading="lazy" onerror="this.src='../assets/images/logo1.png'">
                <span class="pg-card-year-badge">${project.year}</span>
                <span class="pg-card-status-badge ${statusClass}">${project.status}</span>
            </div>
            <div class="pg-card-content">
                <h3 class="pg-card-title">${project.title}</h3>
                <p class="pg-card-desc">${project.shortDescription}</p>
                <div class="pg-card-tags">${techTags}${techExtra}</div>
            </div>
        `;

        return card;
    }

    function renderActiveFilters() {
        if (!activeFiltersContainer) return;

        const chips = [];
        Object.entries(activeFilters).forEach(([type, values]) => {
            values.forEach(val => {
                chips.push({ type, value: val });
            });
        });

        if (chips.length === 0) {
            activeFiltersContainer.innerHTML = '';
            return;
        }

        let html = chips.map(c =>
            `<span class="active-filter-chip">${c.value} <button onclick="window.__removeFilter('${c.type}', '${c.value}')" aria-label="Remove filter">&times;</button></span>`
        ).join('');
        html += `<button class="clear-all-filters" onclick="window.__clearAllFilters()">Clear all</button>`;
        activeFiltersContainer.innerHTML = html;
    }

    function updateResultsCount() {
        if (!resultsCount) return;
        const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const end = Math.min(currentPage * ITEMS_PER_PAGE, filteredProjects.length);
        const showing = filteredProjects.length > 0 ? `${start}–${end}` : '0';
        resultsCount.innerHTML = `Showing <span>${showing}</span> of <span>${filteredProjects.length}</span> projects`;
    }

    // Expose for inline handlers
    window.__removeFilter = removeFilter;
    window.__clearAllFilters = clearAllFilters;

})();
