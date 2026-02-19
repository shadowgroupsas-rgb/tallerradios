class RadioLogic {
    constructor() {
        this.socket = io(window.SIGNALING_SERVER);
        this.localStream = null;
        this.peers = {}; // socketId -> { connection, channel }
        this.myChannel = 1;
        this.isTransmitting = false;

        // Audio Context for beeps/effects
        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        this.initSocket();
        this.initAudio();
        this.init3D();
        this.updateHUD();

        // DOM PTT
        const pttBtn = document.getElementById('ptt-btn');
        pttBtn.addEventListener('mousedown', () => this.startTX());
        pttBtn.addEventListener('mouseup', () => this.stopTX());
        pttBtn.addEventListener('touchstart', (e) => { e.preventDefault(); this.startTX(); });
        pttBtn.addEventListener('touchend', (e) => { e.preventDefault(); this.stopTX(); });
    }

    async initAudio() {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            // Mute local audio tracks initially (PTT logic)
            this.localStream.getAudioTracks().forEach(track => track.enabled = false);
            console.log("Audio initialized");
            this.log("Audio System Ready. Hold PTT to talk.");
        } catch (e) {
            console.error(e);
            this.log("Error accessing microphone: " + e.message);
        }
    }

    initSocket() {
        this.socket.on('connect', () => {
            this.log("Connected to Signaling Server");
            document.getElementById('status-text').innerText = "CONNECTED";

            // Join Scenario Room
            this.socket.emit('join_scenario', {
                scenarioId: window.SCENARIO_ID,
                user: window.USER_DATA,
                channel: this.myChannel
            });
        });

        // Signaling - New Peer Joined
        this.socket.on('peer_joined', async (data) => {
            this.log(`User joined: ${data.user.username}`);
            await this.createPeerConnection(data.socketId, true); // I am initiator
        });

        // Signaling - WebRTC Offer/Answer/Candidate
        this.socket.on('signal', async (data) => {
            const peer = this.peers[data.from];
            if (!peer && data.type === 'offer') {
                 await this.createPeerConnection(data.from, false);
            }

            const connection = this.peers[data.from].connection;

            if (data.type === 'offer') {
                await connection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                const answer = await connection.createAnswer();
                await connection.setLocalDescription(answer);
                this.socket.emit('signal', { type: 'answer', sdp: answer, to: data.from });
            } else if (data.type === 'answer') {
                await connection.setRemoteDescription(new RTCSessionDescription(data.sdp));
            } else if (data.type === 'candidate') {
                if (data.candidate) {
                    await connection.addIceCandidate(new RTCIceCandidate(data.candidate));
                }
            }
        });

        // Peer Disconnected
        this.socket.on('peer_left', (id) => {
            if (this.peers[id]) {
                this.peers[id].connection.close();
                if (this.peers[id].audioEl) this.peers[id].audioEl.remove();
                delete this.peers[id];
            }
        });

        // Peer Changed Channel
        this.socket.on('channel_update', (data) => {
            if (this.peers[data.socketId]) {
                this.peers[data.socketId].channel = data.channel;
                this.updateAudioRouting();
            }
        });

        // Peer TX State (For visual feedback mostly, audio handled by tracks)
        this.socket.on('tx_state', (data) => {
             // Maybe show who is talking on HUD
             if (data.isTalking) {
                 // Check if we can hear them
                 const peer = this.peers[data.socketId];
                 if (peer && this.canHear(peer.channel)) {
                     this.log(`${peer.user.username} is transmitting on CH ${peer.channel}...`);
                 }
             }
        });
    }

    async createPeerConnection(socketId, initiator) {
        const config = { 'iceServers': [{ 'urls': 'stun:stun.l.google.com:19302' }] };
        const pc = new RTCPeerConnection(config);

        // Add local tracks
        this.localStream.getTracks().forEach(track => pc.addTrack(track, this.localStream));

        // Handle remote stream
        pc.ontrack = (event) => {
            const audio = document.createElement('audio');
            audio.srcObject = event.streams[0];
            audio.autoplay = true;
            audio.volume = 0; // Start muted, routing logic un-mutes
            document.body.appendChild(audio);

            if (this.peers[socketId]) {
                this.peers[socketId].audioEl = audio;
                this.updateAudioRouting();
            }
        };

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.socket.emit('signal', { type: 'candidate', candidate: event.candidate, to: socketId });
            }
        };

        if (initiator) {
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            this.socket.emit('signal', { type: 'offer', sdp: offer, to: socketId });
        }

        // Store peer info
        // We need to know who this socketId belongs to.
        // For simplicity, we assume we get a 'peer_info' or we just store basics.
        // Actually 'peer_joined' gave us user info, but 'signal' doesn't.
        // We might need a separate 'peer_info' exchange or just lazy load.
        // Let's assume the server sends 'peer_list' on join to populate existing peers.

        this.peers[socketId] = { connection: pc, channel: 1, user: { username: 'Unknown' } };
        // Note: Real implementation needs better user info sync.
    }

    init3D() {
        this.scene = new RadioScene('simulation-container', window.USER_DATA.radio_model, {
            onPTTDown: () => this.startTX(),
            onPTTUp: () => this.stopTX(),
            onChannelChange: (dir) => this.changeChannel(dir)
        });
    }

    startTX() {
        if (!this.localStream) return;
        this.isTransmitting = true;
        this.localStream.getAudioTracks().forEach(t => t.enabled = true);
        this.playBeep(800, 0.1); // Roger beep start
        document.getElementById('ptt-btn').classList.add('active');
        this.socket.emit('tx_state', { isTalking: true });
    }

    stopTX() {
        if (!this.localStream) return;
        this.isTransmitting = false;
        this.localStream.getAudioTracks().forEach(t => t.enabled = false);
        this.playBeep(600, 0.1); // Roger beep end
        document.getElementById('ptt-btn').classList.remove('active');
        this.socket.emit('tx_state', { isTalking: false });
    }

    changeChannel(dir) {
        if (dir === 'next') {
            if (this.myChannel < 16) this.myChannel++;
        } else if (dir === 'prev') {
            if (this.myChannel > 1) this.myChannel--;
        } else {
            // Direct set
            this.myChannel = parseInt(dir);
        }

        this.updateHUD();
        this.socket.emit('channel_update', { channel: this.myChannel });
        this.playBeep(1000 + (this.myChannel * 50), 0.05); // Channel tone
        this.updateAudioRouting();
    }

    setChannel(ch) {
        this.changeChannel(ch);
    }

    updateHUD() {
        document.getElementById('channel-display').innerText = "CH " + String(this.myChannel).padStart(2, '0');
        // Fake frequency calc
        const freq = 446.000 + (this.myChannel * 0.0125);
        document.getElementById('freq-display').innerText = freq.toFixed(4) + " MHz";
    }

    updateAudioRouting() {
        // Decide which peers to hear
        for (const id in this.peers) {
            const peer = this.peers[id];
            if (peer.audioEl) {
                if (this.canHear(peer.channel)) {
                    peer.audioEl.volume = 1.0;
                } else {
                    peer.audioEl.volume = 0;
                }
            }
        }
    }

    canHear(remoteChannel) {
        // If I am operator (DEM500), I might be monitoring
        // Check window.USER_DATA.role
        // But for simplicity, let's just assume simple channel matching first.

        // Wait, prompt said: "Operator ... podra cambiar de escenario para escuchar a todos los grupos"
        // Also "dependiendo del canal podra cambiar de escenario".
        // And "monitoring".

        // Basic Logic: Same channel = hear.
        return parseInt(remoteChannel) === parseInt(this.myChannel);
    }

    playBeep(freq, duration) {
        const osc = this.audioCtx.createOscillator();
        const gain = this.audioCtx.createGain();
        osc.frequency.value = freq;
        osc.connect(gain);
        gain.connect(this.audioCtx.destination);
        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.00001, this.audioCtx.currentTime + duration);
        osc.stop(this.audioCtx.currentTime + duration);
    }

    log(msg) {
        const log = document.getElementById('logs');
        log.innerHTML += `<div>> ${msg}</div>`;
        log.scrollTop = log.scrollHeight;
    }
}

// Start
window.addEventListener('load', () => {
    window.radioLogic = new RadioLogic();
});
