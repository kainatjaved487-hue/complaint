<?php 
require_once 'includes/header.php'; 

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Initialize variables to prevent "Undefined Variable" notices
$userCount = $complaintCount = $pendingCount = $resolvedCount = 0;
$myDeptTotal = $myDeptPending = 0;
$myTotal = $myPending = 0;

// 1. Fetch Shared System Counts (For Super Admin)
if($role === 'super_admin') {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $complaintCount = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
    $resolvedCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
    $escalatedCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE is_escalated = 1 AND status != 'Resolved'")->fetchColumn();
}

// 2. Fetch Officer Stats (Department specific)
if($role === 'officer' || $role === 'staff') {
    $deptStmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
    $deptStmt->execute([$userId]);
    $deptId = $deptStmt->fetchColumn();

    if ($deptId) {
        $assignedCount = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM complaints c 
                                        LEFT JOIN categories cat ON c.category_id = cat.id 
                                        WHERE cat.dept_id = ?");
        $assignedCount->execute([$deptId]);
        $myDeptTotal = $assignedCount->fetchColumn();

        $assignedPending = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM complaints c 
                                          LEFT JOIN categories cat ON c.category_id = cat.id 
                                          WHERE cat.dept_id = ? AND c.status = 'Pending'");
        $assignedPending->execute([$deptId]);
        $myDeptPending = $assignedPending->fetchColumn();

        $assignedEscalated = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM complaints c 
                                          LEFT JOIN categories cat ON c.category_id = cat.id 
                                          WHERE cat.dept_id = ? AND c.status = 'Pending' AND c.is_escalated = 1");
        $assignedEscalated->execute([$deptId]);
        $myDeptEscalated = $assignedEscalated->fetchColumn();
    }
}

// 3. Fetch User Stats (Self) - Apply to 'user', 'student' and others
if($role === 'user' || $role === 'student' || !in_array($role, ['super_admin', 'officer', 'staff'])) {
    $userTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ?");
    $userTotalStmt->execute([$userId]);
    $myTotal = $userTotalStmt->fetchColumn();

    $userPendingStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Pending'");
    $userPendingStmt->execute([$userId]);
    $myPending = $userPendingStmt->fetchColumn();
}

// 4. Sentiment Analysis Logic (System Wide for Admin/Staff, Own for Users)
$sentimentScore = 0;
$avgRating = 0;
$sentimentLabel = "Neutral";
$sentimentColor = "secondary";
$sentimentIcon = "bi-emoji-neutral";

// Fetch data for sentiment
$totalC = ($role === 'super_admin') ? $complaintCount : ($role === 'officer' ? $myDeptTotal : $myTotal);
$pendingC = ($role === 'super_admin') ? $pendingCount : ($role === 'officer' ? $myDeptPending : $myPending);
$resolvedC = ($role === 'super_admin') ? $resolvedCount : 0; // Simplified for basic score

if ($totalC > 0) {
    // Basic Sentiment Score: (Resolved / Total) * 100 - (Pending / Total) * 50
    // Higher resolved = Better sentiment. Higher pending = Frustration.
    $resRate = ($totalC > 0) ? ($resolvedC / $totalC) * 100 : 0;
    $penRate = ($totalC > 0) ? ($pendingC / $totalC) * 100 : 0;
    
    // Get Average Rating if exists
    $ratingSql = ($role === 'super_admin') ? "SELECT AVG(rating) FROM complaints WHERE rating IS NOT NULL" : 
                ($role === 'officer' ? "SELECT AVG(rating) FROM complaints WHERE rating IS NOT NULL AND category_id IN (SELECT id FROM categories WHERE dept_id = ?)" : 
                "SELECT AVG(rating) FROM complaints WHERE user_id = ? AND rating IS NOT NULL");
    
    $rStmt = $pdo->prepare($ratingSql);
    $rStmt->execute(($role === 'super_admin') ? [] : [($role === 'officer' ? $deptId : $userId)]);
    $avgRating = round($rStmt->fetchColumn() ?: 0, 1);

    // Combine Rating and Resolution Status for Sentiment
    if ($avgRating >= 4) {
        $sentimentLabel = "Excellent"; $sentimentColor = "success"; $sentimentIcon = "bi-emoji-heart-eyes-fill";
    } elseif ($avgRating >= 3 || ($penRate < 30 && $totalC > 0)) {
        $sentimentLabel = "Satisfied"; $sentimentColor = "info"; $sentimentIcon = "bi-emoji-smile-fill";
    } elseif ($penRate > 60) {
        $sentimentLabel = "Frustrated"; $sentimentColor = "danger"; $sentimentIcon = "bi-emoji-angry-fill";
    } else {
        $sentimentLabel = "Neutral"; $sentimentColor = "warning"; $sentimentIcon = "bi-emoji-neutral-fill";
    }
}

