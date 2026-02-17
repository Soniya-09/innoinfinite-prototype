<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$request_id = intval($_GET['id'] ?? 0);

if (!$request_id) {
    header('Location: requests.php');
    exit();
}

// Get request details
$request = $conn->query("
    SELECT sr.*, s.name as service_name, s.base_price
    FROM service_requests sr 
    LEFT JOIN services s ON sr.service_id = s.id 
    WHERE sr.id = $request_id AND sr.client_id = $user_id
")->fetch_assoc();

if (!$request) {
    header('Location: requests.php');
    exit();
}

// Get proposals
$proposals = $conn->query("
    SELECT p.*, u.full_name as admin_name
    FROM proposals p
    LEFT JOIN users u ON p.admin_id = u.id
    WHERE p.request_id = $request_id
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Handle proposal response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_proposal'])) {
    $proposal_id = intval($_POST['proposal_id'] ?? 0);
    $response = $_POST['response'] ?? 'rejected';
    
    $stmt = $conn->prepare("UPDATE proposals SET status = ?, responded_at = NOW() WHERE id = ? AND request_id = ?");
    $stmt->bind_param("sii", $response, $proposal_id, $request_id);
    
    if ($stmt->execute()) {
        if ($response === 'accepted') {
            // Create project
            $conn->query("INSERT INTO projects (request_id, proposal_id, title, description, status) 
                         SELECT $request_id, $proposal_id, sr.title, sr.description, 'approved' 
                         FROM service_requests sr WHERE sr.id = $request_id");
            
            // Update request status
            $conn->query("UPDATE service_requests SET status = 'approved', approved_at = NOW() WHERE id = $request_id");
            
            // Create notification for admin
            $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
            if ($admin) {
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$admin['id']}, 'Proposal Accepted', 'A proposal has been accepted', 'proposal')");
            }
        }
        header('Location: request_detail.php?id=' . $request_id);
        exit();
    }
    $stmt->close();
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
        
        <?php if (!empty($proposals)): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-earmark-check"></i> Proposals</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($proposals as $proposal): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6><?php echo htmlspecialchars($proposal['title']); ?></h6>
                                        <small class="text-muted">From: <?php echo htmlspecialchars($proposal['admin_name']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $proposal['status'] === 'accepted' ? 'success' : ($proposal['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($proposal['status']); ?>
                                    </span>
                                </div>
                                
                                <p><?php echo nl2br(htmlspecialchars($proposal['description'])); ?></p>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Estimated Cost:</strong> $<?php echo number_format($proposal['estimated_cost'], 2); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Estimated Duration:</strong> <?php echo $proposal['estimated_duration']; ?> days
                                    </div>
                                </div>
                                
                                <?php if ($proposal['terms_conditions']): ?>
                                    <div class="mb-3">
                                        <strong>Terms & Conditions:</strong>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($proposal['terms_conditions'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($proposal['status'] === 'pending'): ?>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="respond_proposal" value="1">
                                        <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                        <div class="btn-group w-100">
                                            <button type="submit" name="response" value="accepted" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Accept Proposal
                                            </button>
                                            <button type="submit" name="response" value="rejected" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this proposal?');">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                
                                <small class="text-muted">Created: <?php echo date('F d, Y H:i', strtotime($proposal['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No proposals received yet. Please wait for the admin to review your request.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
