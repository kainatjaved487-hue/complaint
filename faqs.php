<?php
require_once 'includes/header.php';

// Fetch all FAQs
$faqs = $pdo->query("SELECT * FROM sys_faqs ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="text-center mb-5 animate__animated animate__fadeInDown">
            <h1 class="display-4 fw-bold text-primary">Frequently Asked Questions</h1>
            <p class="lead text-muted">Need help? Find quick answers to common queries about our Complaint Management System.</p>
        </div>

        <div class="accordion accordion-flush shadow-sm rounded border" id="faqAccordion">
            <?php if (count($faqs) > 0): ?>
                <?php foreach ($faqs as $index => $f): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $f['id'] ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $f['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $f['id'] ?>">
                                <i class="bi bi-question-circle-fill text-primary me-3 fs-5"></i>
                                <?= htmlspecialchars($f['question']) ?>
                            </button>
                        </h2>
                        <div id="collapse<?= $f['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $f['id'] ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body py-4 px-5 text-secondary lead" style="line-height: 1.8;">
                                <?= nl2br(htmlspecialchars($f['answer'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white">
                    <i class="bi bi-patch-question display-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted fs-4">No FAQs added yet. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card bg-primary text-white mt-5 shadow-sm border-0 animate__animated animate__pulse animate__infinite">
            <div class="card-body text-center py-4">
                <h4 class="mb-2">Still have questions?</h4>
                <p class="mb-3">If you can't find what you're looking for, feel free to submit a new complaint or contact support.</p>
                <a href="<?= BASE_URL ?>dashboards/user/submit_complaint.php" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary">Contact Us Now</a>
            </div>
        </div>
    </div>
</div>

<style>
.accordion-button:not(.collapsed) {
    background-color: rgba(0, 123, 255, 0.05);
    color: #007bff;
    box-shadow: none;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}
.accordion-item { border-bottom: 1px solid rgba(0,0,0,.05); }
</style>

<?php require_once 'includes/footer.php'; ?>
