<?php
/**
 * WebConnect - Admin Forums Management
 */
require_once '../includes/bootstrap.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_forum') {
        $forumId = (int) ($_POST['forum_id'] ?? 0);
        Database::delete('forums', 'id = ?', [$forumId]);
        redirect(APP_URL . '/admin/forums.php');
    }
    if ($action === 'edit_forum') {
        $forumId = (int) ($_POST['forum_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        Database::update('forums', ['name' => $name, 'description' => $description], 'id = ?', [$forumId]);
        redirect(APP_URL . '/admin/forums.php');
    }
}

$forums = Database::fetchAll("SELECT *, (SELECT COUNT(*) FROM forum_posts WHERE forum_id = f.id) as post_count FROM forums f ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Forums — Admin</title>
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
                <li class="nav-item"><a class="nav-link active" href="<?php echo APP_URL; ?>/admin/forums.php"><i class="fas fa-forum me-2"></i>Forums</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/reports.php"><i class="fas fa-flag me-2"></i>Reports <span class="badge bg-danger ms-1"><?php echo (int)Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status='open'"); ?></span></a></li>
                <li class="nav-item mt-3"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-forum me-2"></i>Forum Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createForumModal">
                    <i class="fas fa-plus me-1"></i>Create Forum
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr><th>ID</th><th>Name</th><th>Description</th><th>Posts</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forums as $f): ?>
                            <tr>
                                <td><?php echo (int)$f['id']; ?></td>
                                <td><strong><?php echo e($f['name']); ?></strong></td>
                                <td class="text-muted"><?php echo e($f['description'] ?? ''); ?></td>
                                <td><?php echo (int)$f['post_count']; ?></td>
                                <td class="text-muted"><?php echo date('M j, Y', strtotime($f['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editForumModal"
                                        data-id="<?php echo (int)$f['id']; ?>" data-name="<?php echo e($f['name']); ?>" data-desc="<?php echo e($f['description'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete forum '<?php echo e($f['name']); ?>' and all its posts?')">
                                        <input type="hidden" name="action" value="delete_forum">
                                        <input type="hidden" name="forum_id" value="<?php echo (int)$f['id']; ?>">
                                        <?php echo csrfField(); ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Create Forum Modal -->
<div class="modal fade" id="createForumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create Forum</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="<?php echo APP_URL; ?>/api/forums.php?action=create_forum_admin">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Forum Modal -->
<div class="modal fade" id="editForumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Forum</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_forum">
                <input type="hidden" name="forum_id" id="edit_forum_id">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" name="name" id="edit_forum_name" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" id="edit_forum_desc" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-bs-target="#editForumModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_forum_id').value = btn.dataset.id;
        document.getElementById('edit_forum_name').value = btn.dataset.name;
        document.getElementById('edit_forum_desc').value = btn.dataset.desc;
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
