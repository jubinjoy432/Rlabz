document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('robot-container');
    if (!container) return;

    // --- Scene Setup ---
    const scene = new THREE.Scene();
    // Use a wider FOV for that "toy photo" look
    const camera = new THREE.PerspectiveCamera(40, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 0, 9);

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // --- Lighting (Bright Studio / Toy Product) ---
    // Boosted brightness to make white look WHITE
    scene.add(new THREE.AmbientLight(0xffffff, 0.9));

    const key = new THREE.DirectionalLight(0xffffff, 2.5); // Brighter
    key.position.set(5, 8, 5);
    key.castShadow = true;
    key.shadow.mapSize.width = 1024;
    key.shadow.mapSize.height = 1024;
    scene.add(key);

    const fill = new THREE.DirectionalLight(0xbbeeff, 0.8);
    fill.position.set(-5, 3, 5);
    scene.add(fill);

    const back = new THREE.DirectionalLight(0xffeedd, 0.5);
    back.position.set(0, 5, -5);
    scene.add(back);

    // --- Materials ---
    // User requested BLUE and WHITE (replacing Pink)
    const primaryBlue = new THREE.MeshPhysicalMaterial({
        color: 0x89CFF0, // Baby Blue / Pastel Blue
        metalness: 0.1,
        roughness: 0.2,
        clearcoat: 1.0,
        clearcoatRoughness: 0.1
    });

    const glossyWhite = new THREE.MeshPhysicalMaterial({
        color: 0xffffff,
        metalness: 0.1,
        roughness: 0.1,
        clearcoat: 1.0
    });

    const chrome = new THREE.MeshStandardMaterial({
        color: 0xaaaaaa,
        metalness: 1.0,
        roughness: 0.2
    });

    const blackPlastic = new THREE.MeshStandardMaterial({
        color: 0x111111,
        roughness: 0.4
    });

    const glassLens = new THREE.MeshPhysicalMaterial({
        color: 0xffffff,
        metalness: 0.0,
        roughness: 0.0,
        transmission: 0.95, // Glass
        thickness: 0.5,
        clearcoat: 1.0
    });

    const eyeBacking = new THREE.MeshBasicMaterial({ color: 0x000000 });

    const glowLight = new THREE.MeshBasicMaterial({ color: 0xffffee });


    // --- Geometry Helpers ---

    // Create a Rounded Rectangle Shape (Squircle)
    function createRoundedRectShape(width, height, radius) {
        const shape = new THREE.Shape();
        const x = -width / 2;
        const y = -height / 2;

        shape.moveTo(x, y + radius);
        shape.lineTo(x, y + height - radius);
        shape.quadraticCurveTo(x, y + height, x + radius, y + height);
        shape.lineTo(x + width - radius, y + height);
        shape.quadraticCurveTo(x + width, y + height, x + width, y + height - radius);
        shape.lineTo(x + width, y + radius);
        shape.quadraticCurveTo(x + width, y, x + width - radius, y);
        shape.lineTo(x + radius, y);
        shape.quadraticCurveTo(x, y, x, y + radius);

        return shape;
    }

    // Create Extruded Rounded Box (Squircle Prism)
    function createSquircleMesh(w, h, depth, radius, material) {
        const shape = createRoundedRectShape(w, h, radius);
        const geo = new THREE.ExtrudeGeometry(shape, {
            depth: depth,
            bevelEnabled: true,
            bevelSegments: 4,
            bevelSize: 0.05, // Rounding on the Z edges
            bevelThickness: 0.05
        });
        // Center the geometry
        geo.center();
        const mesh = new THREE.Mesh(geo, material);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        return mesh;
    }

    // Create Ribbed/Corrugated Tube (Accordion Joint)
    function createRibbedTube(length, radius, ribs, material) {
        const points = [];
        const segments = ribs * 4;
        for (let i = 0; i <= segments; i++) {
            const t = i / segments;
            const y = (t - 0.5) * length;
            // Sine wave radius variation
            const r = radius + Math.sin(t * Math.PI * ribs * 2) * (radius * 0.15);
            points.push(new THREE.Vector2(r, y));
        }
        const geo = new THREE.LatheGeometry(points, 32);
        const mesh = new THREE.Mesh(geo, material);
        mesh.castShadow = true;
        return mesh;
    }


    // --- BUILD ROBOT ---
    const robot = new THREE.Group();
    scene.add(robot);

    // 1. HEAD
    const headGroup = new THREE.Group();
    headGroup.position.y = 1.6;
    robot.add(headGroup);

    // Main Head "TV" Shape
    const head = createSquircleMesh(2.2, 1.6, 1.4, 0.4, primaryBlue);
    headGroup.add(head);

    // Face Plate (White Inset)
    const facePlate = createSquircleMesh(1.9, 1.3, 0.1, 0.3, glossyWhite);
    facePlate.position.z = 0.72; // Slightly front
    headGroup.add(facePlate);

    // Eyes
    function createEye(x) {
        const g = new THREE.Group();
        g.position.set(x, 0.1, 0.90);

        // 1. Chrome Rim (Thinner = cleaner)
        const rim = new THREE.Mesh(
            new THREE.TorusGeometry(0.35, 0.04, 16, 64), // Thinner tube (0.08 -> 0.04)
            chrome
        );
        rim.position.z = 0.01;
        g.add(rim);

        // 2. Eye fill
        const socket = new THREE.Mesh(
            new THREE.CircleGeometry(0.33, 32),
            new THREE.MeshBasicMaterial({ color: 0x111122 }) // Darker blue-black
        );
        socket.position.z = 0.02;
        g.add(socket);

        // 3. Iris (Vibrant Blue)
        const iris = new THREE.Mesh(
            new THREE.CircleGeometry(0.26, 32),
            new THREE.MeshBasicMaterial({ color: 0x4488ff }) // Brighter blue
        );
        iris.position.z = 0.03;
        g.add(iris);

        // 4. Pupil (BIG = Adorable Puppy Eyes)
        const pupil = new THREE.Mesh(
            new THREE.CircleGeometry(0.14, 32), // Bigger (0.08 -> 0.14)
            new THREE.MeshBasicMaterial({ color: 0x000011 })
        );
        pupil.position.z = 0.04;
        g.add(pupil);

        // 5. Highlights (Sparkles)
        const ref1 = new THREE.Mesh(
            new THREE.CircleGeometry(0.10, 32),
            new THREE.MeshBasicMaterial({ color: 0xffffff })
        );
        ref1.position.set(-0.08, 0.08, 0.05);
        g.add(ref1);

        const ref2 = new THREE.Mesh(
            new THREE.CircleGeometry(0.04, 32),
            new THREE.MeshBasicMaterial({ color: 0xffffff })
        );
        ref2.position.set(0.08, -0.06, 0.05);
        g.add(ref2);

        // Extra tiny sparkle
        const ref3 = new THREE.Mesh(
            new THREE.CircleGeometry(0.02, 32),
            new THREE.MeshBasicMaterial({ color: 0xffffff })
        );
        ref3.position.set(-0.05, -0.08, 0.05);
        g.add(ref3);

        return g;
    }

    const eyeL = createEye(-0.5);
    headGroup.add(eyeL);

    const eyeR = createEye(0.5);
    headGroup.add(eyeR);

    // Ears (Dials)
    const earGeo = new THREE.CylinderGeometry(0.3, 0.35, 0.2, 32);
    const earL = new THREE.Mesh(earGeo, primaryBlue);
    earL.rotation.z = Math.PI / 2;
    earL.position.set(-1.2, 0, 0);
    headGroup.add(earL);

    const earCapL = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, 0.25, 32), glossyWhite);
    earCapL.rotation.z = Math.PI / 2;
    earCapL.position.set(-1.22, 0, 0);
    headGroup.add(earCapL);

    const earR = earL.clone();
    earR.position.set(1.2, 0, 0);
    headGroup.add(earR);
    const earCapR = earCapL.clone();
    earCapR.position.set(1.22, 0, 0);
    headGroup.add(earCapR);

    // SMILE (Curve)
    const smileGeo = new THREE.TorusGeometry(0.25, 0.03, 16, 32, 2.5);
    const smile = new THREE.Mesh(smileGeo, new THREE.MeshBasicMaterial({ color: 0x333333 }));
    smile.rotation.z = -1.25 + Math.PI;
    smile.position.set(0, -0.25, 0.74);
    headGroup.add(smile);

    // ANTENNA (The "Cute" Factor)
    const antennaGroup = new THREE.Group();
    antennaGroup.position.y = 0.8; // Top of head
    headGroup.add(antennaGroup);

    // Stem
    const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.3, 8), chrome);
    stem.position.y = 0.15;
    antennaGroup.add(stem);

    // Bulb
    const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.08, 16, 16), new THREE.MeshBasicMaterial({ color: 0xff4444 }));
    bulb.position.y = 0.3;
    antennaGroup.add(bulb);


    // 2. TORSO
    const bodyGroup = new THREE.Group();
    robot.add(bodyGroup);

    // Torso Shape (Squircle Prism)
    const torso = createSquircleMesh(1.4, 1.4, 1.0, 0.3, primaryBlue);
    torso.position.y = 0;
    bodyGroup.add(torso);

    // Chest Screen (Black Glass)
    const chestScreen = createSquircleMesh(1.0, 0.8, 0.05, 0.15, blackPlastic);
    chestScreen.position.z = 0.51;
    chestScreen.position.y = 0.1;
    bodyGroup.add(chestScreen);

    // HEART (Glowing Pulse)
    const chestLight = new THREE.Mesh(
        new THREE.CircleGeometry(0.15, 32),
        new THREE.MeshBasicMaterial({ color: 0x00ffcc })
    );
    chestLight.position.set(0, 0.1, 0.54);
    bodyGroup.add(chestLight);

    // Neck
    const neck = createRibbedTube(0.5, 0.3, 3, chrome);
    neck.position.y = 0.9;
    robot.add(neck);


    // 3. ARMS V6 (Cute Mittens)
    function createArm(x) {
        const g = new THREE.Group();
        g.position.set(x, 0.25, 0.25);

        // 1. Shoulder (Big Ball)
        const shoulder = new THREE.Mesh(new THREE.SphereGeometry(0.32, 32, 32), primaryBlue);
        g.add(shoulder);

        // 2. Upper Arm (Thicker)
        const upperArm = new THREE.Mesh(
            new THREE.CylinderGeometry(0.2, 0.16, 0.5, 32),
            primaryBlue
        );
        upperArm.position.y = -0.35;
        g.add(upperArm);

        // 3. Elbow (Silver)
        const elbow = new THREE.Mesh(new THREE.SphereGeometry(0.19, 32, 32), chrome);
        elbow.position.y = -0.65;
        g.add(elbow);

        // 4. Forearm (Gauntlet)
        const forearm = new THREE.Mesh(
            new THREE.CylinderGeometry(0.16, 0.24, 0.45, 32),
            primaryBlue
        );
        forearm.position.y = -0.92;
        g.add(forearm);

        // Wrist Ring (Silver)
        const cuff = new THREE.Mesh(
            new THREE.TorusGeometry(0.22, 0.05, 16, 32),
            chrome
        );
        cuff.rotation.x = Math.PI / 2;
        cuff.position.y = -1.13;
        g.add(cuff);

        // 5. Hand (Cute Mitten)
        const hand = new THREE.Group();
        hand.position.y = -1.25;
        g.add(hand);

        // Main Mitten Shape (Flattened Sphere)
        const mittenGeo = new THREE.SphereGeometry(0.22, 32, 32);
        const mitten = new THREE.Mesh(mittenGeo, glossyWhite);
        mitten.scale.set(1.0, 1.2, 0.6); // Tall and flat
        hand.add(mitten);

        // Thumb (Small Sphere on side)
        const thumb = new THREE.Mesh(new THREE.SphereGeometry(0.12, 32, 32), glossyWhite);
        thumb.position.set(0, 0.1, 0.15);
        hand.add(thumb);

        return g;
    }

    const armL = createArm(-1.0);
    armL.rotation.z = 0.1;
    bodyGroup.add(armL);

    const armR = createArm(1.0);
    armR.rotation.z = -0.1;
    bodyGroup.add(armR);


    // 4. LEGS
    function createLeg(x) {
        const legGroup = new THREE.Group();
        legGroup.position.set(x, -0.7, 0);

        // Hip Joint
        const hip = new THREE.Mesh(new THREE.SphereGeometry(0.3, 32, 32), chrome);
        legGroup.add(hip);

        // Ribbed Tubing Leg
        const tube = createRibbedTube(1.0, 0.2, 5, chrome);
        tube.position.y = -0.6;
        legGroup.add(tube);

        // Boot/Foot
        const boot = new THREE.Group();
        boot.position.y = -1.2;
        legGroup.add(boot);

        const bootBase = createSquircleMesh(0.6, 0.3, 0.8, 0.15, primaryBlue);
        bootBase.rotation.x = -0.1;
        bootBase.position.y = 0.1;
        bootBase.scale.z = 1.2;
        boot.add(bootBase);

        const bootCuff = new THREE.Mesh(new THREE.CylinderGeometry(0.25, 0.25, 0.1, 32), glossyWhite);
        bootCuff.position.y = 0.3;
        boot.add(bootCuff);

        return legGroup;
    }

    const legL = createLeg(-0.4);
    robot.add(legL);

    const legR = createLeg(0.4);
    robot.add(legR);

    // Ground Shadow (Fake)
    const shadowGeo = new THREE.CircleGeometry(1.5, 32);
    const shadowMat = new THREE.MeshBasicMaterial({ color: 0x000000, opacity: 0.2, transparent: true });
    const shadow = new THREE.Mesh(shadowGeo, shadowMat);
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = -2.0;
    robot.add(shadow);


    // --- ANIMATION ---
    const mouse = { x: 0, y: 0 };
    let time = 0;

    // Idle System
    let lastMouseMoveTime = Date.now();
    let isIdle = false;

    document.addEventListener('mousemove', (e) => {
        mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;
        lastMouseMoveTime = Date.now();
        isIdle = false;
    });

    window.addEventListener('resize', () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });

    // Blinking State
    let isBlinking = false;
    let blinkProgress = 0;
    let blinkSpeed = 0.15;

    function animate() {
        requestAnimationFrame(animate);
        time += 0.02;

        // Idle Logic (500ms)
        if (Date.now() - lastMouseMoveTime > 500) {
            isIdle = true;
        }

        // Floating Bob (always active)
        robot.position.y = Math.sin(time * 1.2) * 0.06;

        // Head Animation
        if (isIdle) {
            // Look around using layered sine waves
            const targetX = Math.sin(time * 0.4) * 0.3 + Math.sin(time * 1.1) * 0.1;
            const targetY = Math.sin(time * 0.3) * 0.15;
            headGroup.rotation.y += (targetX - headGroup.rotation.y) * 0.05;
            headGroup.rotation.x += (targetY - headGroup.rotation.x) * 0.05;

            // Antenna Twitch
            if (Math.random() > 0.985) {
                antennaGroup.rotation.z = (Math.random() - 0.5) * 0.6;
            } else {
                antennaGroup.rotation.z *= 0.9;
            }

            // Chest Light Pulse
            const pulse = 1 + Math.sin(time * 3) * 0.15;
            chestLight.scale.set(pulse, pulse, 1);
        } else {
            // Track Mouse
            const targetX = mouse.x * 0.6;
            let targetY = -mouse.y * 0.4;
            // Limit looking down to prevent clipping
            if (targetY > 0.15) targetY = 0.15;

            headGroup.rotation.y += (targetX - headGroup.rotation.y) * 0.1;
            headGroup.rotation.x += (targetY - headGroup.rotation.x) * 0.1;

            // Reset antenna & chest
            antennaGroup.rotation.z *= 0.8;
            chestLight.scale.set(1, 1, 1);
        }

        bodyGroup.rotation.y = Math.sin(time * 0.5 - 0.5) * 0.02;

        // Arms Sway
        armL.rotation.x = Math.sin(time * 1.5) * 0.1;
        armR.rotation.x = Math.sin(time * 1.5 + 1) * 0.1;

        // BLINKING LOGIC
        if (Math.random() > 0.995) isBlinking = true;

        if (isBlinking) {
            blinkProgress += blinkSpeed;
            if (blinkProgress >= 1) {
                blinkProgress = 1;
                blinkSpeed = -0.15;
            } else if (blinkProgress <= 0) {
                blinkProgress = 0;
                blinkSpeed = 0.15;
                isBlinking = false;
            }
        }

        // Update Eyelids (safe even if no eyelid mesh exists)
        const closedY = -0.1;
        const openY = 0.24;
        const lidY = openY - (blinkProgress * (openY - closedY));
        const lids = [eyeL.getObjectByName("eyelid"), eyeR.getObjectByName("eyelid")];
        lids.forEach(lid => {
            if (lid) lid.position.y = lidY;
        });

        renderer.render(scene, camera);
    }

    animate();
});
