<?php
/**
 * Unified Navigation Bar Component
 * Tailoring Management System
 * Role-based menu for all user types
 */

if (!isLoggedIn()) {
    return; // Don't show navbar if not logged in
}

$userRole = $_SESSION['user_role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
$dashboardUrl = baseUrl($userRole . '/dashboard.php');

// Define menu items for each role
$menuItems = [];

if ($userRole === 'admin') {
    $menuItems = [
        [
            'title' => 'Dashboard',
            'url' => baseUrl('admin/dashboard.php'),
            'icon' => 'bi-speedometer2',
            'active' => $currentPage === 'dashboard.php'
        ],
        [
            'title' => 'Users',
            'url' => baseUrl('admin/users.php'),
            'icon' => 'bi-people',
            'active' => $currentPage === 'users.php'
        ],
        [
            'title' => 'Orders',
            'url' => baseUrl('admin/orders.php'),
            'icon' => 'bi-bag',
            'active' => $currentPage === 'orders.php'
        ],
        [
            'title' => 'Inventory',
            'url' => baseUrl('admin/inventory.php'),
            'icon' => 'bi-box-seam',
            'active' => $currentPage === 'inventory.php'
        ],
        [
            'title' => 'Staff',
            'url' => baseUrl('admin/staff.php'),
            'icon' => 'bi-person-badge',
            'active' => $currentPage === 'staff.php'
        ],
        [
            'title' => 'Reports',
            'url' => baseUrl('admin/reports.php'),
            'icon' => 'bi-file-earmark-text',
            'active' => $currentPage === 'reports.php'
        ],
        [
            'title' => 'Notifications',
            'url' => baseUrl('admin/notifications.php'),
            'icon' => 'bi-bell',
            'active' => $currentPage === 'notifications.php'
        ],
        [
            'title' => 'Feedback',
            'url' => baseUrl('admin/feedback.php'),
            'icon' => 'bi-chat-dots',
            'active' => $currentPage === 'feedback.php'
        ]
    ];
    $navbarColor = 'bg-dark';
    $brandText = '<i class="bi bi-shield-check"></i> Admin Panel';
} elseif ($userRole === 'staff') {
    $menuItems = [
        [
            'title' => 'Dashboard',
            'url' => baseUrl('staff/dashboard.php'),
            'icon' => 'bi-speedometer2',
            'active' => $currentPage === 'dashboard.php'
        ],
        [
            'title' => 'Tasks',
            'url' => baseUrl('staff/tasks.php'),
            'icon' => 'bi-list-check',
            'active' => $currentPage === 'tasks.php'
        ],
        [
            'title' => 'Orders',
            'url' => baseUrl('staff/orders.php'),
            'icon' => 'bi-bag',
            'active' => $currentPage === 'orders.php'
        ],
        [
            'title' => 'Inventory',
            'url' => baseUrl('staff/inventory.php'),
            'icon' => 'bi-box-seam',
            'active' => $currentPage === 'inventory.php'
        ],
        [
            'title' => 'Notifications',
            'url' => baseUrl('staff/notifications.php'),
            'icon' => 'bi-bell',
            'active' => $currentPage === 'notifications.php'
        ]
    ];
    $navbarColor = 'bg-primary';
    $brandText = '<i class="bi bi-scissors"></i> TMS';
} elseif ($userRole === 'customer') {
    $menuItems = [
        [
            'title' => 'Dashboard',
            'url' => baseUrl('customer/dashboard.php'),
            'icon' => 'bi-speedometer2',
            'active' => $currentPage === 'dashboard.php'
        ],
        [
            'title' => 'My Orders',
            'url' => baseUrl('customer/orders.php'),
            'icon' => 'bi-bag',
            'active' => $currentPage === 'orders.php'
        ],
        [
            'title' => 'Track Order',
            'url' => baseUrl('customer/track.php'),
            'icon' => 'bi-geo-alt',
            'active' => $currentPage === 'track.php'
        ],
        [
            'title' => 'Feedback',
            'url' => baseUrl('customer/feedback.php'),
            'icon' => 'bi-star',
            'active' => $currentPage === 'feedback.php'
        ],
        [
            'title' => 'Profile',
            'url' => baseUrl('customer/profile.php'),
            'icon' => 'bi-person',
            'active' => $currentPage === 'profile.php'
        ]
    ];
    $navbarColor = 'bg-primary';
    $brandText = '<i class="bi bi-scissors"></i> TMS';
}
?>

<?php if (isLoggedIn() && !isset($hide_main_nav)): ?>
<nav class="navbar navbar-expand-lg navbar-dark <?php echo $navbarColor; ?>">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $dashboardUrl; ?>">
            <?php echo $brandText; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <!-- Global Search (for all roles except admin who has it in admin_nav) -->
            <?php if ($userRole !== 'admin'): ?>
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
            <?php endif; ?>
            
            <!-- Main Menu Items -->
            <ul class="navbar-nav <?php echo $userRole === 'admin' ? 'me-auto' : 'ms-auto'; ?>">
                <?php foreach ($menuItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>" 
                       href="<?php echo $item['url']; ?>">
                        <i class="bi <?php echo $item['icon']; ?>"></i> <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Right Side Items (Notifications, User Menu) -->
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <?php if ($userRole === 'customer'): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" id="notificationBell" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                                0
                            </span>
                        </a>
                    </li>
                <?php elseif ($userRole === 'staff'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>" 
                           href="<?php echo baseUrl('staff/notifications.php'); ?>">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo baseUrl($userRole . '/profile.php'); ?>">
                                <i class="bi bi-person"></i> Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo baseUrl('includes/logout.php'); ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

