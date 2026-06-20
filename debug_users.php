<?php
require_once 'core/db.php';
$s=$pdo->query('DESCRIBE users'); 
echo "<pre>";
print_r($s->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
