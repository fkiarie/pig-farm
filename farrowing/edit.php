<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

// Fetch existing record with Sow details for context
$stmt = $conn->prepare("
    SELECT f.*, s.tag_no, s.breed 
    FROM farrowings f 
    JOIN sows s ON f.sow_id = s.id 
    WHERE f.id = ?
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
    $farrowing_date = $_POST['farrowing_date'];
    $total_born     = (int)$_POST['total_born'];
    $piglets_alive  = (int)$_POST['piglets_alive'];
    $stillbirths    = (int)$_POST['stillbirths'];
    $notes          = trim($_POST['notes']);

    // Validation: Total must match the sum of parts
    if ($total_born !== ($piglets_alive + $stillbirths)) {
        $error = "Data Mismatch: Total born ($total_born) must equal Alive ($piglets_alive) + Stillbirths ($stillbirths).";
    } else {
        $update = $conn->prepare("
            UPDATE farrowings
            SET farrowing_date=?, total_born=?, piglets_alive=?, stillbirths=?, notes=?
            WHERE id=?
        ");
        $update->bind_param("siiisi", $farrowing_date, $total_born, $piglets_alive, $stillbirths, $notes, $id);
        
        if ($update->execute()) {
            header("Location: list.php?msg=updated");
            exit;
        } else {
            $error = "Failed to update record. Please try again.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    .sow-header { background: #f8f9fa; border-radius: 10px; padding: 15px; border-left: 5px solid #198754; margin-bottom: 25px; }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control { border-left: none; }
    .form-control:focus { border-color: #dee2e6; box-shadow: none; border-left: none; }
    .input-group:focus-within { box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15); border-radius: 0.375rem; }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="list.php">Farrowing Records</a></li>
                    <li class="breadcrumb-item active">Edit Record</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Farrowing Record</h2>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="sow-header d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.7rem;">Currently Editing For</small>
                            <span class="h5 mb-0">Sow: <strong><?= htmlspecialchars($data['tag_no']) ?></strong></span>
                            <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($data['breed']) ?></span>
                        </div>
                        <i class="bi bi-piggy-bank fs-2 text-success opacity-25"></i>
                    </div>

                    <form method="POST" id="editFarrowForm" class="needs-validation" novalidate>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Piglets Alive</label>
                                <div class="input-group">
                                    <span class="input-group-text text-success"><i class="bi bi-heart-pulse-fill"></i></span>
                                    <input type="number" name="piglets_alive" id="alive" class="form-control form-control-lg" 
                                           value="<?= $data['piglets_alive'] ?>" min="0" required>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Stillbirths</label>
                                <div class="input-group">
                                    <span class="input-group-text text-danger"><i class="bi bi-exclamation-octagon"></i></span>
                                    <input type="number" name="stillbirths" id="dead" class="form-control form-control-lg" 
                                           value="<?= $data['stillbirths'] ?>" min="0" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-primary">Total Born</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-plus-circle"></i></span>
                                    <input type="number" name="total_born" id="total" class="form-control form-control-lg bg-light" 
                                           value="<?= $data['total_born'] ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Farrowing Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input type="date" name="farrowing_date" class="form-control" 
                                       value="<?= $data['farrowing_date'] ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Notes & Observations</label>
                            <textarea name="notes" class="form-control" rows="4" 
                                      placeholder="Any issues during birth..."><?= htmlspecialchars($data['notes']) ?></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                                <i class="bi bi-save me-2"></i>Update Record
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const aliveInput = document.getElementById('alive');
    const deadInput = document.getElementById('dead');
    const totalInput = document.getElementById('total');

    function calculateTotal() {
        const alive = parseInt(aliveInput.value) || 0;
        const dead = parseInt(deadInput.value) || 0;
        totalInput.value = alive + dead;
    }

    aliveInput.addEventListener('input', calculateTotal);
    deadInput.addEventListener('input', calculateTotal);
    
    // Bootstrap validation
    const form = document.getElementById('editFarrowForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>