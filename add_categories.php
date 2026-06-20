<?php
require_once 'core/db.php';

$newCategories = [
    ['name' => 'Service Complaints', 'dept_id' => 2], // Admin & Finance
    ['name' => 'General Queries', 'dept_id' => 2]    // Admin & Finance
];

try {
    $stmt = $pdo->prepare("INSERT INTO categories (category_name, dept_id) VALUES (?, ?)");
    foreach ($newCategories as $cat) {
        $stmt->execute([$cat['name'], $cat['dept_id']]);
        echo "Added Category: " . $cat['name'] . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
