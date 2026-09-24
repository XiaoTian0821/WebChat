// WebConnect - Chat Module
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
                            this.selectConversation(parseInt(el.dataset.id));
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

    async selectConversation(id) {
        this.currentChatId = id;
        this.currentChatType = 'private';
        if (this.els.conversationList) {
            this.els.conversationList.querySelectorAll('.conversation-item').forEach(el => {
                el.classList.toggle('active', el.dataset.id == id);
            });
        }
        this.showChat();
        await this.loadMessages();
        await this.loadChatInfo(id);
    }

    async loadChatInfo(id) {
        try {
            const res = await fetch(`/api/chat.php?action=chat_info&id=${id}`);
            const data = await res.json();
            if (data.success && this.els.chatHeaderName) {
                this.els.chatHeaderName.textContent = data.data.username;
                this.els.chatHeaderStatus.innerHTML = getStatusBadge(data.data.status, data.data.last_seen);
                this.els.chatHeaderAvatar.innerHTML = this.getAvatarHTML(data.data, 40);
            }
        } catch (e) { console.error('Load chat info error:', e); }
    }

    showChat() {
        if (this.els.chatMain) this.els.chatMain.classList.remove('chat-hidden');
    }

    showSidebar() {
        if (this.els.chatMain) this.els.chatMain.classList.add('chat-hidden');
        this.currentChatId = null;
    }

    toggleInfoPanel() {
        const panel = this.els.chatInfoPanel;
        if (panel) panel.classList.toggle('d-none');
    }

    async loadMessages() {
        if (!this.currentChatId) return;
        try {
            const res = await fetch(`/api/messages.php?action=get&type=private&id=${this.currentChatId}`);
            const data = await res.json();
            if (data.success && this.els.messagesArea) {
                this.els.messagesArea.innerHTML = data.data.map(m => this.renderMessage(m)).join('');
                this.messages = data.data;
                this.scrollToBottom();
                this.bindMessageEvents();
            }
        } catch (e) { console.error(e); }
    }

    bindMessageEvents() {
        this.els.messagesArea?.querySelectorAll('.msg-bubble').forEach(el => {
            el.addEventListener('click', e => {
                const msgId = parseInt(el.dataset.id);
                if (e.target.closest('.msg-reply-btn')) {
                    const msg = this.messages.find(m => m.id === msgId);
                    if (msg) this.startReply(msg);
                }
            });
        });
    }

    renderMessage(m) {
        const isMe = m.sender_id == getCurrentUserId();
        const bubbleClass = isMe ? 'msg-bubble msg-sent' : 'msg-bubble msg-received';
        const avatar = isMe
            ? this.getAvatarHTML({ username: 'Me' })
            : this.getAvatarHTML({ username: m.sender_username, avatar: m.sender_avatar });
        let content = '';
        if (m.type === 'text' || !m.type) {
            content = `<div class="msg-text">${this.esc(m.body)}</div>`;
        } else if (m.type === 'image') {
            content = `<img src="/api/media.php?file=${encodeURIComponent(m.file_path)}&type=image" class="msg-image" alt="image">`;
        } else if (m.type === 'file') {
            content = `<div class="msg-file"><i class="fas fa-file"></i> ${this.esc(m.file_name)}</div>`;
        } else if (m.type === 'voice') {
            content = `<audio controls src="/api/media.php?file=${encodeURIComponent(m.file_path)}&type=voice" style="width:200px;"></audio>`;
        }
        return `
            <div class="${bubbleClass}" data-id="${m.id}" data-sender="${m.sender_id}">
                ${!isMe ? avatar : ''}
                <div class="msg-content">
                    ${m.reply_to_id ? `<div class="msg-reply">Reply: ${this.esc(m.reply_body || '[deleted]')}</div>` : ''}
                    ${content}
                    <div class="msg-meta">
                        <span class="msg-time">${formatTime(m.created_at)}</span>
                        ${m.status === 'read' ? '<span class="msg-status read">✓✓</span>' : m.status === 'delivered' ? '<span class="msg-status">✓✓</span>' : '<span class="msg-status">✓</span>'}
                    </div>
                </div>
                ${isMe ? avatar : ''}
            </div>
        `;
    }

    async sendMessage() {
        const input = this.els.messageInput;
        const text = input?.value?.trim();
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
        try {
            const res = await fetch('/api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            console.log('[ChatApp] File upload response:', data);
            if (data.success) await this.loadMessages();
        } catch (e) { console.error(e); }
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

    async toggleReaction(msgId, emoji) {
        try {
            await fetch('/api/reactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle', message_id: msgId, emoji })
            });
            await this.loadMessages();
        } catch (e) { console.error(e); }
    }

    async deleteMessage(msgId) {
        if (!confirm('Delete this message?')) return;
        try {
            await fetch('/api/messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', message_id: msgId })
            });
            await this.loadMessages();
        } catch (e) { console.error(e); }
    }

    startPolling() {
        clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => {
            if (this.currentChatId) this.loadMessages();
            this.updateNotificationBadge();
        }, 2000);
    }

    async updateNotificationBadge() {
        try {
            const res = await fetch('/api/notifications.php?action=count');
            const data = await res.json();
            if (data.success && data.data !== undefined) {
                const badge = document.querySelector('.nav-link[href*="notifications"] .badge, .navbar .badge');
                if (badge) {
                    badge.textContent = data.data;
                    badge.style.display = data.data > 0 ? '' : 'none';
                }
            }
        } catch (e) {}
    }

    scrollToBottom() {
        if (this.els.messagesArea) {
            this.els.messagesArea.scrollTop = this.els.messagesArea.scrollHeight;
        }
    }

    getAvatarHTML(m, size = 32) {
        if (m.avatar) {
            return `<img src="/api/media.php?file=${encodeURIComponent(m.avatar)}&type=avatar" style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover;" class="me-1">`;
        }
        const initial = (m.username || 'U')[0].toUpperCase();
        return `<div class="avatar avatar-default" style="width:${size}px;height:${size}px;font-size:${size*0.4}px;flex-shrink:0;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);">${initial}</div>`;
    }

    startVoiceCall() {
        console.log('[ChatApp] startVoiceCall', this.currentChatId);
        if (!this.currentChatId) {
            alert('Please select a conversation first');
            return;
        }
        if (window.calls) {
            window.calls.startVoiceCall(this.currentChatId);
        } else {
            console.warn('[ChatApp] calls.js not loaded');
            alert('Calling feature is not available');
        }
    }

    startVideoCall() {
        console.log('[ChatApp] startVideoCall', this.currentChatId);
        if (!this.currentChatId) {
            alert('Please select a conversation first');
            return;
        }
        if (window.calls) {
            window.calls.startVideoCall(this.currentChatId);
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
}

// Initialize chat on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('[DOM] Ready, initializing ChatApp...');
    window.chatApp = new ChatApp();
    console.log('[DOM] ChatApp initialized');
});
