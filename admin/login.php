<?php
/**
 * WebConnect - Admin Login
 */
require_once '../includes/bootstrap.php';

if (isAdminLoggedIn()) {
    redirect(APP_URL . '/admin/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $errors[] = 'Invalid security token.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = loginAdmin($username, $password);
        if ($result === false) {
            $errors[] = 'Invalid admin credentials.';
        } else {
            redirect(APP_URL . '/admin/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-shield-alt text-primary me-2"></i>Admin Panel</h4>
                        <p class="text-muted small"><?php echo e(APP_NAME); ?></p>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?php echo e($e); ?></div><?php endforeach; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-1"></i>Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/login.php" class="small text-muted">Back to Login</a>
                    </div>
                    <div class="col-md-6 mb-2">
                    <div class="input-group input-group-sm">
                            <span class="input-group-text">Admin</span>
                            <input type="text" class="form-control" value="admin" readonly>
                            <input type="password" class="form-control" value="admin123" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
