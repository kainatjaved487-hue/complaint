<?php
require_once '../../includes/header.php';

$msg = '';
$msgType = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $name = trim($_POST['category_name']);
        $desc = trim($_POST['description']);
        $dept = $_POST['dept_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description, dept_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, $dept]);
            $msg = "Category added successfully!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    if (isset($_POST['update_cat'])) {
        $id = $_POST['cat_id'];
        $name = trim($_POST['category_name']);
        $desc = trim($_POST['description']);
        $dept = $_POST['dept_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, dept_id = ? WHERE id = ?");
            $stmt->execute([$name, $desc, $dept, $id]);
            $msg = "Category updated successfully!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    if (isset($_POST['delete_cat'])) {
        $id = $_POST['cat_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Category deleted!";
            $msgType = "warning";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// Fetch Categories with Department names
$categories = $pdo->query("SELECT c.*, d.dept_name 
                           FROM categories c 
                           LEFT JOIN departments d ON c.dept_id = d.id")->fetchAll();

// Fetch Departments for mapping
$departments = $pdo->query("SELECT id, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Add New Category</h3>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g., Internet Issue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Routing Department</label>
                        <select name="dept_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['dept_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Complaints in this category will be routed to this department.</div>
                    </div>
                    <button type="submit" name="add_cat" class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Complaint Categories</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Linked Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?= $cat['id'] ?></td>
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($cat['category_name']) ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($cat['description']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1"></i><?= $cat['dept_name'] ?: 'Unassigned' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editCatModal<?= $cat['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                        <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" name="delete_cat" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Category Modals (Moved outside table for stability) -->
        <?php foreach ($categories as $cat): ?>
            <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="" method="post" class="modal-content shadow">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Category Name</label>
                                <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($cat['category_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($cat['description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Routing Department</label>
                                <select name="dept_id" class="form-select" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= ($cat['dept_id'] == $d['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['dept_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_cat" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
