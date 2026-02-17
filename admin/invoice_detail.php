<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$conn = getDBConnection();
$invoice_id = intval($_GET['id'] ?? 0);

if (!$invoice_id) {
    header('Location: invoices.php');
    exit();
}

// Get invoice details
$invoice = $conn->query("
    SELECT i.*, p.title as project_title, sr.client_id, u.full_name as client_name, 
           u.email as client_email, u.phone as client_phone, u.company_name, u.address
    FROM invoices i
    LEFT JOIN projects p ON i.project_id = p.id
    LEFT JOIN service_requests sr ON p.request_id = sr.id
    LEFT JOIN users u ON sr.client_id = u.id
    WHERE i.id = $invoice_id
")->fetch_assoc();

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

// Get payment history
$payments = $conn->query("SELECT * FROM payments WHERE invoice_id = $invoice_id ORDER BY payment_date DESC")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Invoice Details';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <a href="invoices.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Invoices
        </a>
        <h2><i class="bi bi-receipt"></i> Invoice Details</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Bill To:</h6>
                        <p>
                            <strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong><br>
                            <?php if ($invoice['company_name']): ?>
                                <?php echo htmlspecialchars($invoice['company_name']); ?><br>
                            <?php endif; ?>
                            <?php if ($invoice['address']): ?>
                                <?php echo nl2br(htmlspecialchars($invoice['address'])); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($invoice['client_email']); ?><br>
                            <?php if ($invoice['client_phone']): ?>
                                <?php echo htmlspecialchars($invoice['client_phone']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Status:</strong> 
                            <span class="badge status-<?php echo $invoice['status']; ?>">
                                <?php echo ucfirst($invoice['status']); ?>
                            </span>
                        </p>
                        <p><strong>Issued Date:</strong> <?php echo date('F d, Y', strtotime($invoice['issued_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <strong>Project:</strong> <?php echo htmlspecialchars($invoice['project_title']); ?>
                </div>
                
                <?php if ($invoice['description']): ?>
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <?php echo nl2br(htmlspecialchars($invoice['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Service Amount</td>
                            <td class="text-end">$<?php echo number_format($invoice['amount'], 2); ?></td>
                        </tr>
                        <?php if ($invoice['tax_amount'] > 0): ?>
                            <tr>
                                <td>Tax</td>
                                <td class="text-end">$<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="table-primary">
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if (!empty($payments)): ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-credit-card"></i> Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
