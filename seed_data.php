<?php
require_once 'core/db.php';

echo "<h2>DCMS Seeding - Basic Setup</h2>";

try {
    // 1. Insert Default Departments
    $depts = ['IT Support', 'Admin & Finance', 'Student Affairs', 'Maintenance'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (dept_name) VALUES (?)");
    foreach ($depts as $dept) {
        $stmt->execute([$dept]);
    }

    // 2. Fetch Department IDs
    $deptIds = $pdo->query("SELECT id, dept_name FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);
    $itId = array_search('IT Support', $deptIds);
    $adminId = array_search('Admin & Finance', $deptIds);
    $studentId = array_search('Student Affairs', $deptIds);
    $maintId = array_search('Maintenance', $deptIds);

    // 3. Insert Default Categories
    $categories = [
        ['Hardware Issue', 'Problems with computer, printer, etc.', $itId],
        ['Software License', 'Request for software installation', $itId],
        ['Fee Inquiry', 'Questions regarding tuition or fines', $adminId],
        ['Hostel Complaint', 'Issues in student housing', $studentId],
        ['Electric/Water', 'Maintenance requests for utilities', $maintId]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (category_name, description, dept_id) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    echo "<div style='color: green;'>✅ Seeding successful! Default departments and categories added.</div>";

} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ Seeding Error: " . $e->getMessage() . "</div>";
}
?>
