<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($receiver_id > 0 && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $receiver_id, $subject, $message);
        
        if ($stmt->execute()) {
            // Create notification for admin
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($receiver_id, 'New Message', '$subject', 'message')");
        }
        $stmt->close();
    }
}

// Get admin user for messaging
$admin = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();

// Get messages (sent and received)
$messages = $conn->query("
    SELECT m.*, 
           sender.full_name as sender_name, sender.email as sender_email,
           receiver.full_name as receiver_name, receiver.email as receiver_email
    FROM messages m
    LEFT JOIN users sender ON m.sender_id = sender.id
    LEFT JOIN users receiver ON m.receiver_id = receiver.id
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    ORDER BY m.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Mark messages as read
$conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id");

closeDBConnection($conn);

$page_title = 'Messages';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-envelope"></i> Messages</h2>
        <?php if ($admin): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                <i class="bi bi-send"></i> Send Message
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <p class="text-muted text-center">No messages yet</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($messages as $msg): ?>
                            <div class="list-group-item message-item <?php echo !$msg['is_read'] && $msg['receiver_id'] == $user_id ? 'unread' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php if ($msg['sender_id'] == $user_id): ?>
                                                <i class="bi bi-arrow-up text-primary"></i> To: <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                            <?php else: ?>
                                                <i class="bi bi-arrow-down text-success"></i> From: <?php echo htmlspecialchars($msg['sender_name']); ?>
                                            <?php endif; ?>
                                        </h6>
                                        <?php if ($msg['subject']): ?>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($msg['subject']); ?></strong></p>
                                        <?php endif; ?>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <small class="text-muted"><?php echo date('F d, Y H:i', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($admin): ?>
    <!-- Send Message Modal -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Message to Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="send_message" value="1">
                        <input type="hidden" name="receiver_id" value="<?php echo $admin['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" placeholder="Message subject">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
