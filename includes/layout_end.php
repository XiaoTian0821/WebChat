</main>
<footer class="main-footer">
    <div class="container text-center">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?>. All rights reserved.</p>
        <p class="mb-0 text-muted small">Built with PHP, MySQL, HTML5, CSS3 & Vanilla JavaScript</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>
<?php if (isset($additionalScripts)): ?>
    <?php foreach ($additionalScripts as $script): ?>
    <script src="<?php echo APP_URL . $script; ?>?v=<?php echo APP_VERSION; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
