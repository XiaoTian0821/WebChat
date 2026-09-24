// WebConnect - Notifications Module
document.addEventListener('DOMContentLoaded', () => {
    // Mark all read
    document.getElementById('mark-all-read')?.addEventListener('click', async () => {
        const res = await fetch('/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    });

    // Delete all notifications button
    document.getElementById('delete-all-notif')?.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to delete all notifications?')) return;
        
        const res = await fetch('/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_all' })
        });
        const data = await res.json();
        if (data.success) {
            alert('Deleted ' + data.data + ' notifications');
            location.reload();
        }
    });
});
