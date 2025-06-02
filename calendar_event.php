<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

require_once 'db.php';

// Check if event ID is provided
if (!isset($_GET['id'])) {
    die("No event specified");
}

$eventId = (int)$_GET['id'];

try {
    // Get event details with creator information
    $stmt = $pdo->prepare("
        SELECT e.*, u.username as creator_name
        FROM events e
        JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        die("Event not found");
    }

    // Format date and time
    $date = date('F j, Y', strtotime($event['date']));
    $startTime = date('g:i A', strtotime($event['start_time']));
    $endTime = date('g:i A', strtotime($event['end_time']));
?>

<div class="event-details">
    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
    
    <div class="event-meta">
        <div class="meta-item">
            <strong>Date:</strong> 
            <?php echo $date; ?>
        </div>
        
        <div class="meta-item">
            <strong>Time:</strong> 
            <?php echo $startTime . ' - ' . $endTime; ?>
        </div>
        
        <div class="meta-item">
            <strong>Created by:</strong> 
            <?php echo htmlspecialchars($event['creator_name']); ?>
        </div>
    </div>

    <?php if (!empty($event['description'])): ?>
        <div class="event-description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($event['created_by'] == $_SESSION['user_id'] || isset($_SESSION['is_admin'])): ?>
        <div class="event-actions">
            <button class="btn btn-small" onclick="editEvent(<?php echo $event['id']; ?>)">Edit</button>
            <button class="btn btn-small btn-danger" onclick="deleteEvent(<?php echo $event['id']; ?>)">Delete</button>
        </div>
    <?php endif; ?>
</div>

<script>
function editEvent(eventId) {
    // Redirect to edit page or show edit modal
    window.location.href = `calendar_edit.php?id=${eventId}`;
}

function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event?')) {
        fetch('calendar_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${eventId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to delete event');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete event');
        });
    }
}
</script>

<?php
} catch (PDOException $e) {
    error_log("Event details error: " . $e->getMessage());
    echo "<p class='error'>Failed to load event details</p>";
}
?>
