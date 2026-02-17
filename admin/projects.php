<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Handle project status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id = intval($_POST['project_id'] ?? 0);
    $status = $_POST['status'] ?? 'requested';
    $progress = intval($_POST['progress'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE projects SET status = ?, progress_percentage = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $progress, $project_id);
    $stmt->execute();
    $stmt->close();
    
    // Create notification for client
    $project = $conn->query("SELECT sr.client_id FROM projects p JOIN service_requests sr ON p.request_id = sr.id WHERE p.id = $project_id")->fetch_assoc();
    if ($project) {
        $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$project['client_id']}, 'Project Updated', 'Your project status has been updated', 'project')");
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

$query = "
    SELECT p.*, sr.title as request_title, sr.client_id, u.full_name as client_name
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
";

if ($filter !== 'all') {
    $query .= " WHERE p.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $projects = $conn->query($query . " ORDER BY p.updated_at DESC")->fetch_all(MYSQLI_ASSOC);
}

closeDBConnection($conn);

$page_title = 'Manage Projects';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-folder"></i> Manage Projects</h2>
        <div>
            <a href="?filter=all" class="btn btn-sm btn-<?php echo $filter === 'all' ? 'primary' : 'outline-primary'; ?>">All</a>
            <a href="?filter=requested" class="btn btn-sm btn-<?php echo $filter === 'requested' ? 'primary' : 'outline-primary'; ?>">Requested</a>
            <a href="?filter=approved" class="btn btn-sm btn-<?php echo $filter === 'approved' ? 'primary' : 'outline-primary'; ?>">Approved</a>
            <a href="?filter=in_progress" class="btn btn-sm btn-<?php echo $filter === 'in_progress' ? 'primary' : 'outline-primary'; ?>">In Progress</a>
            <a href="?filter=completed" class="btn btn-sm btn-<?php echo $filter === 'completed' ? 'primary' : 'outline-primary'; ?>">Completed</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project Title</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No projects found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($project['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                <td>
                                    <span class="badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress_percentage']; ?>%">
                                            <?php echo $project['progress_percentage']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A'; ?></td>
                                <td><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
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
