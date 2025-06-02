<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">School System</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><a href="filesharing.php">Files</a></li>
                    <li><a href="news.php">News</a></li>
                    <li><a href="calendar.php">Calendar</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container">
