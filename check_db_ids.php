<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id FROM complaints");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Existing IDs: " . implode(', ', $ids);
?>
