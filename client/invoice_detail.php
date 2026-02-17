<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$invoice_id = intval($_GET['id'] ?? 0);
$pay_mode = isset($_GET['pay']);

if (!$invoice_id) {
    header('Location: invoices.php');
    exit();
}

// Get invoice details
$invoice = $conn->query("
    SELECT i.*, p.title as project_title, sr.client_id
    FROM invoices i
    JOIN projects p ON i.project_id = p.id
    JOIN service_requests sr ON p.request_id = sr.id
    WHERE i.id = $invoice_id AND sr.client_id = $user_id
")->fetch_assoc();

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

// Get payment history
$payments = $conn->query("SELECT * FROM payments WHERE invoice_id = $invoice_id ORDER BY payment_date DESC")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    
    if ($amount <= 0 || empty($payment_method)) {
        $error = 'Please fill in all required fields';
    } elseif ($amount > $invoice['total_amount']) {
        $error = 'Payment amount cannot exceed invoice total';
    } else {
        // Generate dummy transaction ID
        $transaction_id = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        $stmt = $conn->prepare("INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')");
        $stmt->bind_param("idss", $invoice_id, $amount, $payment_method, $transaction_id);
        
        if ($stmt->execute()) {
            // Update invoice status if fully paid
            $total_paid = $conn->query("SELECT SUM(amount) as total FROM payments WHERE invoice_id = $invoice_id AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
            
            if ($total_paid >= $invoice['total_amount']) {
                $conn->query("UPDATE invoices SET status = 'paid', paid_date = CURDATE() WHERE id = $invoice_id");
            }
            
            // Create notification for admin
            $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
            if ($admin) {
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$admin['id']}, 'Payment Received', 'A payment has been received for invoice {$invoice['invoice_number']}', 'payment')");
            }
            
            $success = 'Payment processed successfully! Transaction ID: ' . $transaction_id;
            header('refresh:2;url=invoices.php');
        } else {
            $error = 'Failed to process payment';
        }
        $stmt->close();
    }
}

closeDBConnection($conn);

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
                <h5>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Bill To:</h6>
                        <p>
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <?php echo htmlspecialchars($_SESSION['email']); ?>
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
    
    <?php if ($invoice['status'] === 'pending' && ($pay_mode || empty($payments))): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-credit-card"></i> Make Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="process_payment" value="1">
                        <div class="mb-3">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="<?php echo $invoice['total_amount']; ?>" value="<?php echo $invoice['total_amount']; ?>" required>
                            <small class="text-muted">Invoice Total: $<?php echo number_format($invoice['total_amount'], 2); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Note:</strong> This is a dummy payment module for demonstration purposes.
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Process Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
