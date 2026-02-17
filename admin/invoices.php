<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';

// Handle invoice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $project_id = intval($_POST['project_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    
    if ($project_id <= 0 || $amount <= 0 || empty($due_date)) {
        $error = 'Please fill in all required fields';
    } else {
        $total_amount = $amount + $tax_amount;
        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($project_id, 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO invoices (project_id, invoice_number, amount, tax_amount, total_amount, description, issued_date, due_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)");
        $stmt->bind_param("isddds", $project_id, $invoice_number, $amount, $tax_amount, $total_amount, $description, $due_date);
        
        if ($stmt->execute()) {
            $invoice_id = $stmt->insert_id;
            // Create notification for client
            $project = $conn->query("SELECT sr.client_id FROM projects p JOIN service_requests sr ON p.request_id = sr.id WHERE p.id = $project_id")->fetch_assoc();
            if ($project) {
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$project['client_id']}, 'New Invoice', 'A new invoice has been generated for your project', 'invoice')");
            }
            $success = 'Invoice created successfully';
        } else {
            $error = 'Failed to create invoice';
        }
        $stmt->close();
    }
}

// Get all invoices
$invoices = $conn->query("
    SELECT i.*, p.title as project_title, sr.client_id, u.full_name as client_name
    FROM invoices i
    LEFT JOIN projects p ON i.project_id = p.id
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
    ORDER BY i.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get projects for dropdown
$projects = $conn->query("
    SELECT p.id, p.title, sr.client_id, u.full_name as client_name
    FROM projects p
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
    WHERE p.status IN ('approved', 'in_progress')
    ORDER BY p.title
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'Manage Invoices';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-receipt"></i> Manage Invoices</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
            <i class="bi bi-plus-circle"></i> Create Invoice
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
                        <th>Invoice #</th>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Issued</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No invoices found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($invoice['project_title']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge status-<?php echo $invoice['status']; ?>">
                                        <?php echo ucfirst($invoice['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($invoice['issued_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                <td>
                                    <a href="invoice_detail.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">
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

<!-- Create Invoice Modal -->
<div class="modal fade" id="createInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="create_invoice" value="1">
                    <div class="mb-3">
                        <label class="form-label">Project <span class="text-danger">*</span></label>
                        <select class="form-select" name="project_id" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>">
                                    <?php echo htmlspecialchars($project['title']); ?> - <?php echo htmlspecialchars($project['client_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Amount ($)</label>
                        <input type="number" class="form-control" name="tax_amount" step="0.01" min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
