<?php
/**
 * WebConnect - Login Page
 */
require_once 'includes/layout_start.php';
$errors = [];

if (isLoggedIn()) {
    redirect(APP_URL . '/chat.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $errors[] = 'Please fill in all fields.';
        } elseif (!checkRateLimit('login_' . $identifier, 5, 60)) {
            $errors[] = 'Too many login attempts. Please wait a moment and try again.';
        } else {
            $result = loginUser($identifier, $password);
            if ($result === false) {
                $errors[] = 'Invalid username/email or password.';
            } elseif (is_array($result) && isset($result['error'])) {
                $errors[] = 'Your account has been disabled. Contact support.';
            } else {
                redirect(APP_URL . '/chat.php');
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="text-center mb-4">
            <h3><i class="fas fa-sign-in-alt text-primary me-2"></i>Login</h3>
            <p class="text-muted">Welcome back to <?php echo e(APP_NAME); ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <?php echo csrfField(); ?>
            <div class="mb-3">
                <label for="identifier" class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="identifier" name="identifier"
                           placeholder="Enter username or email" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        <div class="text-center">
            <span class="text-muted">Don't have an account? </span>
            <a href="<?php echo APP_URL; ?>/register.php">Register here</a>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo APP_URL; ?>/admin/login.php" class="text-muted small">Admin Login</a>
        </div>
        
        <div class="demo-accounts mt-4 p-3 bg-light rounded">
            <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Demo Accounts</h6>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">User</span>
                        <input type="text" class="form-control" value="ali" readonly>
                        <input type="password" class="form-control" value="password123" readonly>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">User</span>
                        <input type="text" class="form-control" value="meimei" readonly>
                        <input type="password" class="form-control" value="password123" readonly>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">User</span>
                        <input type="text" class="form-control" value="abu" readonly>
                        <input type="password" class="form-control" value="password123" readonly>
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="copyDemoAccounts()">
                <i class="fas fa-copy me-1"></i>Copy All Credentials
            </button>
        </div>
    </div>
</div>

<script>
function copyDemoAccounts() {
    const text = `User: ali / password123
User: meimei / password123
User: abu / password123
Admin: admin / admin123
Admin: superadmin / superadmin123`;
    navigator.clipboard.writeText(text).then(() => {
        alert('Demo accounts copied to clipboard!');
    });
}
</script>

<?php require_once 'includes/layout_end.php'; ?>
