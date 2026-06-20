<?php
require_once '../../includes/header.php';

$userId = $_SESSION['user_id'];
$complaintId = $_GET['id'] ?? 0;

// Fetch Complaint Details (Ensure security: user must own the complaint)
$sql = "SELECT c.*, cat.category_name, d.dept_name 
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN complaint_assignments ca ON c.id = ca.complaint_id
        LEFT JOIN departments d ON ca.department_id = d.id
        WHERE c.id = ? AND c.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$complaintId, $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die('<div class="alert alert-danger m-5">Complaint not found or access denied.</div>');
}

// Fetch Logs/Timeline
$logStmt = $pdo->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role 
                         FROM complaint_logs l 
                         JOIN users u ON l.action_by = u.id 
                         WHERE l.complaint_id = ? 
                         ORDER BY l.created_at ASC");
$logStmt->execute([$complaintId]);
$logs = $logStmt->fetchAll();

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending': return 'bg-warning';
        case 'In-Process': return 'bg-info';
        case 'Resolved': return 'bg-success';
        case 'Closed': return 'bg-secondary';
        default: return 'bg-dark';
    }
}

// 3. Handle Feedback Submission
$feedbackMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = (int)$_POST['rating'];
    $feedbackText = trim($_POST['feedback_text']);
    
    if ($rating >= 1 && $rating <= 5) {
        try {
            $updStmt = $pdo->prepare("UPDATE complaints SET rating = ?, feedback_text = ?, feedback_date = NOW() WHERE id = ? AND user_id = ?");
            if ($updStmt->execute([$rating, $feedbackText, $complaintId, $userId])) {
                $feedbackMsg = "Thank you for your feedback!";
                // Refresh complaint data
                $complaint['rating'] = $rating;
                $complaint['feedback_text'] = $feedbackText;
                $complaint['feedback_date'] = date('Y-m-d H:i:s');
            }
        } catch (PDOException $e) {
            $feedbackMsg = "Error: " . $e->getMessage();
        }
    }
}
?>

<style>
/* Star Rating CSS */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.star-rating input {
    display: none;
}
.star-rating label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f39c12;
}
</style>

<div class="row">
    <!-- Complaint Info -->
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title">General Info</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Complaint ID
                        <span class="fw-bold">#<?= $complaint['id'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Current Status
                        <span class="badge rounded-pill <?= getStatusBadgeClass($complaint['status']) ?>"><?= $complaint['status'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Priority
                        <span class="text-<?= $complaint['priority'] === 'High' ? 'danger' : ($complaint['priority'] === 'Medium' ? 'warning' : 'success') ?> fw-bold"><?= $complaint['priority'] ?></span>
                    </li>
                    <li class="list-group-item">
                        <small class="text-muted d-block">Category</small>
                        <span><?= htmlspecialchars($complaint['category_name']) ?></span>
                    </li>
                    <li class="list-group-item">
                        <small class="text-muted d-block">Assigned Department</small>
                        <span class="badge bg-light text-dark border"><i class="bi bi-building me-1"></i><?= $complaint['dept_name'] ?? 'Unassigned' ?></span>
                    </li>
                </ul>
                
                <?php if ($complaint['attachment_path']): ?>
                    <div class="mt-4">
                        <a href="<?= BASE_URL . $complaint['attachment_path'] ?>" target="_blank" class="btn btn-outline-secondary w-100">
                           <i class="bi bi-paperclip me-2"></i>View Attachment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback Section -->
        <?php if ($complaint['status'] === 'Resolved'): ?>
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header"><h3 class="card-title">Resolution Feedback</h3></div>
                <div class="card-body">
                    <?php if ($feedbackMsg): ?>
                        <div class="alert alert-success py-2"><?= $feedbackMsg ?></div>
                    <?php endif; ?>

                    <?php if ($complaint['rating'] === NULL): ?>
                        <form action="" method="post">
                            <label class="form-label d-block fw-bold">How was your experience?</label>
                            <div class="star-rating mb-3">
                                <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star4" name="rating" value="4"/><label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star3" name="rating" value="3"/><label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star2" name="rating" value="2"/><label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star1" name="rating" value="1"/><label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Comments (Optional)</label>
                                <textarea name="feedback_text" class="form-control" rows="3" placeholder="Tell us if you are satisfied..."></textarea>
                            </div>
                            <button type="submit" name="submit_feedback" class="btn btn-success w-100">Submit Feedback</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <h4 class="text-warning">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $complaint['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </h4>
                            <p class="mt-2 fst-italic text-secondary">"<?= htmlspecialchars($complaint['feedback_text'] ?: 'No comments provided') ?>"</p>
                            <small class="text-muted d-block mt-3">Rated on: <?= date('d M, Y', strtotime($complaint['feedback_date'])) ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Timeline & Description -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title">Subject: <?= htmlspecialchars($complaint['subject']) ?></h3>
            </div>
            <div class="card-body">
                <p class="text-justify bg-light p-3 rounded border">
                    <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                </p>
                <small class="text-muted float-end">Submitted on: <?= date('F j, Y, g:i a', strtotime($complaint['created_at'])) ?></small>
            </div>
        </div>

        <h4 class="mb-3 mt-5"><i class="bi bi-clock-history me-2"></i>Resolution Timeline</h4>
        <div class="timeline">
            <?php foreach ($logs as $log): ?>
                <div>
                    <i class="bi bi-record-circle bg-primary text-white shadow-sm"></i>
                    <div class="timeline-item">
                        <span class="time"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($log['created_at'])) ?></span>
                        <h3 class="timeline-header">
                            <span class="fw-bold"><?= htmlspecialchars($log['actor_name']) ?></span> 
                            <small class="text-muted">(<?= ucfirst($log['actor_role']) ?>)</small> - 
                            <span class="text-primary fw-medium"><?= $log['action_taken'] ?></span>
                        </h3>
                        <div class="timeline-body">
                            <?= nl2br(htmlspecialchars($log['remarks'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div> <i class="bi bi-check-circle-fill bg-success text-white shadow-sm"></i> </div>
        </div>
    </div>
</div>

<style>
/* Quick Timeline fixes for AdminLTE compatibility if needed */
.timeline { margin: 0 0 45px; padding: 0; position: relative; }
.timeline::before {
    border-radius: 0.25rem;
    background-color: #dee2e6;
    bottom: 0;
    content: "";
    left: 31px;
    margin: 0;
    position: absolute;
    top: 0;
    width: 4px;
}
.timeline > div { margin-bottom: 30px; margin-right: 10px; position: relative; }
.timeline > div > i {
    background-color: #adb5bd;
    border-radius: 50%;
    font-size: 16px;
    height: 30px;
    left: 18px;
    line-height: 30px;
    position: absolute;
    text-align: center;
    top: 0;
    width: 30px;
    z-index: 10;
}
.timeline > div > .timeline-item {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.25rem;
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    margin-left: 60px;
    margin-right: 15px;
    margin-top: 0;
    padding: 0;
    position: relative;
    border: 1px solid rgba(0,0,0,.125);
}
.timeline-header { border-bottom: 1px solid rgba(0,0,0,.125); color: #495057; font-size: 16px; line-height: 1.1; margin: 0; padding: 10px; }
.timeline-body { padding: 10px; }
.timeline-item > .time { color: #999; float: right; font-size: 12px; padding: 10px; }
</style>

<?php require_once '../../includes/footer.php'; ?>
