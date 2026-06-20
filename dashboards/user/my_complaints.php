<?php
require_once '../../includes/header.php';

$userId = $_SESSION['user_id'];

// Fetch User's Complaints
$sql = "SELECT c.*, cat.category_name 
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$complaints = $stmt->fetchAll();

// Helper for status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'Pending': return 'bg-warning text-dark';
        case 'In-Process': return 'bg-info text-white';
        case 'Resolved': return 'bg-success text-white';
        case 'Closed': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}

// Helper for priority badges
function getPriorityBadge($priority) {
    switch ($priority) {
        case 'High': return 'text-danger';
        case 'Medium': return 'text-warning';
        case 'Low': return 'text-success';
        default: return 'text-muted';
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="bi bi-list-stars me-2"></i>My Complaints</h3>
                <a href="submit_complaint.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Complaint</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($complaints) > 0): ?>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['subject']) ?></td>
                                        <td><?= htmlspecialchars($c['category_name']) ?></td>
                                        <td>
                                            <i class="bi bi-circle-fill me-1 <?= getPriorityBadge($c['priority']) ?>" style="font-size: 0.6rem;"></i>
                                            <?= $c['priority'] ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($c['status']) ?>">
                                                <?= $c['status'] ?>
                                            </span>
                                            <?php if($c['is_escalated']): ?>
                                                <span class="badge bg-danger rounded-pill" title="This complaint has been escalated for faster resolution">Escalated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                                        <td>
                                            <a href="view_details.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-folder2-open display-4 mb-3 d-block"></i>
                                            <p>No complaints found. <a href="submit_complaint.php">Submit one here</a>.</p>
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
