<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

// Fetch existing activity record
$stmt = $conn->prepare("SELECT * FROM daily_activities WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    die('Activity not found');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_date = $_POST['activity_date'];
    $activity_type = trim($_POST['activity_type']);
    $animal_type   = $_POST['animal_type'];
    $animal_id     = !empty($_POST['animal_id']) ? (int)$_POST['animal_id'] : null;
    $notes         = trim($_POST['notes']);

    try {
        $update = $conn->prepare("
            UPDATE daily_activities
            SET activity_date = ?, activity_type = ?, animal_type = ?, animal_id = ?, notes = ?
            WHERE id = ?
        ");
        $update->bind_param("sssisi", $activity_date, $activity_type, $animal_type, $animal_id, $notes, $id);
        
        if ($update->execute()) {
            header("Location: list.php?msg=updated");
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch animals for the dropdowns
$sows  = $conn->query("SELECT id, tag_no FROM sows WHERE status != 'Culled' ORDER BY tag_no");
$boars = $conn->query("SELECT id, name FROM boars WHERE status != 'Culled' ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1); }
    .quick-tag { cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
    .quick-tag:hover { transform: translateY(-2px); background-color: #e9ecef !important; }
    .context-label { background: #f8f9fa; padding: 10px 15px; border-radius: 8px; border-left: 4px solid #198754; margin-bottom: 20px; font-size: 0.9rem; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Activity</h1>
                    <p class="text-muted small mb-0">Update log details for record #<?= $id ?></p>
                </div>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Back to Log</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $error ?></div>
            <?php endif; ?>

            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="context-label">
                        <i class="bi bi-info-circle me-2"></i> 
                        Editing activity originally recorded on <strong><?= date('d M Y', strtotime($activity['created_at'])) ?></strong>
                    </div>

                    <form method="POST" id="editActivityForm">
                        <div class="row g-4">
                            
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Activity Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="activity_date" class="form-control border-start-0" 
                                           value="<?= htmlspecialchars($activity['activity_date']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold small">Activity Type</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag"></i></span>
                                    <input type="text" name="activity_type" id="activity_type" class="form-control border-start-0" 
                                           value="<?= htmlspecialchars($activity['activity_type']) ?>" required>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Feeding')">Feeding</span>
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Vaccination')">Vaccination</span>
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Cleaning')">Cleaning</span>
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Animal Type</label>
                                <select name="animal_type" id="animalType" class="form-select">
                                    <option value="General" <?= $activity['animal_type'] === 'General' ? 'selected' : '' ?>>General</option>
                                    <option value="Sow" <?= $activity['animal_type'] === 'Sow' ? 'selected' : '' ?>>Sow</option>
                                    <option value="Boar" <?= $activity['animal_type'] === 'Boar' ? 'selected' : '' ?>>Boar</option>
                                </select>
                            </div>

                            <div class="col-md-8" id="animalSelectRow" style="<?= $activity['animal_type'] === 'General' ? 'display:none;' : '' ?>">
                                <label class="form-label fw-bold small">Target Animal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-piggy-bank"></i></span>
                                    <select name="animal_id" id="animalId" class="form-select border-start-0">
                                        <option value="">-- Choose Animal --</option>
                                        
                                        <optgroup label="Sows" id="sowGroup">
                                            <?php while ($s = $sows->fetch_assoc()): ?>
                                                <option value="<?= $s['id'] ?>" <?= $activity['animal_id'] == $s['id'] ? 'selected' : '' ?>>
                                                    🐷 Tag: <?= htmlspecialchars($s['tag_no']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </optgroup>

                                        <optgroup label="Boars" id="boarGroup">
                                            <?php while ($b = $boars->fetch_assoc()): ?>
                                                <option value="<?= $b['id'] ?>" <?= $activity['animal_id'] == $b['id'] ? 'selected' : '' ?>>
                                                    🐗 Name: <?= htmlspecialchars($b['name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Notes</label>
                                <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($activity['notes']) ?></textarea>
                            </div>

                        </div>

                        <div class="mt-5 d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg flex-grow-1 shadow-sm">
                                <i class="bi bi-save me-2"></i>Update Record
                            </button>
                            <a href="list.php" class="btn btn-outline-secondary btn-lg px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setActivity(val) {
    document.getElementById('activity_type').value = val;
}

document.getElementById('animalType').addEventListener('change', function () {
    const row = document.getElementById('animalSelectRow');
    const sowGrp = document.getElementById('sowGroup');
    const boarGrp = document.getElementById('boarGroup');
    const animalId = document.getElementById('animalId');

    if (this.value === 'General') {
        row.style.display = 'none';
        animalId.value = '';
    } else {
        row.style.display = 'block';
        sowGrp.hidden = (this.value !== 'Sow');
        boarGrp.hidden = (this.value !== 'Boar');
    }
});

// Run once on load to ensure correct visibility if editing a Sow/Boar record
window.addEventListener('load', function() {
    const type = document.getElementById('animalType').value;
    if (type !== 'General') {
        document.getElementById('sowGroup').hidden = (type !== 'Sow');
        document.getElementById('boarGroup').hidden = (type !== 'Boar');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>