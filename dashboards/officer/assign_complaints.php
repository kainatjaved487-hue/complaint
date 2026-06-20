<?php
require_once '../../includes/header.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Fetch Officer's Department
$deptStmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$deptStmt->execute([$userId]);
$deptId = $deptStmt->fetchColumn();

if (!$deptId && $userRole !== 'super_admin') {
    echo '<div class="alert alert-warning m-5">You are not assigned to any department. Please contact the administrator.</div>';
    require_once '../../includes/footer.php';
    exit;
}

$msg = '';
$msgType = '';

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff'])) {
    $cid = $_POST['complaint_id'];
    $staffId = $_POST['staff_id'];
    
    if ($staffId) {
        try {
            // Check if assignment exists
            $checkStmt = $pdo->prepare("SELECT id FROM complaint_assignments WHERE complaint_id = ?");
            $checkStmt->execute([$cid]);
            $exists = $checkStmt->fetchColumn();
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE complaint_assignments SET assigned_to = ?, assigned_at = NOW() WHERE complaint_id = ?");
                $stmt->execute([$staffId, $cid]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO complaint_assignments (complaint_id, department_id, assigned_to) VALUES (?, ?, ?)");
                // Try to get the department of the complaint based on category if not set
                $catDeptStmt = $pdo->prepare("SELECT cat.dept_id FROM complaints c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
                $catDeptStmt->execute([$cid]);
                $cDeptId = $catDeptStmt->fetchColumn();
                $stmt->execute([$cid, $cDeptId ?: $deptId, $staffId]);
            }

            // Also update complaint status to In-Process if it was Pending
            $pdo->prepare("UPDATE complaints SET status = 'In-Process' WHERE id = ? AND status = 'Pending'")->execute([$cid]);

            // Notify Staff
            addNotification($staffId, "New Assignment", "Complaint #$cid has been assigned to you.", "dashboards/officer/process_complaint.php?id=$cid");

            $msg = "Complaint #$cid successfully assigned to staff!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    } else {
        $msg = "Please select a staff member to assign.";
        $msgType = "warning";
    }
}

// Fetch Staff Members (Support Staff, Technicians, Managers)
$staffQuery = "SELECT id, name, role FROM users WHERE role IN ('staff', 'technician', 'accounts_manager', 'hostel_manager', 'clerk') ";
if ($userRole !== 'super_admin') {
    $staffQuery .= " AND department_id = " . (int)$deptId;
}
$staffMembers = $pdo->query($staffQuery)->fetchAll();

// Fetch Pending & In-Process Complaints for Assignment
$whereClause = " WHERE c.status IN ('Pending', 'In-Process') ";
$params = [];

if ($userRole !== 'super_admin') {
    $whereClause .= " AND (ca.department_id = ? OR cat.dept_id = ?) ";
    $params[] = $deptId;
    $params[] = $deptId;
}

$sql = "SELECT c.id, c.subject, c.status, c.priority, cat.category_name, u.name as submitter_name, ca.assigned_to, s.name as staff_name 
        FROM complaints c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN complaint_assignments ca ON c.id = ca.complaint_id
        LEFT JOIN users s ON ca.assigned_to = s.id
        $whereClause
        GROUP BY c.id
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

?>

<div class="row">
    <div class="col-12">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-person-lines-fill me-2"></i>Assign Complaints to Support Staff
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Subject / Category</th>
                                <th>Status</th>
                                <th>Current Assignee</th>
                                <th>Assign To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($complaints) > 0): ?>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td>#<?= $c['id'] ?></td>
                                        <td>
                                            <span class="fw-bold"><?= htmlspecialchars($c['subject']) ?></span><br>
                                            <small class="text-muted"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($c['category_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $c['status'] == 'Pending' ? 'warning' : 'info' ?>">
                                                <?= $c['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($c['assigned_to']): ?>
                                                <span class="badge bg-success"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($c['staff_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                                                <select name="staff_id" class="form-select form-select-sm me-2" style="width: 200px;" required>
                                                    <option value="">-- Select Staff --</option>
                                                    <?php foreach ($staffMembers as $staff): ?>
                                                        <option value="<?= $staff['id'] ?>" <?= $c['assigned_to'] == $staff['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($staff['name']) ?> (<?= ucwords(str_replace('_', ' ', $staff['role'])) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="assign_staff" class="btn btn-primary btn-sm">Assign</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-check2-all display-4 mb-3 d-block"></i>
                                            <p>No active complaints require assignment.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
