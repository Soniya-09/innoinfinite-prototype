<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Get statistics for reports
$stats = [];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'paid'");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Pending payments
$result = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['total'] ?? 0;

// Total clients
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
$stats['total_clients'] = $result->fetch_assoc()['count'];

// Active projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status IN ('approved', 'in_progress')");
$stats['active_projects'] = $result->fetch_assoc()['count'];

// Completed projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
$stats['completed_projects'] = $result->fetch_assoc()['count'];

// Revenue by month (last 6 months)
$revenue_by_month = $conn->query("
    SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, SUM(total_amount) as revenue
    FROM invoices
    WHERE status = 'paid' AND paid_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Top clients by revenue
$top_clients = $conn->query("
    SELECT u.full_name, u.company_name, SUM(i.total_amount) as total_spent, COUNT(i.id) as invoice_count
    FROM invoices i
    JOIN projects p ON i.project_id = p.id
    JOIN service_requests sr ON p.request_id = sr.id
    JOIN users u ON sr.client_id = u.id
    WHERE i.status = 'paid'
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Service popularity
$service_stats = $conn->query("
    SELECT s.name, COUNT(sr.id) as request_count, AVG(pr.estimated_cost) as avg_cost
    FROM services s
    LEFT JOIN service_requests sr ON s.id = sr.service_id
    LEFT JOIN proposals pr ON sr.id = pr.request_id
    GROUP BY s.id
    ORDER BY request_count DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Reports & Analytics';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
    </div>
</div>

<div class="dashboard-stats mb-4">
    <div class="stat-card success">
        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
        <p><i class="bi bi-currency-dollar"></i> Total Revenue</p>
    </div>
    <div class="stat-card warning">
        <h3>$<?php echo number_format($stats['pending_payments'], 2); ?></h3>
        <p><i class="bi bi-hourglass-split"></i> Pending Payments</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $stats['total_clients']; ?></h3>
        <p><i class="bi bi-people"></i> Total Clients</p>
    </div>
    <div class="stat-card info">
        <h3><?php echo $stats['active_projects']; ?></h3>
        <p><i class="bi bi-folder"></i> Active Projects</p>
    </div>
    <div class="stat-card success">
        <h3><?php echo $stats['completed_projects']; ?></h3>
        <p><i class="bi bi-check-circle"></i> Completed Projects</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-trophy"></i> Top Clients by Revenue</h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_clients)): ?>
                    <p class="text-muted">No data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Invoices</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_clients as $client): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($client['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($client['company_name'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo $client['invoice_count']; ?></td>
                                        <td><strong>$<?php echo number_format($client['total_spent'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-list-ul"></i> Service Statistics</h5>
            </div>
            <div class="card-body">
                <?php if (empty($service_stats)): ?>
                    <p class="text-muted">No data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Requests</th>
                                    <th>Avg. Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($service_stats as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $service['request_count']; ?></span></td>
                                        <td>$<?php echo number_format($service['avg_cost'] ?? 0, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bar-chart"></i> Revenue by Month (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($revenue_by_month)): ?>
                    <p class="text-muted">No revenue data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_revenue = max(array_column($revenue_by_month, 'revenue'));
                                foreach ($revenue_by_month as $month_data): 
                                    $percentage = $max_revenue > 0 ? ($month_data['revenue'] / $max_revenue) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month_data['month'] . '-01')); ?></td>
                                        <td><strong>$<?php echo number_format($month_data['revenue'], 2); ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
