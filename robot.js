console.log("ROBOT: Starting initialization...");

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('robot-container');
    if (!container) {
        console.error("ROBOT: Container missing!");
        return;
    }

    console.log("ROBOT: Container found. Initializing scene...");

    // --- Scroll Animation Setup ---
    // 1. Get Anchors
    const heroVisual = document.querySelector('.hero-blue-visual');
    const targetHeading = document.querySelector('.center-bento-title'); // "Comprehensive Solutions..."

    // 2. Detach and Fix Container
    // We need to set explicit size because 'fixed' removes it from flow
    // --- Fullscreen Overlay Setup ---
    container.style.position = 'fixed';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '100%';
    container.style.height = '100%';
    container.style.zIndex = '50';
    container.style.pointerEvents = 'none'; // Click-through
    document.body.appendChild(container);

    const scene = new THREE.Scene();

    // Camera matches full viewport
    const camera = new THREE.PerspectiveCamera(40, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.set(0, 0, 9); // Initial Z depth

    // Optimize WebGL dynamically
    const isMobileSize = window.innerWidth < 768;
    
    // Check hardware capability: 6+ logical cores is typical for flagship phones
    const logicalCores = navigator.hardwareConcurrency || 4;
    const isHighTierMobile = isMobileSize && logicalCores >= 6;

    // NEVER use antialias on mobile, even flagship, because the high pixel ratio handles smoothing
    // and MSAA buffers at 2x resolution cause memory crashes (silent WebGL failure/disappearing robot)
    const renderer = new THREE.WebGLRenderer({ 
        alpha: true, 
        antialias: !isMobileSize,
        powerPreference: "high-performance" 
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    
    // High-end mobiles get up to 2.0 pixel ratio, standard gets 1.5, desktop uses native or 1.5
    const maxPixelRatio = isHighTierMobile ? 2.0 : 1.5;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, maxPixelRatio));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = isMobileSize ? THREE.BasicShadowMap : THREE.PCFSoftShadowMap;
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

        // Eyelid (ADDED BACK FOR SAFEKEEPING, BUT NOT ANIMATED YET TO AVOID CRASH)
        // If we animate it, we need to name it "eyelid"
        // For now, skipping to match 'createEye' function from previous context
        // But if blinking logic needs it, we should add it.
        // Let's add it but make it invisible initially or higher up.
        // Or simply remove blinking logic causing crash.
        // Removing blinking logic is safer.

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

    // RLabz Logo on Chest Screen (Cropped to just the icon)
    // Bypass local file CORS strictly by using an embedded base64 string of the logo
    const rlabzBase64 = "data:image/png;base64,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";
    const logoTexture = new THREE.TextureLoader().load(rlabzBase64, function (texture) {
        if (THREE.SRGBColorSpace) {
            texture.colorSpace = THREE.SRGBColorSpace;
        } else if (THREE.sRGBEncoding) {
            texture.encoding = THREE.sRGBEncoding;
        }
        console.log("ROBOT INFO: Base64 logo loaded!");
    });

    const logoWidth = 0.72;  // Slightly larger on the chest screen
    const logoHeight = logoWidth * (180 / 256); // Preserve aspect ratio 256x180
    const logoGeo = new THREE.PlaneGeometry(logoWidth, logoHeight);

    const logoMat = new THREE.MeshBasicMaterial({
        map: logoTexture,
        transparent: true,
        alphaTest: 0.1, // Discard invisible pixels
        depthWrite: false
    });

    const logoMesh = new THREE.Mesh(logoGeo, logoMat);

    // Position right on the chest screen
    logoMesh.position.set(0, 0.1, 0.61);
    bodyGroup.add(logoMesh);

    /* Default Heart removed for RLabz Logo
    // HEART (Glowing Pulse)
    const chestLight = new THREE.Mesh(
        new THREE.CircleGeometry(0.15, 32),
        new THREE.MeshBasicMaterial({ color: 0x0ea5e9 }) // Cyan glow
    );
    chestLight.position.z = 0.51 + 0.05 / 2 + 0.01;
    chestLight.position.y = 0.1;
    bodyGroup.add(chestLight);
    */

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

        // 5. Hand (Cartoon Mitt - Fixed Alignment & Position)
        const hand = new THREE.Group();
        hand.name = "hand";
        hand.position.y = -1.3;
        g.add(hand);

        // Palm (Huge flattened sphere - glove body)
        const palmGeo = new THREE.SphereGeometry(0.15, 32, 32);
        const palm = new THREE.Mesh(palmGeo, glossyWhite);
        palm.scale.set(1.4, 0.9, 0.8);
        hand.add(palm);

        // Fingers - LONGER, LOWER, and angled NATURALLY
        const fR = 0.065;
        const fLen = 0.16;

        const fingerData = [
            { angle: -0.25, x: -0.11 },
            { angle: 0.0, x: 0.00 },
            { angle: 0.25, x: 0.11 }
        ];

        for (let i = 0; i < 3; i++) {
            const fd = fingerData[i];
            const finger = new THREE.Mesh(
                new THREE.CapsuleGeometry(fR, fLen, 16, 8),
                glossyWhite
            );
            // Position: Start LOW (bottom of palm) so they aren't buried
            // Palm Y-radius is ~0.135 (0.15 * 0.9). Start at -0.12.
            finger.position.set(fd.x, -0.12, 0.05);

            // Point DOWN and FORWARD
            // Math.PI = Straight Down.
            // Math.PI - 0.5 = Angled forward ~30 deg from vertical.
            // This looks natural and shows length.
            finger.rotation.x = Math.PI - 0.5;

            finger.rotation.z = fd.angle;

            hand.add(finger);
        }

        // Thumb (Thick nub on side)
        const thumbSide = x > 0 ? -1 : 1;
        const thumb = new THREE.Mesh(
            new THREE.CapsuleGeometry(fR * 1.1, fLen * 0.9, 16, 8),
            glossyWhite
        );
        // Stick out from side EDGE (Inner Side)
        // Palm width is ~0.21. Thumb needs to be near 0.20.
        thumb.position.set(0.20 * thumbSide, -0.05, 0.04);

        // Angle out ~45deg
        thumb.rotation.z = 0.6 * thumbSide;

        // Curl forward/down
        thumb.rotation.x = -0.3;

        hand.add(thumb);

        return g;
    }

    const armL = createArm(-1.0);
    armL.rotation.z = 0.1;
    bodyGroup.add(armL);

    const armR = createArm(1.0);
    armR.rotation.z = -0.1;
    bodyGroup.add(armR);

    // --- PROPS SETUP (Right Hand) ---
    const handR = armR.getObjectByName("hand");
    const props = {};

    if (handR) {
        // 1. Mobile Phone (Simple Box Model for Guaranteed Visibility)
        const mobileProp = new THREE.Group();

        // Materials
        const frameMat = new THREE.MeshStandardMaterial({
            color: 0xcccccc, roughness: 0.4, metalness: 0.5
        });
        const screenMat = new THREE.MeshBasicMaterial({ color: 0x000000 });

        // High Contrast Camera Materials
        const camBumpMat = new THREE.MeshBasicMaterial({ color: 0x111111 });
        const camRingMat = new THREE.MeshBasicMaterial({ color: 0xffffff });

        // Dims
        const pW = 0.45;
        const pH = 0.85;
        const pD = 0.05; // Slightly thicker to be safe

        // Body (BoxGeometry - No bevels, no confusion)
        const phoneBody = new THREE.Mesh(
            new THREE.BoxGeometry(pW, pH, pD),
            frameMat
        );

        // Screen (Plane on front)
        const phoneScreen = new THREE.Mesh(
            new THREE.PlaneGeometry(pW - 0.04, pH - 0.04),
            screenMat
        );
        phoneScreen.position.z = pD / 2 + 0.002;

        mobileProp.add(phoneBody, phoneScreen);

        // Cameras (On Back)
        const backZ = -pD / 2;

        const camOffsetX = -0.12;
        const camStartY = 0.25;
        const camSpacing = 0.16;

        for (let i = 0; i < 3; i++) {
            const posY = camStartY - i * camSpacing;

            // Large Camera Bump
            const bump = new THREE.Mesh(
                new THREE.CylinderGeometry(0.07, 0.07, 0.05, 32), // Cylinder sticks out more visibly
                camBumpMat
            );
            bump.rotation.x = Math.PI / 2;
            bump.position.set(camOffsetX, posY, backZ - 0.025); // Push out by half height
            mobileProp.add(bump);

            // Ring
            const ring = new THREE.Mesh(
                new THREE.TorusGeometry(0.07, 0.015, 8, 32),
                camRingMat
            );
            ring.position.set(camOffsetX, posY, backZ - 0.05); // At the tip
            mobileProp.add(ring);
        }

        // Flash
        const flash = new THREE.Mesh(
            new THREE.CylinderGeometry(0.03, 0.03, 0.02, 16),
            new THREE.MeshBasicMaterial({ color: 0xffff00 })
        );
        flash.rotation.x = Math.PI / 2;
        flash.position.set(camOffsetX + 0.12, camStartY, backZ - 0.01);
        mobileProp.add(flash);

        // Orientation: Back faces user
        mobileProp.rotation.x = -0.2;
        mobileProp.rotation.y = 0.2; // Turn around so screen faces robot
        mobileProp.rotation.z = Math.PI - 0.1; // Flip upright (screen still faces robot)
        mobileProp.position.set(0.05, -0.45, 0.15);

        mobileProp.visible = false;
        handR.add(mobileProp);
        props['Mobile Solutions'] = mobileProp;

        // 2. Web Solutions (Mini Laptop)
        // 2. Web Solutions (Mini Laptop)
        const laptopProp = new THREE.Group();

        // Laptop Material (Dark Grey Matte)
        const laptopFrameMat = new THREE.MeshStandardMaterial({
            color: 0x2b2b2b,
            roughness: 0.4,
            metalness: 0.5
        });

        // 1. BASE (Keyboard section)
        // Using BoxGeometry for sharper, recognizable laptop shape
        const baseGeo = new THREE.BoxGeometry(0.8, 0.04, 0.55);
        const lBase = new THREE.Mesh(baseGeo, laptopFrameMat);

        // Keyboard Texture/Mesh (Darker inset)
        const kbGeo = new THREE.PlaneGeometry(0.7, 0.28);
        const kbMat = new THREE.MeshBasicMaterial({ color: 0x111111 }); // Black keys
        const keyboard = new THREE.Mesh(kbGeo, kbMat);
        keyboard.rotation.x = -Math.PI / 2;
        keyboard.position.set(0, 0.021, 0.05); // On top of base
        lBase.add(keyboard);

        // 2. LID (Screen section)
        const lidGroup = new THREE.Group();
        // Hinge position: Back edge of base
        lidGroup.position.set(0, 0.02, -0.27);

        // Screen Frame
        const lidGeo = new THREE.BoxGeometry(0.8, 0.04, 0.55);
        const lLid = new THREE.Mesh(lidGeo, laptopFrameMat);
        // Offset lid so it rotates around bottom edge
        lLid.position.set(0, 0, -0.275);
        // Actually, easiest to just center the geometry in a logical way or offset mesh
        // Let's keep it simple: Pivot is at (0,0,0) of lidGroup
        // Lid mesh center should be up and back?
        // Let's re-think: Lid is a box. Pivot is bottom edge.
        lLid.geometry.translate(0, 0, -0.275); // Shift geometry so origin is at bottom edge? No.
        // Re-do Lid: Origin of lidGroup is the Hinge.
        // Lid mesh should extend UP from hinge.
        // If we rotate lidGroup X, lid mesh should follow.
        // Let's define lid mesh relative to hinge.
        // Hinge is at Z = -0.27 of base.
        // Lid mesh center should be at Z = -0.275 (half height) if it was flat?
        // Let's just build it simple:
        const screenFrame = new THREE.Mesh(new THREE.BoxGeometry(0.8, 0.55, 0.04), laptopFrameMat);
        screenFrame.position.set(0, 0.275, 0); // Center relative to hinge (which is at bottom of screen)

        // Screen Display (Bright Blue)
        const screenGeo = new THREE.PlaneGeometry(0.72, 0.45);
        const laptopScreenMat = new THREE.MeshBasicMaterial({ color: 0x3399ff });
        const screen = new THREE.Mesh(screenGeo, laptopScreenMat);
        screen.position.set(0, 0.275, 0.021); // Slightly in front of frame
        lidGroup.add(screenFrame, screen);

        // Open the laptop
        lidGroup.rotation.x = -Math.PI / 12; // Tilted back slightly from vertical? 
        // Wait, Base is flat (Y up). 
        // ScreenFrame is vertical (Y up).
        // If rotation is 0, it's a 90 degree angle (L shape).
        // We want it slightly more open, so rotate back.
        lidGroup.rotation.x = 0.3; // Tilt back 

        laptopProp.add(lBase, lidGroup);

        // Orientation in hand
        // Arm hold pose: rotation.x = -1.2, rotation.z = -0.3
        // Refined Alignment: 
        // 1. Tilt base slightly towards viewer (RotX 1.6 - 1.2 = 0.4 rad tilt)
        // 2. Adjust position to sit ON TOP of the mitten sphere
        laptopProp.rotation.x = 1.6;
        laptopProp.rotation.y = -0.1;
        laptopProp.rotation.z = 0.3;

        // Position: Sit on the sphere
        laptopProp.position.set(0, -0.20, 0.3);

        laptopProp.visible = false;
        handR.add(laptopProp);
        props['Web Solutions'] = laptopProp;

        // 3. Rocket (Services)
        const rocketProp = new THREE.Group();
        const rBody = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 0.25, 16), glossyWhite);
        const rNose = new THREE.Mesh(new THREE.ConeGeometry(0.06, 0.12, 16), new THREE.MeshBasicMaterial({ color: 0xff4444 }));
        rNose.position.y = 0.18;
        const rFin = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.08, 0.02), new THREE.MeshBasicMaterial({ color: 0xff4444 }));
        rFin.position.y = -0.08;
        rocketProp.add(rBody, rNose, rFin);
        rocketProp.add(rBody, rNose, rFin);
        // Arm is at -1.8 (Face level). 
        // To point UP, we need to counter-rotate.
        rocketProp.rotation.x = 1.5;
        rocketProp.rotation.z = 0.5;
        rocketProp.position.set(0, -0.4, 0.1);
        rocketProp.scale.set(1.8, 1.8, 1.8); // Scale up for visibility
        rocketProp.visible = false;
        handR.add(rocketProp);
        props['Digital Services'] = rocketProp;

        // 4. Cloud (Integration)
        const cloudProp = new THREE.Group();
        const c1 = new THREE.Mesh(new THREE.SphereGeometry(0.12, 16, 16), glossyWhite);
        const c2 = new THREE.Mesh(new THREE.SphereGeometry(0.09, 16, 16), glossyWhite);
        c2.position.set(0.14, -0.02, 0);
        const c3 = new THREE.Mesh(new THREE.SphereGeometry(0.09, 16, 16), glossyWhite);
        c3.position.set(-0.14, -0.02, 0);
        cloudProp.add(c1, c2, c3);
        cloudProp.position.set(0, -0.4, 0.1);
        cloudProp.scale.set(1.8, 1.8, 1.8); // Scale up for visibility
        cloudProp.visible = false;
        handR.add(cloudProp);
        props['Cloud Integration'] = cloudProp;
    }


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
        if (!isHoldingObject) isIdle = false; // Only wake up if not holding object (or wake up anyway?)
        // Better: wake up anyway, but head tracking might be overridden if holding? 
        // For now, let's keep it simple.
        isIdle = false;
    });

    // --- INTERACTION LISTENERS ---
    let isHoldingObject = false;
    let targetArmRotX = 0; // Default sine wave base
    let targetArmRotZ = -0.1; // Default idle

    const cards = document.querySelectorAll('.feature-card');

    // Global event listener for explicitly forcing a prop (used by mobile swiper)
    window.addEventListener('robot-show-prop', (e) => {
        const title = e.detail.title;
        if (!title) {
            isHoldingObject = false;
            Object.values(props).forEach(p => p.visible = false);
        } else if (props[title]) {
            Object.values(props).forEach(p => p.visible = false);
            props[title].visible = true;
            isHoldingObject = true;
            isIdle = false; // Wake up robot
        }
    });

    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            if (window.innerWidth <= 992) return; // Ignore on mobile (handled by scroll sync)
            const title = card.querySelector('h3') ? card.querySelector('h3').textContent.trim() : '';
            window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title } }));
        });

        card.addEventListener('mouseleave', () => {
            if (window.innerWidth <= 992) return;
            window.dispatchEvent(new CustomEvent('robot-show-prop', { detail: { title: null } }));
        });
    });

    // Custom Event from script.js GSAP hover logic
    // Fade out the robot when a dynamic preview is shown in the bento grid
    window.addEventListener('bento-hover', (e) => {
        if (e.detail.active) {
            container.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            container.style.opacity = '0';
            container.style.transform = 'scale(0.95)';
        } else {
            container.style.opacity = '1';
            container.style.transform = 'scale(1)';
        }
    });

    // Stable Viewport Caching for Mobile Jitter Fix
    let vw = window.innerWidth;
    let vh = Math.max(window.innerHeight, 1);
    let scrollY = window.scrollY;

    function invalidateRobotMobileCache() {
        window._robotMobileCached = false;
        window._robotWorldUnitsPerPixel = null;
        window._robotMobileCachedScrollY = null;
        window._robotMobileCachedHeroX = null;
        window._robotMobileCachedHeroY = null;
        window._robotMobileCachedScaleHero = null;
        window._robotMobileCachedAnchorX = null;
        window._robotMobileCachedAnchorY = null;
    }

    function syncViewportMetrics() {
        vw = window.innerWidth;
        vh = Math.max(window.innerHeight, 1);
        camera.aspect = vw / vh;
        camera.updateProjectionMatrix();
        renderer.setSize(vw, vh);
        invalidateRobotMobileCache();
    }

    // Resize Handling
    let resizeTimer;
    const queueViewportSync = () => {
        clearTimeout(resizeTimer);
        // Debounce mobile browser chrome changes so the robot re-caches once per settled viewport update.
        resizeTimer = setTimeout(syncViewportMetrics, 180);
    };

    window.addEventListener('resize', queueViewportSync);
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', queueViewportSync);
    }

    window.addEventListener('orientationchange', () => {
        clearTimeout(resizeTimer);
        syncViewportMetrics();
    });

    window.addEventListener('scroll', () => {
        scrollY = window.scrollY;
    }, { passive: true });

    // Helper: Map DOM (pixels) to World (3D units) at a given depth
    function getZPosition(depth) {
        // Calculate the height of the view at this depth
        const vFOV = THREE.MathUtils.degToRad(camera.fov); // vertical fov in radians
        const height = 2 * Math.tan(vFOV / 2) * (camera.position.z - depth);
        const width = height * camera.aspect;
        return { width, height };
    }

    function mapDomToWorld(rect, depth, alignMode = 'center') {
        const { width: viewW, height: viewH } = getZPosition(depth);
        const canvasW = vw;
        const canvasH = vh;

        // Normalized coordinates (-1 to +1)
        // Center of rect
        let domX = rect.left + rect.width / 2;
        let domY = rect.top + rect.height / 2;

        if (alignMode === 'left-of') {
            domX = rect.left;
        } else if (alignMode === 'right-center') {
            domX = rect.right; // Just the X reference
            domY = rect.top + rect.height / 2;
        }

        const ndcX = (domX / canvasW) * 2 - 1;
        const ndcY = -(domY / canvasH) * 2 + 1;

        const worldX = (ndcX * viewW) / 2;
        const worldY = (ndcY * viewH) / 2;
        return { x: worldX, y: worldY };
    }


    // Blinking State
    let isBlinking = false;
    let blinkProgress = 0;
    let blinkSpeed = 0.15;

    // Waving State (New)
    let isWaving = false;
    let waveStartTime = 0;

    // Trigger Wave on Load
    setTimeout(() => {
        isWaving = true;
        waveStartTime = Date.now();

        // Stop waving after 2.0 seconds (2 slow waves)
        setTimeout(() => {
            isWaving = false;
        }, 2000);
    }, 1000); // Wait 1s for robot to appear deeply

    let smoothedPos = null;

    function animate() {
        try {
            requestAnimationFrame(animate);
            time += 0.02;

            // --- 1. Position & Scroll Logic (Run First) ---
            let currentPos = new THREE.Vector3(0, 0, 0);
            let currentScale = 1;

            if (heroVisual && targetHeading) {
                const bentoSection = document.getElementById('what-we-do');
                let targetAnchor = document.querySelector('#robot-target-anchor');

                // Hijack the anchor trajectory on mobile to push the robot safely to the bottom of the screen
                if (window.innerWidth <= 992) {
                    const mobileAnchor = document.querySelector('#robot-target-anchor-mobile');
                    if (mobileAnchor) targetAnchor = mobileAnchor;
                }

                if (window.innerWidth <= 992) {
                    // === MOBILE: Fully cached, zero per-frame DOM reads ===
                    if (!window._robotMobileCached) {
                        const rHero = heroVisual.getBoundingClientRect();
                        const depth0 = 0;
                        const posHero = mapDomToWorld(rHero, depth0, 'center');

                        // Calculate world units per pixel for jitter-free scroll offset
                        // viewHeight is the total world units visible top-to-bottom at depth0
                        const { height: viewHeight } = getZPosition(depth0);
                        window._robotWorldUnitsPerPixel = viewHeight / vh;
                        window._robotMobileCachedScrollY = scrollY;

                        let responsiveScale = window.innerWidth <= 380 ? 0.40 : (window.innerWidth <= 480 ? 0.45 : (window.innerWidth <= 768 ? 0.55 : (window.innerWidth <= 992 ? 0.70 : 0.82)));

                        // Force perfect horizontal center to prevent perspective drift
                        window._robotMobileCachedHeroX = 0;
                        window._robotMobileCachedHeroY = posHero.y;
                        window._robotMobileCachedScaleHero = responsiveScale;

                        // Compute anchor position from viewport math (bottom 15% of screen, centered)
                        const anchorRect = { left: vw / 2, top: vh * 0.85, width: 0, height: 0, right: vw / 2, bottom: vh * 0.85 };
                        const posAnchor = mapDomToWorld(anchorRect, depth0, 'center');
                        window._robotMobileCachedAnchorX = 0; // Force perfect center
                        window._robotMobileCachedAnchorY = posAnchor.y;

                        window._robotMobileCached = true;
                    }

                    const cHeroX = window._robotMobileCachedHeroX;
                    const cHeroY = window._robotMobileCachedHeroY;
                    const cAnchorX = window._robotMobileCachedAnchorX;
                    const cAnchorY = window._robotMobileCachedAnchorY;
                    const cScaleHero = window._robotMobileCachedScaleHero;
                    const cScaleAnchor = 0.35;
                    const cInitialScroll = window._robotMobileCachedScrollY;
                    const unitsPerPixel = window._robotWorldUnitsPerPixel;

                    const progress2 = window.bentoScrollProgress || 0;
                    const deltaScroll = scrollY - cInitialScroll;
                    const worldOffset = deltaScroll * unitsPerPixel;

                    // Detect actual screen position of the section to handle unpinning
                    const rBento = bentoSection.getBoundingClientRect();
                    // Force offset to 0 while pinned to prevent jitter — only apply when scrolling away/towards
                    const isPinned = progress2 > 0.01 && progress2 < 0.99;
                    const stableBentoTop = isPinned ? 0 : rBento.top;
                    const bentoWorldOffset = -stableBentoTop * unitsPerPixel;

                    if (progress2 >= 0.3) {
                        // Solutions cards are appearing — robot rises from below
                        const riseStart = 0.3;
                        const riseEnd = 0.7;
                        const riseProgress = Math.min(1, (progress2 - riseStart) / (riseEnd - riseStart));
                        const riseEase = 1 - Math.pow(1 - riseProgress, 3);

                        const belowScreenY = -6;
                        currentPos.x = cAnchorX;
                        currentPos.y = belowScreenY + (cAnchorY - belowScreenY) * riseEase + bentoWorldOffset;
                        currentScale = cScaleAnchor + (cScaleAnchor * 0.2) * (1 - riseEase);
                    } else if (progress2 > 0.01) {
                        // Pinned section started but cards haven't reached rise point — hide
                        currentPos.x = cAnchorX;
                        currentPos.y = -6 + bentoWorldOffset;
                        currentScale = cScaleAnchor;
                    } else {
                        // Hero section — robot scrolls 1:1 with the page (mathematical offset)
                        currentPos.x = cHeroX;
                        currentPos.y = cHeroY + worldOffset;
                        currentScale = cScaleHero;
                        
                        // Hide it if it scrolls too far off top or bottom
                        if (currentPos.y > 7 || currentPos.y < -7) {
                            currentPos.y = -10; 
                        }
                    }

                    currentPos.z = 0;
                    container.style.opacity = '1';
                    if (shadow) {
                        const ease2 = progress2 < 0.5 ? 2 * progress2 * progress2 : 1 - Math.pow(-2 * progress2 + 2, 2) / 2;
                        shadow.material.opacity = 0.2 * (1 - ease2);
                    }
                } else {
                    // === DESKTOP: Original scroll-linked behavior (unchanged) ===
                    const rHero = heroVisual.getBoundingClientRect();
                    const rBento = bentoSection.getBoundingClientRect();
                    const targetElement = targetAnchor ? targetAnchor : targetHeading;
                    const rAnchor = targetElement.getBoundingClientRect();

                    const rCenter = {
                        left: vw / 2,
                        top: vh * 0.98,
                        width: 0, height: 0,
                        right: vw / 2,
                        bottom: vh * 0.98
                    };

                    const depth0 = 0;
                    const posHero = mapDomToWorld(rHero, depth0, 'center');
                    const posCenter = mapDomToWorld(rCenter, depth0, 'center');
                    const posAnchor = mapDomToWorld(rAnchor, depth0, 'center');

                    let responsiveScale = window.innerWidth <= 380 ? 0.40 : (window.innerWidth <= 480 ? 0.45 : (window.innerWidth <= 768 ? 0.55 : (window.innerWidth <= 992 ? 0.70 : 0.82)));
                    const scaleHero = responsiveScale;
                    const scaleCenter = window.innerWidth <= 768 ? responsiveScale * 1.1 : 0.85;
                    const scaleAnchor = targetAnchor ? 0.38 : 0.32;

                    // Phase 1 Progress: Scroll from Hero to Bento
                    let progress1 = 0;
                    if (rBento.top > 0) {
                        progress1 = 1 - Math.min(1, rBento.top / vh);
                    } else {
                        progress1 = 1;
                    }
                    const ease1 = 1 - Math.pow(1 - progress1, 3);

                    // Phase 2 Progress: Driven by GSAP from script.js
                    const progress2 = window.bentoScrollProgress || 0;
                    const ease2 = progress2 < 0.5 ? 2 * progress2 * progress2 : 1 - Math.pow(-2 * progress2 + 2, 2) / 2;

                    // Compute Base Phase 1 Output
                    currentPos.x = posHero.x + (posCenter.x - posHero.x) * ease1;
                    currentPos.y = posHero.y + (posCenter.y - posHero.y) * ease1;
                    currentScale = scaleHero + (scaleCenter - scaleHero) * ease1;

                    // Compute Overlay Phase 2 Output
                    if (progress2 > 0) {
                        const centerAdjustedY = posCenter.y + 0.15;
                        currentPos.x = posCenter.x + (posAnchor.x - posCenter.x) * ease2;
                        currentPos.y = centerAdjustedY + (posAnchor.y - centerAdjustedY) * ease2;
                        currentScale = scaleCenter + (scaleAnchor - scaleCenter) * ease2;
                    }

                    currentPos.z = depth0;
                    container.style.opacity = '1';
                    if (shadow) {
                        shadow.material.opacity = 0.2 * (1 - ease2);
                    }
                }
            }

            // --- 2. Apply Position + Bobbing ---
            const bobOffset = Math.sin(time * 1.2) * 0.06;

            if (isFinite(currentPos.x) && isFinite(currentPos.y) && isFinite(currentPos.z)) {
                if (!smoothedPos) {
                    smoothedPos = currentPos.clone();
                } else {
                    // Soft LERP on mobile completely masks any compositor 1-frame jitter
                    const lerpFactor = window.innerWidth <= 992 ? 0.15 : 1.0; 
                    
                    // Bypass LERP for large jumps (teleporting between sections) to prevent "flashing" across screen
                    if (smoothedPos.distanceTo(currentPos) > 3.0) {
                        smoothedPos.copy(currentPos);
                    } else {
                        smoothedPos.lerp(currentPos, lerpFactor);
                    }
                }
                
                robot.position.set(smoothedPos.x, smoothedPos.y + bobOffset, smoothedPos.z);
                robot.scale.set(currentScale, currentScale, currentScale);
            } else {
                robot.position.set(0, -1 + bobOffset, 0);
                robot.scale.set(1, 1, 1);
            }

            // --- 3. Body Animations ---
            bodyGroup.rotation.y = Math.sin(time * 0.5 - 0.5) * 0.02;

            if (isWaving) {
                bodyGroup.rotation.y += (0 - bodyGroup.rotation.y) * 0.1;
                const targetArmRotX = -2.5;
                const elapsed = (Date.now() - waveStartTime) / 1000;
                const waveOscillation = Math.sin(elapsed * Math.PI * 2) * 0.3;
                const targetArmRotZ = 0.4 + waveOscillation;

                armR.rotation.x += (targetArmRotX - armR.rotation.x) * 0.1;
                armR.rotation.z += (targetArmRotZ - armR.rotation.z) * 0.1;

                if (handR) {
                    handR.rotation.x += (1.0 - handR.rotation.x) * 0.1;
                    handR.rotation.z = Math.sin(time * 8) * 0.2;
                }
                armL.rotation.x = Math.sin(time * 1.5) * 0.1;
            } else if (isHoldingObject) {
                if (handR) {
                    handR.rotation.x *= 0.8;
                    handR.rotation.z *= 0.8;
                }
                const raiseSpeed = 0.03; // Slower, smoother reveal
                let holdRotX = -1.8;
                let holdRotZ = -0.5;

                if (props['Web Solutions'] && props['Web Solutions'].visible) {
                    holdRotX = -1.2;
                    holdRotZ = -0.3;
                }

                armR.rotation.x += (holdRotX - armR.rotation.x) * raiseSpeed;
                armR.rotation.z += (holdRotZ - armR.rotation.z) * raiseSpeed;
                armL.rotation.x = Math.sin(time * 1.5) * 0.1;
            } else {
                if (handR) {
                    handR.rotation.x *= 0.8;
                    handR.rotation.z *= 0.8;
                }
                armL.rotation.x = Math.sin(time * 1.5) * 0.1;
                const idleRotX = Math.sin(time * 1.5 + 1) * 0.1;
                const idleRotZ = -0.1;

                armR.rotation.x += (idleRotX - armR.rotation.x) * 0.05; // Smoother return to idle
                armR.rotation.z += (idleRotZ - armR.rotation.z) * 0.05; // Smoother return to idle
            }

            // --- 4. Head Logic (Idle vs Looking) ---
            if (isNaN(headGroup.rotation.y)) headGroup.rotation.y = 0;
            if (isNaN(headGroup.rotation.x)) headGroup.rotation.x = 0;

            if (Date.now() - lastMouseMoveTime > 500) {
                isIdle = true;
            }

            if (isWaving) {
                const lookAtX = -currentPos.x;
                const lookAtZ = 9 - currentPos.z;
                const targetHeadRotY = Math.atan2(lookAtX, lookAtZ);
                const lookAtY = -(currentPos.y + 1.6);
                let targetHeadRotX = Math.atan2(-lookAtY, lookAtZ);
                if (targetHeadRotX > 0.15) targetHeadRotX = 0.15;

                headGroup.rotation.y += (targetHeadRotY - headGroup.rotation.y) * 0.1;
                headGroup.rotation.x += (targetHeadRotX - headGroup.rotation.x) * 0.1;

                antennaGroup.rotation.z *= 0.8;
            } else if (isIdle) {
                const idleTX = Math.sin(time * 0.4) * 0.3 + Math.sin(time * 1.1) * 0.1;
                const idleTY = Math.sin(time * 0.3) * 0.15;
                headGroup.rotation.y += (idleTX - headGroup.rotation.y) * 0.05;
                headGroup.rotation.x += (idleTY - headGroup.rotation.x) * 0.05;

                if (Math.random() > 0.985) {
                    antennaGroup.rotation.z = (Math.random() - 0.5) * 0.6;
                } else {
                    antennaGroup.rotation.z *= 0.9;
                }
            } else {
                const robotScreen = robot.position.clone().project(camera);
                const triggerStart = 0;
                const triggerEnd = window.innerHeight * 0.8;
                const range = triggerEnd - triggerStart;
                let currentScrollT = (Math.abs(range) > 1) ? (scrollY - triggerStart) / range : 0;
                currentScrollT = Math.max(0, Math.min(1, isFinite(currentScrollT) ? currentScrollT : 0));
                const ease = currentScrollT * currentScrollT * (3 - 2 * currentScrollT);

                const refX = robotScreen.x * ease;
                const refY = robotScreen.y * ease;
                const dx = mouse.x - refX;
                const dy = mouse.y - refY;

                const targetX = dx * 0.6;
                let targetY = -dy * 0.4;
                if (targetY > 0.15) targetY = 0.15;

                if (!isNaN(targetX)) {
                    headGroup.rotation.y += (targetX - headGroup.rotation.y) * 0.1;
                }
                if (!isNaN(targetY)) {
                    headGroup.rotation.x += (targetY - headGroup.rotation.x) * 0.1;
                }

                antennaGroup.rotation.z *= 0.8;
            }

            renderer.render(scene, camera);
        } catch (err) {
            console.error("CRASH IN ANIMATE LOOP:", err);
            // Render it to screen so the user can see exactly what broke
            let debugBlock = document.getElementById('robot-debug-error');
            if (!debugBlock) {
                debugBlock = document.createElement('div');
                debugBlock.id = 'robot-debug-error';
                debugBlock.style.cssText = "position:fixed; top:10%; left:10%; right:10%; background:red; color:white; p-4; z-index:99999; font-size:16px; font-family:monospace; padding: 20px;";
                document.body.appendChild(debugBlock);
            }
            debugBlock.innerHTML = "Robot crashed!<br>" + err.stack.replace(/\n/g, '<br>');
        }
    }

    // Start Animation with Try-Catch for safety
    try {
        animate();
        console.log("ROBOT: Animation loop started.");

        // Trigger Preloader Removal
        if (typeof window.hideRLabzPreloader === 'function') {
            window.hideRLabzPreloader();
        }
    } catch (e) {
        console.error("ROBOT: Animation failed to start:", e);

        // Trigger Preloader Removal on error to ensure user can still see the site
        if (typeof window.hideRLabzPreloader === 'function') {
            window.hideRLabzPreloader();
        }
    }
});
