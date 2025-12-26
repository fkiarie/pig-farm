<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Handle form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $serving_id       = (int) $_POST['serving_id'];
    $sow_id           = (int) $_POST['sow_id'];
    $farrowing_date   = $_POST['farrowing_date'];
    $total_born       = (int) $_POST['total_born'];
    $piglets_alive    = (int) $_POST['piglets_alive'];
    $stillbirths      = (int) $_POST['stillbirths'];
    $notes            = trim($_POST['notes']);

    // Basic validation
    if ($total_born !== ($piglets_alive + $stillbirths)) {
        die("Error: Total born must equal alive + stillbirths.");
    }

    $conn->begin_transaction();

    try {
        /**
         * 1. Validate serving belongs to sow
         */
        $check = $conn->prepare("
            SELECT sow_id 
            FROM servings 
            WHERE id = ? AND status != 'Completed'
        ");
        $check->bind_param("i", $serving_id);
        $check->execute();
        $serving = $check->get_result()->fetch_assoc();

        if (!$serving || $serving['sow_id'] != $sow_id) {
            throw new Exception("Invalid or already completed serving selected.");
        }

        /**
         * 2. Insert farrowing
         */
        $stmt = $conn->prepare("
            INSERT INTO farrowings
            (serving_id, sow_id, farrowing_date, total_born, piglets_alive, stillbirths, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisiiis",
            $serving_id,
            $sow_id,
            $farrowing_date,
            $total_born,
            $piglets_alive,
            $stillbirths,
            $notes
        );
        $stmt->execute();

        /**
         * 3. Mark serving as completed
         */
        $stmt = $conn->prepare("
            UPDATE servings
            SET status = 'Completed'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $serving_id);
        $stmt->execute();

        /**
         * 4. Update sow status → Lactating
         */
        $stmt = $conn->prepare("
            UPDATE sows
            SET status = 'Lactating'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $sow_id);
        $stmt->execute();

        /**
         * 5. Commit transaction
         */
        $conn->commit();

        header("Location: list.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Farrowing save failed: " . $e->getMessage());
    }
}

/**
 * Load available servings (not yet farrowed)
 */
$servings = $conn->query("
    SELECT 
        sv.id,
        sv.serving_date,
        s.id AS sow_id,
        s.tag_no
    FROM servings sv
    JOIN sows s ON sv.sow_id = s.id
    LEFT JOIN farrowings f ON f.serving_id = sv.id
    WHERE f.id IS NULL
      AND sv.status != 'Completed'
    ORDER BY sv.serving_date DESC
");

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">🐖 Record Farrowing</h1>

<form method="post" class="card p-3 shadow-sm">

    <div class="mb-3">
        <label class="form-label">Serving (Sow & Date)</label>
        <select name="serving_id" id="servingSelect" class="form-select" required>
            <option value="">-- Select Serving --</option>
            <?php while ($sv = $servings->fetch_assoc()): ?>
                <option value="<?= $sv['id'] ?>"
                        data-sow="<?= $sv['sow_id'] ?>">
                    Sow <?= htmlspecialchars($sv['tag_no']) ?> —
                    <?= htmlspecialchars($sv['serving_date']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <input type="hidden" name="sow_id" id="sow_id">

    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Total Born</label>
            <input type="number" name="total_born" class="form-control" min="0" required>
        </div>
        <div class="col-md-4 mb-3">
            <label>Alive</label>
            <input type="number" name="piglets_alive" class="form-control" min="0" value="0">
        </div>
        <div class="col-md-4 mb-3">
            <label>Stillbirths</label>
            <input type="number" name="stillbirths" class="form-control" min="0" value="0">
        </div>
    </div>

    <div class="mb-3">
        <label>Farrowing Date</label>
        <input type="date" name="farrowing_date" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-success">Save Farrowing</button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.getElementById('servingSelect').addEventListener('change', function () {
    const sowId = this.options[this.selectedIndex].dataset.sow || '';
    document.getElementById('sow_id').value = sowId;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
