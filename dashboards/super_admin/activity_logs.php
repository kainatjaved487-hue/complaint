<?php
require_once '../../includes/header.php';

// Fetch Logs with User Info
$sql = "SELECT l.*, u.name as user_name, u.role as user_role 
        FROM sys_activity_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT 500";
$logs = $pdo->query($sql)->fetchAll();

// Statistics
$totalLogs = count($logs);
$uniqueUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM sys_activity_logs")->fetchColumn();
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info elevation-1"><i class="bi bi-list-columns-reverse"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Recorded</span>
                <span class="info-box-number"><?= $totalLogs ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success elevation-1"><i class="bi bi-people"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Users</span>
                <span class="info-box-number"><?= $uniqueUsers ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-activity me-2"></i>System Activity Timeline</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Logs
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="activityTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3 border-0">Timestamp</th>
                                <th class="border-0">User</th>
                                <th class="border-0">Action</th>
                                <th class="border-0">Details</th>
                                <th class="border-0 text-center">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold small"><?= date('d M, Y', strtotime($log['created_at'])) ?></div>
                                            <div class="text-muted small"><?= date('h:i:A', strtotime($log['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <?php if($log['user_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle p-2 me-2 text-center" style="width:32px; height:32px; line-height:16px;">
                                                        <?= strtoupper(substr($log['user_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold small"><?= htmlspecialchars($log['user_name']) ?></div>
                                                        <span class="badge bg-light text-dark extra-small border"><?= ucfirst($log['user_role']) ?></span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted italic">System / Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= strpos(strtolower($log['action']), 'fail') !== false ? 'danger' : 'primary' ?>-subtle text-<?= strpos(strtolower($log['action']), 'fail') !== false ? 'danger' : 'primary' ?> px-3">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-secondary" style="max-width: 300px;">
                                            <?= htmlspecialchars($log['details']) ?>
                                        </td>
                                        <td class="text-center">
                                            <code class="text-muted extra-small"><?= $log['ip_address'] ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted fst-italic">
                                        No activity recorded yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-muted small py-3">
                <i class="bi bi-info-circle me-1"></i> Showing the last 500 activities recorded in the system.
            </div>
        </div>
    </div>
</div>

<style>
.extra-small { font-size: 0.75rem; }
.avatar-sm { font-size: 0.8rem; font-weight: bold; }
</style>

<script>
$(function () {
    if ($.fn.DataTable) {
        $('#activityTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "responsive": true
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
