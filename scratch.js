// Let's test the syntax for horizontal scroll trigger
nodes.forEach((node, index) => {
    gsap.from(node, {
        opacity: 0,
        scale: 0.85,
        duration: 0.6,
        delay: (index % 3) * 0.05,
        ease: 'back.out(1.7)',
        scrollTrigger: {
            trigger: node,
            scroller: pinContainer,
            horizontal: true,
            start: 'left 85%',
            toggleActions: 'play none none none'
        }
    });
});
