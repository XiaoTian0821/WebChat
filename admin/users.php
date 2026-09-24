<?php
/**
 * WebConnect - Admin Users Management
 */
require_once '../includes/bootstrap.php';
requireAdminLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle actions
if ($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $user = Database::fetchOne("SELECT is_active FROM users WHERE id = ?", [$userId]);
    if ($user) {
        Database::update('users', ['is_active' => $user['is_active'] ? 0 : 1], 'id = ?', [$userId]);
    }
    redirect(APP_URL . '/admin/users.php');
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($userId !== 1) { // Don't delete first user
        Database::delete('users', 'id = ?', [$userId]);
    }
    redirect(APP_URL . '/admin/users.php');
}

$search = sanitize($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = $search ? "WHERE (username LIKE ? OR email LIKE ?)" : "";
$params = $search ? ["%$search%", "%$search%"] : [];

$users = Database::fetchAll("SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$limit, $offset]));
$total = (int) Database::fetchColumn("SELECT COUNT(*) FROM users $where", $params);
$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
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
                <li class="nav-item"><a class="nav-link active" href="<?php echo APP_URL; ?>/admin/users.php"><i class="fas fa-users me-2"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/forums.php"><i class="fas fa-forum me-2"></i>Forums</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/reports.php"><i class="fas fa-flag me-2"></i>Reports <span class="badge bg-danger ms-1"><?php echo (int)Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status='open'"); ?></span></a></li>
                <li class="nav-item mt-3"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-users me-2"></i>User Management</h4>
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo e($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if ($search): ?><a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-secondary">Clear</a><?php endif; ?>
                </form>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Joined</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo (int)$u['id']; ?></td>
                                    <td><strong><?php echo e($u['username']); ?></strong></td>
                                    <td class="text-muted"><?php echo e($u['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Disabled'; ?></span></td>
                                    <td class="text-muted"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Toggle status for <?php echo e($u['username']); ?>?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <?php echo csrfField(); ?>
                                            <button class="btn btn-sm btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $u['is_active'] ? 'Disable' : 'Enable'; ?>">
                                                <i class="fas fa-<?php echo $u['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete <?php echo e($u['username']); ?>? This cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <?php echo csrfField(); ?>
                                            <button class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $p; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $p; ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
