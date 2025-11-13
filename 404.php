<?php
/**
 * Custom 404 Error Page
 * Tailoring Management System
 */
http_response_code(404);
$pageTitle = "404 - Page Not Found";
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
$isLoggedIn = function_exists('isLoggedIn') && isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - TMS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .error-message {
            font-size: 1.5rem;
            margin: 1rem 0;
        }
        .error-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">
                <i class="bi bi-exclamation-triangle"></i> Page Not Found
            </div>
            <div class="error-description">
                The page you are looking for does not exist or has been moved.
            </div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <?php if ($isLoggedIn && isset($_SESSION['user_role'])): ?>
                    <a href="<?php echo baseUrl($_SESSION['user_role'] . '/dashboard.php'); ?>" class="btn btn-light btn-lg">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo baseUrl('login.php'); ?>" class="btn btn-light btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Go to Login
                    </a>
                <?php endif; ?>
                <a href="javascript:history.back()" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
                <a href="<?php echo baseUrl(); ?>" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

