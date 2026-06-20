<?php
require_once 'core/db.php';
try {
    $pdo->beginTransaction();
    
    // 1. Register Public FAQ Page
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (0, 'FAQs Help', 'faqs.php', 'bi bi-patch-question', 150)");
    $stmt->execute();
    $publicId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM sys_pages WHERE page_url = 'faqs.php'")->fetchColumn();

    // Grant access to ALL roles
    $roles = $pdo->query("SELECT role_key FROM sys_roles")->fetchAll(PDO::FETCH_COLUMN);
    $grant = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
    foreach ($roles as $role) {
        $grant->execute([$role, $publicId]);
    }

    // 2. Register Admin Manage FAQ Page
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (0, 'Manage FAQs', 'dashboards/super_admin/manage_faqs.php', 'bi bi-question-square', 10)");
    $stmt->execute();
    $adminId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_faqs.php'")->fetchColumn();
    $grant->execute(['super_admin', $adminId]);

    // 3. Seed some default FAQs
    $seedFaq = $pdo->prepare("INSERT IGNORE INTO sys_faqs (question, answer, sort_order) VALUES (?, ?, ?)");
    $seedFaq->execute(["How do I submit a complaint?", "Login to your account, go to 'Submit Complaint', fill the form with details and attachments, then click 'Submit'.", 1]);
    $seedFaq->execute(["How long does it take to resolve a complaint?", "Our average resolution time is 2-4 working days. If it stays pending for more than 48 hours, it automatically gets escalated to Super Admins.", 2]);
    $seedFaq->execute(["Can I track the progress of my complaint?", "Yes! Go to 'My Complaints' section to see the current status and detailed resolution timeline for each submission.", 3]);

    $pdo->commit();
    echo "FAQ Pages registered and seeded.";
} catch (Exception $e) { $pdo->rollBack(); echo $e->getMessage(); }
?>
