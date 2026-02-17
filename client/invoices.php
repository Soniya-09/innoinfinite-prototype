<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get all invoices for this client
$invoices = $conn->query("
    SELECT i.*, p.title as project_title
    FROM invoices i
    JOIN projects p ON i.project_id = p.id
    JOIN service_requests sr ON p.request_id = sr.id
    WHERE sr.client_id = $user_id
    ORDER BY i.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = 'My Invoices';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-receipt"></i> My Invoices</h2>
    </div>
</div>

<?php if (empty($invoices)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No invoices yet</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Project</th>
                            <th>Amount</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($invoice['project_title']); ?></td>
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
                                    <?php if ($invoice['status'] === 'pending'): ?>
                                        <a href="invoice_detail.php?id=<?php echo $invoice['id']; ?>&pay=1" class="btn btn-sm btn-success">
                                            <i class="bi bi-credit-card"></i> Pay
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
