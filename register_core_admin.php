<?php
require_once 'core/db.php';

echo "<h2>Registering Core Admin Pages</h2>";

try {
    $pdo->beginTransaction();

    $pages = [
        ['parent_id' => 0, 'name' => 'User Directory', 'url' => 'dashboards/super_admin/manage_users.php', 'icon' => 'bi bi-people-fill', 'order' => 10],
        ['parent_id' => 0, 'name' => 'Role Management', 'url' => 'dashboards/super_admin/manage_roles.php', 'icon' => 'bi bi-shield-lock-fill', 'order' => 11],
        ['parent_id' => 0, 'name' => 'Page Management', 'url' => 'dashboards/super_admin/manage_pages.php', 'icon' => 'bi bi-file-earmark-code-fill', 'order' => 12]
    ];

    $insertPage = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (?, ?, ?, ?, ?)");
    $grantAccess = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)");

    foreach ($pages as $p) {
        $check = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
        $check->execute([$p['url']]);
        $pageId = $check->fetchColumn();

        if (!$pageId) {
            $insertPage->execute([$p['parent_id'], $p['name'], $p['url'], $p['icon'], $p['order']]);
            $pageId = $pdo->lastInsertId();
            echo "Added Page: " . $p['name'] . "<br>";
        } else {
            // Update icon and order if already exists
            $upd = $pdo->prepare("UPDATE sys_pages SET icon_class = ?, sort_order = ?, page_name = ? WHERE id = ?");
            $upd->execute([$p['icon'], $p['order'], $p['name'], $pageId]);
        }

        $grantAccess->execute([$pageId]);
    }

    $pdo->commit();
    echo "<div style='color: green;'>✅ Core Admin pages registered/updated!</div>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>
