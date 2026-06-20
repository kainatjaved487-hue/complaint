<?php
require_once '../../includes/header.php';

$msg = '';
$msgType = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_dept'])) {
        $name = trim($_POST['dept_name']);
        $head = $_POST['dept_head_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (dept_name, dept_head_id) VALUES (?, ?)");
            $stmt->execute([$name, $head]);
            $msg = "Department added successfully!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    if (isset($_POST['update_dept'])) {
        $id = $_POST['dept_id'];
        $name = trim($_POST['dept_name']);
        $head = $_POST['dept_head_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("UPDATE departments SET dept_name = ?, dept_head_id = ? WHERE id = ?");
            $stmt->execute([$name, $head, $id]);
            $msg = "Department updated successfully!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    if (isset($_POST['delete_dept'])) {
        $id = $_POST['dept_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Department deleted!";
            $msgType = "warning";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// Fetch Departments with Head names
$depts = $pdo->query("SELECT d.*, u.name as head_name 
                      FROM departments d 
                      LEFT JOIN users u ON d.dept_head_id = u.id")->fetchAll();

// Fetch Potential Heads (Users with Admin or Staff roles)
$heads = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('super_admin', 'staff', 'officer')")->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Add New Department</h3>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="dept_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Head (Optional)</label>
                        <select name="dept_head_id" class="form-select">
                            <option value="">-- No Head Assigned --</option>
                            <?php foreach ($heads as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?> (<?= ucfirst($h['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_dept" class="btn btn-primary w-100">Add Department</button>
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
                <h3 class="card-title">Managed Departments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Head</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($depts as $d): ?>
                                <tr>
                                    <td><?= $d['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($d['dept_name']) ?></td>
                                    <td>
                                        <?php if($d['head_name']): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($d['head_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>Unassigned</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $d['id'] ?>" title="Edit Department">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                            <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                                            <button type="submit" name="delete_dept" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals moved outside table for better stability -->
        <?php foreach ($depts as $d): ?>
            <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="" method="post" class="modal-content shadow">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title font-weight-bold">
                                <i class="bi bi-pencil-square me-2"></i>Edit Department
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Department Name</label>
                                <input type="text" name="dept_name" class="form-control" value="<?= htmlspecialchars($d['dept_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Department Head</label>
                                <select name="dept_head_id" class="form-select">
                                    <option value="">-- No Head Assigned --</option>
                                    <?php foreach ($heads as $h): ?>
                                        <option value="<?= $h['id'] ?>" <?= ($d['dept_head_id'] == $h['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($h['name']) ?> (<?= ucfirst($h['role']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text mt-1 italic">Assigning a head enables department-specific notifications.</div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_dept" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