// 5. Leaderboard Data (Top Performing Departments)
$leaderboard = [];
if (in_array($role, ['super_admin', 'officer', 'staff'])) {
    $leaderboardSql = "
        SELECT d.dept_name, 
               COUNT(CASE WHEN c.status = 'Resolved' THEN 1 END) as resolved_count,
               AVG(c.rating) as avg_rating
        FROM departments d
        LEFT JOIN categories cat ON d.id = cat.dept_id
        LEFT JOIN complaints c ON cat.id = c.category_id
        GROUP BY d.id
        ORDER BY resolved_count DESC, avg_rating DESC
        LIMIT 5";
    $leaderboard = $pdo->query($leaderboardSql)->fetchAll();
}

// 6. SLA Analysis (Average Resolution Time in Hours)
$avgResolutionTime = 0;
$slaRating = "Excellent";
$slaColor = "success";

$slaSql = ($role === 'super_admin') ? 
    "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM complaints WHERE status = 'Resolved'" :
    "SELECT AVG(TIMESTAMPDIFF(HOUR, c.created_at, c.updated_at)) FROM complaints c 
     JOIN categories cat ON c.category_id = cat.id 
     WHERE c.status = 'Resolved' AND cat.dept_id = ?";

$slaStmt = $pdo->prepare($slaSql);
$slaStmt->execute(($role === 'super_admin') ? [] : [$deptId ?? 0]);
$avgResolutionTime = round($slaStmt->fetchColumn() ?: 0, 1);

// SLA Status logic
if ($avgResolutionTime > 72) { $slaRating = "Sluggish"; $slaColor = "danger"; }
elseif ($avgResolutionTime > 24) { $slaRating = "Moderate"; $slaColor = "warning"; }
else { $slaRating = "Excellent"; $slaColor = "success"; }

// 7. Heatmap / Category Distribution (Admin/Staff only)
$categoryStats = [];
if (in_array($role, ['super_admin', 'officer', 'staff'])) {
    $catStatSql = "SELECT cat.category_name, COUNT(c.id) as count 
                   FROM categories cat 
                   LEFT JOIN complaints c ON cat.id = c.category_id 
                   GROUP BY cat.id 
                   ORDER BY count DESC 
                   LIMIT 5";
    $categoryStats = $pdo->query($catStatSql)->fetchAll();
}

// 8. Critical Deadlines (Near Escalation)
$criticalDeadlines = [];
if (in_array($role, ['super_admin', 'officer', 'staff'])) {
    // Show complaints about to escalate (between 40 and 48 hours old)
    $deadSql = "SELECT id, subject, created_at 
                FROM complaints 
                WHERE status = 'Pending' AND is_escalated = 0 ";
    if ($role !== 'super_admin') {
        $deadSql .= " AND category_id IN (SELECT id FROM categories WHERE dept_id = " . (int)($deptId ?? 0) . ")";
    }
    $deadSql .= " AND created_at <= DATE_SUB(NOW(), INTERVAL 40 HOUR) 
                  ORDER BY created_at ASC LIMIT 3";
    $criticalDeadlines = $pdo->query($deadSql)->fetchAll();
}

