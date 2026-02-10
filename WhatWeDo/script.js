document.addEventListener('DOMContentLoaded', () => {
    // --- Entrance Animations ---
    const devices = document.querySelectorAll('.device');
    // Small delay to ensure styles are ready
    setTimeout(() => {
        devices.forEach(device => {
            device.classList.add('animate-in');
        });
    }, 100);

    // --- Hero Slider Logic ---
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    const visual = document.querySelector('.hero-blue-visual');
    let currentSlide = 0;

    function goToSlide(index) {
        // Update Text Slides
        slides.forEach(slide => slide.classList.remove('active'));
        if (slides[index]) slides[index].classList.add('active');

        // Update Dots
        dots.forEach(dot => dot.classList.remove('active'));
        if (dots[index]) dots[index].classList.add('active');

        // Update Visual State (Alignment of Devices)
        if (visual) {
            if (index === 1) {
                visual.classList.add('state-aligned');
            } else {
                visual.classList.remove('state-aligned');
            }
        }
        currentSlide = index;
    }

    // Dot Click Events
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            goToSlide(index);
        });
    });

    // --- 3D Card Tilt Effect ---
    // (Existing card tilt logic...)

    // --- Product Showcase Parallax ---
    const showcaseContainer = document.querySelector('.showcase-container');
    const showcaseStage = document.querySelector('.showcase-stage');

    if (showcaseContainer && showcaseStage) {
        showcaseContainer.addEventListener('mousemove', (e) => {
            const rect = showcaseContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            // Rotate stage based on mouse position
            const rotateX = ((y - centerY) / centerY) * -5; // Max 5deg tilt
            const rotateY = ((x - centerX) / centerX) * 5;

            showcaseStage.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;

            // Parallax for individual elements
            document.querySelectorAll('.device, .floating-badge').forEach(el => {
                const speed = parseFloat(el.getAttribute('data-speed')) || 2;
                const moveX = ((x - centerX) / centerX) * speed * -1;
                const moveY = ((y - centerY) / centerY) * speed * -1;

                // Keep existing transforms (like translateZ) and add parallax
                // We use CSS custom properties to avoid overwriting the transform property directly 
                // if it's complex, but here a simple approach is to modify the transform matrix 
                // or just use translate3d if the base transform is handled via CSS classes.
                // However, our CSS uses translate(-50%, -50%) etc. 
                // So best to apply parallax to a wrapper OR use CSS variables.

                // Let's use CSS variables for cleaner integration if supported, 
                // but since we didn't set that up in CSS, we'll use a simpler approach:
                // We'll update a custom property --parallax-x and --parallax-y
                el.style.setProperty('--parallax-x', `${moveX}px`);
                el.style.setProperty('--parallax-y', `${moveY}px`);
            });
        });

        showcaseContainer.addEventListener('mouseleave', () => {
            showcaseStage.style.transform = 'rotateX(0) rotateY(0)';
            document.querySelectorAll('.device, .floating-badge').forEach(el => {
                el.style.setProperty('--parallax-x', '0px');
                el.style.setProperty('--parallax-y', '0px');
            });
        });
    }
    const cards = document.querySelectorAll('.card');

    cards.forEach(card => {
        card.addEventListener('mousemove', handleMouseMove);
        card.addEventListener('mouseleave', handleMouseLeave);
        card.addEventListener('mouseenter', () => setActiveCard(card));
        card.addEventListener('mouseleave', () => setActiveCard(null));
    });

    function handleMouseMove(e) {
        const card = this;
        const cardRect = card.getBoundingClientRect();

        const x = e.clientX - cardRect.left;
        const y = e.clientY - cardRect.top;

        const rotateX = (y - cardRect.height / 2) / -20;
        const rotateY = (x - cardRect.width / 2) / 20;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;

        // Set CSS variables for spotlight effect
        card.style.setProperty('--mouse-x', `${x}px`);
        card.style.setProperty('--mouse-y', `${y}px`);
    }

    function handleMouseLeave(e) {
        this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
    }

    // --- ENHANCED AMBIENT PARTICLES ---
    const canvas = document.getElementById('ambient-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const section = document.getElementById('what-we-do');

    let width, height;
    let particles = [];
    let sparkles = [];
    let cardRects = [];
    let activeCardIndex = -1;
    let mouse = { x: -1000, y: -1000 };

    const CONFIG = {
        particleCount: 400, // Increased count
        colorBase: 'rgba(11, 83, 148, ', // Primary Blue base
        colorHighlight: 'rgba(0, 210, 255, ', // Cyan Highlight
        colorAccent: 'rgba(224, 242, 254, ', // Light Blue accent
        connectionDist: 140,
        mouseRadius: 300,
    };

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = section.offsetHeight;
        updateCardRects();
        initParticles();
    }

    function updateCardRects() {
        const canvasRect = canvas.getBoundingClientRect();

        cardRects = Array.from(cards).map(card => {
            const r = card.getBoundingClientRect();
            return {
                x: r.left - canvasRect.left,
                y: r.top - canvasRect.top,
                w: r.width,
                h: r.height,
                cx: (r.left - canvasRect.left) + r.width / 2,
                cy: (r.top - canvasRect.top) + r.height / 2
            };
        });
    }

    function setActiveCard(card) {
        if (!card) {
            activeCardIndex = -1;
        } else {
            cards.forEach((c, i) => { if (c === card) activeCardIndex = i; });
        }
    }

    document.addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        } else {
            mouse.x = -1000;
            mouse.y = -1000;
        }
    });

    class Particle {
        constructor() {
            this.init();
        }

        init() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            // Base velocity for gentle drifting
            this.baseVx = (Math.random() - 0.5) * 0.5;
            this.baseVy = (Math.random() - 0.5) * 0.5;
            // Interaction velocity (push from mouse)
            this.ivx = 0;
            this.ivy = 0;
            this.size = Math.random() * 2.5 + 0.5;
            this.baseSize = this.size;
            this.excitement = 0;
            this.opacity = 0.6 + Math.random() * 0.4;
        }

        update() {
            // Mouse Interaction
            const dx = mouse.x - this.x;
            const dy = mouse.y - this.y;
            const distMouse = Math.sqrt(dx * dx + dy * dy);

            if (distMouse < CONFIG.mouseRadius) {
                // Add to interaction velocity, not base velocity
                this.ivx -= (dx / distMouse) * 0.02;
                this.ivy -= (dy / distMouse) * 0.02;
                this.excitement = Math.min(this.excitement + 0.02, 1);
            } else {
                this.excitement = Math.max(this.excitement - 0.01, 0);
            }

            // Subtle card attraction
            if (activeCardIndex !== -1 && cardRects[activeCardIndex]) {
                const rect = cardRects[activeCardIndex];
                const cdx = rect.cx - this.x;
                const cdy = rect.cy - this.y;
                const distCard = Math.sqrt(cdx * cdx + cdy * cdy);

                if (distCard < 400) {
                    this.ivx += (cdx / distCard) * 0.005;
                    this.ivy += (cdy / distCard) * 0.005;
                    this.excitement = Math.min(this.excitement + 0.01, 0.9);
                }
            }

            // Apply friction to interaction velocity
            this.ivx *= 0.95;
            this.ivy *= 0.95;

            // Update position with combined velocities
            this.x += (this.baseVx + this.ivx) * (1 + this.excitement * 0.3);
            this.y += (this.baseVy + this.ivy) * (1 + this.excitement * 0.3);

            // Bounds
            if (this.x < 0) this.x = width; if (this.x > width) this.x = 0;
            if (this.y < 0) this.y = height; if (this.y > height) this.y = 0;
        }

        draw() {
            const displaySize = this.size * (1 + this.excitement * 0.5);

            // Enhanced glow when excited
            if (this.excitement > 0.4) {
                ctx.beginPath();
                ctx.arc(this.x, this.y, displaySize * 6, 0, Math.PI * 2);
                const glowGradient = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, displaySize * 6);
                glowGradient.addColorStop(0, CONFIG.colorAccent + (this.excitement * 0.3) + ')');
                glowGradient.addColorStop(1, CONFIG.colorAccent + '0)');
                ctx.fillStyle = glowGradient;
                ctx.fill();
            }

            ctx.beginPath();
            ctx.arc(this.x, this.y, displaySize, 0, Math.PI * 2);

            if (this.excitement > 0.1) {
                ctx.fillStyle = CONFIG.colorHighlight + (0.4 + this.excitement * 0.6) + ')';
                if (this.excitement > 0.5) {
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = CONFIG.colorAccent + (this.excitement * 0.6) + ')';
                }
            } else {
                ctx.fillStyle = CONFIG.colorBase + (this.opacity * 0.4) + ')';
            }
            ctx.fill();
            ctx.shadowBlur = 0;
        }
    }

    // Sparkle particle class
    class Sparkle {
        constructor(x, y) {
            this.x = x;
            this.y = y;
            this.vx = (Math.random() - 0.5) * 2;
            this.vy = -Math.random() * 2 - 1; // Float upward
            this.life = 1;
            this.size = Math.random() * 2 + 1;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;
            this.life -= 0.015;
            this.vy += 0.02; // Slight gravity
        }

        draw() {
            if (this.life <= 0) return;

            const alpha = this.life;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = CONFIG.colorHighlight + (alpha * 0.9) + ')';
            ctx.shadowBlur = 6;
            ctx.shadowColor = CONFIG.colorAccent + (alpha * 0.7) + ')';
            ctx.fill();
            ctx.shadowBlur = 0;
        }

        isDead() {
            return this.life <= 0;
        }
    }

    function initParticles() {
        particles = [];
        for (let i = 0; i < CONFIG.particleCount; i++) {
            particles.push(new Particle());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        if (activeCardIndex !== -1) {
            updateCardRects();

            // Spawn sparkles around card edges
            if (Math.random() < 0.3) { // 30% chance per frame
                const rect = cardRects[activeCardIndex];
                const pad = 8;

                // Random edge position
                const edge = Math.floor(Math.random() * 4);
                let sx, sy;

                if (edge === 0) { // Top
                    sx = rect.cx - rect.w / 2 + Math.random() * rect.w;
                    sy = rect.cy - rect.h / 2 - pad;
                } else if (edge === 1) { // Right
                    sx = rect.cx + rect.w / 2 + pad;
                    sy = rect.cy - rect.h / 2 + Math.random() * rect.h;
                } else if (edge === 2) { // Bottom
                    sx = rect.cx - rect.w / 2 + Math.random() * rect.w;
                    sy = rect.cy + rect.h / 2 + pad;
                } else { // Left
                    sx = rect.cx - rect.w / 2 - pad;
                    sy = rect.cy - rect.h / 2 + Math.random() * rect.h;
                }

                sparkles.push(new Sparkle(sx, sy));
            }

        }


        particles.forEach((p, i) => {
            p.update();
            p.draw();

            // Connect to mouse
            const dxMouse = p.x - mouse.x;
            const dyMouse = p.y - mouse.y;
            const distMouse = Math.sqrt(dxMouse * dxMouse + dyMouse * dyMouse);
            if (distMouse < CONFIG.connectionDist * 1.5) {
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(mouse.x, mouse.y);
                ctx.strokeStyle = CONFIG.colorAccent + (1 - distMouse / (CONFIG.connectionDist * 1.5)) * 0.5 + ')';
                ctx.lineWidth = 0.8;
                ctx.stroke();
            }

            // Connections between particles
            if (p.excitement < 0.5) {
                for (let j = i + 1; j < particles.length; j++) {
                    const p2 = particles[j];
                    const dx = p.x - p2.x;
                    const dy = p.y - p2.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < CONFIG.connectionDist) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        const alpha = (1 - dist / CONFIG.connectionDist) * 0.2;
                        ctx.strokeStyle = CONFIG.colorBase + alpha + ')';
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }
        });

        // Update and draw sparkles
        sparkles = sparkles.filter(s => {
            s.update();
            s.draw();
            return !s.isDead();
        });

        requestAnimationFrame(animate);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        card.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(card);
    });

    window.addEventListener('resize', resize);
    setTimeout(() => { resize(); animate(); }, 100);
    setTimeout(() => { resize(); }, 500);
});

