<?php
// 0. Robust ID Fetching (Before anything else)
if (isset($_GET['id'])) {
    $complaintId = (int)$_GET['id'];
} else {
    // Fallback: Parse from raw URI if $_GET is empty
    parse_str($_SERVER['QUERY_STRING'], $qs);
    $complaintId = isset($qs['id']) ? (int)$qs['id'] : 0;
}

require_once '../../includes/header.php';
$officerId = $_SESSION['user_id'];

// 1. Fetch Complaint Details (Minimal for testing)
$sql = "SELECT c.id FROM complaints c WHERE c.id = ?";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$complaintId]);
    $complaintExists = $stmt->fetch();
} catch (PDOException $e) {
    die('<div class="alert alert-danger m-5">SQL Error: ' . $e->getMessage() . '</div>');
}

if (!$complaintExists) {
    echo '<div class="alert alert-danger m-5">';
    echo '<h4><i class="bi bi-exclamation-triangle-fill me-2"></i>Complaint not found.</h4>';
    echo '<p>Requested ID: <strong>' . htmlspecialchars($complaintId) . '</strong></p>';
    echo '<hr><p class="mb-0 small">Tip: Make sure you are clicking the "Process" button from the list page.</p>';
    echo '</div>';
    require_once '../../includes/footer.php';
    exit;
}

// Now fetch full details since it exists
$sql = "SELECT c.*, cat.category_name, u.name as submitter_name, u.email as submitter_email, d.dept_name
        FROM complaints c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN complaint_assignments ca ON c.id = ca.complaint_id
        LEFT JOIN departments d ON ca.department_id = d.id
        WHERE c.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

// 2. Handle Action Submission
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $remarks = trim($_POST['remarks']);
    
    try {
        $pdo->beginTransaction();

        // Update Status
        $updateStmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $complaintId]);

        // Insert Log
        $logStmt = $pdo->prepare("INSERT INTO complaint_logs (complaint_id, action_by, action_taken, remarks) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$complaintId, $officerId, "Status Updated to $newStatus", $remarks]);

        $pdo->commit();

        // Notify Submitter
        addNotification($complaint['user_id'], "Status Updated: #" . $complaintId, "The status of your complaint has been updated to '$newStatus'. Remark: " . substr($remarks, 0, 50) . "...", "dashboards/user/view_details.php?id=$complaintId");

        $msg = "Complaint status updated successfully!";
        $msgType = "success";
        
        // Refresh local data
        $complaint['status'] = $newStatus;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msgType = "danger";
    }
}

