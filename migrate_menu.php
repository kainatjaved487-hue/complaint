<?php
require_once 'core/db.php';

try {
    // 1. Add is_menu column
    $pdo->exec("ALTER TABLE sys_pages ADD COLUMN is_menu TINYINT(1) DEFAULT 1 AFTER page_url");
    echo "Added is_menu column to sys_pages.<br>";

    // 2. Hide detail-only pages from sidebar
    $detailPages = [
        'dashboards/user/view_details.php',
        'dashboards/officer/process_complaint.php'
    ];

    $hideStmt = $pdo->prepare("UPDATE sys_pages SET is_menu = 0 WHERE page_url = ?");
    foreach($detailPages as $url) {
        $hideStmt->execute([$url]);
        echo "Hid $url from sidebar.<br>";
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column is_menu already exists. Skipping add.<br>";
    } else {
        die("Error: " . $e->getMessage());
    }
}
?>
