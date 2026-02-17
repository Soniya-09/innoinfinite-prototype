<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get all projects for this client
$projects = $conn->query("
    SELECT p.*, sr.title as request_title
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    WHERE sr.client_id = $user_id
    ORDER BY p.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'My Projects';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-folder"></i> My Projects</h2>
    </div>
</div>

<?php if (empty($projects)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-folder" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No projects yet</p>
            <p class="text-muted">Projects will appear here once your service requests are approved and proposals are accepted.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars($project['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</p>
                        
                        <div class="mb-3">
                            <strong>Status:</strong> 
                            <span class="badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Progress:</strong>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress_percentage']; ?>%">
                                    <?php echo $project['progress_percentage']; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">
                                    <strong>Start:</strong><br>
                                    <?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <strong>End:</strong><br>
                                    <?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'Not set'; ?>
                                </small>
                            </div>
                        </div>
                        
                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-primary w-100">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
