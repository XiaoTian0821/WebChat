<?php
/**
 * WebConnect - Groups Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$title = 'Groups';
$additionalScripts = ['/assets/js/groups.js'];
$userId = getCurrentUserId();

$groups = getUserGroups($userId);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-users me-2 text-primary"></i>Groups</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="fas fa-plus me-1"></i>Create Group
        </button>
    </div>

    <?php if (count($groups) > 0): ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php foreach ($groups as $g): ?>
        <div class="col">
            <a href="<?php echo APP_URL; ?>/group_chat.php?id=<?php echo (int)$g['id']; ?>" class="group-card text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <?php if ($g['avatar'] && file_exists(GROUP_PATH . '/' . $g['avatar'])): ?>
                            <img src="<?php echo APP_URL; ?>/api/media.php?file=<?php echo urlencode($g['avatar']); ?>&type=group" class="group-avatar mb-3" alt="<?php echo e($g['name']); ?>">
                        <?php else: ?>
                            <div class="group-avatar mb-3"><i class="fas fa-users"></i></div>
                        <?php endif; ?>
                        <h6 class="fw-bold mb-1"><?php echo e($g['name']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo e($g['description'] ?? 'No description'); ?></p>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="fas fa-comment me-1"></i><?php echo (int)$g['message_count']; ?> msgs</span>
                            <span class="badge bg-<?php echo $g['role'] === 'owner' ? 'warning' : ($g['role'] === 'admin' ? 'info' : 'secondary'); ?>"><?php echo e(ucfirst($g['role'])); ?></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users fa-3x mb-3"></i>
        <h5>No Groups Yet</h5>
        <p class="text-muted">Create a group to start chatting with multiple people!</p>
    </div>
    <?php endif; ?>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users me-2"></i>Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-group-form" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Group Name *</label>
                        <input type="text" class="form-control" name="name" required maxlength="100" placeholder="Enter group name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Group Avatar</label>
                        <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-create me-1"></i>Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_end.php'; ?>
