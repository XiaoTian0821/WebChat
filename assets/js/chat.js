// WebConnect - Chat Module (Original Design)
class ChatApp {
    constructor() {
        this.currentChatId = null;
        this.currentChatType = null;
        this.pollInterval = null;
        this.typingTimeout = null;
        this.isTyping = false;
        this.replyTo = null;
        this.messages = [];
        this.unreadCount = 0;
        this.init();
    }

    init() {
        console.log('[ChatApp] Initializing...');
        this.cacheElements();
        this.bindEvents();
        this.loadConversations();
        this.startPolling();
    }

    cacheElements() {
        this.els = {
            chatSidebar: document.getElementById('chat-sidebar'),
            chatMain: document.getElementById('chat-main'),
            chatBackBtn: document.getElementById('chat-back-btn'),
            messagesArea: document.getElementById('messages-area'),
            messageInput: document.getElementById('message-input'),
            sendBtn: document.getElementById('send-btn'),
            attachBtn: document.getElementById('attach-btn'),
            attachInput: document.getElementById('attach-input'),
            voiceBtn: document.getElementById('voice-btn'),
            replyBar: document.getElementById('reply-bar'),
            replyText: document.getElementById('reply-text'),
            replyCancel: document.getElementById('reply-cancel'),
            conversationList: document.getElementById('conversation-list'),
            groupList: document.getElementById('group-list'),
            chatHeaderName: document.getElementById('chat-header-name'),
            chatHeaderStatus: document.getElementById('chat-header-status'),
            chatHeaderAvatar: document.getElementById('chat-header-avatar'),
            emojiPickerDropdown: document.getElementById('emoji-picker-dropdown'),
            emojiBtn: document.getElementById('emoji-btn'),
            emojiPickerWrapper: document.getElementById('emoji-picker-wrapper'),
            chatInfoPanel: document.getElementById('chat-info-panel'),
            voiceRecorder: document.getElementById('voice-recorder'),
            voiceTimer: document.getElementById('voice-timer'),
            voiceCancel: document.getElementById('voice-cancel'),
            voiceSend: document.getElementById('voice-send'),
            sidebarTabs: document.querySelectorAll('.sidebar-tabs .nav-link'),
            searchInput: document.getElementById('search-conversations'),
            toggleInfoBtn: document.getElementById('toggle-info'),
            voiceCallBtn: document.getElementById('voice-call-btn'),
            videoCallBtn: document.getElementById('video-call-btn'),
        };
        console.log('[ChatApp] Elements cached:', Object.keys(this.els));
    }

