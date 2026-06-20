<?php
require_once 'core/db.php';
try {
    $sql = file_get_contents('database_faqs.sql');
    $pdo->exec($sql);
    echo "Success: FAQ table created.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
