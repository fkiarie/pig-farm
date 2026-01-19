<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

// Fetch existing record with Farrowing & Sow details for context and validation
$stmt = $conn->prepare("
    SELECT w.*, s.tag_no, s.breed, f.farrowing_date, f.piglets_alive 
    FROM weanings w 
    JOIN sows s ON w.sow_id = s.id 
    JOIN farrowings f ON w.farrowing_id = f.id
    WHERE w.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: list.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weaning_date   = $_POST['weaning_date'];
    $piglets_weaned = (int)$_POST['piglets_weaned'];
    $notes          = trim($_POST['notes']);

    // Validation: Cannot wean more than were born alive
    if ($piglets_weaned > $data['piglets_alive']) {
        $error = "Validation Error: You cannot wean $piglets_weaned piglets because only {$data['piglets_alive']} were born alive in this litter.";
    } else {
        $update = $conn->prepare("
            UPDATE weanings
            SET weaning_date = ?, piglets_weaned = ?, notes = ?
            WHERE id = ?
        ");
        $update->bind_param("sisi", $weaning_date, $piglets_weaned, $notes, $id);
        
        if ($update->execute()) {
            header("Location: list.php?msg=updated");
            exit;
        } else {
            $error = "Failed to update record. Please check your connection.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1); }
    .context-box { background: #f0f7ff; border-radius: 12px; padding: 20px; border-left: 5px solid #0d6efd; margin-bottom: 30px; }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control { border-left: none; }
    .form-control:focus { border-color: #dee2e6; box-shadow: none; border-left: none; }
    .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); border-radius: 0.375rem; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Weaning Record</h1>
                    <p class="text-muted small mb-0">Adjusting weaning data for existing batch.</p>
                </div>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="context-box d-flex align-items-start">
                        <div class="me-3 fs-2 text-primary">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Record Context</h6>
                            <p class="small text-muted mb-0">
                                This weaning record is for <strong>Sow <?= htmlspecialchars($data['tag_no']) ?></strong>.<br>
                                Farrowing occurred on <strong><?= date('d M Y', strtotime($data['farrowing_date'])) ?></strong>.<br>
                                Piglets born alive: <span class="badge bg-primary"><?= $data['piglets_alive'] ?></span>
                            </p>
                        </div>
                    </div>

                    <form method="POST" id="editWeaningForm" class="needs-validation" novalidate>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Weaning Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="weaning_date" class="form-control" 
                                           value="<?= $data['weaning_date'] ?>" 
                                           max="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Piglets Weaned</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="number" name="piglets_weaned" class="form-control" 
                                           value="<?= $data['piglets_weaned'] ?>" 
                                           min="0" max="<?= $data['piglets_alive'] ?>" required>
                                </div>
                                <div class="form-text small">Cannot exceed <?= $data['piglets_alive'] ?> (Born Alive).</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Notes & Observations</label>
                            <textarea name="notes" class="form-control" rows="4" 
                                      placeholder="Note any health issues or average weaning weights..."><?= htmlspecialchars($data['notes']) ?></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                                <i class="bi bi-check2-circle me-2"></i>Update Weaning Data
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted extra-small">
                    <i class="bi bi-shield-check"></i> Integrity check enabled: Weaned count is cross-referenced with farrowing birth records.
                </p>
            </div>

        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>