<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $farrowing_id   = (int) $_POST['farrowing_id'];
    $sow_id         = (int) $_POST['sow_id'];
    $weaning_date   = $_POST['weaning_date'];
    $piglets_weaned = (int) $_POST['piglets_weaned'];
    $notes          = trim($_POST['notes']);

    $conn->begin_transaction();

    try {
        // Validate farrowing, sow & status
        $check = $conn->prepare("
            SELECT 
                s.status,
                f.piglets_alive,
                f.farrowing_date
            FROM farrowings f
            JOIN sows s ON f.sow_id = s.id
            LEFT JOIN weanings w ON w.farrowing_id = f.id
            WHERE f.id = ? AND w.id IS NULL
        ");
        $check->bind_param("i", $farrowing_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if (!$row) {
            throw new Exception("Invalid farrowing selected.");
        }

        if ($row['status'] !== 'Lactating') {
            throw new Exception("Sow must be in Lactating status to record weaning.");
        }

        if ($piglets_weaned < 0 || $piglets_weaned > $row['piglets_alive']) {
            throw new Exception("Piglets weaned cannot exceed piglets born alive ({$row['piglets_alive']}).");
        }

        // Insert weaning record
        $stmt = $conn->prepare("
            INSERT INTO weanings
                (farrowing_id, sow_id, weaning_date, piglets_weaned, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisis",
            $farrowing_id,
            $sow_id,
            $weaning_date,
            $piglets_weaned,
            $notes
        );
        $stmt->execute();

        // Update sow → Dry
        $stmt = $conn->prepare("
            UPDATE sows
            SET status = 'Dry'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $sow_id);
        $stmt->execute();

        $conn->commit();
        $success = "Weaning recorded successfully. Sow status updated to Dry.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Weaning failed: " . $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| FETCH AVAILABLE FARROWINGS (Lactating & Not Weaned)
|--------------------------------------------------------------------------
*/
$farrowings = $conn->query("
    SELECT 
        f.id,
        f.farrowing_date,
        f.piglets_alive,
        s.id AS sow_id,
        s.tag_no,
        s.breed
    FROM farrowings f
    JOIN sows s ON f.sow_id = s.id
    LEFT JOIN weanings w ON w.farrowing_id = f.id
    WHERE w.id IS NULL
      AND s.status = 'Lactating'
    ORDER BY f.farrowing_date ASC
");

$available_count = $farrowings->num_rows;

require_once __DIR__ . '/../includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">🍼 Record Weaning</h1>
    <a href="list.php" class="btn btn-outline-secondary">← Back</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $available_count ?></h3>
                <p class="text-muted mb-0">Litters Ready to Wean</p>
            </div>
        </div>
    </div>
</div>

<?php if ($available_count === 0): ?>

<div class="alert alert-warning">
    No lactating sows available for weaning.
</div>

<?php else: ?>

<form method="POST" class="card p-4">

    <div class="mb-3">
        <label class="form-label">Select Farrowing *</label>
        <select name="farrowing_id" id="farrowingSelect" class="form-select" required>
            <option value="">-- Select --</option>
            <?php while ($f = $farrowings->fetch_assoc()): 
                $days = (new DateTime($f['farrowing_date']))->diff(new DateTime())->days;
            ?>
            <option value="<?= $f['id'] ?>"
                data-sow="<?= $f['sow_id'] ?>"
                data-piglets="<?= $f['piglets_alive'] ?>"
                data-days="<?= $days ?>">
                🐷 <?= htmlspecialchars($f['tag_no']) ?> —
                <?= date('d M Y', strtotime($f['farrowing_date'])) ?>
                (<?= $days ?> days, <?= $f['piglets_alive'] ?> alive)
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <input type="hidden" name="sow_id" id="sow_id">

    <div class="mb-3">
        <label class="form-label">Weaning Date *</label>
        <input type="date" name="weaning_date" class="form-control"
               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Piglets Weaned *</label>
        <input type="number" name="piglets_weaned" id="piglets_weaned"
               class="form-control" min="0" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>

    <div class="text-end">
        <button class="btn btn-success">✓ Record Weaning</button>
    </div>

</form>

<?php endif; ?>

<script>
document.getElementById('farrowingSelect')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('sow_id').value = opt.dataset.sow || '';
    document.getElementById('piglets_weaned').max = opt.dataset.piglets || '';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
