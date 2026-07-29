/**
 * Projects Gallery Page — JavaScript
 * Handles search, filtering, card rendering, and load more
 */

(function () {
    'use strict';

    const ITEMS_PER_PAGE = 9;
    let displayedCount = 0;
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
    const loadMoreBtn = document.getElementById('projectsLoadMore');
    const activeFiltersContainer = document.getElementById('activeFiltersContainer');

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
            searchClear.classList.toggle('visible', searchQuery.length > 0);
            applyFilters();
        });

        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            searchQuery = '';
            searchClear.classList.remove('visible');
            applyFilters();
        });
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
        const menu = document.querySelectorAll(`[data-filter-type="${type}"][data-value="${value}"]`);
        menu.forEach(el => el.classList.remove('selected'));
        applyFilters();
    }

    function clearAllFilters() {
        Object.keys(activeFilters).forEach(key => {
            activeFilters[key] = [];
        });
        document.querySelectorAll('.filter-option').forEach(el => el.classList.remove('selected'));
        searchInput.value = '';
        searchQuery = '';
        searchClear.classList.remove('visible');
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

        displayedCount = 0;
        renderCards();
        renderActiveFilters();
        updateResultsCount();
    }

    function renderCards() {
        grid.innerHTML = '';
        const toShow = filteredProjects.slice(0, displayedCount + ITEMS_PER_PAGE);
        displayedCount = toShow.length;

        if (toShow.length === 0) {
            grid.innerHTML = `
                <div class="projects-no-results">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No projects found</h3>
                    <p>Try adjusting your search or filters to find what you're looking for.</p>
                </div>
            `;
            loadMoreBtn.classList.add('hidden');
            return;
        }

        toShow.forEach((project, index) => {
            const card = createCardElement(project, index);
            grid.appendChild(card);
        });

        // Show/hide load more
        if (displayedCount >= filteredProjects.length) {
            loadMoreBtn.classList.add('hidden');
        } else {
            loadMoreBtn.classList.remove('hidden');
        }
    }

    function createCardElement(project, index) {
        const card = document.createElement('article');
        card.className = 'project-gallery-card';
        card.style.animationDelay = `${index * 0.06}s`;
        card.style.cursor = 'pointer';
        card.onclick = () => {
            window.location.href = `project-details.html?id=${project.id}`;
        };

        const statusClass = project.status === 'Deployed' ? 'status-deployed' :
            project.status === 'In Development' ? 'status-in-development' : 'status-completed';

        const techTags = project.tech.slice(0, 4).map(t =>
            `<span class="pg-card-tag">${t}</span>`
        ).join('');
        const extraTechCount = project.tech.length - 4;
        const techExtra = extraTechCount > 0 ? `<span class="pg-card-tag">+${extraTechCount}</span>` : '';

        // Team avatars
        let teamHTML = '';
        if (project.team && project.team.length > 0) {
            const avatars = project.team.slice(0, 3).map(m =>
                `<img src="${m.photo || '../assets/images/rz-logo.webp'}" alt="${m.name}" class="pg-card-team-avatar" loading="lazy" onerror="this.src='../assets/images/rz-logo.webp'">`
            ).join('');
            const teamCountText = project.team.length > 3 ? `+${project.team.length - 3} more` : `${project.team.length} member${project.team.length > 1 ? 's' : ''}`;
            teamHTML = `
                <div class="pg-card-team">
                    <div class="pg-card-team-avatars">${avatars}</div>
                    <span class="pg-card-team-count">${teamCountText}</span>
                </div>
            `;
        } else {
            teamHTML = `<div class="pg-card-team"><span class="pg-card-team-count">${project.department || 'RLabZ'}</span></div>`;
        }

        card.innerHTML = `
            <div class="pg-card-image">
                <img src="${project.thumbnail}" alt="${project.title}" loading="lazy" onerror="this.src='../assets/images/rz-logo.webp'">
                <span class="pg-card-year-badge">${project.year}</span>
                <span class="pg-card-status-badge ${statusClass}">${project.status}</span>
            </div>
            <div class="pg-card-content">
                <h3 class="pg-card-title">${project.title}</h3>
                <p class="pg-card-desc">${project.shortDescription}</p>
                <div class="pg-card-tags">${techTags}${techExtra}</div>
                <div class="pg-card-footer">
                    ${teamHTML}
                    <span class="pg-card-view-btn">
                        View <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
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
        resultsCount.innerHTML = `Showing <span>${displayedCount}</span> of <span>${filteredProjects.length}</span> projects`;
    }

    // Load More
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            const start = displayedCount;
            const end = Math.min(displayedCount + ITEMS_PER_PAGE, filteredProjects.length);

            for (let i = start; i < end; i++) {
                const card = createCardElement(filteredProjects[i], i - start);
                grid.appendChild(card);
            }

            displayedCount = end;
            updateResultsCount();

            if (displayedCount >= filteredProjects.length) {
                loadMoreBtn.classList.add('hidden');
            }
        });
    }

    // Expose for inline handlers
    window.__removeFilter = removeFilter;
    window.__clearAllFilters = clearAllFilters;

})();