    bindEvents() {
        const els = this.els;
        console.log('[ChatApp] Binding events...');
        
        // Send message
        if (els.sendBtn) els.sendBtn.addEventListener('click', () => this.sendMessage());
        
        // Message input
        if (els.messageInput) {
            els.messageInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            els.messageInput.addEventListener('input', () => this.handleTyping());
        }
        
        // Attach file
        if (els.attachBtn) els.attachBtn.addEventListener('click', () => {
            console.log('[ChatApp] Attach button clicked');
            els.attachInput?.click();
        });
        if (els.attachInput) els.attachInput.addEventListener('change', e => this.handleFileUpload(e));
        
        // Reply
        if (els.replyCancel) els.replyCancel.addEventListener('click', () => this.cancelReply());
        
        // Voice recording
        if (els.voiceBtn) els.voiceBtn.addEventListener('click', () => this.toggleVoiceRecording());
        if (els.voiceCancel) els.voiceCancel.addEventListener('click', () => this.cancelVoiceRecording());
        if (els.voiceSend) els.voiceSend.addEventListener('click', () => this.sendVoiceRecording());
        
        // Emoji picker
        if (els.emojiBtn) {
            els.emojiBtn.addEventListener('click', e => {
                e.stopPropagation();
                console.log('[ChatApp] Emoji button clicked');
                els.emojiPickerDropdown?.classList.toggle('d-none');
            });
        }
        if (els.emojiPickerWrapper) {
            els.emojiPickerWrapper.addEventListener('click', e => {
                if (e.target.classList.contains('emoji-btn')) {
                    const input = els.messageInput;
                    if (input) { input.value += e.target.dataset.emoji; input.focus(); }
                    els.emojiPickerDropdown?.classList.add('d-none');
                }
            });
        }
        
        // Back button
        if (els.chatBackBtn) els.chatBackBtn.addEventListener('click', () => this.showSidebar());
        
        // Info panel toggle
        if (els.toggleInfoBtn) els.toggleInfoBtn.addEventListener('click', () => this.toggleInfoPanel());
        
        // Voice/Video call buttons
        if (els.voiceCallBtn) els.voiceCallBtn.addEventListener('click', () => this.startVoiceCall());
        if (els.videoCallBtn) els.videoCallBtn.addEventListener('click', () => this.startVideoCall());
        
        // Search conversations
        if (els.searchInput) {
            let debounceTimer;
            els.searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => this.filterConversations(els.searchInput.value), 300);
            });
        }
        
        // Sidebar tabs
        if (els.sidebarTabs && els.sidebarTabs.length > 0) {
            els.sidebarTabs.forEach(tab => {
                tab.addEventListener('click', e => {
                    e.preventDefault();
                    els.sidebarTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    const tabName = tab.dataset.tab;
                    if (tabName === 'private') {
                        this.els.conversationList?.classList.remove('d-none');
                        this.els.groupList?.classList.add('d-none');
                        this.loadConversations();
                    } else if (tabName === 'groups') {
                        this.els.conversationList?.classList.add('d-none');
                        this.els.groupList?.classList.remove('d-none');
                        this.loadGroupConversations();
                    }
                });
            });
        }
        
        // Close emoji picker when clicking outside
        document.addEventListener('click', e => {
            if (els.emojiPickerDropdown && !els.emojiPickerWrapper?.contains(e.target)) {
                els.emojiPickerDropdown.classList.add('d-none');
            }
        });
        
        console.log('[ChatApp] Events bound');
    }

    filterConversations(query) {
        if (!this.els.conversationList) return;
        const items = this.els.conversationList.querySelectorAll('.conversation-item');
        const q = query.toLowerCase();
        items.forEach(item => {
            const name = item.querySelector('.conv-name')?.textContent.toLowerCase() || '';
            item.style.display = name.includes(q) ? '' : 'none';
        });
    }

    async loadGroupConversations() {
        if (!this.els.groupList) return;
        this.els.groupList.innerHTML = '<div class="loading-spinner p-3"><i class="fas fa-spinner fa-spin"></i></div>';
        try {
            const res = await fetch('/api/chat.php?action=group_conversations');
            const data = await res.json();
            if (this.els.groupList) {
                if (data.success && data.data.length > 0) {
                    this.els.groupList.innerHTML = data.data.map(g => this.renderGroupItem(g)).join('');
                } else {
                    this.els.groupList.innerHTML = '<div class="p-3 text-center text-muted small">No groups yet</div>';
                }
            }
        } catch (e) {
            console.error('Load groups error:', e);
            if (this.els.groupList) {
                this.els.groupList.innerHTML = '<div class="p-3 text-center text-danger small">Error loading groups</div>';
            }
        }
    }

    renderGroupItem(g) {
        const time = g.last_msg_time ? formatTime(g.last_msg_time) : '';
        const preview = g.last_msg ? g.last_msg.substring(0, 40) + (g.last_msg.length > 40 ? '...' : '') : 'No messages yet';
        return `
            <a href="#" class="conversation-item group-item" data-id="${g.id}" data-type="group">
                <div class="conv-header">
                    <div class="me-2">${g.avatar
                        ? `<img src="/api/media.php?file=${encodeURIComponent(g.avatar)}&type=group" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" class="me-2">`
                        : `<div class="avatar avatar-default" style="width:40px;height:40px;border-radius:50%;">${g.name[0]}</div>`}</div>
                    <div class="conv-name flex-grow-1">${this.esc(g.name)}</div>
                </div>
                <div class="conv-preview small text-muted">${this.esc(preview)}</div>
                ${time ? `<div class="conv-time small text-muted">${time}</div>` : ''}
            </a>
        `;
    }

    async loadConversations() {
        if (!this.els.conversationList) return;
        this.els.conversationList.innerHTML = '<div class="loading-spinner p-3"><i class="fas fa-spinner fa-spin"></i></div>';
        try {
            const res = await fetch('/api/chat.php?action=conversations');
            const data = await res.json();
            console.log('[ChatApp] Conversations response:', data);
            if (this.els.conversationList) {
                if (data.success && data.data.length > 0) {
                    this.els.conversationList.innerHTML = data.data.map(c => this.renderConversationItem(c)).join('');
                    this.els.conversationList.querySelectorAll('.conversation-item').forEach(el => {
                        el.addEventListener('click', e => {
                            e.preventDefault();
                            const id = parseInt(el.dataset.id);
                            console.log('[ChatApp] Clicked conversation with id:', id);
                            this.selectConversation(id);
                        });
                    });
                } else {
                    this.els.conversationList.innerHTML = '<div class="p-3 text-center text-muted small">No conversations yet</div>';
                }
            }
        } catch (e) {
            console.error('Load conversations error:', e);
            if (this.els.conversationList) {
                this.els.conversationList.innerHTML = '<div class="p-3 text-center text-danger small">Error loading conversations</div>';
            }
        }
    }

    renderConversationItem(c) {
        const time = c.last_msg_time ? formatTime(c.last_msg_time) : '';
        const preview = c.last_msg ? c.last_msg.substring(0, 40) + (c.last_msg.length > 40 ? '...' : '') : 'No messages yet';
        const isActive = this.currentChatId == c.id && this.currentChatType === 'private';
        return `
            <a href="#" class="conversation-item ${isActive ? 'active' : ''}" data-id="${c.id}" data-type="private">
                <div class="conv-header">
                    <div class="me-2">${this.getAvatarHTML(c)}</div>
                    <div class="conv-name flex-grow-1">${this.esc(c.username)}</div>
                    ${c.unread > 0 ? `<span class="badge bg-danger rounded-pill">${c.unread}</span>` : ''}
                </div>
                <div class="conv-preview small text-muted">${this.esc(preview)}</div>
                ${time ? `<div class="conv-time small text-muted">${time}</div>` : ''}
            </a>
        `;
    }

    getAvatarHTML(c) {
        if (c.avatar) {
            return `<img src="/api/media.php?file=${encodeURIComponent(c.avatar)}&type=avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" class="me-2">`;
        }
        const initial = c.username ? c.username.charAt(0).toUpperCase() : '?';
        return `<div class="avatar avatar-default" style="width:40px;height:40px;border-radius:50%;">${initial}</div>`;
    }

    async selectConversation(id) {
        console.log('[ChatApp] Selecting conversation:', id, 'currentChatId:', this.currentChatId);
        this.currentChatId = id;
        this.currentChatType = 'private';
        
        // Update UI - do NOT hide sidebar
        document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
        const el = document.querySelector(`.conversation-item[data-id="${id}"]`);
        if (el) el.classList.add('active');
        
        // Show chat main area
        if (this.els.chatMain) {
            this.els.chatMain.classList.remove('chat-hidden');
        }
        
        // Load user info
        await this.loadUserInfo(id);
        
        // Load messages
        await this.loadMessages();
    }

    async loadUserInfo(userId) {
        try {
            const res = await fetch('/api/chat.php?action=user_info&id=' + userId);
            const data = await res.json();
            if (data.success && data.data) {
                const user = data.data;
                if (this.els.chatHeaderName) {
                    this.els.chatHeaderName.textContent = user.username;
                }
                if (this.els.chatHeaderStatus) {
                    this.els.chatHeaderStatus.textContent = user.status || 'Offline';
                }
                if (this.els.chatHeaderAvatar) {
                    this.els.chatHeaderAvatar.innerHTML = this.getAvatarHTML(user);
                }
            }
        } catch (e) {
            console.error('Load user info error:', e);
        }
    }

    async loadMessages() {
        if (!this.currentChatId) {
            console.log('[ChatApp] No currentChatId set, skipping loadMessages');
            return;
        }
        console.log('[ChatApp] Loading messages for chat_id:', this.currentChatId);
        try {
            const res = await fetch('/api/messages.php?action=get&type=private&chat_id=' + this.currentChatId);
            const data = await res.json();
            if (data.success && data.data) {
                this.messages = data.data;
                this.renderMessages();
            }
        } catch (e) {
            console.error('Load messages error:', e);
        }
    }

    renderMessages() {
        if (!this.els.messagesArea) return;
        if (this.messages.length === 0) {
            this.els.messagesArea.innerHTML = '<div class="empty-state"><i class="fas fa-comments fa-3x mb-3"></i><p>No messages yet</p></div>';
            return;
        }
        
        this.els.messagesArea.innerHTML = this.messages.map(m => this.renderMessage(m)).join('');
        this.scrollToBottom();
    }

    renderMessage(m) {
        const isMine = m.sender_id == window.currentUserId;
        const time = m.created_at ? new Date(m.created_at).toLocaleTimeString() : '';
        const avatar = isMine ? '' : `<img src="/api/media.php?file=${encodeURIComponent(m.avatar || '')}&type=avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" class="me-2">`;
        
        let content = '';
        if (m.message_type === 'image') {
            content = `<img src="/api/media.php?file=${encodeURIComponent(m.body)}&type=message" class="img-fluid rounded" style="max-width:300px;">`;
        } else if (m.message_type === 'voice') {
            content = `<audio controls src="/api/media.php?file=${encodeURIComponent(m.body)}&type=voice"></audio>`;
        } else {
            content = this.esc(m.body || '');
        }
        
        return `
            <div class="message ${isMine ? 'message-mine' : 'message-other'}" data-id="${m.id}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-bubble">${content}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
    }

    esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    scrollToBottom() {
        if (this.els.messagesArea) {
            this.els.messagesArea.scrollTop = this.els.messagesArea.scrollHeight;
        }
    }

    async sendMessage() {
        const input = this.els.messageInput;
        const text = input?.value?.trim();
        console.log('[ChatApp] sendMessage - text:', text, 'currentChatId:', this.currentChatId);
        if (!text || !this.currentChatId) return;
        const replyTo = this.replyTo;
        try {
            const res = await fetch('/api/messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send',
                    type: 'private',
                    chat_id: this.currentChatId,
                    message: text,
                    message_type: 'text',
                    reply_to: replyTo,
                })
            });
            const data = await res.json();
            console.log('[ChatApp] Send message response:', data);
            if (data.success) {
                input.value = '';
                this.replyTo = null;
                this.hideReplyBar();
                await this.loadMessages();
            } else {
                console.error('[ChatApp] Send message failed:', data.message);
                alert('Failed to send message: ' + (data.message || 'Unknown error'));
            }
        } catch (e) { console.error('[ChatApp] Send message error:', e); }
    }

    handleTyping() {
        clearTimeout(this.typingTimeout);
        if (!this.isTyping && this.currentChatId) {
            this.isTyping = true;
            fetch('/api/messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'typing', type: 'private', chat_id: this.currentChatId })
            });
        }
        this.typingTimeout = setTimeout(() => { this.isTyping = false; }, 3000);
    }

    async handleFileUpload(e) {
        const file = e.target.files[0];
        if (!file || !this.currentChatId) return;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('chat_id', this.currentChatId);
        formData.append('type', 'private');
        formData.append('action', 'send');
        formData.append('action', 'send');
        try {
            const res = await fetch('/api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            console.log('[ChatApp] File upload response:', data);
            if (data.success) await this.loadMessages();
            else console.error('[ChatApp] Upload failed:', data.message);
        } catch (e) { console.error('[ChatApp] Upload error:', e); }
        e.target.value = '';
    }

    toggleVoiceRecording() {
        const recorder = this.els.voiceRecorder;
        if (!recorder) return;
        recorder.classList.toggle('d-none');
        if (!recorder.classList.contains('d-none')) {
            this.startVoiceRecording();
        } else {
            this.cancelVoiceRecording();
        }
    }

    async startVoiceRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];
            this.mediaRecorder.ondataavailable = e => { this.audioChunks.push(e.data); };
            this.mediaRecorder.start();
            this.startVoiceTimer();
        } catch (e) {
            console.error('Microphone access denied:', e);
            alert('Microphone access denied. Please allow microphone access.');
        }
    }

    startVoiceTimer() {
        this.voiceStartTime = Date.now();
        this.voiceTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.voiceStartTime) / 1000);
            if (this.els.voiceTimer) {
                this.els.voiceTimer.textContent = `${Math.floor(elapsed / 60)}:${(elapsed % 60).toString().padStart(2, '0')}`;
            }
        }, 1000);
    }

    cancelVoiceRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }
        clearInterval(this.voiceTimerInterval);
        if (this.els.voiceRecorder) this.els.voiceRecorder.classList.add('d-none');
    }

    async sendVoiceRecording() {
        if (!this.mediaRecorder || this.mediaRecorder.state === 'inactive') return;
        this.cancelVoiceRecording();
        const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
        const formData = new FormData();
        formData.append('file', blob, 'voice.webm');
        formData.append('chat_id', this.currentChatId);
        formData.append('type', 'private');
        formData.append('action', 'send');
        try {
            const res = await fetch('/api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) await this.loadMessages();
        } catch (e) { console.error(e); }
    }

    startReply(m) {
        this.replyTo = m.id;
        const bar = this.els.replyBar;
        if (bar) {
            bar.classList.remove('d-none');
            const text = this.els.replyText;
            if (text) text.textContent = m.body ? m.body.substring(0, 30) + '...' : '[image]';
        }
    }

    cancelReply() {
        this.replyTo = null;
        if (this.els.replyBar) this.els.replyBar.classList.add('d-none');
    }

    hideReplyBar() {
        if (this.els.replyBar) this.els.replyBar.classList.add('d-none');
    }

    showSidebar() {
        // Do NOT hide sidebar - keep it visible
        if (this.els.chatMain) this.els.chatMain.classList.add('chat-hidden');
    }

    toggleInfoPanel() {
        if (this.els.chatInfoPanel) {
            this.els.chatInfoPanel.classList.toggle('d-none');
        }
    }

    startVoiceCall() {
        console.log('[ChatApp] startVoiceCall called, currentChatId:', this.currentChatId);
        const targetId = this.currentChatId;
        if (!targetId) {
            alert('No recipient selected');
            return;
        }
        if (window.calls) {
            window.calls.startVoiceCall(targetId);
        } else {
            console.warn('[ChatApp] calls.js not loaded');
            alert('Calling feature is not available');
        }
    }

    startVideoCall() {
        console.log('[ChatApp] startVideoCall called, currentChatId:', this.currentChatId);
        const targetId = this.currentChatId;
        if (!targetId) {
            alert('No recipient selected');
            return;
        }
        if (window.calls) {
            window.calls.startVideoCall(targetId);
        } else {
            console.warn('[ChatApp] calls.js not loaded');
            alert('Calling feature is not available');
        }
    }

    esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    formatSize(bytes) {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
    }

    formatDuration(sec) {
        const m = Math.floor(sec / 60);
        const s = Math.floor(sec % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    startPolling() {
        this.pollInterval = setInterval(async () => {
            if (this.currentChatId) {
                await this.loadMessages();
                await this.loadUserInfo(this.currentChatId);
            }
        }, 3000);
    }
}

// Initialize chat on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('[DOM] Ready, initializing ChatApp...');
    window.chatApp = new ChatApp();
    console.log('[DOM] ChatApp initialized');
});
