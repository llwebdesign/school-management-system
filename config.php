<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// File upload configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
$allowedFileTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];

// Session configuration
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400); // 24 hours
?>
