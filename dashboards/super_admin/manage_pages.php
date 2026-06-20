<?php 
require_once '../../includes/header.php'; 

// Handle Create Page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pName = $_POST['page_name'];
    $pUrl = $_POST['page_url'];
    $pParent = $_POST['parent_id'];
    $pIcon = $_POST['icon_class'];
    $roles = $_POST['roles'] ?? []; // Array of role_keys

    $pdo->beginTransaction();
    try {
        // 1. Insert Page
        $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class) VALUES (?,?,?,?)");
        $stmt->execute([$pParent, $pName, $pUrl, $pIcon]);
        $pageId = $pdo->lastInsertId();

        // 2. Assign Permissions
        $permStmt = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES (?, ?)");
        foreach($roles as $rKey) {
            $permStmt->execute([$rKey, $pageId]);
        }
        $pdo->commit();
    } catch(Exception $e) {
        $pdo->rollBack();
        die($e->getMessage());
    }
}
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">System Pages</h3></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label>Page Name</label>
                <input type="text" name="page_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>URL</label>
                <input type="text" name="page_url" class="form-control" placeholder="dashboards/..." required>
            </div>
            <div class="col-md-6">
                <label>Parent Menu</label>
                <select name="parent_id" class="form-select">
                    <option value="0">Root (No Parent)</option>
                    <?php 
                    $parents = $pdo->query("SELECT * FROM sys_pages WHERE page_url = '#'")->fetchAll();
                    foreach($parents as $p) echo "<option value='{$p['id']}'>{$p['page_name']}</option>";
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Icon (Bootstrap Icons)</label>
                <input type="text" name="icon_class" class="form-control" placeholder="bi bi-circle">
            </div>
            <div class="col-12">
                <label class="form-label d-block">Assign Access Immediately:</label>
                <?php 
                $roles = $pdo->query("SELECT * FROM sys_roles")->fetchAll();
                foreach($roles as $r): 
                ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $r['role_key'] ?>" checked>
                    <label class="form-check-label"><?= $r['role_name'] ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Create Page & Assign Permissions</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>