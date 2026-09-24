<?php
/**
 * WebConnect - Register Page
 */
require_once 'includes/layout_start.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required.';
        }
        if (!isValidUsername($username)) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, underscores only).';
        }
        if (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!isValidPassword($password)) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $userId = registerUser($username, $email, $password);
            if ($userId) {
                // Auto-login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['logged_in_at'] = time();
                redirect(APP_URL . '/chat.php');
            } else {
                $errors[] = 'Username or email already exists.';
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="text-center mb-4">
            <h3><i class="fas fa-user-plus text-primary me-2"></i>Create Account</h3>
            <p class="text-muted">Join <?php echo e(APP_NAME); ?> today</p>
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
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Choose a username" maxlength="50"
                           value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="form-text">3-50 characters. Letters, numbers, and underscores only.</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="your@email.com" maxlength="100"
                           value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Min. 6 characters" minlength="6" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter password" minlength="6" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        <div class="text-center">
            <span class="text-muted">Already have an account? </span>
            <a href="<?php echo APP_URL; ?>/login.php">Login here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_end.php'; ?>
