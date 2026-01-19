<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farrowing_id   = (int) $_POST['farrowing_id'];
    $sow_id         = (int) $_POST['sow_id'];
    $weaning_date   = $_POST['weaning_date'];
    $piglets_weaned = (int) $_POST['piglets_weaned'];
    $notes          = trim($_POST['notes']);

    $conn->begin_transaction();
    try {
        $check = $conn->prepare("
            SELECT s.status, f.piglets_alive FROM farrowings f
            JOIN sows s ON f.sow_id = s.id
            LEFT JOIN weanings w ON w.farrowing_id = f.id
            WHERE f.id = ? AND w.id IS NULL
        ");
        $check->bind_param("i", $farrowing_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if (!$row) throw new Exception("Invalid farrowing selected.");
        if ($row['status'] !== 'Lactating') throw new Exception("Sow must be 'Lactating' to record weaning.");
        if ($piglets_weaned > $row['piglets_alive']) throw new Exception("Weaned count cannot exceed alive count ({$row['piglets_alive']}).");

        $stmt = $conn->prepare("INSERT INTO weanings (farrowing_id, sow_id, weaning_date, piglets_weaned, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $farrowing_id, $sow_id, $weaning_date, $piglets_weaned, $notes);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE sows SET status = 'Dry' WHERE id = ?");
        $stmt->bind_param("i", $sow_id);
        $stmt->execute();

        $conn->commit();
        header("Location: list.php?msg=weaned");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

$farrowings = $conn->query("
    SELECT f.id, f.farrowing_date, f.piglets_alive, s.id AS sow_id, s.tag_no
    FROM farrowings f
    JOIN sows s ON f.sow_id = s.id
    LEFT JOIN weanings w ON w.farrowing_id = f.id
    WHERE w.id IS NULL AND s.status = 'Lactating'
    ORDER BY f.farrowing_date ASC
");
$available_count = $farrowings->num_rows;

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 15px; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08); }
    .status-preview { background: #f0f7ff; border-radius: 10px; padding: 15px; border-left: 4px solid #0d6efd; }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control, .form-select { border-left: none; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-box-arrow-right text-primary me-2"></i>Record Weaning</h1>
                    <p class="text-muted small">Moving piglets from sow to weaning nursery.</p>
                </div>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $error ?></div>
            <?php endif; ?>

            <div class="alert <?= $available_count > 0 ? 'alert-info' : 'alert-warning' ?> border-0 shadow-sm mb-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong><?= $available_count ?></strong> litters currently in the farrowing house ready for weaning.
            </div>

            <?php if ($available_count > 0): ?>
            <div class="card form-card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" id="weanForm">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small">1. Select Farrowing Record</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <select name="farrowing_id" id="farrowingSelect" class="form-select" required>
                                    <option value="">-- Choose Sow --</option>
                                    <?php while ($f = $farrowings->fetch_assoc()): 
                                        $age = (new DateTime($f['farrowing_date']))->diff(new DateTime())->days;
                                    ?>
                                        <option value="<?= $f['id'] ?>" 
                                                data-sow="<?= $f['sow_id'] ?>" 
                                                data-alive="<?= $f['piglets_alive'] ?>">
                                            Sow <?= htmlspecialchars($f['tag_no']) ?> (Age: <?= $age ?> days)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <input type="hidden" name="sow_id" id="sow_id">
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">2. Weaning Date</label>
                                <input type="date" name="weaning_date" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">3. Count Weaned</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="number" name="piglets_weaned" id="weanedCount" 
                                           class="form-control" min="0" placeholder="Number of piglets" required>
                                </div>
                                <div id="maxHint" class="form-text text-muted">Select a sow to see born alive count.</div>
                            </div>
                        </div>

                        <div class="status-preview mb-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3 fs-3 text-primary"><i class="bi bi-arrow-left-right"></i></div>
                                <div>
                                    <span class="d-block fw-bold">Automatic Status Update</span>
                                    <small class="text-muted">The sow will be moved from <strong>Lactating</strong> to <strong>Dry</strong> status.</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Condition of piglets, weaning weights, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="bi bi-check-circle me-2"></i>Complete Weaning Process
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('farrowingSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const sowId = selected.dataset.sow || '';
    const alive = selected.dataset.alive || '';
    
    document.getElementById('sow_id').value = sowId;
    const countInput = document.getElementById('weanedCount');
    
    if(alive) {
        countInput.max = alive;
        document.getElementById('maxHint').innerHTML = `<i class="bi bi-info-circle"></i> Max possible: <strong>${alive}</strong> (Born alive)`;
    } else {
        document.getElementById('maxHint').innerText = 'Select a sow to see born alive count.';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>