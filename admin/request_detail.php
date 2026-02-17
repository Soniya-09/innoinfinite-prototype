<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$request_id = intval($_GET['id'] ?? 0);

if (!$request_id) {
    header('Location: requests.php');
    exit();
}

// Get request details
$stmt = $conn->prepare("
    SELECT sr.*, u.full_name as client_name, u.email as client_email, u.phone as client_phone, 
           u.company_name, s.name as service_name, s.base_price
    FROM service_requests sr 
    LEFT JOIN users u ON sr.client_id = u.id 
    LEFT JOIN services s ON sr.service_id = s.id 
    WHERE sr.id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    header('Location: requests.php');
    exit();
}

// Get existing proposal if any
$proposal = $conn->query("SELECT * FROM proposals WHERE request_id = $request_id")->fetch_assoc();

// Get project if exists
$project = $conn->query("SELECT * FROM projects WHERE request_id = $request_id")->fetch_assoc();

$error = '';
$success = '';

// Handle proposal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_proposal'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $estimated_cost = floatval($_POST['estimated_cost'] ?? 0);
    $estimated_duration = intval($_POST['estimated_duration'] ?? 0);
    $terms_conditions = trim($_POST['terms_conditions'] ?? '');
    $admin_id = getCurrentUserId();
    
    if (empty($title) || $estimated_cost <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO proposals (request_id, admin_id, title, description, estimated_cost, estimated_duration, terms_conditions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdis", $request_id, $admin_id, $title, $description, $estimated_cost, $estimated_duration, $terms_conditions);
        
        if ($stmt->execute()) {
            $success = 'Proposal created successfully';
            // Update request status
            $conn->query("UPDATE service_requests SET status = 'approved', approved_at = NOW() WHERE id = $request_id");
            // Create notification
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$request['client_id']}, 'New Proposal', 'A new proposal has been created for your request', 'proposal')");
            header('Location: request_detail.php?id=' . $request_id);
            exit();
        } else {
            $error = 'Failed to create proposal';
        }
        $stmt->close();
    }
}

closeDBConnection($conn);

$page_title = 'Request Details';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <a href="requests.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Requests
        </a>
        <h2><i class="bi bi-file-text"></i> Request Details</h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Request Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Request ID:</th>
                        <td>#<?php echo $request['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Title:</th>
                        <td><strong><?php echo htmlspecialchars($request['title']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo nl2br(htmlspecialchars($request['description'])); ?></td>
                    </tr>
                    <tr>
                        <th>Service:</th>
                        <td><?php echo htmlspecialchars($request['service_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Requested Date:</th>
                        <td><?php echo date('F d, Y H:i', strtotime($request['requested_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Client Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Name:</th>
                        <td><?php echo htmlspecialchars($request['client_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($request['client_email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?php echo htmlspecialchars($request['client_phone'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Company:</th>
                        <td><?php echo htmlspecialchars($request['company_name'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($proposal): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-file-earmark-check"></i> Proposal</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="200">Title:</th>
                            <td><?php echo htmlspecialchars($proposal['title']); ?></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($proposal['description'])); ?></td>
                        </tr>
                        <tr>
                            <th>Estimated Cost:</th>
                            <td><strong>$<?php echo number_format($proposal['estimated_cost'], 2); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Estimated Duration:</th>
                            <td><?php echo $proposal['estimated_duration']; ?> days</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?php echo $proposal['status'] === 'accepted' ? 'success' : ($proposal['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($proposal['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($proposal['terms_conditions']): ?>
                            <tr>
                                <th>Terms & Conditions:</th>
                                <td><?php echo nl2br(htmlspecialchars($proposal['terms_conditions'])); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <?php if (!$proposal && $request['status'] === 'requested'): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-earmark-plus"></i> Create Proposal</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="create_proposal" value="1">
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimated Cost ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="estimated_cost" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimated Duration (days)</label>
                            <input type="number" class="form-control" name="estimated_duration" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Terms & Conditions</label>
                            <textarea class="form-control" name="terms_conditions" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Create & Send Proposal
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($project): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-folder"></i> Project</h5>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> 
                        <span class="badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </p>
                    <p><strong>Progress:</strong> <?php echo $project['progress_percentage']; ?>%</p>
                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-primary w-100">
                        View Project
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
