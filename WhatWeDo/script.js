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
        // Optimization: Cache elements to avoid querying DOM on every frame
        const parallaxElements = document.querySelectorAll('.device, .floating-badge');

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
            parallaxElements.forEach(el => {
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
            parallaxElements.forEach(el => {
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
    // === ENHANCED AMBIENT PARTICLES (REFACTORED) ===
    function initParticleCanvas(canvasId, sectionId, cardSelector) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const section = document.getElementById(sectionId);
        if (!section) return;

        let width, height;
        let particles = [];
        let sparkles = [];
        let cardRects = [];
        let activeCardIndex = -1;
        let mouse = { x: -1000, y: -1000 };

        // Local cards for interaction
        const localCards = section.querySelectorAll(cardSelector);

        // Add hover listeners for interaction
        localCards.forEach((card, index) => {
            card.addEventListener('mouseenter', () => { activeCardIndex = index; });
            card.addEventListener('mouseleave', () => { activeCardIndex = -1; });
        });

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
            cardRects = Array.from(localCards).map(card => {
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

        document.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();
            // simple check if mouse is over section vertically
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
                this.baseVx = (Math.random() - 0.5) * 0.5;
                this.baseVy = (Math.random() - 0.5) * 0.5;
                this.ivx = 0;
                this.ivy = 0;
                this.size = Math.random() * 2.5 + 0.5;
                this.baseSize = this.size;
                this.excitement = 0;
                this.opacity = 0.6 + Math.random() * 0.4;
            }

            update() {
                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const distMouse = Math.sqrt(dx * dx + dy * dy);

                if (distMouse < CONFIG.mouseRadius) {
                    this.ivx -= (dx / distMouse) * 0.02;
                    this.ivy -= (dy / distMouse) * 0.02;
                    this.excitement = Math.min(this.excitement + 0.02, 1);
                } else {
                    this.excitement = Math.max(this.excitement - 0.01, 0);
                }

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

                this.ivx *= 0.95;
                this.ivy *= 0.95;

                this.x += (this.baseVx + this.ivx) * (1 + this.excitement * 0.3);
                this.y += (this.baseVy + this.ivy) * (1 + this.excitement * 0.3);

                if (this.x < 0) this.x = width; if (this.x > width) this.x = 0;
                if (this.y < 0) this.y = height; if (this.y > height) this.y = 0;
            }

            draw() {
                const displaySize = this.size * (1 + this.excitement * 0.5);

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

        class Sparkle {
            constructor(x, y) {
                this.x = x;
                this.y = y;
                this.vx = (Math.random() - 0.5) * 2;
                this.vy = -Math.random() * 2 - 1;
                this.life = 1;
                this.size = Math.random() * 2 + 1;
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;
                this.life -= 0.015;
                this.vy += 0.02;
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

            isDead() { return this.life <= 0; }
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
                if (Math.random() < 0.3) {
                    if (cardRects[activeCardIndex]) {
                        const rect = cardRects[activeCardIndex];
                        const pad = 8;
                        const edge = Math.floor(Math.random() * 4);
                        let sx, sy;
                        if (edge === 0) { sx = rect.cx - rect.w / 2 + Math.random() * rect.w; sy = rect.cy - rect.h / 2 - pad; }
                        else if (edge === 1) { sx = rect.cx + rect.w / 2 + pad; sy = rect.cy - rect.h / 2 + Math.random() * rect.h; }
                        else if (edge === 2) { sx = rect.cx - rect.w / 2 + Math.random() * rect.w; sy = rect.cy + rect.h / 2 + pad; }
                        else { sx = rect.cx - rect.w / 2 - pad; sy = rect.cy - rect.h / 2 + Math.random() * rect.h; }
                        sparkles.push(new Sparkle(sx, sy));
                    }
                }
            }

            particles.forEach((p, i) => {
                p.update();
                p.draw();

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

            // Optimized loop to avoid GC thrashing (reusing memory)
            for (let i = sparkles.length - 1; i >= 0; i--) {
                const s = sparkles[i];
                s.update();
                s.draw();
                if (s.isDead()) {
                    sparkles.splice(i, 1);
                }
            }

            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', resize);
        setTimeout(() => { resize(); animate(); }, 100);
        setTimeout(() => { resize(); }, 500);
    }

    // Initialize Canvases
    initParticleCanvas('ambient-canvas', 'what-we-do', '.feature-card');
    initParticleCanvas('works-canvas', 'our-works', '.project-card');

    // Re-implement the entrance animation observer if needed
    // (Note: The original code used global 'cards' for this, which might have been empty or referring to something else.
    // If we want entrance animations for feature-cards, we should do it explicitly)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    // Observer for feature cards
    document.querySelectorAll('.feature-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        card.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(card);
    });



});



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

    // Scroll-based Sticky Stack Animation
    function setupStickyStackAnimation() {
        const section = document.getElementById('clientsSection');
        const cards = document.querySelectorAll('.testimonial-card');
        const totalCards = cards.length;

        if (!section || totalCards === 0) return;

        // Reset cards initially
        cards.forEach((card, index) => {
            card.style.zIndex = index + 1;
            if (index > 0) {
                card.style.transform = 'translateY(110%)'; // Start off-screen
                card.style.opacity = '1';
            } else {
                card.style.transform = 'translateY(0)';
                card.style.opacity = '1';
            }
        });

        function updateCards() {
            // Check if mobile
            if (window.innerWidth <= 768) {
                cards.forEach(card => card.style.transform = 'none');
                return;
            }

            const rect = section.getBoundingClientRect();
            const sectionHeight = section.offsetHeight;
            const viewportHeight = window.innerHeight;

            // Calculate scroll progress (0 to 1) based on sticky section
            // The section is 400vh, but logic applies to the scrollable part (300vh)
            const scrollDist = -rect.top;
            const scrollRange = sectionHeight - viewportHeight;
            let progress = Math.max(0, Math.min(1, scrollDist / scrollRange));

            // Map progress to card index
            // We want to animate card 2, 3, 4, etc. (Card 1 is static at base)
            // Divide progress by number of cards to stack
            const cardsToAnimate = totalCards - 1;
            const progressPerCard = 1 / cardsToAnimate;

            cards.forEach((card, index) => {
                if (index === 0) {
                    // Base card logic: it scales down as subsequent cards arrive
                    // It scales down continuously as progress increases
                    const scale = Math.max(0.9, 1 - (progress * 0.1));
                    card.style.transform = `scale(${scale})`;
                    card.style.opacity = '1';
                } else {
                    // Logic for Card N (where N > 0)
                    // It should slide up when progress reaches its segment

                    // Card 1 starts animating effectively at 0
                    // Card 2 at progress 0.25 (if 4 cards), etc.

                    const cardStartThreshold = (index - 1) * progressPerCard;

                    // Calculate local progress for this specific card
                    let localProgress = (progress - cardStartThreshold) / progressPerCard;
                    localProgress = Math.max(0, Math.min(1, localProgress));

                    // Transformation
                    if (localProgress <= 0) {
                        card.style.transform = 'translateY(110%)';
                    } else if (localProgress >= 1) {
                        // Card is fully stacked. 
                        // It should also scale down slightly if MORE cards are coming after it
                        // Calculate remaining progress AFTER this card was fully revealed
                        const cardEndThreshold = index * progressPerCard;
                        const remainingProgress = (progress - cardEndThreshold) / (1 - cardEndThreshold);
                        const scale = (remainingProgress > 0) ? Math.max(0.9, 1 - (remainingProgress * 0.05)) : 1;

                        card.style.transform = `translateY(0) scale(${scale})`;
                    } else {
                        // Card is sliding up
                        // Ease out
                        const ease = 1 - Math.pow(1 - localProgress, 3);
                        const translateY = 100 * (1 - ease);
                        card.style.transform = `translateY(${translateY}%)`;
                    }
                }
            });
        }

        // Ticking mechanism for performance
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    updateCards();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Resize listener
        window.addEventListener('resize', updateCards);

        // Initial call
        updateCards();

        // Ensure robust init after all resources load
        window.addEventListener('load', updateCards);
    }


    // Initialize
    generateTestimonialCards();
    setupStickyStackAnimation();

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


    // --- Navbar Scroll Effect ---
    const navbar = document.querySelector('.premium-nav');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
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


// === Lenis Smooth Scroll Init ===
document.addEventListener('DOMContentLoaded', () => {
    const lenis = new Lenis({
        duration: 1.5,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        direction: 'vertical',
        gestureDirection: 'vertical',
        smooth: true,
        mouseMultiplier: 1,
        smoothTouch: false,
        touchMultiplier: 2,
        wheelMultiplier: 1.2,
    });

    // Sync Lenis scroll with RequestAnimationFrame
    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }

    requestAnimationFrame(raf);
});

/* =========================================
   BENTO GRID & DETAILS LOGIC
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const bentoGrid = document.getElementById('bento-grid');
    const detailsPanel = document.getElementById('project-details-panel');

    if (!bentoGrid || !detailsPanel) return;

    // Helper to get icon based on title or keywords
    const getIcon = (title) => {
        const t = title.toLowerCase();
        if (t.includes('medical')) return 'fa-solid fa-heart-pulse';
        if (t.includes('travel') || t.includes('splendore')) return 'fa-solid fa-plane-departure';
        if (t.includes('event') || t.includes('euphoria') || t.includes('luke')) return 'fa-solid fa-calendar-star';
        if (t.includes('commerce') || t.includes('cocobies') || t.includes('shop')) return 'fa-solid fa-cart-shopping';
        if (t.includes('campus') || t.includes('connect')) return 'fa-solid fa-graduation-cap';
        if (t.includes('management') || t.includes('ctrm')) return 'fa-solid fa-list-check';
        if (t.includes('reach')) return 'fa-solid fa-hand-holding-heart';
        return 'fa-solid fa-code';
    };

    // Function to render details with transition
    const renderDetails = (id) => {
        const p = projects[id];
        if (!p) return;

        // Create HTML structure
        const html = `
            <div class="detail-content-wrapper fading">
                <img src="${p.img}" alt="${p.title}" class="detail-image">
                <h2 class="detail-title">${p.title}</h2>
                
                <div class="detail-meta-row">
                    <span><i class="fa-solid fa-user"></i> ${p.client}</span>
                    <span><i class="fa-solid fa-calendar"></i> ${p.date}</span>
                </div>

                <p class="detail-desc">${p.desc}</p>

                <div class="tech-stack">
                    ${p.tech.map(t => `<span class="tech-badge">${t}</span>`).join('')}
                </div>

                <a href="${p.link}" target="_blank" class="detail-btn">
                    <span>Live Demo</span> <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        `;

        // Smooth transition logic
        const currentContent = detailsPanel.querySelector('.detail-content-wrapper');

        if (currentContent) {
            // Fade out current
            currentContent.classList.add('fading');
            setTimeout(() => {
                detailsPanel.innerHTML = html;
                // Fade in new (forced reflow/timeout to ensure transition triggers)
                requestAnimationFrame(() => {
                    const newContent = detailsPanel.querySelector('.detail-content-wrapper');
                    if (newContent) newContent.classList.remove('fading');
                });
            }, 300); // Match CSS transition duration
        } else {
            // First render (instant or fade in)
            detailsPanel.innerHTML = html;
            requestAnimationFrame(() => {
                const newContent = detailsPanel.querySelector('.detail-content-wrapper');
                if (newContent) newContent.classList.remove('fading');
            });
        }
    };

    // =========================================
    // MULTI-BATCH BENTO GRID LOGIC
    // =========================================

    // Config
    const BATCH_SIZE = 5;
    const ROTATION_DELAY = 3000; // 3 seconds
    const allProjectIds = Object.keys(projects);

    // State
    let currentBatchStartIndex = 0;
    let autoRotateInterval = null;
    // We track the active card *within* the currently displayed batch (0 to BATCH_SIZE-1)
    // If -1, it means we might be transitioning or user hovered out (though we want to keep one active usually)
    let activeCardIndex = 0;

    // Helper to render a specific batch of 5 cards
    const renderBatch = (startIndex) => {
        bentoGrid.innerHTML = ''; // Clear existing

        // Get the slice of IDs for this batch
        // If we reach the end and have fewer than 5, we loop back to start to always fill 5 slots?
        // Or just show what's left? The CSS assumes 5 items for the 2fr/1fr layout uniqueness.
        // Let's ensure we always get 5 items by wrapping around if needed.
        const batchIds = [];
        for (let i = 0; i < BATCH_SIZE; i++) {
            const index = (startIndex + i) % allProjectIds.length;
            batchIds.push(allProjectIds[index]);
        }

        batchIds.forEach((id, index) => {
            const p = projects[id];
            const card = document.createElement('div');

            // Set active class if it matches the current active index state
            const isActive = index === activeCardIndex;
            card.className = `bento-card ${isActive ? 'active' : ''}`;
            card.dataset.index = index; // Store local batch index (0-4)
            card.dataset.id = id;

            // Staggered entry animation (reset on every batch change)
            card.style.animation = 'none';
            card.offsetHeight; /* trigger reflow */
            card.style.animation = `fadeInUp 0.6s ease forwards ${index * 0.1}s`;

            const iconClass = getIcon(p.title);

            card.innerHTML = `
                <div class="bento-icon-wrapper">
                    <i class="${iconClass}"></i>
                </div>
                <h3>${p.title}</h3>
                <p>${p.desc}</p>
            `;

            // Interaction
            card.addEventListener('mouseenter', () => {
                stopAutoRotation();
                setActiveCard(index);
            });

            card.addEventListener('mouseleave', () => {
                startAutoRotation();
            });

            bentoGrid.appendChild(card);
        });

        // Ensure details match the actively rendered card
        if (batchIds[activeCardIndex]) {
            renderDetails(batchIds[activeCardIndex]);
        }
    };

    // Helper to set active card within the current batch
    const setActiveCard = (index) => {
        // Validation
        const cards = bentoGrid.querySelectorAll('.bento-card');
        if (!cards[index]) return;

        // Visual Update
        cards.forEach(c => c.classList.remove('active'));
        cards[index].classList.add('active');

        // State Update
        activeCardIndex = index;

        // Details Update
        const projectId = cards[index].dataset.id;
        renderDetails(projectId);
    };

    // Auto Rotation Step
    const rotateStep = () => {
        // Move to next card in the batch
        let nextIndex = activeCardIndex + 1;

        // If we've gone through all cards in this batch (0 to 4), switch to NEXT BATCH
        if (nextIndex >= BATCH_SIZE) {
            // Calculate new start index for project list
            currentBatchStartIndex = (currentBatchStartIndex + BATCH_SIZE) % allProjectIds.length;

            // Reset active card to the first one of the new batch
            activeCardIndex = 0;

            // Re-render the whole grid with new projects
            renderBatch(currentBatchStartIndex);
        } else {
            // Just highlight the next card in the SAME batch
            setActiveCard(nextIndex);
        }
    };

    const startAutoRotation = () => {
        stopAutoRotation();
        autoRotateInterval = setInterval(rotateStep, ROTATION_DELAY);
    };

    const stopAutoRotation = () => {
        if (autoRotateInterval) {
            clearInterval(autoRotateInterval);
            autoRotateInterval = null;
        }
    };

    // --- Initialization ---

    // 1. Initial Render (Batch 0)
    renderBatch(0);

    // 2. Start Rotation
    startAutoRotation();

    // 3. Pause on Details Hover
    detailsPanel.addEventListener('mouseenter', stopAutoRotation);
    detailsPanel.addEventListener('mouseleave', startAutoRotation);

    // 4. CSS for Animation
    if (!document.getElementById('bento-animations')) {
        const styleSheet = document.createElement("style");
        styleSheet.id = 'bento-animations';
        styleSheet.innerText = `
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        `;
        document.head.appendChild(styleSheet);
    }
});
