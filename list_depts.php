<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, dept_name FROM departments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
