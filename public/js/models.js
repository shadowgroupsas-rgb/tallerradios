class RadioScene {
    constructor(containerId, modelType, callbacks) {
        this.container = document.getElementById(containerId);
        this.modelType = modelType; // 'DEP450' or 'DEM500'
        this.callbacks = callbacks; // { onPTTDown, onPTTUp, onChannelChange }

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });

        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.container.appendChild(this.renderer.domElement);

        // Lighting
        const light = new THREE.DirectionalLight(0xffffff, 1);
        light.position.set(5, 5, 5);
        this.scene.add(light);
        this.scene.add(new THREE.AmbientLight(0x404040)); // soft white light

        // Raycaster
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        this.interactables = [];

        this.initModel();
        this.initEvents();

        this.camera.position.z = 5;
        this.animate();
    }

    initModel() {
        if (this.modelType === 'DEP450') {
            this.buildDEP450();
        } else {
            this.buildDEM500();
        }
    }

    buildDEP450() {
        // Body
        const bodyGeo = new THREE.BoxGeometry(1.5, 4, 0.8);
        const bodyMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.5 });
        this.body = new THREE.Mesh(bodyGeo, bodyMat);
        this.scene.add(this.body);

        // Antenna
        const antGeo = new THREE.CylinderGeometry(0.1, 0.15, 3, 16);
        const antMat = new THREE.MeshStandardMaterial({ color: 0x000000 });
        const antenna = new THREE.Mesh(antGeo, antMat);
        antenna.position.set(-0.5, 3.5, 0);
        this.body.add(antenna);

        // Channel Knob (Interactive)
        const knobGeo = new THREE.CylinderGeometry(0.3, 0.3, 0.5, 16);
        const knobMat = new THREE.MeshStandardMaterial({ color: 0x333333 });
        this.channelKnob = new THREE.Mesh(knobGeo, knobMat);
        this.channelKnob.position.set(0.3, 2.25, 0);
        this.channelKnob.rotation.x = Math.PI / 2; // Actually, default cylinder is Y-up. Let's keep it Y-up.
        this.channelKnob.rotation.x = 0;
        this.channelKnob.name = 'channel_knob';
        this.body.add(this.channelKnob);
        this.interactables.push(this.channelKnob);

        // Volume Knob (Visual)
        const volKnob = new THREE.Mesh(knobGeo, knobMat);
        volKnob.position.set(-0.2, 2.25, 0);
        this.body.add(volKnob);

        // PTT Button (Side)
        const pttGeo = new THREE.BoxGeometry(0.2, 1, 0.4);
        const pttMat = new THREE.MeshStandardMaterial({ color: 0x333333 });
        this.pttBtn = new THREE.Mesh(pttGeo, pttMat);
        this.pttBtn.position.set(-0.85, 0.5, 0);
        this.pttBtn.name = 'ptt_btn';
        this.body.add(this.pttBtn);
        this.interactables.push(this.pttBtn);

        // Label
        // (Skipping text texture for simplicity, using color/shape to denote)
    }

    buildDEM500() {
        // Base Station
        const bodyGeo = new THREE.BoxGeometry(4, 1.5, 3);
        const bodyMat = new THREE.MeshStandardMaterial({ color: 0x222222 });
        this.body = new THREE.Mesh(bodyGeo, bodyMat);
        this.scene.add(this.body);

        // Screen Area
        const screenGeo = new THREE.PlaneGeometry(1.5, 0.8);
        const screenMat = new THREE.MeshBasicMaterial({ color: 0x00ff00 }); // Green screen
        const screen = new THREE.Mesh(screenGeo, screenMat);
        screen.position.set(0, 0, 1.51);
        this.body.add(screen);

        // Channel Up Button
        const btnGeo = new THREE.BoxGeometry(0.3, 0.3, 0.1);
        const btnMat = new THREE.MeshStandardMaterial({ color: 0x555555 });
        this.chUp = new THREE.Mesh(btnGeo, btnMat);
        this.chUp.position.set(1.2, 0.3, 1.5);
        this.chUp.name = 'ch_up';
        this.body.add(this.chUp);
        this.interactables.push(this.chUp);

        // Channel Down Button
        this.chDown = new THREE.Mesh(btnGeo, btnMat);
        this.chDown.position.set(1.2, -0.3, 1.5);
        this.chDown.name = 'ch_down';
        this.body.add(this.chDown);
        this.interactables.push(this.chDown);

        // Mic (Handheld part) - Floating nearby
        const micGeo = new THREE.BoxGeometry(0.8, 1.2, 0.5);
        this.mic = new THREE.Mesh(micGeo, new THREE.MeshStandardMaterial({ color: 0x111111 }));
        this.mic.position.set(2.5, -0.5, 1);
        this.mic.name = 'ptt_mic'; // Clicking the mic is PTT
        this.scene.add(this.mic);
        this.interactables.push(this.mic);
    }

    initEvents() {
        window.addEventListener('resize', () => {
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });

        // Mouse Down (Click/PTT)
        this.container.addEventListener('mousedown', (e) => this.onMouseDown(e));
        this.container.addEventListener('mouseup', (e) => this.onMouseUp(e));

        // Touch support
        this.container.addEventListener('touchstart', (e) => {
            e.preventDefault(); // Prevent scrolling
            const touch = e.touches[0];
            this.onMouseDown({ clientX: touch.clientX, clientY: touch.clientY });
        }, { passive: false });

        this.container.addEventListener('touchend', (e) => {
             this.onMouseUp(e);
        });
    }

    onMouseDown(event) {
        this.mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        this.mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        const intersects = this.raycaster.intersectObjects(this.interactables);

        if (intersects.length > 0) {
            const obj = intersects[0].object;

            if (obj.name === 'ptt_btn' || obj.name === 'ptt_mic') {
                obj.material.color.set(0xff0000); // Visual feedback
                if(this.callbacks.onPTTDown) this.callbacks.onPTTDown();
            } else if (obj.name === 'channel_knob') {
                // Rotate knob
                obj.rotation.y += 0.5;
                if(this.callbacks.onChannelChange) this.callbacks.onChannelChange('next');
            } else if (obj.name === 'ch_up') {
                 if(this.callbacks.onChannelChange) this.callbacks.onChannelChange('next');
            } else if (obj.name === 'ch_down') {
                 if(this.callbacks.onChannelChange) this.callbacks.onChannelChange('prev');
            }
        }
    }

    onMouseUp(event) {
        // Reset PTT visual
        if (this.pttBtn) this.pttBtn.material.color.set(0x333333);
        if (this.mic) this.mic.material.color.set(0x111111);

        if(this.callbacks.onPTTUp) this.callbacks.onPTTUp();
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Idle animation (slow rotation)
        if (this.body) {
            this.body.rotation.y += 0.002;
        }

        this.renderer.render(this.scene, this.camera);
    }
}
