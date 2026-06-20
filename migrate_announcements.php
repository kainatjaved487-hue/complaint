<?php
require_once 'core/db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

if (!in_array('announcements', $tables)) {
    $pdo->exec("CREATE TABLE announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1
    )");
    echo "\nAnnouncements table created.";

    // Seed it
    $pdo->exec("INSERT INTO announcements (title, message, type) VALUES 
        ('System Update', 'The complaint system has been upgraded with a new Sentiment and SLA Dashboard!', 'success'),
        ('Maintenance', 'Database cleanup scheduled for Sunday, 2 AM.', 'warning')");
}
?>
