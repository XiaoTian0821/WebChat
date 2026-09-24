// WebConnect - Groups Module
document.addEventListener('DOMContentLoaded', () => {
    const createForm = document.getElementById('create-group-form');
    if (createForm) {
        createForm.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(createForm);
            try {
                const res = await fetch('/api/groups.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '/WebChat/group_chat.php?id=' + data.data.group_id;
                } else {
                    alert(data.message || 'Failed to create group');
                }
            } catch (err) { console.error(err); }
        });
    }

    // Group member management
    document.querySelectorAll('.add-member-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const groupId = btn.dataset.groupId;
            const modal = new bootstrap.Modal(document.getElementById('add-member-modal'));
            modal.show();
            document.getElementById('add-member-group-id').value = groupId;
            searchGroupMembers(groupId);
        });
    });

    document.getElementById('search-group-member')?.addEventListener('input', e => {
        const groupId = document.getElementById('add-member-group-id').value;
        searchGroupMembers(groupId, e.target.value);
    });
});

async function searchGroupMembers(groupId, query = '') {
    try {
        const res = await fetch(`/api/groups.php?action=search_users&q=${encodeURIComponent(query)}`);
        const data = await res.json();
        const container = document.getElementById('group-member-search-results');
        if (container && data.success) {
            container.innerHTML = data.data.map(u => `
                <div class="d-flex align-items-center justify-content-between p-2">
                    <div class="d-flex align-items-center gap-2">
                        ${u.avatar
                            ? `<img src="/api/media.php?file=${encodeURIComponent(u.avatar)}&type=avatar" style="width:32px;height:32px;border-radius:50%;">`
                            : `<div class="avatar avatar-default" style="width:32px;height:32px;font-size:12px;border-radius:50%;">${u.username[0]}</div>`}
                        <span>${u.username}</span>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="addGroupMember(${groupId}, ${u.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `).join('');
        }
    } catch (e) { console.error(e); }
}

async function addGroupMember(groupId, userId) {
    try {
        const res = await fetch('/api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_member', group_id: groupId, user_id: userId })
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('add-member-modal'))?.hide();
            location.reload();
        } else {
            alert(data.message || 'Failed to add member');
        }
    } catch (e) { console.error(e); }
}

async function removeGroupMember(groupId, userId) {
    if (!confirm('Remove this member?')) return;
    try {
        const res = await fetch('/api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_member', group_id: groupId, user_id: userId })
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (e) { console.error(e); }
}

async function changeMemberRole(groupId, userId, role) {
    try {
        const res = await fetch('/api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_role', group_id: groupId, user_id: userId, role })
        });
        const data = await res.json();
        if (data.success) location.reload();
    } catch (e) { console.error(e); }
}

async function leaveGroup(groupId) {
    if (!confirm('Leave this group?')) return;
    try {
        const res = await fetch('/api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leave', group_id: groupId })
        });
        const data = await res.json();
        if (data.success) window.location.href = '/WebConnect/groups.php';
    } catch (e) { console.error(e); }
}

async function deleteGroup(groupId) {
    if (!confirm('Delete this group? This cannot be undone.')) return;
    try {
        const res = await fetch('/api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', group_id: groupId })
        });
        const data = await res.json();
        if (data.success) window.location.href = '/WebConnect/groups.php';
    } catch (e) { console.error(e); }
}
