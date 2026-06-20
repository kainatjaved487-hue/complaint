<?php
require_once 'core/db.php';

try {
    // Add is_escalated column to complaints table
    $sql = "ALTER TABLE complaints 
            ADD COLUMN is_escalated TINYINT(1) DEFAULT 0,
            ADD COLUMN escalated_at TIMESTAMP NULL DEFAULT NULL";
    
    $pdo->exec($sql);
    echo "Success: Complaints table updated with escalation columns.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Note: Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
