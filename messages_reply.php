<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

require_once 'db.php';

// Check if required data is provided
if (!isset($_POST['original_message_id']) || !isset($_POST['reply_message'])) {
    die(json_encode(['success' => false, 'error' => 'Missing required data']));
}

$originalMessageId = (int)$_POST['original_message_id'];
$replyMessage = trim($_POST['reply_message']);

if (empty($replyMessage)) {
    die(json_encode(['success' => false, 'error' => 'Reply message cannot be empty']));
}

try {
    // Get original message details
    $stmt = $pdo->prepare("
        SELECT id, sender_id, recipient_id, subject, thread_id 
        FROM messages 
        WHERE id = ? AND (sender_id = ? OR recipient_id = ?)
    ");
    $stmt->execute([$originalMessageId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $originalMessage = $stmt->fetch();

    if (!$originalMessage) {
        die(json_encode(['success' => false, 'error' => 'Original message not found']));
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Determine thread_id
    $threadId = $originalMessage['thread_id'] ?: $originalMessage['id'];

    // Create reply message
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            sender_id, 
            recipient_id, 
            subject, 
            message, 
            thread_id, 
            created_at
        ) VALUES (
            ?, 
            ?, 
            ?, 
            ?, 
            ?, 
            NOW()
        )
    ");

    // Set recipient as the other party from the original message
    $recipientId = ($originalMessage['sender_id'] == $_SESSION['user_id']) 
        ? $originalMessage['recipient_id'] 
        : $originalMessage['sender_id'];

    $subject = "Re: " . preg_replace('/^Re: /', '', $originalMessage['subject']);

    $stmt->execute([
        $_SESSION['user_id'],
        $recipientId,
        $subject,
        $replyMessage,
        $threadId
    ]);

    // Update thread_id of original message if it doesn't have one
    if (!$originalMessage['thread_id']) {
        $stmt = $pdo->prepare("UPDATE messages SET thread_id = ? WHERE id = ?");
        $stmt->execute([$threadId, $originalMessage['id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Reply error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send reply. Please try again.'
    ]);
}
