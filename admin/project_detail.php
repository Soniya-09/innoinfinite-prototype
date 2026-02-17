<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$project_id = intval($_GET['id'] ?? 0);

if (!$project_id) {
    header('Location: projects.php');
    exit();
}

// Get project details
$project = $conn->query("
    SELECT p.*, sr.title as request_title, sr.client_id, u.full_name as client_name, u.email as client_email
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
    WHERE p.id = $project_id
")->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get milestones
$milestones = $conn->query("SELECT * FROM milestones WHERE project_id = $project_id ORDER BY due_date")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_project') {
        $status = $_POST['status'] ?? 'requested';
        $progress = intval($_POST['progress'] ?? 0);
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        $stmt = $conn->prepare("UPDATE projects SET status = ?, progress_percentage = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->bind_param("sissi", $status, $progress, $start_date, $end_date, $project_id);
        
        if ($stmt->execute()) {
            $success = 'Project updated successfully';
            $project['status'] = $status;
            $project['progress_percentage'] = $progress;
            $project['start_date'] = $start_date;
            $project['end_date'] = $end_date;
            
            // Create notification
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$project['client_id']}, 'Project Updated', 'Your project has been updated', 'project')");
        } else {
            $error = 'Failed to update project';
        }
        $stmt->close();
    } elseif ($action === 'add_milestone') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? null;
        
        if (empty($title)) {
            $error = 'Milestone title is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO milestones (project_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $project_id, $title, $description, $due_date);
            
            if ($stmt->execute()) {
                $success = 'Milestone added successfully';
                header('Location: project_detail.php?id=' . $project_id);
                exit();
            } else {
                $error = 'Failed to add milestone';
            }
            $stmt->close();
        }
    } elseif ($action === 'update_milestone') {
        $milestone_id = intval($_POST['milestone_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        
        $update_fields = "status = ?";
        if ($status === 'completed') {
            $update_fields .= ", completed_at = NOW()";
        }
        
        $conn->query("UPDATE milestones SET $update_fields WHERE id = $milestone_id");
        header('Location: project_detail.php?id=' . $project_id);
        exit();
    }
}

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
                        <th>Client:</th>
                        <td><?php echo htmlspecialchars($project['client_name']); ?> (<?php echo htmlspecialchars($project['client_email']); ?>)</td>
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
                            <div class="progress" style="height: 25px;">
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
        
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-list-check"></i> Milestones</h5>
            </div>
            <div class="card-body">
                <?php if (empty($milestones)): ?>
                    <p class="text-muted">No milestones added yet</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($milestones as $milestone): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><?php echo htmlspecialchars($milestone['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($milestone['description']); ?></p>
                                        <small class="text-muted">Due: <?php echo $milestone['due_date'] ? date('M d, Y', strtotime($milestone['due_date'])) : 'Not set'; ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $milestone['status'] === 'completed' ? 'success' : ($milestone['status'] === 'in_progress' ? 'primary' : 'secondary'); ?>">
                                            <?php echo ucfirst($milestone['status']); ?>
                                        </span>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_milestone">
                                            <input type="hidden" name="milestone_id" value="<?php echo $milestone['id']; ?>">
                                            <select name="status" class="form-select form-select-sm mt-2" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $milestone['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo $milestone['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="completed" <?php echo $milestone['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-pencil"></i> Update Project</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_project">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="requested" <?php echo $project['status'] === 'requested' ? 'selected' : ''; ?>>Requested</option>
                            <option value="approved" <?php echo $project['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Progress (%)</label>
                        <input type="number" class="form-control" name="progress" min="0" max="100" value="<?php echo $project['progress_percentage']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $project['start_date']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $project['end_date']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Update Project
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-plus-circle"></i> Add Milestone</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_milestone">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus"></i> Add Milestone
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
