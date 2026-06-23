// --- Initialize Lenis Smooth Scrolling ---
if (typeof Lenis !== 'undefined') {
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // standard easing
        direction: 'vertical',
        gestureDirection: 'vertical',
        smooth: true,
        smoothTouch: false, // Turned off to prevent conflict with normalizeScroll
        touchMultiplier: 2,
    });

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);

    let isMobileLenisKilled = false;

    // Forces scroll onto the main thread on mobile to eliminate compositor divergence 
    // and prevents URL bar height jumping which destroys 100vh canvas alignment
    if (window.innerWidth <= 992) {
        ScrollTrigger.normalizeScroll(true);
        // Completely destroy Lenis loop on mobile because it creates event-loop 
        // feedback vibrations when fighting GSAP's normalizeScroll
        lenis.destroy();
        isMobileLenisKilled = true;
    }

    // Smooth scroll for nav anchor links using Lenis instead of CSS scroll-behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            if (isMobileLenisKilled) {
                // Fallback to native smooth scrolling for anchor links on mobile
                if (href === '#') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const target = document.querySelector(href);
                    if (target) {
                        const top = target.getBoundingClientRect().top + window.scrollY;
                        window.scrollTo({ top, behavior: 'smooth' });
                    }
                }
            } else {
                if (href === '#') {
                    lenis.scrollTo(0);
                } else {
                    const target = document.querySelector(href);
                    if (target) {
                        lenis.scrollTo(target);
                    }
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Sliding Pill Navbar Animation ---
    const navLinksContainer = document.querySelector('.nav-links-container');
    const links = document.querySelectorAll('.nav-link');
    const pill = document.querySelector('.sliding-pill');

    if (navLinksContainer && links.length && pill) {
        let activeLink = document.querySelector('.nav-link.active') || links[0];

        function updatePill(link) {
            if (!link) return;
            const linkRect = link.getBoundingClientRect();
            const containerRect = navLinksContainer.getBoundingClientRect();

            pill.style.width = `${linkRect.width}px`;
            pill.style.left = `${linkRect.left - containerRect.left}px`;
        }

        // Initialize pill position (timeout ensures fonts/layout have loaded)
        setTimeout(() => updatePill(activeLink), 50);

        window.addEventListener('resize', () => {
            updatePill(document.querySelector('.nav-link.active') || links[0]);
        });

        // Track active class changes caused by ScrollSpy down the page
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class' && mutation.target.classList.contains('active')) {
                    activeLink = mutation.target;
                    // Only forcefully move pill if user isn't actively hovering somewhere else
                    if (!navLinksContainer.matches(':hover')) {
                        updatePill(activeLink);
                    }
                }
            });
        });

        links.forEach(link => {
            observer.observe(link, { attributes: true });

            link.addEventListener('mouseenter', () => updatePill(link));

            link.addEventListener('click', () => {
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                activeLink = link;
                updatePill(link);
            });
        });

        navLinksContainer.addEventListener('mouseleave', () => {
            activeLink = document.querySelector('.nav-link.active') || links[0];
            updatePill(activeLink);
        });
    }

    // --- Mobile Menu Toggle ---
    const menuToggleBtn = document.querySelector('.menu-toggle-btn');
    if (menuToggleBtn && navLinksContainer) {
        menuToggleBtn.addEventListener('click', () => {
            menuToggleBtn.classList.toggle('open');
            navLinksContainer.classList.toggle('open');
        });

        // Close mobile menu when a link is clicked
        links.forEach(link => {
            link.addEventListener('click', () => {
                menuToggleBtn.classList.remove('open');
                navLinksContainer.classList.remove('open');
            });
        });
    }

    // --- Entrance Animations ---
    const devices = document.querySelectorAll('.device');
    // Small delay to ensure styles are ready
    setTimeout(() => {
        devices.forEach(device => {
            device.classList.add('animate-in');
        });
    }, 100);

    // --- Hero Slider & Typing Logic ---
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    const visual = document.querySelector('.hero-blue-visual');
    let currentSlide = 0;

    // Typing Effect Setup
    const heroTitles = document.querySelectorAll('.hero-blue-title');
    heroTitles.forEach(title => {
        const typeTarget = title.querySelector('.typing-word');
        if (typeTarget) {
            title.dataset.typeWord = typeTarget.innerHTML.trim();
            typeTarget.innerHTML = '';
        }
    });

    let typingTimeout;
    let heroVisible = false;

    function typeTitle(titleElement) {
        if (!titleElement) return;
        clearTimeout(typingTimeout);

        const typeTarget = titleElement.querySelector('.typing-word');
        if (!typeTarget) return;

        const wordToType = titleElement.dataset.typeWord || '';
        typeTarget.innerHTML = '';
        let charIndex = 0;

        function revealCta() {
            const slide = titleElement.closest('.slide');
            if (slide) {
                const cta = slide.querySelector('.hero-blue-cta');
                if (cta) cta.classList.add('revealed');
            }
        }

        // Hide CTA when typing starts
        const slide = titleElement.closest('.slide');
        if (slide) {
            const cta = slide.querySelector('.hero-blue-cta');
            if (cta) cta.classList.remove('revealed');
        }

        function typeWriter() {
            if (charIndex < wordToType.length) {
                typeTarget.innerHTML = wordToType.substring(0, charIndex + 1) + '<span class="typing-cursor">|</span>';
                charIndex++;
                typingTimeout = setTimeout(typeWriter, 900);
            } else {
                typeTarget.innerHTML = wordToType;
                setTimeout(revealCta, 400); // delay after typing finishes before button appears
            }
        }
        typingTimeout = setTimeout(typeWriter, 1500); // 1.5s delay before typing starts
    }

    const heroSection = document.getElementById('hero-blue');
    if (heroSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !heroVisible) {
                    heroVisible = true;
                    // Type the active slide's title
                    const activeSlide = heroSection.querySelector('.slide.active');
                    if (activeSlide) {
                        const title = activeSlide.querySelector('.hero-blue-title');
                        typeTitle(title);
                    }
                }
            });
        }, { threshold: 0.3 });
        observer.observe(heroSection);
    }

    function goToSlide(index) {
        // Update Text Slides
        slides.forEach(slide => slide.classList.remove('active'));
        if (slides[index]) {
            slides[index].classList.add('active');
            if (heroVisible) {
                const title = slides[index].querySelector('.hero-blue-title');
                typeTitle(title);
            }
        }

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

        let _sk = false;
        function animate() {
            if (window.innerWidth < 768) { _sk = !_sk; if (_sk) { requestAnimationFrame(animate); return; } }
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
    // works-canvas particle system removed — replaced by ambient orb system in the new pj- section


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

    // =========================================
    // GSAP SCROLL-DRIVEN BENTO ANIMATION
    // =========================================
    if (typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined") {
        gsap.registerPlugin(ScrollTrigger);

        // --- Proxy for Mobile WebGL Sync ---
        // Emits a perfectly synced 0-to-1 float for the Hero section's scroll
        ScrollTrigger.create({
            trigger: '#hero-blue',
            start: 'top top',
            end: 'bottom top',
            onUpdate: (self) => {
                window.heroScrollProgress = self.progress;
            }
        });

        const bentoSection = document.getElementById('what-we-do');
        const bentoGrid = document.querySelector('.feature-bento-grid');
        const centerDefault = document.querySelector('.bento-center-default');
        const centerBg = document.querySelector('.bento-center');
        const outerCards = [
            document.querySelector('.bento-top-left'),
            document.querySelector('.bento-top-right'),
            document.querySelector('.bento-bottom-left'),
            document.querySelector('.bento-bottom-right')
        ];

        if (bentoSection && bentoGrid) {
            const isMobile = window.innerWidth <= 992;

            // First, override the CSS transition from the observer above
            outerCards.forEach(card => {
                if (card) {
                    card.style.transition = 'none';
                }
            });

            // Make the center background initially transparent
            gsap.set(centerBg, { backgroundColor: 'transparent', boxShadow: 'none', zIndex: 100 });

            // Start the central text appropriately sized and positioned based on flow
            gsap.set(centerDefault, {
                position: isMobile ? "relative" : "absolute",
                top: isMobile ? "auto" : "30%", // Perfectly positions exactly above robot destination on desktop
                left: isMobile ? "auto" : "50%",
                xPercent: isMobile ? 0 : -50,
                yPercent: isMobile ? 0 : -50,
                // On mobile, allow a moderate initial scale factor to achieve the "hero shrink" effect without harsh boundary cropping
                scale: isMobile ? 1.15 : 3,
                zIndex: 101, // Stay above Robot (z-index 50) and parent wrapper
                transformOrigin: "center center"
            });

            // Initially hide the targets depending on device layout
            if (isMobile) {
                // outerCards (slides 1-4) should be hidden until the title slide finishes its entrance
                outerCards.forEach(card => {
                    if (card) gsap.set(card, { autoAlpha: 0 });
                });
                const dots = document.querySelector('.feature-slider-dots');
                if (dots) gsap.set(dots, { autoAlpha: 0 });
                // We do NOT hide the mobileSliderWrapper anymore because it now holds the title text slide!
            } else {
                outerCards.forEach(card => {
                    if (card) gsap.set(card, { autoAlpha: 0 });
                });
            }

            // 0. Mask Reveal Entrance Animation (Runs once when section enters view)
            const revealTexts = document.querySelectorAll('.reveal-text');
            if (revealTexts.length > 0) {
                gsap.set(revealTexts, { clearProps: "transform,opacity,visibility" });
                gsap.fromTo(revealTexts,
                    { y: 100, autoAlpha: 0 },
                    {
                        y: 0,
                        autoAlpha: 1,
                        duration: 1,
                        ease: "power3.out",
                        stagger: 0.2, // Staggered reveal for each line
                        scrollTrigger: {
                            trigger: bentoSection,
                            start: "top 80%",
                            once: true // Trigger only once
                        }
                    }
                );
            }

            // Create the Pinning Timeline (Identical for Desktop & Mobile to empower Robot.js completely)
            const tl = gsap.timeline({
                scrollTrigger: {
                    trigger: bentoSection,
                    start: "top top", // Trigger when section hits top of viewport
                    end: "+=150%",     // Pin for 1.5x viewport height of scrolling
                    pin: true,        // Screen lock for both!
                    scrub: 1,         // Smooth scrubbing
                    onUpdate: (self) => {
                        window.bentoScrollProgress = self.progress; // Critically powers Robot.js
                    },
                    onLeaveBack: () => {
                        // If user scrolls backwards (up) out of the section, force horizontal slider to reset
                        if (window.innerWidth <= 992) {
                            const mSlider = document.querySelector('.mobile-feature-slider');
                            if (mSlider && mSlider.scrollLeft > 0) {
                                mSlider.scrollTo({ left: 0, behavior: 'instant' });
                                if (window.lastActiveRobotProp !== null) {
                                    window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title: null } }));
                                    window.lastActiveRobotProp = null;
                                }
                            }
                        }
                    }
                }
            });

            // 1. Center text shrinks down to standard size
            tl.to(centerDefault, {
                scale: 1,
                // Do not deploy absolute positioning translations on mobile since it utilizes native flex-grid layout!
                top: isMobile ? "auto" : "50%",
                left: isMobile ? "auto" : "50%",
                xPercent: isMobile ? 0 : -50,
                yPercent: isMobile ? 0 : -50,
                zIndex: 10,
                ease: "power2.inOut",
                duration: 2
            }, 0);

            // 2. Background fades in (soft lavender-blue, pairs with the purple side cards)
            tl.to(centerBg, {
                backgroundColor: isMobile ? '#ffffff' : '#eef0ff',
                borderColor: isMobile ? 'transparent' : 'rgba(120, 100, 220, 0.25)',
                boxShadow: isMobile ? '0 10px 30px -5px rgba(11, 83, 148, 0.15)' : `
                    0 1px 0 0 rgba(255,255,255,0.8) inset,
                    0 -1px 0 0 rgba(100, 80, 200, 0.12) inset,
                    0 4px 6px -1px rgba(80, 60, 180, 0.08),
                    0 12px 24px -4px rgba(80, 60, 180, 0.14),
                    0 32px 64px -8px rgba(80, 60, 180, 0.18),
                    0 2px 4px 0 rgba(80, 60, 180, 0.06)
                `,
                ease: "power1.inOut",
                duration: 1
            }, 1);

            // 3. UI Splice Entrance (Cards or Slider)
            if (isMobile) {
                // The subsequent 4 slides, dots, and swipe indicator fade into view adjacent to the title slide
                tl.fromTo(outerCards, { y: 20, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 1.5, stagger: 0.1, ease: "power2.out" }, 1);
                tl.fromTo('.feature-slider-dots, .card-swipe-arrow', { autoAlpha: 0 }, { autoAlpha: 1, duration: 1.5, ease: "power2.out" }, 1);
            } else {
                tl.fromTo(outerCards[0], { x: -100, y: -50 }, { x: 0, y: 0, autoAlpha: 1, duration: 1.5, ease: "power2.out" }, 1); // Top Left
                tl.fromTo(outerCards[1], { x: 100, y: -50 }, { x: 0, y: 0, autoAlpha: 1, duration: 1.5, ease: "power2.out" }, 1.2); // Top Right
                tl.fromTo(outerCards[2], { x: -100, y: 50 }, { x: 0, y: 0, autoAlpha: 1, duration: 1.5, ease: "power2.out" }, 1.4); // Bottom Left
                tl.fromTo(outerCards[3], { x: 100, y: 50 }, { x: 0, y: 0, autoAlpha: 1, duration: 1.5, ease: "power2.out" }, 1.6); // Bottom Right
            }

            // --- Mobile Slider Navigation Dots Sync ---
            const mobileSliderWrapper = document.querySelector('.mobile-feature-slider');
            if (mobileSliderWrapper) {
                const dots = document.querySelectorAll('.feature-slider-dots .feature-dot');
                const swipeArrow = document.querySelector('.card-swipe-arrow');
                if (dots.length > 0) {
                    mobileSliderWrapper.addEventListener('scroll', () => {
                        const scrollLeft = mobileSliderWrapper.scrollLeft;
                        // Query the first slide (which could be the feature-card or the bento-center slide)
                        const cardElement = mobileSliderWrapper.querySelector('.feature-card, .feature-card-slide');
                        if (!cardElement) return;

                        const cardWidth = cardElement.offsetWidth;
                        const gap = 20; // Matches CSS 1.25rem gap
                        const index = Math.round(scrollLeft / (cardWidth + gap));

                        dots.forEach((dot, i) => {
                            dot.classList.toggle('active', i === index);
                        });

                        if (swipeArrow) {
                            swipeArrow.style.transition = 'opacity 0.3s ease';
                            swipeArrow.style.opacity = index >= dots.length - 1 ? '0' : '1';
                        }

                        // Programmatically trigger robot prop holding by checking active centered card
                        const allCards = mobileSliderWrapper.querySelectorAll('.feature-card-slide, .feature-card');
                        if (allCards[index] && allCards[index].classList.contains('feature-card')) {
                            const title = allCards[index].querySelector('h3') ? allCards[index].querySelector('h3').textContent.trim() : null;
                            if (window.lastActiveRobotProp !== title) {
                                window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title } }));
                                window.lastActiveRobotProp = title;
                            }
                        } else {
                            if (window.lastActiveRobotProp !== null) {
                                window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title: null } }));
                                window.lastActiveRobotProp = null;
                            }
                        }
                    });

                    // Make dots clickable
                    dots.forEach((dot, index) => {
                        dot.addEventListener('click', () => {
                            const cardElement = mobileSliderWrapper.querySelector('.feature-card, .feature-card-slide');
                            if (!cardElement) return;

                            const cardWidth = cardElement.offsetWidth;
                            const gap = 20;
                            mobileSliderWrapper.scrollTo({ left: index * (cardWidth + gap), behavior: 'smooth' });
                        });
                    });

                    // Make arrow clickable
                    if (swipeArrow) {
                        swipeArrow.addEventListener('click', () => {
                            const cardElement = mobileSliderWrapper.querySelector('.feature-card, .feature-card-slide');
                            if (!cardElement) return;

                            const cardWidth = cardElement.offsetWidth;
                            const gap = 20;
                            mobileSliderWrapper.scrollTo({ left: 1 * (cardWidth + gap), behavior: 'smooth' });
                        });
                    }
                }

                // --- Manual Touch Swipe Handler ---
                // Native scroll is blocked by GSAP/Lenis intercepting touch events.
                // This manually drives horizontal scrolling via touchstart/move/end.
                if (window.innerWidth <= 992) {
                    let touchStartX = 0;
                    let touchStartY = 0;
                    let touchStartScrollLeft = 0;
                    let touchStartTime = 0;
                    let lastMoveX = 0;
                    let lastMoveTime = 0;
                    let isHorizontalSwipe = null; // null = undecided, true/false once determined
                    let isSwiping = false;

                    // Disable CSS snap during manual drag to prevent fighting
                    function disableSnap() {
                        mobileSliderWrapper.style.scrollSnapType = 'none';
                        mobileSliderWrapper.style.scrollBehavior = 'auto';
                    }

                    function enableSnapAndSlide(targetLeft) {
                        mobileSliderWrapper.style.scrollBehavior = 'smooth';
                        mobileSliderWrapper.scrollTo({ left: targetLeft, behavior: 'smooth' });
                        // Re-enable snap after the smooth scroll finishes
                        setTimeout(() => {
                            mobileSliderWrapper.style.scrollSnapType = 'x mandatory';
                        }, 400);
                    }

                    mobileSliderWrapper.addEventListener('touchstart', (e) => {
                        touchStartX = e.touches[0].clientX;
                        touchStartY = e.touches[0].clientY;
                        touchStartScrollLeft = mobileSliderWrapper.scrollLeft;
                        touchStartTime = Date.now();
                        lastMoveX = touchStartX;
                        lastMoveTime = touchStartTime;
                        isHorizontalSwipe = null;
                        isSwiping = false;
                    }, { passive: true });

                    mobileSliderWrapper.addEventListener('touchmove', (e) => {
                        const currentX = e.touches[0].clientX;
                        const currentY = e.touches[0].clientY;
                        const dx = currentX - touchStartX;
                        const dy = currentY - touchStartY;

                        // Determine swipe direction with a low threshold (4px)
                        if (isHorizontalSwipe === null && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) {
                            isHorizontalSwipe = Math.abs(dx) >= Math.abs(dy);
                            if (isHorizontalSwipe) {
                                disableSnap();
                            }
                        }

                        if (isHorizontalSwipe) {
                            // Prevent vertical scroll (GSAP scrub) from firing
                            e.preventDefault();
                            e.stopPropagation();
                            isSwiping = true;
                            // Track velocity
                            lastMoveX = currentX;
                            lastMoveTime = Date.now();
                            // 1:1 finger tracking
                            mobileSliderWrapper.scrollLeft = touchStartScrollLeft - dx;
                        }
                    }, { passive: false }); // passive: false required for preventDefault

                    mobileSliderWrapper.addEventListener('touchend', (e) => {
                        if (isSwiping) {
                            const cardElement = mobileSliderWrapper.querySelector('.feature-card, .feature-card-slide');
                            if (cardElement) {
                                const cardWidth = cardElement.offsetWidth;
                                const gap = parseFloat(getComputedStyle(mobileSliderWrapper).gap) || 20;
                                const snapUnit = cardWidth + gap;

                                // Calculate velocity (px/ms)
                                const endTime = Date.now();
                                const dt = endTime - lastMoveTime || 1;
                                const totalDx = e.changedTouches[0].clientX - touchStartX;
                                const velocity = Math.abs(totalDx) / (endTime - touchStartTime || 1);

                                // Determine current card index before swipe
                                const startIndex = Math.round(touchStartScrollLeft / snapUnit);
                                let targetIndex;

                                if (velocity > 0.3 || Math.abs(totalDx) > cardWidth * 0.25) {
                                    // Fast flick or dragged more than 25% of card width → advance
                                    targetIndex = totalDx < 0 ? startIndex + 1 : startIndex - 1;
                                } else {
                                    // Small/slow drag → snap back to current
                                    targetIndex = startIndex;
                                }

                                // Clamp within bounds
                                const allSlides = mobileSliderWrapper.querySelectorAll('.feature-card-slide, .feature-card');
                                const maxIndex = allSlides.length - 1;
                                targetIndex = Math.max(0, Math.min(targetIndex, maxIndex));

                                enableSnapAndSlide(targetIndex * snapUnit);
                            }
                        }
                        isHorizontalSwipe = null;
                        isSwiping = false;
                    }, { passive: true });
                }

                // --- Reset Slider When Scrolled Out of View ---
                // This ensures the robot drops the prop and the text is visible if the user scrolls deeply away
                const resetObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) {
                            // Instantly snap slider back to the start (Slide 0 / Center Title)
                            mobileSliderWrapper.scrollTo({ left: 0, behavior: 'instant' });

                            // Forcibly clear the robot's prop immediately
                            if (window.lastActiveRobotProp !== null) {
                                window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title: null } }));
                                window.lastActiveRobotProp = null;
                            }
                        }
                    });
                }, { threshold: 0 });
                resetObserver.observe(mobileSliderWrapper);
            }
        }

        // Hover effects disabled as requested by user. The center robot and title will remain permanently visible.
    }

    // =========================================
    // GSAP ANIMATIONS FOR "WHO WE ARE" PREMIUM SECTION
    // =========================================
    const aboutSection = document.getElementById('aboutUsSection');
    if (aboutSection && typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined") {
        const aboutTl = gsap.timeline({
            scrollTrigger: {
                trigger: aboutSection,
                start: "top 70%",
                once: true
            }
        });

        // Background Elements Reveal
        aboutTl.to(aboutSection.querySelectorAll('.bg-sweep'), {
            opacity: 0.15,
            duration: 2,
            stagger: 0.2,
            ease: "power2.out"
        }, 0);

        // Text Content Reveal
        aboutTl.to(aboutSection.querySelectorAll('.reveal-up'), {
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: "back.out(1.2)"
        }, 0.2);

        // Image Gallery Reveal (Target the exact classes and properties)
        aboutTl.to(aboutSection.querySelector('.img-hero'), {
            scale: 1, // Remove scaling
            opacity: 1,
            duration: 1,
            ease: "power3.out"
        }, 0.5);

        aboutTl.to(aboutSection.querySelector('.img-overlap'), {
            x: 0,
            opacity: 1,
            duration: 0.8,
            ease: "power2.out"
        }, 0.7);
    }
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

    let _th_hero = 0;
    function updateParallax() {
        ticking = false;
        if (!heroSection) return;
        if (window.innerWidth < 768) { _th_hero++; if (_th_hero % 3 !== 0) return; }

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

    let _sk2 = false;
    function animate(time) {
        if (window.innerWidth < 768) { _sk2 = !_sk2; if (_sk2) { requestAnimationFrame(animate); return; } }
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
    if (typeof Lenis === 'undefined') return;

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

    if (typeof ScrollTrigger !== 'undefined') {
        lenis.on('scroll', () => ScrollTrigger.update());
    }

    // Smooth scroll for anchor links via Lenis
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = this.getAttribute('href');
            if (!target || target === '#') return;

            const targetElement = document.querySelector(target);
            if (!targetElement) return;

            e.preventDefault();

            const nav = document.querySelector('.premium-nav');
            const navOffset = nav ? nav.offsetHeight + 12 : 0;
            lenis.scrollTo(targetElement, { offset: -navOffset });
        });
    });

    // Sync Lenis with GSAP so pinned sections and custom scroll stay on the same frame.
    if (typeof gsap !== 'undefined') {
        gsap.ticker.add((time) => {
            lenis.raf(time * 1000);
        });
        gsap.ticker.lagSmoothing(0);
        return;
    }

    // Fallback when GSAP is unavailable.
    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }

    requestAnimationFrame(raf);
});

