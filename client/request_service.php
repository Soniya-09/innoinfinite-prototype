<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$service_id = intval($_GET['service_id'] ?? 0);

$error = '';
$success = '';

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    
    if (empty($title) || empty($description)) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO service_requests (client_id, service_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $service_id, $title, $description);
        
        if ($stmt->execute()) {
            // Create notification for admin
            $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
            if ($admin) {
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$admin['id']}, 'New Service Request', 'A new service request has been submitted', 'request')");
            }
            $success = 'Service request submitted successfully!';
            header('refresh:2;url=requests.php');
        } else {
            $error = 'Failed to submit request';
        }
        $stmt->close();
    }
}

// Get service details if service_id is provided
$service = null;
if ($service_id > 0) {
    $service = $conn->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
}

// Get all services for dropdown
$services = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Request Service';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <a href="services.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Services
        </a>
        <h2><i class="bi bi-plus-circle"></i> Request Service</h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Submit Service Request</h5>
            </div>
            <div class="card-body">
                <?php if ($service): ?>
                    <div class="alert alert-info">
                        <strong>Selected Service:</strong> <?php echo htmlspecialchars($service['name']); ?>
                        <?php if ($service['base_price'] > 0): ?>
                            <br><strong>Starting Price:</strong> $<?php echo number_format($service['base_price'], 2); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                        <select class="form-select" id="service_id" name="service_id" required>
                            <option value="">Select a service</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($service_id == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?> 
                                    <?php if ($s['base_price'] > 0): ?>
                                        - $<?php echo number_format($s['base_price'], 2); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Request Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Website Development for My Company">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="6" required placeholder="Please provide detailed information about your requirements..."></textarea>
                        <small class="text-muted">Include project scope, timeline expectations, and any specific requirements.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
