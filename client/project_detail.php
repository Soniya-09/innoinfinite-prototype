<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$project_id = intval($_GET['id'] ?? 0);

if (!$project_id) {
    header('Location: projects.php');
    exit();
}

// Get project details
$project = $conn->query("
    SELECT p.*, sr.title as request_title, sr.client_id
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    WHERE p.id = $project_id AND sr.client_id = $user_id
")->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get milestones
$milestones = $conn->query("SELECT * FROM milestones WHERE project_id = $project_id ORDER BY due_date")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Project Details';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <a href="projects.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Projects
        </a>
        <h2><i class="bi bi-folder"></i> Project Details</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Project Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Project ID:</th>
                        <td>#<?php echo $project['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Title:</th>
                        <td><strong><?php echo htmlspecialchars($project['title']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo nl2br(htmlspecialchars($project['description'])); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Progress:</th>
                        <td>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress_percentage']; ?>%">
                                    <?php echo $project['progress_percentage']; ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Start Date:</th>
                        <td><?php echo $project['start_date'] ? date('F d, Y', strtotime($project['start_date'])) : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <th>End Date:</th>
                        <td><?php echo $project['end_date'] ? date('F d, Y', strtotime($project['end_date'])) : 'Not set'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($milestones)): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-check"></i> Project Milestones</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($milestones as $milestone): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><?php echo htmlspecialchars($milestone['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($milestone['description']); ?></p>
                                        <small class="text-muted">Due: <?php echo $milestone['due_date'] ? date('M d, Y', strtotime($milestone['due_date'])) : 'Not set'; ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $milestone['status'] === 'completed' ? 'success' : ($milestone['status'] === 'in_progress' ? 'primary' : 'secondary'); ?>">
                                        <?php echo ucfirst($milestone['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