/* =========================================
   PROJECTS SECTION — True Infinite Connected Timeline (pj-)
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {

    // ── 1. Icon map ──
    const pjIconMap = {
        1: "fa-heartbeat",   2: "fa-music",
        3: "fa-theater-masks", 4: "fa-calendar-alt",
        5: "fa-users",       6: "fa-mobile-alt",
        7: "fa-globe",       8: "fa-chalkboard-teacher",
        9: "fa-hands-helping", 10: "fa-glass-cheers"
    };

    const track    = document.getElementById('pj-track');
    const viewport = document.getElementById('pj-viewport');
    const prevBtn  = document.getElementById('pj-prev');
    const nextBtn  = document.getElementById('pj-next');
    const section  = document.getElementById('our-works');

    if (!track || !viewport || !section) return;

    const projectEntries = Object.entries(projects);
    const N = projectEntries.length;

    // ── 2. Build ONE pj-item per project ──
    function buildItem(id, proj, altIndex) {
        const year      = proj.date ? proj.date.split(' ').pop() : '—';
        const iconClass = pjIconMap[id] || 'fa-laptop-code';
        const isTop     = altIndex % 2 === 0;

        const item = document.createElement('div');
        item.className = `pj-item ${isTop ? 'pj-item--top' : 'pj-item--bottom'}`;
        item.setAttribute('data-orig-idx', altIndex);

        // Year node on the timeline
        const node = document.createElement('div');
        node.className = 'pj-node';
        
        // Year text label
        const yearLabel = document.createElement('div');
        yearLabel.className = 'pj-card-year';
        yearLabel.textContent = year;

        // Vertical stem
        const stem = document.createElement('div');
        stem.className = 'pj-stem';

        // Project card
        const card = document.createElement('article');
        card.className = 'pj-card';
        card.setAttribute('data-id', id);
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', `View project: ${proj.title}`);
        card.innerHTML = `
            <div class="pj-card-img">
                <img src="${proj.img}" alt="${proj.title}" loading="lazy"
                     onerror="this.src='images/campuscon-image.png'">
                <div class="pj-card-badge"><i class="fas ${iconClass}"></i></div>
            </div>
            <div class="pj-card-body">
                <h3 class="pj-card-title">${proj.title}</h3>
                <p class="pj-card-desc">${proj.desc}</p>
                <span class="pj-card-client"><i class="fas fa-user" style="font-size:0.65rem;margin-right:4px;color:#38bdf8;"></i>${proj.client}</span>
            </div>`;

        // Mouse spotlight effect
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            card.style.setProperty('--pj-mx', `${e.clientX - r.left}px`);
            card.style.setProperty('--pj-my', `${e.clientY - r.top}px`);
        });

        // Click → modal
        const openModal = () => pjOpenModal(id, proj, iconClass);
        card.addEventListener('click', openModal);
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(); }
        });

        item.appendChild(node);
        item.appendChild(yearLabel);
        item.appendChild(stem);
        item.appendChild(card);
        return item;
    }

    // ── 3. Populate track: original + two clones (3× items for seamless loop) ──
    // We render 3 sets so we can loop: set A (clone-left), set B (original), set C (clone-right)
    function populateTrack() {
        track.innerHTML = '';
        [0, 1, 2].forEach(setIdx => {
            projectEntries.forEach(([id, proj], i) => {
                const item = buildItem(id, proj, i);
                item.setAttribute('data-set', setIdx);
                track.appendChild(item);
            });
        });
    }
    populateTrack();

    // ── 4. Measure and position so we start at the MIDDLE set ──
    let ITEM_W  = 0; // computed after layout
    let ITEM_GAP = 0;
    let SET_W   = 0; // width of one full set of N items
    let offsetX = 0; // current horizontal translation (negative = scroll right)
    let isDragging = false;
    let dragStartX = 0;
    let dragStartOffset = 0;
    let isUserInteracting = false;
    let userInteractionTimer = null;

    function measure() {
        const items = track.querySelectorAll('.pj-item');
        if (!items.length) return;
        const first = items[0];
        const second = items[1];
        if (!second) return;
        
        // Use offsetLeft to get scale-invariant distance between items
        const distance = second.offsetLeft - first.offsetLeft;
        ITEM_W   = first.offsetWidth;
        ITEM_GAP = distance - ITEM_W;
        SET_W = N * distance;
        
        // Start positioned at the middle set (set index 1)
        offsetX = -(SET_W + viewport.clientWidth / 2 - ITEM_W / 2);
    }

    // Center viewport on the middle project of the middle set
    function centerOnMiddle() {
        measure();
        // offsetX so that the center item of set 1 is in the center of the viewport
        const midItemIndex = N * 1 + Math.floor(N / 2); // middle set, middle item
        const vpCenter = viewport.clientWidth / 2;
        offsetX = -(midItemIndex * (ITEM_W + ITEM_GAP) - vpCenter + ITEM_W / 2);
        applyTransform(false);
        updateCenter();
    }

    function applyTransform(animated) {
        track.style.transition = animated ? 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)' : 'none';
        track.style.transform  = `translateX(${offsetX}px)`;
    }

    // ── 5. Infinite loop seam check — jump silently when near edges ──
    function checkSeam() {
        // If we've scrolled too far right (toward set 0), jump forward by SET_W
        if (offsetX > -(SET_W * 0.5)) {
            offsetX -= SET_W;
            applyTransform(false);
        }
        // If we've scrolled too far left (toward set 2), jump back by SET_W
        if (offsetX < -(SET_W * 2.5)) {
            offsetX += SET_W;
            applyTransform(false);
        }
    }

    // ── 6. Center detection — which item is closest to viewport center ──
    function updateCenter() {
        const allItems = Array.from(track.querySelectorAll('.pj-item'));
        const vpCenter = viewport.getBoundingClientRect().left + viewport.clientWidth / 2;
        let bestItem   = null;
        let bestDist   = Infinity;

        allItems.forEach(item => {
            const r    = item.getBoundingClientRect();
            const itemCenter = r.left + r.width / 2;
            const dist = Math.abs(itemCenter - vpCenter);
            if (dist < bestDist) { bestDist = dist; bestItem = item; }
        });

        allItems.forEach(item => {
            item.classList.remove('is-center');
            const card = item.querySelector('.pj-card');
            if (card) card.classList.remove('is-dim');
        });

        if (bestItem) {
            bestItem.classList.add('is-center');
            // Dim cards that are far from center
            allItems.forEach(item => {
                if (item !== bestItem) {
                    const r = item.getBoundingClientRect();
                    const itemCenter = r.left + r.width / 2;
                    const dist = Math.abs(itemCenter - vpCenter);
                    const card = item.querySelector('.pj-card');
                    if (card && dist > ITEM_W * 1.5) card.classList.add('is-dim');
                }
            });
        }
    }

    // ── 7. Auto-scroll RAF loop ──
    const AUTO_SPEED = 0.4; // px per frame (smooth slow crawl)
    let rafId = null;
    let lastTime = 0;

    function autoScrollLoop(time) {
        if (!isUserInteracting) {
            const delta = time - lastTime;
            // Cap delta to avoid huge jumps on tab-resume
            const step  = Math.min(delta, 50) * AUTO_SPEED * 0.06;
            offsetX -= step;
            applyTransform(false);
            checkSeam();
            updateCenter();
        }
        lastTime = time;
        rafId = requestAnimationFrame(autoScrollLoop);
    }

    // ── 8. Manual navigation (prev/next buttons snap to previous/next original item) ──
    function snapToNearest(direction) {
        isUserInteracting = true;
        clearTimeout(userInteractionTimer);

        // Find item currently closest to center
        const allItems = Array.from(track.querySelectorAll('.pj-item'));
        const vpCenter = viewport.getBoundingClientRect().left + viewport.clientWidth / 2;
        let bestItem   = null;
        let bestDist   = Infinity;

        allItems.forEach(item => {
            const r    = item.getBoundingClientRect();
            const ic   = r.left + r.width / 2;
            const dist = Math.abs(ic - vpCenter);
            if (dist < bestDist) { bestDist = dist; bestItem = item; }
        });

        if (!bestItem) { isUserInteracting = false; return; }

        const siblings = allItems;
        const cursorIndex = siblings.indexOf(bestItem);
        const targetIndex = Math.max(0, Math.min(siblings.length - 1, cursorIndex + direction));
        const targetItem  = siblings[targetIndex];

        if (targetItem) {
            const r = targetItem.getBoundingClientRect();
            const ic = r.left + r.width / 2;
            const delta = ic - vpCenter;
            offsetX -= delta;
            applyTransform(true);
            checkSeam();
            updateCenter();
        }

        userInteractionTimer = setTimeout(() => { isUserInteracting = false; }, 2500);
    }

    if (prevBtn) prevBtn.addEventListener('click', () => snapToNearest(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => snapToNearest(1));

    // Keyboard nav
    section.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft')  snapToNearest(-1);
        if (e.key === 'ArrowRight') snapToNearest(1);
    });

    // ── 9. Drag / touch interaction ──
    function onDragStart(clientX) {
        isDragging = true;
        isUserInteracting = true;
        clearTimeout(userInteractionTimer);
        dragStartX = clientX;
        dragStartOffset = offsetX;
        track.style.cursor = 'grabbing';
        cancelAnimationFrame(rafId);
    }

    function onDragMove(clientX) {
        if (!isDragging) return;
        const dx = clientX - dragStartX;
        offsetX = dragStartOffset + dx;
        applyTransform(false);
        checkSeam();
        updateCenter();
    }

    function onDragEnd() {
        if (!isDragging) return;
        isDragging = false;
        track.style.cursor = 'grab';
        // Restart auto scroll after delay
        userInteractionTimer = setTimeout(() => {
            isUserInteracting = false;
            lastTime = performance.now();
            rafId = requestAnimationFrame(autoScrollLoop);
        }, 2500);
    }

    // Mouse drag
    viewport.addEventListener('mousedown', (e) => { e.preventDefault(); onDragStart(e.clientX); });
    window.addEventListener('mousemove', (e) => { if (isDragging) onDragMove(e.clientX); });
    window.addEventListener('mouseup', onDragEnd);

    // Touch drag
    viewport.addEventListener('touchstart', (e) => { onDragStart(e.touches[0].clientX); }, { passive: true });
    viewport.addEventListener('touchmove', (e) => { onDragMove(e.touches[0].clientX); }, { passive: true });
    viewport.addEventListener('touchend', onDragEnd, { passive: true });

    // ── 10. Pause auto-scroll on section hover ──
    section.addEventListener('mouseenter', () => {
        isUserInteracting = true;
        clearTimeout(userInteractionTimer);
    });
    section.addEventListener('mouseleave', () => {
        if (!isDragging) {
            userInteractionTimer = setTimeout(() => {
                isUserInteracting = false;
            }, 500);
        }
    });

    // (Orb logic removed for cleaner timeline look)

    // ── 13. GSAP header reveal ──
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
        gsap.fromTo('.pj-header',
            { y: 40, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.9, ease: 'power3.out',
              scrollTrigger: { trigger: '#our-works', start: 'top 75%', once: true } }
        );
        gsap.fromTo('.pj-timeline-unified',
            { opacity: 0 },
            { opacity: 1, duration: 1, ease: 'power2.out',
              scrollTrigger: { trigger: '#our-works', start: 'top 70%', once: true } }
        );
    }

    // ── 14. Initialize: measure → position → start RAF ──
    // Wait for fonts/images to settle then boot
    function boot() {
        centerOnMiddle();
        track.style.cursor = 'grab';
        lastTime = performance.now();
        rafId = requestAnimationFrame(autoScrollLoop);
    }

    // Defer until section is in/near viewport (IntersectionObserver for performance)
    const bootObserver = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            bootObserver.disconnect();
            requestAnimationFrame(() => requestAnimationFrame(boot));
        }
    }, { rootMargin: '200px' });
    bootObserver.observe(section);

    window.addEventListener('resize', () => {
        cancelAnimationFrame(rafId);
        setTimeout(() => {
            populateTrack();
            centerOnMiddle();
            lastTime = performance.now();
            rafId = requestAnimationFrame(autoScrollLoop);
        }, 150);
    });
});

// ── Modal open/close ──
function pjOpenModal(id, proj, iconClass) {
    const modal    = document.getElementById('pj-modal');
    if (!modal) return;

    document.getElementById('pj-modal-icon').innerHTML  = `<i class="fas ${iconClass || 'fa-laptop-code'}"></i>`;
    document.getElementById('pj-modal-title').textContent  = proj.title  || '';
    document.getElementById('pj-modal-desc').textContent   = proj.desc   || '';
    document.getElementById('pj-modal-client').textContent = proj.client || '';
    document.getElementById('pj-modal-year').textContent   = proj.date   || '';

    // Tech badges
    const techWrap = document.getElementById('pj-modal-tech');
    techWrap.innerHTML = '';
    (proj.tech || []).forEach(t => {
        const b = document.createElement('span');
        b.className   = 'pj-tech-badge';
        b.textContent = t;
        techWrap.appendChild(b);
    });

    // Link
    const linkEl = document.getElementById('pj-modal-link');
    if (proj.link && proj.link !== '#') {
        linkEl.href  = proj.link;
        linkEl.style.display = 'inline-flex';
    } else {
        linkEl.style.display = 'none';
    }

    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

document.addEventListener('DOMContentLoaded', () => {
    const modal     = document.getElementById('pj-modal');
    const closeBtn  = document.getElementById('pj-modal-close');
    const backdrop  = document.getElementById('pj-modal-backdrop');

    function pjCloseModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    if (closeBtn)  closeBtn.addEventListener('click',  pjCloseModal);
    if (backdrop)  backdrop.addEventListener('click',  pjCloseModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) pjCloseModal();
    });
});



// =========================================
// OUR WORKS SECTION - PARALLAX SCROLL EFFECT
// =========================================
(function () {
    const ourWorks = document.getElementById('our-works');
    const whatWeDo = document.getElementById('what-we-do');
    if (!ourWorks || !whatWeDo) return;

    // Apply initial CSS to create clip-path / translateY reveal
    Object.assign(ourWorks.style, {
        position: 'relative',
        willChange: 'transform',
        transition: 'none'
    });

    let _th_ow = 0;
    function onParallaxScroll() {
        if (window.innerWidth < 768) { _th_ow++; if (_th_ow % 3 !== 0) return; }
        const wwdRect = whatWeDo.getBoundingClientRect();
        const owRect = ourWorks.getBoundingClientRect();

        // How far the previous section has scrolled past the viewport top
        const scrolled = -wwdRect.top;
        const sectionHeight = whatWeDo.offsetHeight;

        // Clamp progress 0 → 1 while user scrolls through `what-we-do`
        const progress = Math.max(0, Math.min(1, scrolled / sectionHeight));

        // Parallax: section starts 80px below its natural position and rises to 0
        const translateY = (1 - progress) * 80;

        // Opacity: fade in from 0.4 → 1
        const opacity = 0.4 + progress * 0.6;

        // Scale: subtle zoom from 0.97 → 1
        const scale = 0.97 + progress * 0.03;

        ourWorks.style.transform = `translateY(${translateY}px) scale(${scale})`;
        ourWorks.style.opacity = opacity;
    }

    window.addEventListener('scroll', onParallaxScroll, { passive: true });
    onParallaxScroll(); // Run once on load
})();
// =========================================
// OUR WORKS - HEADING SCROLL PARALLAX (kept)
// =========================================
(function () {
    function initHeadingParallax() {
        const heading = document.querySelector('#our-works .section-header');
        if (!heading) return;

        let ticking = false;

        let _th_head = 0;
        function applyHeadingParallax() {
            if (!ticking) {
                requestAnimationFrame(() => {
                    ticking = false;
                    if (window.innerWidth < 768) { _th_head++; if (_th_head % 3 !== 0) return; }
                    const rect = heading.getBoundingClientRect();
                    const viewportMid = window.innerHeight / 2;
                    const fromCenter = rect.top + rect.height / 2 - viewportMid;
                    const translateY = fromCenter * 0.08;
                    heading.style.transform = `translateY(${translateY}px)`;
                    heading.style.willChange = 'transform';
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', applyHeadingParallax, { passive: true });
        applyHeadingParallax();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeadingParallax);
    } else {
        initHeadingParallax();
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    // =========================================
    // STICKY SCROLL NAVBAR LOGO ANIMATION & SCROLL SPY
    // =========================================
    const nav = document.querySelector('.premium-nav');
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('section');

    if (nav) {
        window.addEventListener('scroll', () => {
            // Navbar Background toggle
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Scroll Spy Logic
            let currentSectionId = '';

            sections.forEach(section => {
                const rect = section.getBoundingClientRect();

                // Trigger if the top of the section has reached the upper half of the viewport
                if (rect.top <= window.innerHeight / 2) {
                    currentSectionId = section.getAttribute('id');
                }
            });

            // Update Active Link
            let matchingLink = null;
            navLinks.forEach(link => {
                if (link.getAttribute('href') === `#${currentSectionId}`) {
                    matchingLink = link;
                }
            });

            // Only update the active class if we found a matching link for this section
            // This prevents the active link from losing its white text color when scrolling past sections without nav items (like About Us)
            if (matchingLink) {
                navLinks.forEach(link => link.classList.remove('active'));
                matchingLink.classList.add('active');
            }
        });

        // Trigger once on load in case user refreshed while down the page
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        }
    }
});

// =========================================
// ABOUT / DISCOVER RLABZ - SCROLL ANIMATIONS (GSAP + ScrollTrigger)
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);

    const aboutSection = document.getElementById('aboutUsSection');
    if (!aboutSection) return;

    const stDefaults = { trigger: aboutSection, start: 'top 80%', once: true };

    // 1. Main Image — Scale-in + Fade
    const imgHero = aboutSection.querySelector('.img-hero');
    if (imgHero) {
        gsap.fromTo(imgHero,
            { opacity: 0, scale: 0.85, visibility: 'hidden' },
            {
                opacity: 1, scale: 1, visibility: 'visible', duration: 1, ease: 'power3.out',
                scrollTrigger: stDefaults
            }
        );
    }

    // 2. Overlap Image — Slide from Right
    const imgOverlap = aboutSection.querySelector('.img-overlap');
    if (imgOverlap) {
        gsap.fromTo(imgOverlap,
            { opacity: 0, x: 80, visibility: 'hidden' },
            {
                opacity: 1, x: 0, visibility: 'visible', duration: 0.9, ease: 'power3.out', delay: 0.3,
                scrollTrigger: stDefaults
            }
        );
    }

    // 3. Innovation Hub Card — Elastic Bounce-in
    const statCard = aboutSection.querySelector('.floating-stat-card');
    if (statCard) {
        gsap.fromTo(statCard,
            { opacity: 0, y: 40, scale: 0.6, visibility: 'hidden' },
            {
                opacity: 1, y: 0, scale: 1, visibility: 'visible', duration: 1, ease: 'elastic.out(1, 0.5)', delay: 0.6,
                scrollTrigger: stDefaults
            }
        );
    }

    // 4. Badge — Slide Down + Fade
    const badge = aboutSection.querySelector('.about-badge');
    if (badge) {
        gsap.fromTo(badge,
            { opacity: 0, y: -20, visibility: 'hidden' },
            {
                opacity: 1, y: 0, visibility: 'visible', duration: 0.6, ease: 'power2.out', delay: 0.15,
                scrollTrigger: stDefaults
            }
        );
    }

    // 5. Heading — Fade-up the whole container
    const heading = aboutSection.querySelector('.about-heading');
    if (heading) {
        gsap.fromTo(heading,
            { opacity: 0, y: 30, visibility: 'hidden' },
            {
                opacity: 1, y: 0, visibility: 'visible', duration: 0.7, ease: 'power3.out', delay: 0.3,
                scrollTrigger: stDefaults
            }
        );
    }

    // 6. Paragraph — Fade-up
    const lead = aboutSection.querySelector('.about-lead');
    if (lead) {
        gsap.fromTo(lead,
            { opacity: 0, y: 25, visibility: 'hidden' },
            {
                opacity: 1, y: 0, visibility: 'visible', duration: 0.7, ease: 'power2.out', delay: 0.5,
                scrollTrigger: stDefaults
            }
        );
    }

    // 7. Blockquote — Border Draw + Text Fade
    const quote = aboutSection.querySelector('.about-quote');
    if (quote) {
        quote.classList.add('anim-ready');

        const qTl = gsap.timeline({
            scrollTrigger: stDefaults,
            delay: 0.7
        });

        qTl.set(quote, { visibility: 'visible' });
        // Draw the pseudo-element border via scaleY
        qTl.fromTo(quote, { '--quote-border-scale': '0' }, {
            '--quote-border-scale': '1', duration: 0.5, ease: 'power2.inOut'
        });
        // Fade in quote text overlapping border draw
        qTl.fromTo(quote, { opacity: 0 }, { opacity: 1, duration: 0.5, ease: 'power2.out' }, '-=0.3');
    }

    // 8. Incubation Card — Slide Up
    const incubationCard = aboutSection.querySelector('.incubation-card');
    if (incubationCard) {
        gsap.fromTo(incubationCard,
            { opacity: 0, y: 40, visibility: 'hidden' },
            {
                opacity: 1, y: 0, visibility: 'visible', duration: 0.8, ease: 'power3.out', delay: 0.9,
                scrollTrigger: stDefaults
            }
        );
    }
});

// --- Elfsight Horizontal Carousel Timeline ---
document.addEventListener('DOMContentLoaded', () => {
    const sliderViewport = document.getElementById('es-slider-viewport');
    const sliderTrack = document.getElementById('es-slider-track');
    const cards = document.querySelectorAll('.es-card');
    const dots = document.querySelectorAll('.es-dot-item');
    const prevBtn = document.getElementById('es-nav-prev');
    const nextBtn = document.getElementById('es-nav-next');
    
    if (!sliderViewport || !sliderTrack || cards.length === 0) return;

    let currentIndex = 0;
    const totalCards = cards.length;
    let isDragging = false;
    let startX = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID;
    let autoPlayInterval;

    function getCardWidth() {
        return cards[0].getBoundingClientRect().width;
    }

    function updateTimeline() {
        const cardWidth = getCardWidth();
        currentTranslate = currentIndex * -cardWidth;
        prevTranslate = currentTranslate;
        
        sliderTrack.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
        sliderTrack.style.transform = `translateX(${currentTranslate}px)`;
        
        // Update dots
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    function nextSlide() {
        if (currentIndex < totalCards - 1) {
            currentIndex++;
        } else {
            currentIndex = 0; // Loop back
        }
        updateTimeline();
    }

    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
        } else {
            currentIndex = totalCards - 1; // Loop to end
        }
        updateTimeline();
    }

    // Auto Play
    function startAutoPlay() {
        autoPlayInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    // Event Listeners for Buttons
    if (nextBtn) nextBtn.addEventListener('click', () => {
        nextSlide();
        stopAutoPlay();
        startAutoPlay(); // Reset timer
    });
    
    if (prevBtn) prevBtn.addEventListener('click', () => {
        prevSlide();
        stopAutoPlay();
        startAutoPlay(); // Reset timer
    });

    // Event Listeners for Dots
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.getAttribute('data-index'));
            updateTimeline();
            stopAutoPlay();
            startAutoPlay();
        });
    });

    // Drag / Swipe functionality
    function touchStart(event) {
        isDragging = true;
        startX = getPositionX(event);
        animationID = requestAnimationFrame(animation);
        sliderTrack.style.transition = 'none'; // Disable transition while dragging
        stopAutoPlay();
    }

    function touchEnd() {
        if (!isDragging) return;
        isDragging = false;
        cancelAnimationFrame(animationID);
        
        const cardWidth = getCardWidth();
        const movedBy = currentTranslate - prevTranslate;
        
        // Snap to next/prev if moved enough
        if (movedBy < -100 && currentIndex < totalCards - 1) currentIndex += 1;
        if (movedBy > 100 && currentIndex > 0) currentIndex -= 1;
        
        updateTimeline();
        startAutoPlay();
    }

    function touchMove(event) {
        if (isDragging) {
            const currentPosition = getPositionX(event);
            currentTranslate = prevTranslate + currentPosition - startX;
        }
    }

    function getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }

    function animation() {
        sliderTrack.style.transform = `translateX(${currentTranslate}px)`;
        if (isDragging) requestAnimationFrame(animation);
    }

    // Touch events
    sliderViewport.addEventListener('touchstart', touchStart);
    sliderViewport.addEventListener('touchend', touchEnd);
    sliderViewport.addEventListener('touchmove', touchMove);

    // Mouse events
    sliderViewport.addEventListener('mousedown', touchStart);
    sliderViewport.addEventListener('mouseup', touchEnd);
    sliderViewport.addEventListener('mouseleave', touchEnd);
    sliderViewport.addEventListener('mousemove', touchMove);

    // Handle Window Resize
    window.addEventListener('resize', () => {
        updateTimeline();
    });

    // Initialize
    updateTimeline();
    startAutoPlay();
});

document.addEventListener('DOMContentLoaded', () => {
    // --- Elfsight Modal Logic ---
    const modal = document.getElementById('es-modal');
    const modalBackdrop = document.getElementById('es-modal-backdrop');
    const modalClose = document.getElementById('es-modal-close');
    const modalTitle = document.getElementById('es-modal-title');
    const modalDesc = document.getElementById('es-modal-desc');
    const modalYear = document.getElementById('es-modal-year');
    const modalTech = document.getElementById('es-modal-tech');
    const modalIcon = document.getElementById('es-modal-icon');
    
    const projectData = {
        'euphoria': {
            title: 'Launch of Euphoria',
            year: '2019',
            desc: 'Our flagship techfest that brought together thousands of innovators and creators for a 3-day immersive experience. We set a new standard for college festivals with cutting edge technology integration.',
            icon: '<i class="fa-solid fa-rocket"></i>',
            tech: ['Event Management', 'TechFest', 'Innovation']
        },
        'campuscon': {
            title: 'CampusCon Initiative',
            year: '2020',
            desc: 'A major step towards integrating advanced campus networking solutions and fostering digital education. This initiative paved the way for seamless communication across departments.',
            icon: '<i class="fa-solid fa-network-wired"></i>',
            tech: ['Networking', 'Education', 'Infrastructure']
        },
        'ctrm': {
            title: 'CTRM Deployment',
            year: '2021',
            desc: 'Implementing the comprehensive CTRM platform to streamline administrative processes and boost productivity. This unified system replaced dozens of legacy tools.',
            icon: '<i class="fa-solid fa-server"></i>',
            tech: ['Enterprise Software', 'Management', 'System Integration']
        },
        'fesbud': {
            title: 'Fesbud Platform',
            year: '2023',
            desc: 'A budget management and financial tracking system designed specifically for our complex ecosystem. It allows real-time tracking of expenses and resource allocation.',
            icon: '<i class="fa-solid fa-wallet"></i>',
            tech: ['FinTech', 'Budgeting', 'Analytics']
        },
        'arkon': {
            title: 'Arkon Expansion',
            year: '2025',
            desc: 'Our latest expansion into cutting-edge AI-driven solutions and infrastructure modernization. Arkon provides a scalable foundation for future AI projects.',
            icon: '<i class="fa-solid fa-microchip"></i>',
            tech: ['AI', 'Cloud Native', 'Modernization']
        }
    };

    const learnMoreBtns = document.querySelectorAll('.es-learn-more');
    
    function openModal(projectId) {
        if (!modal || !projectData[projectId]) return;
        
        const data = projectData[projectId];
        
        modalTitle.textContent = data.title;
        modalDesc.textContent = data.desc;
        modalYear.textContent = data.year;
        modalIcon.innerHTML = data.icon;
        
        // Populate tech badges
        modalTech.innerHTML = '';
        if (data.tech && data.tech.length) {
            data.tech.forEach(t => {
                const badge = document.createElement('span');
                badge.className = 'es-tech-badge';
                badge.textContent = t;
                modalTech.appendChild(badge);
            });
        }
        
        modal.classList.add('is-open');
    }

    function closeModal() {
        if (modal) modal.classList.remove('is-open');
    }

    learnMoreBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const projectId = btn.getAttribute('data-project');
            openModal(projectId);
        });
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
    
    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
});
