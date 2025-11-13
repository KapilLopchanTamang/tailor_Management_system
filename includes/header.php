<?php
// Ensure functions are loaded
if (!function_exists('baseUrl')) {
    require_once __DIR__ . '/functions.php';
}

if (!isset($pageTitle)) {
    $pageTitle = "Tailoring Management System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo baseUrl(); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - TMS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/style.css'); ?>">
    
    <!-- jQuery (for form validation) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- jQuery Validation Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
</head>
<body <?php if (isLoggedIn()): ?>class="logged-in"<?php endif; ?>>
    <?php if (isLoggedIn() && !isset($hide_main_nav)): ?>
        <?php if ($_SESSION['user_role'] !== 'admin'): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo baseUrl(); ?>">
                    <i class="bi bi-scissors"></i> TMS
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Global Search -->
                    <form class="d-flex me-auto" style="max-width: 350px;">
                        <div class="input-group position-relative">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="globalSearchInput" 
                                   placeholder="Search orders, inventory..." 
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
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl($_SESSION['user_role'] . '/dashboard.php'); ?>">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] === 'customer'): ?>
                            <li class="nav-item">
                                <a class="nav-link position-relative" href="#" id="notificationBell" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                                    <i class="bi bi-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                                        0
                                    </span>
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_role'] === 'staff'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo baseUrl('staff/notifications.php'); ?>">
                                    <i class="bi bi-bell"></i> Notifications
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo baseUrl($_SESSION['user_role'] . '/profile.php'); ?>">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo baseUrl('includes/logout.php'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; ?>
    <?php endif; ?>

