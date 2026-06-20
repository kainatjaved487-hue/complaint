<?php
require_once 'core/db.php';
try {
    $sql = file_get_contents('database_notifications.sql');
    $pdo->exec($sql);
    echo "Success: Notifications table created.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
