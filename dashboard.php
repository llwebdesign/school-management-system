<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

try {
    // Get unread messages count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadMessages = $stmt->fetch()['count'];

    // Get recent files
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM files WHERE uploaded_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$_SESSION['user_id']]);
    $recentFiles = $stmt->fetch()['count'];

    // Get upcoming events
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute();
    $upcomingEvents = $stmt->fetch()['count'];

    // Get latest news count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM news WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $latestNews = $stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

include 'header.php';
?>

<div class="dashboard">
    <!-- Messages Card -->
    <div class="card">
        <h3>Messages</h3>
        <div class="card-content">
            <div class="stat-number"><?php echo $unreadMessages ?? 0; ?></div>
            <p>Unread Messages</p>
            <a href="messages.php" class="btn">View Messages</a>
        </div>
    </div>

    <!-- Files Card -->
    <div class="card">
        <h3>Files</h3>
        <div class="card-content">
            <div class="stat-number"><?php echo $recentFiles ?? 0; ?></div>
            <p>Recent Files</p>
            <a href="filesharing.php" class="btn">Manage Files</a>
        </div>
    </div>

    <!-- News Card -->
    <div class="card">
        <h3>News</h3>
        <div class="card-content">
            <div class="stat-number"><?php echo $latestNews ?? 0; ?></div>
            <p>New Updates</p>
            <a href="news.php" class="btn">Read News</a>
        </div>
    </div>

    <!-- Calendar Card -->
    <div class="card">
        <h3>Calendar</h3>
        <div class="card-content">
            <div class="stat-number"><?php echo $upcomingEvents ?? 0; ?></div>
            <p>Upcoming Events</p>
            <a href="calendar.php" class="btn">View Calendar</a>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="recent-activity">
    <div class="section-header">
        <h2>Recent Activity</h2>
    </div>
    <div class="activity-list">
        <?php
        try {
            // Get recent activities (combined from messages, files, and events)
            $query = "
                (SELECT 'message' as type, subject as title, created_at 
                FROM messages 
                WHERE recipient_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5)
                UNION ALL
                (SELECT 'file' as type, filename as title, created_at 
                FROM files 
                WHERE uploaded_by = ? 
                ORDER BY created_at DESC 
                LIMIT 5)
                UNION ALL
                (SELECT 'event' as type, title, date as created_at 
                FROM events 
                WHERE date >= CURDATE() 
                ORDER BY date ASC 
                LIMIT 5)
                ORDER BY created_at DESC
                LIMIT 10
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
            $activities = $stmt->fetchAll();

            if ($activities) {
                foreach ($activities as $activity) {
                    $icon = match($activity['type']) {
                        'message' => '✉️',
                        'file' => '📄',
                        'event' => '📅',
                        default => '📌'
                    };
                    echo '<div class="activity-item">';
                    echo '<span class="activity-icon">' . $icon . '</span>';
                    echo '<div class="activity-details">';
                    echo '<p class="activity-title">' . htmlspecialchars($activity['title']) . '</p>';
                    echo '<p class="activity-time">' . date('M j, Y g:i A', strtotime($activity['created_at'])) . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="no-activity">No recent activity</p>';
            }
        } catch (PDOException $e) {
            error_log("Activity error: " . $e->getMessage());
            echo '<p class="error">Unable to load recent activities</p>';
        }
        ?>
    </div>
</div>

<?php include 'footer.php'; ?>
