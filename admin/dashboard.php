<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total clients
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
$stats['clients'] = $result->fetch_assoc()['count'];

// Total services
$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
$stats['services'] = $result->fetch_assoc()['count'];

// Pending requests
$result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'requested'");
$stats['pending_requests'] = $result->fetch_assoc()['count'];

// Active projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status IN ('approved', 'in_progress')");
$stats['active_projects'] = $result->fetch_assoc()['count'];

// Pending invoices
$result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
$stats['pending_invoices'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'paid'");
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Recent requests
$recent_requests = $conn->query("
    SELECT sr.*, u.full_name as client_name, s.name as service_name 
    FROM service_requests sr 
    LEFT JOIN users u ON sr.client_id = u.id 
    LEFT JOIN services s ON sr.service_id = s.id 
    ORDER BY sr.requested_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent projects
$recent_projects = $conn->query("
    SELECT p.*, sr.title as request_title, u.full_name as client_name
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
    ORDER BY p.updated_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3><?php echo $stats['clients']; ?></h3>
        <p><i class="bi bi-people"></i> Total Clients</p>
    </div>
    <div class="stat-card success">
        <h3><?php echo $stats['services']; ?></h3>
        <p><i class="bi bi-list-ul"></i> Active Services</p>
    </div>
    <div class="stat-card warning">
        <h3><?php echo $stats['pending_requests']; ?></h3>
        <p><i class="bi bi-inbox"></i> Pending Requests</p>
    </div>
    <div class="stat-card info">
        <h3><?php echo $stats['active_projects']; ?></h3>
        <p><i class="bi bi-folder"></i> Active Projects</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $stats['pending_invoices']; ?></h3>
        <p><i class="bi bi-receipt"></i> Pending Invoices</p>
    </div>
    <div class="stat-card success">
        <h3>$<?php echo number_format($stats['revenue'], 2); ?></h3>
        <p><i class="bi bi-currency-dollar"></i> Total Revenue</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-inbox"></i> Recent Service Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_requests)): ?>
                    <p class="text-muted">No recent requests</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['service_name'] ?? 'N/A'); ?></td>
                                        <td><span class="badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="requests.php" class="btn btn-sm btn-primary">View All</a>
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
                    <p class="text-muted">No recent projects</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Client</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                        <td><span class="badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>"><?php echo ucfirst($project['status']); ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress_percentage']; ?>%">
                                                    <?php echo $project['progress_percentage']; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
