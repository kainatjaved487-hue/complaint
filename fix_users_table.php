<?php
require_once 'core/db.php';

try {
    // Check if avatar column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER registration_no");
        echo "Column 'avatar' added successfully to 'users' table.<br>";
    } else {
        echo "Column 'avatar' already exists in 'users' table.<br>";
    }

    // Also check for identity_no and registration_no just in case
    $cols = ['identity_no', 'registration_no'];
    foreach ($cols as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col VARCHAR(100) DEFAULT NULL");
            echo "Column '$col' added successfully.<br>";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
