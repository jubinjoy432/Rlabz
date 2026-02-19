/* =========================================
   OUR WORKS – BENTO GRID LOGIC
========================================= */

document.addEventListener("DOMContentLoaded", () => {

    const bentoGrid = document.getElementById("bento-grid");

    // Sample Projects (Replace with your actual project object)
    const projects = [
        { title: "Splendore", desc: "Luxury travel platform.", img: "https://picsum.photos/800/600?1" },
        { title: "Euphoria", desc: "Event management system.", img: "https://picsum.photos/800/600?2" },
        { title: "Cocobies", desc: "E-commerce web store.", img: "https://picsum.photos/800/600?3" },
        { title: "The Luke", desc: "Creative portfolio site.", img: "https://picsum.photos/800/600?4" },
        { title: "NovaX", desc: "AI SaaS Dashboard.", img: "https://picsum.photos/800/600?5" },
        { title: "Skyline", desc: "Real estate platform.", img: "https://picsum.photos/800/600?6" },
        { title: "Orbit", desc: "Startup landing page.", img: "https://picsum.photos/800/600?7" },
        { title: "Zenith", desc: "Marketing automation tool.", img: "https://picsum.photos/800/600?8" },
        { title: "Pulse", desc: "Healthcare analytics.", img: "https://picsum.photos/800/600?9" },
        { title: "Aurora", desc: "Fintech mobile app.", img: "https://picsum.photos/800/600?10" }
    ];

    const itemsPerView = 5;
    let currentIndex = 0;

    function renderProjects() {

        bentoGrid.classList.add("fade-out");

        setTimeout(() => {
            bentoGrid.innerHTML = "";

            const visibleProjects = projects.slice(
                currentIndex,
                currentIndex + itemsPerView
            );

            visibleProjects.forEach(project => {

                const card = document.createElement("div");
                card.className = "bento-card";

                card.innerHTML = `
                    <img src="${project.img}" alt="${project.title}">
                    <div class="bento-overlay">
                        <div class="bento-content">
                            <h3>${project.title}</h3>
                            <p>${project.desc}</p>
                        </div>
                    </div>
                `;

                bentoGrid.appendChild(card);
            });

            bentoGrid.classList.remove("fade-out");
            bentoGrid.classList.add("fade-in");

        }, 500);
    }

    function nextSet() {
        currentIndex += itemsPerView;

        if (currentIndex >= projects.length) {
            currentIndex = 0;
        }

        renderProjects();
    }

    renderProjects();

    // Auto change every 5 seconds
    setInterval(nextSet, 5000);

});
