<?php
require_once '../../includes/header.php';

$msg = '';
$msgType = '';

// Fetch Categories for the dropdown
$categories = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $categoryId = $_POST['category_id'];
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    
    $attachmentPath = NULL;

    // Handle File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/complaints/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['attachment']['tmp_name'];
        $fileName = $_FILES['attachment']['name'];
        $fileSize = $_FILES['attachment']['size'];
        $fileType = $_FILES['attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip'];
        if (in_array($fileExtension, $allowedExtensions)) {
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $attachmentPath = 'uploads/complaints/' . $newFileName;
            } else {
                $msg = "Error moving the uploaded file.";
                $msgType = "danger";
            }
        } else {
            $msg = "Upload failed. Allowed types: " . implode(',', $allowedExtensions);
            $msgType = "danger";
        }
    }

    if (empty($msg)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert Complaint
            $sql = "INSERT INTO complaints (user_id, category_id, subject, description, attachment_path, status, priority) 
                    VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $categoryId, $subject, $description, $attachmentPath, $priority]);
            $complaintId = $pdo->lastInsertId();

            // 2. Initial Log Entry
            $logSql = "INSERT INTO complaint_logs (complaint_id, action_by, action_taken, remarks) 
                       VALUES (?, ?, 'Complaint Submitted', 'User submitted the initial complaint.')";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([$complaintId, $userId]);

            // 3. Auto-Assignment (Optional/Basic)
            // Fetch department linked to category
            $catStmt = $pdo->prepare("SELECT dept_id FROM categories WHERE id = ?");
            $catStmt->execute([$categoryId]);
            $deptId = $catStmt->fetchColumn();

            if ($deptId) {
                $assignSql = "INSERT INTO complaint_assignments (complaint_id, department_id) VALUES (?, ?)";
                $assignStmt = $pdo->prepare($assignSql);
                $assignStmt->execute([$complaintId, $deptId]);

                // --- NOTIFICATION LOGIC ---
                // 1. Notify Department Head
                $headStmt = $pdo->prepare("SELECT dept_head_id FROM departments WHERE id = ?");
                $headStmt->execute([$deptId]);
                $headId = $headStmt->fetchColumn();
                if ($headId) {
                    addNotification($headId, "New Complaint Filed", "A new complaint #$complaintId has been filed in your department.", "dashboards/officer/process_complaint.php?id=$complaintId");
                }

                // 2. Notify Department Officers
                $offStmt = $pdo->prepare("SELECT id FROM users WHERE department_id = ? AND role IN ('officer', 'staff')");
                $offStmt->execute([$deptId]);
                while($offId = $offStmt->fetchColumn()) {
                    addNotification($offId, "Complaint Assigned", "A new complaint #$complaintId requiring action has been assigned to your department.", "dashboards/officer/process_complaint.php?id=$complaintId");
                }
            }

            $pdo->commit();
            $msg = "Complaint submitted successfully! ID: #" . $complaintId;
            $msgType = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "Database Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pencil-square me-2"></i>Submit New Complaint</h3>
            </div>
            <div class="card-body">
                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
                        <?= $msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-bold">Complaint Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="" disabled selected>Select a category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose the department or type of issue you're facing.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="subject" class="form-label fw-bold">Subject</label>
                                <input type="text" name="subject" id="subject" class="form-control" placeholder="Brief summary of the issue" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="priority" class="form-label fw-bold">Priority</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Detailed Description</label>
                        <textarea name="description" id="description" class="form-control" rows="5" placeholder="Please provide as much detail as possible..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="attachment" class="form-label fw-bold">Attachment (Optional)</label>
                        <input type="file" name="attachment" id="attachment" class="form-control">
                        <div class="form-text">Allowed: JPG, PNG, PDF, DOCX (Max 5MB).</div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-secondary me-md-2">Reset Form</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-2"></i>Submit Complaint</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
