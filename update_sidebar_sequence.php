<?php
require_once 'core/db.php';

try {
    // 1. Reset all sort orders and parents for a clean slate
    // Fix Top Level Parents and Sort Orders
    $updates = [
        ['id' => 1, 'parent_id' => 0, 'sort_order' => 1],   // Dashboard
        ['id' => 6, 'parent_id' => 0, 'sort_order' => 10],  // Submit Complaint
        ['id' => 7, 'parent_id' => 0, 'sort_order' => 11],  // My Complaints
        ['id' => 11, 'parent_id' => 0, 'sort_order' => 20], // Assigned Complaints
        ['id' => 14, 'parent_id' => 0, 'sort_order' => 30], // Notifications
        ['id' => 15, 'parent_id' => 0, 'sort_order' => 40], // FAQs Help
        ['id' => 2, 'parent_id' => 0, 'sort_order' => 100], // System Management
        ['id' => 13, 'parent_id' => 0, 'sort_order' => 110], // System Reports
        
        // Items under System Management (Parent ID 2)
        ['id' => 3, 'parent_id' => 2, 'sort_order' => 1],   // User Directory
        ['id' => 4, 'parent_id' => 2, 'sort_order' => 2],   // Role Management
        ['id' => 5, 'parent_id' => 2, 'sort_order' => 3],   // Page Management
        ['id' => 9, 'parent_id' => 2, 'sort_order' => 4],   // Departments
        ['id' => 10, 'parent_id' => 2, 'sort_order' => 5],  // Complaint Categories
        ['id' => 16, 'parent_id' => 2, 'sort_order' => 6],  // Manage FAQs
    ];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id = ?, sort_order = ? WHERE id = ?");
    foreach ($updates as $up) {
        $stmt->execute([$up['parent_id'], $up['sort_order'], $up['id']]);
    }
    $pdo->commit();
    echo "Sidebar sequence updated successfully!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
