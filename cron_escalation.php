<?php
require_once 'core/db.php';
require_once 'core/functions.php';

/**
 * ESCALATION POLICY SCRIPT
 * This script identifies complaints pending for more than 48 hours.
 * It notifies Super Admins and marks the complaint as escalated.
 */

try {
    // 1. Find pending complaints older than 48 hours that aren't already escalated
    // We use DATE_SUB(NOW(), INTERVAL 48 HOUR)
    $sql = "SELECT c.id, c.subject, c.created_at, u.name as submitter_name 
            FROM complaints c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'Pending' 
            AND c.is_escalated = 0 
            AND c.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)";
    
    $stmt = $pdo->query($sql);
    $escalatedItems = $stmt->fetchAll();

    if (count($escalatedItems) > 0) {
        // 2. Fetch all Super Admins to notify them
        $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'super_admin'");
        $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($escalatedItems as $item) {
            $cid = $item['id'];
            $subject = $item['subject'];
            
            // 3. Mark as Escalated in DB
            $upd = $pdo->prepare("UPDATE complaints SET is_escalated = 1, escalated_at = NOW() WHERE id = ?");
            $upd->execute([$cid]);

            // 4. Send Notifications to all Super Admins
            foreach ($adminIds as $adminId) {
                addNotification(
                    $adminId, 
                    "⚠️ ESCALATION: Complaint #$cid", 
                    "Complaint '$subject' from {$item['submitter_name']} has been pending for over 48 hours and requires immediate attention.", 
                    "dashboards/officer/process_complaint.php?id=$cid"
                );
            }
            
            echo "Escalated Complaint #$cid successfully.\n";
        }
    } else {
        echo "No complaints found for escalation currenty.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
