<?php
/**
 * WebConnect - Profile Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$errors = [];
$success = '';
$user = getCurrentUser();
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        // Update profile
        if ($action === 'update_profile') {
            $statusMessage = sanitize($_POST['status_message'] ?? '');
            Database::update('users', ['status_message' => $statusMessage], 'id = ?', [$userId]);
            $success = 'Profile updated successfully.';
        }

        // Change password
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!verifyPassword($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (!isValidPassword($newPassword)) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            } else {
                Database::update('users', ['password' => hashPassword($newPassword)], 'id = ?', [$userId]);
                $success = 'Password changed successfully.';
            }
        }

        // Upload avatar
        if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $mime = mime_content_type($file['tmp_name']);
                if (in_array($mime, ALLOWED_AVATAR_MIME) && in_array($ext, ALLOWED_AVATAR_EXT)) {
                    if ($file['size'] <= MAX_AVATAR_SIZE) {
                        $randomName = generateRandomFilename($ext);
                        $dest = AVATAR_PATH . '/' . $randomName;
                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            // Remove old avatar
                            if ($user['avatar'] && file_exists(AVATAR_PATH . '/' . $user['avatar'])) {
                                @unlink(AVATAR_PATH . '/' . $user['avatar']);
                            }
                            Database::update('users', ['avatar' => $randomName], 'id = ?', [$userId]);
                            $success = 'Avatar uploaded successfully.';
                        } else {
                            $errors[] = 'Failed to save avatar.';
                        }
                    } else {
                        $errors[] = 'Avatar file is too large (max 2MB).';
                    }
                } else {
                    $errors[] = 'Invalid avatar file type. Use JPG, PNG, GIF, or WEBP.';
                }
            }
        }

        // Remove avatar
        if ($action === 'remove_avatar') {
            if ($user['avatar'] && file_exists(AVATAR_PATH . '/' . $user['avatar'])) {
                @unlink(AVATAR_PATH . '/' . $user['avatar']);
            }
            Database::update('users', ['avatar' => null], 'id = ?', [$userId]);
            $success = 'Avatar removed.';
        }
    }
}

// Re-fetch user data
$user = getCurrentUser();

$title = 'Profile';
$additionalScripts = ['/assets/js/profile.js'];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h4 class="mb-4"><i class="fas fa-user-circle me-2 text-primary"></i>My Profile</h4>

            <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?php echo e($e); ?></div><?php endforeach; ?></div><?php endif; ?>

            <!-- Avatar Section -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-camera me-2"></i>Avatar</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-4">
                        <div id="avatarPreview">
                            <?php echo getUserAvatar($userId, 100); ?>
                        </div>
                        <div>
                            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="upload_avatar">
                                <div class="mb-2">
                                    <label for="avatarInput" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-upload me-1"></i>Upload New Avatar
                                    </label>
                                    <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none" onchange="document.getElementById('avatarForm').submit()">
                                </div>
                            </form>
                            <form method="POST" class="mt-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="remove_avatar">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-info-circle me-2"></i>Profile Information</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Username:</strong> <span class="text-muted"><?php echo e($user['username']); ?></span></div>
                        <div class="col-md-6"><strong>Email:</strong> <span class="text-muted"><?php echo e($user['email']); ?></span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Disabled'; ?></span></div>
                        <div class="col-md-6"><strong>Member since:</strong> <span class="text-muted"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span></div>
                    </div>
                    <div class="mb-3">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_profile">
                            <label class="form-label">Status Message</label>
                            <input type="text" class="form-control" name="status_message" value="<?php echo e($user['status_message']); ?>" maxlength="200" placeholder="Share something about yourself...">
                            <button type="submit" class="btn btn-sm btn-primary mt-2"><i class="fas fa-save me-1"></i>Update</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-key me-2"></i>Change Password</div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i>Change Password</button>
                    </form>
                </div>
            </div>

            <!-- Account Stats -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-chart-bar me-2"></i>Account Statistics</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="display-6 text-primary"><?php echo getFriendCount($userId); ?></div>
                            <div class="text-muted small">Friends</div>
                        </div>
                        <div class="col-4">
                            <div class="display-6 text-success"><?php echo getUserGroups($userId); echo count(getUserGroups($userId)); ?></div>
                            <div class="text-muted small">Groups</div>
                        </div>
                        <div class="col-4">
                            <div class="display-6 text-info">—</div>
                            <div class="text-muted small">Messages</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/layout_end.php'; ?>
