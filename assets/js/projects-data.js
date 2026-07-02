/**
 * RLabz Projects Data
 * Comprehensive project repository data for the frontend.
 * When the database is fully connected, this file can be replaced
 * with dynamic API calls to admin/api/get_projects.php
 */

window.RLABZ_PROJECTS = [];
let projectsLoadedPromise = null;

function fetchProjects() {
    if (projectsLoadedPromise) return projectsLoadedPromise;

    projectsLoadedPromise = fetch('../../admin/api/public_projects.php')
        .then(response => response.json())
        .then(data => {
            if(data && !data.error) {
                window.RLABZ_PROJECTS = data;
            } else {
                console.error('API Error:', data.error);
            }
            window.dispatchEvent(new Event('projectsLoaded'));
            return window.RLABZ_PROJECTS;
        })
        .catch(err => {
            console.error('Error loading projects from DB:', err);
            window.dispatchEvent(new Event('projectsLoaded'));
            return [];
        });
    return projectsLoadedPromise;
}

// Initiate fetch immediately
fetchProjects();

// Helper: Get all unique years
function getProjectYears() {
    const years = [...new Set(window.RLABZ_PROJECTS.map(p => p.year))];
    return years.sort((a, b) => {
        if (a === 'N/A') return -1;
        if (b === 'N/A') return 1;
        return parseInt(a) - parseInt(b);
    });
}

// Helper: Get all unique technologies
function getProjectTechnologies() {
    const techs = new Set();
    window.RLABZ_PROJECTS.forEach(p => p.tech.forEach(t => techs.add(t)));
    return [...techs].sort();
}

// Helper: Get all unique categories
function getProjectCategories() {
    return [...new Set(window.RLABZ_PROJECTS.map(p => p.category))].sort();
}

// Helper: Get all unique statuses
function getProjectStatuses() {
    return [...new Set(window.RLABZ_PROJECTS.map(p => p.status))].sort();
}

// Helper: Find project by ID
function getProjectById(id) {
    return window.RLABZ_PROJECTS.find(p => p.id === id) || null;
}

// Helper: Get adjacent projects for prev/next navigation
function getAdjacentProjects(id) {
    const index = window.RLABZ_PROJECTS.findIndex(p => p.id === id);
    if (index === -1) return { prev: null, next: null };
    return {
        prev: index > 0 ? window.RLABZ_PROJECTS[index - 1] : null,
        next: index < window.RLABZ_PROJECTS.length - 1 ? window.RLABZ_PROJECTS[index + 1] : null
    };
}

