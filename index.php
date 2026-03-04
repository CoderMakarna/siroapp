<?php
/**
 * SIROAPP - Complete Messaging Platform
 * Combines WhatsApp intimacy with Discord community structure
 * Single-file PHP application for InfinityFree hosting
 * 
 * Features:
 * - User Authentication (Login/Register)
 * - Server Management (Create, Join, Roles)
 * - Channel Management (Text, Voice, Private)
 * - Real-time Messaging with Polling
 * - Direct Messages & Friends System
 * - Status Updates (WhatsApp-like)
 * - Voice/Video Calls (WebRTC)
 * - Emoji Reactions & GIFs (GIPHY)
 * - File Sharing
 * - Typing Indicators & Read Receipts
 */

// Include database connection
require_once 'db_connect.php';

// Error handling - hide errors in production
error_reporting(0);
ini_set('display_errors', 0);

// Application Configuration
define('APP_NAME', 'SiroApp');
define('GIPHY_API_KEY', 'YOUR_GIPHY_API_KEY'); // Replace with your GIPHY API key
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mp3', 'wav', 'pdf', 'doc', 'docx', 'txt']);

// Handle AJAX API requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_username':
            $username = sanitize_input($_POST['username'] ?? '');
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            echo json_encode(['available' => mysqli_stmt_num_rows($stmt) === 0]);
            exit;
            
        case 'check_email':
            $email = sanitize_input($_POST['email'] ?? '');
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            echo json_encode(['available' => mysqli_stmt_num_rows($stmt) === 0]);
            exit;
            
        case 'register':
            handle_register($conn);
            exit;
            
        case 'login':
            handle_login($conn);
            exit;
            
        case 'logout':
            handle_logout($conn);
            exit;
            
        case 'get_servers':
            get_servers($conn);
            exit;
            
        case 'create_server':
            create_server($conn);
            exit;
            
        case 'join_server':
            join_server($conn);
            exit;
            
        case 'get_channels':
            get_channels($conn);
            exit;
            
        case 'create_channel':
            create_channel($conn);
            exit;
            
        case 'get_messages':
            get_messages($conn);
            exit;
            
        case 'send_message':
            send_message($conn);
            exit;
            
        case 'delete_message':
            delete_message($conn);
            exit;
            
        case 'edit_message':
            edit_message($conn);
            exit;
            
        case 'add_reaction':
            add_reaction($conn);
            exit;
            
        case 'remove_reaction':
            remove_reaction($conn);
            exit;
            
        case 'typing':
            set_typing($conn);
            exit;
            
        case 'get_typing':
            get_typing($conn);
            exit;
            
        case 'mark_read':
            mark_read($conn);
            exit;
            
        case 'get_friends':
            get_friends($conn);
            exit;
            
        case 'send_friend_request':
            send_friend_request($conn);
            exit;
            
        case 'respond_friend_request':
            respond_friend_request($conn);
            exit;
            
        case 'get_statuses':
            get_statuses($conn);
            exit;
            
        case 'create_status':
            create_status($conn);
            exit;
            
        case 'view_status':
            view_status($conn);
            exit;
            
        case 'get_notifications':
            get_notifications($conn);
            exit;
            
        case 'mark_notification_read':
            mark_notification_read($conn);
            exit;
            
        case 'update_profile':
            update_profile($conn);
            exit;
            
        case 'upload_file':
            handle_file_upload($conn);
            exit;
            
        case 'get_server_members':
            get_server_members($conn);
            exit;
            
        case 'kick_member':
            kick_member($conn);
            exit;
            
        case 'ban_member':
            ban_member($conn);
            exit;
            
        case 'update_member_role':
            update_member_role($conn);
            exit;
            
        case 'pin_message':
            pin_message($conn);
            exit;
            
        case 'unpin_message':
            unpin_message($conn);
            exit;
            
        case 'get_pinned_messages':
            get_pinned_messages($conn);
            exit;
            
        case 'search_messages':
            search_messages($conn);
            exit;
            
        case 'search_users':
            search_users($conn);
            exit;
            
        case 'block_user':
            block_user($conn);
            exit;
            
        case 'unblock_user':
            unblock_user($conn);
            exit;
            
        case 'get_blocked_users':
            get_blocked_users($conn);
            exit;
            
        case 'leave_server':
            leave_server($conn);
            exit;
            
        case 'delete_server':
            delete_server($conn);
            exit;
            
        case 'update_server':
            update_server($conn);
            exit;
            
        case 'generate_invite':
            generate_invite($conn);
            exit;
            
        case 'get_poll_data':
            get_poll_data($conn);
            exit;
    }
    
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Handle regular POST requests (forms)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                handle_register($conn);
                break;
            case 'login':
                handle_login($conn);
                break;
            case 'logout':
                handle_logout($conn);
                break;
        }
    }
}

// ==================== HELPER FUNCTIONS ====================

function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return mysqli_real_escape_string($conn, $data);
}

function generate_invite_code() {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
}

function get_user_role_in_server($conn, $user_id, $server_id) {
    $stmt = mysqli_prepare($conn, "SELECT role FROM server_members WHERE user_id = ? AND server_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $server_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['role'];
    }
    return null;
}

function is_server_owner($conn, $user_id, $server_id) {
    $stmt = mysqli_prepare($conn, "SELECT owner_id FROM servers WHERE server_id = ? AND owner_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $server_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    return mysqli_stmt_num_rows($stmt) > 0;
}

function can_access_channel($conn, $user_id, $channel_id) {
    $stmt = mysqli_prepare($conn, "SELECT c.server_id, c.is_private, sm.role FROM channels c LEFT JOIN server_members sm ON c.server_id = sm.server_id AND sm.user_id = ? WHERE c.channel_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $channel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['is_private'] == 0) return true;
        if ($row['role'] === 'admin' || $row['role'] === 'operator') return true;
    }
    return false;
}

function create_notification($conn, $user_id, $type, $title, $content = '', $related_id = null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, content, related_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssi", $user_id, $type, $title, $content, $related_id);
    mysqli_stmt_execute($stmt);
}

// ==================== AUTHENTICATION HANDLERS ====================

function handle_register($conn) {
    $real_name = sanitize_input($_POST['real_name'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($real_name) || strlen($real_name) < 2) {
        $errors[] = 'Real name must be at least 2 characters';
    }
    
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters, alphanumeric and underscores only';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check uniqueness
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'Username already taken';
    }
    
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'Email already registered';
    }
    
    if (!empty($errors)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false, 'errors' => $errors]);
        } else {
            $_SESSION['register_errors'] = $errors;
            header('Location: index.php');
        }
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO users (real_name, username, email, password) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $real_name, $username, $email, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['real_name'] = $real_name;
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
        } else {
            header('Location: index.php');
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false, 'errors' => ['Registration failed. Please try again.']]);
        } else {
            $_SESSION['register_errors'] = ['Registration failed. Please try again.'];
            header('Location: index.php');
        }
    }
    exit;
}

function handle_login($conn) {
    $login = sanitize_input($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $stmt = mysqli_prepare($conn, "SELECT user_id, real_name, username, password FROM users WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $login, $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['real_name'] = $row['real_name'];
            
            update_online_status($conn, $row['user_id'], 1);
            
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    } else {
        $_SESSION['login_error'] = 'Invalid credentials';
        header('Location: index.php');
    }
    exit;
}

function handle_logout($conn) {
    if (isset($_SESSION['user_id'])) {
        update_online_status($conn, $_SESSION['user_id'], 0);
    }
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => true]);
    } else {
        header('Location: index.php');
    }
    exit;
}

// ==================== SERVER HANDLERS ====================

function get_servers($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT s.server_id, s.server_name, s.server_icon, s.invite_code, s.owner_id, sm.role 
            FROM servers s 
            JOIN server_members sm ON s.server_id = sm.server_id 
            WHERE sm.user_id = ? 
            ORDER BY s.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $servers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Get unread count for this server
        $unread_sql = "SELECT COUNT(*) as unread FROM messages m 
                       JOIN channels c ON m.channel_id = c.channel_id 
                       WHERE c.server_id = ? AND m.sender_id != ? 
                       AND m.message_id > (SELECT COALESCE(MAX(message_id), 0) FROM message_reads WHERE user_id = ?)";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        mysqli_stmt_bind_param($unread_stmt, "iii", $row['server_id'], $user_id, $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_row = mysqli_fetch_assoc($unread_result);
        
        $row['unread_count'] = $unread_row['unread'];
        $servers[] = $row;
    }
    
    echo json_encode(['servers' => $servers]);
    exit;
}

