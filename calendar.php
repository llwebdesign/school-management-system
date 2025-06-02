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

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (
                    title,
                    description,
                    date,
                    start_time,
                    end_time,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $title,
                $description,
                $date,
                $start_time,
                $end_time,
                $_SESSION['user_id']
            ]);
            
            $success = "Event created successfully!";
            
        } catch (PDOException $e) {
            error_log("Event creation error: " . $e->getMessage());
            $error = "Failed to create event.";
        }
    }
}

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Ensure valid month/year
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

include 'header.php';
?>

<div class="calendar-container">
    <!-- Calendar Header -->
    <div class="calendar-header">
        <div class="calendar-nav">
            <a href="?month=<?php echo $month-1; ?>&year=<?php echo $year; ?>" class="btn btn-small">< Previous</a>
            <h2><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h2>
            <a href="?month=<?php echo $month+1; ?>&year=<?php echo $year; ?>" class="btn btn-small">Next ></a>
        </div>
        <button class="btn" onclick="showAddEventModal()">Add Event</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Calendar Grid -->
    <div class="calendar-grid">
        <!-- Day headers -->
        <div class="calendar-day-header">Sun</div>
        <div class="calendar-day-header">Mon</div>
        <div class="calendar-day-header">Tue</div>
        <div class="calendar-day-header">Wed</div>
        <div class="calendar-day-header">Thu</div>
        <div class="calendar-day-header">Fri</div>
        <div class="calendar-day-header">Sat</div>

        <?php
        // Get first day of the month
        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = date('t', $firstDay);
        $startingDay = date('w', $firstDay);

        // Get events for this month
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM events 
                WHERE DATE_FORMAT(date, '%Y-%m') = ?
                ORDER BY date, start_time
            ");
            $stmt->execute([date('Y-m', $firstDay)]);
            $events = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Calendar events error: " . $e->getMessage());
            $events = [];
        }

        // Add empty cells for days before start of month
        for ($i = 0; $i < $startingDay; $i++) {
            echo "<div class='calendar-day empty'></div>";
        }

        // Add days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $isToday = $currentDate === date('Y-m-d');
            $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';

            echo "<div class='$dayClass'>";
            echo "<div class='day-number'>$day</div>";

            // Display events for this day
            if (isset($events[$currentDate])) {
                echo "<div class='day-events'>";
                foreach ($events[$currentDate] as $event) {
                    $timeStr = date('g:ia', strtotime($event['start_time']));
                    echo "<div class='event' onclick='showEventDetails({$event['id']})'>";
                    echo "<div class='event-time'>$timeStr</div>";
                    echo "<div class='event-title'>" . htmlspecialchars($event['title']) . "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }

            echo "</div>";

            // Start new row on Sunday
            if (($day + $startingDay) % 7 === 0) {
                echo "</div><div class='calendar-row'>";
            }
        }

        // Add empty cells for days after end of month
        $endingDay = ($daysInMonth + $startingDay) % 7;
        if ($endingDay > 0) {
            for ($i = 0; $i < (7 - $endingDay); $i++) {
                echo "<div class='calendar-day empty'></div>";
            }
        }
        ?>
    </div>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="hideAddEventModal()">&times;</span>
        <h2>Add New Event</h2>
        <form method="POST" class="event-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Create Event</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddEventModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventDetailsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="hideEventDetails()">&times;</span>
        <div id="eventDetails"></div>
    </div>
</div>

<script>
function showAddEventModal() {
    document.getElementById('addEventModal').style.display = 'block';
}

function hideAddEventModal() {
    document.getElementById('addEventModal').style.display = 'none';
}

function showEventDetails(eventId) {
    // Fetch event details via AJAX
    fetch(`calendar_event.php?id=${eventId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('eventDetails').innerHTML = html;
            document.getElementById('eventDetailsModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load event details');
        });
}

function hideEventDetails() {
    document.getElementById('eventDetailsModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
