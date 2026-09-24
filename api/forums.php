<?php
/**
 * WebConnect - Forums API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_forums':
        $forums = Database::fetchAll("SELECT * FROM forums ORDER BY created_at DESC");
        jsonResponse(['success' => true, 'data' => $forums]);
        break;

    case 'get_posts':
        $forumId = (int) ($_GET['forum_id'] ?? 0);
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $posts = Database::fetchAll(
            "SELECT fp.*, u.username, u.avatar,
                    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = fp.id) as comment_count,
                    (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = fp.id) as like_count,
                    (SELECT COUNT(*) FROM post_shares ps WHERE ps.post_id = fp.id) as share_count,
                    (SELECT MAX(CASE WHEN pl.user_id = ? THEN 1 ELSE 0 END) FROM post_likes pl WHERE pl.post_id = fp.id) as is_liked
             FROM forum_posts fp
             JOIN users u ON fp.user_id = u.id
             WHERE fp.forum_id = ?
             ORDER BY fp.created_at DESC LIMIT ? OFFSET ?",
            [$userId, $forumId, $limit, $offset]
        );
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM forum_posts WHERE forum_id = ?", [$forumId]);

        jsonResponse(['success' => true, 'data' => [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
        ]]);
        break;

    case 'get_post':
        $postId = (int) ($_GET['id'] ?? 0);
        $post = Database::fetchOne(
            "SELECT fp.*, u.username, u.avatar,
                    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = fp.id) as comment_count,
                    (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = fp.id) as like_count,
                    (SELECT MAX(CASE WHEN pl.user_id = ? THEN 1 ELSE 0 END) FROM post_likes pl WHERE pl.post_id = fp.id) as is_liked
             FROM forum_posts fp JOIN users u ON fp.user_id = u.id WHERE fp.id = ?",
            [$userId, $postId]
        );
        if ($post) {
            // Increment views
            Database::query("UPDATE forum_posts SET views = views + 1 WHERE id = ?", [$postId]);
            $post['views']++;
        }
        $comments = Database::fetchAll(
            "SELECT pc.*, u.username, u.avatar FROM post_comments pc JOIN users u ON pc.user_id = u.id WHERE pc.post_id = ? ORDER BY pc.created_at ASC",
            [$postId]
        );
        jsonResponse(['success' => true, 'data' => array_merge($post ?: [], ['comments' => $comments])]);
        break;

    case 'create_forum_admin':
        requireAdminLogin();
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Forum name is required'], 400);
        }
        $forumId = Database::insert('forums', ['name' => $name, 'description' => $description, 'created_by' => getCurrentAdminId()]);
        jsonResponse(['success' => true, 'data' => ['forum_id' => $forumId]]);
        break;

    case 'create_post':
        requirePOST();
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Invalid token'], 403);
        }
        $forumId = (int) ($_POST['forum_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        if (empty($title) || empty($content)) {
            jsonResponse(['success' => false, 'message' => 'Title and content are required'], 400);
        }
        $postId = Database::insert('forum_posts', ['forum_id' => $forumId, 'user_id' => $userId, 'title' => $title, 'content' => $content]);
        jsonResponse(['success' => true, 'data' => ['post_id' => $postId]]);
        break;

    case 'add_comment':
        requirePOST();
        $postId = (int) ($_POST['post_id'] ?? 0);
        $content = sanitize($_POST['content'] ?? '');
        if (empty($content)) {
            jsonResponse(['success' => false, 'message' => 'Comment cannot be empty'], 400);
        }
        $commentId = Database::insert('post_comments', ['post_id' => $postId, 'user_id' => $userId, 'content' => $content]);
        // Notify post author
        $post = Database::fetchOne("SELECT user_id FROM forum_posts WHERE id = ?", [$postId]);
        if ($post && $post['user_id'] != $userId) {
            createNotification((int)$post['user_id'], 'forum_comment', 'New Comment', 'Someone commented on your post', $postId, $userId);
        }
        jsonResponse(['success' => true, 'data' => ['comment_id' => $commentId]]);
        break;

    case 'toggle_like':
        requirePOST();
        $postId = (int) ($_POST['post_id'] ?? 0);
        $existing = Database::fetchOne("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
        if ($existing) {
            Database::delete('post_likes', 'id = ?', [$existing['id']]);
            $liked = false;
        } else {
            Database::insert('post_likes', ['post_id' => $postId, 'user_id' => $userId]);
            $liked = true;
            // Notify post author
            $post = Database::fetchOne("SELECT user_id FROM forum_posts WHERE id = ?", [$postId]);
            if ($post && $post['user_id'] != $userId) {
                createNotification((int)$post['user_id'], 'forum_like', 'Post Liked', 'Someone liked your post', $postId, $userId);
            }
        }
        $count = (int) Database::fetchColumn("SELECT COUNT(*) FROM post_likes WHERE post_id = ?", [$postId]);
        jsonResponse(['success' => true, 'data' => ['liked' => $liked, 'like_count' => $count]]);
        break;

    case 'share':
        requirePOST();
        $postId = (int) ($_POST['post_id'] ?? 0);
        Database::insert('post_shares', ['post_id' => $postId, 'user_id' => $userId]);
        jsonResponse(['success' => true]);
        break;

    case 'report':
        requirePOST();
        $targetType = sanitize($_POST['target_type'] ?? '');
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');
        if (empty($reason)) {
            jsonResponse(['success' => false, 'message' => 'Reason is required'], 400);
        }
        Database::insert('reports', [
            'reporter_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
        ]);
        jsonResponse(['success' => true, 'message' => 'Report submitted']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
