import os

works_dir = r"c:\Users\alber\Desktop\Rlabz\Rlabz\WhatWeDo"
css_path = os.path.join(works_dir, "styles.css")
js_path = os.path.join(works_dir, "script.js")

# --- UPDATE CSS ---
with open(css_path, 'r', encoding='utf-8') as f:
    css_lines = f.readlines()

new_css = """
/* =========================================
   OUR WORKS SLIDER (New Layout)
   ========================================= */

#our-works-slider {
    width: 100%;
    height: 100%;
    min-height: 600px;
    border-radius: 30px;
    overflow: hidden;
    display: flex;
    background: white;
    position: relative;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

#our-works-slider .left-column {
    width: 50%;
    position: relative;
    overflow: hidden;
}

#our-works-slider .card {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 80px;
    transform: translateY(100%);
    opacity: 0;
    transition: transform 0.8s ease, opacity 0.8s ease;
    box-shadow: none; /* override old card shadow if any */
}

#our-works-slider .card.active {
    transform: translateY(0);
    opacity: 1;
}

#our-works-slider .left-column .card:nth-child(odd) {
    background: white;
    color: #111;
}

#our-works-slider .left-column .card:nth-child(even) {
    background: #e6f2ff;
    color: #111;
}

#our-works-slider .left-column h1 {
    font-size: 38px;
    margin-bottom: 20px;
    font-family: var(--font-heading);
}

#our-works-slider .left-column p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.8;
}

#our-works-slider .left-column .explore-btn {
    padding: 15px 30px;
    border-radius: 40px;
    border: none;
    font-size: 16px;
    cursor: pointer;
    background: var(--primary-blue);
    color: white;
    width: fit-content;
    text-decoration: none;
    transition: transform 0.3s ease, background 0.3s ease;
}

#our-works-slider .left-column .explore-btn:hover {
    background: #094780;
    transform: translateY(-2px);
}

/* RIGHT SIDE */
#our-works-slider .right-column {
    width: 50%;
    position: relative;
    overflow: hidden;
}

#our-works-slider .image-card {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    transform: translateY(-100%);
    opacity: 0;
    transition: transform 0.8s ease, opacity 0.8s ease;
}

#our-works-slider .image-card.active {
    transform: translateY(0);
    opacity: 1;
}

#our-works-slider .image-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Responsive adjustments */
@media (max-width: 900px) {
    #our-works-slider {
        flex-direction: column;
    }
    #our-works-slider .left-column,
    #our-works-slider .right-column {
        width: 100%;
        height: 50%;
    }
    #our-works-slider .card {
        padding: 40px;
    }
    #our-works-slider .left-column h1 {
        font-size: 28px;
        margin-bottom: 15px;
    }
    #our-works-slider .left-column p {
        font-size: 16px;
        margin-bottom: 20px;
    }
}
"""

with open(css_path, 'w', encoding='utf-8') as f:
    f.writelines(css_lines[:6336])
    f.write(new_css)


# --- UPDATE JS ---
with open(js_path, 'r', encoding='utf-8') as f:
    js_lines = f.readlines()

new_js = """
/* =========================================
   OUR WORKS SLIDER LOGIC
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const worksSlider = document.getElementById('our-works-slider');
    if (!worksSlider) return;

    const leftColumn = worksSlider.querySelector('.left-column');
    const rightColumn = worksSlider.querySelector('.right-column');
    
    // Inject cards
    const allProjectIds = Object.keys(projects);
    
    allProjectIds.forEach((id, index) => {
        const p = projects[id];
        
        // Left Card
        const leftCard = document.createElement('div');
        leftCard.className = `card ${index === 0 ? 'active' : ''}`;
        leftCard.innerHTML = `
            <h1>${p.title}</h1>
            <p>${p.desc}</p>
            <a href="${p.link}" target="_blank" class="explore-btn">View Project →</a>
        `;
        leftColumn.appendChild(leftCard);
        
        // Right Image Card
        const rightCard = document.createElement('div');
        rightCard.className = `image-card ${index === 0 ? 'active' : ''}`;
        rightCard.innerHTML = `
            <img src="${p.img}" alt="${p.title}">
        `;
        rightColumn.appendChild(rightCard);
    });

    const leftCards = leftColumn.querySelectorAll(".card");
    const rightCards = rightColumn.querySelectorAll(".image-card");

    let currentIndex = 0;
    let isAnimating = false;
    let scrollLocked = false;
    const total = leftCards.length;

    // Handle wheel event only when mouse is over the slider section
    let isMouseOverSlider = false;
    worksSlider.addEventListener('mouseenter', () => isMouseOverSlider = true);
    worksSlider.addEventListener('mouseleave', () => isMouseOverSlider = false);

    window.addEventListener("wheel", (e) => {
        if (!isMouseOverSlider) return; // Only capture scroll when hovering the slider

        if (isAnimating || scrollLocked) {
            e.preventDefault(); // stop natural scroll if we are in the middle of animating
            return;
        }

        // Small threshold to ignore tiny trackpad movements
        if (Math.abs(e.deltaY) < 30) return;

        // Determine if we can actually scroll the cards
        if (e.deltaY > 0 && currentIndex < total - 1) {
            e.preventDefault();
            scrollLocked = true;
            changeCard(currentIndex + 1, "down");
        } else if (e.deltaY < 0 && currentIndex > 0) {
            e.preventDefault();
            scrollLocked = true;
            changeCard(currentIndex - 1, "up");
        } else {
            // Allow natural scrolling if we are at the top/bottom boundary
            return; 
        }

        // Unlock scroll after short delay
        setTimeout(() => {
            scrollLocked = false;
        }, 900); // slightly more than animation time

    }, { passive: false });

    function changeCard(newIndex, direction) {
        isAnimating = true;

        const leftCurrent = leftCards[currentIndex];
        const rightCurrent = rightCards[currentIndex];

        const leftNext = leftCards[newIndex];
        const rightNext = rightCards[newIndex];

        // Remove active
        leftCurrent.classList.remove("active");
        rightCurrent.classList.remove("active");

        // Prepare next start position
        if (direction === "down") {
            leftNext.style.transform = "translateY(100%)";
            rightNext.style.transform = "translateY(-100%)";
        } else {
            leftNext.style.transform = "translateY(-100%)";
            rightNext.style.transform = "translateY(100%)";
        }

        leftNext.style.opacity = "1";
        rightNext.style.opacity = "1";

        void leftNext.offsetWidth; // force reflow

        // Animate current out
        if (direction === "down") {
            leftCurrent.style.transform = "translateY(-100%)";
            rightCurrent.style.transform = "translateY(100%)";
        } else {
            leftCurrent.style.transform = "translateY(100%)";
            rightCurrent.style.transform = "translateY(-100%)";
        }

        // Animate next in
        leftNext.style.transform = "translateY(0)";
        rightNext.style.transform = "translateY(0)";

        currentIndex = newIndex;

        setTimeout(() => {
            leftCurrent.style.opacity = "0";
            rightCurrent.style.opacity = "0";

            leftNext.classList.add("active");
            rightNext.classList.add("active");

            isAnimating = false;
        }, 800);
    }
});
"""

with open(js_path, 'w', encoding='utf-8') as f:
    f.writelines(js_lines[:1461])
    f.write(new_js)

print("Updates applied successfully.")
