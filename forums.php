<?php
/**
 * WebConnect - Forums Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$title = 'Forums';
$additionalScripts = ['/assets/js/forums.js'];
$userId = getCurrentUserId();

$forums = Database::fetchAll("SELECT * FROM forums ORDER BY created_at ASC");
$activeForumId = (int) ($_GET['forum'] ?? ($forums[0]['id'] ?? 0));
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <i class="fas fa-plus me-1"></i>New Post
                    </button>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-folder me-2"></i>Categories</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($forums as $f): ?>
                    <a href="<?php echo APP_URL; ?>/forums.php?forum=<?php echo (int)$f['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo (int)$f['id'] === $activeForumId ? 'active' : ''; ?>">
                        <span><i class="fas fa-folder me-2"></i><?php echo e($f['name']); ?></span>
                        <small><?php echo (int)Database::fetchColumn("SELECT COUNT(*) FROM forum_posts WHERE forum_id = ?", [$f['id']]); ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <div class="col-lg-9">
            <?php foreach ($forums as $f): if ((int)$f['id'] === $activeForumId): ?>
            <div class="forum-category-header mb-4">
                <h4 class="mb-1"><?php echo e($f['name']); ?></h4>
                <p class="mb-0 opacity-75"><?php echo e($f['description'] ?? ''); ?></p>
            </div>
            <?php endif; endforeach; ?>

            <div id="forum-posts-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i>New Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-post-form" method="POST" action="<?php echo APP_URL; ?>/api/forums.php?action=create_post">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Forum</label>
                        <select class="form-select" name="forum_id" required>
                            <?php foreach ($forums as $f): ?>
                            <option value="<?php echo (int)$f['id']; ?>"><?php echo e($f['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_end.php'; ?>
