<?php
require_once '../../includes/header.php';

// 1. Complaint Status Distribution
$statusDist = $pdo->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status")->fetchAll();

// 2. Complaints by Department
$deptStats = $pdo->query("SELECT d.id, d.dept_name, COUNT(c.id) as count 
                          FROM departments d 
                          LEFT JOIN categories cat ON d.id = cat.dept_id 
                          LEFT JOIN complaints c ON cat.id = c.category_id 
                          GROUP BY d.id")->fetchAll();

// 3. Complaints by Category
$catStats = $pdo->query("SELECT cat.category_name, COUNT(c.id) as count 
                         FROM categories cat 
                         LEFT JOIN complaints c ON cat.id = c.category_id 
                         GROUP BY cat.id 
                         ORDER BY count DESC LIMIT 10")->fetchAll();

// 4. User Satisfaction Metric
$ratingStats = $pdo->query("SELECT AVG(rating) as avg_rating, COUNT(rating) as total_ratings FROM complaints WHERE rating IS NOT NULL")->fetch();
$avgRating = round($ratingStats['avg_rating'] ?? 0, 1);
$totalRatings = $ratingStats['total_ratings'];

// 5. Monthly Trend (Last 6 Months)
$monthlyTrend = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
                             FROM complaints 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                             GROUP BY DATE_FORMAT(created_at, '%Y-%m'), month 
                             ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC")->fetchAll();

// 6. Average Resolution Time (in days)
$resTime = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) / 24 as avg_days 
                        FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$avgResTime = round($resTime ?? 0, 1);
?>

<div class="row mb-3">
    <div class="col-12 text-end">
        <a href="print_report.php" target="_blank" class="btn btn-outline-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generate PDF Report
        </a>
    </div>
</div>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-3">
        <div class="card card-outline card-primary shadow-sm" style="height: 180px;">
            <div class="card-header"><h3 class="card-title">Status Overview</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach($statusDist as $s): ?>
                            <tr>
                                <td><?= $s['status'] ?></td>
                                <td class="text-end fw-bold"><?= $s['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Satisfaction Metric -->
    <div class="col-md-3">
        <div class="card card-outline card-warning shadow-sm" style="height: 180px;">
            <div class="card-header"><h3 class="card-title">User Satisfaction</h3></div>
            <div class="card-body text-center">
                <h2 class="text-warning mb-0"><?= $avgRating ?> <small class="text-muted">/ 5</small></h2>
                <div class="stars text-warning fs-4">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <small class="text-muted"><?= $totalRatings ?> Total Feedbacks</small>
            </div>
        </div>
    </div>

    <!-- Department Distribution -->
    <div class="col-md-6">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header"><h3 class="card-title">Complaints by Department</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Total Complaints</th>
                                <th>Progress Bar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxDept = max(array_column($deptStats, 'count')) ?: 1;
                            foreach($deptStats as $d): 
                                $percent = ($d['count'] / $maxDept) * 100;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['dept_name']) ?></td>
                                    <td><?= $d['count'] ?></td>
                                    <td style="width: 40%">
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-info" style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Monthly Trend Chart -->
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-graph-up me-2"></i>Monthly Submission Trend</h3></div>
            <div class="card-body">
                <canvas id="trendChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Average Resolution Metric -->
    <div class="col-md-4">
        <div class="card card-outline card-success shadow-sm" style="height: 310px;">
            <div class="card-header"><h3 class="card-title">Efficiency Metric</h3></div>
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="display-4 text-success fw-bold"><?= $avgResTime ?></div>
                <div class="text-muted fs-5">Average Days to Resolve</div>
                <hr>
                <div class="text-start">
                    <small><i class="bi bi-info-circle me-1"></i> Calculated based on time difference between submission and resolution.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Status Pie Chart -->
    <div class="col-md-4">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header"><h3 class="card-title">Status Distribution</h3></div>
            <div class="card-body">
                <canvas id="statusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Rankings -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header"><h3 class="card-title">Departmental Performance</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total</th>
                                <th>Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($deptStats as $d): 
                                // Fetch resolution rate for each dept
                                $resCount = $pdo->prepare("SELECT COUNT(*) FROM complaints c JOIN categories cat ON c.category_id = cat.id WHERE cat.dept_id = ? AND c.status = 'Resolved'");
                                $resCount->execute([$d['id'] ?? 0]); // Note: $d['id'] might be needed from query
                                $resolved = $resCount->fetchColumn();
                                $rate = $d['count'] > 0 ? round(($resolved / $d['count']) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['dept_name']) ?></td>
                                    <td><?= $d['count'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                <div class="progress-bar bg-<?= $rate > 70 ? 'success' : ($rate > 40 ? 'warning' : 'danger') ?>" style="width: <?= $rate ?>%"></div>
                                            </div>
                                            <span class="small fw-bold"><?= $rate ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Trend Data
    const trendLabels = <?= json_encode(array_column($monthlyTrend, 'month')) ?>;
    const trendData = <?= json_encode(array_column($monthlyTrend, 'count')) ?>;
    
    if (document.getElementById('trendChart') && typeof Chart !== 'undefined') {
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Complaints',
                    data: trendData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#007bff',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // 2. Status Distribution Data
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($statusDist, 'status')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($statusDist, 'count')) ?>,
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#6c757d']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
