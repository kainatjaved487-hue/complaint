<?php 
require_once '../../includes/header.php'; 

$msg = '';
$msgType = '';

// Fetch Roles for Dropdown
$roles = $pdo->query("SELECT * FROM sys_roles ORDER BY role_name ASC")->fetchAll();
// Fetch Departments for Dropdown
$depts = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();

// Handle Actions (Add, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Add User
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = trim($_POST['password']);
        $identity_no = trim($_POST['identity_no']);
        $registration_no = trim($_POST['registration_no']);
        $department_id = $_POST['department_id'] ?: NULL;
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, role, password, identity_no, registration_no, department_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $email, $role, $hash, $identity_no, $registration_no, $department_id]);
            $msg = "User added successfully!";
            $msgType = "success";
        } catch(Exception $e) { 
            $msg = "Error: " . $e->getMessage(); 
            $msgType = "danger"; 
        }
    }

    // 2. Update User
    if (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $identity_no = trim($_POST['identity_no']);
        $registration_no = trim($_POST['registration_no']);
        $department_id = $_POST['department_id'] ?: NULL;
        $active = $_POST['is_active'];
        
        // Handle optional password change
        $passPart = "";
        $params = [$name, $email, $role, $identity_no, $registration_no, $department_id, $active];
        if (!empty($_POST['password'])) {
            $passPart = ", password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $params[] = $id;

        $sql = "UPDATE users SET name = ?, email = ?, role = ?, identity_no = ?, registration_no = ?, department_id = ?, is_active = ? $passPart WHERE id = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "User updated successfully!";
            $msgType = "success";
        } catch(Exception $e) { 
            $msg = "Update Error: " . $e->getMessage(); 
            $msgType = "danger"; 
        }
    }

    // 3. Delete User
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        if ($id == $_SESSION['user_id']) {
            $msg = "You cannot delete yourself!";
            $msgType = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $msg = "User deleted successfully!";
                $msgType = "warning";
            } catch(Exception $e) { 
                $msg = "Delete Error: " . $e->getMessage(); 
                $msgType = "danger"; 
            }
        }
    }
}

// Fetch all users with role and department info
$users = $pdo->query("
    SELECT u.*, r.role_name, d.dept_name 
    FROM users u 
    JOIN sys_roles r ON u.role = r.role_key 
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.id DESC
")->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-people-fill me-2"></i>User Directory</h3>
                <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Add New User
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User Info</th>
                                <th>Role</th>
                                <th>Identifiers</th>
                                <th>Dept</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="bi bi-person-circle fs-4 text-secondary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= getRoleBadgeColor($u['role_name']) ?>">
                                        <?= htmlspecialchars($u['role_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-nowrap"><i class="bi bi-card-text me-1"></i> <?= $u['identity_no'] ?: 'N/A' ?></div>
                                        <div class="text-nowrap"><i class="bi bi-hash me-1"></i> <?= $u['registration_no'] ?: 'N/A' ?></div>
                                    </div>
                                </td>
                                <td><?= $u['dept_name'] ?: '<span class="text-muted small">None</span>' ?></td>
                                <td>
                                    <?= $u['is_active'] ? 
                                        '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Active</span>' : 
                                        '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Inactive</span>' 
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>" title="Edit User">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete user? This cannot be undone.');">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit User Modals (Moved outside table for stability) -->
        <?php foreach($users as $u): ?>
            <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="POST" class="modal-content shadow">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold"><i class="bi bi-person-gear me-2"></i>Edit User: <?= htmlspecialchars($u['name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">System Role</label>
                                    <select name="role" class="form-select" required>
                                        <?php foreach($roles as $r): ?>
                                            <option value="<?= $r['role_key'] ?>" <?= $u['role'] == $r['role_key'] ? 'selected' : '' ?>><?= $r['role_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Department Assignment</label>
                                    <select name="department_id" class="form-select">
                                        <option value="">-- No Department --</option>
                                        <?php foreach($depts as $d): ?>
                                            <option value="<?= $d['id'] ?>" <?= $u['department_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Identity No (CNIC)</label>
                                    <input type="text" name="identity_no" class="form-control" value="<?= htmlspecialchars($u['identity_no']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Registration No</label>
                                    <input type="text" name="registration_no" class="form-control" value="<?= htmlspecialchars($u['registration_no']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Change Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current" autocomplete="new-password">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Account Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" <?= $u['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                                        <option value="0" <?= $u['is_active'] == 0 ? 'selected' : '' ?>>Inactive / Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_user" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New System User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">System Role</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled selected>-- Select Role --</option>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department Assignment</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- No Department --</option>
                            <?php foreach($depts as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['dept_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Identity No (CNIC)</label>
                        <input type="text" name="identity_no" class="form-control" placeholder="e.g. 12345-6789012-3">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Registration No</label>
                        <input type="text" name="registration_no" class="form-control" placeholder="e.g. STU-2023-001">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_user" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
