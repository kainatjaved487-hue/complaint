<?php
require_once 'core/db.php';

echo "<h2>DCMS Database Migration - Phase 1</h2>";

try {
    $sql = file_get_contents('database_phase1.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green;'>
            ✅ Database updated successfully! <br>
            Tables created: departments, categories, complaints, complaint_assignments, complaint_logs.
          </div>";
          
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>
            ❌ Error during migration: " . $e->getMessage() . "
          </div>";
}

echo "<br><a href='index.php'>Return to Dashboard</a>";
?>
