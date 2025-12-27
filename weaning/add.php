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
        // Validate farrowing & sow
        $check = $conn->prepare("
            SELECT s.status, f.piglets_alive, f.farrowing_date
            FROM farrowings f
            JOIN sows s ON f.sow_id = s.id
            LEFT JOIN weanings w ON w.farrowing_id = f.id
            WHERE f.id = ? AND w.id IS NULL
        ");
        $check->bind_param("i", $farrowing_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();

        if (!$row || $row['status'] !== 'Nursing') {
            throw new Exception("Invalid weaning state. Sow must be in nursing status.");
        }

        if ($piglets_weaned > $row['piglets_alive']) {
            throw new Exception("Cannot wean more piglets than were born alive ({$row['piglets_alive']}).");
        }

        // Insert weaning
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

        // Update sow → Active
        $stmt = $conn->prepare("
            UPDATE sows
            SET status = 'Active'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $sow_id);
        $stmt->execute();

        $conn->commit();
        $success = "Weaning recorded successfully! Sow status updated to Active.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Weaning failed: " . $e->getMessage();
    }
}

// Fetch available farrowings (not yet weaned, sow is nursing)
$farrowings = $conn->query("
SELECT f.id, f.farrowing_date, f.piglets_alive, s.id AS sow_id, s.tag_no, s.breed
FROM farrowings f
JOIN sows s ON f.sow_id = s.id
LEFT JOIN weanings w ON w.farrowing_id = f.id
WHERE w.id IS NULL AND s.status = 'Nursing'
ORDER BY f.farrowing_date ASC
");

$available_count = $farrowings->num_rows;

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐷</span> Record Weaning
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
            <a href="list.php" class="btn btn-sm btn-success">View All Weanings</a>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="window.location.reload()">
                Record Another Weaning
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $available_count ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐷</span> Ready to Wean
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2">21-28</h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📅</span> Optimal Days
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-4">
        <div class="card stat-card" id="nursingDaysCard" style="cursor: help;" data-bs-toggle="tooltip" title="Nursing days will be calculated when you select a farrowing">
            <div class="card-body">
                <h3 class="mb-2" id="nursingDays">—</h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">⏱️</span> Nursing Days
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
                    Weaning Information
                </h5>
            </div>
            <div class="card-body p-4">
                
                <?php if ($available_count === 0): ?>
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading">
                            <span class="emoji-icon">⚠️</span> No Litters Ready for Weaning
                        </h5>
                        <p class="mb-0">
                            There are no nursing sows with litters ready to wean. Make sure you have recorded farrowings with sows in "Nursing" status.
                        </p>
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/farrowing/list.php" class="btn btn-sm btn-warning">View Farrowings</a>
                            <a href="list.php" class="btn btn-sm btn-outline-secondary">View Weaning History</a>
                        </div>
                    </div>
                <?php else: ?>

                <form method="POST" class="needs-validation" novalidate id="weaningForm">

                    <div class="row g-4">
                        
                        <!-- Farrowing Selection -->
                        <div class="col-12">
                            <div class="form-floating">
                                <select name="farrowing_id" id="farrowingSelect" class="form-select" required>
                                    <option value="">Select a farrowing to wean...</option>
                                    <?php 
                                    $farrowings->data_seek(0);
                                    while ($f = $farrowings->fetch_assoc()): 
                                        $farrowDate = new DateTime($f['farrowing_date']);
                                        $today = new DateTime();
                                        $daysOld = $farrowDate->diff($today)->days;
                                    ?>
                                        <option value="<?= $f['id'] ?>" 
                                                data-sow="<?= $f['sow_id'] ?>"
                                                data-piglets="<?= $f['piglets_alive'] ?>"
                                                data-farrow-date="<?= $f['farrowing_date'] ?>"
                                                data-days-old="<?= $daysOld ?>">
                                            🐷 <?= htmlspecialchars($f['tag_no']) ?>
                                            <?php if ($f['breed']): ?>
                                                (<?= htmlspecialchars($f['breed']) ?>)
                                            <?php endif; ?>
                                            — Born: <?= date('d M Y', strtotime($f['farrowing_date'])) ?>
                                            (<?= $daysOld ?> days old) — <?= $f['piglets_alive'] ?> piglets alive
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="farrowingSelect">
                                    <span class="emoji-icon">🐣</span> Select Farrowing *
                                </label>
                                <div class="invalid-feedback">
                                    Please select a farrowing to wean.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Choose the litter you want to wean
                            </small>
                        </div>

                        <input type="hidden" name="sow_id" id="sow_id">

                        <!-- Farrowing Details Card (Hidden initially) -->
                        <div class="col-12" id="farrowingDetails" style="display: none;">
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <span class="emoji-icon">ℹ️</span> Litter Information
                                </h6>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">Sow Tag</small>
                                        <strong id="detailSowTag">—</strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">Born Date</small>
                                        <strong id="detailFarrowDate">—</strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">Days Old</small>
                                        <strong id="detailDaysOld">—</strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">Piglets Alive</small>
                                        <strong id="detailPigletsAlive">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Weaning Date -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="date" 
                                       name="weaning_date" 
                                       id="weaning_date" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d') ?>"
                                       required>
                                <label for="weaning_date">
                                    <span class="emoji-icon">📅</span> Weaning Date *
                                </label>
                                <div class="invalid-feedback">
                                    Please select a weaning date.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Date when piglets are weaned
                            </small>
                        </div>

                        <!-- Piglets Weaned -->
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="number" 
                                       name="piglets_weaned" 
                                       id="piglets_weaned" 
                                       class="form-control" 
                                       min="0" 
                                       required>
                                <label for="piglets_weaned">
                                    <span class="emoji-icon">🐽</span> Number of Piglets Weaned *
                                </label>
                                <div class="invalid-feedback">
                                    Please enter the number of piglets weaned.
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2" id="pigletsHelper">
                                How many piglets survived to weaning
                            </small>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea name="notes" 
                                          id="notes" 
                                          class="form-control" 
                                          placeholder="Add any notes..."
                                          style="height: 100px"></textarea>
                                <label for="notes">
                                    <span class="emoji-icon">📝</span> Notes (Optional)
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Additional observations or comments about the weaning
                            </small>
                        </div>

                        <!-- Nursing Period Warning -->
                        <div class="col-12" id="nursingWarning" style="display: none;">
                            <div class="alert alert-warning" role="alert">
                                <strong>⚠️ Early Weaning Notice:</strong>
                                <span id="warningMessage"></span>
                            </div>
                        </div>

                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                        <a href="list.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <span class="emoji-icon">✓</span> Record Weaning
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
                    <span class="emoji-icon">💡</span> Weaning Guidelines
                </h6>
                <ul class="mb-0 small text-muted">
                    <li><strong>Optimal Weaning Age:</strong> 21-28 days for best piglet health and development</li>
                    <li><strong>Early Weaning:</strong> Before 21 days may reduce piglet survival and growth rates</li>
                    <li><strong>Late Weaning:</strong> After 28 days may reduce sow productivity</li>
                    <li><strong>Post-Weaning:</strong> Sow status will automatically update to "Active" and ready for next breeding</li>
                    <li><strong>Record Mortality:</strong> Only count piglets that survived to weaning date</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const farrowingSelect = document.getElementById('farrowingSelect');
    const sowIdInput = document.getElementById('sow_id');
    const pigletsWeanedInput = document.getElementById('piglets_weaned');
    const farrowingDetails = document.getElementById('farrowingDetails');
    const nursingWarning = document.getElementById('nursingWarning');
    const nursingDaysDisplay = document.getElementById('nursingDays');
    const nursingDaysCard = document.getElementById('nursingDaysCard');

    farrowingSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            // Set hidden sow ID
            sowIdInput.value = selectedOption.dataset.sow || '';
            
            // Get data
            const sowTag = selectedOption.textContent.split('—')[0].replace('🐷', '').trim();
            const farrowDate = selectedOption.dataset.farrowDate;
            const daysOld = parseInt(selectedOption.dataset.daysOld);
            const pigletsAlive = parseInt(selectedOption.dataset.piglets);
            
            // Update details card
            document.getElementById('detailSowTag').textContent = sowTag;
            document.getElementById('detailFarrowDate').textContent = new Date(farrowDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            document.getElementById('detailDaysOld').textContent = daysOld + ' days';
            document.getElementById('detailPigletsAlive').textContent = pigletsAlive + ' piglets';
            farrowingDetails.style.display = 'block';
            
            // Update nursing days stat card
            nursingDaysDisplay.textContent = daysOld;
            if (daysOld < 21) {
                nursingDaysCard.classList.add('border-warning');
                nursingDaysCard.classList.remove('border-success', 'border-info');
            } else if (daysOld <= 28) {
                nursingDaysCard.classList.add('border-success');
                nursingDaysCard.classList.remove('border-warning', 'border-info');
            } else {
                nursingDaysCard.classList.add('border-info');
                nursingDaysCard.classList.remove('border-warning', 'border-success');
            }
            
            // Set max piglets weaned
            pigletsWeanedInput.max = pigletsAlive;
            pigletsWeanedInput.value = pigletsAlive;
            document.getElementById('pigletsHelper').innerHTML = 
                `Maximum ${pigletsAlive} piglets (number born alive)`;
            
            // Check for early/late weaning
            if (daysOld < 21) {
                nursingWarning.style.display = 'block';
                document.getElementById('warningMessage').textContent = 
                    `Piglets are only ${daysOld} days old. Early weaning (before 21 days) may affect piglet health and growth.`;
            } else if (daysOld > 28) {
                nursingWarning.style.display = 'block';
                document.getElementById('warningMessage').textContent = 
                    `Piglets are ${daysOld} days old. Extended nursing beyond 28 days may reduce sow productivity.`;
            } else {
                nursingWarning.style.display = 'none';
            }
        } else {
            // Reset everything
            sowIdInput.value = '';
            farrowingDetails.style.display = 'none';
            nursingWarning.style.display = 'none';
            pigletsWeanedInput.value = '';
            pigletsWeanedInput.max = '';
            nursingDaysDisplay.textContent = '—';
            nursingDaysCard.classList.remove('border-warning', 'border-success', 'border-info');
            document.getElementById('pigletsHelper').textContent = 'How many piglets survived to weaning';
        }
    });

    // Form validation
    const form = document.getElementById('weaningForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }

    // Validate piglets weaned doesn't exceed alive
    pigletsWeanedInput.addEventListener('input', function() {
        const max = parseInt(this.max);
        const value = parseInt(this.value);
        
        if (value > max) {
            this.setCustomValidity(`Cannot wean more than ${max} piglets`);
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>