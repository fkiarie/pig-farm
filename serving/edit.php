<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../auth/auth_check.php';
require_once '../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_id");
    exit();
}

$id = (int)$_GET['id'];
$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sow_id = $_POST['sow_id'];
    $boar_id = $_POST['boar_id'];
    $serving_date = $_POST['serving_date'];
    $method = $_POST['method'];
    $status = $_POST['status'];

    // Calculate expected farrowing (Standard: 114-115 days)
    $expected_farrowing = date('Y-m-d', strtotime($serving_date . ' + 114 days'));

    $stmt = $conn->prepare("UPDATE servings SET sow_id = ?, boar_id = ?, serving_date = ?, expected_farrowing = ?, method = ?, status = ? WHERE id = ?");
    $stmt->bind_param("iissssi", $sow_id, $boar_id, $serving_date, $expected_farrowing, $method, $status, $id);

    if ($stmt->execute()) {
        // If status is changed to completed or if this is a pregnant record, you might want to update the sow status here as well
        header("Location: index.php?success=Record updated");
        exit();
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

/*
|--------------------------------------------------------------------------
| Fetch Current Record & Dropdown Data
|--------------------------------------------------------------------------
*/
// Get current serving details
$res = $conn->query("SELECT * FROM servings WHERE id = $id");
$current = $res->fetch_assoc();

if (!$current) {
    header("Location: index.php?error=not_found");
    exit();
}

// Get Sows and Boars for dropdowns
$sows = $conn->query("SELECT id, tag_no, breed FROM sows WHERE status != 'Culled' ORDER BY tag_no ASC");
$boars = $conn->query("SELECT id, name, breed FROM boars WHERE status = 'Active' OR id = " . $current['boar_id']);

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h3">Edit Breeding Record</h1>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Sow (Female)</label>
                            <select name="sow_id" class="form-select" required>
                                <?php while($sow = $sows->fetch_assoc()): ?>
                                    <option value="<?= $sow['id'] ?>" <?= $sow['id'] == $current['sow_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sow['tag_no']) ?> (<?= htmlspecialchars($sow['breed']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Boar (Male)</label>
                            <select name="boar_id" class="form-select" required>
                                <?php while($boar = $boars->fetch_assoc()): ?>
                                    <option value="<?= $boar['id'] ?>" <?= $boar['id'] == $current['boar_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($boar['name']) ?> (<?= htmlspecialchars($boar['breed']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Serving Date</label>
                                <input type="date" name="serving_date" class="form-control" 
                                       value="<?= $current['serving_date'] ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select">
                                    <option value="Natural" <?= $current['method'] == 'Natural' ? 'selected' : '' ?>>Natural</option>
                                    <option value="AI" <?= $current['method'] == 'AI' ? 'selected' : '' ?>>Artificial Insemination</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Record Status</label>
                            <select name="status" class="form-select">
                                <option value="Scheduled" <?= $current['status'] == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="Completed" <?= $current['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $current['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <div class="form-text">Change to 'Completed' once pregnancy is confirmed or farrowing occurs.</div>
                        </div>

                        <hr>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Breeding Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5>Current Projections</h5>
                    <p class="text-muted small">Based on the current serving date recorded.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                            Expected Farrowing:
                            <span class="fw-bold text-success"><?= date('d M Y', strtotime($current['expected_farrowing'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                            Gestation Progress:
                            <?php
                                $start = new DateTime($current['serving_date']);
                                $today = new DateTime();
                                $diff = $start->diff($today);
                                echo $diff->days . " days passed";
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>