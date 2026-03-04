<?php
/**
 * SIROAPP - Database Connection File
 * For InfinityFree Hosting
 * 
 * Instructions:
 * 1. Create MySQL database in InfinityFree control panel
 * 2. Update these credentials with your actual database info
 * 3. This file will auto-create required tables on first run
 */

// Database Configuration - UPDATE THESE WITH YOUR INFINITYFREE CREDENTIALS
$db_host = 'sql308.infinityfree.com';  // Your InfinityFree database host
$db_user = 'if0_41268005';       // Your InfinityFree database username
$db_pass = '81oPHkH093'; // Your InfinityFree database password
$db_name = 'if0_41268005_siro1'; // Your InfinityFree database name

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to handle special characters
mysqli_set_charset($conn, "utf8mb4");

// Auto-create tables if they don't exist
$tables_sql = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        real_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        profile_pic VARCHAR(255) DEFAULT NULL,
        about_status TEXT DEFAULT NULL,
        online_status TINYINT DEFAULT 0,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        custom_status VARCHAR(100) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS servers (
        server_id INT AUTO_INCREMENT PRIMARY KEY,
        server_name VARCHAR(100) NOT NULL,
        server_icon VARCHAR(255) DEFAULT NULL,
        server_description TEXT DEFAULT NULL,
        owner_id INT NOT NULL,
        invite_code VARCHAR(20) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_invite (invite_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS server_members (
        member_id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('admin', 'operator', 'member') DEFAULT 'member',
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES servers(server_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_member (server_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS channels (
        channel_id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        channel_name VARCHAR(100) NOT NULL,
        channel_type ENUM('text', 'voice', 'announcement', 'rules', 'private') DEFAULT 'text',
        is_private TINYINT DEFAULT 0,
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (server_id) REFERENCES servers(server_id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS channel_permissions (
        permission_id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT NOT NULL,
        role ENUM('admin', 'operator', 'member') DEFAULT 'member',
        can_view TINYINT DEFAULT 1,
        can_send TINYINT DEFAULT 1,
        FOREIGN KEY (channel_id) REFERENCES channels(channel_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT DEFAULT NULL,
        sender_id INT NOT NULL,
        recipient_id INT DEFAULT NULL,
        message_type ENUM('text', 'gif', 'image', 'file', 'voice', 'system') DEFAULT 'text',
        content TEXT NOT NULL,
        file_url VARCHAR(500) DEFAULT NULL,
        gif_url VARCHAR(500) DEFAULT NULL,
        is_edited TINYINT DEFAULT 0,
        edited_at DATETIME DEFAULT NULL,
        is_deleted TINYINT DEFAULT 0,
        reply_to INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (channel_id) REFERENCES channels(channel_id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (reply_to) REFERENCES messages(message_id) ON DELETE SET NULL,
        INDEX idx_channel_time (channel_id, created_at),
        INDEX idx_dm (sender_id, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS message_reads (
        read_id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_read (message_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS message_reactions (
        reaction_id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        emoji VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_reaction (message_id, user_id, emoji)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS friends (
        friendship_id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        addressee_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (addressee_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_friendship (requester_id, addressee_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS statuses (
        status_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        background_color VARCHAR(20) DEFAULT '#5865F2',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS status_views (
        view_id INT AUTO_INCREMENT PRIMARY KEY,
        status_id INT NOT NULL,
        viewer_id INT NOT NULL,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (status_id) REFERENCES statuses(status_id) ON DELETE CASCADE,
        FOREIGN KEY (viewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_view (status_id, viewer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS typing_indicators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT DEFAULT NULL,
        recipient_id INT DEFAULT NULL,
        user_id INT NOT NULL,
        typing_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (channel_id) REFERENCES channels(channel_id) ON DELETE CASCADE,
        INDEX idx_typing (typing_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('message', 'friend_request', 'mention', 'call', 'status') DEFAULT 'message',
        title VARCHAR(200) NOT NULL,
        content TEXT DEFAULT NULL,
        related_id INT DEFAULT NULL,
        is_read TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_user_read (user_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS pinned_messages (
        pin_id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT NOT NULL,
        message_id INT NOT NULL,
        pinned_by INT NOT NULL,
        pinned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (channel_id) REFERENCES channels(channel_id) ON DELETE CASCADE,
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (pinned_by) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS blocked_users (
        block_id INT AUTO_INCREMENT PRIMARY KEY,
        blocker_id INT NOT NULL,
        blocked_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (blocker_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (blocked_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_block (blocker_id, blocked_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Execute table creation
foreach ($tables_sql as $sql) {
    mysqli_query($conn, $sql);
}

// Session configuration for InfinityFree
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to get current user
function get_current_user_data($conn) {
    if (isset($_SESSION['user_id'])) {
        $user_id = intval($_SESSION['user_id']);
        $sql = "SELECT user_id, real_name, username, email, profile_pic, about_status, online_status, last_seen, custom_status FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            return $row;
        }
    }
    return null;
}

// Function to update online status
function update_online_status($conn, $user_id, $status = 1) {
    $sql = "UPDATE users SET online_status = ?, last_seen = CURRENT_TIMESTAMP WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $status, $user_id);
    mysqli_stmt_execute($stmt);
}

// Update current user's online status if logged in
if (isset($_SESSION['user_id'])) {
    update_online_status($conn, $_SESSION['user_id'], 1);
}

// Auto-mark users as offline after 5 minutes of inactivity
$offline_sql = "UPDATE users SET online_status = 0 WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND online_status = 1";
mysqli_query($conn, $offline_sql);

// Clean old typing indicators
$clean_typing = "DELETE FROM typing_indicators WHERE typing_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)";
mysqli_query($conn, $clean_typing);

// Clean expired statuses
$clean_status = "DELETE FROM statuses WHERE expires_at IS NOT NULL AND expires_at < NOW()";
mysqli_query($conn, $clean_status);
?>