// 9. Live Activity Ticker Data (Last 10 Actions)
$activities = $pdo->query("
    SELECT l.action_taken, u.name as actor, c.id as cid 
    FROM complaint_logs l 
    JOIN users u ON l.action_by = u.id 
    JOIN complaints c ON l.complaint_id = c.id 
    ORDER BY l.created_at DESC LIMIT 10
")->fetchAll();

?>

<!-- Sentiment Overview Card (Top Center) -->
<div class="row justify-content-center mb-4">
    <div class="col-md-8 col-lg-6">
        <div class="card card-outline card-<?= $sentimentColor ?> shadow-sm border-0 bg-white">
            <div class="card-body p-4 text-center">
                <h5 class="text-muted small text-uppercase fw-bold mb-3 ls-1">System Sentiment Overview</h5>
                <div class="d-flex align-items-center justify-content-center">
                    <div class="sentiment-icon-box bg-<?= $sentimentColor ?>-subtle rounded-circle p-3 me-4 animate__animated animate__pulse animate__infinite animate__slow">
                        <i class="bi <?= $sentimentIcon ?> display-4 text-<?= $sentimentColor ?>"></i>
                    </div>
                    <div class="text-start">
                        <h2 class="fw-bold mb-0 text-<?= $sentimentColor ?>"><?= $sentimentLabel ?></h2>
                        <div class="d-flex align-items-center mt-1">
                            <span class="badge bg-light text-dark border me-2">Avg. Rating: <?= $avgRating ?: 'N/A' ?> / 5.0</span>
                            <div class="text-warning small">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?= ($i <= round($avgRating)) ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Live Activity Ticker (Real-time Feed) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="ticker-wrapper bg-white shadow-sm rounded-pill overflow-hidden border">
            <div class="ticker-header bg-dark text-white d-inline-block px-4 py-2 small fw-bold text-uppercase">
                <i class="bi bi-broadcast me-2 text-danger animate__animated animate__flash animate__infinite animate__fast"></i> Live Updates
            </div>
            <div class="ticker-scroll overflow-hidden d-inline-block position-relative align-middle w-75 ms-3">
                <div class="ticker-items d-inline-flex animate-ticker white-space-nowrap">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach($activities as $act): ?>
                            <span class="ticker-item px-4 border-end small text-secondary">
                                <strong class="text-primary"><?= htmlspecialchars($act['actor']) ?></strong>: 
                                <?= htmlspecialchars($act['action_taken']) ?> on <span class="badge bg-light text-dark">#<?= $act['cid'] ?></span>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="ticker-item px-4 small text-muted italic">Waiting for new activity...</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if($role === 'super_admin'): ?>
    <!-- Admin Widgets -->
    <div class="col-lg col-6">
        <div class="small-box text-bg-primary">
            <div class="inner"><h3><?= $userCount ?></h3><p>Total Users</p></div>
            <div class="icon"><i class="bi bi-people-fill"></i></div>
            <a href="dashboards/super_admin/manage_users.php" class="small-box-footer">View Users <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg col-6">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3><?= $pendingCount ?></h3><p>Pending</p></div>
            <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <a href="dashboards/officer/assigned_complaints.php?status=Pending" class="small-box-footer">All Pending <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg col-6">
        <div class="small-box text-bg-danger">
            <div class="inner"><h3><?= $escalatedCount ?></h3><p>Escalated 🔥</p></div>
            <div class="icon"><i class="bi bi-fire"></i></div>
            <a href="dashboards/officer/assigned_complaints.php?status=Escalated" class="small-box-footer">Urgent Action <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg col-6">
        <div class="small-box text-bg-success">
            <div class="inner"><h3><?= $resolvedCount ?></h3><p>Resolved</p></div>
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <a href="dashboards/officer/assigned_complaints.php?status=Resolved" class="small-box-footer">View Resolved <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg col-6">
        <div class="small-box text-bg-info">
            <div class="inner"><h3><?= $complaintCount ?></h3><p>Sytem Total</p></div>
            <div class="icon"><i class="bi bi-inbox-fill"></i></div>
            <a href="dashboards/super_admin/reports.php" class="small-box-footer">Reports <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>

    <?php elseif($role === 'officer' || $role === 'staff'): ?>
    <!-- Officer/Staff Widgets -->
    <div class="col-lg-4 col-md-6">
        <div class="small-box text-bg-info">
            <div class="inner"><h3><?= $myDeptTotal ?></h3><p>Dept. Complaints</p></div>
            <div class="icon"><i class="bi bi-building"></i></div>
            <a href="dashboards/officer/assigned_complaints.php" class="small-box-footer">View All <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3><?= $myDeptPending ?></h3><p>Pending Action</p></div>
            <div class="icon"><i class="bi bi-clock-history"></i></div>
            <a href="dashboards/officer/assigned_complaints.php" class="small-box-footer">Process Now <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box text-bg-danger">
            <div class="inner"><h3><?= $myDeptEscalated ?></h3><p>Escalated (48h+) 🔥</p></div>
            <div class="icon"><i class="bi bi-fire"></i></div>
            <a href="dashboards/officer/assigned_complaints.php" class="small-box-footer">Resolve Now <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>

    <?php else: ?>
    <!-- User Widgets -->
    <div class="col-lg-6 col-md-6">
        <div class="small-box text-bg-primary">
            <div class="inner"><h3><?= $myTotal ?></h3><p>My Total Complaints</p></div>
            <div class="icon"><i class="bi bi-list-task"></i></div>
            <a href="dashboards/user/my_complaints.php" class="small-box-footer">View History <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3><?= $myPending ?></h3><p>Still Processing</p></div>
            <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            <a href="dashboards/user/my_complaints.php" class="small-box-footer">Track Status <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Metrics Row 2: Leaderboard & SLA Tracker -->
<div class="row mt-4">
    <?php if (in_array($role, ['super_admin', 'officer', 'staff'])): ?>
    <!-- 2. Performance Leaderboard -->
    <div class="col-lg-7">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-trophy-fill text-warning me-2"></i>Dept. Performance Leaderboard</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Department</th>
                                <th class="text-center">Resolved</th>
                                <th class="text-center">Satisfaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaderboard) > 0): ?>
                                <?php foreach($leaderboard as $entry): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= htmlspecialchars($entry['dept_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-success-subtle text-success px-3"><?= $entry['resolved_count'] ?></span></td>
                                    <td class="text-center">
                                        <div class="text-warning small">
                                            <?php $r = round($entry['avg_rating'] ?: 0); ?>
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="bi bi-star<?= ($i <= $r) ? '-fill' : '' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1 text-muted">(<?= round($entry['avg_rating'] ?: 0, 1) ?>)</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">No performance data available yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. SLA Tracker (Resolution Speed) -->
    <div class="<?= (in_array($role, ['super_admin', 'officer', 'staff'])) ? 'col-lg-5' : 'col-lg-12' ?>">
        <div class="card card-outline card-<?= $slaColor ?> shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-lightning-charge-fill text-<?= $slaColor ?> me-2"></i>Resolution Speed (SLA)</h3>
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="text-muted small mb-1">AVERAGE TIME TO RESOLVE</h5>
                <h2 class="display-4 fw-bold text-dark mb-0"><?= $avgResolutionTime ?> <small class="fs-6 text-muted">Hours</small></h2>
                <div class="mt-3">
                    <span class="badge rounded-pill bg-<?= $slaColor ?>-subtle text-<?= $slaColor ?> px-4 py-2 border border-<?= $slaColor ?>-subtle">
                        Efficiency Rating: <strong><?= $slaRating ?></strong>
                    </span>
                </div>
                <p class="text-muted small mt-4 px-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i> Based on the last 100 resolved complaints in the system.
                </p>
            </div>
        </div>
    </div>
</div>
<!-- Metrics Row 3: Heatmap & Escalation Alert (Admin/Staff only) -->
<?php if (in_array($role, ['super_admin', 'officer', 'staff'])): ?>
<div class="row mt-4">
    <!-- 5. Issue Heatmap / Category Distribution -->
    <div class="col-lg-7">
        <div class="card card-outline card-secondary shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-pie-chart-fill text-info me-2"></i>Issue Distribution Chart</h3>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="max-height: 250px; width: 100%;">
                    <canvas id="categoryHeatmap"></canvas>
                </div>
                <?php if (empty($categoryStats)): ?>
                    <p class="text-center text-muted small py-4">No categories recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 6. Upcoming Deadlines (Escalation Warning) -->
    <div class="col-lg-5">
        <div class="card card-outline card-danger shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h3 class="card-title fw-bold text-danger"><i class="bi bi-alarm-fill me-2"></i>Urgent: Near Escalation</h3>
            </div>
            <div class="card-body p-0">
                <?php if (count($criticalDeadlines) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($criticalDeadlines as $dead): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-danger-subtle bg-danger-subtle bg-opacity-10">
                            <div class="text-truncate" style="max-width: 70%;">
                                <div class="fw-bold text-danger mb-0 small">#<?= $dead['id'] ?> - <?= htmlspecialchars($dead['subject']) ?></div>
                                <small class="text-muted"><i class="bi bi-clock-history me-1"></i>Filed: <?= date('d M, h:i A', strtotime($dead['created_at'])) ?></small>
                            </div>
                            <a href="dashboards/officer/process_complaint.php?id=<?= $dead['id'] ?>" class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill">
                                Resolve Now
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="p-3 text-center">
                        <small class="text-muted fst-italic">These items will escalate to Super Admin in less than 8 hours.</small>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check2-all text-success display-4 mb-2"></i>
                        <p class="text-muted mb-0">Great job! All SLA targets are safe.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title fw-bold">Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h3>
            </div>
            <div class="card-body">
                <p>You are logged in as <strong><?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?></strong>.</p>
                <p class="text-muted">Explore your dashboard metrics above or use the sidebar to manage complaints and system settings.</p>
                <a href="profile.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-person-circle me-1"></i> My Profile</a>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

body { font-family: 'Outfit', sans-serif!important; background-color: #f8f9fc; }

/* Dashboard Card Enhancements */
.card { border-radius: 20px!important; border: none!important; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 10px 40px rgba(0,0,0,0.06)!important; }
.card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px rgba(44, 62, 236, 0.1)!important; }

/* Glow Widgets */
.small-box { border-radius: 20px; overflow: hidden; border: none; }
.small-box .inner { padding: 25px; }
.small-box .icon { top: 15px; right: 15px; font-size: 50px; opacity: 0.3; }

/* Sentiment & Ticker (Premium Glass) */
.sentiment-icon-box { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.ticker-wrapper { background: rgba(255, 255, 255, 0.7)!important; backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.4)!important; }
.ticker-header { border-radius: 40px; background: linear-gradient(45deg, #2c3eec, #8e44ad)!important; }

/* Text Customizations */
.ls-1 { letter-spacing: 1px; }
.text-bg-primary { background: linear-gradient(135deg, #4e54c8, #8f94fb)!important; }
.text-bg-warning { background: linear-gradient(135deg, #f2994a, #f2c94c)!important; color: white!important; }
.text-bg-danger { background: linear-gradient(135deg, #eb3349, #f45c43)!important; }
.text-bg-success { background: linear-gradient(135deg, #11998e, #38ef7d)!important; }
.text-bg-info { background: linear-gradient(135deg, #2193b0, #6dd5ed)!important; }

/* Table Styling */
.table thead th { border-top: none; padding: 15px; font-weight: 600; }
.table tbody td { padding: 15px; }

/* Ticker Layout & Animation (Fixed single line) */
.ticker-wrapper { display: flex; align-items: center; border-radius: 50px; }
.ticker-scroll { flex-grow: 1; white-space: nowrap; }
.ticker-items { display: flex; animation: ticker-scroll 45s linear infinite; width: max-content; }
.ticker-items:hover { animation-play-state: paused; }
.ticker-item { border-right: 1px solid rgba(0,0,0,0.05); font-weight: 400; font-size: 0.9rem; }

@keyframes ticker-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
</style>

<?php require_once 'includes/footer.php'; ?>