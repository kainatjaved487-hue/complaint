<?php
require_once 'core/db.php';

try {
    $pdo->beginTransaction();

    // 1. Find the Dashboard page ID
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = 'index.php'");
    $stmt->execute();
    $dashId = $stmt->fetchColumn();

    if ($dashId) {
        $roles = $pdo->query("SELECT role_key FROM sys_roles")->fetchAll(PDO::FETCH_COLUMN);
        
        $grant = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
        foreach ($roles as $role) {
            $grant->execute([$role, $dashId]);
            echo "Granted dashboard access to: $role<br>";
        }
    }

    $pdo->commit();
    echo "<div style='color: green;'>✅ Access Denied fixed for all roles!</div>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
