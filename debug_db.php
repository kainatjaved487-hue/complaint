<?php
require_once 'core/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM complaints LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($row);
    echo "</pre>";

    $stmt = $pdo->query("DESCRIBE complaints");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage(); }
?>
