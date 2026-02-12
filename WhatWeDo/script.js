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
        particleCount: 150, // Reduced for cleaner look
        colorBase: 'rgba(148, 163, 184, ', // Slate 400 (Light subtle dots)
        colorHighlight: 'rgba(59, 130, 246, ', // Blue 500 (Soft blue glow)
        colorAccent: 'rgba(203, 213, 225, ', // Slate 300 (Very light lines)
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
    // STICKY SCROLL STACKING TESTIMONIALS
    // =========================================

    // Testimonial Data
    const testimonials = [
        {
            id: 1,
            name: "Sarah Mitchell",
            position: "Chief Technology Officer",
            company: "TechVision Inc.",
            text: "Transformed our entire infrastructure with their innovative solutions. The team's dedication and expertise exceeded all our expectations.",
            image: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop"
        },
        {
            id: 2,
            name: "Marcus Chen",
            position: "VP of Engineering",
            company: "CloudScale Systems",
            text: "Exceptional service delivery and outstanding technical expertise. They brought our vision to life with precision and creativity.",
            image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop"
        },
        {
            id: 3,
            name: "Elena Rodriguez",
            position: "Head of Innovation",
            company: "Digital Dynamics",
            text: "A partnership that consistently exceeds expectations. Their innovative approach transformed our digital presence completely.",
            image: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop"
        },
        {
            id: 4,
            name: "James Anderson",
            position: "CEO & Founder",
            company: "Quantum Ventures",
            text: "The strategic partner every growing company needs. Their solutions scaled perfectly with our business growth.",
            image: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop"
        },
        {
            id: 5,
            name: "Priya Sharma",
            position: "Director of Product",
            company: "InnovateLabs",
            text: "Incredible attention to detail and commitment to excellence. Every deliverable was polished and exceeded our standards.",
            image: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=400&fit=crop"
        }
    ];

    const section = document.getElementById('clientsSection');
    const cardsContainer = document.getElementById('client_form_cards_container');

    if (!section || !cardsContainer) return;

    // Generate testimonial cards
    function generateTestimonialCards() {
        cardsContainer.innerHTML = ''; // Clear existing content

        testimonials.forEach((testimonial, index) => {
            const card = document.createElement('div');
            card.className = 'testimonial-card';
            card.setAttribute('data-index', index);
            card.style.zIndex = index + 1; // Higher z-index for later cards

            card.innerHTML = `
                <div class="testimonial-card-content">
                    <div class="testimonial-text">
                        "${testimonial.text}"
                    </div>
                    <div class="testimonial-author">
                        <img src="${testimonial.image}" alt="${testimonial.name}" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>${testimonial.name}</h4>
                            <p>${testimonial.position} at ${testimonial.company}</p>
                        </div>
                    </div>
                </div>
            `;

            cardsContainer.appendChild(card);
        });
    }

    // Scroll-based animation controller
    function setupStickyScrollAnimation() {
        const cards = document.querySelectorAll('.testimonial-card');
        const totalCards = cards.length;

        if (totalCards === 0) return;

        // Calculate scroll progress through the 300vh section
        function updateCardAnimations() {
            const rect = section.getBoundingClientRect();
            const sectionHeight = section.offsetHeight;
            const viewportHeight = window.innerHeight;

            // Progress from 0 (top of section at viewport top) to 1 (bottom of section at viewport bottom)
            const scrollStart = -rect.top;
            const scrollRange = sectionHeight - viewportHeight;
            const progress = Math.max(0, Math.min(1, scrollStart / scrollRange));

            // Animate each card based on progress
            cards.forEach((card, index) => {
                const cardProgress = totalCards > 1 ? index / (totalCards - 1) : 0;
                const startProgress = cardProgress * 0.7; // Cards start appearing progressively
                const endProgress = Math.min(1, startProgress + 0.3); // Each card has 30% of scroll range

                // Calculate individual card progress (0 to 1)
                const individualProgress = Math.max(0, Math.min(1,
                    (progress - startProgress) / (endProgress - startProgress)
                ));

                if (individualProgress <= 0) {
                    // Card hasn't started yet - keep it below
                    card.style.transform = 'translateY(100%)';
                    card.style.opacity = '0';
                    card.style.clipPath = 'inset(100% 0% 0% 0%)';
                } else if (individualProgress >= 1) {
                    // Card fully visible and anchored at top
                    card.style.transform = 'translateY(0%)';
                    card.style.opacity = '1';
                    card.style.clipPath = 'inset(0% 0% 0% 0%)';
                } else {
                    // Card is animating - slide up
                    const translateY = 100 * (1 - individualProgress);
                    const clipInset = 100 * (1 - individualProgress);

                    card.style.transform = `translateY(${translateY}%)`;
                    card.style.opacity = `${individualProgress}`;
                    card.style.clipPath = `inset(${clipInset}% 0% 0% 0%)`;
                }
            });
        }

        // Listen to scroll events
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    updateCardAnimations();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Initial update
        updateCardAnimations();
    }


    // Initialize
    generateTestimonialCards();
    setupStickyScrollAnimation();

    // =========================================
    // LOGO MARQUEE SECTION
    // =========================================

    // Client logos data
    const clientLogos = [
        { name: "TechVision Inc.", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=TechVision" },
        { name: "CloudScale Systems", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=CloudScale" },
        { name: "Digital Dynamics", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=Digital+Dynamics" },
        { name: "Quantum Ventures", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=Quantum" },
        { name: "InnovateLabs", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=InnovateLabs" },
        { name: "NextGen Solutions", logo: "https://via.placeholder.com/150x60/0b5394/ffffff?text=NextGen" }
    ];

    const marqueeTrack = document.getElementById('client_form_marquee_track');

    if (marqueeTrack) {
        // Duplicate logos 3 times for infinite scroll effect
        const duplicatedLogos = [...clientLogos, ...clientLogos, ...clientLogos];

        duplicatedLogos.forEach(client => {
            const logoItem = document.createElement('div');
            logoItem.className = 'client_form_marquee_item';
            logoItem.innerHTML = `
                <img src="${client.logo}" alt="${client.name}" />
            `;
            marqueeTrack.appendChild(logoItem);
        });
    }
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
   NEW CLIENTS FEEDBACK SECTION LOGIC (Restored for Logos & Particles)
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    // Only run if the new container exists
    if (!document.getElementById('client_form_cards_container')) return;

    // Client Logos Data with Icons
    const client_form_logos_data = [
        { name: "TechCorp", icon: "fas fa-microchip" },
        { name: "InnovateLab", icon: "fas fa-flask" },
        { name: "GlobalSoft", icon: "fas fa-globe" },
        { name: "DataFlow", icon: "fas fa-database" },
        { name: "CloudNine", icon: "fas fa-cloud" },
        { name: "FutureWorks", icon: "fas fa-rocket" },
        { name: "DigitalEdge", icon: "fas fa-laptop-code" },
        { name: "SmartSolutions", icon: "far fa-lightbulb" },
        { name: "NextGen", icon: "fas fa-robot" },
        { name: "ProActive", icon: "fas fa-bolt" },
        { name: "SkyBridge", icon: "fab fa-mixcloud" },
        { name: "WebMasters", icon: "fas fa-code" }
    ];

    // Define empty array to prevent crashes in event listeners
    const client_form_testimonials_data = [];

    // Populate Company Logos with Marquee
    function client_form_populate_logos() {
        const client_form_marquee_track = document.getElementById('client_form_marquee_track');
        if (!client_form_marquee_track) return;

        // Clear existing content just in case
        client_form_marquee_track.innerHTML = '';

        // Create double set for seamless loop
        const client_form_doubled_logos = [...client_form_logos_data, ...client_form_logos_data];

        client_form_doubled_logos.forEach(brand => {
            const client_form_logo_item = document.createElement('div');
            client_form_logo_item.className = 'client_form_logo_item';
            client_form_logo_item.innerHTML = `
                <div class="client_form_logo_icon"><i class="${brand.icon}"></i></div>
                <div class="client_form_logo_text">${brand.name}</div>
            `;
            client_form_marquee_track.appendChild(client_form_logo_item);
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
            const client_form_size = Math.random() * 200 + 100;
            client_form_particle.style.width = client_form_size + 'px';
            client_form_particle.style.height = client_form_size + 'px';
            client_form_particle.style.left = Math.random() * 100 + '%';
            client_form_particle.style.top = Math.random() * 100 + '%';
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
    client_form_create_particles();
    client_form_add_logos_blob();
});


document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // "Who We Are" Diagonal Mesh Animation (Ambient Wave)
    // =========================================
    const canvas = document.getElementById('who-we-are-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const section = document.getElementById('aboutUsSection');

    let width, height;
    let gridPoints = [];

    const CONFIG = {
        gridSize: 56,
        lineColor: 'rgba(11, 83, 148, 0.22)',
        vertexColor: 'rgba(11, 83, 148, 0.28)',
        waveSpeed: 0.0016,
        waveAmplitude: 10
    };

    function resize() {
        width = canvas.width = section.offsetWidth;
        height = canvas.height = section.offsetHeight;
        initMesh();
    }

    class Point {
        constructor(x, y, col, row) {
            this.baseX = x;
            this.baseY = y;
            this.x = x;
            this.y = y;
            // Alternating Phase: (col + row) % 2 creates a checkerboard pattern
            // Points (0,0), (1,1), (2,0) etc. move together
            // Points (0,1), (1,0), (2,1) etc. move in opposition
            this.offset = (col + row) % 2 === 0 ? 0 : Math.PI;
        }

        update(time) {
            // "Rise and Fall" Illusion
            // Only modify Y axis to keep the grid structure rigid (no X distortion)
            // The sine wave creates a smooth up/down motion
            // The offset makes neighbors move in opposite directions
            const oscillation = Math.sin(time * CONFIG.waveSpeed + this.offset);

            this.y = this.baseY + oscillation * CONFIG.waveAmplitude;
            this.x = this.baseX; // Keep X fixed to maintain shape
        }
    }

    function initMesh() {
        gridPoints = [];
        const cols = Math.ceil(width / CONFIG.gridSize) + 2;
        const rows = Math.ceil(height / CONFIG.gridSize) + 2;

        for (let i = -1; i < cols; i++) {
            gridPoints[i] = [];
            for (let j = -1; j < rows; j++) {
                // Pass indices (i, j) to constructor for phase calculation
                gridPoints[i][j] = new Point(i * CONFIG.gridSize, j * CONFIG.gridSize, i, j);
            }
        }
    }

    function animate(time) {
        ctx.clearRect(0, 0, width, height);

        const cols = gridPoints.length;

        for (let i = 0; i < cols; i++) {
            if (!gridPoints[i]) continue;
            const rows = gridPoints[i].length;
            for (let j = 0; j < rows; j++) {
                gridPoints[i][j].update(time);
            }
        }

        ctx.strokeStyle = CONFIG.lineColor;
        ctx.lineWidth = 1;
        ctx.beginPath();

        for (let i = 0; i < cols - 1; i++) {
            if (!gridPoints[i]) continue;
            const rows = gridPoints[i].length;
            for (let j = 0; j < rows - 1; j++) {
                const p1 = gridPoints[i][j];
                const p2 = gridPoints[i + 1][j];
                const p3 = gridPoints[i][j + 1];
                const p4 = gridPoints[i + 1][j + 1];

                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p3.x, p3.y);
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p4.x, p4.y);
            }
        }
        ctx.stroke();

        // Draw sparse vertices to make the mesh more visible without crowding text.
        ctx.fillStyle = CONFIG.vertexColor;
        for (let i = 0; i < cols; i++) {
            if (!gridPoints[i]) continue;
            const rows = gridPoints[i].length;
            for (let j = 0; j < rows; j++) {
                if ((i + j) % 2 !== 0) continue;
                const p = gridPoints[i][j];
                ctx.fillRect(p.x - 0.8, p.y - 0.8, 1.6, 1.6);
            }
        }

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resize);
    setTimeout(() => { resize(); requestAnimationFrame(animate); }, 100);
});

// =========================================
// "Comprehensive Solutions" Reveal on Scroll
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    // Target the section header
    const solutionsHeader = document.querySelector('#what-we-do .section-header');
    if (solutionsHeader) {
        solutionsHeader.classList.add('reveal-on-scroll');
        revealObserver.observe(solutionsHeader);
    }

    // Target each feature card with staggered delay
    const featureCards = document.querySelectorAll('#what-we-do .feature-card');
    featureCards.forEach((card, index) => {
        card.classList.add('reveal-on-scroll');
        card.style.transitionDelay = `${(index + 1) * 120}ms`;
        revealObserver.observe(card);
    });

    // =========================================
    // "Have You Seen Our Works" Reveal on Scroll
    // =========================================

    // Target the section header
    const worksHeader = document.querySelector('#our-works .section-header');
    if (worksHeader) {
        worksHeader.classList.add('reveal-on-scroll');
        revealObserver.observe(worksHeader);
    }

    // Target the cube grid with delay
    const worksGrid = document.querySelector('.cube-structure-grid');
    if (worksGrid) {
        worksGrid.classList.add('reveal-on-scroll');
        worksGrid.style.transitionDelay = '200ms';
        revealObserver.observe(worksGrid);
    }

});

// =========================================
// CUBE SLIDER LOGIC
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    const cubeWrapper = document.querySelector('.cube-swiper .swiper-wrapper');
    const cubeTitle = document.getElementById('cube-title');
    const cubeDesc = document.getElementById('cube-desc');
    const cubeTech = document.getElementById('cube-tech');

    const cubeLive = document.getElementById('cube-live');

    if (cubeWrapper && typeof Swiper !== 'undefined') {
        // 1. Inject Slides from 'projects' data
        const projectIds = Object.keys(projects);

        projectIds.forEach(id => {
            const p = projects[id];

            const slide = document.createElement('div');
            slide.className = 'swiper-slide';
            slide.setAttribute('data-id', id);

            slide.innerHTML = `<img src="${p.img}" alt="${p.title}" />`;

            cubeWrapper.appendChild(slide);
        });

        // 2. Initialize Swiper
        const cubeSwiper = new Swiper(".cube-swiper", {
            effect: "cube",
            grabCursor: true,
            loop: true,
            speed: 1000,
            cubeEffect: {
                shadow: false,
                slideShadows: true,
                shadowOffset: 10,
                shadowScale: 0.94,
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            on: {
                slideChange: function () {
                    updateDetails(this.realIndex);
                }
            }
        });

        // 3. Update Details Panel
        function updateDetails(realIndex) {
            const pKey = projectIds[realIndex];
            const p = projects[pKey];

            if (p) {
                const detailsPanel = document.querySelector('.cube-details-panel');
                if (detailsPanel) {
                    detailsPanel.style.opacity = '0';
                    detailsPanel.style.transform = 'translateY(10px)';
                }

                setTimeout(() => {
                    cubeTitle.innerText = p.title;
                    cubeDesc.innerText = p.desc;

                    if (cubeTech) {
                        cubeTech.innerHTML = '';
                        if (p.tech && p.tech.length > 0) {
                            p.tech.forEach(t => {
                                const badge = document.createElement('span');
                                badge.className = 'cube-tech-badge';
                                badge.innerText = t;
                                cubeTech.appendChild(badge);
                            });
                        }
                    }


                    if (cubeLive) {
                        cubeLive.href = p.link || '#';
                        cubeLive.style.opacity = (p.link === '#' || !p.link) ? '0.5' : '1';
                        cubeLive.style.pointerEvents = (p.link === '#' || !p.link) ? 'none' : 'auto';
                    }

                    if (detailsPanel) {
                        detailsPanel.style.opacity = '1';
                        detailsPanel.style.transform = 'translateY(0)';
                    }
                }, 200);
            }
        }

        // 4. Initialize with first project
        updateDetails(0);

        // 5. Pause autoplay on hover
        const cubeContainer = document.querySelector('.cube-swiper');
        if (cubeContainer && cubeSwiper.autoplay) {
            cubeContainer.addEventListener('mouseenter', () => {
                cubeSwiper.autoplay.stop();
            });
            cubeContainer.addEventListener('mouseleave', () => {
                cubeSwiper.autoplay.start();
            });
        }
    }
});
