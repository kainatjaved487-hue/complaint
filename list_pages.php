<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, parent_id, page_name, page_url, sort_order, is_menu FROM sys_pages ORDER BY parent_id, sort_order ASC");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pages);
echo "</pre>";
?>
