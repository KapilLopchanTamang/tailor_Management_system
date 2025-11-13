<?php
/**
 * Customer Profile - Edit Details and Measurements
 * Tailoring Management System
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('customer');

$message = '';
$error = '';

// Get customer data
$customer = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        $error = 'Customer profile not found.';
    }
} catch (PDOException $e) {
    $error = 'Error fetching customer data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF token
    validateCSRF();
    
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Get measurements from form
    $measurements = [
        'bust' => sanitize($_POST['bust'] ?? ''),
        'waist' => sanitize($_POST['waist'] ?? ''),
        'hips' => sanitize($_POST['hips'] ?? ''),
        'shoulder' => sanitize($_POST['shoulder'] ?? ''),
        'sleeve_length' => sanitize($_POST['sleeve_length'] ?? ''),
        'shirt_length' => sanitize($_POST['shirt_length'] ?? ''),
        'pants_length' => sanitize($_POST['pants_length'] ?? ''),
        'notes' => sanitize($_POST['measurement_notes'] ?? '')
    ];
    
    // Remove empty values
    $measurements = array_filter($measurements, function($value) {
        return $value !== '';
    });
    
    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        try {
            $measurementsJson = !empty($measurements) ? json_encode($measurements) : null;
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, measurements = ?, notes = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $address, $measurementsJson, $notes, $_SESSION['user_id']]);
            $message = 'Profile updated successfully.';
            
            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $customer = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Parse measurements JSON
$measurements = [];
if ($customer && $customer['measurements']) {
    $measurements = json_decode($customer['measurements'], true) ?? [];
}

$pageTitle = "My Profile";
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> My Profile & Measurements</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="update_profile" value="1">
                        <?php echo csrfField(); ?>
                        
                        <h5 class="mb-3"><i class="bi bi-person"></i> Personal Information</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3"><i class="bi bi-rulers"></i> Measurements (in inches)</h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="bust" class="form-label">Bust</label>
                                <input type="number" class="form-control" id="bust" name="bust" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['bust'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="waist" class="form-label">Waist</label>
                                <input type="number" class="form-control" id="waist" name="waist" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['waist'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="hips" class="form-label">Hips</label>
                                <input type="number" class="form-control" id="hips" name="hips" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['hips'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="shoulder" class="form-label">Shoulder</label>
                                <input type="number" class="form-control" id="shoulder" name="shoulder" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['shoulder'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="sleeve_length" class="form-label">Sleeve Length</label>
                                <input type="number" class="form-control" id="sleeve_length" name="sleeve_length" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['sleeve_length'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="shirt_length" class="form-label">Shirt Length</label>
                                <input type="number" class="form-control" id="shirt_length" name="shirt_length" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['shirt_length'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="pants_length" class="form-label">Pants Length</label>
                                <input type="number" class="form-control" id="pants_length" name="pants_length" 
                                       step="0.1" min="0" 
                                       value="<?php echo htmlspecialchars($measurements['pants_length'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="measurement_notes" class="form-label">Measurement Notes</label>
                            <textarea class="form-control" id="measurement_notes" name="measurement_notes" rows="2"><?php echo htmlspecialchars($measurements['notes'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Any additional notes about your measurements</small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="<?php echo baseUrl('customer/dashboard.php'); ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
