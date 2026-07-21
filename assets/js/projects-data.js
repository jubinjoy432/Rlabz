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

    const isSubfolder = window.location.pathname.includes('/public/');
    const imgPrefix = isSubfolder ? '../assets/' : 'assets/';
    let apiPath = isSubfolder ? '../admin/api/public_projects.php' : 'admin/api/public_projects.php';
    if (window.location.protocol === 'file:') {
        apiPath = 'http://localhost/Rlabz/admin/api/public_projects.php';
    }

    const fallbackData = [
        {"id":"euphoria-2019","title":"Euphoria 2019","year":"2019","shortDescription":"Official cultural festival website for RCSS MCA department featuring event registrations and schedules.","tech":["HTML5","CSS3","JavaScript","PHP"],"thumbnail":"images/euphoria-2019.webp","screenshots":["images/euphoria-2019.webp"],"team":[],"category":"Web Application","status":"Completed"},
        {"id":"arkon-2017","title":"Arkon Medical Systems","year":"2017","shortDescription":"Healthcare equipment platform built with HTML5, CSS3, JavaScript, and PHP offering comprehensive medical equipment systems.","tech":["HTML5","CSS3","JavaScript","PHP"],"thumbnail":"images/arkon-img.webp","screenshots":["images/arkon-img.webp"],"team":[],"category":"Web Application","status":"Deployed"},
        {"id":"theluke","title":"The Luke","year":"2017","shortDescription":"Professional event management website built with HTML5, CSS3, and JavaScript for The Luke Event Management company.","tech":["HTML5","CSS3","JavaScript"],"thumbnail":"images/luke-image.webp","screenshots":["images/luke-image.webp"],"team":[],"category":"Web Application","status":"Deployed"},
        {"id":"ctrm","title":"CTRM System","year":"2017","shortDescription":"Comprehensive Teaching Resource Management system developed for Rajagiri College of Social Sciences using PHP, HTML5, CSS3.","tech":["PHP","HTML5","CSS3","JavaScript","MySQL"],"thumbnail":"images/ctrm-image.webp","screenshots":["images/ctrm-image.webp"],"team":[],"category":"Web Application","status":"Completed"},
        {"id":"campus-connect-2017","title":"Campus Connect","year":"2017","shortDescription":"Comprehensive web platform built with Python, Django, HTML5, CSS3, and JavaScript connecting students, faculty, and administration.","tech":["Python","Django","HTML5","CSS3","JavaScript"],"thumbnail":"images/campuscon-image.webp","screenshots":["images/campuscon-image.webp"],"team":[],"category":"Web Application","status":"Completed"},
        {"id":"cocobies","title":"Cocobies","year":"2013","shortDescription":"Innovative Android application developed for the RCSS MCA Department, showcasing mobile development capabilities.","tech":["Android","Java"],"thumbnail":"images/cocobi-image.webp","screenshots":["images/cocobi-image.webp"],"team":[],"category":"Mobile Application","status":"Completed"}
    ];

    function applyPrefixes(data) {
        const uploadPrefix = isSubfolder ? '../' : '';
        data.forEach(p => {
            if (p.thumbnail) {
                if (p.thumbnail.startsWith('images/')) p.thumbnail = imgPrefix + p.thumbnail;
                else if (p.thumbnail.startsWith('uploads/')) p.thumbnail = uploadPrefix + p.thumbnail;
            }
            if (p.screenshots) {
                p.screenshots = p.screenshots.map(s => {
                    if (s.startsWith('images/')) return imgPrefix + s;
                    if (s.startsWith('uploads/')) return uploadPrefix + s;
                    return s;
                });
            }
            if (p.team) {
                p.team.forEach(t => {
                    if (t.photo) {
                        if (t.photo.startsWith('images/')) t.photo = imgPrefix + t.photo;
                        else if (t.photo.startsWith('uploads/')) t.photo = uploadPrefix + t.photo;
                    }
                });
            }
            if (p.poster) {
                if (p.poster.startsWith('images/')) p.poster = imgPrefix + p.poster;
                else if (p.poster.startsWith('uploads/')) p.poster = uploadPrefix + p.poster;
            }
        });
        return data;
    }

    projectsLoadedPromise = fetch(apiPath)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if(data && !data.error && Array.isArray(data) && data.length > 0) {
                window.RLABZ_PROJECTS = applyPrefixes(data);
            } else {
                console.error('API Error or Empty:', data.error);
                window.RLABZ_PROJECTS = applyPrefixes(fallbackData);
            }
            window.dispatchEvent(new Event('projectsLoaded'));
            return window.RLABZ_PROJECTS;
        })
        .catch(err => {
            console.error('Error loading projects from DB (using fallback):', err);
            window.RLABZ_PROJECTS = applyPrefixes(fallbackData);
            window.dispatchEvent(new Event('projectsLoaded'));
            return window.RLABZ_PROJECTS;
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

