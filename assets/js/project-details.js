/**
 * Project Detail Page — JavaScript
 * Reads ?id=xxx, renders all sections, handles lightbox
 */

(function () {
    'use strict';

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
        const params = new URLSearchParams(window.location.search);
        const projectId = params.get('id');

        if (!projectId) {
            showNotFound();
            return;
        }

        const project = getProjectById(projectId);
        if (!project) {
            showNotFound();
            return;
        }

        renderPage(project);
        setupLightbox(project);
    }

    function showNotFound() {
        const wrapper = document.getElementById('pdContentArea');
        if (wrapper) {
            wrapper.innerHTML = `
                <div class="pd-not-found">
                    <i class="fa-solid fa-folder-open"></i>
                    <h2>Project Not Found</h2>
                    <p>The project you're looking for doesn't exist or may have been moved.</p>
                    <a href="projects.html"><i class="fa-solid fa-arrow-left"></i> Back to Projects</a>
                </div>
            `;
        }
    }

    function renderPage(project) {
        // Set page title
        document.title = `${project.title} — RLabz Project Repository`;

        // Hero
        renderHero(project);

        // Navigation
        renderNavigation(project);

        // Main content
        renderOverview(project);
        renderScreenshots(project);
        renderFeatures(project);

        // Sidebar
        renderTechnologies(project);
        renderInfoTable(project);
        renderTeam(project);
        renderFaculty(project);

        // Footer nav
        renderFooterNav(project);
    }

    function renderHero(project) {
        const banner = document.getElementById('pdHeroBanner');
        const title = document.getElementById('pdHeroTitle');
        const tagline = document.getElementById('pdHeroTagline');
        const meta = document.getElementById('pdHeroMeta');

        if (banner) {
            const img = banner.querySelector('img');
            if (img) {
                img.src = project.thumbnail || 'images/rz-logo.webp';
                img.alt = project.title;
                img.onerror = function () { this.src = 'images/rz-logo.webp'; };
            }
        }

        if (title) title.textContent = project.title;
        if (tagline) tagline.textContent = project.shortDescription;

        if (meta) {
            const statusClass = project.status === 'Deployed' ? 'status-deployed' :
                project.status === 'In Development' ? 'status-in-development' : 'status-completed';

            let badges = `<span class="pd-meta-badge pd-meta-year"><i class="fa-regular fa-calendar"></i> ${project.year}</span>`;
            if (project.department) {
                badges += `<span class="pd-meta-badge pd-meta-dept"><i class="fa-solid fa-building-columns"></i> ${project.department}</span>`;
            }
            badges += `<span class="pd-meta-badge pd-meta-status ${statusClass}">${project.status}</span>`;
            if (project.category) {
                badges += `<span class="pd-meta-badge pd-meta-dept"><i class="fa-solid fa-layer-group"></i> ${project.category}</span>`;
            }
            if (project.demoLink) {
                badges += `<a href="${project.demoLink}" target="_blank" class="pd-meta-badge pd-meta-demo" title="Visit the live demo or site"><i class="fa-solid fa-external-link-alt"></i> Visit Site</a>`;
            }
            meta.innerHTML = badges;
        }
    }

    function renderNavigation(project) {
        const { prev, next } = getAdjacentProjects(project.id);

        const prevBtn = document.getElementById('pdPrevBtn');
        const nextBtn = document.getElementById('pdNextBtn');

        if (prevBtn) {
            if (prev) {
                prevBtn.href = `project-details.html?id=${prev.id}`;
                prevBtn.classList.remove('disabled');
            } else {
                prevBtn.classList.add('disabled');
            }
        }

        if (nextBtn) {
            if (next) {
                nextBtn.href = `project-details.html?id=${next.id}`;
                nextBtn.classList.remove('disabled');
            } else {
                nextBtn.classList.add('disabled');
            }
        }
    }

    function renderOverview(project) {
        const container = document.getElementById('pdOverview');
        if (!container) return;

        let html = '';

        if (project.fullDescription) {
            html += `<div class="pd-overview-block"><h4>Description</h4><p>${project.fullDescription}</p></div>`;
        }
        if (project.objectives) {
            html += `<div class="pd-overview-block"><h4>Objectives</h4><p>${project.objectives}</p></div>`;
        }
        if (project.problemStatement) {
            html += `<div class="pd-overview-block"><h4>Problem Statement</h4><p>${project.problemStatement}</p></div>`;
        }
        if (project.expectedOutcome) {
            html += `<div class="pd-overview-block"><h4>Expected Outcome</h4><p>${project.expectedOutcome}</p></div>`;
        }

        container.innerHTML = html || '<p style="color: rgba(255,255,255,0.3);">No description available yet.</p>';
    }

    function renderScreenshots(project) {
        const card = document.getElementById('pdScreenshotsCard');
        const grid = document.getElementById('pdScreenshotsGrid');
        if (!card || !grid) return;

        const screenshots = project.screenshots && project.screenshots.length > 0
            ? project.screenshots
            : (project.thumbnail ? [project.thumbnail] : []);

        if (screenshots.length === 0) {
            card.style.display = 'none';
            return;
        }

        grid.innerHTML = screenshots.map((src, i) =>
            `<div class="pd-screenshot-item" data-index="${i}">
                <img src="${src}" alt="${project.title} screenshot ${i + 1}" loading="lazy" onerror="this.src='images/rz-logo.webp'">
            </div>`
        ).join('');
    }

    function renderFeatures(project) {
        const card = document.getElementById('pdFeaturesCard');
        const grid = document.getElementById('pdFeaturesGrid');
        if (!card || !grid) return;

        if (!project.keyFeatures || project.keyFeatures.length === 0) {
            card.style.display = 'none';
            return;
        }

        grid.innerHTML = project.keyFeatures.map(f =>
            `<div class="pd-feature-item">
                <span class="pd-feature-check"><i class="fa-solid fa-check"></i></span>
                <span class="pd-feature-text">${f}</span>
            </div>`
        ).join('');
    }

    function renderTechnologies(project) {
        const grid = document.getElementById('pdTechGrid');
        if (!grid) return;

        if (!project.tech || project.tech.length === 0) {
            grid.innerHTML = '<p style="color: rgba(255,255,255,0.3);">No technologies listed.</p>';
            return;
        }

        grid.innerHTML = project.tech.map(t =>
            `<span class="pd-tech-badge">${t}</span>`
        ).join('');
    }

    function renderInfoTable(project) {
        const table = document.getElementById('pdInfoTable');
        if (!table) return;

        const rows = [];

        if (project.year) rows.push({ label: 'Academic Year', value: project.year });
        if (project.batch) rows.push({ label: 'Batch', value: project.batch });
        if (project.department) rows.push({ label: 'Department', value: project.department });
        if (project.faculty && project.faculty.name) rows.push({ label: 'Guide', value: project.faculty.name });
        if (project.projectType) rows.push({ label: 'Project Type', value: project.projectType });
        if (project.category) rows.push({ label: 'Category', value: project.category });
        if (project.duration) rows.push({ label: 'Duration', value: project.duration });
        if (project.status) rows.push({ label: 'Status', value: project.status });
        if (project.client) rows.push({ label: 'Client', value: project.client });
        if (project.githubLink) rows.push({ label: 'Repository', value: `<a href="${project.githubLink}" target="_blank" rel="noopener"><i class="fa-brands fa-github"></i> GitHub</a>` });
        if (project.demoLink && project.demoLink !== '#') rows.push({ label: 'Demo Link', value: `<a href="${project.demoLink}" target="_blank" rel="noopener"><i class="fa-solid fa-external-link-alt"></i> Visit</a>` });

        table.innerHTML = rows.map(r =>
            `<div class="pd-info-row">
                <span class="pd-info-label">${r.label}</span>
                <span class="pd-info-value">${r.value}</span>
            </div>`
        ).join('');
    }

    function renderTeam(project) {
        const card = document.getElementById('pdTeamCard');
        const grid = document.getElementById('pdTeamGrid');
        if (!card || !grid) return;

        if (!project.team || project.team.length === 0) {
            grid.innerHTML = '<div class="pd-team-empty">Team information will be updated soon.</div>';
            return;
        }

        grid.innerHTML = project.team.map(member => {
            const photoHtml = member.photo ? `<img src="${member.photo}" alt="${member.name}" class="pd-team-avatar" loading="lazy" onerror="this.style.display='none'">` : '';
            return `
                <div class="pd-team-card">
                    ${photoHtml}
                    <div class="pd-team-name">${member.name}</div>
                    ${member.role ? `<div class="pd-team-role">${member.role}</div>` : ''}
                    ${member.regNo ? `<div class="pd-team-reg">${member.regNo}</div>` : ''}
                </div>
            `;
        }).join('');
    }

    function renderFaculty(project) {
        const card = document.getElementById('pdFacultyCard');
        const container = document.getElementById('pdFacultyContent');
        if (!card || !container) return;

        if (!project.faculty || !project.faculty.name) {
            card.style.display = 'none';
            return;
        }

        const f = project.faculty;
        const photoHtml = f.photo ? `<img src="${f.photo}" alt="${f.name}" class="pd-faculty-avatar" loading="lazy" onerror="this.style.display='none'">` : '';

        container.innerHTML = `
            <div class="pd-faculty-card">
                ${photoHtml}
                <div class="pd-faculty-info">
                    <h4>${f.name}</h4>
                    ${f.designation ? `<div class="pd-faculty-designation">${f.designation}</div>` : ''}
                    <span class="pd-faculty-label"><i class="fa-solid fa-user-tie"></i> Faculty In-Charge</span>
                </div>
            </div>
        `;
    }

    function renderFooterNav(project) {
        const { prev, next } = getAdjacentProjects(project.id);

        const prevLink = document.getElementById('pdFooterPrev');
        const nextLink = document.getElementById('pdFooterNext');

        if (prevLink) {
            if (prev) {
                prevLink.href = `project-details.html?id=${prev.id}`;
                prevLink.innerHTML = `<i class="fa-solid fa-arrow-left"></i> ${prev.title}`;
                prevLink.classList.remove('disabled');
            } else {
                prevLink.classList.add('disabled');
                prevLink.innerHTML = `<i class="fa-solid fa-arrow-left"></i> Previous`;
            }
        }

        if (nextLink) {
            if (next) {
                nextLink.href = `project-details.html?id=${next.id}`;
                nextLink.innerHTML = `${next.title} <i class="fa-solid fa-arrow-right"></i>`;
                nextLink.classList.remove('disabled');
            } else {
                nextLink.classList.add('disabled');
                nextLink.innerHTML = `Next <i class="fa-solid fa-arrow-right"></i>`;
            }
        }
    }

    // ---- Lightbox ----
    function setupLightbox(project) {
        const lightbox = document.getElementById('pdLightbox');
        const lightboxImg = document.getElementById('pdLightboxImg');
        const lightboxClose = document.getElementById('pdLightboxClose');
        const lightboxPrev = document.getElementById('pdLightboxPrev');
        const lightboxNext = document.getElementById('pdLightboxNext');
        const lightboxCounter = document.getElementById('pdLightboxCounter');

        if (!lightbox || !lightboxImg) return;

        const screenshots = project.screenshots && project.screenshots.length > 0
            ? project.screenshots
            : (project.thumbnail ? [project.thumbnail] : []);

        if (screenshots.length === 0) return;

        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            lightboxImg.src = screenshots[currentIndex];
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
            updateCounter();
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigateLightbox(direction) {
            currentIndex = (currentIndex + direction + screenshots.length) % screenshots.length;
            lightboxImg.src = screenshots[currentIndex];
            updateCounter();
        }

        function updateCounter() {
            if (lightboxCounter) {
                lightboxCounter.textContent = `${currentIndex + 1} / ${screenshots.length}`;
            }
        }

        // Click screenshot to open
        document.addEventListener('click', (e) => {
            const item = e.target.closest('.pd-screenshot-item');
            if (item) {
                const idx = parseInt(item.getAttribute('data-index'), 10);
                openLightbox(idx);
            }
        });

        if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
        if (lightboxPrev) lightboxPrev.addEventListener('click', () => navigateLightbox(-1));
        if (lightboxNext) lightboxNext.addEventListener('click', () => navigateLightbox(1));

        // Click backdrop to close
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') navigateLightbox(-1);
            if (e.key === 'ArrowRight') navigateLightbox(1);
        });
    }

})();
