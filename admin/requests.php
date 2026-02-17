<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $request_id = intval($_POST['request_id'] ?? 0);
        $status = $_POST['status'] ?? 'requested';
        
        $update_fields = "status = ?";
        $params = [$status];
        $types = "s";
        
        if ($status === 'approved') {
            $update_fields .= ", approved_at = NOW()";
        } elseif ($status === 'completed') {
            $update_fields .= ", completed_at = NOW()";
        }
        
        $stmt = $conn->prepare("UPDATE service_requests SET $update_fields WHERE id = ?");
        $params[] = $request_id;
        $types .= "i";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success = 'Request status updated successfully';
        } else {
            $error = 'Failed to update status';
        }
        $stmt->close();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

$query = "
    SELECT sr.*, u.full_name as client_name, u.email as client_email, s.name as service_name 
    FROM service_requests sr 
    LEFT JOIN users u ON sr.client_id = u.id 
    LEFT JOIN services s ON sr.service_id = s.id 
";

if ($filter !== 'all') {
    $query .= " WHERE sr.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $requests = $conn->query($query . " ORDER BY sr.requested_at DESC")->fetch_all(MYSQLI_ASSOC);
}

closeDBConnection($conn);

$page_title = 'Service Requests';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-inbox"></i> Service Requests</h2>
        <div>
            <a href="?filter=all" class="btn btn-sm btn-<?php echo $filter === 'all' ? 'primary' : 'outline-primary'; ?>">All</a>
            <a href="?filter=requested" class="btn btn-sm btn-<?php echo $filter === 'requested' ? 'primary' : 'outline-primary'; ?>">Requested</a>
            <a href="?filter=approved" class="btn btn-sm btn-<?php echo $filter === 'approved' ? 'primary' : 'outline-primary'; ?>">Approved</a>
            <a href="?filter=in_progress" class="btn btn-sm btn-<?php echo $filter === 'in_progress' ? 'primary' : 'outline-primary'; ?>">In Progress</a>
            <a href="?filter=completed" class="btn btn-sm btn-<?php echo $filter === 'completed' ? 'primary' : 'outline-primary'; ?>">Completed</a>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Title</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No requests found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['client_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['client_email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($request['description'], 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo htmlspecialchars($request['service_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($request['status'] === 'requested'): ?>
                                        <button type="button" class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $request['id']; ?>, 'approved')">
                                            <i class="bi bi-check"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $request['id']; ?>, 'rejected')">
                                            <i class="bi bi-x"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="request_id" id="status_request_id">
    <input type="hidden" name="status" id="status_value">
</form>

<script>
function updateStatus(requestId, status) {
    if (confirm('Are you sure you want to ' + status + ' this request?')) {
        document.getElementById('status_request_id').value = requestId;
        document.getElementById('status_value').value = status;
        document.getElementById('statusForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
