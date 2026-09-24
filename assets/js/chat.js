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
            emojiPicker: document.getElementById('emoji-picker'),
            chatInfoPanel: document.getElementById('chat-info-panel'),
            voiceRecorder: document.getElementById('voice-recorder'),
            voiceTimer: document.getElementById('voice-timer'),
            voiceCancel: document.getElementById('voice-cancel'),
            voiceSend: document.getElementById('voice-send'),
            sidebarTabs: document.querySelectorAll('.sidebar-tabs .nav-link'),
            searchInput: document.getElementById('search-conversations'),
        };
    }

    bindEvents() {
        const els = this.els;
        if (els.sendBtn) els.sendBtn.addEventListener('click', () => this.sendMessage());
        if (els.messageInput) {
            els.messageInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            els.messageInput.addEventListener('input', () => this.handleTyping());
        }
        if (els.attachBtn) els.attachBtn.addEventListener('click', () => els.attachInput?.click());
        if (els.attachInput) els.attachInput.addEventListener('change', e => this.handleFileUpload(e));
        if (els.replyCancel) els.replyCancel.addEventListener('click', () => this.cancelReply());
        if (els.voiceBtn) els.voiceBtn.addEventListener('click', () => this.toggleVoiceRecording());
        if (els.voiceCancel) els.voiceCancel.addEventListener('click', () => this.cancelVoiceRecording());
        if (els.voiceSend) els.voiceSend.addEventListener('click', () => this.sendVoiceRecording());
        if (els.emojiPicker) {
            els.emojiPicker.addEventListener('click', e => {
                if (e.target.classList.contains('emoji-btn')) {
                    const input = els.messageInput;
                    if (input) { input.value += e.target.textContent; input.focus(); }
                    els.emojiPicker.classList.remove('show');
                }
            });
        }
        if (els.chatBackBtn) els.chatBackBtn.addEventListener('click', () => this.showSidebar());
        
        if (els.searchInput) {
            let debounceTimer;
            els.searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => this.filterConversations(els.searchInput.value), 300);
            });
        }
        
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
                    this.els.groupList.querySelectorAll('.group-item').forEach(el => {
                        el.addEventListener('click', e => {
                            e.preventDefault();
                            this.selectGroup(parseInt(el.dataset.id));
                        });
                    });
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

    async selectGroup(id) {
        window.location.href = '/group_chat.php?id=' + id;
    }

    async loadConversations() {
        if (!this.els.conversationList) return;
        this.els.conversationList.innerHTML = '<div class="loading-spinner p-3"><i class="fas fa-spinner fa-spin"></i></div>';
        try {
            const res = await fetch('/api/chat.php?action=conversations');
            const data = await res.json();
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
    }

    showChat() {
        if (this.els.chatMain) this.els.chatMain.classList.remove('chat-hidden');
    }

    showSidebar() {
        if (this.els.chatMain) this.els.chatMain.classList.add('chat-hidden');
        this.currentChatId = null;
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
        if (m.message_type === 'text') {
            content = `<div class="msg-text">${this.esc(m.message)}</div>`;
        } else if (m.message_type === 'image') {
            content = `<img src="/api/media.php?file=${encodeURIComponent(m.file_path)}&type=image" class="msg-image" alt="image">`;
        } else if (m.message_type === 'file') {
            content = `<div class="msg-file"><i class="fas fa-file"></i> ${this.esc(m.file_name)}</div>`;
        } else if (m.message_type === 'voice') {
            content = `<audio controls src="/api/media.php?file=${encodeURIComponent(m.file_path)}&type=voice" style="width:200px;"></audio>`;
        }
        return `
            <div class="${bubbleClass}" data-id="${m.id}" data-sender="${m.sender_id}">
                ${!isMe ? avatar : ''}
                <div class="msg-content">
                    ${m.reply_to ? `<div class="msg-reply">Reply: ${this.esc(m.reply_message || '[deleted]')}</div>` : ''}
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
            if (data.success) {
                input.value = '';
                this.replyTo = null;
                this.hideReplyBar();
                await this.loadMessages();
            }
        } catch (e) { console.error(e); }
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
            if (text) text.textContent = m.message ? m.message.substring(0, 30) + '...' : '[image]';
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
        }, 2000);
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
        return `<div class="avatar avatar-default" style="width:${size}px;height:${size}px;font-size:${size*0.4}px;flex-shrink:0;">${initial}</div>`;
    }

    startVoiceCall(id) {
        console.log('[ChatApp] startVoiceCall', id);
        if (window.calls) window.calls.startVoiceCall(id);
    }

    startVideoCall(id) {
        console.log('[ChatApp] startVideoCall', id);
        if (window.calls) window.calls.startVideoCall(id);
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

document.addEventListener('DOMContentLoaded', () => {
    window.chatApp = new ChatApp();
});
