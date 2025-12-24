<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

// Fetch active sows (NOT pregnant)
$sows = $conn->query("
    SELECT id, tag_no, breed
    FROM sows 
    WHERE status = 'Active'
    ORDER BY tag_no
");

// Fetch active boars
$boars = $conn->query("
    SELECT id, name, breed
    FROM boars 
    WHERE status = 'Active'
    ORDER BY name
");

// Count available animals
$sow_count = $sows->num_rows;
$boar_count = $boars->num_rows;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sow_id = $_POST['sow_id'];
    $boar_id = $_POST['boar_id'];
    $serving_date = $_POST['serving_date'];
    $method = $_POST['method'];

    // Calculate expected farrowing (114 days)
    $expected_farrowing = date('Y-m-d', strtotime($serving_date . ' +114 days'));

    // Safety check: confirm sow is still not pregnant
    $check = $conn->prepare("SELECT status FROM sows WHERE id = ?");
    $check->bind_param("i", $sow_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if (!$result || $result['status'] !== 'Active') {
        $error = "This sow cannot be served (already pregnant or inactive).";
    } else {

        // Insert serving
        $stmt = $conn->prepare("
            INSERT INTO servings 
            (sow_id, boar_id, serving_date, expected_farrowing, method)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisss",
            $sow_id,
            $boar_id,
            $serving_date,
            $expected_farrowing,
            $method
        );

        if ($stmt->execute()) {

            // Update sow status to Pregnant
            $update = $conn->prepare("
                UPDATE sows SET status = 'Pregnant' WHERE id = ?
            ");
            $update->bind_param("i", $sow_id);
            $update->execute();

            $success = "Serving recorded successfully! Expected farrowing date: " . date('d M Y', strtotime($expected_farrowing));
        } else {
            $error = "Failed to record serving. Please try again.";
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">❤️</span> Record Serving
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="list.php" class="btn btn-outline-secondary">
            <span class="d-none d-sm-inline">← Back to List</span>
            <span class="d-inline d-sm-none">← Back</span>
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <div class="mt-2">
            <a href="list.php" class="btn btn-sm btn-success">View All Servings</a>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="window.location.reload()">
                Record Another Serving
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $sow_count ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐷</span> Available Sows
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $boar_count ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐗</span> Active Boars
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2">114</h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📅</span> Days to Farrow
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card" style="cursor: help;" data-bs-toggle="tooltip" title="Expected farrowing date will be calculated automatically">
            <div class="card-body">
                <h3 class="mb-2" id="expectedDate">—</h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐣</span> Expected Date
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Main Form Card -->
<div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <span class="emoji-icon me-2">📝</span>
                    Serving Information
                </h5>
            </div>
            <div class="card-body p-4">
                
                <?php if ($sow_count === 0 || $boar_count === 0): ?>
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading">
                            <span class="emoji-icon">⚠️</span> Cannot Record Serving
                        </h5>
                        <p class="mb-0">
                            <?php if ($sow_count === 0): ?>
                                No active sows available for serving. Please ensure you have active sows in the system.
                            <?php elseif ($boar_count === 0): ?>
                                No active boars available. Please ensure you have active boars in the system.
                            <?php endif; ?>
                        </p>
                        <hr>
                        <div class="d-flex gap-2">
                            <?php if ($sow_count === 0): ?>
                                <a href="../sows/add.php" class="btn btn-sm btn-warning">Add Sow</a>
                            <?php endif; ?>
                            <?php if ($boar_count === 0): ?>
                                <a href="../boars/add.php" class="btn btn-sm btn-warning">Add Boar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>

                <form method="POST" class="needs-validation" novalidate id="servingForm">

                    <div class="row g-4">
                        
                        <!-- Sow Selection -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select name="sow_id" id="sow_id" class="form-select" required>
                                    <option value="">Select a sow...</option>
                                    <?php 
                                    $sows->data_seek(0); // Reset pointer
                                    while ($sow = $sows->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $sow['id'] ?>" data-breed="<?= htmlspecialchars($sow['breed']) ?>">
                                            <?= htmlspecialchars($sow['tag_no']) ?>
                                            <?php if ($sow['breed']): ?>
                                                (<?= htmlspecialchars($sow['breed']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="sow_id">
                                    <span class="emoji-icon">🐷</span> Select Sow *
                                </label>
                                <div class="invalid-feedback">
                                    Please select a sow.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Choose the sow to be served
                            </small>
                        </div>

                        <!-- Boar Selection -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select name="boar_id" id="boar_id" class="form-select" required>
                                    <option value="">Select a boar...</option>
                                    <?php 
                                    $boars->data_seek(0); // Reset pointer
                                    while ($boar = $boars->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $boar['id'] ?>" data-breed="<?= htmlspecialchars($boar['breed']) ?>">
                                            <?= htmlspecialchars($boar['name']) ?>
                                            <?php if ($boar['breed']): ?>
                                                (<?= htmlspecialchars($boar['breed']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="boar_id">
                                    <span class="emoji-icon">🐗</span> Select Boar *
                                </label>
                                <div class="invalid-feedback">
                                    Please select a boar.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Choose the boar for serving
                            </small>
                        </div>

                        <!-- Serving Date -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="date" 
                                       name="serving_date" 
                                       id="serving_date" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d') ?>"
                                       required>
                                <label for="serving_date">
                                    <span class="emoji-icon">📅</span> Serving Date *
                                </label>
                                <div class="invalid-feedback">
                                    Please select a serving date.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Date when serving occurred
                            </small>
                        </div>

                        <!-- Method -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select name="method" id="method" class="form-select">
                                    <option value="Natural" selected>Natural Mating</option>
                                    <option value="AI">Artificial Insemination (AI)</option>
                                </select>
                                <label for="method">
                                    <span class="emoji-icon">🔬</span> Breeding Method
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Choose the breeding method used
                            </small>
                        </div>

                        <!-- Expected Farrowing Info Card -->
                        <div class="col-12">
                            <div class="alert alert-info" role="alert" id="farrowingInfo" style="display: none;">
                                <h6 class="alert-heading">
                                    <span class="emoji-icon">ℹ️</span> Breeding Information
                                </h6>
                                <p class="mb-2">
                                    <strong>Expected Farrowing Date:</strong> <span id="farrowingDate">—</span>
                                </p>
                                <small class="text-muted">
                                    The typical gestation period for pigs is approximately 114 days (3 months, 3 weeks, 3 days).
                                </small>
                            </div>
                        </div>

                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                        <a href="list.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <span class="emoji-icon">✓</span> Record Serving
                        </button>
                    </div>

                </form>

                <?php endif; ?>

            </div>
        </div>

        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <span class="emoji-icon">💡</span> Important Information
                </h6>
                <ul class="mb-0 small text-muted">
                    <li>Only active sows can be served</li>
                    <li>The sow's status will automatically change to "Pregnant" after recording</li>
                    <li>Expected farrowing date is calculated as 114 days from the serving date</li>
                    <li>Make sure to record the serving date accurately for proper tracking</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const servingDateInput = document.getElementById('serving_date');
    const expectedDateDisplay = document.getElementById('expectedDate');
    const farrowingInfo = document.getElementById('farrowingInfo');
    const farrowingDate = document.getElementById('farrowingDate');

    function calculateFarrowingDate() {
        const servingDate = servingDateInput.value;
        if (servingDate) {
            const date = new Date(servingDate);
            date.setDate(date.getDate() + 114);
            
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            
            expectedDateDisplay.textContent = formattedDate;
            farrowingDate.textContent = formattedDate;
            farrowingInfo.style.display = 'block';
        } else {
            expectedDateDisplay.textContent = '—';
            farrowingInfo.style.display = 'none';
        }
    }

    // Calculate on page load if date is set
    if (servingDateInput.value) {
        calculateFarrowingDate();
    }

    // Update when date changes
    servingDateInput.addEventListener('change', calculateFarrowingDate);

    // Form validation
    const form = document.getElementById('servingForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }

    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert && !successAlert.querySelector('.btn')) {
        setTimeout(function() {
            const alert = new bootstrap.Alert(successAlert);
            alert.close();
        }, 5000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>