<?php
/**
 * WebConnect - 404 Page
 */
require_once 'includes/bootstrap.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
            <h1 class="display-1">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-home me-2"></i>Go Home
            </a>
        </div>
    </div>
</body>
</html>