// === 3D Coverflow Carousel ===
const carouselSection = document.getElementById('our-works');
const carouselContainer = document.querySelector('.carousel-container');
const carouselTrack = document.querySelector('.carousel-track');
const projectCards = document.querySelectorAll('.project-card');
const prevBtn = document.querySelector('.nav-btn.prev');
const nextBtn = document.querySelector('.nav-btn.next');

if (carouselContainer && carouselTrack && projectCards.length > 0) {
    let currentIndex = 0;
    const totalCards = projectCards.length;

    // Coverflow configuration
    const spacing = 450;      // Horizontal spacing between cards
    const sideAngle = 60;     // Rotation angle for side cards (degrees)
    const sideDepth = -350;   // Z-depth for side cards (negative = away)
    const sideScale = 0.9;    // Scale factor for side cards
    const sideOpacity = 0.7;  // Opacity for side cards

    let previousIndex = 0;

    function updateCarousel() {
        projectCards.forEach((card, index) => {
            // Calculate distance from center (with wrapping)
            let diff = index - currentIndex;

            // Calculate previous distance for transition logic
            let prevDiff = index - previousIndex;

            // Normalize to shortest path around carousel
            if (diff > totalCards / 2) diff -= totalCards;
            if (diff < -totalCards / 2) diff += totalCards;

            if (prevDiff > totalCards / 2) prevDiff -= totalCards;
            if (prevDiff < -totalCards / 2) prevDiff += totalCards;

            // Active card is the one at center (diff === 0)
            const isActive = diff === 0;

            // Check if card is moving between hidden positions (wrap-around)
            // If both new and old positions are "far" (> 1 or < -1), disable transition
            // This prevents cards from animating across the visible area when wrapping
            const isHiddenMove = Math.abs(diff) > 1 && Math.abs(prevDiff) > 1;

            if (isHiddenMove) {
                card.style.transition = 'none';
            } else {
                // Slower, smoother transition (0.8s)
                card.style.transition = 'transform 0.8s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.8s ease, box-shadow 0.3s ease';
            }

            // Calculate transforms based on distance from center
            const translateX = diff * spacing;
            const translateZ = isActive ? 0 : sideDepth;
            const rotateY = isActive ? 0 : (diff > 0 ? -sideAngle : sideAngle);
            const scale = isActive ? 1.0 : sideScale;
            // Opacity: Visible only at 0 and adjacent ±1 positions (or adjust logic as needed)
            const opacity = isActive ? 1 : (Math.abs(diff) > 1 ? 0 : sideOpacity);

            // Apply transforms
            // Force reflow if transition was disabled to ensure it snaps instantly
            if (isHiddenMove) void card.offsetWidth;

            card.style.transform = `translate(-50%, -50%) translateX(${translateX}px) translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
            card.style.opacity = opacity;
            card.style.zIndex = totalCards - Math.abs(diff);
            card.style.pointerEvents = isActive ? 'auto' : 'none';

            // Toggle active class
            if (isActive) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    }

    function nextCard() {
        previousIndex = currentIndex;
        currentIndex = (currentIndex + 1) % totalCards;
        updateCarousel();
    }

    function prevCard() {
        previousIndex = currentIndex;
        currentIndex = (currentIndex - 1 + totalCards) % totalCards;
        updateCarousel();
    }

    // Auto-rotation (Slower interval: 5000ms)
    let autoRotateInterval = setInterval(nextCard, 5000);

    function resetAutoRotate() {
        clearInterval(autoRotateInterval);
        autoRotateInterval = setInterval(nextCard, 5000);
    }

    // Pause on hover
    carouselContainer.addEventListener('mouseenter', () => {
        clearInterval(autoRotateInterval);
    });

    carouselContainer.addEventListener('mouseleave', () => {
        resetAutoRotate();
    });

    // Navigation buttons
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextCard();
            resetAutoRotate();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevCard();
            resetAutoRotate();
        });
    }

    // Touch/swipe support
    let touchStartX = 0;
    carouselContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        clearInterval(autoRotateInterval);
    }, { passive: true });

    carouselContainer.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                nextCard();
            } else {
                prevCard();
            }
        }
        resetAutoRotate();
    }, { passive: true });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (carouselSection) {
            const rect = carouselSection.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

            if (isVisible) {
                if (e.key === 'ArrowRight') {
                    nextCard();
                    resetAutoRotate();
                } else if (e.key === 'ArrowLeft') {
                    prevCard();
                    resetAutoRotate();
                }
            }
        }
    });

    // Initialize
    updateCarousel();
}

// 2. Project Data (Mock Data matching IDs)
const projects = {
    1: {
        title: "Arkon Medical Systems",
        desc: "Assured Service And Support And Reaching Out With Wide Product Range. Comprehensive medical equipment and systems for modern healthcare facilities.",
        tech: ["HTML5", "CSS3", "JavaScript", "PHP"],
        img: "images/arkon-img.png",
        client: "Arkon Medical Systems",
        date: "December 2017",
        link: "http://www.arkonmedicalsystems.in"
    },
    2: {
        title: "Splendore",
        desc: "Website for Rajagiri College of Social Sciences cultural festival.",
        tech: ["HTML5", "CSS3", "JavaScript"],
        img: "images/splendore-2019.png",
        client: "Rajagiri College of Social Sciences",
        date: "August 2019",
        link: "http://www.splendorercss.in"
    },
    3: {
        title: "Euphoria 2k17",
        desc: "Cultural festival website for RCSS MCA Department.",
        tech: ["HTML5", "CSS3", "JavaScript", "PHP"],
        img: "images/euphoria-2019.png",
        client: "RCSS MCA Dept.",
        date: "May 2017",
        link: "http://euphoria.rlabz.in"
    },
    4: {
        title: "Fest Buddy",
        desc: "Android application for managing college festivals and events.",
        tech: ["Android"],
        img: "images/fesbud-image.png",
        client: "Impress Project",
        date: "March 2020",
        link: "#"
    },
    5: {
        title: "Campus Connect",
        desc: "A comprehensive web platform connecting students, faculty, and administration for seamless campus communication and collaboration.",
        tech: ["Python", "Django", "HTML5", "CSS3", "JavaScript"],
        img: "images/campuscon-image.png",
        client: "Rajagiri College of Social Sciences",
        date: "November 2017",
        link: "http://xxsreexx.pythonanywhere.com"
    },
    6: {
        title: "Cocobies",
        desc: "An innovative Android application developed for the RCSS MCA Department.",
        tech: ["Android"],
        img: "images/cocobi-image.png",
        client: "RCSS MCA Dept.",
        date: "July 2013",
        link: "#"
    },
    7: {
        title: "ReX",
        desc: "A comprehensive web platform developed for Rajagiri College of Social Sciences.",
        tech: ["PHP", "HTML5", "CSS3", "JavaScript"],
        img: "images/rex-image.png",
        client: "Rajagiri College of Social Sciences",
        date: "N/A",
        link: "#"
    },
    8: {
        title: "CTRM System",
        desc: "Comprehensive Teaching Resource Management system developed for Rajagiri College of Social Sciences.",
        tech: ["PHP", "HTML5", "CSS3", "JavaScript"],
        img: "images/ctrm-image.png",
        client: "Rajagiri College of Social Sciences",
        date: "March 2017",
        link: "#"
    },
    9: {
        title: "RAJAGIRI OutREACH",
        desc: "Community outreach and social engagement platform for Rajagiri College of Social Sciences.",
        tech: ["PHP", "HTML", "CSS", "JavaScript"],
        img: "images/outreach-image.png",
        client: "Rajagiri College of Social Sciences",
        date: "June 2019",
        link: "#"
    },
    10: {
        title: "The Luke",
        desc: "Professional event management website for The Luke Event Management company.",
        tech: ["HTML5", "CSS3", "JavaScript"],
        img: "images/luke-image.png",
        client: "The Luke Event Management",
        date: "August 2017",
        link: "http://www.theluke.in"
    }
};

// 3. Modal Logic
const modal = document.getElementById('project-modal');
const closeModalBtn = document.querySelector('.close-modal');

// Modal Elements
const modalImg = document.getElementById('modal-image');
const modalTitle = document.getElementById('modal-title');
const modalDesc = document.getElementById('modal-description');
const modalStack = document.getElementById('modal-tech-stack');

function openModal(id) {
    const data = projects[id];
    if (!data) return;

    // Use detail image if available, otherwise use thumbnail
    const imageSrc = data.detailImg || data.img;
    console.log('Opening modal for:', data.title);
    console.log('Image source:', imageSrc);
    modalImg.src = imageSrc;
    modalTitle.innerText = data.title;
    modalDesc.innerText = data.desc;

    // Populate client and date if available
    const modalClient = document.getElementById('modal-client');
    const modalDate = document.getElementById('modal-date');
    const modalWebsiteLink = document.getElementById('modal-website-link');

    if (modalClient) modalClient.innerText = data.client || data.title;
    if (modalDate) modalDate.innerText = data.date || 'N/A';
    if (modalWebsiteLink) {
        modalWebsiteLink.href = data.link || '#';
        modalWebsiteLink.innerText = data.link || 'N/A';
    }

    // Clear and add badges
    modalStack.innerHTML = '';
    data.tech.forEach(tech => {
        const span = document.createElement('span');
        span.className = 'tech-badge';
        span.innerText = tech;
        modalStack.appendChild(span);
    });

    // Show modal with animation
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scroll

    // Trigger animation after browser has rendered initial state
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}


function closeModal() {
    modal.classList.remove('show');
    document.body.style.overflow = '';

    // Hide modal after transition completes
    setTimeout(() => {
        modal.style.display = 'none';
    }, 500); // Match the CSS transition duration
}


projectCards.forEach(card => {
    // Spotlight Effect - Only update CSS variables, don't modify transform
    card.addEventListener('mousemove', (e) => {
        const cardRect = card.getBoundingClientRect();
        const x = e.clientX - cardRect.left;
        const y = e.clientY - cardRect.top;
        card.style.setProperty('--mouse-x', `${x}px`);
        card.style.setProperty('--mouse-y', `${y}px`);
    });

    // Click to Open
    card.addEventListener('click', () => {
        const id = card.getAttribute('data-id');
        openModal(id);
    });
});

if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);

// Close on backdrop click
window.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});



document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // Clients Section Logic
    // =========================================

    // Client Data
    const clients = [
        {
            id: 1,
            name: "Sarah Mitchell",
            position: "Chief Technology Officer",
            company: "TechVision Inc.",
            description: "Transformed our entire infrastructure with their innovative solutions.",
            image: "https://images.unsplash.com/photo-1758518729459-235dcaadc611?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        },
        {
            id: 2,
            name: "Marcus Chen",
            position: "VP of Engineering",
            company: "CloudScale Systems",
            description: "Exceptional service delivery and outstanding technical expertise.",
            image: "https://images.unsplash.com/photo-1581065178047-8ee15951ede6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        },
        {
            id: 3,
            name: "Elena Rodriguez",
            position: "Head of Innovation",
            company: "Digital Dynamics",
            description: "A partnership that consistently exceeds expectations.",
            image: "https://images.unsplash.com/photo-1758518727888-ffa196002e59?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        },
        {
            id: 4,
            name: "James Anderson",
            position: "CEO & Founder",
            company: "Quantum Ventures",
            description: "The strategic partner every growing company needs.",
            image: "https://images.unsplash.com/photo-1758691737605-69a0e78bd193?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        },
        {
            id: 5,
            name: "Priya Sharma",
            position: "Director of Product",
            company: "InnovateLabs",
            description: "Incredible attention to detail and commitment to excellence.",
            image: "https://images.unsplash.com/photo-1737574821698-862e77f044c1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        },
        {
            id: 6,
            name: "David Park",
            position: "Chief Product Officer",
            company: "NextGen Solutions",
            description: "Their platform has become integral to our operations.",
            image: "https://images.unsplash.com/photo-1758691737605-69a0e78bd193?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400"
        }
    ];

    // State
    let isAutoScrolling = true;
    let isDragging = false;
    let startX = 0;
    let scrollLeft = 0;
    let autoScrollInterval = null;
    let inactivityTimer = null;

    // DOM Elements - Using Class Selectors where renamed
    const section = document.getElementById('clientsSection');
    const header = document.querySelector('.clients-header');
    const carouselWrapper = document.querySelector('.clients-carousel-wrapper');
    const scrollContainer = document.getElementById('scrollContainer');
    const scrollLeftBtn = document.getElementById('scrollLeft');
    const scrollRightBtn = document.getElementById('scrollRight');
    const particlesContainer = document.getElementById('particlesContainer');
    const scrollProgress = document.getElementById('scrollProgress');

    if (!section || !scrollContainer) return; // Guard clause

    // Generate particles
    function generateParticles() {
        if (!particlesContainer) return;
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            const left = Math.random() * 100;
            const top = Math.random() * 100;
            const tx = (Math.random() - 0.5) * 200;
            const ty = (Math.random() - 0.5) * 200;
            particle.style.left = `${left}%`;
            particle.style.top = `${top}%`;
            particle.style.setProperty('--tx', tx);
            particle.style.setProperty('--ty', ty);
            particle.style.animationDelay = `${Math.random() * 5}s`;
            particle.style.animationDuration = `${15 + Math.random() * 10}s`;
            particlesContainer.appendChild(particle);
        }
    }

    // Generate client cards
    function generateCards() {
        // Duplicate clients 3 times for infinite scroll
        const infiniteClients = [...clients, ...clients, ...clients];

        infiniteClients.forEach((client, index) => {
            const cardWrapper = document.createElement('div');
            cardWrapper.className = 'client-card-wrapper';

            cardWrapper.innerHTML = `
                <div class="client-card">
                    <div class="card-shine"></div>
                    <div class="card-glow"></div>
                    <div class="card-image">
                        <img src="${client.image}" alt="${client.name}" loading="lazy">
                        <div class="image-overlay"></div>
                    </div>
                    <div class="card-info">
                        <div class="name-linkedin">
                            <h3>${client.name}</h3>
                            <a href="#" class="linkedin-link" aria-label="View LinkedIn Profile" onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect x="2" y="9" width="4" height="12"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                            </a>
                        </div>
                        <p class="position">${client.position}</p>
                        <p class="company">${client.company}</p>
                        
                        <div class="feedback">
                            <p class="feedback-text">${client.description}</p>
                        </div>
                    </div>
                </div>
            `;

            // Staggered animation
            cardWrapper.style.animationDelay = `${(index % clients.length) * 0.1}s`;

            scrollContainer.appendChild(cardWrapper);
        });
    }

    // Intersection Observer for fade-in
    function setupIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    section.classList.add('visible');
                    if (header) header.classList.add('visible');
                    if (carouselWrapper) carouselWrapper.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        observer.observe(section);
    }

    // Auto-scroll functionality
    function startAutoScroll() {
        if (!isAutoScrolling) return;

        autoScrollInterval = setInterval(() => {
            if (!isAutoScrolling) return;

            const maxScroll = scrollContainer.scrollWidth / 3; // Since we have 3x duplication

            if (scrollContainer.scrollLeft >= maxScroll) {
                scrollContainer.scrollLeft = 0;
            } else {
                scrollContainer.scrollLeft += 1;
            }
        }, 30);
    }

    function stopAutoScroll() {
        if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;
        }
    }

    function pauseAutoScroll() {
        isAutoScrolling = false;
        stopAutoScroll();

        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
        }

        inactivityTimer = setTimeout(() => {
            isAutoScrolling = true;
            startAutoScroll();
        }, 5000);
    }

    // Manual scroll
    function handleManualScroll(direction) {
        const scrollAmount = 400;
        const newScrollLeft = direction === 'left'
            ? scrollContainer.scrollLeft - scrollAmount
            : scrollContainer.scrollLeft + scrollAmount;

        scrollContainer.scrollTo({
            left: newScrollLeft,
            behavior: 'smooth'
        });

        pauseAutoScroll();
    }

    // Update navigation button states
    function updateNavButtons() {
        const isAtStart = scrollContainer.scrollLeft <= 10;
        const isAtEnd = scrollContainer.scrollLeft >= scrollContainer.scrollWidth - scrollContainer.clientWidth - 10;

        if (scrollLeftBtn) scrollLeftBtn.classList.toggle('disabled', isAtStart);
        if (scrollRightBtn) scrollRightBtn.classList.toggle('disabled', isAtEnd);
    }

    // Update scroll progress
    function updateScrollProgress() {
        if (!scrollProgress) return;
        const scrollableWidth = scrollContainer.scrollWidth - scrollContainer.clientWidth;
        const scrollProgressPercent = (scrollContainer.scrollLeft / scrollableWidth) * 100;
        scrollProgress.style.width = `${Math.min(100, Math.max(0, scrollProgressPercent))}%`;
    }

    // Keyboard navigation
    function handleKeyDown(e) {
        if (e.key === 'ArrowLeft') {
            // Only capture if section is in view? Removed to avoid interfering with other carousels
            // handleManualScroll('left'); 
        } else if (e.key === 'ArrowRight') {
            // handleManualScroll('right');
        }
    }

    // Drag functionality
    function handleMouseDown(e) {
        isDragging = true;
        scrollContainer.classList.add('grabbing');
        startX = e.pageX - scrollContainer.offsetLeft;
        scrollLeft = scrollContainer.scrollLeft;
        pauseAutoScroll();
    }

    function handleMouseMove(e) {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - scrollContainer.offsetLeft;
        const walk = (x - startX) * 2;
        scrollContainer.scrollLeft = scrollLeft - walk;
    }

    function handleMouseUp() {
        isDragging = false;
        scrollContainer.classList.remove('grabbing');
    }

    function handleMouseLeave() {
        isDragging = false;
        scrollContainer.classList.remove('grabbing');
    }

    // Click to pause
    function handleClick() {
        pauseAutoScroll();
    }

    // Event listeners
    if (scrollLeftBtn) scrollLeftBtn.addEventListener('click', () => handleManualScroll('left'));
    if (scrollRightBtn) scrollRightBtn.addEventListener('click', () => handleManualScroll('right'));

    scrollContainer.addEventListener('mousedown', handleMouseDown);
    scrollContainer.addEventListener('mousemove', handleMouseMove);
    scrollContainer.addEventListener('mouseup', handleMouseUp);
    scrollContainer.addEventListener('mouseleave', handleMouseLeave);
    scrollContainer.addEventListener('click', handleClick);
    scrollContainer.addEventListener('scroll', () => {
        updateNavButtons();
        updateScrollProgress();
    });
    // window.addEventListener('keydown', handleKeyDown); // Commented out to avoid global conflict

    // Touch support for mobile
    let touchStartX = 0;
    scrollContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        pauseAutoScroll();
    });

    scrollContainer.addEventListener('touchmove', (e) => {
        if (Math.abs(e.touches[0].clientX - touchStartX) > 10) {
            pauseAutoScroll();
        }
    });

    // Initialize
    generateParticles();
    generateCards();
    setupIntersectionObserver();
    startAutoScroll();
    updateNavButtons();

    // Initial scroll progress update
    setTimeout(() => {
        updateScrollProgress();
    }, 100);

    // Cleanup on page unload (optional, handled by browser mostly)
});

document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // About Us Section Logic
    // =========================================

    // --- 1. Animated Stats Counter ---
    const statValues = document.querySelectorAll('.about-stat-value');

    const animateCounter = (element) => {
        const target = parseInt(element.dataset.target);
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    statValues.forEach(stat => statsObserver.observe(stat));

    // --- 2. Mouse Tracking for Feature Cards ---
    const featureCards = document.querySelectorAll('.about-feature-card');

    featureCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });

    // --- 3. Scroll-Triggered Animations ---
    const scrollElements = document.querySelectorAll('.about-reveal, .about-scale-in, .about-blur-in, .about-slide-left, .about-slide-right');

    const elementInView = (el, percentageScroll = 100) => {
        const elementTop = el.getBoundingClientRect().top;
        return (
            elementTop <=
            ((window.innerHeight || document.documentElement.clientHeight) * (percentageScroll / 100))
        );
    };

    const displayScrollElement = (element) => {
        element.classList.add('active');
    };

    const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
            if (elementInView(el, 85)) {
                displayScrollElement(el);
            }
        });
    };

    window.addEventListener('scroll', () => {
        handleScrollAnimation();
    });

    // Trigger on load for elements already in view
    handleScrollAnimation();

});

document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // About Us Section Logic
    // =========================================

    // --- 1. Animated Stats Counter ---
    const statValues = document.querySelectorAll('.about-stat-value');

    const animateCounter = (element) => {
        const target = parseInt(element.dataset.target);
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    statValues.forEach(stat => statsObserver.observe(stat));

    // --- 2. Mouse Tracking for Feature Cards ---
    const featureCards = document.querySelectorAll('.about-feature-card');

    featureCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });

    // --- 3. Scroll-Triggered Animations ---
    const scrollElements = document.querySelectorAll('.about-reveal, .about-scale-in, .about-blur-in, .about-slide-left, .about-slide-right');

    const elementInView = (el, percentageScroll = 100) => {
        const elementTop = el.getBoundingClientRect().top;
        return (
            elementTop <=
            ((window.innerHeight || document.documentElement.clientHeight) * (percentageScroll / 100))
        );
    };

    const displayScrollElement = (element) => {
        element.classList.add('active');
    };

    const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
            if (elementInView(el, 85)) {
                displayScrollElement(el);
            }
        });
    };

    window.addEventListener('scroll', () => {
        handleScrollAnimation();
    });

    // Trigger on load for elements already in view
    handleScrollAnimation();

});

document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // Contact Form Handling
    // =========================================
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = contactForm.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;

            // Simulate API call
            setTimeout(() => {
                alert('Thank you! Your message has been sent successfully.');
                contactForm.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }

    // =========================================
    // Back to Top Button Logic
    // =========================================
    const backToTopBtn = document.getElementById('backToTop');

    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    // --- Scroll Animations for Bottom Cards (Our Works) ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, observerOptions);

    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach((card, index) => {
        card.style.transitionDelay = `${index * 100}ms`; // Staggered delay
        observer.observe(card);
    });





    // --- Typewriter Effect & Card Animations ---
    const heroTitle = document.querySelector('.hero-blue-title');
    const heroCards = document.querySelectorAll('.hero-blue-card');

    if (heroTitle) {
        // 1. Capture Original HTML completely to restore later for perfect layout
        const originalHTML = heroTitle.innerHTML;

        // 2. Prepare for animation (Span-based reveal)
        const originalNodes = Array.from(heroTitle.childNodes);
        heroTitle.innerHTML = '';
        heroTitle.style.opacity = '1';

        // Rebuild content with transparency for animation
        originalNodes.forEach(node => {
            if (node.nodeType === 3) { // Text Node
                const chars = node.textContent.split('');
                chars.forEach(char => {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.style.opacity = '0';
                    span.style.transition = 'opacity 0.05s ease';
                    heroTitle.appendChild(span);
                });
            } else {
                heroTitle.appendChild(node.cloneNode(true)); // Preserve elements like <br>
            }
        });

        // 3. Trigger reveal animation
        const spans = heroTitle.querySelectorAll('span');
        setTimeout(() => {
            heroTitle.classList.add('typing');
            spans.forEach((span, index) => {
                setTimeout(() => {
                    span.style.opacity = '1';
                }, index * 30); // 30ms per char
            });

            // 4. RESTORE ORIGINAL HTML after animation
            // This ensures perfect layout (ligatures, kerning, wrapping) matches CSS
            setTimeout(() => {
                heroTitle.classList.remove('typing');
                heroTitle.innerHTML = originalHTML; // Restore exact original content
            }, (spans.length * 30) + 100); // Slight buffer
        }, 500);
    }

    // Trigger Card Animations
    if (heroCards.length > 0) {
        setTimeout(() => {
            heroCards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-in');
                }, index * 200); // Stagger delay
            });
        }, 1000); // Wait for title to start
    }

    // --- Hero Horizontal Parallax Effect ---
    const heroSection = document.querySelector('.hero-blue-section');
    const heroVisual = document.querySelector('.hero-blue-visual');
    const parallaxCards = document.querySelectorAll('.hero-blue-card');

    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateParallax() {
        if (!heroSection) return;

        const scrollY = window.scrollY;
        const sectionHeight = heroSection.offsetHeight;

        // Only animate if within relevant scroll range (hero + a bit more)
        if (scrollY < sectionHeight * 1.5) {
            // Calculate movement based on scroll
            // Phone container moves right
            const visualMove = scrollY * 0.15;

            if (heroVisual) {
                // Keep the tilt if it exists, add translateX
                // Note: The phone frame inside handles tilt on hover, visuals container just moves
                heroVisual.style.transform = `translateX(${visualMove}px)`;
            }

            // Cards move at different speeds (Layered Depth)
            parallaxCards.forEach((card, index) => {
                const speed = 0.05 + (index * 0.08); // 0.05, 0.13, 0.21
                const cardMove = scrollY * speed;

                // We need to maintain the initial float animation transform if possible, 
                // but usually direct transform overrides animation. 
                // To mix them, we wrapper or use specific properties. 
                // However, the requested effect implies the container moves.
                // Since cards have their own CSS animation (float-left, etc.), 
                // applying transform here directly might break the CSS keyframe animation.
                // Better approach: Apply parallax to the WRAPPER of cards if possible, 
                // OR use margin-left/right (less performant) OR use CSS variables.

                // Let's use CSS variables to add to the transform
                card.style.setProperty('--parallax-x', `${cardMove}px`);
            });
        }

        // --- SHARED MORPHIC HIGHLIGHT TRANSITION ---
        const sharedHighlight = document.getElementById('shared-morphic-highlight');
        const sourceEl = document.getElementById('highlight-solutions');
        const targetEl = document.getElementById('highlight-works');

        if (sharedHighlight && sourceEl && targetEl) {
            function updateMorphicHighlight() {
                const sourceRect = sourceEl.getBoundingClientRect();
                const targetRect = targetEl.getBoundingClientRect();
                const body = document.body;

                // Transition Zone: Start when source is middle of screen, end when target is middle
                const viewHeight = window.innerHeight;
                const sourceCenter = sourceRect.top + sourceRect.height / 2;
                const targetCenter = targetRect.top + targetRect.height / 2;

                // Calculate progress (0 to 1) between the two headers
                // We want the element to be at Source when source is in view, and at Target when target is in view.
                // A simple way: use the viewport center as the "activation" point.

                // If both are off-screen in the same direction, hide it
                if (targetRect.bottom < 0 || sourceRect.top > viewHeight) {
                    sharedHighlight.classList.remove('active');
                    body.setAttribute('data-morphic-active', 'false');
                    return;
                }

                sharedHighlight.classList.add('active');
                body.setAttribute('data-morphic-active', 'true');

                // Interpolate based on the progress of scroll between source and target
                // Total distance to travel in scroll pixels
                const travelDist = targetCenter - sourceCenter;
                const totalScrollDist = (targetRect.top + window.scrollY) - (sourceRect.top + window.scrollY);

                // Define active range: from source being at 30% of height to target being at 30%
                const startPoint = viewHeight * 0.4; // source at 40% height
                const endPoint = viewHeight * 0.4;   // target at 40% height

                // Progress is 0 when source is at startPoint, 1 when target is at endPoint
                const pStart = sourceCenter;
                const pEnd = targetCenter;

                let progress = (startPoint - sourceCenter) / (targetCenter - sourceCenter);
                progress = Math.max(0, Math.min(1, progress));

                // Easing for a "premium" feel
                const ease = p => p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
                const easedProgress = ease(progress);

                // Interpolate values
                const currentWidth = sourceRect.width + (targetRect.width - sourceRect.width) * easedProgress;
                const currentLeft = sourceRect.left + (targetRect.left - sourceRect.left) * easedProgress;
                const currentTop = sourceRect.top + (targetRect.top - sourceRect.top) * easedProgress;

                // Apply transforms
                // We use fixed positioning, so we just update top/left
                sharedHighlight.style.width = `${currentWidth}px`;
                sharedHighlight.style.left = `${currentLeft}px`;
                sharedHighlight.style.top = `${currentTop + sourceRect.height - 8}px`; // Align to bottom of text
            }

            window.addEventListener('scroll', updateMorphicHighlight);
            window.addEventListener('resize', updateMorphicHighlight);
            // Initial call
            updateMorphicHighlight();
        }

        ticking = false;
    }

    window.addEventListener('scroll', () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }
    });

    // --- Reactive 3D Device Hover Effect ---
    const elementToHover = document.querySelector('.hero-blue-visual');
    const stageToRotate = document.querySelector('.showcase-stage');

    if (elementToHover && stageToRotate) {
        elementToHover.addEventListener('mousemove', (e) => {
            const rect = elementToHover.getBoundingClientRect();
            const x = e.clientX - rect.left; // Mouse X relative to container
            const y = e.clientY - rect.top; // Mouse Y relative to container

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            // Calculate rotation (max +/- 20 degrees) - More Energetic
            const rotateY = ((x - centerX) / centerX) * 20;
            const rotateX = -((y - centerY) / centerY) * 20;

            stageToRotate.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;

            // Parallax for individual devices
            const devices = document.querySelectorAll('.device');
            devices.forEach(device => {
                const speed = parseFloat(device.getAttribute('data-speed')) || 2;
                // Significant increase in movement range for "energetic" feel
                const moveX = ((x - centerX) / centerX) * speed * 12;
                const moveY = ((y - centerY) / centerY) * speed * 12;

                device.style.setProperty('--parallax-x', `${moveX}px`);
                device.style.setProperty('--parallax-y', `${moveY}px`);
            });
        });

        elementToHover.addEventListener('mouseleave', () => {
            // Reset rotation
            stageToRotate.style.transition = 'transform 0.5s ease-out';
            stageToRotate.style.transform = 'rotateX(0deg) rotateY(0deg)';

            // Reset Parallax
            const devices = document.querySelectorAll('.device');
            devices.forEach(device => {
                device.style.transition = 'transform 0.5s ease-out'; // Smooth return
                device.style.setProperty('--parallax-x', '0px');
                device.style.setProperty('--parallax-y', '0px');

                // Restore original transition after a delay to allow re-entry without lag
                setTimeout(() => {
                    device.style.transition = 'transform 0.1s ease-out';
                }, 500);
            });

            setTimeout(() => {
                stageToRotate.style.transition = 'transform 0.1s ease-out'; // Restore quick response
            }, 500);
        });

        elementToHover.addEventListener('mouseenter', () => {
            stageToRotate.style.transition = 'transform 0.1s ease-out';
        });
    }

});


/* =========================================
   NEW CLIENTS FEEDBACK SECTION LOGIC
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    // Only run if the new container exists
    if (!document.getElementById('client_form_cards_container')) return;

    // Client Logos Data
    const client_form_logos_data = [
        "TechCorp",
        "InnovateLab",
        "GlobalSoft",
        "DataFlow Inc",
        "CloudNine",
        "FutureWorks",
        "DigitalEdge",
        "SmartSolutions",
        "NextGen Tech",
        "ProActive",
        "SkyBridge",
        "WebMasters"
    ];

    // Testimonials Data
    const client_form_testimonials_data = [
        {
            name: "Sarah Mitchell",
            role: "CEO, TechCorp",
            feedback: "Working with this team transformed our digital presence. Their attention to detail and innovative approach exceeded all expectations. We saw a 300% increase in user engagement!",
            image: "https://i.pravatar.cc/150?img=1",
            linkedin: "https://www.linkedin.com/in/sarah-mitchell"
        },
        {
            name: "David Chen",
            role: "CTO, InnovateLab",
            feedback: "Exceptional service and outstanding results. The team's expertise in modern web technologies helped us launch our product ahead of schedule. Highly recommended!",
            image: "https://i.pravatar.cc/150?img=13",
            linkedin: "https://www.linkedin.com/in/david-chen"
        },
        {
            name: "Emily Rodriguez",
            role: "Marketing Director, GlobalSoft",
            feedback: "The creative solutions and professional execution made our project a huge success. Their ability to understand our vision and bring it to life was remarkable.",
            image: "https://i.pravatar.cc/150?img=5",
            linkedin: "https://www.linkedin.com/in/emily-rodriguez"
        },
        {
            name: "Michael Thompson",
            role: "Founder, DataFlow Inc",
            feedback: "From concept to deployment, the entire process was seamless. The team's dedication and technical prowess delivered results beyond our imagination. Outstanding work!",
            image: "https://i.pravatar.cc/150?img=12",
            linkedin: "https://www.linkedin.com/in/michael-thompson"
        },
        {
            name: "Jessica Park",
            role: "Product Manager, CloudNine",
            feedback: "Their innovative approach and cutting-edge solutions helped us stand out in a competitive market. The ROI has been phenomenal. Truly a game-changer!",
            image: "https://i.pravatar.cc/150?img=9",
            linkedin: "https://www.linkedin.com/in/jessica-park"
        },
        {
            name: "Robert Anderson",
            role: "VP Engineering, FutureWorks",
            feedback: "Professional, efficient, and incredibly talented. They turned our complex requirements into an elegant solution that our users absolutely love.",
            image: "https://i.pravatar.cc/150?img=33",
            linkedin: "https://www.linkedin.com/in/robert-anderson"
        }
    ];

    // Populate Company Logos with Marquee
    function client_form_populate_logos() {
        const client_form_marquee_track = document.getElementById('client_form_marquee_track');
        if (!client_form_marquee_track) return;

        // Create double set for seamless loop
        const client_form_doubled_logos = [...client_form_logos_data, ...client_form_logos_data];

        client_form_doubled_logos.forEach(client_form_logo_name => {
            const client_form_logo_item = document.createElement('div');
            client_form_logo_item.className = 'client_form_logo_item';
            client_form_logo_item.innerHTML = `<div class="client_form_logo_text">${client_form_logo_name}</div>`;
            client_form_marquee_track.appendChild(client_form_logo_item);
        });
    }

    // Populate Testimonial Cards
    function client_form_populate_cards() {
        const client_form_cards_container = document.getElementById('client_form_cards_container');
        if (!client_form_cards_container) return;

        // Use single set for normal scrolling
        client_form_testimonials_data.forEach((client_form_testimonial, client_form_index) => {
            const client_form_card = document.createElement('div');
            client_form_card.className = 'client_form_card';
            client_form_card.dataset.index = client_form_index;
            client_form_card.id = `client_form_card_${client_form_index}`;

            const client_form_linkedin_icon = `
                <svg class="client_form_linkedin_icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
            `;

            client_form_card.innerHTML = `
                <div class="client_form_card_header">
                    <img src="${client_form_testimonial.image}" alt="${client_form_testimonial.name}" class="client_form_profile_pic">
                    <div class="client_form_profile_info">
                        <div class="client_form_name">${client_form_testimonial.name}</div>
                        <div class="client_form_role">${client_form_testimonial.role}</div>
                    </div>
                    ${client_form_testimonial.linkedin ? `<a href="${client_form_testimonial.linkedin}" target="_blank" rel="noopener noreferrer" class="client_form_linkedin_link" aria-label="Visit ${client_form_testimonial.name}'s LinkedIn profile">${client_form_linkedin_icon}</a>` : ''}
                </div>
                <div class="client_form_feedback">${client_form_testimonial.feedback}</div>
            `;

            client_form_cards_container.appendChild(client_form_card);
        });
    }

    // Normal scrolling functionality
    let client_form_current_card_index = 0;
    let client_form_is_scrolling = false;

    const client_form_cards_wrapper = document.querySelector('.client_form_cards_wrapper');
    const client_form_cards_container = document.getElementById('client_form_cards_container');
    const client_form_prev_btn = document.getElementById('client_form_prev_btn');
    const client_form_next_btn = document.getElementById('client_form_next_btn');

    // Manual navigation with smooth scroll
    function client_form_navigate(client_form_direction) {
        if (client_form_is_scrolling) return;

        const client_form_cards = document.querySelectorAll('.client_form_card');
        if (client_form_cards.length === 0) return;

        client_form_is_scrolling = true;

        if (client_form_direction === 'next') {
            client_form_current_card_index++;
        } else {
            client_form_current_card_index--;
        }

        // Handle wrapping
        const client_form_total_cards = client_form_testimonials_data.length;
        if (client_form_current_card_index >= client_form_total_cards) {
            client_form_current_card_index = 0;
        } else if (client_form_current_card_index < 0) {
            client_form_current_card_index = client_form_total_cards - 1;
        }

        // Scroll to the target card
        const client_form_target_card = document.getElementById(`client_form_card_${client_form_current_card_index}`);
        if (client_form_target_card && client_form_cards_wrapper) {
            const client_form_wrapper_rect = client_form_cards_wrapper.getBoundingClientRect();
            const client_form_card_rect = client_form_target_card.getBoundingClientRect();
            const client_form_scroll_top = client_form_cards_wrapper.scrollTop;
            const client_form_target_position = client_form_scroll_top + client_form_card_rect.top - client_form_wrapper_rect.top - 20;

            client_form_cards_wrapper.scrollTo({
                top: client_form_target_position,
                behavior: 'smooth'
            });
        }

        setTimeout(() => {
            client_form_is_scrolling = false;
        }, 800);
    }

    // Event Listeners
    if (client_form_prev_btn) client_form_prev_btn.addEventListener('click', () => client_form_navigate('prev'));
    if (client_form_next_btn) client_form_next_btn.addEventListener('click', () => client_form_navigate('next'));

    // Track scroll position to update current card index
    if (client_form_cards_wrapper) {
        client_form_cards_wrapper.addEventListener('scroll', () => {
            const client_form_cards = document.querySelectorAll('.client_form_card');
            if (client_form_cards.length === 0) return;

            const client_form_wrapper_rect = client_form_cards_wrapper.getBoundingClientRect();
            // const client_form_wrapper_top = client_form_wrapper_rect.top;
            const client_form_wrapper_center = client_form_wrapper_rect.top + (client_form_wrapper_rect.height / 2);

            let client_form_closest_index = 0;
            let client_form_closest_distance = Infinity;

            client_form_cards.forEach((client_form_card, client_form_index) => {
                const client_form_card_rect = client_form_card.getBoundingClientRect();
                const client_form_card_center = client_form_card_rect.top + (client_form_card_rect.height / 2);
                const client_form_distance = Math.abs(client_form_card_center - client_form_wrapper_center);

                if (client_form_distance < client_form_closest_distance) {
                    client_form_closest_distance = client_form_distance;
                    client_form_closest_index = client_form_index;
                }
            });

            client_form_current_card_index = client_form_closest_index;
        });
    }

    // Create animated background particles
    function client_form_create_particles() {
        const client_form_particles_container = document.getElementById('client_form_bg_particles');
        if (!client_form_particles_container) return;
        const client_form_particle_count = 15;

        for (let i = 0; i < client_form_particle_count; i++) {
            const client_form_particle = document.createElement('div');
            client_form_particle.className = 'client_form_particle';

            // Random size between 100px and 300px
            const client_form_size = Math.random() * 200 + 100;
            client_form_particle.style.width = client_form_size + 'px';
            client_form_particle.style.height = client_form_size + 'px';

            // Random position
            client_form_particle.style.left = Math.random() * 100 + '%';
            client_form_particle.style.top = Math.random() * 100 + '%';

            // Random animation delay
            client_form_particle.style.animationDelay = Math.random() * 20 + 's';
            client_form_particle.style.animationDuration = (Math.random() * 10 + 15) + 's';

            client_form_particles_container.appendChild(client_form_particle);
        }
    }


    // Add extra blob to logos section
    function client_form_add_logos_blob() {
        const client_form_logos_section = document.querySelector('.client_form_logos_section');
        if (client_form_logos_section) {
            const client_form_extra_blob = document.createElement('div');
            client_form_extra_blob.className = 'client_form_logos_blob_extra';
            client_form_logos_section.appendChild(client_form_extra_blob);
        }
    }

    // Initialize
    client_form_populate_logos();
    client_form_populate_cards();
    client_form_create_particles();
    client_form_add_logos_blob();
});
