document.addEventListener('DOMContentLoaded', () => {
    // --- 3D Card Tilt Effect ---
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
        colorBase: 'rgba(100, 149, 237, ', // Cornflower Blue base
        colorHighlight: 'rgba(138, 43, 226, ', // Blue Violet
        colorAccent: 'rgba(0, 255, 255, ', // Cyan accent
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
            this.vx = (Math.random() - 0.5) * 0.5; // Slowed down
            this.vy = (Math.random() - 0.5) * 0.5; // Slowed down
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
                this.vx -= (dx / distMouse) * 0.02; // Gentler push
                this.vy -= (dy / distMouse) * 0.02; // Gentler push
                this.excitement = Math.min(this.excitement + 0.02, 1);
            } else {
                this.excitement = Math.max(this.excitement - 0.01, 0); // Slower decay
            }

            // Subtle card attraction
            if (activeCardIndex !== -1 && cardRects[activeCardIndex]) {
                const rect = cardRects[activeCardIndex];
                const cdx = rect.cx - this.x;
                const cdy = rect.cy - this.y;
                const distCard = Math.sqrt(cdx * cdx + cdy * cdy);

                if (distCard < 400) {
                    // Very gentle pull toward card area
                    this.vx += (cdx / distCard) * 0.015;
                    this.vy += (cdy / distCard) * 0.015;
                    this.excitement = Math.min(this.excitement + 0.03, 0.9);
                }
            }

            this.x += this.vx * (1 + this.excitement * 0.3);
            this.y += this.vy * (1 + this.excitement * 0.3);

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
