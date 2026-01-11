<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid activity ID');
}

// Fetch activity
$stmt = $conn->prepare("
    SELECT * FROM daily_activities WHERE id = ?
");
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
    $animal_id     = $_POST['animal_id'] !== '' ? (int)$_POST['animal_id'] : null;
    $notes         = trim($_POST['notes']);

    try {
        $stmt = $conn->prepare("
            UPDATE daily_activities
            SET activity_date = ?, activity_type = ?, animal_type = ?, animal_id = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssisi",
            $activity_date,
            $activity_type,
            $animal_type,
            $animal_id,
            $notes,
            $id
        );
        $stmt->execute();

        $success = "Activity updated successfully.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h4 mb-4">✏️ Edit Daily Activity</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4">
        <div class="mb-3">
            <label class="form-label">Activity Date</label>
            <input type="date" name="activity_date" class="form-control"
                   value="<?= htmlspecialchars($activity['activity_date']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Activity Type</label>
            <input type="text" name="activity_type" class="form-control"
                   value="<?= htmlspecialchars($activity['activity_type']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Animal Type</label>
            <select name="animal_type" class="form-select">
                <option value="General" <?= $activity['animal_type'] === 'General' ? 'selected' : '' ?>>General</option>
                <option value="Sow" <?= $activity['animal_type'] === 'Sow' ? 'selected' : '' ?>>Sow</option>
                <option value="Boar" <?= $activity['animal_type'] === 'Boar' ? 'selected' : '' ?>>Boar</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Animal ID (optional)</label>
            <input type="number" name="animal_id" class="form-control"
                   value="<?= htmlspecialchars($activity['animal_id']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($activity['notes']) ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
