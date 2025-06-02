<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

require_once 'db.php';

// Check if message ID is provided
if (!isset($_GET['id'])) {
    die("No message specified");
}

$messageId = (int)$_GET['id'];

try {
    // Get message details with sender information
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sender.username as sender_name,
               recipient.username as recipient_name
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users recipient ON m.recipient_id = recipient.id
        WHERE m.id = ? AND (m.sender_id = ? OR m.recipient_id = ?)
    ");
    $stmt->execute([$messageId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch();

    // Check if message exists and user has permission to view it
    if (!$message) {
        die("Message not found or access denied");
    }

    // Mark message as read if user is recipient
    if ($message['recipient_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$messageId]);
    }

    // Get message thread (all related messages)
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sender.username as sender_name
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        WHERE m.thread_id = ? OR m.id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$message['thread_id'] ?: $message['id'], $message['id']]);
    $thread = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Message view error: " . $e->getMessage());
    die("Error loading message");
}
?>

<div class="message-detail">
    <div class="message-header">
        <h2><?php echo htmlspecialchars($message['subject']); ?></h2>
        <div class="message-meta">
            <span class="from">From: <?php echo htmlspecialchars($message['sender_name']); ?></span>
            <span class="to">To: <?php echo htmlspecialchars($message['recipient_name']); ?></span>
            <span class="date">Date: <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
        </div>
    </div>

    <div class="message-thread">
        <?php foreach ($thread as $msg): ?>
            <div class="message-bubble <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                <div class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                <div class="message-time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Form -->
    <div class="reply-form">
        <h3>Reply</h3>
        <form id="replyForm" onsubmit="sendReply(event)">
            <input type="hidden" name="original_message_id" value="<?php echo $messageId; ?>">
            <div class="form-group">
                <textarea name="reply_message" rows="4" required placeholder="Type your reply..."></textarea>
            </div>
            <button type="submit" class="btn">Send Reply</button>
        </form>
    </div>
</div>

<script>
function sendReply(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('messages_reply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the message view to show the new reply
            viewMessage(<?php echo $messageId; ?>);
            form.reset();
        } else {
            alert(data.error || 'Failed to send reply');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send reply');
    });
}
</script>
