<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get statistics
$stats = [];

// Total requests
$result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE client_id = $user_id");
$stats['requests'] = $result->fetch_assoc()['count'];

// Active projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects p JOIN service_requests sr ON p.request_id = sr.id WHERE sr.client_id = $user_id AND p.status IN ('approved', 'in_progress')");
$stats['active_projects'] = $result->fetch_assoc()['count'];

// Pending invoices
$result = $conn->query("SELECT COUNT(*) as count FROM invoices i JOIN projects p ON i.project_id = p.id JOIN service_requests sr ON p.request_id = sr.id WHERE sr.client_id = $user_id AND i.status = 'pending'");
$stats['pending_invoices'] = $result->fetch_assoc()['count'];

// Unread messages
$result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND is_read = 0");
$stats['unread_messages'] = $result->fetch_assoc()['count'];

// Unread notifications
$result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
$stats['unread_notifications'] = $result->fetch_assoc()['count'];

// Recent requests
$recent_requests = $conn->query("
    SELECT sr.*, s.name as service_name 
    FROM service_requests sr 
    LEFT JOIN services s ON sr.service_id = s.id 
    WHERE sr.client_id = $user_id
    ORDER BY sr.requested_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent projects
$recent_projects = $conn->query("
    SELECT p.*, sr.title as request_title
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    WHERE sr.client_id = $user_id
    ORDER BY p.updated_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent notifications
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Client Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Client Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3><?php echo $stats['requests']; ?></h3>
        <p><i class="bi bi-inbox"></i> Total Requests</p>
    </div>
    <div class="stat-card success">
        <h3><?php echo $stats['active_projects']; ?></h3>
        <p><i class="bi bi-folder"></i> Active Projects</p>
    </div>
    <div class="stat-card warning">
        <h3><?php echo $stats['pending_invoices']; ?></h3>
        <p><i class="bi bi-receipt"></i> Pending Invoices</p>
    </div>
    <div class="stat-card info">
        <h3><?php echo $stats['unread_messages']; ?></h3>
        <p><i class="bi bi-envelope"></i> Unread Messages</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $stats['unread_notifications']; ?></h3>
        <p><i class="bi bi-bell"></i> Notifications</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-inbox"></i> Recent Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_requests)): ?>
                    <p class="text-muted">No requests yet</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h6>
                                        <p class="mb-1"><small><?php echo htmlspecialchars($request['service_name'] ?? 'N/A'); ?></small></p>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></small>
                                    </div>
                                    <span class="badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="requests.php" class="btn btn-sm btn-primary mt-3">View All</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-folder"></i> Recent Projects</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_projects)): ?>
                    <p class="text-muted">No projects yet</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_projects as $project): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h6>
                                        <div class="progress mb-2" style="height: 15px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress_percentage']; ?>%">
                                                <?php echo $project['progress_percentage']; ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo ucfirst($project['status']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="projects.php" class="btn btn-sm btn-primary mt-3">View All</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($notifications)): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-bell"></i> Recent Notifications</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-danger">New</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