// 2b. Handle Forwarding / Re-assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_complaint'])) {
    $newAssignee = $_POST['new_assignee_id'];
    $forwardRemarks = trim($_POST['forward_remarks']);
    
    if ($newAssignee) {
        try {
            $pdo->beginTransaction();

            // Get new assignee name for log
            $nameStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $nameStmt->execute([$newAssignee]);
            $assigneeName = $nameStmt->fetchColumn();

            // Update Assignment
            $updateStmt = $pdo->prepare("UPDATE complaint_assignments SET assigned_to = ?, assigned_at = NOW() WHERE complaint_id = ?");
            $updateStmt->execute([$newAssignee, $complaintId]);

            // Insert Log
            $logStmt = $pdo->prepare("INSERT INTO complaint_logs (complaint_id, action_by, action_taken, remarks) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$complaintId, $officerId, "Forwarded to $assigneeName", "Reason: $forwardRemarks"]);

            $pdo->commit();

            // Notify New Assignee
            addNotification($newAssignee, "Complaint Forwarded", "Complaint #$complaintId has been forwarded to you.", "dashboards/officer/process_complaint.php?id=$complaintId");

            $msg = "Complaint forwarded to $assigneeName successfully!";
            $msgType = "success";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "Forwarding Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// Fetch peers for forwarding (same department)
$peerQuery = "SELECT id, name, role FROM users WHERE id != ? AND role IN ('staff', 'technician', 'accounts_manager', 'hostel_manager', 'clerk') AND department_id = (SELECT department_id FROM users WHERE id = ?)";
$peerStmt = $pdo->prepare($peerQuery);
$peerStmt->execute([$officerId, $officerId]);
$peers = $peerStmt->fetchAll();

// 3. Fetch Timeline
$logStmt = $pdo->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role 
                         FROM complaint_logs l 
                         LEFT JOIN users u ON l.action_by = u.id 
                         WHERE l.complaint_id = ? 
                         ORDER BY l.created_at DESC");
$logStmt->execute([$complaintId]);
$logs = $logStmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <!-- Submitter Info -->
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header"><h3 class="card-title">Submitter Profile</h3></div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="<?= BASE_URL ?>assets/img/avatar.png" class="rounded-circle border" width="80" alt="Submitter">
                    <h5 class="mt-2 mb-0"><?= htmlspecialchars($complaint['submitter_name']) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($complaint['submitter_email']) ?></small>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Department:</span>
                    <span class="fw-bold"><?= $complaint['dept_name'] ?? 'General' ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Priority:</span>
                    <span class="badge bg-<?= $complaint['priority'] === 'High' ? 'danger' : ($complaint['priority'] === 'Medium' ? 'warning' : 'success') ?>">
                        <?= $complaint['priority'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Feedback Display (Visible to Officers once rated) -->
        <?php if ($complaint['rating'] !== NULL): ?>
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header"><h3 class="card-title">User Feedback</h3></div>
                <div class="card-body">
                    <div class="text-center">
                        <h4 class="text-warning">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $complaint['rating'] ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </h4>
                        <p class="mt-2 text-secondary">"<?= htmlspecialchars($complaint['feedback_text'] ?: 'No comments provided') ?>"</p>
                        <small class="text-muted d-block mt-3 border-top pt-2">Rated on: <?= date('d M, Y', strtotime($complaint['feedback_date'])) ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header"><h3 class="card-title">Take Action</h3></div>
            <div class="card-body">
                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?> py-2"><?= $msg ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Update Status</label>
                        <select name="status" class="form-select">
                            <option value="Pending" <?= $complaint['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="In-Process" <?= $complaint['status'] == 'In-Process' ? 'selected' : '' ?>>In-Process</option>
                            <option value="Resolved" <?= $complaint['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="Closed" <?= $complaint['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Action Taken</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Describe the work done or reason for status change..." required></textarea>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-check-circle-fill me-2"></i>Submit Update
                    </button>
                </form>
            </div>
        </div>

        <?php if (count($peers) > 0): ?>
        <!-- Forward Form -->
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-forward-fill me-2"></i>Forward Complaint</h3></div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign To</label>
                        <select name="new_assignee_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach($peers as $peer): ?>
                                <option value="<?= $peer['id'] ?>"><?= htmlspecialchars($peer['name']) ?> (<?= ucwords(str_replace('_', ' ', $peer['role'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Forwarding</label>
                        <textarea name="forward_remarks" class="form-control" rows="2" placeholder="Why are you forwarding this?" required></textarea>
                    </div>
                    <button type="submit" name="forward_complaint" class="btn btn-warning w-100">
                        <i class="bi bi-send-fill me-2"></i>Forward
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <!-- Complaint Content -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title">Subject: <?= htmlspecialchars($complaint['subject']) ?></h3>
                <div class="card-tools">
                    <span class="badge bg-light text-dark border">Category: <?= htmlspecialchars($complaint['category_name']) ?></span>
                </div>
            </div>
            <div class="card-body">
                <p class="p-3 bg-light rounded border"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                <?php if ($complaint['attachment_path']): ?>
                    <div class="mt-3">
                        <h6><i class="bi bi-paperclip me-1"></i>Attachment:</h6>
                        <a href="<?= BASE_URL . $complaint['attachment_path'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            View Original File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History / Logs -->
        <h5 class="mb-3 mt-4"><i class="bi bi-clock-history me-2"></i>Action History</h5>
        <div class="timeline">
            <?php foreach ($logs as $log): ?>
                <div>
                    <i class="bi bi-info-circle bg-secondary text-white shadow-sm"></i>
                    <div class="timeline-item">
                        <span class="time"><i class="bi bi-clock me-1"></i><?= date('d M, h:i A', strtotime($log['created_at'])) ?></span>
                        <h3 class="timeline-header">
                            <strong><?= htmlspecialchars($log['actor_name']) ?></strong> 
                            <small>(<?= ucfirst($log['actor_role']) ?>)</small> - 
                            <span class="text-primary"><?= $log['action_taken'] ?></span>
                        </h3>
                        <div class="timeline-body"><?= nl2br(htmlspecialchars($log['remarks'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.timeline::before { left: 31px; width: 4px; background-color: #dee2e6; }
.timeline-item { margin-left: 60px; border: 1px solid rgba(0,0,0,.1); border-radius: 8px; background: #fff; }
.timeline > div > i { left: 18px; width: 30px; height: 30px; line-height: 30px; border-radius: 50%; }
</style>

<?php require_once '../../includes/footer.php'; ?>
