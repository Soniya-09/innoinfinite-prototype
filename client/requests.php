<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get all requests for this client
$requests = $conn->query("
    SELECT sr.*, s.name as service_name,
           (SELECT COUNT(*) FROM proposals WHERE request_id = sr.id) as proposal_count,
           (SELECT COUNT(*) FROM projects WHERE request_id = sr.id) as project_count
    FROM service_requests sr 
    LEFT JOIN services s ON sr.service_id = s.id 
    WHERE sr.client_id = $user_id
    ORDER BY sr.requested_at DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'My Requests';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-inbox"></i> My Service Requests</h2>
        <a href="request_service.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Request
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No service requests yet</p>
                <a href="request_service.php" class="btn btn-primary">Submit Your First Request</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Proposals</th>
                            <th>Projects</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
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
                                <td>
                                    <?php if ($request['proposal_count'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $request['proposal_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['project_count'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $request['project_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <a href="request_detail.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
