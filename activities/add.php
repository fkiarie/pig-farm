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

        $stmt = $conn->prepare("
            INSERT INTO daily_activities
            (activity_date, activity_type, animal_type, animal_id, notes)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssds",
            $activity_date,
            $activity_type,
            $animal_type,
            $animal_id,
            $notes
        );

        $stmt->execute();
        $success = "Daily activity recorded successfully.";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch animals
$sows  = $conn->query("SELECT id, tag_no FROM sows WHERE status != 'Culled' ORDER BY tag_no");
$boars = $conn->query("SELECT id, name FROM boars ORDER BY name");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">📝 Record Daily Activity</h1>
        <a href="list.php" class="btn btn-outline-secondary">← Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4">

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label">Activity Date *</label>
                <input type="date" name="activity_date" class="form-control"
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Activity Type *</label>
                <input type="text" name="activity_type" class="form-control"
                       placeholder="Feeding, Treatment, Cleaning..." required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Animal Type</label>
                <select name="animal_type" id="animalType" class="form-select">
                    <option value="General">General</option>
                    <option value="Sow">Sow</option>
                    <option value="Boar">Boar</option>
                </select>
            </div>

            <div class="col-md-8" id="animalSelect" style="display:none;">
                <label class="form-label">Select Animal</label>

                <select name="animal_id" id="animalId" class="form-select">
                    <option value="">— Select —</option>

                    <optgroup label="Sows" id="sowOptions">
                        <?php while ($s = $sows->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>">
                                🐷 <?= htmlspecialchars($s['tag_no']) ?>
                            </option>
                        <?php endwhile; ?>
                    </optgroup>

                    <optgroup label="Boars" id="boarOptions">
                        <?php while ($b = $boars->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>">
                                🐗 <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </optgroup>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>

        </div>

        <div class="mt-4 text-end">
            <button class="btn btn-success">✓ Save Activity</button>
        </div>

    </form>
</div>

<script>
document.getElementById('animalType').addEventListener('change', function () {
    const animalSelect = document.getElementById('animalSelect');
    const sowGroup = document.getElementById('sowOptions');
    const boarGroup = document.getElementById('boarOptions');

    if (this.value === 'General') {
        animalSelect.style.display = 'none';
    } else {
        animalSelect.style.display = 'block';
        sowGroup.style.display  = this.value === 'Sow' ? 'block' : 'none';
        boarGroup.style.display = this.value === 'Boar' ? 'block' : 'none';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
s