<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'client'");
    $stmt->bind_param("si", $status, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get all clients
$clients = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM service_requests WHERE client_id = u.id) as total_requests,
           (SELECT COUNT(*) FROM projects p 
            JOIN service_requests sr ON p.request_id = sr.id 
            WHERE sr.client_id = u.id) as total_projects
    FROM users u 
    WHERE u.role = 'client' 
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Manage Clients';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-people"></i> Manage Clients</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Requests</th>
                        <th>Projects</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No clients found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['full_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($client['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($client['company_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-info"><?php echo $client['total_requests']; ?></span></td>
                                <td><span class="badge bg-primary"><?php echo $client['total_projects']; ?></span></td>
                                <td>
                                    <span class="badge bg-<?php echo $client['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($client['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $client['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                            <option value="active" <?php echo $client['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $client['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
