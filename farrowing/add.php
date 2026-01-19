<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serving_id       = (int) $_POST['serving_id'];
    $sow_id           = (int) $_POST['sow_id'];
    $farrowing_date   = $_POST['farrowing_date'];
    $total_born       = (int) $_POST['total_born'];
    $piglets_alive    = (int) $_POST['piglets_alive'];
    $stillbirths      = (int) $_POST['stillbirths'];
    $notes            = trim($_POST['notes']);

    if ($total_born !== ($piglets_alive + $stillbirths)) {
        $error = "Data Mismatch: Total born ($total_born) must equal Alive ($piglets_alive) + Stillbirths ($stillbirths).";
    } else {
        $conn->begin_transaction();
        try {
            // Validate serving
            $check = $conn->prepare("SELECT sow_id FROM servings WHERE id = ? AND (status != 'Completed' OR status IS NULL)");
            $check->bind_param("i", $serving_id);
            $check->execute();
            $serving = $check->get_result()->fetch_assoc();

            if (!$serving || $serving['sow_id'] != $sow_id) {
                throw new Exception("Invalid or already completed serving selected.");
            }

            // Insert farrowing
            $stmt = $conn->prepare("INSERT INTO farrowings (serving_id, sow_id, farrowing_date, total_born, piglets_alive, stillbirths, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisiiis", $serving_id, $sow_id, $farrowing_date, $total_born, $piglets_alive, $stillbirths, $notes);
            $stmt->execute();

            // Mark serving as completed
            $stmt = $conn->prepare("UPDATE servings SET status = 'Completed' WHERE id = ?");
            $stmt->bind_param("i", $serving_id);
            $stmt->execute();

            // Update sow status -> Lactating
            $stmt = $conn->prepare("UPDATE sows SET status = 'Lactating' WHERE id = ?");
            $stmt->bind_param("i", $sow_id);
            $stmt->execute();

            $conn->commit();
            header("Location: list.php?msg=success");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Load available servings
$servings = $conn->query("
    SELECT sv.id, sv.serving_date, s.id AS sow_id, s.tag_no, s.breed
    FROM servings sv
    JOIN sows s ON sv.sow_id = s.id
    LEFT JOIN farrowings f ON f.serving_id = sv.id
    WHERE f.id IS NULL AND (sv.status != 'Completed' OR sv.status IS NULL)
    ORDER BY sv.serving_date DESC
");

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control, .form-select { border-left: none; }
    .calc-box { background-color: #f0f7ff; border-radius: 8px; padding: 15px; border: 1px dashed #0d6efd; }
    .status-badge-preview { font-size: 0.8rem; padding: 4px 12px; border-radius: 50px; background: #e1f6e1; color: #198754; font-weight: 600; }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-egg-fried text-success me-2"></i>Record Farrowing</h1>
                    <p class="text-muted small mb-0">Log new litter details and update sow status.</p>
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
                    <form method="POST" id="farrowForm" class="needs-validation" novalidate>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Select Active Serving</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <select name="serving_id" id="servingSelect" class="form-select form-select-lg" required>
                                    <option value="">-- Choose Sow & Serving Date --</option>
                                    <?php while ($sv = $servings->fetch_assoc()): ?>
                                        <option value="<?= $sv['id'] ?>" data-sow="<?= $sv['sow_id'] ?>">
                                            Sow <?= htmlspecialchars($sv['tag_no']) ?> (Served: <?= date('d M Y', strtotime($sv['serving_date'])) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <input type="hidden" name="sow_id" id="sow_id">
                            <div class="form-text">Only sows currently marked as "Pregnant" appear here.</div>
                        </div>

                        <hr class="my-4 opacity-25">

                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Litter Results</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="small text-muted">Born Alive</label>
                                    <input type="number" name="piglets_alive" id="alive" class="form-control form-control-lg" min="0" value="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Stillbirths / Mummies</label>
                                    <input type="number" name="stillbirths" id="dead" class="form-control form-control-lg" min="0" value="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Total Born</label>
                                    <input type="number" name="total_born" id="total" class="form-control form-control-lg bg-light" readonly required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Farrowing Date</label>
                                <input type="date" name="farrowing_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100 p-2 border rounded bg-light small">
                                    <i class="bi bi-arrow-repeat me-1 text-primary"></i> 
                                    New Sow Status: <span class="status-badge-preview">Lactating</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Observations / Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Difficulty of birth, fosterings, etc."></textarea>
                            </div>
                        </div>

                        <div class="pt-3">
                            <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm">
                                <i class="bi bi-check2-circle me-2"></i>Save Farrowing Record
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted small"><i class="bi bi-info-circle me-1"></i> Saving this record will automatically mark the breeding cycle as "Completed".</p>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const servingSelect = document.getElementById('servingSelect');
    const sowInput = document.getElementById('sow_id');
    const aliveInput = document.getElementById('alive');
    const deadInput = document.getElementById('dead');
    const totalInput = document.getElementById('total');

    // Link Sow ID to Serving Selection
    servingSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        sowInput.value = selected.dataset.sow || '';
    });

    // Auto-calculate Total
    function calculateTotal() {
        const alive = parseInt(aliveInput.value) || 0;
        const dead = parseInt(deadInput.value) || 0;
        totalInput.value = alive + dead;
    }

    aliveInput.addEventListener('input', calculateTotal);
    deadInput.addEventListener('input', calculateTotal);
    
    // Form Validation
    const form = document.getElementById('farrowForm');
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