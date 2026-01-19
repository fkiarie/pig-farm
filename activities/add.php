<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_date = $_POST['activity_date'];
    $activity_type = trim($_POST['activity_type']);
    $animal_type   = $_POST['animal_type'];
    $animal_id     = !empty($_POST['animal_id']) ? (int)$_POST['animal_id'] : null;
    $notes         = trim($_POST['notes']);

    try {
        if (!$activity_date || !$activity_type) {
            throw new Exception("Activity date and type are required.");
        }

        $stmt = $conn->prepare("INSERT INTO daily_activities (activity_date, activity_type, animal_type, animal_id, notes) VALUES (?, ?, ?, ?, ?)");
        // Use 'i' for animal_id (integer/null)
        $stmt->bind_param("sssis", $activity_date, $activity_type, $animal_type, $animal_id, $notes);

        if ($stmt->execute()) {
            $success = "Activity recorded successfully.";
        } else {
            throw new Exception("Execution failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch active animals
$sows  = $conn->query("SELECT id, tag_no FROM sows WHERE status != 'Culled' ORDER BY tag_no");
$boars = $conn->query("SELECT id, name FROM boars WHERE status != 'Culled' ORDER BY name");
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08); }
    .quick-tag { cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
    .quick-tag:hover { transform: translateY(-2px); background-color: #e9ecef !important; }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control, .form-select { border-left: none; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Record Activity</h1>
                    <p class="text-muted small mb-0">Log treatments, feeding, or maintenance tasks.</p>
                </div>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="bi bi-x-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
            <?php endif; ?>

            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" id="activityForm">
                        <div class="row g-4">
                            
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Activity Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="activity_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold small">Activity Type</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" name="activity_type" id="activity_type" class="form-control" placeholder="e.g., Vaccination" required>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Feeding')">Feeding</span>
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Vaccination')">Vaccination</span>
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1 me-1" onclick="setActivity('Cleaning')">Cleaning</span>
                                    <span class="badge bg-light text-dark border quick-tag px-2 py-1" onclick="setActivity('Heat Detection')">Heat Detection</span>
                                </div>
                            </div>

                            <hr class="my-4 opacity-25">

                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Scope</label>
                                <select name="animal_type" id="animalType" class="form-select border-start">
                                    <option value="General">General (Whole Farm)</option>
                                    <option value="Sow">Specific Sow</option>
                                    <option value="Boar">Specific Boar</option>
                                </select>
                            </div>

                            <div class="col-md-8" id="animalSelectRow" style="display:none;">
                                <label class="form-label fw-bold small">Select Animal</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <select name="animal_id" id="animalId" class="form-select">
                                        <option value="">-- Choose Animal --</option>
                                        
                                        <optgroup label="Sows" id="sowGroup">
                                            <?php while ($s = $sows->fetch_assoc()): ?>
                                                <option value="<?= $s['id'] ?>">🐷 Tag: <?= htmlspecialchars($s['tag_no']) ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>

                                        <optgroup label="Boars" id="boarGroup">
                                            <?php while ($b = $boars->fetch_assoc()): ?>
                                                <option value="<?= $b['id'] ?>">🐗 Name: <?= htmlspecialchars($b['name']) ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Observation Notes</label>
                                <textarea name="notes" class="form-control border-start" rows="4" placeholder="Mention dosages, specific pen numbers, or health observations..."></textarea>
                            </div>

                        </div>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm">
                                <i class="bi bi-cloud-arrow-up me-2"></i>Save Activity Record
                            </button>
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
        animalId.value = ''; // Reset selection
    } else {
        row.style.display = 'block';
        sowGrp.hidden = (this.value !== 'Sow');
        boarGrp.hidden = (this.value !== 'Boar');
        animalId.value = ''; // Reset selection to force a valid choice
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>