    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
<!-- Custom JS -->
<script src="<?php echo baseUrl('assets/js/main.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/js/auth.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/js/validation.js'); ?>"></script>
<?php if (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
<script src="<?php echo baseUrl('assets/js/notifications.js'); ?>"></script>
<?php endif; ?>
<?php if (isLoggedIn()): ?>
<script src="<?php echo baseUrl('assets/js/search.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/js/security.js'); ?>"></script>
<?php endif; ?>

<!-- Footer -->
<footer class="bg-light text-center text-muted py-3 mt-5">
    <div class="container">
        <p class="mb-0">
            &copy; <?php echo date('Y'); ?> Tailoring Management System. All rights reserved.
        </p>
        <p class="mb-0 small">
            <a href="<?php echo baseUrl(); ?>" class="text-decoration-none text-muted">Home</a> | 
            <a href="<?php echo baseUrl('login.php'); ?>" class="text-decoration-none text-muted">Login</a>
            <?php if (isLoggedIn()): ?>
                | <a href="<?php echo baseUrl($_SESSION['user_role'] . '/dashboard.php'); ?>" class="text-decoration-none text-muted">Dashboard</a>
            <?php endif; ?>
        </p>
    </div>
</footer>

</body>
</html>

