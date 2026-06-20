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

// Handle Quick Actions (Update/Delete)
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Complaint (Status/Priority)
    if (isset($_POST['update_complaint'])) {
        $cid = $_POST['complaint_id'];
        $status = $_POST['status'];
        $priority = $_POST['priority'];
        $subject = trim($_POST['subject']);
        
        try {
            $stmt = $pdo->prepare("UPDATE complaints SET status = ?, priority = ?, subject = ? WHERE id = ?");
            $stmt->execute([$status, $priority, $subject, $cid]);

            // Notify Submitter
            $subStmt = $pdo->prepare("SELECT user_id FROM complaints WHERE id = ?");
            $subStmt->execute([$cid]);
            $sid = $subStmt->fetchColumn();
            if ($sid) {
                addNotification($sid, "Complaint Status Updated", "Your complaint #$cid status has been changed to '$status' with $priority priority.", "dashboards/user/view_details.php?id=$cid");
            }

            $msg = "Complaint #$cid updated successfully!";
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    // 2. Delete Complaint
    if (isset($_POST['delete_complaint']) && $userRole === 'super_admin') {
        $cid = $_POST['complaint_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM complaint_logs WHERE complaint_id = ?")->execute([$cid]);
            $pdo->prepare("DELETE FROM complaint_assignments WHERE complaint_id = ?")->execute([$cid]);
            $pdo->prepare("DELETE FROM complaints WHERE id = ?")->execute([$cid]);
            $pdo->commit();
            $msg = "Complaint #$cid deleted permanently.";
            $msgType = "warning";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "Delete Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// Logic to fetch complaints with optional status filtering
$filterStatus = $_GET['status'] ?? '';
$whereClause = "";
$params = [];

if ($userRole === 'super_admin') {
    if ($filterStatus === 'Escalated') {
        $whereClause = " WHERE c.is_escalated = 1 ";
    } elseif ($filterStatus) {
        $whereClause = " WHERE c.status = ? ";
        $params[] = $filterStatus;
    }
    
    $sql = "SELECT c.id, c.subject, c.description, c.status, c.priority, c.is_escalated, c.created_at, 
                   cat.category_name, u.name as submitter_name 
            FROM complaints c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.user_id = u.id
            $whereClause
            ORDER BY c.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $whereClause = " WHERE (ca.department_id = ? OR cat.dept_id = ?) ";
    $params[] = $deptId ?? 0;
    $params[] = $deptId ?? 0;

    if ($filterStatus === 'Escalated') {
        $whereClause .= " AND c.is_escalated = 1 ";
    } elseif ($filterStatus) {
        $whereClause .= " AND c.status = ? ";
        $params[] = $filterStatus;
    }

    $sql = "SELECT c.id, c.subject, c.description, c.status, c.priority, c.is_escalated, c.created_at, 
                   cat.category_name, u.name as submitter_name 
            FROM complaints c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN complaint_assignments ca ON c.id = ca.complaint_id
            $whereClause
            GROUP BY c.id
            ORDER BY c.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
$complaints = $stmt->fetchAll();

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending': return 'bg-warning text-dark';
        case 'In-Process': return 'bg-info text-white';
        case 'Resolved': return 'bg-success text-white';
        case 'Closed': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card card-outline card-info shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold">
                    <i class="bi bi-inbox-fill me-2"></i>
                    <?= $filterStatus ? htmlspecialchars($filterStatus) . " " : "All " ?>Complaints
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Submitter</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($complaints) > 0): ?>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td>#<?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['submitter_name']) ?></td>
                                        <td title="<?= htmlspecialchars($c['description']) ?>"><?= htmlspecialchars($c['subject']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $c['priority'] === 'High' ? 'danger' : ($c['priority'] === 'Medium' ? 'warning' : 'success') ?>">
                                                <?= $c['priority'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= getStatusBadgeClass($c['status']) ?>">
                                                <?= $c['status'] ?>
                                            </span>
                                            <?php if($c['is_escalated']): ?>
                                                <span class="badge bg-danger animate__animated animate__flash animate__infinite">
                                                    <i class="bi bi-exclamation-octagon me-1"></i>Escalated
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="process_complaint.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm" title="Process/View Logs">
                                                    <i class="bi bi-chat-dots-fill"></i>
                                                </a>
                                                <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#editModal<?= $c['id'] ?>" title="Quick Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <?php if($userRole === 'super_admin'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this complaint?');">
                                                    <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                                                    <button type="submit" name="delete_complaint" class="btn btn-danger btn-sm" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-check2-all display-4 mb-3 d-block"></i>
                                            <p>No complaints found for your department.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Edit Modals (Moved outside table for stability) -->
        <?php foreach ($complaints as $c): ?>
            <div class="modal fade" id="editModal<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" class="modal-content shadow">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Quick Edit: Complaint #<?= $c['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($c['subject']) ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Priority</label>
                                    <select name="priority" class="form-select shadow-sm">
                                        <option value="Low" <?= $c['priority'] == 'Low' ? 'selected' : '' ?>>Low</option>
                                        <option value="Medium" <?= $c['priority'] == 'Medium' ? 'selected' : '' ?>>Medium</option>
                                        <option value="High" <?= $c['priority'] == 'High' ? 'selected' : '' ?>>High</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" class="form-select shadow-sm">
                                        <option value="Pending" <?= $c['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="In-Process" <?= $c['status'] == 'In-Process' ? 'selected' : '' ?>>In-Process</option>
                                        <option value="Resolved" <?= $c['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option value="Closed" <?= $c['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-secondary border-0" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_complaint" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Complaint
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
