// WebConnect - Forums Module
document.addEventListener('DOMContentLoaded', () => {
    // Forum category tabs
    document.querySelectorAll('.forum-category-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('.forum-category-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            loadForumPosts(link.dataset.forumId);
        });
    });

    // Load first forum
    const firstCategory = document.querySelector('.forum-category-link.active');
    if (firstCategory) loadForumPosts(firstCategory.dataset.forumId);

    // Like buttons
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleLike(btn));
    });

    // Share buttons
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', () => sharePost(btn));
    });
});

async function loadForumPosts(forumId) {
    try {
        const res = await fetch(`/api/forums.php?action=get_posts&forum_id=${forumId}&page=1`);
        const data = await res.json();
        const container = document.getElementById('forum-posts-list');
        if (!container) return;
        if (data.success) {
            container.innerHTML = data.data.posts.map(p => renderPost(p)).join('');
        }
    } catch (e) { console.error(e); }
}

function renderPost(p) {
    return `
        <div class="card forum-post-card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div>${p.avatar
                        ? `<img src="/api/media.php?file=${encodeURIComponent(p.avatar)}&type=avatar" style="width:45px;height:45px;border-radius:50%;object-fit:cover;">`
                        : `<div class="avatar avatar-default" style="width:45px;height:45px;border-radius:50%;">${p.username[0]}</div>`}</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1"><a href="#" class="text-decoration-none text-dark" onclick="showPostDetail(${p.id});return false;">${p.title}</a></h5>
                        <p class="text-muted small mb-2">${p.content?.substring(0, 200)}${p.content?.length > 200 ? '...' : ''}</p>
                        <div class="d-flex align-items-center gap-3 post-stats">
                            <span><i class="fas fa-user me-1"></i>${p.username}</span>
                            <span><i class="fas fa-clock me-1"></i>${formatTime(p.created_at)}</span>
                            <span><i class="fas fa-comments me-1"></i>${p.comment_count} comments</span>
                            <span><i class="fas fa-eye me-1"></i>${p.views} views</span>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button class="btn btn-sm btn-outline-primary like-btn ${p.is_liked ? 'active' : ''}" data-post-id="${p.id}" data-liked="${p.is_liked}">
                                <i class="fas fa-heart${p.is_liked ? '' : '-empty'} me-1"></i>${p.like_count}
                            </button>
                            <button class="btn btn-sm btn-outline-secondary share-btn" data-post-id="${p.id}">
                                <i class="fas fa-share me-1"></i>Share
                            </button>
                            <button class="btn btn-sm btn-outline-danger ms-auto" onclick="reportPost(${p.id})">
                                <i class="fas fa-flag"></i> Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

async function toggleLike(btn) {
    const postId = btn.dataset.postId;
    const res = await fetch('/api/forums.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_like', post_id: postId })
    });
    const data = await res.json();
    if (data.success) {
        btn.classList.toggle('active');
        const icon = btn.querySelector('i');
        icon.className = data.data.liked ? 'fas fa-heart me-1' : 'fas fa-heart-empty me-1';
        btn.innerHTML = `<i class="${data.data.liked ? 'fas' : 'fas'} fa-heart${data.data.liked ? '' : '-empty'} me-1"></i>${data.data.like_count}`;
    }
}

async function sharePost(btn) {
    const postId = btn.dataset.postId;
    await fetch('/api/forums.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'share', post_id: postId })
    });
    // Copy link to clipboard
    const url = window.location.origin + '/WebChat/forums.php?post=' + postId;
    navigator.clipboard.writeText(url).then(() => {
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-share me-1"></i>Share'; }, 2000);
    });
}

async function reportPost(postId) {
    const reason = prompt('Please describe why you are reporting this post:');
    if (!reason) return;
    try {
        const res = await fetch('/api/forums.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'report', target_type: 'forum_post', target_id: postId, reason })
        });
        const data = await res.json();
        if (data.success) alert('Report submitted. Thank you.');
    } catch (e) { console.error(e); }
}

function formatTime(dateStr) {
    const d = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
    return d.toLocaleDateString();
}
