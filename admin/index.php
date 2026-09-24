<?php
/**
 * WebConnect - Admin Dashboard
 */
require_once '../includes/bootstrap.php';
requireAdminLogin();

// Stats
$totalUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users");
$activeUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE is_active = 1");
$disabledUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE is_active = 0");
$totalMessages = (int) Database::fetchColumn("SELECT COUNT(*) FROM messages");
$totalGroups = (int) Database::fetchColumn("SELECT COUNT(*) FROM group_chats");
$totalPosts = (int) Database::fetchColumn("SELECT COUNT(*) FROM forum_posts");
$openReports = (int) Database::fetchColumn("SELECT COUNT(*) FROM reports WHERE status = 'open'");
$newUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$newMessages = (int) Database::fetchColumn("SELECT COUNT(*) FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar .nav-link { color: #94a3b8; padding: 0.75rem 1rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        .stat-card { border: none; border-radius: 12px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar py-3">
            <div class="text-center mb-4">
                <h5 class="text-white"><i class="fas fa-shield-alt me-2"></i>Admin Panel</h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="<?php echo APP_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/users.php"><i class="fas fa-users me-2"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/forums.php"><i class="fas fa-forum me-2"></i>Forums</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/reports.php"><i class="fas fa-flag me-2"></i>Reports <span class="badge bg-danger ms-1"><?php echo $openReports; ?></span></a></li>
                <li class="nav-item mt-3"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h4 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h4>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $totalUsers; ?></div>
                                <div class="text-muted small">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-check"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $activeUsers; ?></div>
                                <div class="text-muted small">Active Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-user-slash"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $disabledUsers; ?></div>
                                <div class="text-muted small">Disabled</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-comment"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $totalMessages; ?></div>
                                <div class="text-muted small">Messages</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-users-cog"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $totalGroups; ?></div>
                                <div class="text-muted small">Groups</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-purple bg-opacity-10" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-forum"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $totalPosts; ?></div>
                                <div class="text-muted small">Forum Posts</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-flag"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $openReports; ?></div>
                                <div class="text-muted small">Open Reports</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-plus"></i></div>
                            <div>
                                <div class="display-6 fw-bold mb-0"><?php echo $newUsers; ?></div>
                                <div class="text-muted small">New (7 days)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-user-clock me-2"></i>Recent Users</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>User</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
                            <tbody>
                                <?php $recentUsers = Database::fetchAll("SELECT id, username, email, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                                foreach ($recentUsers as $ru): ?>
                                <tr>
                                    <td><strong><?php echo e($ru['username']); ?></strong></td>
                                    <td class="text-muted"><?php echo e($ru['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $ru['is_active'] ? 'success' : 'danger'; ?>"><?php echo $ru['is_active'] ? 'Active' : 'Disabled'; ?></span></td>
                                    <td class="text-muted"><?php echo date('M j, Y', strtotime($ru['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
