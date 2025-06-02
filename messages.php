<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error = '';
$success = '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = trim($_POST['recipient']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    try {
        // Validate recipient exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$recipient]);
        $recipientId = $stmt->fetchColumn();
        
        if ($recipientId) {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $recipientId, $subject, $message]);
            $success = "Message sent successfully!";
        } else {
            $error = "Recipient not found.";
        }
    } catch (PDOException $e) {
        error_log("Message send error: " . $e->getMessage());
        $error = "Failed to send message. Please try again.";
    }
}

include 'header.php';
?>

<div class="messages-container">
    <!-- Messages Sidebar -->
    <div class="message-sidebar">
        <button id="compose-btn" class="btn" onclick="showComposeForm()">Compose Message</button>
        
        <div class="message-filters">
            <button class="filter-btn active" onclick="filterMessages('inbox')">Inbox</button>
            <button class="filter-btn" onclick="filterMessages('sent')">Sent</button>
        </div>

        <div class="message-list">
            <?php
            try {
                // Get inbox messages
                $stmt = $pdo->prepare("
                    SELECT m.*, u.username as sender_name 
                    FROM messages m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE m.recipient_id = ? 
                    ORDER BY m.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $messages = $stmt->fetchAll();

                if ($messages) {
                    foreach ($messages as $msg) {
                        $unreadClass = $msg['is_read'] ? '' : 'unread';
                        echo "<div class='message-item {$unreadClass}' onclick='viewMessage({$msg['id']})'>";
                        echo "<div class='message-sender'>" . htmlspecialchars($msg['sender_name']) . "</div>";
                        echo "<div class='message-subject'>" . htmlspecialchars($msg['subject']) . "</div>";
                        echo "<div class='message-preview'>" . htmlspecialchars(substr($msg['message'], 0, 50)) . "...</div>";
                        echo "<div class='message-date'>" . date('M j, Y', strtotime($msg['created_at'])) . "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p class='no-messages'>No messages in inbox</p>";
                }
            } catch (PDOException $e) {
                error_log("Message list error: " . $e->getMessage());
                echo "<p class='error'>Unable to load messages</p>";
            }
            ?>
        </div>
    </div>

    <!-- Message Content Area -->
    <div class="message-content">
        <!-- Compose Message Form -->
        <div id="compose-form" class="compose-message" style="display: none;">
            <h2>New Message</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="recipient">To:</label>
                    <input type="text" id="recipient" name="recipient" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="6" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Send Message</button>
                    <button type="button" class="btn btn-secondary" onclick="hideComposeForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Message View Area -->
        <div id="message-view"></div>
    </div>
</div>

<script>
function showComposeForm() {
    document.getElementById('compose-form').style.display = 'block';
    document.getElementById('message-view').style.display = 'none';
}

function hideComposeForm() {
    document.getElementById('compose-form').style.display = 'none';
}

function viewMessage(messageId) {
    // AJAX request to get message content
    fetch(`messages_view.php?id=${messageId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('message-view').innerHTML = html;
            document.getElementById('message-view').style.display = 'block';
            document.getElementById('compose-form').style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function filterMessages(type) {
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // AJAX request to get filtered messages
    fetch(`messages_filter.php?type=${type}`)
        .then(response => response.text())
        .then(html => {
            document.querySelector('.message-list').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
        });
}
</script>

<?php include 'footer.php'; ?>
