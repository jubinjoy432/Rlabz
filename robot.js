console.log("ROBOT: Starting initialization...");

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('robot-container');
    if (!container) {
        console.error("ROBOT: Container missing!");
        return;
    }

    // --- Robot Popup Setup ---
    let popupState = 'hidden';
    const popup = document.createElement('div');
    popup.id = 'robot-popup';
    container.appendChild(popup);

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
    const rlabzBase64 = "data:image/png;base64,...";
const logoTexture = new THREE.TextureLoader().load(
    'images/rz-logo.webp',
    function (texture) {
        if (THREE.SRGBColorSpace) {
            texture.colorSpace = THREE.SRGBColorSpace;
        } else if (THREE.sRGBEncoding) {
            texture.encoding = THREE.sRGBEncoding;
        }
        console.log("ROBOT INFO: Logo loaded!");
    }
);

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

                        // Force perfect horizontal center, and place it at mathematically reliable height above text
                        window._robotMobileCachedHeroX = 0;
                        window._robotMobileCachedHeroY = viewHeight * 0.15; // Sit inside card's upper white area
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
                    const progress1 = window.heroScrollProgress || 0;

                    // The height of the view mapped to 100vh is simply viewHeight
                    const { height: viewHeight } = getZPosition(0);

                    // Incorporate negative scrollY to perfectly stick the robot to the iOS/Android "rubber-band" bounce
                    const overscrollBounce = scrollY < 0 ? (scrollY * unitsPerPixel) : 0;
                    const worldOffset = (progress1 * viewHeight) + overscrollBounce;

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
                        // Protect against NaN
                        const riseProgress = Math.min(1, Math.max(0, (progress2 - riseStart) / (riseEnd - riseStart)));
                        const riseEase = 1 - Math.pow(1 - riseProgress, 3);

                        const belowScreenY = -10;
                        currentPos.x = cAnchorX;
                        currentPos.y = belowScreenY + (cAnchorY - belowScreenY) * riseEase + bentoWorldOffset;
                        currentScale = cScaleAnchor + (cScaleAnchor * 0.2) * (1 - riseEase);
                    } else if (progress2 > 0.01) {
                        // Pinned section started but cards haven't reached rise point — hide
                        currentPos.x = cAnchorX;
                        currentPos.y = -10 + bentoWorldOffset;
                        currentScale = cScaleAnchor;
                    } else {
                        // Hero section — robot scrolls 1:1 with the page via GSAP Proxy
                        currentPos.x = cHeroX;
                        currentPos.y = cHeroY + worldOffset;
                        currentScale = cScaleHero;

                        // Hide it if it scrolls fundamentally out of the world bounds
                        if (currentPos.y > 15 || currentPos.y < -15) {
                            currentPos.y = -20;
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
                    // GSAP is perfectly smooth on its own; LERP is no longer needed and causes overlap
                    const lerpFactor = 1.0;

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

            // Update Popup Position
            if (popupState === 'tracking' && popup) {
                const rPos = robot.position.clone();
                const isMobile = window.innerWidth < 768;
                
                if (isMobile) {
                    rPos.x += 0.65; // Right edge of head / over the ear
                    rPos.y += 1.0; // Lower down (around neck/lower head)
                } else {
                    rPos.x += 1.2; // Right side of the robot
                    rPos.y += 0.5; // Roughly chest level
                }
                
                rPos.project(camera);
                
                let px = (rPos.x * 0.5 + 0.5) * window.innerWidth;
                let py = -(rPos.y * 0.5 - 0.5) * window.innerHeight;
                
                if (isMobile) {
                    popup.style.transform = 'translate(0, -50%)'; // Anchor to left
                    const bubbleW = popup.getBoundingClientRect().width || 110;
                    
                    // Clamp padding so it doesn't overflow right edge entirely
                    const maxPx = window.innerWidth - bubbleW - 10;
                    if (px > maxPx) px = maxPx;
                    
                    // Ensure it clears the 80px mobile header
                    if (py < 90) py = 90;
                } else {
                    popup.style.transform = '';
                    // Clamp padding so it doesn't overflow white container (desktop only)
                    const maxPx = window.innerWidth - 250;
                    if (px > maxPx) px = maxPx;
                }

                if (Math.abs(rPos.z) > 0.99) {
                    popup.style.opacity = '0';
                } else {
                    popup.style.opacity = '1';
                    popup.style.left = `${px}px`;
                    popup.style.top = `${py}px`;
                }
            }
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

        // Delay popup until screen loaded
        setTimeout(() => {
            if (popupState === 'hidden' && popup) {
                popupState = 'tracking';
                popup.innerHTML = `
                    <div class="popup-bubble">
                        <div class="popup-arrow"></div>
                        <span class="desktop-text">
                            <span class="typing-target"></span>
                            <button class="popup-close-btn desktop-close-btn" style="display: none; background: rgba(255,255,255,0.2); border: none; color: white; opacity: 0.9; font-size: 0.8rem; cursor: pointer; padding: 0; width: 22px; height: 22px; margin-left: 12px; border-radius: 50%; justify-content: center; align-items: center;"><i class="fas fa-times"></i></button>
                        </span>
                        <span class="mobile-text">
                            <span class="typing-target-mobile"></span>
                            <span class="banner-actions" style="display: flex; opacity: 0; width: 0; overflow: hidden; align-items: center; gap: 10px; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
                                <a href="" target="_blank" class="banner-btn" style="white-space: nowrap; background: white; color: #d6aa31; padding: 6px 12px; border-radius: 12px; font-size: 0.8rem; text-decoration: none; font-weight: bold;">Know More &rarr;</a>
                                <button class="popup-close-btn" style="background: none; border: none; color: white; opacity: 0.9; font-size: 1.2rem; cursor: pointer; padding: 0;"><i class="fas fa-times"></i></button>
                            </span>
                        </span>
                    </div>
                `;
                popup.classList.add('visible');

                const popupCloseBtns = popup.querySelectorAll('.popup-close-btn');
                popupCloseBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        popup.style.setProperty('display', 'none', 'important');
                    });
                });

                // Typing effect
                let charInd = 0;
                const targetText = "Internships Open";
                const targetTextMobile = "Internships Open";

                function typeBubble() {
                    if (popupState !== 'tracking') return;
                    const desktopEl = popup.querySelector('.typing-target');
                    const mobileEl = popup.querySelector('.typing-target-mobile');
                    
                    if (desktopEl && charInd < targetText.length) {
                        desktopEl.innerHTML += targetText.charAt(charInd);
                    }
                    if (mobileEl && charInd < targetTextMobile.length) {
                        mobileEl.innerHTML += targetTextMobile.charAt(charInd);
                    }
                    
                    charInd++;
                    if (charInd < Math.max(targetText.length, targetTextMobile.length)) {
                        setTimeout(typeBubble, 60);
                    }
                }
                
                // Start typing shortly after appearing
                setTimeout(typeBubble, 300);

                // Wait 5 seconds, then glide to sidebar strictly as a comment box
                setTimeout(() => {
                    if (popupState === 'tracking' && popup) {
                        popupState = 'transitioning';
                        popup.style.opacity = '1'; // Ensure visible
                        
                        // Small delay ensures bounds read
                        setTimeout(() => {
                            const exactWidth = popup.getBoundingClientRect().width;
                            const isMobile = window.innerWidth < 768;
                            
                            const targetLeft = isMobile ? (window.innerWidth - exactWidth) / 2 : window.innerWidth - exactWidth - 20;
                            const targetTop = isMobile ? window.innerHeight - popup.getBoundingClientRect().height - 10 : window.innerHeight / 2;

                            gsap.to(popup, {
                                left: targetLeft,
                                top: targetTop,
                                duration: 0.6, // Fast glide
                                ease: "power2.inOut",
                                onComplete: () => {
                                    // Convert to button ONLY on reaching the side
                                    popupState = 'fixed';
                                    popup.classList.add('fixed-sidebar-btn');
                                    popup.style.left = '';
                                    popup.style.top = '';
                                    popup.style.transform = '';
                                    
                                    if (isMobile) {
                                        const actions = popup.querySelector('.banner-actions');
                                        if (actions) {
                                            actions.style.opacity = '1';
                                            actions.style.width = '145px';
                                        }
                                    } else {
                                        const pcClose = popup.querySelector('.desktop-close-btn');
                                        if (pcClose) pcClose.style.display = 'flex';
                                    }
                                }
                            });
                        }, 50);
                    }
                }, 4000);
            }
        }, 1500);
    } catch (e) {
        console.error("ROBOT: Animation failed to start:", e);

        // Trigger Preloader Removal on error to ensure user can still see the site
        if (typeof window.hideRLabzPreloader === 'function') {
            window.hideRLabzPreloader();
        }
    }
});
