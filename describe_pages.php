<?php
require_once 'core/db.php';
$stmt = $pdo->query("DESCRIBE sys_pages");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
