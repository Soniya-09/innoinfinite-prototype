<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();

// Get all active services
$services = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Browse Services';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-list-ul"></i> Browse Services</h2>
        <p class="text-muted">Explore our available consultancy services</p>
    </div>
</div>

<?php if (empty($services)): ?>
    <div class="alert alert-info">No services available at the moment.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($services as $service): ?>
            <div class="col-md-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-briefcase text-primary"></i> <?php echo htmlspecialchars($service['name']); ?>
                        </h5>
                        <?php if ($service['category']): ?>
                            <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($service['category']); ?></span>
                        <?php endif; ?>
                        <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                        <div class="mb-3">
                            <?php if ($service['base_price'] > 0): ?>
                                <p class="mb-1"><strong>Starting from:</strong> $<?php echo number_format($service['base_price'], 2); ?></p>
                            <?php endif; ?>
                            <?php if ($service['duration_days'] > 0): ?>
                                <p class="mb-0"><strong>Duration:</strong> <?php echo $service['duration_days']; ?> days</p>
                            <?php endif; ?>
                        </div>
                        <a href="request_service.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Request This Service
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
