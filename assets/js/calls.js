// WebConnect - Calls Module (WebRTC) - Fixed
class CallApp {
    constructor() {
        this.pc = null;
        this.localStream = null;
        this.callId = null;
        this.callType = null;
        this.isCaller = false;
        this.isMuted = false;
        this.isCameraOff = false;
        this.pollInterval = null;
        this.callPeerId = null;
        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.checkForIncomingCalls();
    }

    async checkForIncomingCalls() {
        // Check URL for call_id parameter
        const urlParams = new URLSearchParams(window.location.search);
        const callId = urlParams.get('call_id');
        if (callId) {
            console.log('[CallApp] Found call_id in URL:', callId);
            await this.showIncomingCallFromUrl(callId);
            return;
        }
        
        // Poll for incoming calls
        setInterval(async () => {
            try {
                const res = await fetch('/api/calls.php?action=get_incoming');
                const data = await res.json();
                if (data.success && data.data) {
                    const call = data.data;
                    // Only show if call is less than 5 minutes old
                    const callTime = new Date(call.created_at);
                    const now = new Date();
                    if ((now - callTime) < 300000) {
                        this.showIncomingCall(call);
                    }
                }
            } catch (e) {
                console.error('[CallApp] Check incoming calls error:', e);
            }
        }, 5000);
    }

    async showIncomingCallFromUrl(callId) {
        try {
            const res = await fetch('/api/calls.php?action=get_status&call_id=' + callId);
            const data = await res.json();
            if (data.success && data.data) {
                const call = data.data;
                if (call.status === 'ringing') {
                    this.showIncomingCall(call);
                }
            }
        } catch (e) {
            console.error('[CallApp] Show incoming call from URL error:', e);
        }
    }

    cacheElements() {
        this.els = {
            overlay: document.getElementById('call-overlay'),
            remoteVideo: document.getElementById('remote-video'),
            localVideo: document.getElementById('local-video'),
            callerName: document.getElementById('caller-name'),
            callStatus: document.getElementById('call-status'),
            muteBtn: document.getElementById('mute-btn'),
            cameraBtn: document.getElementById('camera-btn'),
            endCallBtn: document.getElementById('end-call-btn'),
            acceptCallBtn: document.getElementById('accept-call-btn'),
            rejectCallBtn: document.getElementById('reject-call-btn'),
            callVideoContainer: document.getElementById('call-video-container'),
            incomingCallControls: document.getElementById('incoming-call-controls'),
        };
    }

    bindEvents() {
        const els = this.els;
        if (els.muteBtn) els.muteBtn.addEventListener('click', () => this.toggleMute());
        if (els.cameraBtn) els.cameraBtn.addEventListener('click', () => this.toggleCamera());
        if (els.endCallBtn) els.endCallBtn.addEventListener('click', () => this.endCall());
        if (els.acceptCallBtn) els.acceptCallBtn.addEventListener('click', () => this.acceptCall());
        if (els.rejectCallBtn) els.rejectCallBtn.addEventListener('click', () => this.rejectCall());
    }

    startVoiceCall(targetId) {
        console.log('[CallApp] startVoiceCall', targetId);
        if (!targetId) {
            alert('No recipient selected');
            return;
        }
        this.startCall(targetId, 'voice');
    }

    startVideoCall(targetId) {
        console.log('[CallApp] startVideoCall', targetId);
        if (!targetId) {
            alert('No recipient selected');
            return;
        }
        this.startCall(targetId, 'video');
    }

