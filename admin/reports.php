<?php
/**
 * WebConnect - Admin Reports Management
 */
require_once '../includes/bootstrap.php';
requireAdminLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle actions
if ($action === 'resolve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $notes = sanitize($_POST['admin_notes'] ?? '');
    Database::update('reports', ['status' => 'resolved', 'admin_notes' => $notes], 'id = ?', [$reportId]);
    redirect(APP_URL . '/admin/reports.php');
}
if ($action === 'review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    Database::update('reports', ['status' => 'reviewed'], 'id = ?', [$reportId]);
    redirect(APP_URL . '/admin/reports.php');
}

$statusFilter = $_GET['status'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($statusFilter !== 'all') {
    $where .= " AND r.status = ?";
    $params[] = $statusFilter;
}
if ($search) {
    $where .= " AND (r.reason LIKE ? OR r2.username LIKE ? OR r3.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$reports = Database::fetchAll(
    "SELECT r.*, r2.username as reporter_username, r2.email as reporter_email,
            CASE r.target_type WHEN 'user' THEN u.username WHEN 'forum_post' THEN fp.title ELSE NULL END as target_info
     FROM reports r
     LEFT JOIN users r2 ON r.reporter_id = r2.id
     LEFT JOIN users u ON CAST(r.target_id AS SIGNED) = u.id AND r.target_type = 'user'
     LEFT JOIN forum_posts fp ON CAST(r.target_id AS SIGNED) = fp.id AND r.target_type = 'forum_post'
     $where
     ORDER BY r.created_at DESC LIMIT 50",
    $params
);

$stats = [
    'open' => (int) Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status = 'open'"),
    'reviewed' => (int) Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status = 'reviewed'"),
    'resolved' => (int) Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status = 'resolved'"),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar .nav-link { color: #94a3b8; padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar py-3">
            <div class="text-center mb-4"><h5 class="text-white"><i class="fas fa-shield-alt me-2"></i>Admin</h5></div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/users.php"><i class="fas fa-users me-2"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/forums.php"><i class="fas fa-forum me-2"></i>Forums</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo APP_URL; ?>/admin/reports.php"><i class="fas fa-flag me-2"></i>Reports</a></li>
                <li class="nav-item mt-3"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h4 class="mb-4"><i class="fas fa-flag me-2"></i>Reports</h4>

            <div class="row g-3 mb-4">
                <div class="col-sm-4"><div class="card stat-card border-0 shadow-sm bg-danger text-white"><div class="card-body text-center"><h3><?php echo $stats['open']; ?></h3><div>Open</div></div></div></div>
                <div class="col-sm-4"><div class="card stat-card border-0 shadow-sm bg-warning text-dark"><div class="card-body text-center"><h3><?php echo $stats['reviewed']; ?></h3><div>Reviewed</div></div></div></div>
                <div class="col-sm-4"><div class="card stat-card border-0 shadow-sm bg-success text-white"><div class="card-body text-center"><h3><?php echo $stats['resolved']; ?></h3><div>Resolved</div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo e($search); ?>">
                        <select name="status" class="form-select" style="width:140px;">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="reviewed" <?php echo $statusFilter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        <?php if ($statusFilter !== 'all' || $search): ?><a href="<?php echo APP_URL; ?>/admin/reports.php" class="btn btn-secondary">Clear</a><?php endif; ?>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr><th>Date</th><th>Reporter</th><th>Type</th><th>Target</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $r): ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo date('M j, Y H:i', strtotime($r['created_at'])); ?></td>
                                        <td><strong><?php echo e($r['reporter_username']); ?></strong><br><small class="text-muted"><?php echo e($r['reporter_email']); ?></small></td>
                                        <td><span class="badge bg-<?php echo $r['target_type'] === 'user' ? 'warning' : ($r['target_type'] === 'forum_post' ? 'info' : 'secondary'); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $r['target_type']))); ?></span></td>
                                        <td><?php echo e($r['target_info'] ?? 'N/A'); ?></td>
                                        <td class="text-truncate" style="max-width:200px;" title="<?php echo e($r['reason']); ?>"><?php echo e($r['reason']); ?></td>
                                        <td><span class="badge bg-<?php echo $r['status'] === 'open' ? 'danger' : ($r['status'] === 'reviewed' ? 'warning' : 'success'); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
                                        <td>
                                            <?php if ($r['status'] !== 'resolved'): ?>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#resolveModal" data-report="<?php echo htmlspecialchars(json_encode($r)); ?>">
                                                <i class="fas fa-check"></i> Resolve
                                            </button>
                                            <?php else: ?>
                                            <small class="text-muted"><?php echo e($r['admin_notes'] ?? 'No notes'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No reports found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Resolve Report</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" id="resolveForm">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="report_id" id="resolve_report_id">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div id="resolve-details" class="mb-3 small text-muted"></div>
                    <div class="mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="3" placeholder="Add notes about this resolution..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Mark as Resolved</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-bs-target="#resolveModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const r = JSON.parse(btn.dataset.report);
        document.getElementById('resolve_report_id').value = r.id;
        document.getElementById('resolve-details').innerHTML =
            `<strong>Reporter:</strong> ${r.reporter_username}<br>` +
            `<strong>Type:</strong> ${r.target_type}<br>` +
            `<strong>Target:</strong> ${r.target_info || 'N/A'}<br>` +
            `<strong>Reason:</strong> ${r.reason}`;
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
