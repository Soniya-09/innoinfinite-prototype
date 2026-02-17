<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $base_price = floatval($_POST['base_price'] ?? 0);
        $duration_days = intval($_POST['duration_days'] ?? 0);
        
        if (empty($name)) {
            $error = 'Service name is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO services (name, description, category, base_price, duration_days) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdi", $name, $description, $category, $base_price, $duration_days);
            
            if ($stmt->execute()) {
                $success = 'Service added successfully';
            } else {
                $error = 'Failed to add service';
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $base_price = floatval($_POST['base_price'] ?? 0);
        $duration_days = intval($_POST['duration_days'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, category = ?, base_price = ?, duration_days = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssdisi", $name, $description, $category, $base_price, $duration_days, $status, $id);
        
        if ($stmt->execute()) {
            $success = 'Service updated successfully';
        } else {
            $error = 'Failed to update service';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = 'Service deleted successfully';
        } else {
            $error = 'Failed to delete service';
        }
        $stmt->close();
    }
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Manage Services';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-list-ul"></i> Manage Services</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="bi bi-plus-circle"></i> Add Service
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Base Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No services found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($service['description'], 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo htmlspecialchars($service['category']); ?></td>
                                <td>$<?php echo number_format($service['base_price'], 2); ?></td>
                                <td><?php echo $service['duration_days']; ?> days</td>
                                <td>
                                    <span class="badge bg-<?php echo $service['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this service?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category" placeholder="e.g., Development, Consulting">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Price ($)</label>
                            <input type="number" class="form-control" name="base_price" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (days)</label>
                            <input type="number" class="form-control" name="duration_days" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" name="category" id="edit_category">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Price ($)</label>
                            <input type="number" class="form-control" name="base_price" id="edit_base_price" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (days)</label>
                            <input type="number" class="form-control" name="duration_days" id="edit_duration_days" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editService(service) {
    document.getElementById('edit_id').value = service.id;
    document.getElementById('edit_name').value = service.name;
    document.getElementById('edit_description').value = service.description || '';
    document.getElementById('edit_category').value = service.category || '';
    document.getElementById('edit_base_price').value = service.base_price;
    document.getElementById('edit_duration_days').value = service.duration_days;
    document.getElementById('edit_status').value = service.status;
    
    const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
