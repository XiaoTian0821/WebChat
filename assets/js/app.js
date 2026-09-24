// WebConnect - Main Application JavaScript
const APP_URL = (document.querySelector('meta[name="app-url"]')?.content || window.location.origin + '/WebChat').replace(/\/$/, '');
const API = APP_URL + '/api/';

const nativeFetch = window.fetch.bind(window);
window.fetch = function(input, init) {
    if (typeof input === 'string' && input.startsWith('/')) {
        return nativeFetch(APP_URL + input, init);
    }
    if (input instanceof URL && input.origin === window.location.origin && input.pathname.startsWith('/')) {
        const rewritten = new URL(input.href);
        rewritten.pathname = APP_URL.replace(window.location.origin, '') + rewritten.pathname;
        return nativeFetch(rewritten.toString(), init);
    }
    return nativeFetch(input, init);
};

// =====================================================
// Utility Functions
// =====================================================
function getCurrentUserId() {
    const meta = document.querySelector('meta[name="user-id"]');
    return meta ? parseInt(meta.content) : 0;
}

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiRequest(endpoint, options = {}) {
    const defaultOpts = {
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    };
    if (options.body && !(options.body instanceof FormData)) {
        options.body = JSON.stringify(options.body);
    }
    const resp = await fetch(API + endpoint, { ...defaultOpts, ...options });
    return resp.json();
}

function formatTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function getStatusBadge(status, lastSeen) {
    const colors = {
        online: 'bg-success',
        away: 'bg-warning',
        busy: 'bg-danger',
        offline: 'bg-secondary'
    };
    const labels = {
        online: 'Online',
        away: 'Away',
        busy: 'Busy',
        offline: 'Offline'
    };
    const color = colors[status] || 'bg-secondary';
    const label = labels[status] || 'Offline';
    let time = '';
    if (lastSeen) {
        const d = new Date(lastSeen);
        const diff = Math.floor((Date.now() - d.getTime()) / 1000);
        if (diff < 60) time = 'just now';
        else if (diff < 3600) time = Math.floor(diff / 60) + 'm ago';
        else if (diff < 86400) time = Math.floor(diff / 3600) + 'h ago';
        else time = Math.floor(diff / 86400) + 'd ago';
    }
    return `<span class="status-dot ${color}" title="${label}"></span> ${label} ${time ? '· ' + time : ''}`;
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function getInitials(name) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

// =====================================================
// Emoji Picker
// =====================================================
const EMOJIS = [
    '😀','😂','🥰','😍','😘','😎','🤩','😇',
    '🙂','😉','😊','🥳','😋','😛','😜','🤪',
    '😢','😭','😤','😠','🤬','😱','🥺','😴',
    '👍','👎','👏','🙌','🤝','💪','🙏','❤️',
    '🔥','⭐','💯','🎉','🎊','💬','💭','👀',
    '✅','❌','⚡','💤','🌈','🎵','📱','💻'
];

function initEmojiPicker(containerId, inputId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const input = document.getElementById(inputId);
    const grid = document.createElement('div');
    grid.className = 'emoji-picker-grid';
    EMOJIS.forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'emoji-btn';
        btn.textContent = emoji;
        btn.addEventListener('click', () => {
            if (input) {
                input.value += emoji;
                input.focus();
            } else {
                container.dispatchEvent(new CustomEvent('emoji-select', { detail: emoji }));
            }
            container.classList.remove('show');
        });
        grid.appendChild(btn);
    });
    container.appendChild(grid);
    container.addEventListener('click', e => e.stopPropagation());
}

// =====================================================
// AJAX Form Submission
// =====================================================
function submitForm(formId, callback) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = new FormData(form);
        const obj = Object.fromEntries(data);
        try {
            const result = await apiRequest(form.dataset.api, { method: 'POST', body: obj });
            if (callback) callback(result, form);
        } catch (err) {
            console.error('Form submit error:', err);
        }
    });
}

// =====================================================
// Notification Sound
// =====================================================
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        gain.gain.value = 0.1;
        osc.start();
        setTimeout(() => { osc.stop(); ctx.close(); }, 150);
    } catch (e) {}
}

// =====================================================
// Initialize on DOM ready
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
    // Close dropdowns on outside click
    document.addEventListener('click', e => {
        document.querySelectorAll('.emoji-picker.show').forEach(p => {
            if (!p.contains(e.target)) p.classList.remove('show');
        });
    });
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bs = bootstrap.Alert.getOrCreateInstance(alert);
            if (bs) bs.close();
        }, 5000);
    });
});
