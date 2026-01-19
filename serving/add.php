<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

// Fetch active sows (NOT pregnant)
$sows = $conn->query("SELECT id, tag_no, breed FROM sows WHERE status = 'Active' ORDER BY tag_no");

// Fetch active boars
$boars = $conn->query("SELECT id, name, breed FROM boars WHERE status = 'Active' ORDER BY name");

$sow_count = $sows->num_rows;
$boar_count = $boars->num_rows;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sow_id = $_POST['sow_id'];
    $boar_id = $_POST['boar_id'];
    $serving_date = $_POST['serving_date'];
    $method = $_POST['method'];
    $expected_farrowing = date('Y-m-d', strtotime($serving_date . ' +114 days'));

    $check = $conn->prepare("SELECT status FROM sows WHERE id = ?");
    $check->bind_param("i", $sow_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if (!$result || $result['status'] !== 'Active') {
        $error = "This sow cannot be served (already pregnant or inactive).";
    } else {
        $stmt = $conn->prepare("INSERT INTO servings (sow_id, boar_id, serving_date, expected_farrowing, method) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $sow_id, $boar_id, $serving_date, $expected_farrowing, $method);

        if ($stmt->execute()) {
            $update = $conn->prepare("UPDATE sows SET status = 'Pregnant' WHERE id = ?");
            $update->bind_param("i", $sow_id);
            $update->execute();
            $success = "Serving recorded! Expected farrowing: " . date('d M Y', strtotime($expected_farrowing));
        } else {
            $error = "Failed to record serving.";
        }
    }
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .form-card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    .stat-mini { border-radius: 10px; padding: 12px; background: #f8f9fa; border: 1px solid #eee; }
    .stat-mini h4 { margin: 0; color: #0d6efd; font-weight: 700; }
    .stat-mini small { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; font-weight: 600; }
    .gestation-preview { background: #e7f1ff; border-radius: 10px; padding: 15px; border-left: 5px solid #0d6efd; }
</style>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="list.php">Breeding</a></li>
            <li class="breadcrumb-item active">Record Serving</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            
            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                    <div>
                        <div class="fw-bold">Success!</div>
                        <?= $success ?>
                        <div class="mt-2">
                            <a href="list.php" class="btn btn-sm btn-success px-3">View Records</a>
                            <button onclick="window.location.reload()" class="btn btn-sm btn-outline-success px-3 ms-2">Record Another</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <h2 class="h4 mb-1 fw-bold">Record New Serving</h2>
                            <p class="text-muted small mb-0">Select animals and date to track gestation.</p>
                        </div>
                        <div class="col-auto d-none d-md-flex gap-2">
                            <div class="stat-mini">
                                <small>Available Sows</small>
                                <h4><?= $sow_count ?></h4>
                            </div>
                            <div class="stat-mini">
                                <small>Active Boars</small>
                                <h4><?= $boar_count ?></h4>
                            </div>
                        </div>
                    </div>

                    <?php if ($sow_count === 0 || $boar_count === 0): ?>
                        <div class="text-center py-5">
                            <div class="p-4 bg-light rounded-circle d-inline-block mb-3">
                                <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
                            </div>
                            <h5>Insufficient Animals</h5>
                            <p class="text-muted">You need at least one active sow and one active boar.</p>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <a href="../sows/add.php" class="btn btn-primary btn-sm">Add Sow</a>
                                <a href="../boars/add.php" class="btn btn-outline-primary btn-sm">Add Boar</a>
                            </div>
                        </div>
                    <?php else: ?>

                    <form method="POST" id="servingForm" class="needs-validation" novalidate>
                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Select Sow <span class="text-danger">*</span></label>
                                        <select name="sow_id" class="form-select form-select-lg shadow-sm" required>
                                            <option value="">Choose sow...</option>
                                            <?php while ($s = $sows->fetch_assoc()): ?>
                                                <option value="<?= $s['id'] ?>"><?= $s['tag_no'] ?> (<?= $s['breed'] ?: 'No Breed' ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Select Boar <span class="text-danger">*</span></label>
                                        <select name="boar_id" class="form-select form-select-lg shadow-sm" required>
                                            <option value="">Choose boar...</option>
                                            <?php while ($b = $boars->fetch_assoc()): ?>
                                                <option value="<?= $b['id'] ?>"><?= $b['name'] ?> (<?= $b['breed'] ?: 'No Breed' ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Serving Date</label>
                                        <input type="date" name="serving_date" id="serving_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Method</label>
                                        <select name="method" class="form-select">
                                            <option value="Natural">Natural</option>
                                            <option value="AI">AI (Artificial Insemination)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="gestation-preview h-100 d-flex flex-column justify-content-center">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-calendar-event text-primary fs-1"></i>
                                    </div>
                                    <h6 class="text-center text-primary text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Gestation Calculation</h6>
                                    <div class="text-center">
                                        <div class="display-6 fw-bold text-dark mb-0" id="previewDate">—</div>
                                        <p class="text-muted small">Estimated Farrowing Date</p>
                                    </div>
                                    <div class="mt-3 p-3 bg-white bg-opacity-50 rounded small">
                                        <i class="bi bi-info-circle me-1"></i> Based on the standard <strong>114-day</strong> period (3 months, 3 weeks, 3 days).
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4 text-end">
                                <hr class="my-4 opacity-25">
                                <a href="list.php" class="btn btn-link text-decoration-none text-muted me-3">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                                    <i class="bi bi-save me-2"></i>Record Serving
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('serving_date');
    const preview = document.getElementById('previewDate');

    function updateGestation() {
        if (!dateInput.value) return;
        const d = new Date(dateInput.value);
        d.setDate(d.getDate() + 114);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        preview.textContent = d.toLocaleDateString('en-GB', options);
    }

    dateInput.addEventListener('change', updateGestation);
    updateGestation(); // Initial load
});
</script>

<?php require_once '../includes/header.php'; ?>