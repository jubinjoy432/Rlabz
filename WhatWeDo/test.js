const leftCards = document.querySelectorAll(".left-column .card");
const rightCards = document.querySelectorAll(".right-column .image-card");

let currentIndex = 0;
let isAnimating = false;
let scrollLocked = false;
const total = leftCards.length;

window.addEventListener("wheel", (e) => {

    e.preventDefault(); // stop natural scroll

    if (isAnimating || scrollLocked) return;

    // Small threshold to ignore tiny trackpad movements
    if (Math.abs(e.deltaY) < 30) return;

    scrollLocked = true;

    if (e.deltaY > 0 && currentIndex < total - 1) {
        changeCard(currentIndex + 1, "down");
    }
    else if (e.deltaY < 0 && currentIndex > 0) {
        changeCard(currentIndex - 1, "up");
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