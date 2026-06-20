<?php
require_once 'includes/header.php';

$userId = $_SESSION['user_id'];

// Handle Mark All as Read
if (isset($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE sys_notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "<script>window.location='notifications.php';</script>";
    exit;
}

// Fetch All Notifications
$stmt = $pdo->prepare("SELECT * FROM sys_notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-bell-fill me-2"></i>Your Notifications</h3>
                <div class="card-tools">
                    <a href="?mark_read=1" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-check-all me-1"></i> Mark All as Read
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="list-group-item list-group-item-action py-3 <?= $n['is_read'] ? '' : 'bg-light border-start border-4 border-primary' ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-1 text-primary fw-bold"><?= htmlspecialchars($n['title']) ?></h5>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M, Y h:i A', strtotime($n['created_at'])) ?></small>
                                </div>
                                <p class="mb-1 text-secondary"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                <?php if ($n['link']): ?>
                                    <a href="<?= BASE_URL . $n['link'] ?>" class="btn btn-sm btn-link p-0 text-decoration-none font-weight-bold">
                                        View Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash display-4 text-muted mb-3 d-block"></i>
                            <p class="text-muted">You have no notifications yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
