<?php
require_once '../../includes/header.php';

$msg = '';
$msgType = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $pdo->commit();
        $msg = "System settings updated successfully!";
        $msgType = "success";
        
        // Refresh settings in current session context if needed
        // (Header already fetches these on every load, so redirect is enough)
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
        $msgType = "danger";
    }
}

// Fetch Latest Settings
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
                <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title fw-bold"><i class="bi bi-gear-wide-connected me-2"></i>System Configuration</h3>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-4">
                        <label class="form-label fw-bold">System / Organization Name</label>
                        <input type="text" name="settings[system_name]" class="form-control" value="<?= htmlspecialchars($settings['system_name'] ?? '') ?>" required>
                        <div class="form-text">This name appears in the browser tab and header.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">System Logo (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-image"></i></span>
                            <input type="text" name="settings[system_logo]" class="form-control" value="<?= htmlspecialchars($settings['system_logo'] ?? '') ?>" required>
                        </div>
                        <div class="form-text">External link or local path to the system logo image.</div>
                        <?php if(!empty($settings['system_logo'])): ?>
                            <div class="mt-2 text-center bg-light p-2 rounded">
                                <img src="<?= $settings['system_logo'] ?>" height="40" alt="Current Logo Preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Footer Copyright Text</label>
                        <input type="text" name="settings[footer_text]" class="form-control" value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>" required>
                        <div class="form-text">Display text for the global footer at the bottom of every page.</div>
                    </div>

                    <div class="alert alert-info bg-light border-0 shadow-none small">
                        <i class="bi bi-info-circle me-1"></i> Changes made here will take effect immediately across all user sessions upon their next page load.
                    </div>

                    <div class="text-end">
                        <button type="submit" name="update_settings" class="btn btn-primary px-5 rounded-pill shadow-sm">
                            <i class="bi bi-save me-2"></i>Save All Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-secondary shadow-sm mt-4">
            <div class="card-header"><h3 class="card-title">Advanced: App Initialization Info</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 small">
                    <tbody>
                        <tr><td class="ps-3 fw-bold">PHP Version</td><td class="text-end pe-3"><?= PHP_VERSION ?></td></tr>
                        <tr><td class="ps-3 fw-bold">Database Server</td><td class="text-end pe-3"><?= $pdo->getAttribute(PDO::ATTR_SERVER_INFO) ?></td></tr>
                        <tr><td class="ps-3 fw-bold">App Root</td><td class="text-end pe-3"><code><?= APP_ROOT ?></code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
