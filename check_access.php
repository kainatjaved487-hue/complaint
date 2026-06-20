<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, page_name, page_url FROM sys_pages");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nRole Access:\n";
$stmt = $pdo->query("SELECT role_key, page_id FROM role_access");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
