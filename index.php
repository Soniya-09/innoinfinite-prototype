<?php
require_once __DIR__ . '/config/config.php';

$page_title = 'Home';
include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="jumbotron bg-primary text-white p-5 rounded mb-4">
            <h1 class="display-4"><i class="bi bi-briefcase"></i> Welcome to Innoinfinite Solutions</h1>
            <p class="lead">Streamline your software consultancy operations with our comprehensive management platform.</p>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.3);">
            <p>Manage projects, clients, invoices, and communications all in one place.</p>
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-light btn-lg" href="auth/login.php" role="button">
                    <i class="bi bi-box-arrow-in-right"></i> Get Started
                </a>
            <?php else: ?>
                <a class="btn btn-light btn-lg" href="<?php echo isAdmin() ? 'admin/dashboard.php' : 'client/dashboard.php'; ?>" role="button">
                    <i class="bi bi-speedometer2"></i> Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people-fill text-primary" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Client Management</h5>
                <p class="card-text">Efficiently manage client relationships, onboarding, and communications.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-folder-fill text-success" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Project Tracking</h5>
                <p class="card-text">Track project progress, milestones, and deliverables in real-time.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-receipt-cutoff text-warning" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Invoice Management</h5>
                <p class="card-text">Generate invoices, track payments, and manage financial records.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-shield-check"></i> For Administrators</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> Manage consultancy services</li>
                    <li><i class="bi bi-check-circle text-success"></i> Client onboarding and management</li>
                    <li><i class="bi bi-check-circle text-success"></i> Review and approve service requests</li>
                    <li><i class="bi bi-check-circle text-success"></i> Create proposals and quotations</li>
                    <li><i class="bi bi-check-circle text-success"></i> Track project workflows</li>
                    <li><i class="bi bi-check-circle text-success"></i> Generate invoices and manage payments</li>
                    <li><i class="bi bi-check-circle text-success"></i> View reports and analytics</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-badge"></i> For Clients</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> Browse available services</li>
                    <li><i class="bi bi-check-circle text-success"></i> Submit service requests</li>
                    <li><i class="bi bi-check-circle text-success"></i> View and respond to proposals</li>
                    <li><i class="bi bi-check-circle text-success"></i> Track project progress</li>
                    <li><i class="bi bi-check-circle text-success"></i> View invoices and payment status</li>
                    <li><i class="bi bi-check-circle text-success"></i> Make payments securely</li>
                    <li><i class="bi bi-check-circle text-success"></i> Communicate with the company</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
