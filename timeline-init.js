/* =========================================
   OUR WORKS VERTICAL TIMELINE - ISOLATED IMPLEMENTATION
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const timelineContainer = document.getElementById('timeline-container');
    if (!timelineContainer) return;

    const allProjectIds = Object.keys(projects);
    const timeline = document.createElement('div');
    timeline.className = 'timeline';

    allProjectIds.forEach((id, index) => {
        const p = projects[id];
        const year = p.date ? p.date.split(' ').pop() : 'N/A';
        const techBadges = p.tech ? p.tech.map(t => `<span class="timeline-badge">${t}</span>`).join('') : '';

        const iconMap = {
            1: "fa-heartbeat", 2: "fa-music", 3: "fa-theater-masks", 4: "fa-calendar-alt",
            5: "fa-users", 6: "fa-mobile-alt", 7: "fa-globe", 8: "fa-chalkboard-teacher",
            9: "fa-hands-helping", 10: "fa-glass-cheers"
        };
        const iconClass = iconMap[id] || "fa-laptop-code";

        const timelineItem = document.createElement('div');
        timelineItem.className = 'timeline-item';
        timelineItem.style.animationDelay = `${index * 150}ms`;

        timelineItem.innerHTML = `
            <div class="timeline-marker">
                <div class="timeline-dot"><i class="fas ${iconClass}"></i></div>
            </div>
            <div class="timeline-content">
                <div class="timeline-card"
                     data-id="${id}"
                     data-title="${p.title}"
                     data-desc="${p.desc}"
                     data-client="${p.client}"
                     data-year="${year}"
                     data-tech='${JSON.stringify(p.tech || [])}'
                     data-link="${p.link}"
                     data-icon="${iconClass}">
                    <div class="timeline-year">${year}</div>
                    <h3 class="timeline-title">${p.title}</h3>
                    <p class="timeline-desc">${p.desc}</p>
                    <div class="timeline-tags">${techBadges}</div>
                    <div class="timeline-client">Client: ${p.client}</div>
                    <button class="timeline-btn">View Details</button>
                </div>
            </div>
        `;

        timeline.appendChild(timelineItem);
    });

    timelineContainer.appendChild(timeline);

    const timelineItems = document.querySelectorAll('.timeline-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.2, rootMargin: '0px 0px -50px 0px' });

    timelineItems.forEach(item => observer.observe(item));

    const timelineCards = document.querySelectorAll('.timeline-card');
    timelineCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const rotateX = (y - rect.height / 2) / -20;
            const rotateY = (x - rect.width / 2) / 20;
            card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(800px) rotateX(0deg) rotateY(0deg) scale(1)';
        });

        const btn = card.querySelector('.timeline-btn');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openProjectModal(card);
            });
        }
    });
});