    async startCall(targetId, type) {
        this.callType = type;
        this.isCaller = true;
        this.isMuted = false;
        this.isCameraOff = false;
        
        try {
            const res = await fetch('/api/calls.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', target_id: targetId, call_type: type })
            });
            const data = await res.json();
            console.log('[CallApp] Create call response:', data);
            
            if (!data.success) {
                alert(data.message || 'Failed to start call');
                return;
            }
            
            this.callId = data.data.call_id;
            this.callPeerId = targetId;
            this.showCallUI('Calling...');
            this.startCallPolling();
            await this.setupPeerConnection();
        } catch (e) {
            console.error('[CallApp] startCall error:', e);
            alert('Failed to initiate call: ' + e.message);
        }
    }

    async setupPeerConnection() {
        const rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' }
            ]
        };
        this.pc = new RTCPeerConnection(rtcConfig);
        console.log('[CallApp] PeerConnection created');

        const constraints = this.callType === 'video'
            ? { audio: true, video: { width: 640, height: 480 } }
            : { audio: true, video: false };

        try {
            this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('[CallApp] Got local stream');
            this.localStream.getTracks().forEach(track => this.pc.addTrack(track, this.localStream));
            if (this.els.localVideo) this.els.localVideo.srcObject = this.localStream;
        } catch (e) {
            console.error('[CallApp] Media error:', e);
            alert('Microphone (and camera) access denied. Please allow access to make calls.');
            this.endCall();
            return;
        }

        this.pc.ontrack = e => {
            console.log('[CallApp] Remote track received');
            if (this.els.remoteVideo) {
                this.els.remoteVideo.srcObject = e.streams[0];
            }
        };

        this.pc.onicecandidate = e => {
            if (e.candidate && this.callId) {
                fetch('/api/calls.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'signal', call_id: this.callId, type: 'ice_candidate', data: JSON.stringify(e.candidate) })
                }).catch(err => console.error('[CallApp] ICE candidate error:', err));
            }
        };

        this.pc.onconnectionstatechange = () => {
            console.log('[CallApp] Connection state:', this.pc.connectionState);
            if (this.pc.connectionState === 'connected') {
                this.showCallUI('Connected');
            } else if (this.pc.connectionState === 'failed' || this.pc.connectionState === 'disconnected') {
                this.showCallUI('Connection lost');
            }
        };

        try {
            const offer = await this.pc.createOffer();
            await this.pc.setLocalDescription(offer);
            
            const res = await fetch('/api/calls.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'signal', 
                    call_id: this.callId, 
                    type: 'offer', 
                    data: JSON.stringify(offer.sdp) 
                })
            });
            const data = await res.json();
            console.log('[CallApp] Offer sent, response:', data);
        } catch (e) {
            console.error('[CallApp] Create offer error:', e);
        }
    }

    async acceptCall() {
        if (!this.callId) {
            alert('No incoming call to accept');
            return;
        }
        
        this.isCaller = false;
        this.showCallUI('Ringing...');
        
        try {
            const res = await fetch('/api/calls.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'accept', call_id: this.callId })
            });
            const data = await res.json();
            console.log('[CallApp] Accept response:', data);
            
            if (!data.success) {
                alert(data.message || 'Failed to accept call');
                return;
            }
            
            // Poll for offer until it's available
            let offerData = null;
            for (let i = 0; i < 10; i++) {
                await new Promise(r => setTimeout(r, 500));
                const offerRes = await fetch('/api/calls.php?action=get_offer&call_id=' + this.callId);
                const offerResult = await offerRes.json();
                if (offerResult.success && offerResult.data) {
                    offerData = offerResult.data;
                    break;
                }
            }
            
            if (offerData) {
                await this.setupPeerConnection();
                await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: offerData }));
                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);
                const ansRes = await fetch('/api/calls.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'signal', call_id: this.callId, type: 'answer', data: JSON.stringify(answer.sdp) })
                });
                console.log('[CallApp] Answer sent');
            } else {
                console.error('[CallApp] Offer not found after polling');
            }
        } catch (e) {
            console.error('[CallApp] Accept call error:', e);
            alert('Failed to accept call');
        }
    }

    async rejectCall() {
        if (this.callId) {
            await fetch('/api/calls.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', call_id: this.callId })
            });
        }
        this.endCall();
    }

    async endCall() {
        console.log('[CallApp] Ending call');
        if (this.callId) {
            await fetch('/api/calls.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'end', call_id: this.callId })
            }).catch(e => console.error('[CallApp] End call error:', e));
        }
        
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }
        if (this.pc) {
            this.pc.close();
            this.pc = null;
        }
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        if (this.els.overlay) this.els.overlay.classList.add('d-none');
        this.callId = null;
        this.callPeerId = null;
    }

    toggleMute() {
        this.isMuted = !this.isMuted;
        if (this.localStream) {
            this.localStream.getAudioTracks().forEach(track => track.enabled = !this.isMuted);
        }
        if (this.els.muteBtn) {
            this.els.muteBtn.innerHTML = this.isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>';
        }
    }

    toggleCamera() {
        this.isCameraOff = !this.isCameraOff;
        if (this.localStream) {
            this.localStream.getVideoTracks().forEach(track => track.enabled = !this.isCameraOff);
        }
        if (this.els.cameraBtn) {
            this.els.cameraBtn.innerHTML = this.isCameraOff ? '<i class="fas fa-video-slash"></i>' : '<i class="fas fa-video"></i>';
        }
    }

    showCallUI(status) {
        if (this.els.overlay) this.els.overlay.classList.remove('d-none');
        if (this.els.callStatus) this.els.callStatus.textContent = status;
        if (this.els.callVideoContainer) {
            this.els.callVideoContainer.style.display = this.callType === 'video' ? 'block' : 'none';
        }
        if (this.els.cameraBtn) {
            this.els.cameraBtn.style.display = this.callType === 'video' ? 'inline-flex' : 'none';
        }
    }

    startCallPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(async () => {
            if (!this.callId) return;
            try {
                const res = await fetch('/api/calls.php?action=get_status&call_id=' + this.callId);
                const data = await res.json();
                console.log('[CallApp] Poll status:', data);
                if (data.success) {
                    if (data.data.status === 'accepted') {
                        this.showCallUI('Connected');
                    } else if (data.data.status === 'ended' || data.data.status === 'rejected') {
                        this.endCall();
                    }
                }
            } catch (e) {
                console.error('[CallApp] Poll error:', e);
            }
        }, 1000);
    }
}

// Initialize call app
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('call-overlay')) {
        window.calls = new CallApp();
        console.log('[CallApp] Initialized');
    }
});
