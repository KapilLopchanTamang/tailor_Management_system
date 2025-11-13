<?php
/**
 * Admin Navigation Component
 * Tailoring Management System
 */
require_once __DIR__ . '/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . baseUrl('login.php'));
    exit();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo baseUrl('admin/dashboard.php'); ?>">
            <i class="bi bi-shield-check"></i> Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <!-- Global Search -->
            <form class="d-flex me-auto" style="max-width: 350px;">
                <div class="input-group position-relative">
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="globalSearchInput" 
                           placeholder="Search orders, customers, inventory..." 
                           autocomplete="off">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <div class="dropdown-menu dropdown-menu-start w-100 position-absolute top-100 start-0 mt-1" 
                         id="globalSearchDropdown" 
                         style="max-height: 400px; overflow-y: auto; z-index: 1050; display: none;">
                    </div>
                </div>
            </form>
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo baseUrl('admin/dashboard.php'); ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                       href="<?php echo baseUrl('admin/users.php'); ?>">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" 
                       href="<?php echo baseUrl('admin/orders.php'); ?>">
                        <i class="bi bi-bag"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" 
                       href="<?php echo baseUrl('admin/inventory.php'); ?>">
                        <i class="bi bi-box-seam"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'active' : ''; ?>" 
                       href="<?php echo baseUrl('admin/staff.php'); ?>">
                        <i class="bi bi-person-badge"></i> Staff
                    </a>
                </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" 
                           href="<?php echo baseUrl('admin/reports.php'); ?>">
                            <i class="bi bi-file-earmark-text"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" 
                           href="<?php echo baseUrl('admin/notifications.php'); ?>">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>" 
                           href="<?php echo baseUrl('admin/feedback.php'); ?>">
                            <i class="bi bi-chat-dots"></i> Feedback
                        </a>
                    </li>
                </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminUserDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo baseUrl('admin/profile.php'); ?>">
                            <i class="bi bi-person"></i> Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo baseUrl('includes/logout.php'); ?>">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

