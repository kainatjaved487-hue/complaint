<?php
/**
 * Global Helper Functions for Complaint Management System
 */

/**
 * Add a notification for a user
 */
function addNotification($userId, $title, $message, $link = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO sys_notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $link]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sys_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/**
 * Get recent notifications for a user
 */
function getRecentNotifications($userId, $limit = 5) {
    global $pdo;
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT * FROM sys_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get CSS class for role badges
 */
function getRoleBadgeColor($roleName) {
    $roleName = strtolower($roleName);
    if (strpos($roleName, 'admin') !== false) return 'bg-danger';
    if (strpos($roleName, 'officer') !== false) return 'bg-primary';
    if (strpos($roleName, 'technician') !== false) return 'bg-warning text-dark';
    if (strpos($roleName, 'accounts') !== false) return 'bg-success';
    if (strpos($roleName, 'hostel') !== false) return 'bg-purple';
    if (strpos($roleName, 'clerk') !== false) return 'bg-dark';
    if (strpos($roleName, 'staff') !== false) return 'bg-info';
    return 'bg-secondary';
}

/**
 * Log a system-wide activity
 */
function logActivity($userId, $action, $details = null) {
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $details, $ip]);
    } catch (PDOException $e) {
        return false;
    }
}
?>
