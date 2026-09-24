// WebConnect - Friends Module (Enhanced with debug)
document.addEventListener('DOMContentLoaded', () => {
    // Search users
    const searchInput = document.getElementById('search-user');
    if (!searchInput) {
        console.error('ERROR: search-user input not found!');
        return;
    }
    
    console.log('✓ Search input found:', searchInput);
    
    // Add log on every input
    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        console.log('Input changed:', val, 'length:', val.length);
    });
    
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const val = searchInput.value.trim();
            console.log('Searching for:', val, 'length:', val.length);
            searchUsers(val);
        }, 300);
    });

    // Friend tabs
    document.querySelectorAll('.friend-tab').forEach(tab => {
        tab.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('.friend-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const target = tab.dataset.target;
            document.querySelectorAll('.friend-panel').forEach(p => {
                p.classList.remove('d-block');
                p.classList.add('d-none');
            });
            const targetPanel = document.getElementById(target);
            if (targetPanel) {
                targetPanel.classList.remove('d-none');
                targetPanel.classList.add('d-block');
            }
        });
    });
});

async function searchUsers(query) {
    if (query.length < 2) return;
    try {
        const res = await fetch(`/api/friends.php?action=search&q=${encodeURIComponent(query)}`);
        const data = await res.json();
        const container = document.getElementById('search-results');
        if (!container) return;
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(u => `
                <div class="friend-card d-flex align-items-center p-3 border-bottom">
                    <div class="me-3">${u.avatar
                        ? `<img src="/api/media.php?file=${encodeURIComponent(u.avatar)}&type=avatar" style="width:45px;height:45px;border-radius:50%;object-fit:cover;">`
                        : `<div class="avatar avatar-default" style="width:45px;height:45px;border-radius:50%;">${u.username[0].toUpperCase()}</div>`}</div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${u.username}</div>
                        <div class="small text-muted">${getStatusText(u.status)} ${u.last_seen ? '· ' + timeAgo(u.last_seen) : ''}</div>
                    </div>
                    <div class="friend-actions">
                        ${u.request_status === 'pending_sent'
                            ? `<button class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-clock"></i></button>`
                            : u.request_status === 'pending_received'
                                ? `<button class="btn btn-sm btn-success" onclick="respondFriend(${u.id}, 'accept')"><i class="fas fa-check"></i></button>
                                   <button class="btn btn-sm btn-danger" onclick="respondFriend(${u.id}, 'reject')"><i class="fas fa-times"></i></button>`
                                : !u.is_friend && !u.is_blocked
                                    ? `<button class="btn btn-sm btn-primary" onclick="sendFriendRequest(${u.id}, this)"><i class="fas fa-user-plus"></i> Add</button>`
                                    : u.is_friend ? `<span class="badge bg-success">Friends</span>` : ''}
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="p-3 text-center text-muted">No users found</div>';
        }
    } catch (e) {
        console.error('Search error:', e);
    }
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

function getStatusText(status) {
    const map = { online: 'Online', away: 'Away', busy: 'Busy', offline: 'Offline' };
    return map[status] || '';
}

async function sendFriendRequest(userId, btn) {
    try {
        const res = await fetch('/api/friends.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', friend_id: userId })
        });
        const data = await res.json();
        if (data.success) {
            btn.closest('.friend-actions').innerHTML = '<button class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-clock"></i> Pending</button>';
        }
    } catch (e) { console.error(e); }
}

async function respondFriend(userId, action) {
    try {
        const res = await fetch('/api/friends.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'respond', response: action, friend_id: userId })
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (e) { console.error(e); }
}

async function removeFriend(userId) {
    if (!confirm('Remove this friend?')) return;
    try {
        const res = await fetch('/api/friends.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', friend_id: userId })
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (e) { console.error(e); }
}

async function blockUser(userId) {
    if (!confirm('Block this user?')) return;
    try {
        const res = await fetch('/api/blocks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'block', user_id: userId })
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (e) { console.error(e); }
}
