<?php
require_once '../../includes/header.php';

$msg = '';
$msgType = '';

// Handle Create/Update FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_faq'])) {
        $id = $_POST['faq_id'] ?? null;
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $order = (int)$_POST['sort_order'];

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE sys_faqs SET question = ?, answer = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $order, $id]);
                $msg = "FAQ updated successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO sys_faqs (question, answer, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$question, $answer, $order]);
                $msg = "New FAQ added!";
            }
            $msgType = "success";
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }

    // Handle Delete
    if (isset($_POST['delete_faq'])) {
        $id = $_POST['faq_id'];
        try {
            $pdo->prepare("DELETE FROM sys_faqs WHERE id = ?")->execute([$id]);
            $msg = "FAQ deleted.";
            $msgType = "warning";
        } catch (PDOException $e) {
            $msg = "Delete Error: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

// Fetch all FAQs
$faqs = $pdo->query("SELECT * FROM sys_faqs ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h3 class="card-title">Add / Edit FAQ</h3></div>
            <div class="card-body">
                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?> py-2"><?= $msg ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <input type="hidden" id="faq_id" name="faq_id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Question</label>
                        <input type="text" id="question" name="question" class="form-control" placeholder="e.g. How to track my complaint?" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Answer</label>
                        <textarea id="answer" name="answer" class="form-control" rows="5" placeholder="Provide a detailed explanation..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" value="0">
                    </div>

                    <button type="submit" name="save_faq" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Save FAQ
                    </button>
                    <button type="button" onclick="resetFaqForm()" class="btn btn-default w-100 mt-2">Clear Form</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header"><h3 class="card-title">Existing FAQs</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px">Order</th>
                                <th>Question & Answer</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faqs as $f): ?>
                                <tr>
                                    <td><?= $f['sort_order'] ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($f['question']) ?></div>
                                        <small class="text-muted d-block"><?= substr(htmlspecialchars($f['answer']), 0, 100) ?>...</small>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info text-white" onclick='editFaq(<?= json_encode($f) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this FAQ?');">
                                            <input type="hidden" name="faq_id" value="<?= $f['id'] ?>">
                                            <button type="submit" name="delete_faq" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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
function editFaq(data) {
    document.getElementById('faq_id').value = data.id;
    document.getElementById('question').value = data.question;
    document.getElementById('answer').value = data.answer;
    document.getElementById('sort_order').value = data.sort_order;
    document.querySelector('.card-title').innerText = "Edit FAQ #" + data.id;
}

function resetFaqForm() {
    document.getElementById('faq_id').value = "";
    document.getElementById('question').value = "";
    document.getElementById('answer').value = "";
    document.getElementById('sort_order').value = "0";
    document.querySelector('.card-title').innerText = "Add / Edit FAQ";
}
</script>

<?php require_once '../../includes/footer.php'; ?>