function create_server($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_name = sanitize_input($_POST['server_name'] ?? '');
    $server_description = sanitize_input($_POST['server_description'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (empty($server_name) || strlen($server_name) < 2) {
        echo json_encode(['success' => false, 'error' => 'Server name must be at least 2 characters']);
        exit;
    }
    
    $invite_code = generate_invite_code();
    
    // Handle server icon upload
    $server_icon = null;
    if (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] === 0) {
        $upload_result = upload_file_internal($_FILES['server_icon'], 'server_icons');
        if ($upload_result['success']) {
            $server_icon = $upload_result['url'];
        }
    }
    
    $stmt = mysqli_prepare($conn, "INSERT INTO servers (server_name, server_description, server_icon, owner_id, invite_code) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssis", $server_name, $server_description, $server_icon, $user_id, $invite_code);
    
    if (mysqli_stmt_execute($stmt)) {
        $server_id = mysqli_insert_id($conn);
        
        // Add creator as admin
        $member_stmt = mysqli_prepare($conn, "INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'admin')");
        mysqli_stmt_bind_param($member_stmt, "ii", $server_id, $user_id);
        mysqli_stmt_execute($member_stmt);
        
        // Create default channels
        $channels = [
            ['general', 'text', 0],
            ['rules', 'rules', 0],
            ['announcements', 'announcement', 0],
            ['General Voice', 'voice', 0]
        ];
        
        foreach ($channels as $channel) {
            $ch_stmt = mysqli_prepare($conn, "INSERT INTO channels (server_id, channel_name, channel_type, created_by) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($ch_stmt, "issi", $server_id, $channel[0], $channel[1], $user_id);
            mysqli_stmt_execute($ch_stmt);
        }
        
        echo json_encode(['success' => true, 'server_id' => $server_id, 'invite_code' => $invite_code]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create server']);
    }
    exit;
}

function join_server($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $invite_code = sanitize_input($_POST['invite_code'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (empty($invite_code)) {
        echo json_encode(['success' => false, 'error' => 'Invite code is required']);
        exit;
    }
    
    // Find server by invite code
    $stmt = mysqli_prepare($conn, "SELECT server_id FROM servers WHERE invite_code = ?");
    mysqli_stmt_bind_param($stmt, "s", $invite_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $server_id = $row['server_id'];
        
        // Check if already a member
        $check_stmt = mysqli_prepare($conn, "SELECT member_id FROM server_members WHERE server_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($check_stmt, "ii", $server_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            echo json_encode(['success' => false, 'error' => 'You are already a member of this server']);
            exit;
        }
        
        // Add as member
        $member_stmt = mysqli_prepare($conn, "INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'member')");
        mysqli_stmt_bind_param($member_stmt, "ii", $server_id, $user_id);
        
        if (mysqli_stmt_execute($member_stmt)) {
            // Notify server admins
            $admin_sql = "SELECT user_id FROM server_members WHERE server_id = ? AND role = 'admin'";
            $admin_stmt = mysqli_prepare($conn, $admin_sql);
            mysqli_stmt_bind_param($admin_stmt, "i", $server_id);
            mysqli_stmt_execute($admin_stmt);
            $admin_result = mysqli_stmt_get_result($admin_stmt);
            
            while ($admin = mysqli_fetch_assoc($admin_result)) {
                create_notification($conn, $admin['user_id'], 'member', 'New Member', $_SESSION['real_name'] . ' joined your server', $server_id);
            }
            
            echo json_encode(['success' => true, 'server_id' => $server_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to join server']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid invite code']);
    }
    exit;
}

function leave_server($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check if owner
    if (is_server_owner($conn, $user_id, $server_id)) {
        echo json_encode(['success' => false, 'error' => 'Owner cannot leave server. Delete it instead.']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM server_members WHERE server_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $server_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to leave server']);
    }
    exit;
}

function delete_server($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!is_server_owner($conn, $user_id, $server_id)) {
        echo json_encode(['success' => false, 'error' => 'Only owner can delete server']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM servers WHERE server_id = ? AND owner_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $server_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete server']);
    }
    exit;
}

function update_server($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!is_server_owner($conn, $user_id, $server_id)) {
        echo json_encode(['success' => false, 'error' => 'Only owner can update server']);
        exit;
    }
    
    $server_name = sanitize_input($_POST['server_name'] ?? '');
    $server_description = sanitize_input($_POST['server_description'] ?? '');
    
    $stmt = mysqli_prepare($conn, "UPDATE servers SET server_name = ?, server_description = ? WHERE server_id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $server_name, $server_description, $server_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update server']);
    }
    exit;
}

function generate_invite($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $role = get_user_role_in_server($conn, $user_id, $server_id);
    if (!$role || ($role !== 'admin' && $role !== 'operator')) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    $new_code = generate_invite_code();
    $stmt = mysqli_prepare($conn, "UPDATE servers SET invite_code = ? WHERE server_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_code, $server_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'invite_code' => $new_code]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to generate invite']);
    }
    exit;
}

// ==================== CHANNEL HANDLERS ====================

function get_channels($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_GET['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Get user's role in server
    $role = get_user_role_in_server($conn, $user_id, $server_id);
    
    if (!$role && $server_id > 0) {
        echo json_encode(['error' => 'Not a member']);
        exit;
    }
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM messages m WHERE m.channel_id = c.channel_id AND m.sender_id != ? 
             AND m.message_id > (SELECT COALESCE(MAX(message_id), 0) FROM message_reads mr WHERE mr.user_id = ?)) as unread_count
            FROM channels c 
            WHERE c.server_id = ? 
            ORDER BY c.created_at ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $server_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $channels = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if user can access private channel
        if ($row['is_private'] == 1 && $role !== 'admin' && $role !== 'operator') {
            continue;
        }
        $channels[] = $row;
    }
    
    echo json_encode(['channels' => $channels, 'user_role' => $role]);
    exit;
}

function create_channel($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $channel_name = sanitize_input($_POST['channel_name'] ?? '');
    $channel_type = sanitize_input($_POST['channel_type'] ?? 'text');
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    $role = get_user_role_in_server($conn, $user_id, $server_id);
    if (!$role || ($role !== 'admin' && $role !== 'operator')) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    if (empty($channel_name)) {
        echo json_encode(['success' => false, 'error' => 'Channel name is required']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "INSERT INTO channels (server_id, channel_name, channel_type, is_private, created_by) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issii", $server_id, $channel_name, $channel_type, $is_private, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $channel_id = mysqli_insert_id($conn);
        
        // Add system message
        $system_msg = "Channel created by " . $_SESSION['real_name'];
        $msg_stmt = mysqli_prepare($conn, "INSERT INTO messages (channel_id, sender_id, message_type, content) VALUES (?, ?, 'system', ?)");
        mysqli_stmt_bind_param($msg_stmt, "iis", $channel_id, $user_id, $system_msg);
        mysqli_stmt_execute($msg_stmt);
        
        echo json_encode(['success' => true, 'channel_id' => $channel_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create channel']);
    }
    exit;
}

// ==================== MESSAGE HANDLERS ====================

function get_messages($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $channel_id = intval($_GET['channel_id'] ?? 0);
    $recipient_id = intval($_GET['recipient_id'] ?? 0);
    $before_id = intval($_GET['before_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 50);
    $user_id = $_SESSION['user_id'];
    
    // Limit max messages
    if ($limit > 100) $limit = 100;
    
    if ($channel_id > 0) {
        // Channel messages
        if (!can_access_channel($conn, $user_id, $channel_id)) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $sql = "SELECT m.*, u.username, u.real_name, u.profile_pic, u.online_status,
                (SELECT GROUP_CONCAT(CONCAT(emoji, ':', user_id) SEPARATOR '|') FROM message_reactions WHERE message_id = m.message_id) as reactions
                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id 
                WHERE m.channel_id = ? AND m.is_deleted = 0";
        
        if ($before_id > 0) {
            $sql .= " AND m.message_id < ?";
        }
        
        $sql .= " ORDER BY m.message_id DESC LIMIT ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($before_id > 0) {
            mysqli_stmt_bind_param($stmt, "iii", $channel_id, $before_id, $limit);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $channel_id, $limit);
        }
        
    } elseif ($recipient_id > 0) {
        // DM messages
        $sql = "SELECT m.*, u.username, u.real_name, u.profile_pic, u.online_status,
                (SELECT GROUP_CONCAT(CONCAT(emoji, ':', user_id) SEPARATOR '|') FROM message_reactions WHERE message_id = m.message_id) as reactions
                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id 
                WHERE ((m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)) 
                AND m.channel_id IS NULL AND m.is_deleted = 0";
        
        if ($before_id > 0) {
            $sql .= " AND m.message_id < ?";
        }
        
        $sql .= " ORDER BY m.message_id DESC LIMIT ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($before_id > 0) {
            mysqli_stmt_bind_param($stmt, "iiiiii", $user_id, $recipient_id, $recipient_id, $user_id, $before_id, $limit);
        } else {
            mysqli_stmt_bind_param($stmt, "iiiii", $user_id, $recipient_id, $recipient_id, $user_id, $limit);
        }
    } else {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if message is read by current user
        $read_stmt = mysqli_prepare($conn, "SELECT read_id FROM message_reads WHERE message_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($read_stmt, "ii", $row['message_id'], $user_id);
        mysqli_stmt_execute($read_stmt);
        mysqli_stmt_store_result($read_stmt);
        $row['is_read'] = mysqli_stmt_num_rows($read_stmt) > 0;
        
        // Get read receipts for sender
        if ($row['sender_id'] == $user_id) {
            $receipts_sql = "SELECT u.username FROM message_reads mr JOIN users u ON mr.user_id = u.user_id WHERE mr.message_id = ?";
            $receipts_stmt = mysqli_prepare($conn, $receipts_sql);
            mysqli_stmt_bind_param($receipts_stmt, "i", $row['message_id']);
            mysqli_stmt_execute($receipts_stmt);
            $receipts_result = mysqli_stmt_get_result($receipts_stmt);
            $read_by = [];
            while ($r = mysqli_fetch_assoc($receipts_result)) {
                $read_by[] = $r['username'];
            }
            $row['read_by'] = $read_by;
        }
        
        $messages[] = $row;
    }
    
    // Reverse to get chronological order
    $messages = array_reverse($messages);
    
    echo json_encode(['messages' => $messages]);
    exit;
}

function send_message($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $channel_id = intval($_POST['channel_id'] ?? 0);
    $recipient_id = intval($_POST['recipient_id'] ?? 0);
    $content = sanitize_input($_POST['content'] ?? '');
    $message_type = sanitize_input($_POST['message_type'] ?? 'text');
    $gif_url = sanitize_input($_POST['gif_url'] ?? '');
    $reply_to = intval($_POST['reply_to'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (empty($content) && empty($gif_url) && $message_type !== 'file') {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit;
    }
    
    // Handle file upload
    $file_url = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_result = upload_file_internal($_FILES['file'], 'uploads');
        if ($upload_result['success']) {
            $file_url = $upload_result['url'];
            $message_type = 'file';
        }
    }
    
    if ($channel_id > 0) {
        if (!can_access_channel($conn, $user_id, $channel_id)) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        $stmt = mysqli_prepare($conn, "INSERT INTO messages (channel_id, sender_id, message_type, content, gif_url, file_url, reply_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissssi", $channel_id, $user_id, $message_type, $content, $gif_url, $file_url, $reply_to);
        
    } elseif ($recipient_id > 0) {
        // Check if blocked
        $block_stmt = mysqli_prepare($conn, "SELECT block_id FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
        mysqli_stmt_bind_param($block_stmt, "iiii", $user_id, $recipient_id, $recipient_id, $user_id);
        mysqli_stmt_execute($block_stmt);
        mysqli_stmt_store_result($block_stmt);
        
        if (mysqli_stmt_num_rows($block_stmt) > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot message this user']);
            exit;
        }
        
        $stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, recipient_id, message_type, content, gif_url, file_url, reply_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissssi", $user_id, $recipient_id, $message_type, $content, $gif_url, $file_url, $reply_to);
        
        // Create notification for recipient
        create_notification($conn, $recipient_id, 'message', 'New Message', substr($content, 0, 50), $user_id);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid destination']);
        exit;
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $message_id = mysqli_insert_id($conn);
        
        // Get the inserted message with user info
        $msg_sql = "SELECT m.*, u.username, u.real_name, u.profile_pic, u.online_status FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.message_id = ?";
        $msg_stmt = mysqli_prepare($conn, $msg_sql);
        mysqli_stmt_bind_param($msg_stmt, "i", $message_id);
        mysqli_stmt_execute($msg_stmt);
        $msg_result = mysqli_stmt_get_result($msg_stmt);
        $message = mysqli_fetch_assoc($msg_result);
        
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
    exit;
}

function delete_message($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check if user owns the message or is admin/operator
    $stmt = mysqli_prepare($conn, "SELECT sender_id, channel_id FROM messages WHERE message_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $can_delete = ($row['sender_id'] == $user_id);
        
        if (!$can_delete && $row['channel_id']) {
            $role_stmt = mysqli_prepare($conn, "SELECT sm.role FROM server_members sm JOIN channels c ON sm.server_id = c.server_id WHERE c.channel_id = ? AND sm.user_id = ?");
            mysqli_stmt_bind_param($role_stmt, "ii", $row['channel_id'], $user_id);
            mysqli_stmt_execute($role_stmt);
            $role_result = mysqli_stmt_get_result($role_stmt);
            if ($role_row = mysqli_fetch_assoc($role_result)) {
                $can_delete = ($role_row['role'] === 'admin' || $role_row['role'] === 'operator');
            }
        }
        
        if ($can_delete) {
            $del_stmt = mysqli_prepare($conn, "UPDATE messages SET is_deleted = 1, content = 'This message was deleted' WHERE message_id = ?");
            mysqli_stmt_bind_param($del_stmt, "i", $message_id);
            mysqli_stmt_execute($del_stmt);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
    }
    exit;
}

function edit_message($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $content = sanitize_input($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "SELECT sender_id FROM messages WHERE message_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['sender_id'] == $user_id) {
            $edit_stmt = mysqli_prepare($conn, "UPDATE messages SET content = ?, is_edited = 1, edited_at = CURRENT_TIMESTAMP WHERE message_id = ?");
            mysqli_stmt_bind_param($edit_stmt, "si", $content, $message_id);
            mysqli_stmt_execute($edit_stmt);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Can only edit your own messages']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
    }
    exit;
}

function add_reaction($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $emoji = sanitize_input($_POST['emoji'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE emoji = ?");
    mysqli_stmt_bind_param($stmt, "iiss", $message_id, $user_id, $emoji, $emoji);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add reaction']);
    }
    exit;
}

function remove_reaction($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $emoji = sanitize_input($_POST['emoji'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "DELETE FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
    mysqli_stmt_bind_param($stmt, "iis", $message_id, $user_id, $emoji);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove reaction']);
    }
    exit;
}

function set_typing($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $channel_id = intval($_POST['channel_id'] ?? 0);
    $recipient_id = intval($_POST['recipient_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Delete old typing indicator
    $del_stmt = mysqli_prepare($conn, "DELETE FROM typing_indicators WHERE user_id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $user_id);
    mysqli_stmt_execute($del_stmt);
    
    // Insert new typing indicator
    $stmt = mysqli_prepare($conn, "INSERT INTO typing_indicators (channel_id, recipient_id, user_id) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iii", $channel_id, $recipient_id, $user_id);
    mysqli_stmt_execute($stmt);
    
    echo json_encode(['success' => true]);
    exit;
}

function get_typing($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $channel_id = intval($_GET['channel_id'] ?? 0);
    $recipient_id = intval($_GET['recipient_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($channel_id > 0) {
        $sql = "SELECT u.username, u.real_name FROM typing_indicators t JOIN users u ON t.user_id = u.user_id WHERE t.channel_id = ? AND t.user_id != ? AND t.typing_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $channel_id, $user_id);
    } elseif ($recipient_id > 0) {
        $sql = "SELECT u.username, u.real_name FROM typing_indicators t JOIN users u ON t.user_id = u.user_id WHERE t.recipient_id = ? AND t.user_id = ? AND t.typing_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $recipient_id);
    } else {
        echo json_encode(['typing' => []]);
        exit;
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $typing = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $typing[] = $row;
    }
    
    echo json_encode(['typing' => $typing]);
    exit;
}

function mark_read($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $message_id, $user_id);
    mysqli_stmt_execute($stmt);
    
    echo json_encode(['success' => true]);
    exit;
}

// ==================== FRIEND HANDLERS ====================

function get_friends($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get accepted friends
    $sql = "SELECT u.user_id, u.username, u.real_name, u.profile_pic, u.online_status, u.last_seen, u.custom_status,
            f.status, f.requester_id, f.friendship_id
            FROM friends f 
            JOIN users u ON (f.requester_id = u.user_id AND f.addressee_id = ?) OR (f.addressee_id = u.user_id AND f.requester_id = ?)
            WHERE f.status = 'accepted' AND u.user_id != ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $friends = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Get unread message count
        $unread_sql = "SELECT COUNT(*) as unread FROM messages WHERE sender_id = ? AND recipient_id = ? 
                       AND message_id > (SELECT COALESCE(MAX(message_id), 0) FROM message_reads WHERE user_id = ?)";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        mysqli_stmt_bind_param($unread_stmt, "iii", $row['user_id'], $user_id, $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_row = mysqli_fetch_assoc($unread_result);
        $row['unread_count'] = $unread_row['unread'];
        
        $friends[] = $row;
    }
    
    // Get pending requests
    $pending_sql = "SELECT u.user_id, u.username, u.real_name, u.profile_pic, f.requester_id, f.friendship_id 
                    FROM friends f 
                    JOIN users u ON f.requester_id = u.user_id 
                    WHERE f.addressee_id = ? AND f.status = 'pending'";
    $pending_stmt = mysqli_prepare($conn, $pending_sql);
    mysqli_stmt_bind_param($pending_stmt, "i", $user_id);
    mysqli_stmt_execute($pending_stmt);
    $pending_result = mysqli_stmt_get_result($pending_stmt);
    
    $pending = [];
    while ($row = mysqli_fetch_assoc($pending_result)) {
        $pending[] = $row;
    }
    
    // Get sent requests
    $sent_sql = "SELECT u.user_id, u.username, u.real_name, u.profile_pic, f.friendship_id 
                 FROM friends f 
                 JOIN users u ON f.addressee_id = u.user_id 
                 WHERE f.requester_id = ? AND f.status = 'pending'";
    $sent_stmt = mysqli_prepare($conn, $sent_sql);
    mysqli_stmt_bind_param($sent_stmt, "i", $user_id);
    mysqli_stmt_execute($sent_stmt);
    $sent_result = mysqli_stmt_get_result($sent_stmt);
    
    $sent = [];
    while ($row = mysqli_fetch_assoc($sent_result)) {
        $sent[] = $row;
    }
    
    echo json_encode(['friends' => $friends, 'pending' => $pending, 'sent' => $sent]);
    exit;
}

function send_friend_request($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $username = sanitize_input($_POST['username'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Find user by username
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $friend_id = $row['user_id'];
        
        if ($friend_id == $user_id) {
            echo json_encode(['success' => false, 'error' => 'Cannot add yourself']);
            exit;
        }
        
        // Check if already friends or pending
        $check_stmt = mysqli_prepare($conn, "SELECT status FROM friends WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)");
        mysqli_stmt_bind_param($check_stmt, "iiii", $user_id, $friend_id, $friend_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            echo json_encode(['success' => false, 'error' => 'Friend request already exists or already friends']);
            exit;
        }
        
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO friends (requester_id, addressee_id, status) VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $friend_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            create_notification($conn, $friend_id, 'friend_request', 'Friend Request', $_SESSION['real_name'] . ' sent you a friend request', $user_id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send request']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
    exit;
}

function respond_friend_request($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $friendship_id = intval($_POST['friendship_id'] ?? 0);
    $response = sanitize_input($_POST['response'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($response === 'accept') {
        $stmt = mysqli_prepare($conn, "UPDATE friends SET status = 'accepted' WHERE friendship_id = ? AND addressee_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $friendship_id, $user_id);
        mysqli_stmt_execute($stmt);
        
        // Get requester info for notification
        $req_sql = "SELECT requester_id FROM friends WHERE friendship_id = ?";
        $req_stmt = mysqli_prepare($conn, $req_sql);
        mysqli_stmt_bind_param($req_stmt, "i", $friendship_id);
        mysqli_stmt_execute($req_stmt);
        $req_result = mysqli_stmt_get_result($req_stmt);
        if ($req_row = mysqli_fetch_assoc($req_result)) {
            create_notification($conn, $req_row['requester_id'], 'friend_request', 'Friend Request Accepted', $_SESSION['real_name'] . ' accepted your friend request', $user_id);
        }
        
        echo json_encode(['success' => true]);
    } elseif ($response === 'decline') {
        $stmt = mysqli_prepare($conn, "DELETE FROM friends WHERE friendship_id = ? AND addressee_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $friendship_id, $user_id);
        mysqli_stmt_execute($stmt);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid response']);
    }
    exit;
}

// ==================== STATUS HANDLERS ====================

function get_statuses($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get statuses from friends that haven't expired
    $sql = "SELECT s.*, u.username, u.real_name, u.profile_pic,
            (SELECT COUNT(*) FROM status_views WHERE status_id = s.status_id) as view_count,
            (SELECT COUNT(*) FROM status_views WHERE status_id = s.status_id AND viewer_id = ?) as has_viewed
            FROM statuses s 
            JOIN users u ON s.user_id = u.user_id 
            WHERE (s.user_id = ? OR s.user_id IN (
                SELECT CASE WHEN requester_id = ? THEN addressee_id ELSE requester_id END 
                FROM friends WHERE (requester_id = ? OR addressee_id = ?) AND status = 'accepted'
            )) AND (s.expires_at IS NULL OR s.expires_at > NOW())
            ORDER BY s.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $statuses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $statuses[] = $row;
    }
    
    echo json_encode(['statuses' => $statuses]);
    exit;
}

function create_status($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $content = sanitize_input($_POST['content'] ?? '');
    $background_color = sanitize_input($_POST['background_color'] ?? '#5865F2');
    $user_id = $_SESSION['user_id'];
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Status content is required']);
        exit;
    }
    
    // Set expiry to 24 hours from now
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = mysqli_prepare($conn, "INSERT INTO statuses (user_id, content, background_color, expires_at) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $content, $background_color, $expires);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'status_id' => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create status']);
    }
    exit;
}

function view_status($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $status_id = intval($_POST['status_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO status_views (status_id, viewer_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $status_id, $user_id);
    mysqli_stmt_execute($stmt);
    
    echo json_encode(['success' => true]);
    exit;
}

// ==================== NOTIFICATION HANDLERS ====================

function get_notifications($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    $unread_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['is_read'] == 0) $unread_count++;
        $notifications[] = $row;
    }
    
    echo json_encode(['notifications' => $notifications, 'unread_count' => $unread_count]);
    exit;
}

function mark_notification_read($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($notification_id === 0) {
        // Mark all as read
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

// ==================== PROFILE HANDLERS ====================

function update_profile($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $real_name = sanitize_input($_POST['real_name'] ?? '');
    $about_status = sanitize_input($_POST['about_status'] ?? '');
    $custom_status = sanitize_input($_POST['custom_status'] ?? '');
    
    // Handle profile picture upload
    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $upload_result = upload_file_internal($_FILES['profile_pic'], 'profile_pics');
        if ($upload_result['success']) {
            $profile_pic = $upload_result['url'];
        }
    }
    
    if ($profile_pic) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET real_name = ?, about_status = ?, custom_status = ?, profile_pic = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $real_name, $about_status, $custom_status, $profile_pic, $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET real_name = ?, about_status = ?, custom_status = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $real_name, $about_status, $custom_status, $user_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['real_name'] = $real_name;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
    exit;
}

// ==================== FILE UPLOAD HANDLER ====================

function upload_file_internal($file, $folder) {
    $upload_dir = "uploads/$folder/";
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_name = basename($file['name']);
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file size
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Validate file type
    if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $new_file_name = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $upload_path = $upload_dir . $new_file_name;
    
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return ['success' => true, 'url' => $upload_path];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}

function handle_file_upload($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    if (isset($_FILES['file'])) {
        $result = upload_file_internal($_FILES['file'], 'uploads');
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    }
    exit;
}

// ==================== SERVER MEMBER HANDLERS ====================

function get_server_members($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_GET['server_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check if user is member
    $role = get_user_role_in_server($conn, $user_id, $server_id);
    if (!$role) {
        echo json_encode(['error' => 'Not a member']);
        exit;
    }
    
    $sql = "SELECT u.user_id, u.username, u.real_name, u.profile_pic, u.online_status, u.last_seen, u.custom_status, sm.role, sm.joined_at 
            FROM server_members sm 
            JOIN users u ON sm.user_id = u.user_id 
            WHERE sm.server_id = ? 
            ORDER BY FIELD(sm.role, 'admin', 'operator', 'member'), u.real_name";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $server_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
    
    echo json_encode(['members' => $members, 'user_role' => $role]);
    exit;
}

function kick_member($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $role = get_user_role_in_server($conn, $user_id, $server_id);
    if (!$role || ($role !== 'admin' && $role !== 'operator')) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    // Cannot kick admin
    $member_role = get_user_role_in_server($conn, $member_id, $server_id);
    if ($member_role === 'admin') {
        echo json_encode(['success' => false, 'error' => 'Cannot kick admin']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM server_members WHERE server_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $server_id, $member_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to kick member']);
    }
    exit;
}

function ban_member($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!is_server_owner($conn, $user_id, $server_id)) {
        echo json_encode(['success' => false, 'error' => 'Only owner can ban members']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM server_members WHERE server_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $server_id, $member_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to ban member']);
    }
    exit;
}

function update_member_role($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $server_id = intval($_POST['server_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $new_role = sanitize_input($_POST['role'] ?? 'member');
    $user_id = $_SESSION['user_id'];
    
    if (!is_server_owner($conn, $user_id, $server_id)) {
        echo json_encode(['success' => false, 'error' => 'Only owner can change roles']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "UPDATE server_members SET role = ? WHERE server_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $new_role, $server_id, $member_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update role']);
    }
    exit;
}

// ==================== PINNED MESSAGE HANDLERS ====================

function pin_message($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $message_id = intval($_POST['message_id'] ?? 0);
    $channel_id = intval($_POST['channel_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check permissions
    $role = get_user_role_in_server($conn, $user_id, 0);
    $stmt = mysqli_prepare($conn, "SELECT server_id FROM channels WHERE channel_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $channel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $role = get_user_role_in_server($conn, $user_id, $row['server_id']);
    }
    
    if (!$role || ($role !== 'admin' && $role !== 'operator')) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    $pin_stmt = mysqli_prepare($conn, "INSERT INTO pinned_messages (channel_id, message_id, pinned_by) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($pin_stmt, "iii", $channel_id, $message_id, $user_id);
    
    if (mysqli_stmt_execute($pin_stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to pin message']);
    }
    exit;
}

function unpin_message($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $pin_id = intval($_POST['pin_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "DELETE FROM pinned_messages WHERE pin_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to unpin message']);
    }
    exit;
}

function get_pinned_messages($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $channel_id = intval($_GET['channel_id'] ?? 0);
    
    $sql = "SELECT pm.*, m.content, m.sender_id, m.created_at as message_date, u.username, u.real_name, u.profile_pic 
            FROM pinned_messages pm 
            JOIN messages m ON pm.message_id = m.message_id 
            JOIN users u ON m.sender_id = u.user_id 
            WHERE pm.channel_id = ? 
            ORDER BY pm.pinned_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $channel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $pinned = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pinned[] = $row;
    }
    
    echo json_encode(['pinned' => $pinned]);
    exit;
}

// ==================== SEARCH HANDLERS ====================

function search_messages($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $query = sanitize_input($_GET['query'] ?? '');
    $channel_id = intval($_GET['channel_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (empty($query)) {
        echo json_encode(['messages' => []]);
        exit;
    }
    
    $search = "%$query%";
    
    if ($channel_id > 0) {
        $sql = "SELECT m.*, u.username, u.real_name, u.profile_pic FROM messages m 
                JOIN users u ON m.sender_id = u.user_id 
                WHERE m.channel_id = ? AND m.content LIKE ? AND m.is_deleted = 0 
                ORDER BY m.created_at DESC LIMIT 50";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $channel_id, $search);
    } else {
        // Search all accessible channels and DMs
        $sql = "SELECT m.*, u.username, u.real_name, u.profile_pic, c.channel_name, c.server_id 
                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id 
                LEFT JOIN channels c ON m.channel_id = c.channel_id 
                WHERE m.content LIKE ? AND m.is_deleted = 0 
                AND (m.sender_id = ? OR m.recipient_id = ? OR m.channel_id IN (
                    SELECT channel_id FROM channels WHERE server_id IN (
                        SELECT server_id FROM server_members WHERE user_id = ?
                    )
                ))
                ORDER BY m.created_at DESC LIMIT 50";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siiii", $search, $user_id, $user_id, $user_id, $user_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    
    echo json_encode(['messages' => $messages]);
    exit;
}

function search_users($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $query = sanitize_input($_GET['query'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (empty($query)) {
        echo json_encode(['users' => []]);
        exit;
    }
    
    $search = "%$query%";
    
    $sql = "SELECT user_id, username, real_name, profile_pic, online_status FROM users 
            WHERE (username LIKE ? OR real_name LIKE ?) AND user_id != ? 
            LIMIT 20";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $search, $search, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    echo json_encode(['users' => $users]);
    exit;
}

// ==================== BLOCK HANDLERS ====================

function block_user($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $blocked_id = intval($_POST['blocked_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($blocked_id == $user_id) {
        echo json_encode(['success' => false, 'error' => 'Cannot block yourself']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "INSERT INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $blocked_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Remove friendship if exists
        $del_stmt = mysqli_prepare($conn, "DELETE FROM friends WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)");
        mysqli_stmt_bind_param($del_stmt, "iiii", $user_id, $blocked_id, $blocked_id, $user_id);
        mysqli_stmt_execute($del_stmt);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to block user']);
    }
    exit;
}

function unblock_user($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $blocked_id = intval($_POST['blocked_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conn, "DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $blocked_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to unblock user']);
    }
    exit;
}

function get_blocked_users($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT u.user_id, u.username, u.real_name, u.profile_pic FROM blocked_users b 
            JOIN users u ON b.blocked_id = u.user_id 
            WHERE b.blocker_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $blocked = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $blocked[] = $row;
    }
    
    echo json_encode(['blocked' => $blocked]);
    exit;
}

function get_poll_data($conn) {
    echo json_encode(['success' => true]);
    exit;
}

// Get current user data for the UI
$current_user = get_current_user_data($conn);
$is_logged_in = ($current_user !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo APP_NAME; ?> - Connect with Friends</title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PeerJS for WebRTC -->
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    
    <style>
        /* ==================== CSS VARIABLES - DISCORD DARK THEME ==================== */
        :root {
            /* Primary Colors */
            --primary: #5865F2;
            --primary-hover: #4752C4;
            --primary-active: #3C45A5;
            
            /* Background Colors */
            --bg-primary: #313338;
            --bg-secondary: #2B2D31;
            --bg-tertiary: #1E1F22;
            --bg-quaternary: #111214;
            --bg-modifier-hover: rgba(78, 80, 88, 0.3);
            --bg-modifier-active: rgba(78, 80, 88, 0.5);
            --bg-modifier-selected: rgba(78, 80, 88, 0.6);
            --bg-mention: rgba(250, 166, 26, 0.1);
            --bg-mention-hover: rgba(250, 166, 26, 0.2);
            
            /* Text Colors */
            --text-primary: #F2F3F5;
            --text-secondary: #B5BAC1;
            --text-muted: #949BA4;
            --text-link: #00A8FC;
            --text-positive: #23A559;
            --text-warning: #F0B232;
            --text-danger: #F23F43;
            
            /* Interactive Colors */
            --interactive-normal: #B5BAC1;
            --interactive-hover: #DBDEE1;
            --interactive-active: #FFFFFF;
            --interactive-muted: #4E5058;
            
            /* Status Colors */
            --status-online: #23A559;
            --status-idle: #F0B232;
            --status-dnd: #F23F43;
            --status-offline: #80848E;
            --status-streaming: #593695;
            
            /* Accent Colors */
            --accent: #5865F2;
            --accent-hover: #4752C4;
            --button-danger: #DA373C;
            --button-danger-hover: #A12828;
            --button-positive: #248046;
            --button-positive-hover: #1A6334;
            
            /* Border Colors */
            --border-subtle: rgba(78, 80, 88, 0.6);
            --border-strong: #4E5058;
            
            /* Spacing */
            --guildbar-width: 72px;
            --sidebar-width: 240px;
            --member-list-width: 240px;
            --header-height: 48px;
            --input-height: 68px;
            
            /* Font */
            --font-primary: 'gg sans', 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            --font-display: 'gg sans', 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            --font-code: 'gg mono', 'Source Code Pro', Consolas, monospace;
            
            /* Shadows */
            --shadow-low: 0 1px 0 rgba(0, 0, 0, 0.2), 0 1.5px 0 rgba(0, 0, 0, 0.05), 0 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-medium: 0 4px 4px rgba(0, 0, 0, 0.16);
            --shadow-high: 0 8px 16px rgba(0, 0, 0, 0.24);
            
            /* Scrollbar */
            --scrollbar-thin-thumb: #1A1B1E;
            --scrollbar-thin-track: transparent;
            --scrollbar-auto-thumb: #1A1B1E;
            --scrollbar-auto-track: #2B2D31;
            
            /* Radius */
            --radius-xs: 4px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-round: 50%;
        }
        
        /* ==================== RESET & BASE ==================== */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: var(--font-primary);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        button {
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: none;
            color: inherit;
        }
        
        input, textarea {
            font-family: inherit;
            border: none;
            outline: none;
            background: transparent;
            color: inherit;
        }
        
        a {
            color: var(--text-link);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* ==================== SCROLLBAR ==================== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--scrollbar-auto-track);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-auto-thumb);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #2B2D31;
        }
        
        ::-webkit-scrollbar-corner {
            background: transparent;
        }
        
        /* ==================== AUTHENTICATION PAGE ==================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5865F2 0%, #7289DA 50%, #99AAB5 100%);
            padding: 20px;
        }
        
        .auth-container {
            display: flex;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 500px;
        }
        
        .auth-visual {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            text-align: center;
        }
        
        .auth-visual h1 {
            font-size: 2.5rem;
            margin-bottom: 16px;
            font-weight: 800;
        }
        
        .auth-visual p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 300px;
        }
        
        .auth-visual .logo {
            font-size: 5rem;
            margin-bottom: 24px;
        }
        
        .auth-form-container {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .auth-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s;
            color: var(--text-secondary);
        }
        
        .auth-tab:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .auth-tab.active {
            background: var(--primary);
            color: white;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xs);
            font-size: 1rem;
            color: var(--text-primary);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.3);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
        }
        
        .form-input.error {
            border-color: var(--button-danger);
        }
        
        .form-error {
            color: var(--button-danger);
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }
        
        .form-error.visible {
            display: block;
        }
        
        .password-strength {
            height: 4px;
            background: var(--bg-tertiary);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .password-strength-bar.weak { width: 33%; background: var(--button-danger); }
        .password-strength-bar.medium { width: 66%; background: var(--status-idle); }
        .password-strength-bar.strong { width: 100%; background: var(--status-online); }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-xs);
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-secondary {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: var(--bg-modifier-active);
        }
        
        .btn-danger {
            background: var(--button-danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: var(--button-danger-hover);
        }
        
        .btn-success {
            background: var(--button-positive);
            color: white;
        }
        
        .btn-success:hover {
            background: var(--button-positive-hover);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            cursor: pointer;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .checkbox-wrapper span {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .auth-footer a {
            color: var(--text-link);
        }
        
        .availability-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            margin-top: 4px;
        }
        
        .availability-indicator.available {
            color: var(--status-online);
        }
        
        .availability-indicator.taken {
            color: var(--button-danger);
        }
        
        /* ==================== MAIN APP LAYOUT ==================== */
        .app-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        
        /* ==================== GUILDS SIDEBAR (LEFTMOST) ==================== */
        .guilds-sidebar {
            width: var(--guildbar-width);
            background: var(--bg-tertiary);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 0;
            gap: 8px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .guilds-sidebar::-webkit-scrollbar {
            width: 0;
        }
        
        .guild-separator {
            width: 32px;
            height: 2px;
            background: var(--bg-quaternary);
            border-radius: 1px;
            margin: 4px 0;
        }
        
        .guild-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-round);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            background: var(--bg-secondary);
            overflow: hidden;
        }
        
        .guild-icon:hover {
            border-radius: var(--radius-md);
            background: var(--primary);
        }
        
        .guild-icon.active {
            border-radius: var(--radius-md);
            background: var(--primary);
        }
        
        .guild-icon.active::before {
            content: '';
            position: absolute;
            left: -16px;
            width: 8px;
            height: 40px;
            background: white;
            border-radius: 0 4px 4px 0;
        }
        
        .guild-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .guild-icon.home {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .guild-icon.home:hover, .guild-icon.home.active {
            background: var(--primary);
            color: white;
        }
        
        .guild-icon.add {
            background: var(--bg-secondary);
            color: var(--status-online);
            font-size: 1.5rem;
        }
        
        .guild-icon.add:hover {
            background: var(--status-online);
            color: white;
        }
        
        .guild-icon.unread::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: var(--button-danger);
            border-radius: 50%;
            border: 3px solid var(--bg-tertiary);
        }
        
        .guild-tooltip {
            position: fixed;
            background: var(--bg-quaternary);
            color: var(--text-primary);
            padding: 8px 12px;
            border-radius: var(--radius-xs);
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            pointer-events: none;
            box-shadow: var(--shadow-high);
        }
        
        .guild-tooltip.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .guild-tooltip::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: var(--bg-quaternary);
        }
        
        /* ==================== CHANNELS SIDEBAR ==================== */
        .channels-sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .channels-header {
            height: var(--header-height);
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--bg-tertiary);
            box-shadow: var(--shadow-low);
            cursor: pointer;
        }
        
        .channels-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .channels-header i {
            color: var(--text-muted);
        }
        
        .channels-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 8px;
        }
        
        .channel-category {
            margin-bottom: 16px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            user-select: none;
        }
        
        .category-header:hover {
            color: var(--text-secondary);
        }
        
        .category-header i {
            font-size: 0.7rem;
            transition: transform 0.2s;
        }
        
        .category-header.collapsed i {
            transform: rotate(-90deg);
        }
        
        .channel-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            margin: 2px 0;
            border-radius: var(--radius-xs);
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 0.95rem;
            transition: all 0.15s;
        }
        
        .channel-item:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .channel-item.active {
            background: var(--bg-modifier-selected);
            color: var(--text-primary);
        }
        
        .channel-item.unread {
            color: var(--text-primary);
        }
        
        .channel-item.unread::before {
            content: '';
            position: absolute;
            left: 4px;
            width: 4px;
            height: 4px;
            background: var(--text-primary);
            border-radius: 50%;
        }
        
        .channel-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        .channel-item span {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .channel-item .unread-badge {
            background: var(--button-danger);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .dm-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            margin: 2px 0;
            border-radius: var(--radius-xs);
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .dm-item:hover {
            background: var(--bg-modifier-hover);
        }
        
        .dm-item.active {
            background: var(--bg-modifier-selected);
        }
        
        .dm-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            position: relative;
            flex-shrink: 0;
        }
        
        .dm-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .dm-avatar .status-indicator {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }
        
        .dm-avatar .status-indicator.online { background: var(--status-online); }
        .dm-avatar .status-indicator.idle { background: var(--status-idle); }
        .dm-avatar .status-indicator.dnd { background: var(--status-dnd); }
        .dm-avatar .status-indicator.offline { background: var(--status-offline); }
        
        .dm-info {
            flex: 1;
            min-width: 0;
        }
        
        .dm-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dm-preview {
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dm-item.unread .dm-name {
            font-weight: 700;
        }
        
        .dm-item.unread .dm-preview {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .dm-item .unread-badge {
            background: var(--button-danger);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .channels-footer {
            padding: 8px;
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-panel {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            padding: 4px;
            border-radius: var(--radius-xs);
            cursor: pointer;
        }
        
        .user-panel:hover {
            background: var(--bg-modifier-hover);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            position: relative;
            flex-shrink: 0;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-avatar .status-indicator {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg-tertiary);
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-status {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-actions {
            display: flex;
            gap: 4px;
        }
        
        .user-action-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .user-action-btn:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        /* ==================== MAIN CHAT AREA ==================== */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            min-width: 0;
        }
        
        .chat-header {
            height: var(--header-height);
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--bg-tertiary);
            box-shadow: var(--shadow-low);
            flex-shrink: 0;
        }
        
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        
        .chat-header-icon {
            color: var(--text-muted);
            font-size: 1.2rem;
        }
        
        .chat-header-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chat-header-title .typing-indicator {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 400;
        }
        
        .chat-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chat-header-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .chat-header-btn:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .chat-header-btn.active {
            background: var(--bg-modifier-selected);
            color: var(--text-primary);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 0;
            display: flex;
            flex-direction: column;
        }
        
        .welcome-screen {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .welcome-screen i {
            font-size: 5rem;
            margin-bottom: 24px;
            color: var(--primary);
        }
        
        .welcome-screen h2 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        .welcome-screen p {
            font-size: 1rem;
            max-width: 400px;
        }
        
        .message-group {
            padding: 2px 16px;
            display: flex;
            gap: 16px;
            transition: background 0.15s;
        }
        
        .message-group:hover {
            background: var(--bg-modifier-hover);
        }
        
        .message-group:hover .message-actions {
            opacity: 1;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            flex-shrink: 0;
            cursor: pointer;
        }
        
        .message-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            flex: 1;
            min-width: 0;
        }
        
        .message-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        
        .message-author {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .message-author:hover {
            text-decoration: underline;
        }
        
        .message-timestamp {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .message-edited {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        
        .message-body {
            font-size: 1rem;
            color: var(--text-secondary);
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .message-body .mention {
            background: rgba(88, 101, 242, 0.3);
            color: var(--text-link);
            padding: 0 4px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .message-body .mention:hover {
            background: var(--primary);
            color: white;
        }
        
        .message-body code {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: var(--font-code);
            font-size: 0.9em;
        }
        
        .message-body pre {
            background: var(--bg-tertiary);
            padding: 12px;
            border-radius: var(--radius-xs);
            overflow-x: auto;
            margin: 8px 0;
        }
        
        .message-body pre code {
            background: none;
            padding: 0;
        }
        
        .message-body blockquote {
            border-left: 4px solid var(--border-strong);
            padding-left: 12px;
            margin: 8px 0;
            color: var(--text-muted);
        }
        
        .message-body img.gif {
            max-width: 300px;
            border-radius: var(--radius-xs);
            cursor: pointer;
        }
        
        .message-body .file-attachment {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            max-width: 400px;
            margin-top: 8px;
        }
        
        .file-attachment i {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .file-info {
            flex: 1;
            min-width: 0;
        }
        
        .file-name {
            font-size: 0.95rem;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-size {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .file-download {
            color: var(--text-muted);
            font-size: 1.2rem;
        }
        
        .file-download:hover {
            color: var(--text-primary);
        }
        
        .message-reactions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }
        
        .reaction {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: var(--bg-secondary);
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid transparent;
        }
        
        .reaction:hover {
            background: var(--bg-modifier-hover);
            border-color: var(--border-subtle);
        }
        
        .reaction.active {
            background: rgba(88, 101, 242, 0.3);
            border-color: var(--primary);
        }
        
        .reaction-count {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .message-actions {
            position: absolute;
            right: 16px;
            top: -16px;
            display: flex;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            box-shadow: var(--shadow-medium);
            opacity: 0;
            transition: opacity 0.15s;
            overflow: hidden;
        }
        
        .message-action-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .message-action-btn:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .message-group {
            position: relative;
        }
        
        .system-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .system-message i {
            color: var(--primary);
        }
        
        .new-messages-divider {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            margin: 8px 0;
            color: var(--button-danger);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .new-messages-divider::before,
        .new-messages-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--button-danger);
            opacity: 0.3;
        }
        
        /* ==================== MESSAGE INPUT ==================== */
        .chat-input-container {
            padding: 0 16px 24px;
            flex-shrink: 0;
        }
        
        .chat-input-wrapper {
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            padding: 12px;
        }
        
        .chat-input-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .input-tool-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: all 0.15s;
        }
        
        .input-tool-btn:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .chat-input {
            width: 100%;
            min-height: 44px;
            max-height: 200px;
            background: transparent;
            border: none;
            resize: none;
            font-size: 1rem;
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .chat-input::placeholder {
            color: var(--text-muted);
        }
        
        .chat-input-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .typing-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .typing-dots {
            display: flex;
            gap: 3px;
        }
        
        .typing-dots span {
            width: 6px;
            height: 6px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
        
        /* ==================== MEMBERS SIDEBAR ==================== */
        .members-sidebar {
            width: var(--member-list-width);
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .members-header {
            height: var(--header-height);
            padding: 0 16px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--bg-tertiary);
            box-shadow: var(--shadow-low);
        }
        
        .members-header h3 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
        
        .members-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        
        .member-group {
            margin-bottom: 16px;
        }
        
        .member-group-title {
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 16px;
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .member-item:hover {
            background: var(--bg-modifier-hover);
        }
        
        .member-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            position: relative;
            flex-shrink: 0;
        }
        
        .member-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .member-avatar .status-indicator {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }
        
        .member-avatar .status-indicator.online { background: var(--status-online); }
        .member-avatar .status-indicator.idle { background: var(--status-idle); }
        .member-avatar .status-indicator.dnd { background: var(--status-dnd); }
        .member-avatar .status-indicator.offline { background: var(--status-offline); }
        
        .member-info {
            flex: 1;
            min-width: 0;
        }
        
        .member-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .member-status {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* ==================== MODALS ==================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.2s;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1);
        }
        
        .modal.large {
            max-width: 700px;
        }
        
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 1.2rem;
        }
        
        .modal-close:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--bg-tertiary);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* ==================== EMOJI PICKER ==================== */
        .emoji-picker {
            position: absolute;
            bottom: 80px;
            left: 16px;
            width: 350px;
            height: 400px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            display: flex;
            flex-direction: column;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s;
        }
        
        .emoji-picker.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .emoji-picker-header {
            padding: 12px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .emoji-search {
            width: 100%;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-xs);
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        
        .emoji-categories {
            display: flex;
            gap: 4px;
            padding: 8px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .emoji-category {
            flex: 1;
            padding: 8px;
            text-align: center;
            border-radius: var(--radius-xs);
            cursor: pointer;
            color: var(--text-muted);
        }
        
        .emoji-category:hover, .emoji-category.active {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .emoji-grid {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
        }
        
        .emoji-item {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            border-radius: var(--radius-xs);
        }
        
        .emoji-item:hover {
            background: var(--bg-modifier-hover);
        }
        
        /* ==================== GIF PICKER ==================== */
        .gif-picker {
            position: absolute;
            bottom: 80px;
            left: 16px;
            width: 400px;
            height: 450px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            display: flex;
            flex-direction: column;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s;
        }
        
        .gif-picker.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .gif-picker-header {
            padding: 12px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .gif-search {
            width: 100%;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-xs);
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        
        .gif-grid {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .gif-item {
            aspect-ratio: 16/9;
            border-radius: var(--radius-xs);
            overflow: hidden;
            cursor: pointer;
            background: var(--bg-tertiary);
        }
        
        .gif-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gif-item:hover {
            outline: 2px solid var(--primary);
        }
        
        /* ==================== STATUS BAR ==================== */
        .status-bar {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            overflow-x: auto;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .status-bar::-webkit-scrollbar {
            height: 0;
        }
        
        .status-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .status-avatar {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-round);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            position: relative;
            border: 3px solid transparent;
        }
        
        .status-avatar.has-status {
            border-color: var(--primary);
        }
        
        .status-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-add {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
        }
        
        .status-name {
            font-size: 0.75rem;
            color: var(--text-muted);
            max-width: 60px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* ==================== CALL OVERLAY ==================== */
        .call-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--bg-quaternary);
            z-index: 2000;
            display: none;
            flex-direction: column;
        }
        
        .call-overlay.active {
            display: flex;
        }
        
        .call-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .call-participants {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            padding: 24px;
        }
        
        .call-participant {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .call-avatar {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: white;
        }
        
        .call-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .call-name {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .call-status {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        .call-controls {
            padding: 24px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }
        
        .call-btn {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-round);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            transition: all 0.2s;
        }
        
        .call-btn.mute {
            background: var(--bg-secondary);
        }
        
        .call-btn.mute:hover {
            background: var(--bg-modifier-hover);
        }
        
        .call-btn.video {
            background: var(--bg-secondary);
        }
        
        .call-btn.video:hover {
            background: var(--bg-modifier-hover);
        }
        
        .call-btn.end {
            background: var(--button-danger);
        }
        
        .call-btn.end:hover {
            background: var(--button-danger-hover);
        }
        
        .call-btn.active {
            background: var(--button-danger);
        }
        
        /* ==================== CONTEXT MENU ==================== */
        .context-menu {
            position: fixed;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            box-shadow: var(--shadow-high);
            min-width: 180px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.95);
            transition: all 0.1s;
            overflow: hidden;
        }
        
        .context-menu.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }
        
        .context-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .context-item:hover {
            background: var(--primary);
            color: white;
        }
        
        .context-item.danger {
            color: var(--button-danger);
        }
        
        .context-item.danger:hover {
            background: var(--button-danger);
            color: white;
        }
        
        .context-separator {
            height: 1px;
            background: var(--bg-tertiary);
            margin: 4px 0;
        }
        
        /* ==================== TOAST NOTIFICATIONS ==================== */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 3000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .toast {
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-high);
            min-width: 280px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.success {
            border-left: 4px solid var(--status-online);
        }
        
        .toast.error {
            border-left: 4px solid var(--button-danger);
        }
        
        .toast.info {
            border-left: 4px solid var(--primary);
        }
        
        .toast-icon {
            font-size: 1.2rem;
        }
        
        .toast.success .toast-icon { color: var(--status-online); }
        .toast.error .toast-icon { color: var(--button-danger); }
        .toast.info .toast-icon { color: var(--primary); }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .toast-message {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .toast-close {
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .toast-close:hover {
            color: var(--text-primary);
        }
        
        /* ==================== LOADING SPINNER ==================== */
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--bg-tertiary);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        
        /* ==================== RESPONSIVE DESIGN ==================== */
        @media (max-width: 1024px) {
            .members-sidebar {
                display: none;
            }
            
            .chat-header-btn[data-action="toggle-members"] {
                display: flex;
            }
        }
        
        @media (max-width: 768px) {
            .app-container {
                position: relative;
            }
            
            .guilds-sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                height: 60px;
                flex-direction: row;
                justify-content: space-around;
                padding: 8px;
                z-index: 100;
            }
            
            .guild-separator {
                display: none;
            }
            
            .guild-icon {
                width: 44px;
                height: 44px;
            }
            
            .channels-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 60px;
                width: 280px;
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 50;
            }
            
            .channels-sidebar.active {
                transform: translateX(0);
            }
            
            .chat-container {
                padding-bottom: 60px;
            }
            
            .mobile-menu-btn {
                display: flex !important;
            }
            
            .auth-container {
                flex-direction: column;
            }
            
            .auth-visual {
                padding: 24px;
                min-height: 200px;
            }
            
            .auth-visual h1 {
                font-size: 1.75rem;
            }
            
            .auth-form-container {
                padding: 24px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        /* ==================== UTILITY CLASSES ==================== */
        .hidden {
            display: none !important;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-center { justify-content: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-4 { gap: 16px; }
        .flex-1 { flex: 1; }
        
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        
        .p-2 { padding: 8px; }
        .p-4 { padding: 16px; }
        
        .text-sm { font-size: 0.875rem; }
        .text-lg { font-size: 1.125rem; }
        .font-bold { font-weight: 700; }
        
        .text-primary { color: var(--text-primary); }
        .text-secondary { color: var(--text-secondary); }
        .text-muted { color: var(--text-muted); }
        .text-success { color: var(--status-online); }
        .text-danger { color: var(--button-danger); }
        
        .bg-primary { background: var(--bg-primary); }
        .bg-secondary { background: var(--bg-secondary); }
        
        .rounded { border-radius: var(--radius-xs); }
        .rounded-lg { border-radius: var(--radius-md); }
        
        .cursor-pointer { cursor: pointer; }
        
        .hover\:bg-hover:hover { background: var(--bg-modifier-hover); }
        
        /* ==================== ANIMATIONS ==================== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease;
        }
        
        .animate-slideUp {
            animation: slideUp 0.3s ease;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        /* ==================== REPLY PREVIEW ==================== */
        .reply-preview {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        
        .reply-preview-bar {
            width: 3px;
            height: 24px;
            background: var(--text-muted);
            border-radius: 2px;
        }
        
        .reply-preview-content {
            flex: 1;
            min-width: 0;
        }
        
        .reply-preview-author {
            color: var(--primary);
            font-weight: 500;
        }
        
        .reply-preview-text {
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .reply-preview-close {
            color: var(--text-muted);
            cursor: pointer;
        }
        
        .reply-preview-close:hover {
            color: var(--text-primary);
        }
        
        /* ==================== MESSAGE REPLY INDICATOR ==================== */
        .message-reply-to {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .message-reply-to i {
            font-size: 0.7rem;
        }
        
        .message-reply-to .reply-author {
            color: var(--primary);
            font-weight: 500;
        }
        
        /* ==================== SEARCH BAR ==================== */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-xs);
            margin: 8px 16px;
        }
        
        .search-bar input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .search-bar input::placeholder {
            color: var(--text-muted);
        }
        
        .search-bar i {
            color: var(--text-muted);
        }
        
        /* ==================== FRIEND REQUEST BADGE ==================== */
        .friend-request-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            background: var(--button-danger);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ==================== EMPTY STATE ==================== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .empty-state p {
            font-size: 0.9rem;
        }
        
        /* ==================== BADGE ==================== */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 6px;
            background: var(--button-danger);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 9px;
        }
        
        /* ==================== TOOLTIP ==================== */
        [data-tooltip] {
            position: relative;
        }
        
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-8px);
            padding: 6px 10px;
            background: var(--bg-quaternary);
            color: var(--text-primary);
            font-size: 0.8rem;
            border-radius: var(--radius-xs);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            z-index: 1000;
            pointer-events: none;
        }
        
        [data-tooltip]:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        /* ==================== DIVIDER ==================== */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 16px 0;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-subtle);
        }
        
        /* ==================== AVATAR GROUP ==================== */
        .avatar-group {
            display: flex;
        }
        
        .avatar-group .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
            margin-left: -8px;
        }
        
        .avatar-group .avatar:first-child {
            margin-left: 0;
        }
        
        /* ==================== PROGRESS BAR ==================== */
        .progress-bar {
            height: 4px;
            background: var(--bg-tertiary);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
            transition: width 0.3s;
        }
        
        /* ==================== TABS ==================== */
        .tabs {
            display: flex;
            gap: 4px;
            padding: 8px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .tab {
            padding: 8px 16px;
            border-radius: var(--radius-xs);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .tab:hover {
            background: var(--bg-modifier-hover);
            color: var(--text-primary);
        }
        
        .tab.active {
            background: var(--bg-modifier-selected);
            color: var(--text-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* ==================== LIST ITEM ==================== */
        .list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .list-item:hover {
            background: var(--bg-modifier-hover);
        }
        
        .list-item-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-round);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .list-item-content {
            flex: 1;
            min-width: 0;
        }
        
        .list-item-title {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .list-item-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .list-item-action {
            color: var(--text-muted);
        }
        
        /* ==================== CARD ==================== */
        .card {
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .card-header {
            padding: 16px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .card-body {
            padding: 16px;
        }
        
        .card-footer {
            padding: 16px;
            border-top: 1px solid var(--bg-tertiary);
        }
        
        /* ==================== ALERT ==================== */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .alert-info {
            background: rgba(88, 101, 242, 0.1);
            border-left: 4px solid var(--primary);
        }
        
        .alert-success {
            background: rgba(35, 165, 89, 0.1);
            border-left: 4px solid var(--status-online);
        }
        
        .alert-warning {
            background: rgba(240, 178, 50, 0.1);
            border-left: 4px solid var(--status-idle);
        }
        
        .alert-error {
            background: rgba(242, 63, 67, 0.1);
            border-left: 4px solid var(--button-danger);
        }
        
        /* ==================== SKELETON LOADING ==================== */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-tertiary) 25%, var(--bg-secondary) 50%, var(--bg-tertiary) 75%);
            background-size: 200% 100%;
            animation: skeleton 1.5s infinite;
            border-radius: var(--radius-xs);
        }
        
        @keyframes skeleton {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* ==================== HOVER CARD ==================== */
        .hover-card {
            position: absolute;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            padding: 16px;
            z-index: 500;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            min-width: 280px;
        }
        
        .hover-card.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* ==================== POPOVER ==================== */
        .popover {
            position: absolute;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            z-index: 500;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.95);
            transition: all 0.15s;
        }
        
        .popover.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }
        
        /* ==================== INFINITE SCROLL TRIGGER ==================== */
        .infinite-scroll-trigger {
            height: 1px;
            margin: 8px 0;
        }
        
        /* ==================== DATE SEPARATOR ==================== */
        .date-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 16px 0;
            position: relative;
        }
        
        .date-separator::before {
            content: '';
            position: absolute;
            left: 16px;
            right: 16px;
            height: 1px;
            background: var(--border-subtle);
        }
        
        .date-separator span {
            background: var(--bg-primary);
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        
        /* ==================== MESSAGE HIGHLIGHT ==================== */
        .message-group.highlighted {
            background: rgba(88, 101, 242, 0.1);
            animation: highlight 2s ease;
        }
        
        @keyframes highlight {
            0% { background: rgba(88, 101, 242, 0.3); }
            100% { background: rgba(88, 101, 242, 0.1); }
        }
        
        /* ==================== MENTION HIGHLIGHT ==================== */
        .mention-highlight {
            background: rgba(250, 166, 26, 0.1);
            border-left: 2px solid var(--status-idle);
        }
        
        /* ==================== CODE BLOCK ==================== */
        .code-block {
            position: relative;
        }
        
        .code-block-copy {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 4px 8px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            font-size: 0.75rem;
            color: var(--text-muted);
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.15s;
        }
        
        .code-block:hover .code-block-copy {
            opacity: 1;
        }
        
        .code-block-copy:hover {
            color: var(--text-primary);
        }
        
        /* ==================== SPOILER ==================== */
        .spoiler {
            background: var(--bg-tertiary);
            color: transparent;
            border-radius: 3px;
            cursor: pointer;
            padding: 0 4px;
        }
        
        .spoiler.revealed {
            background: rgba(255, 255, 255, 0.1);
            color: inherit;
        }
        
        /* ==================== EMBED ==================== */
        .embed {
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            border-left: 4px solid var(--primary);
            padding: 12px 16px;
            margin-top: 8px;
            max-width: 500px;
        }
        
        .embed-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .embed-description {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .embed-image {
            margin-top: 8px;
            border-radius: var(--radius-xs);
            overflow: hidden;
        }
        
        .embed-image img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
        }
        
        /* ==================== STICKER ==================== */
        .sticker {
            width: 160px;
            height: 160px;
            margin-top: 8px;
        }
        
        .sticker img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* ==================== VOICE MESSAGE ==================== */
        .voice-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            max-width: 300px;
            margin-top: 8px;
        }
        
        .voice-message-play {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
        }
        
        .voice-message-waveform {
            flex: 1;
            height: 24px;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .waveform-bar {
            width: 3px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .voice-message-duration {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        /* ==================== POLL ==================== */
        .poll {
            background: var(--bg-secondary);
            border-radius: var(--radius-xs);
            padding: 16px;
            margin-top: 8px;
            max-width: 400px;
        }
        
        .poll-question {
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .poll-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-xs);
            margin-bottom: 8px;
            cursor: pointer;
        }
        
        .poll-option:hover {
            background: var(--bg-modifier-hover);
        }
        
        .poll-option-bar {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            background: rgba(88, 101, 242, 0.2);
            border-radius: var(--radius-xs);
        }
        
        .poll-option-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        
        .poll-option-text {
            flex: 1;
        }
        
        .poll-option-votes {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        /* ==================== THREAD INDICATOR ==================== */
        .thread-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 0.8rem;
            color: var(--primary);
            cursor: pointer;
        }
        
        .thread-indicator:hover {
            text-decoration: underline;
        }
        
        /* ==================== PINNED MESSAGES ==================== */
        .pinned-messages {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--bg-tertiary);
            max-height: 200px;
            overflow-y: auto;
        }
        
        .pinned-message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .pinned-message:last-child {
            border-bottom: none;
        }
        
        .pinned-icon {
            color: var(--status-idle);
            font-size: 1rem;
        }
        
        .pinned-content {
            flex: 1;
            min-width: 0;
        }
        
        .pinned-author {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .pinned-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pinned-unpin {
            color: var(--text-muted);
            cursor: pointer;
        }
        
        .pinned-unpin:hover {
            color: var(--button-danger);
        }
        
        /* ==================== NOTIFICATION CENTER ==================== */
        .notification-center {
            position: absolute;
            top: 48px;
            right: 16px;
            width: 360px;
            max-height: 500px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-high);
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }
        
        .notification-center.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .notification-header {
            padding: 16px;
            border-bottom: 1px solid var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-header h3 {
            font-size: 1rem;
            font-weight: 700;
        }
        
        .notification-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border-radius: var(--radius-xs);
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .notification-item:hover {
            background: var(--bg-modifier-hover);
        }
        
        .notification-item.unread {
            background: rgba(88, 101, 242, 0.1);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .notification-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        /* ==================== QUICK SWITCHER ==================== */
        .quick-switcher {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 3000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
        }
        
        .quick-switcher.active {
            display: flex;
        }
        
        .quick-switcher-input {
            width: 100%;
            max-width: 600px;
            padding: 16px 20px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            font-size: 1.25rem;
            color: var(--text-primary);
            border: none;
        }
        
        .quick-switcher-results {
            width: 100%;
            max-width: 600px;
            max-height: 400px;
            overflow-y: auto;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            margin-top: 8px;
        }
        
        /* ==================== KEYBOARD SHORTCUTS ==================== */
        .kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            padding: 0 6px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            font-family: var(--font-code);
            font-size: 0.75rem;
            color: var(--text-secondary);
            box-shadow: 0 2px 0 var(--bg-quaternary);
        }
        
        /* ==================== FOCUS MODE ==================== */
        .focus-mode .guilds-sidebar,
        .focus-mode .channels-sidebar,
        .focus-mode .members-sidebar {
            display: none;
        }
        
        .focus-mode .chat-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* ==================== PRINT STYLES ==================== */
        @media print {
            .guilds-sidebar,
            .channels-sidebar,
            .members-sidebar,
            .chat-input-container,
            .chat-header-actions {
                display: none !important;
            }
            
            .chat-container {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
<!-- ==================== AUTHENTICATION PAGE ==================== -->
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-visual">
            <div class="logo">
                <i class="fas fa-comments"></i>
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Connect with friends and communities. Chat, call, and share moments together.</p>
        </div>
        <div class="auth-form-container">
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Login</button>
                <button class="auth-tab" data-tab="register">Register</button>
            </div>
            
            <!-- Login Form -->
            <form class="auth-form active" id="login-form" method="POST">
                <input type="hidden" name="action" value="login">
                
                <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <input type="text" name="login" class="form-input" placeholder="Enter your username or email" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
                
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Remember me</span>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="#" onclick="switchTab('register')">Register</a></p>
                </div>
            </form>
            
            <!-- Register Form -->
            <form class="auth-form" id="register-form" method="POST">
                <input type="hidden" name="action" value="register">
                
                <?php if (isset($_SESSION['register_errors'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo implode(', ', $_SESSION['register_errors']); unset($_SESSION['register_errors']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Real Name</label>
                    <input type="text" name="real_name" class="form-input" placeholder="Enter your real name" required minlength="2">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Choose a username" required 
                           pattern="[a-zA-Z0-9_]{3,50}" title="3-50 characters, alphanumeric and underscores only">
                    <div class="availability-indicator" id="username-availability"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                    <div class="availability-indicator" id="email-availability"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" id="reg-password" placeholder="Create a password" required minlength="8">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-input" id="reg-confirm-password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="#" onclick="switchTab('login')">Login</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ==================== MAIN APPLICATION ==================== -->
<div class="app-container" id="app">
    <!-- Guilds Sidebar (Leftmost) -->
    <div class="guilds-sidebar" id="guilds-sidebar">
        <!-- Home/DM Button -->
        <div class="guild-icon home active" data-tooltip="Direct Messages" data-server-id="0" onclick="selectServer(0)">
            <i class="fas fa-comment-dots"></i>
        </div>
        
        <div class="guild-separator"></div>
        
        <!-- Server List -->
        <div id="server-list"></div>
        
        <div class="guild-separator"></div>
        
        <!-- Add Server Button -->
        <div class="guild-icon add" data-tooltip="Add a Server" onclick="showCreateServerModal()">
            <i class="fas fa-plus"></i>
        </div>
        
        <!-- Join Server Button -->
        <div class="guild-icon add" data-tooltip="Join a Server" onclick="showJoinServerModal()" style="color: var(--primary);">
            <i class="fas fa-compass"></i>
        </div>
    </div>
    
    <!-- Channels Sidebar -->
    <div class="channels-sidebar" id="channels-sidebar">
        <div class="channels-header" onclick="showServerMenu()">
            <h2 id="channels-header-title">Direct Messages</h2>
            <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Find or start a conversation" id="channel-search" oninput="searchChannels()">
        </div>
        
        <div class="channels-list" id="channels-list">
            <!-- Friends Section -->
            <div class="channel-category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Friends</span>
                </div>
                <div class="category-content" id="friends-list">
                    <div class="dm-item" onclick="showFriendsPage()">
                        <div class="dm-avatar" style="background: var(--primary);">
                            <i class="fas fa-user-friends" style="font-size: 0.9rem;"></i>
                        </div>
                        <div class="dm-info">
                            <div class="dm-name">Friends</div>
                            <div class="dm-preview">View all friends</div>
                        </div>
                        <span class="badge hidden" id="friend-request-badge">0</span>
                    </div>
                    
                    <div class="dm-item" onclick="showAddFriendModal()">
                        <div class="dm-avatar" style="background: var(--status-online);">
                            <i class="fas fa-user-plus" style="font-size: 0.9rem;"></i>
                        </div>
                        <div class="dm-info">
                            <div class="dm-name">Add Friend</div>
                            <div class="dm-preview">Find new friends</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Section -->
            <div class="channel-category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Statuses</span>
                </div>
                <div class="category-content">
                    <div class="dm-item" onclick="showStatusModal()">
                        <div class="dm-avatar" style="background: var(--status-idle);">
                            <i class="fas fa-circle-notch" style="font-size: 0.9rem;"></i>
                        </div>
                        <div class="dm-info">
                            <div class="dm-name">My Status</div>
                            <div class="dm-preview">Add a status update</div>
                        </div>
                    </div>
                    <div class="dm-item" onclick="showStatusesPage()">
                        <div class="dm-avatar" style="background: var(--primary);">
                            <i class="fas fa-eye" style="font-size: 0.9rem;"></i>
                        </div>
                        <div class="dm-info">
                            <div class="dm-name">View Statuses</div>
                            <div class="dm-preview">See friends' updates</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Direct Messages Section -->
            <div class="channel-category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Direct Messages</span>
                </div>
                <div class="category-content" id="dm-list"></div>
            </div>
            
            <!-- Server Channels (shown when server selected) -->
            <div id="server-channels" class="hidden"></div>
        </div>
        
        <!-- User Panel -->
        <div class="channels-footer">
            <div class="user-panel" onclick="showProfileModal()">
                <div class="user-avatar" id="user-avatar">
                    <?php if ($current_user['profile_pic']): ?>
                        <img src="<?php echo $current_user['profile_pic']; ?>" alt="">
                    <?php else: ?>
                        <?php echo strtoupper(substr($current_user['real_name'], 0, 1)); ?>
                    <?php endif; ?>
                    <span class="status-indicator online" id="user-status-indicator"></span>
                </div>
                <div class="user-info">
                    <div class="user-name" id="user-display-name"><?php echo $current_user['real_name']; ?></div>
                    <div class="user-status" id="user-custom-status"><?php echo $current_user['custom_status'] ?: 'Online'; ?></div>
                </div>
            </div>
            <div class="user-actions">
                <button class="user-action-btn" onclick="toggleMute()" id="mute-btn" data-tooltip="Mute">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="user-action-btn" onclick="showSettingsModal()" data-tooltip="Settings">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="user-action-btn" onclick="logout()" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="chat-container" id="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-left">
                <button class="chat-header-btn mobile-menu-btn" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <i class="chat-header-icon" id="chat-header-icon">@</i>
                <div class="chat-header-title">
                    <span id="chat-header-title">Welcome</span>
                    <span class="typing-indicator" id="typing-indicator"></span>
                </div>
            </div>
            <div class="chat-header-actions">
                <button class="chat-header-btn" onclick="startVoiceCall()" data-tooltip="Voice Call" id="voice-call-btn">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="chat-header-btn" onclick="startVideoCall()" data-tooltip="Video Call" id="video-call-btn">
                    <i class="fas fa-video"></i>
                </button>
                <button class="chat-header-btn" onclick="togglePinnedMessages()" data-tooltip="Pinned Messages">
                    <i class="fas fa-thumbtack"></i>
                </button>
                <button class="chat-header-btn" onclick="showSearchModal()" data-tooltip="Search">
                    <i class="fas fa-search"></i>
                </button>
                <button class="chat-header-btn" onclick="toggleMembersSidebar()" data-tooltip="Member List" id="toggle-members-btn">
                    <i class="fas fa-users"></i>
                </button>
                <button class="chat-header-btn" onclick="showChannelInfo()" data-tooltip="Channel Info">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>
        
        <!-- Pinned Messages -->
        <div class="pinned-messages hidden" id="pinned-messages"></div>
        
        <!-- Status Bar (for DMs) -->
        <div class="status-bar hidden" id="status-bar"></div>
        
        <!-- Chat Messages -->
        <div class="chat-messages" id="chat-messages">
            <div class="welcome-screen" id="welcome-screen">
                <i class="fas fa-comments"></i>
                <h2>Welcome to <?php echo APP_NAME; ?></h2>
                <p>Select a friend or channel to start messaging. Join servers to connect with communities.</p>
            </div>
            <div id="messages-container" class="hidden"></div>
            <div class="infinite-scroll-trigger" id="infinite-scroll-trigger"></div>
        </div>
        
        <!-- Chat Input -->
        <div class="chat-input-container hidden" id="chat-input-container">
            <div class="chat-input-wrapper">
                <!-- Reply Preview -->
                <div class="reply-preview hidden" id="reply-preview">
                    <div class="reply-preview-bar"></div>
                    <div class="reply-preview-content">
                        <span class="reply-preview-author" id="reply-author"></span>
                        <span class="reply-preview-text" id="reply-text"></span>
                    </div>
                    <i class="fas fa-times reply-preview-close" onclick="cancelReply()"></i>
                </div>
                
                <div class="chat-input-toolbar">
                    <button class="input-tool-btn" onclick="toggleEmojiPicker()" data-tooltip="Emoji">
                        <i class="far fa-smile"></i>
                    </button>
                    <button class="input-tool-btn" onclick="toggleGifPicker()" data-tooltip="GIF">
                        <i class="fas fa-image"></i>
                    </button>
                    <button class="input-tool-btn" onclick="document.getElementById('file-input').click()" data-tooltip="Upload File">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <input type="file" id="file-input" class="hidden" onchange="handleFileUpload(this)">
                </div>
                <textarea class="chat-input" id="message-input" placeholder="Message @..." 
                          onkeydown="handleMessageKeydown(event)" oninput="handleTyping()"></textarea>
                <div class="chat-input-footer">
                    <div class="typing-status" id="typing-status"></div>
                    <div>
                        <span id="char-count"></span>
                        <kbd class="kbd">Enter</kbd> to send
                    </div>
                </div>
            </div>
            
            <!-- Emoji Picker -->
            <div class="emoji-picker" id="emoji-picker">
                <div class="emoji-picker-header">
                    <input type="text" class="emoji-search" placeholder="Search emoji..." oninput="searchEmoji(this.value)">
                </div>
                <div class="emoji-categories">
                    <div class="emoji-category active" onclick="showEmojiCategory('smileys')">😀</div>
                    <div class="emoji-category" onclick="showEmojiCategory('people')">👋</div>
                    <div class="emoji-category" onclick="showEmojiCategory('animals')">🐱</div>
                    <div class="emoji-category" onclick="showEmojiCategory('food')">🍎</div>
                    <div class="emoji-category" onclick="showEmojiCategory('activities')">⚽</div>
                    <div class="emoji-category" onclick="showEmojiCategory('travel')">🚗</div>
                    <div class="emoji-category" onclick="showEmojiCategory('objects')">💡</div>
                    <div class="emoji-category" onclick="showEmojiCategory('symbols')">❤️</div>
                </div>
                <div class="emoji-grid" id="emoji-grid"></div>
            </div>
            
            <!-- GIF Picker -->
            <div class="gif-picker" id="gif-picker">
                <div class="gif-picker-header">
                    <input type="text" class="gif-search" placeholder="Search GIFs..." oninput="searchGIFs(this.value)">
                </div>
                <div class="gif-grid" id="gif-grid"></div>
            </div>
        </div>
    </div>
    
    <!-- Members Sidebar -->
    <div class="members-sidebar hidden" id="members-sidebar">
        <div class="members-header">
            <h3>Members</h3>
        </div>
        <div class="members-list" id="members-list"></div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- Create Server Modal -->
<div class="modal-overlay" id="create-server-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Create Your Server</h2>
            <button class="modal-close" onclick="closeModal('create-server-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Server Name</label>
                <input type="text" class="form-input" id="new-server-name" placeholder="Enter server name">
            </div>
            <div class="form-group">
                <label class="form-label">Server Description (Optional)</label>
                <input type="text" class="form-input" id="new-server-description" placeholder="What's this server about?">
            </div>
            <div class="form-group">
                <label class="form-label">Server Icon (Optional)</label>
                <input type="file" class="form-input" id="new-server-icon" accept="image/*">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-server-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="createServer()">Create Server</button>
        </div>
    </div>
</div>

<!-- Join Server Modal -->
<div class="modal-overlay" id="join-server-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Join a Server</h2>
            <button class="modal-close" onclick="closeModal('join-server-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Invite Code</label>
                <input type="text" class="form-input" id="join-invite-code" placeholder="Enter invite code">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('join-server-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="joinServer()">Join Server</button>
        </div>
    </div>
</div>

<!-- Add Friend Modal -->
<div class="modal-overlay" id="add-friend-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Add Friend</h2>
            <button class="modal-close" onclick="closeModal('add-friend-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-input" id="friend-username" placeholder="Enter username">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('add-friend-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="sendFriendRequest()">Send Request</button>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal-overlay" id="profile-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="modal-close" onclick="closeModal('profile-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Profile Picture</label>
                <input type="file" class="form-input" id="profile-pic-input" accept="image/*">
            </div>
            <div class="form-group">
                <label class="form-label">Real Name</label>
                <input type="text" class="form-input" id="profile-real-name" value="<?php echo $current_user['real_name']; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">About Me</label>
                <textarea class="form-input" id="profile-about" rows="3" placeholder="Tell us about yourself"><?php echo $current_user['about_status']; ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Custom Status</label>
                <input type="text" class="form-input" id="profile-custom-status" value="<?php echo $current_user['custom_status']; ?>" placeholder="What's on your mind?">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('profile-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateProfile()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Create Status Modal -->
<div class="modal-overlay" id="status-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Create Status</h2>
            <button class="modal-close" onclick="closeModal('status-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Status Text</label>
                <textarea class="form-input" id="status-content" rows="3" placeholder="What's on your mind?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Background Color</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #5865F2; cursor: pointer;" onclick="selectStatusColor('#5865F2')"></div>
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #EB459E; cursor: pointer;" onclick="selectStatusColor('#EB459E')"></div>
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #23A559; cursor: pointer;" onclick="selectStatusColor('#23A559')"></div>
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #F0B232; cursor: pointer;" onclick="selectStatusColor('#F0B232')"></div>
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #F23F43; cursor: pointer;" onclick="selectStatusColor('#F23F43')"></div>
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #593695; cursor: pointer;" onclick="selectStatusColor('#593695')"></div>
                </div>
                <input type="hidden" id="status-color" value="#5865F2">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('status-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="createStatus()">Post Status</button>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal-overlay" id="search-modal">
    <div class="modal large">
        <div class="modal-header">
            <h2>Search Messages</h2>
            <button class="modal-close" onclick="closeModal('search-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" class="form-input" id="search-query" placeholder="Search for messages..." oninput="searchMessages()">
            </div>
            <div id="search-results"></div>
        </div>
    </div>
</div>

<!-- Friends Page Modal -->
<div class="modal-overlay" id="friends-modal">
    <div class="modal large">
        <div class="modal-header">
            <h2>Friends</h2>
            <button class="modal-close" onclick="closeModal('friends-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="tabs">
                <div class="tab active" onclick="showFriendsTab('all')">All Friends</div>
                <div class="tab" onclick="showFriendsTab('pending')">Pending <span class="badge hidden" id="pending-count">0</span></div>
                <div class="tab" onclick="showFriendsTab('blocked')">Blocked</div>
            </div>
            <div id="friends-content"></div>
        </div>
    </div>
</div>

<!-- Statuses Page Modal -->
<div class="modal-overlay" id="statuses-modal">
    <div class="modal large">
        <div class="modal-header">
            <h2>Statuses</h2>
            <button class="modal-close" onclick="closeModal('statuses-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="statuses-list"></div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal-overlay" id="settings-modal">
    <div class="modal large">
        <div class="modal-header">
            <h2>Settings</h2>
            <button class="modal-close" onclick="closeModal('settings-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="list-item" onclick="showProfileModal()">
                <div class="list-item-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="list-item-content">
                    <div class="list-item-title">Edit Profile</div>
                    <div class="list-item-subtitle">Change your name, avatar, and status</div>
                </div>
                <i class="fas fa-chevron-right list-item-action"></i>
            </div>
            
            <div class="divider">Notifications</div>
            
            <div class="list-item">
                <div class="list-item-icon" style="background: var(--status-online);">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="list-item-content">
                    <div class="list-item-title">Enable Notifications</div>
                    <div class="list-item-subtitle">Get notified of new messages</div>
                </div>
                <input type="checkbox" checked id="notification-setting">
            </div>
            
            <div class="divider">Appearance</div>
            
            <div class="list-item">
                <div class="list-item-icon" style="background: var(--primary);">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="list-item-content">
                    <div class="list-item-title">Dark Theme</div>
                    <div class="list-item-subtitle">Currently only dark theme available</div>
                </div>
            </div>
            
            <div class="divider">Account</div>
            
            <div class="list-item" onclick="logout()">
                <div class="list-item-icon" style="background: var(--button-danger);">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="list-item-content">
                    <div class="list-item-title">Logout</div>
                    <div class="list-item-subtitle">Sign out of your account</div>
                </div>
                <i class="fas fa-chevron-right list-item-action"></i>
            </div>
        </div>
    </div>
</div>

<!-- Create Channel Modal -->
<div class="modal-overlay" id="create-channel-modal">
    <div class="modal">
        <div class="modal-header">
            <h2>Create Channel</h2>
            <button class="modal-close" onclick="closeModal('create-channel-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Channel Name</label>
                <input type="text" class="form-input" id="new-channel-name" placeholder="new-channel">
            </div>
            <div class="form-group">
                <label class="form-label">Channel Type</label>
                <select class="form-input" id="new-channel-type">
                    <option value="text">Text Channel</option>
                    <option value="voice">Voice Channel</option>
                    <option value="announcement">Announcement</option>
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-wrapper">
                    <input type="checkbox" id="new-channel-private">
                    <span>Private Channel (Admin/Operator only)</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-channel-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="createChannel()">Create Channel</button>
        </div>
    </div>
</div>

<!-- Server Settings Modal -->
<div class="modal-overlay" id="server-settings-modal">
    <div class="modal large">
        <div class="modal-header">
            <h2>Server Settings</h2>
            <button class="modal-close" onclick="closeModal('server-settings-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Server Name</label>
                <input type="text" class="form-input" id="server-settings-name">
            </div>
            <div class="form-group">
                <label class="form-label">Server Description</label>
                <textarea class="form-input" id="server-settings-description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Invite Code</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="form-input" id="server-invite-code" readonly>
                    <button class="btn btn-secondary" onclick="copyInviteCode()">Copy</button>
                    <button class="btn btn-secondary" onclick="regenerateInvite()">New</button>
                </div>
            </div>
            <div class="divider">Danger Zone</div>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-danger" onclick="leaveCurrentServer()">Leave Server</button>
                <button class="btn btn-danger hidden" id="delete-server-btn" onclick="deleteCurrentServer()">Delete Server</button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('server-settings-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateServerSettings()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Call Overlay -->
<div class="call-overlay" id="call-overlay">
    <div class="call-header">
        <h2 id="call-title">Voice Call</h2>
        <span id="call-timer">00:00</span>
    </div>
    <div class="call-participants" id="call-participants"></div>
    <div class="call-controls">
        <button class="call-btn mute" id="call-mute-btn" onclick="toggleCallMute()">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="call-btn video" id="call-video-btn" onclick="toggleCallVideo()">
            <i class="fas fa-video"></i>
        </button>
        <button class="call-btn end" onclick="endCall()">
            <i class="fas fa-phone-slash"></i>
        </button>
    </div>
</div>

<!-- Context Menu -->
<div class="context-menu" id="context-menu">
    <div class="context-item" onclick="replyToMessage()">
        <i class="fas fa-reply"></i>
        <span>Reply</span>
    </div>
    <div class="context-item" onclick="copyMessage()">
        <i class="fas fa-copy"></i>
        <span>Copy Text</span>
    </div>
    <div class="context-item" onclick="pinMessage()">
        <i class="fas fa-thumbtack"></i>
        <span>Pin Message</span>
    </div>
    <div class="context-separator" id="context-owner-separator"></div>
    <div class="context-item danger" id="context-delete-item" onclick="deleteMessage()">
        <i class="fas fa-trash"></i>
        <span>Delete</span>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Guild Tooltip -->
<div class="guild-tooltip" id="guild-tooltip"></div>

<?php endif; ?>

<!-- ==================== JAVASCRIPT ==================== -->
<script>

<?php if (!$is_logged_in): ?>
// ==================== AUTHENTICATION JAVASCRIPT ====================

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });
    
    // Username availability check
    const usernameInput = document.querySelector('input[name="username"]');
    if (usernameInput) {
        let usernameTimeout;
        usernameInput.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const username = this.value;
            const indicator = document.getElementById('username-availability');
            
            if (username.length < 3) {
                indicator.innerHTML = '';
                return;
            }
            
            usernameTimeout = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=check_username&username=${encodeURIComponent(username)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.available) {
                        indicator.innerHTML = '<span class="available"><i class="fas fa-check"></i> Available</span>';
                    } else {
                        indicator.innerHTML = '<span class="taken"><i class="fas fa-times"></i> Taken</span>';
                    }
                });
            }, 500);
        });
    }
    
    // Email availability check
    const emailInput = document.querySelector('input[name="email"]');
    if (emailInput) {
        let emailTimeout;
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value;
            const indicator = document.getElementById('email-availability');
            
            if (!email.includes('@')) {
                indicator.innerHTML = '';
                return;
            }
            
            emailTimeout = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=check_email&email=${encodeURIComponent(email)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.available) {
                        indicator.innerHTML = '<span class="available"><i class="fas fa-check"></i> Available</span>';
                    } else {
                        indicator.innerHTML = '<span class="taken"><i class="fas fa-times"></i> Taken</span>';
                    }
                });
            }, 500);
        });
    }
    
    // Password strength meter
    const passwordInput = document.getElementById('reg-password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('password-strength-bar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            bar.className = 'password-strength-bar';
            if (password.length > 0) {
                if (strength <= 1) bar.classList.add('weak');
                else if (strength === 2) bar.classList.add('medium');
                else bar.classList.add('strong');
            }
        });
    }
    
    // Form validation
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('reg-password').value;
            const confirm = document.getElementById('reg-confirm-password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }
});

function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
    
    document.querySelector(`.auth-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById(`${tab}-form`).classList.add('active');
}

<?php else: ?>
// ==================== MAIN APP JAVASCRIPT ====================

// Global State
const AppState = {
    currentUser: <?php echo json_encode($current_user); ?>,
    currentServer: 0,
    currentChannel: 0,
    currentDM: 0,
    servers: [],
    channels: [],
    friends: [],
    messages: [],
    members: [],
    typingTimeout: null,
    pollingInterval: null,
    typingInterval: null,
    replyTo: null,
    isMuted: false,
    peer: null,
    localStream: null,
    remoteStream: null,
    callStartTime: null,
    callTimer: null,
    lastMessageId: 0,
    isLoadingMessages: false,
    hasMoreMessages: true,
    emojiCategory: 'smileys'
};

// Common Emojis
const EMOJIS = {
    smileys: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥸', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'],
    people: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦵', '🦿', '🦶', '👣', '👂', '🦻', '👃', '🫀', '🫁', '🧠', '🦷', '🦴', '👀', '👁️', '👅', '👄', '💋', '🩸'],
    animals: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐻‍❄️', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🪱', '🐛', '🦋', '🐌', '🐞', '🐜', '🪰', '🪲', '🪳', '🦟', '🦗', '🕷️', '🕸️', '🦂', '🐢', '🐍', '🦎', '🖖', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪', '🐫', '🦒', '🦘', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏', '🐑', '🦙', '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐈‍⬛', '🪶', '🐓', '🦃', '🦚', '🦜', '🦢', '🦩', '🕊️', '🐇', '🦝', '🦨', '🦡', '🦫', '🦦', '🦥', '🐁', '🐀', '🐿️', '🦔'],
    food: ['🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🍍', '🥝', '🥥', '🥑', '🍆', '🥔', '🥕', '🌽', '🌶️', '🫑', '🥒', '🥬', '🥦', '🧄', '🧅', '🍄', '🥜', '🌰', '🍞', '🥐', '🥖', '🥨', '🥯', '🥞', '🧇', '🧀', '🍖', '🍗', '🥩', '🥓', '🍔', '🍟', '🍕', '🌭', '🥪', '🌮', '🌯', '🫔', '🥙', '🧆', '🥚', '🍳', '🥘', '🍲', '🫕', '🥣', '🥗', '🍿', '🧈', '🧂', '🥫', '🍱', '🍘', '🍙', '🍚', '🍛', '🍜', '🍝', '🍠', '🍢', '🍣', '🍤', '🍥', '🥮', '🍡', '🥟', '🥠', '🥡', '🦀', '🦞', '🦐', '🦑', '🦪', '🍦', '🍧', '🍨', '🍩', '🍪', '🎂', '🍰', '🧁', '🥧', '🍫', '🍬', '🍭', '🍮', '🍯'],
    activities: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼', '🤸', '⛹️', '🤺', '🤾', '🏌️', '🏇', '🧘', '🏄', '🏊', '🤽', '🚣', '🧗', '🚵', '🚴', '🏆', '🥇', '🥈', '🥉', '🏅', '🎖️', '🏵️', '🎗️', '🎫', '🎟️', '🎪', '🤹', '🎭', '🩰', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹', '🥁', '🎷', '🎺', '🎸', '🪕', '🎻', '🎲', '♟️', '🎯', '🎳', '🎮', '🎰', '🧩'],
    travel: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚛', '🚜', '🦯', '🦽', '🦼', '🛴', '🚲', '🛵', '🏍️', '🛺', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬', '🛩️', '💺', '🛰️', '🚀', '🛸', '🚁', '🛶', '⛵', '🚤', '🛥️', '🛳️', '⛴️', '🚢', '⚓', '⛽', '🚧', '🚦', '🚥', '🚏', '🗺️', '🗿', '🗽', '🗼', '🏰', '🏯', '🏟️', '🎡', '🎢', '🎠', '⛲', '⛱️', '🏖️', '🏝️', '🏜️', '🌋', '⛰️', '🏔️', '🗻', '🏕️', '⛺', '🏠', '🏡', '🏘️', '🏚️', '🏗️', '🏭', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦', '🏨', '🏪', '🏫', '🏩', '💒', '🏛️', '⛪', '🕌', '🕍', '🛕', '🕋', '⛩️', '🛤️', '🛣️', '🗾', '🎑', '🏞️', '🌅', '🌄', '🌠', '🎇', '🎆', '🌇', '🌆', '🏙️', '🌃', '🌌', '🌉', '🌁'],
    objects: ['⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️', '🗜️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️', '⌛', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴', '💶', '💷', '🪙', '💰', '💳', '💎', '⚖️', '🪜', '🧰', '🪛', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🪚', '🔩', '⚙️', '🪤', '🧱', '⛓️', '🧲', '🔫', '💣', '🧱', '🔪', '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💎', '🔔', '🔕', '📢', '📣', '🥁', '🎷', '🎺', '🎸', '🪕', '🎻', '🎲', '♟️', '🎯', '🎳', '🎮', '🎰', '🧩'],
    symbols: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️', '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠', 'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🈳', '🈂', '🛂', '🛃', '🛄', '🛅', '🛗', '🚹', '🚺', '🚼', '⚧', '🚻', '🚮', '🎦', '📶', '🈁', '🔣', 'ℹ️', '🔤', '🔡', '🔠', '🆖', '🆗', '🆙', '🆒', '🆕', '🆓', '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '🔢', '#️⃣', '*️⃣', '⏏️', '▶️', '⏸️', '⏯️', '⏹️', '⏺️', '⏭️', '⏮️', '⏩', '⏪', '⏫', '⏬', '◀️', '🔼', '🔽', '➡️', '⬅️', '⬆️', '⬇️', '↗️', '↘️', '↙️', '↖️', '↕️', '↔️', '↪️', '↩️', '⤴️', '⤵️', '🔀', '🔁', '🔂', '🔄', '🔃', '🎵', '🎶', '➕', '➖', '➗', '✖️', '💲', '💱', '™️', '©️', '®️', '〰️', '➰', '➿', '🔚', '🔙', '🔛', '🔝', '🔜', '✔️', '☑️', '🔘', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤', '🔺', '🔻', '🔸', '🔹', '🔶', '🔷', '🔳', '🔲', '▪️', '▫️', '◾', '◽', '◼️', '◻️', '🟥', '🟧', '🟨', '🟩', '🟦', '🟪', '⬛', '⬜', '🟫', '🔈', '🔇', '🔉', '🔊', '🔔', '🔕', '📣', '📢', '💬', '💭', '🗯️', '♠️', '♣️', '♥️', '♦️', '🃏', '🎴', '🀄', '🕐', '🕑', '🕒', '🕓', '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛', '🕜', '🕝', '🕞', '🕟', '🕠', '🕡', '🕢', '🕣', '🕤', '🕥', '🕦', '🕧']
};

// Initialize App
document.addEventListener('DOMContentLoaded', function() {
    loadServers();
    loadFriends();
    loadStatuses();
    startPolling();
    initEmojiPicker();
    initTooltips();
    initContextMenu();
    
    // Handle visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page hidden - reduce polling
        } else {
            // Page visible - refresh data
            refreshAll();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape to close modals
        if (e.key === 'Escape') {
            closeAllModals();
            closeEmojiPicker();
            closeGifPicker();
        }
        
        // Ctrl/Cmd + K for quick switcher
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            showQuickSwitcher();
        }
        
        // Ctrl/Cmd + / for help
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            showKeyboardShortcuts();
        }
    });
    
    // Click outside to close pickers
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.emoji-picker') && !e.target.closest('.input-tool-btn')) {
            closeEmojiPicker();
        }
        if (!e.target.closest('.gif-picker') && !e.target.closest('.input-tool-btn')) {
            closeGifPicker();
        }
    });
});

// ==================== SERVER FUNCTIONS ====================

function loadServers() {
    apiRequest('get_servers', {}, function(data) {
        if (data.servers) {
            AppState.servers = data.servers;
            renderServers();
        }
    });
}

function renderServers() {
    const container = document.getElementById('server-list');
    if (!container) return;
    
    container.innerHTML = AppState.servers.map(server => `
        <div class="guild-icon ${server.unread_count > 0 ? 'unread' : ''} ${AppState.currentServer == server.server_id ? 'active' : ''}" 
             data-tooltip="${escapeHtml(server.server_name)}"
             data-server-id="${server.server_id}"
             onclick="selectServer(${server.server_id})"
             style="${server.server_icon ? '' : 'background: ' + stringToColor(server.server_name)}">
            ${server.server_icon 
                ? `<img src="${escapeHtml(server.server_icon)}" alt="">` 
                : escapeHtml(server.server_name.substring(0, 2).toUpperCase())
            }
        </div>
    `).join('');
    
    initTooltips();
}

function selectServer(serverId) {
    AppState.currentServer = serverId;
    AppState.currentChannel = 0;
    AppState.currentDM = 0;
    AppState.messages = [];
    AppState.lastMessageId = 0;
    AppState.hasMoreMessages = true;
    
    // Update UI
    document.querySelectorAll('.guild-icon').forEach(el => el.classList.remove('active'));
    document.querySelector(`.guild-icon[data-server-id="${serverId}"]`)?.classList.add('active');
    
    if (serverId === 0) {
        // DM view
        document.getElementById('channels-header-title').textContent = 'Direct Messages';
        document.getElementById('chat-header-title').textContent = 'Welcome';
        document.getElementById('chat-header-icon').textContent = '@';
        document.getElementById('welcome-screen').classList.remove('hidden');
        document.getElementById('messages-container').classList.add('hidden');
        document.getElementById('chat-input-container').classList.add('hidden');
        document.getElementById('status-bar').classList.add('hidden');
        document.getElementById('members-sidebar').classList.add('hidden');
        renderDMList();
    } else {
        // Server view
        const server = AppState.servers.find(s => s.server_id == serverId);
        if (server) {
            document.getElementById('channels-header-title').textContent = server.server_name;
        }
        loadChannels(serverId);
        loadMembers(serverId);
        document.getElementById('members-sidebar').classList.remove('hidden');
    }
    
    renderServers();
}

function showCreateServerModal() {
    document.getElementById('create-server-modal').classList.add('active');
}

function showJoinServerModal() {
    document.getElementById('join-server-modal').classList.add('active');
}

function createServer() {
    const name = document.getElementById('new-server-name').value;
    const description = document.getElementById('new-server-description').value;
    const iconInput = document.getElementById('new-server-icon');
    
    if (!name) {
        showToast('Please enter a server name', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_server');
    formData.append('server_name', name);
    formData.append('server_description', description);
    if (iconInput.files[0]) {
        formData.append('server_icon', iconInput.files[0]);
    }
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Server created successfully!', 'success');
            closeModal('create-server-modal');
            loadServers();
            selectServer(data.server_id);
        } else {
            showToast(data.error || 'Failed to create server', 'error');
        }
    });
}

function joinServer() {
    const code = document.getElementById('join-invite-code').value;
    
    if (!code) {
        showToast('Please enter an invite code', 'error');
        return;
    }
    
    apiRequest('join_server', { invite_code: code }, function(data) {
        if (data.success) {
            showToast('Joined server successfully!', 'success');
            closeModal('join-server-modal');
            loadServers();
            selectServer(data.server_id);
        } else {
            showToast(data.error || 'Invalid invite code', 'error');
        }
    });
}

function showServerMenu() {
    if (AppState.currentServer > 0) {
        showServerSettings();
    }
}

function showServerSettings() {
    const server = AppState.servers.find(s => s.server_id == AppState.currentServer);
    if (!server) return;
    
    document.getElementById('server-settings-name').value = server.server_name;
    document.getElementById('server-settings-description').value = server.server_description || '';
    document.getElementById('server-invite-code').value = server.invite_code;
    
    // Show delete button only for owner
    const isOwner = server.role === 'admin' && server.owner_id == AppState.currentUser.user_id;
    document.getElementById('delete-server-btn').classList.toggle('hidden', !isOwner);
    
    document.getElementById('server-settings-modal').classList.add('active');
}

function updateServerSettings() {
    apiRequest('update_server', {
        server_id: AppState.currentServer,
        server_name: document.getElementById('server-settings-name').value,
        server_description: document.getElementById('server-settings-description').value
    }, function(data) {
        if (data.success) {
            showToast('Server updated', 'success');
            closeModal('server-settings-modal');
            loadServers();
        } else {
            showToast(data.error, 'error');
        }
    });
}

function leaveCurrentServer() {
    if (!confirm('Are you sure you want to leave this server?')) return;
    
    apiRequest('leave_server', { server_id: AppState.currentServer }, function(data) {
        if (data.success) {
            showToast('Left server', 'success');
            closeModal('server-settings-modal');
            loadServers();
            selectServer(0);
        } else {
            showToast(data.error, 'error');
        }
    });
}

function deleteCurrentServer() {
    if (!confirm('Are you sure? This will permanently delete the server and all its data!')) return;
    
    apiRequest('delete_server', { server_id: AppState.currentServer }, function(data) {
        if (data.success) {
            showToast('Server deleted', 'success');
            closeModal('server-settings-modal');
            loadServers();
            selectServer(0);
        } else {
            showToast(data.error, 'error');
        }
    });
}

function copyInviteCode() {
    const code = document.getElementById('server-invite-code').value;
    navigator.clipboard.writeText(code).then(() => {
        showToast('Invite code copied!', 'success');
    });
}

function regenerateInvite() {
    apiRequest('generate_invite', { server_id: AppState.currentServer }, function(data) {
        if (data.success) {
            document.getElementById('server-invite-code').value = data.invite_code;
            showToast('New invite code generated', 'success');
            loadServers();
        }
    });
}

// ==================== CHANNEL FUNCTIONS ====================

function loadChannels(serverId) {
    apiRequest('get_channels', { server_id: serverId }, function(data) {
        if (data.channels) {
            AppState.channels = data.channels;
            AppState.userRole = data.user_role;
            renderChannels();
        }
    });
}

function renderChannels() {
    const container = document.getElementById('server-channels');
    if (!container) return;
    
    const textChannels = AppState.channels.filter(c => c.channel_type === 'text' || c.channel_type === 'announcement' || c.channel_type === 'rules');
    const voiceChannels = AppState.channels.filter(c => c.channel_type === 'voice');
    
    let html = '';
    
    if (textChannels.length > 0) {
        html += `
            <div class="channel-category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Text Channels</span>
                </div>
                <div class="category-content">
                    ${textChannels.map(channel => `
                        <div class="channel-item ${AppState.currentChannel == channel.channel_id ? 'active' : ''} ${channel.unread_count > 0 ? 'unread' : ''}"
                             onclick="selectChannel(${channel.channel_id})">
                            <i class="fas ${channel.channel_type === 'rules' ? 'fa-scroll' : channel.channel_type === 'announcement' ? 'fa-bullhorn' : channel.is_private ? 'fa-lock' : 'fa-hashtag'}"></i>
                            <span>${escapeHtml(channel.channel_name)}</span>
                            ${channel.unread_count > 0 ? `<span class="unread-badge">${channel.unread_count}</span>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    if (voiceChannels.length > 0) {
        html += `
            <div class="channel-category">
                <div class="category-header" onclick="toggleCategory(this)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Voice Channels</span>
                </div>
                <div class="category-content">
                    ${voiceChannels.map(channel => `
                        <div class="channel-item" onclick="joinVoiceChannel(${channel.channel_id})">
                            <i class="fas fa-volume-up"></i>
                            <span>${escapeHtml(channel.channel_name)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Add create channel button for admin/operator
    if (AppState.userRole === 'admin' || AppState.userRole === 'operator') {
        html += `
            <div class="channel-item" onclick="showCreateChannelModal()" style="color: var(--status-online);">
                <i class="fas fa-plus"></i>
                <span>Create Channel</span>
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.classList.remove('hidden');
}

function selectChannel(channelId) {
    AppState.currentChannel = channelId;
    AppState.currentDM = 0;
    AppState.messages = [];
    AppState.lastMessageId = 0;
    AppState.hasMoreMessages = true;
    
    const channel = AppState.channels.find(c => c.channel_id == channelId);
    if (channel) {
        document.getElementById('chat-header-title').textContent = channel.channel_name;
        document.getElementById('chat-header-icon').innerHTML = '<i class="fas fa-hashtag"></i>';
    }
    
    document.getElementById('welcome-screen').classList.add('hidden');
    document.getElementById('messages-container').classList.remove('hidden');
    document.getElementById('chat-input-container').classList.remove('hidden');
    document.getElementById('status-bar').classList.add('hidden');
    
    renderChannels();
    loadMessages();
    loadPinnedMessages();
}

function showCreateChannelModal() {
    document.getElementById('create-channel-modal').classList.add('active');
}

function createChannel() {
    const name = document.getElementById('new-channel-name').value;
    const type = document.getElementById('new-channel-type').value;
    const isPrivate = document.getElementById('new-channel-private').checked;
    
    if (!name) {
        showToast('Please enter a channel name', 'error');
        return;
    }
    
    apiRequest('create_channel', {
        server_id: AppState.currentServer,
        channel_name: name,
        channel_type: type,
        is_private: isPrivate ? 1 : 0
    }, function(data) {
        if (data.success) {
            showToast('Channel created', 'success');
            closeModal('create-channel-modal');
            loadChannels(AppState.currentServer);
        } else {
            showToast(data.error, 'error');
        }
    });
}

function toggleCategory(header) {
    header.classList.toggle('collapsed');
    const content = header.nextElementSibling;
    if (content) {
        content.style.display = header.classList.contains('collapsed') ? 'none' : 'block';
    }
}

// ==================== MESSAGE FUNCTIONS ====================

function loadMessages(beforeId = 0) {
    if (AppState.isLoadingMessages) return;
    AppState.isLoadingMessages = true;
    
    const params = { limit: 50 };
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        AppState.isLoadingMessages = false;
        return;
    }
    
    if (beforeId > 0) {
        params.before_id = beforeId;
    }
    
    apiRequest('get_messages', params, function(data) {
        AppState.isLoadingMessages = false;
        
        if (data.messages) {
            if (beforeId === 0) {
                AppState.messages = data.messages;
            } else {
                AppState.messages = [...data.messages, ...AppState.messages];
            }
            
            if (data.messages.length < 50) {
                AppState.hasMoreMessages = false;
            }
            
            renderMessages(beforeId === 0);
            
            // Mark messages as read
            data.messages.forEach(msg => {
                if (msg.sender_id != AppState.currentUser.user_id && !msg.is_read) {
                    markMessageRead(msg.message_id);
                }
            });
        }
    });
}

function renderMessages(scrollToBottom = true) {
    const container = document.getElementById('messages-container');
    if (!container) return;
    
    if (AppState.messages.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-comment-slash"></i><h3>No messages yet</h3><p>Be the first to send a message!</p></div>';
        return;
    }
    
    let lastDate = null;
    let lastSender = null;
    
    container.innerHTML = AppState.messages.map((msg, index) => {
        const date = new Date(msg.created_at).toDateString();
        let dateSeparator = '';
        if (date !== lastDate) {
            dateSeparator = `<div class="date-separator"><span>${formatDate(msg.created_at)}</span></div>`;
            lastDate = date;
            lastSender = null;
        }
        
        const isSystem = msg.message_type === 'system';
        const isOwn = msg.sender_id == AppState.currentUser.user_id;
        const showAvatar = lastSender !== msg.sender_id;
        
        if (!isSystem) {
            lastSender = msg.sender_id;
        }
        
        let html = dateSeparator;
        
        if (isSystem) {
            html += `
                <div class="system-message" data-message-id="${msg.message_id}">
                    <i class="fas fa-info-circle"></i>
                    <span>${escapeHtml(msg.content)}</span>
                </div>
            `;
        } else {
            const avatar = msg.profile_pic 
                ? `<img src="${escapeHtml(msg.profile_pic)}" alt="">`
                : escapeHtml(msg.real_name.substring(0, 1).toUpperCase());
            
            const reactions = msg.reactions ? parseReactions(msg.reactions) : [];
            const reactionHtml = reactions.length > 0 ? `
                <div class="message-reactions">
                    ${reactions.map(r => `
                        <div class="reaction ${r.users.includes(AppState.currentUser.user_id) ? 'active' : ''}" 
                             onclick="toggleReaction(${msg.message_id}, '${r.emoji}')">
                            <span>${r.emoji}</span>
                            <span class="reaction-count">${r.count}</span>
                        </div>
                    `).join('')}
                </div>
            ` : '';
            
            const replyHtml = msg.reply_to ? `
                <div class="message-reply-to">
                    <i class="fas fa-reply"></i>
                    <span>Replying to</span>
                    <span class="reply-author">@${escapeHtml(msg.reply_author || 'Unknown')}</span>
                </div>
            ` : '';
            
            const readReceipts = isOwn && msg.read_by ? `
                <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 4px;">
                    ${msg.read_by.length > 0 ? '<i class="fas fa-check-double" style="color: var(--primary);"></i>' : '<i class="fas fa-check"></i>'}
                </span>
            ` : '';
            
            html += `
                <div class="message-group ${isOwn ? 'own' : ''}" data-message-id="${msg.message_id}" data-sender-id="${msg.sender_id}">
                    ${showAvatar ? `
                        <div class="message-avatar" style="background: ${stringToColor(msg.username)}">
                            ${avatar}
                        </div>
                    ` : '<div style="width: 40px;"></div>'}
                    <div class="message-content">
                        ${showAvatar ? `
                            <div class="message-header">
                                <span class="message-author">${escapeHtml(msg.real_name)}</span>
                                <span class="message-timestamp">${formatTime(msg.created_at)}</span>
                                ${msg.is_edited ? '<span class="message-edited">(edited)</span>' : ''}
                                ${readReceipts}
                            </div>
                        ` : ''}
                        ${replyHtml}
                        <div class="message-body">
                            ${formatMessageContent(msg)}
                        </div>
                        ${reactionHtml}
                    </div>
                    <div class="message-actions">
                        <button class="message-action-btn" onclick="setReplyTo(${msg.message_id})" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                        ${isOwn ? `
                            <button class="message-action-btn" onclick="editMessage(${msg.message_id})" title="Edit">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <button class="message-action-btn" onclick="deleteMessage(${msg.message_id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        return html;
    }).join('');
    
    if (scrollToBottom) {
        scrollToBottomMessages();
    }
}

function formatMessageContent(msg) {
    if (msg.is_deleted) {
        return `<i style="color: var(--text-muted); font-style: italic;">This message was deleted</i>`;
    }
    
    if (msg.gif_url) {
        return `<img src="${escapeHtml(msg.gif_url)}" class="gif" alt="GIF">`;
    }
    
    if (msg.file_url) {
        const fileName = msg.file_url.split('/').pop();
        const ext = fileName.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        
        if (isImage) {
            return `<img src="${escapeHtml(msg.file_url)}" style="max-width: 400px; border-radius: 8px; margin-top: 8px;" alt="">`;
        }
        
        return `
            <div class="file-attachment">
                <i class="fas fa-file"></i>
                <div class="file-info">
                    <div class="file-name">${escapeHtml(fileName)}</div>
                </div>
                <a href="${escapeHtml(msg.file_url)}" download class="file-download">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
    }
    
    // Format text
    let content = escapeHtml(msg.content);
    
    // Bold
    content = content.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    // Italic
    content = content.replace(/\*(.+?)\*/g, '<em>$1</em>');
    // Underline
    content = content.replace(/__(.+?)__/g, '<u>$1</u>');
    // Strikethrough
    content = content.replace(/~~(.+?)~~/g, '<del>$1</del>');
    // Code
    content = content.replace(/`(.+?)`/g, '<code>$1</code>');
    // Code block
    content = content.replace(/```([\s\S]+?)```/g, '<pre><code>$1</code></pre>');
    // Spoiler
    content = content.replace(/\|\|(.+?)\|\|/g, '<span class="spoiler" onclick="this.classList.add(\'revealed\')">$1</span>');
    // Mentions
    content = content.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
    // Links
    content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
    
    return content;
}

function parseReactions(reactionsStr) {
    if (!reactionsStr) return [];
    
    const reactions = {};
    reactionsStr.split('|').forEach(r => {
        const [emoji, userId] = r.split(':');
        if (!reactions[emoji]) {
            reactions[emoji] = { emoji, count: 0, users: [] };
        }
        reactions[emoji].count++;
        reactions[emoji].users.push(parseInt(userId));
    });
    
    return Object.values(reactions);
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (!content && !AppState.replyTo) return;
    
    const params = {
        content: content,
        reply_to: AppState.replyTo || 0
    };
    
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        return;
    }
    
    apiRequest('send_message', params, function(data) {
        if (data.success) {
            input.value = '';
            cancelReply();
            AppState.messages.push(data.message);
            renderMessages(true);
        }
    });
}

function sendGIF(gifUrl) {
    const params = { gif_url: gifUrl };
    
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        return;
    }
    
    apiRequest('send_message', params, function(data) {
        if (data.success) {
            closeGifPicker();
            AppState.messages.push(data.message);
            renderMessages(true);
        }
    });
}

function handleFileUpload(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('file', file);
    
    if (AppState.currentChannel > 0) {
        formData.append('channel_id', AppState.currentChannel);
    } else if (AppState.currentDM > 0) {
        formData.append('recipient_id', AppState.currentDM);
    }
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            AppState.messages.push(data.message);
            renderMessages(true);
        }
    });
    
    input.value = '';
}

function handleMessageKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function handleTyping() {
    clearTimeout(AppState.typingTimeout);
    
    const params = {};
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        return;
    }
    
    apiRequest('typing', params);
    
    AppState.typingTimeout = setTimeout(() => {
        // Typing stopped
    }, 5000);
}

function setReplyTo(messageId) {
    const message = AppState.messages.find(m => m.message_id == messageId);
    if (!message) return;
    
    AppState.replyTo = messageId;
    document.getElementById('reply-author').textContent = message.real_name;
    document.getElementById('reply-text').textContent = message.content.substring(0, 50);
    document.getElementById('reply-preview').classList.remove('hidden');
    document.getElementById('message-input').focus();
}

function cancelReply() {
    AppState.replyTo = null;
    document.getElementById('reply-preview').classList.add('hidden');
}

function deleteMessage(messageId) {
    if (!confirm('Delete this message?')) return;
    
    apiRequest('delete_message', { message_id: messageId }, function(data) {
        if (data.success) {
            const msg = AppState.messages.find(m => m.message_id == messageId);
            if (msg) {
                msg.is_deleted = 1;
                msg.content = 'This message was deleted';
                renderMessages(false);
            }
            showToast('Message deleted', 'success');
        }
    });
}

function editMessage(messageId) {
    const message = AppState.messages.find(m => m.message_id == messageId);
    if (!message) return;
    
    const newContent = prompt('Edit message:', message.content);
    if (newContent === null || newContent === message.content) return;
    
    apiRequest('edit_message', { message_id: messageId, content: newContent }, function(data) {
        if (data.success) {
            message.content = newContent;
            message.is_edited = 1;
            renderMessages(false);
        }
    });
}

function toggleReaction(messageId, emoji) {
    const message = AppState.messages.find(m => m.message_id == messageId);
    if (!message) return;
    
    const reactions = message.reactions ? parseReactions(message.reactions) : [];
    const existing = reactions.find(r => r.emoji === emoji && r.users.includes(AppState.currentUser.user_id));
    
    if (existing) {
        apiRequest('remove_reaction', { message_id: messageId, emoji: emoji }, function() {
            loadMessages();
        });
    } else {
        apiRequest('add_reaction', { message_id: messageId, emoji: emoji }, function() {
            loadMessages();
        });
    }
}

function markMessageRead(messageId) {
    apiRequest('mark_read', { message_id: messageId });
}

function scrollToBottomMessages() {
    const container = document.getElementById('chat-messages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function loadPinnedMessages() {
    if (AppState.currentChannel === 0) {
        document.getElementById('pinned-messages').classList.add('hidden');
        return;
    }
    
    apiRequest('get_pinned_messages', { channel_id: AppState.currentChannel }, function(data) {
        if (data.pinned && data.pinned.length > 0) {
            const container = document.getElementById('pinned-messages');
            container.innerHTML = data.pinned.map(p => `
                <div class="pinned-message">
                    <i class="fas fa-thumbtack pinned-icon"></i>
                    <div class="pinned-content">
                        <div class="pinned-author">${escapeHtml(p.real_name)}</div>
                        <div class="pinned-text">${escapeHtml(p.content)}</div>
                    </div>
                    <i class="fas fa-times pinned-unpin" onclick="unpinMessage(${p.pin_id})"></i>
                </div>
            `).join('');
            container.classList.remove('hidden');
        } else {
            document.getElementById('pinned-messages').classList.add('hidden');
        }
    });
}

function pinMessage() {
    if (!AppState.contextMessageId) return;
    
    apiRequest('pin_message', { 
        message_id: AppState.contextMessageId,
        channel_id: AppState.currentChannel
    }, function(data) {
        if (data.success) {
            showToast('Message pinned', 'success');
            loadPinnedMessages();
        }
    });
    hideContextMenu();
}

function unpinMessage(pinId) {
    apiRequest('unpin_message', { pin_id: pinId }, function(data) {
        if (data.success) {
            loadPinnedMessages();
        }
    });
}

function togglePinnedMessages() {
    const pinned = document.getElementById('pinned-messages');
    pinned.classList.toggle('hidden');
}

function searchMessages() {
    const query = document.getElementById('search-query').value;
    if (query.length < 2) {
        document.getElementById('search-results').innerHTML = '';
        return;
    }
    
    const params = { query: query };
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    }
    
    apiRequest('search_messages', params, function(data) {
        const container = document.getElementById('search-results');
        if (data.messages && data.messages.length > 0) {
            container.innerHTML = data.messages.map(m => `
                <div class="list-item" onclick="jumpToMessage(${m.message_id})">
                    <div class="list-item-icon" style="background: ${stringToColor(m.username)}">
                        ${m.profile_pic ? `<img src="${escapeHtml(m.profile_pic)}" alt="">` : escapeHtml(m.real_name.substring(0, 1))}
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">${escapeHtml(m.real_name)}</div>
                        <div class="list-item-subtitle">${escapeHtml(m.content.substring(0, 100))}</div>
                    </div>
                    <span class="text-muted">${formatTime(m.created_at)}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><p>No messages found</p></div>';
        }
    });
}

function jumpToMessage(messageId) {
    // Load messages around this one
    closeModal('search-modal');
    // Implementation would scroll to specific message
}

// ==================== FRIEND FUNCTIONS ====================

function loadFriends() {
    apiRequest('get_friends', {}, function(data) {
        if (data.friends) {
            AppState.friends = data.friends;
            AppState.pendingRequests = data.pending || [];
            renderDMList();
            updateFriendBadge();
        }
    });
}

function renderDMList() {
    const container = document.getElementById('dm-list');
    if (!container) return;
    
    if (AppState.friends.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding: 20px;"><p>No friends yet</p></div>';
        return;
    }
    
    container.innerHTML = AppState.friends.map(friend => `
        <div class="dm-item ${AppState.currentDM == friend.user_id ? 'active' : ''} ${friend.unread_count > 0 ? 'unread' : ''}"
             onclick="selectDM(${friend.user_id})">
            <div class="dm-avatar" style="background: ${stringToColor(friend.username)}">
                ${friend.profile_pic ? `<img src="${escapeHtml(friend.profile_pic)}" alt="">` : escapeHtml(friend.real_name.substring(0, 1))}
                <span class="status-indicator ${friend.online_status ? 'online' : 'offline'}"></span>
            </div>
            <div class="dm-info">
                <div class="dm-name">${escapeHtml(friend.real_name)}</div>
                <div class="dm-preview">${friend.custom_status || (friend.online_status ? 'Online' : 'Offline')}</div>
            </div>
            ${friend.unread_count > 0 ? `<span class="unread-badge">${friend.unread_count}</span>` : ''}
        </div>
    `).join('');
}

function selectDM(userId) {
    AppState.currentDM = userId;
    AppState.currentChannel = 0;
    AppState.currentServer = 0;
    AppState.messages = [];
    AppState.lastMessageId = 0;
    AppState.hasMoreMessages = true;
    
    const friend = AppState.friends.find(f => f.user_id == userId);
    if (friend) {
        document.getElementById('chat-header-title').textContent = friend.real_name;
        document.getElementById('chat-header-icon').innerHTML = '@';
    }
    
    document.getElementById('welcome-screen').classList.add('hidden');
    document.getElementById('messages-container').classList.remove('hidden');
    document.getElementById('chat-input-container').classList.remove('hidden');
    document.getElementById('status-bar').classList.remove('hidden');
    document.getElementById('members-sidebar').classList.add('hidden');
    
    renderDMList();
    loadMessages();
    loadStatuses();
}

function showAddFriendModal() {
    document.getElementById('add-friend-modal').classList.add('active');
}

function sendFriendRequest() {
    const username = document.getElementById('friend-username').value;
    if (!username) {
        showToast('Please enter a username', 'error');
        return;
    }
    
    apiRequest('send_friend_request', { username: username }, function(data) {
        if (data.success) {
            showToast('Friend request sent!', 'success');
            closeModal('add-friend-modal');
            document.getElementById('friend-username').value = '';
        } else {
            showToast(data.error || 'Failed to send request', 'error');
        }
    });
}

function respondFriendRequest(friendshipId, response) {
    apiRequest('respond_friend_request', { 
        friendship_id: friendshipId, 
        response: response 
    }, function(data) {
        if (data.success) {
            loadFriends();
        }
    });
}

function showFriendsPage() {
    renderFriendsList();
    document.getElementById('friends-modal').classList.add('active');
}

function renderFriendsList() {
    const container = document.getElementById('friends-content');
    
    const allFriends = AppState.friends.map(f => `
        <div class="list-item">
            <div class="list-item-icon" style="background: ${stringToColor(f.username)}">
                ${f.profile_pic ? `<img src="${escapeHtml(f.profile_pic)}" alt="">` : escapeHtml(f.real_name.substring(0, 1))}
            </div>
            <div class="list-item-content">
                <div class="list-item-title">${escapeHtml(f.real_name)}</div>
                <div class="list-item-subtitle">@${escapeHtml(f.username)}</div>
            </div>
            <button class="btn btn-secondary" onclick="selectDM(${f.user_id}); closeModal('friends-modal');">Message</button>
        </div>
    `).join('');
    
    const pending = AppState.pendingRequests.map(p => `
        <div class="list-item">
            <div class="list-item-icon" style="background: ${stringToColor(p.username)}">
                ${p.profile_pic ? `<img src="${escapeHtml(p.profile_pic)}" alt="">` : escapeHtml(p.real_name.substring(0, 1))}
            </div>
            <div class="list-item-content">
                <div class="list-item-title">${escapeHtml(p.real_name)}</div>
                <div class="list-item-subtitle">Wants to be your friend</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-success" onclick="respondFriendRequest(${p.friendship_id}, 'accept')">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-danger" onclick="respondFriendRequest(${p.friendship_id}, 'decline')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = `
        <div class="tab-content active" id="tab-all">
            ${allFriends || '<div class="empty-state"><p>No friends yet</p></div>'}
        </div>
        <div class="tab-content" id="tab-pending">
            ${pending || '<div class="empty-state"><p>No pending requests</p></div>'}
        </div>
        <div class="tab-content" id="tab-blocked">
            <div class="empty-state"><p>No blocked users</p></div>
        </div>
    `;
    
    document.getElementById('pending-count').textContent = AppState.pendingRequests.length;
    document.getElementById('pending-count').classList.toggle('hidden', AppState.pendingRequests.length === 0);
}

function showFriendsTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.tab[onclick="showFriendsTab('${tab}')"]`).classList.add('active');
    
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
}

function updateFriendBadge() {
    const badge = document.getElementById('friend-request-badge');
    const count = AppState.pendingRequests ? AppState.pendingRequests.length : 0;
    badge.textContent = count;
    badge.classList.toggle('hidden', count === 0);
}

// ==================== STATUS FUNCTIONS ====================

function loadStatuses() {
    apiRequest('get_statuses', {}, function(data) {
        if (data.statuses) {
            AppState.statuses = data.statuses;
            renderStatusBar();
        }
    });
}

function renderStatusBar() {
    const container = document.getElementById('status-bar');
    if (!container) return;
    
    const myStatus = AppState.statuses.find(s => s.user_id == AppState.currentUser.user_id);
    const friendStatuses = AppState.statuses.filter(s => s.user_id != AppState.currentUser.user_id && !s.has_viewed);
    
    let html = `
        <div class="status-item" onclick="showStatusModal()">
            <div class="status-add">
                <i class="fas fa-plus"></i>
            </div>
            <span class="status-name">Add Status</span>
        </div>
    `;
    
    if (myStatus) {
        html += `
            <div class="status-item" onclick="showStatusesPage()">
                <div class="status-avatar has-status" style="background: ${myStatus.background_color}">
                    ${AppState.currentUser.profile_pic ? `<img src="${escapeHtml(AppState.currentUser.profile_pic)}" alt="">` : escapeHtml(AppState.currentUser.real_name.substring(0, 1))}
                </div>
                <span class="status-name">My Status</span>
            </div>
        `;
    }
    
    friendStatuses.slice(0, 10).forEach(status => {
        html += `
            <div class="status-item" onclick="viewStatus(${status.status_id})">
                <div class="status-avatar has-status" style="background: ${status.background_color}">
                    ${status.profile_pic ? `<img src="${escapeHtml(status.profile_pic)}" alt="">` : escapeHtml(status.real_name.substring(0, 1))}
                </div>
                <span class="status-name">${escapeHtml(status.real_name)}</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function showStatusModal() {
    document.getElementById('status-modal').classList.add('active');
}

function selectStatusColor(color) {
    document.getElementById('status-color').value = color;
}

function createStatus() {
    const content = document.getElementById('status-content').value;
    const color = document.getElementById('status-color').value;
    
    if (!content) {
        showToast('Please enter status text', 'error');
        return;
    }
    
    apiRequest('create_status', { content: content, background_color: color }, function(data) {
        if (data.success) {
            showToast('Status created!', 'success');
            closeModal('status-modal');
            document.getElementById('status-content').value = '';
            loadStatuses();
        }
    });
}

function showStatusesPage() {
    renderStatusesList();
    document.getElementById('statuses-modal').classList.add('active');
}

function renderStatusesList() {
    const container = document.getElementById('statuses-list');
    
    if (!AppState.statuses || AppState.statuses.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>No statuses yet</p></div>';
        return;
    }
    
    container.innerHTML = AppState.statuses.map(s => `
        <div class="card mb-4">
            <div class="card-header" style="display: flex; align-items: center; gap: 12px;">
                <div class="dm-avatar" style="background: ${stringToColor(s.username)}; width: 40px; height: 40px;">
                    ${s.profile_pic ? `<img src="${escapeHtml(s.profile_pic)}" alt="">` : escapeHtml(s.real_name.substring(0, 1))}
                </div>
                <div>
                    <div style="font-weight: 600;">${escapeHtml(s.real_name)}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">${formatTime(s.created_at)}</div>
                </div>
            </div>
            <div class="card-body" style="background: ${s.background_color}; color: white; font-size: 1.25rem; padding: 24px;">
                ${escapeHtml(s.content)}
            </div>
            <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">
                    <i class="fas fa-eye"></i> ${s.view_count} views
                </span>
                ${s.user_id != AppState.currentUser.user_id && !s.has_viewed ? `
                    <button class="btn btn-primary" onclick="viewStatus(${s.status_id})">View Status</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function viewStatus(statusId) {
    apiRequest('view_status', { status_id: statusId }, function() {
        loadStatuses();
    });
}

// ==================== MEMBER FUNCTIONS ====================

function loadMembers(serverId) {
    apiRequest('get_server_members', { server_id: serverId }, function(data) {
        if (data.members) {
            AppState.members = data.members;
            AppState.userRole = data.user_role;
            renderMembers();
        }
    });
}

function renderMembers() {
    const container = document.getElementById('members-list');
    if (!container) return;
    
    const online = AppState.members.filter(m => m.online_status);
    const offline = AppState.members.filter(m => !m.online_status);
    
    let html = '';
    
    if (online.length > 0) {
        html += `
            <div class="member-group">
                <div class="member-group-title">Online — ${online.length}</div>
                ${online.map(m => renderMemberItem(m)).join('')}
            </div>
        `;
    }
    
    if (offline.length > 0) {
        html += `
            <div class="member-group">
                <div class="member-group-title">Offline — ${offline.length}</div>
                ${offline.map(m => renderMemberItem(m)).join('')}
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function renderMemberItem(member) {
    const roleColor = member.role === 'admin' ? '#F23F43' : member.role === 'operator' ? '#F0B232' : '#B5BAC1';
    
    return `
        <div class="member-item" onclick="selectDM(${member.user_id})">
            <div class="member-avatar" style="background: ${stringToColor(member.username)}">
                ${member.profile_pic ? `<img src="${escapeHtml(member.profile_pic)}" alt="">` : escapeHtml(member.real_name.substring(0, 1))}
                <span class="status-indicator ${member.online_status ? 'online' : 'offline'}"></span>
            </div>
            <div class="member-info">
                <div class="member-name" style="color: ${roleColor}">${escapeHtml(member.real_name)}</div>
                <div class="member-status">${member.custom_status || (member.online_status ? 'Online' : 'Offline')}</div>
            </div>
        </div>
    `;
}

function toggleMembersSidebar() {
    document.getElementById('members-sidebar').classList.toggle('hidden');
}

// ==================== PROFILE FUNCTIONS ====================

function showProfileModal() {
    document.getElementById('profile-modal').classList.add('active');
}

function updateProfile() {
    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('real_name', document.getElementById('profile-real-name').value);
    formData.append('about_status', document.getElementById('profile-about').value);
    formData.append('custom_status', document.getElementById('profile-custom-status').value);
    
    const picInput = document.getElementById('profile-pic-input');
    if (picInput.files[0]) {
        formData.append('profile_pic', picInput.files[0]);
    }
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Profile updated!', 'success');
            closeModal('profile-modal');
            // Update UI
            AppState.currentUser.real_name = document.getElementById('profile-real-name').value;
            document.getElementById('user-display-name').textContent = AppState.currentUser.real_name;
            document.getElementById('user-custom-status').textContent = document.getElementById('profile-custom-status').value || 'Online';
        }
    });
}

// ==================== EMOJI PICKER ====================

function initEmojiPicker() {
    renderEmojis('smileys');
}

function renderEmojis(category) {
    const grid = document.getElementById('emoji-grid');
    if (!grid) return;
    
    const emojis = EMOJIS[category] || EMOJIS.smileys;
    grid.innerHTML = emojis.map(e => `
        <div class="emoji-item" onclick="insertEmoji('${e}')">${e}</div>
    `).join('');
}

function showEmojiCategory(category) {
    AppState.emojiCategory = category;
    document.querySelectorAll('.emoji-category').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    renderEmojis(category);
}

function searchEmoji(query) {
    if (!query) {
        renderEmojis(AppState.emojiCategory);
        return;
    }
    
    const allEmojis = Object.values(EMOJIS).flat();
    const filtered = allEmojis.filter(e => e.includes(query));
    document.getElementById('emoji-grid').innerHTML = filtered.map(e => `
        <div class="emoji-item" onclick="insertEmoji('${e}')">${e}</div>
    `).join('');
}

function insertEmoji(emoji) {
    const input = document.getElementById('message-input');
    input.value += emoji;
    input.focus();
}

function toggleEmojiPicker() {
    document.getElementById('emoji-picker').classList.toggle('active');
    closeGifPicker();
}

function closeEmojiPicker() {
    document.getElementById('emoji-picker').classList.remove('active');
}

// ==================== GIF PICKER ====================

function toggleGifPicker() {
    document.getElementById('gif-picker').classList.toggle('active');
    closeEmojiPicker();
    
    if (document.getElementById('gif-picker').classList.contains('active')) {
        searchGIFs('trending');
    }
}

function closeGifPicker() {
    document.getElementById('gif-picker').classList.remove('active');
}

function searchGIFs(query) {
    const grid = document.getElementById('gif-grid');
    if (!grid) return;
    
    // Use GIPHY API - replace with your API key
    const apiKey = 'YOUR_GIPHY_API_KEY';
    const endpoint = query === 'trending' 
        ? `https://api.giphy.com/v1/gifs/trending?api_key=${apiKey}&limit=20`
        : `https://api.giphy.com/v1/gifs/search?api_key=${apiKey}&q=${encodeURIComponent(query)}&limit=20`;
    
    if (apiKey === 'YOUR_GIPHY_API_KEY') {
        grid.innerHTML = '<div class="empty-state"><p>Please add your GIPHY API key</p></div>';
        return;
    }
    
    fetch(endpoint)
        .then(r => r.json())
        .then(data => {
            if (data.data) {
                grid.innerHTML = data.data.map(gif => `
                    <div class="gif-item" onclick="sendGIF('${gif.images.fixed_height.url}')">
                        <img src="${gif.images.fixed_height_still.url}" alt="" loading="lazy">
                    </div>
                `).join('');
            }
        })
        .catch(() => {
            grid.innerHTML = '<div class="empty-state"><p>Failed to load GIFs</p></div>';
        });
}

// ==================== CALL FUNCTIONS ====================

function startVoiceCall() {
    if (AppState.currentDM === 0) {
        showToast('Calls are only available in DMs', 'error');
        return;
    }
    
    const friend = AppState.friends.find(f => f.user_id == AppState.currentDM);
    if (!friend) return;
    
    document.getElementById('call-title').textContent = `Calling ${friend.real_name}...`;
    document.getElementById('call-overlay').classList.add('active');
    
    // Initialize PeerJS
    initPeerConnection(friend.user_id, false);
}

function startVideoCall() {
    showToast('Video calls coming soon!', 'info');
}

function initPeerConnection(peerId, isReceiver) {
    // Initialize PeerJS
    AppState.peer = new Peer(AppState.currentUser.user_id.toString(), {
        host: 'peerjs.com',
        secure: true,
        port: 443
    });
    
    AppState.peer.on('open', function(id) {
        console.log('Peer ID:', id);
        
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(function(stream) {
                AppState.localStream = stream;
                
                if (!isReceiver) {
                    const call = AppState.peer.call(peerId.toString(), stream);
                    call.on('stream', function(remoteStream) {
                        AppState.remoteStream = remoteStream;
                        onCallConnected();
                    });
                }
            })
            .catch(function(err) {
                showToast('Could not access microphone', 'error');
                endCall();
            });
    });
    
    AppState.peer.on('call', function(call) {
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(function(stream) {
                AppState.localStream = stream;
                call.answer(stream);
                call.on('stream', function(remoteStream) {
                    AppState.remoteStream = remoteStream;
                    onCallConnected();
                });
            });
    });
}

function onCallConnected() {
    document.getElementById('call-title').textContent = 'Voice Call';
    AppState.callStartTime = Date.now();
    AppState.callTimer = setInterval(updateCallTimer, 1000);
    
    const friend = AppState.friends.find(f => f.user_id == AppState.currentDM);
    document.getElementById('call-participants').innerHTML = `
        <div class="call-participant">
            <div class="call-avatar" style="background: ${stringToColor(friend.username)}">
                ${friend.profile_pic ? `<img src="${escapeHtml(friend.profile_pic)}" alt="">` : escapeHtml(friend.real_name.substring(0, 1))}
            </div>
            <div class="call-name">${escapeHtml(friend.real_name)}</div>
            <div class="call-status">On call</div>
        </div>
    `;
}

function updateCallTimer() {
    const elapsed = Math.floor((Date.now() - AppState.callStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    document.getElementById('call-timer').textContent = `${minutes}:${seconds}`;
}

function toggleCallMute() {
    AppState.isMuted = !AppState.isMuted;
    if (AppState.localStream) {
        AppState.localStream.getAudioTracks().forEach(track => {
            track.enabled = !AppState.isMuted;
        });
    }
    document.getElementById('call-mute-btn').classList.toggle('active', AppState.isMuted);
    document.getElementById('call-mute-btn').innerHTML = AppState.isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>';
}

function toggleCallVideo() {
    showToast('Video not supported in this call', 'info');
}

function endCall() {
    if (AppState.localStream) {
        AppState.localStream.getTracks().forEach(track => track.stop());
    }
    if (AppState.peer) {
        AppState.peer.destroy();
    }
    if (AppState.callTimer) {
        clearInterval(AppState.callTimer);
    }
    
    AppState.localStream = null;
    AppState.remoteStream = null;
    AppState.peer = null;
    AppState.callTimer = null;
    AppState.isMuted = false;
    
    document.getElementById('call-overlay').classList.remove('active');
    document.getElementById('call-mute-btn').classList.remove('active');
    document.getElementById('call-mute-btn').innerHTML = '<i class="fas fa-microphone"></i>';
    document.getElementById('call-timer').textContent = '00:00';
}

// ==================== POLLING ====================

function startPolling() {
    // Poll for new messages every 3 seconds
    AppState.pollingInterval = setInterval(function() {
        if (AppState.currentChannel > 0 || AppState.currentDM > 0) {
            checkNewMessages();
        }
        checkTyping();
        loadFriends();
    }, 3000);
}

function checkNewMessages() {
    const params = { limit: 10 };
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        return;
    }
    
    apiRequest('get_messages', params, function(data) {
        if (data.messages) {
            const newMessages = data.messages.filter(m => !AppState.messages.find(existing => existing.message_id == m.message_id));
            
            if (newMessages.length > 0) {
                AppState.messages = [...AppState.messages, ...newMessages];
                renderMessages(true);
                
                // Mark as read
                newMessages.forEach(msg => {
                    if (msg.sender_id != AppState.currentUser.user_id) {
                        markMessageRead(msg.message_id);
                    }
                });
            }
        }
    });
}

function checkTyping() {
    const params = {};
    if (AppState.currentChannel > 0) {
        params.channel_id = AppState.currentChannel;
    } else if (AppState.currentDM > 0) {
        params.recipient_id = AppState.currentDM;
    } else {
        document.getElementById('typing-indicator').textContent = '';
        return;
    }
    
    apiRequest('get_typing', params, function(data) {
        if (data.typing && data.typing.length > 0) {
            const names = data.typing.map(t => t.real_name);
            const text = names.length === 1 
                ? `${names[0]} is typing...`
                : names.length === 2
                    ? `${names[0]} and ${names[1]} are typing...`
                    : `${names.length} people are typing...`;
            document.getElementById('typing-indicator').textContent = text;
        } else {
            document.getElementById('typing-indicator').textContent = '';
        }
    });
}

function refreshAll() {
    loadServers();
    loadFriends();
    if (AppState.currentChannel > 0) {
        loadChannels(AppState.currentServer);
    }
}

// ==================== UTILITY FUNCTIONS ====================

function apiRequest(action, params = {}, callback) {
    const formData = new FormData();
    formData.append('action', action);
    
    Object.keys(params).forEach(key => {
        formData.append(key, params[key]);
    });
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (callback) callback(data);
    })
    .catch(err => {
        console.error('API Error:', err);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function stringToColor(str) {
    if (!str) return '#5865F2';
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colors = ['#5865F2', '#EB459E', '#23A559', '#F0B232', '#F23F43', '#593695', '#00A8FC', '#FF73FA'];
    return colors[Math.abs(hash) % colors.length];
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined });
    }
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} toast-icon"></i>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</div>
            <div class="toast-message">${escapeHtml(message)}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
}

function initTooltips() {
    const tooltip = document.getElementById('guild-tooltip');
    if (!tooltip) return;
    
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const text = this.dataset.tooltip;
            tooltip.textContent = text;
            tooltip.classList.add('visible');
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.right + 10) + 'px';
            tooltip.style.top = (rect.top + rect.height / 2 - tooltip.offsetHeight / 2) + 'px';
        });
        
        el.addEventListener('mouseleave', function() {
            tooltip.classList.remove('visible');
        });
    });
}

function initContextMenu() {
    document.addEventListener('click', function() {
        hideContextMenu();
    });
}

function showContextMenu(e, messageId, senderId) {
    e.preventDefault();
    AppState.contextMessageId = messageId;
    
    const menu = document.getElementById('context-menu');
    const isOwn = senderId == AppState.currentUser.user_id;
    
    document.getElementById('context-delete-item').classList.toggle('hidden', !isOwn);
    document.getElementById('context-owner-separator').classList.toggle('hidden', !isOwn);
    
    menu.style.left = e.pageX + 'px';
    menu.style.top = e.pageY + 'px';
    menu.classList.add('active');
}

function hideContextMenu() {
    document.getElementById('context-menu').classList.remove('active');
}

function replyToMessage() {
    if (AppState.contextMessageId) {
        setReplyTo(AppState.contextMessageId);
    }
    hideContextMenu();
}

function copyMessage() {
    const message = AppState.messages.find(m => m.message_id == AppState.contextMessageId);
    if (message) {
        navigator.clipboard.writeText(message.content).then(() => {
            showToast('Message copied', 'success');
        });
    }
    hideContextMenu();
}

function toggleMute() {
    AppState.isMuted = !AppState.isMuted;
    const btn = document.getElementById('mute-btn');
    btn.innerHTML = AppState.isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>';
    btn.style.color = AppState.isMuted ? 'var(--button-danger)' : '';
}

function showSettingsModal() {
    document.getElementById('settings-modal').classList.add('active');
}

function showSearchModal() {
    document.getElementById('search-modal').classList.add('active');
    document.getElementById('search-query').focus();
}

function showChannelInfo() {
    if (AppState.currentServer > 0) {
        showServerSettings();
    }
}

function toggleMobileSidebar() {
    document.getElementById('channels-sidebar').classList.toggle('active');
}

function showQuickSwitcher() {
    // Implementation for quick switcher
}

function showKeyboardShortcuts() {
    showToast('Keyboard shortcuts: Esc to close, Ctrl+K for quick switch', 'info');
}

function searchChannels() {
    const query = document.getElementById('channel-search').value.toLowerCase();
    document.querySelectorAll('.dm-item, .channel-item').forEach(el => {
        const text = el.textContent.toLowerCase();
        el.style.display = text.includes(query) ? '' : 'none';
    });
}

function logout() {
    if (!confirm('Are you sure you want to logout?')) return;
    
    apiRequest('logout', {}, function() {
        window.location.reload();
    });
}

<?php endif; ?>
</script>

</body>
</html>
