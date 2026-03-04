<?php
/**
 * SIROAPP - Database Connection File
 * For InfinityFree Hosting
 */

// Database Configuration
$db_host = 'sql308.infinityfree.com';
$db_user = 'if0_41268005';
$db_pass = '81oPHkH093';
$db_name = 'if0_41268005_siro1';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Optional: CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
