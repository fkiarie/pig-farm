<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    die('Invalid sow ID');
}

/*
|--------------------------------------------------------------------------
| Fetch Sow Details
|--------------------------------------------------------------------------
*/
$sowResult = $conn->query("
    SELECT *
    FROM sows
    WHERE id = $id
    LIMIT 1
");

if ($sowResult->num_rows === 0) {
    die('Sow not found');
}

$sow = $sowResult->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Fetch Serving History
|--------------------------------------------------------------------------
*/
$servings = $conn->query("
    SELECT 
        s.serving_date,
        s.expected_farrowing,
        s.method,
        b.name AS boar_name
    FROM servings s
    LEFT JOIN boars b ON b.id = s.boar_id
    WHERE s.sow_id = $id
    ORDER BY s.serving_date DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>🐷 Sow Profile</h2>
    <div>
        <a href="edit.php?id=<?= $sow['id'] ?>" class="btn btn-sm btn-outline-primary">
            Edit
        </a>

        <?php if ($sow['status'] !== 'Culled'): ?>
            <a href="soft_delete.php?id=<?= $sow['id'] ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Cull this sow? This action can be reversed.')">
               Cull
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Sow Summary -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <strong>Name</strong><br>
                <?= htmlspecialchars($sow['tag_no']) ?>
            </div>
            <div class="col-6 col-md-3">
                <strong>Breed</strong><br>
                <?= $sow['breed'] ?: '—' ?>
            </div>
            <div class="col-6 col-md-3">
                <strong>Status</strong><br>
                <span class="badge bg-<?= $sow['status'] === 'Pregnant' ? 'warning' : ($sow['status'] === 'Culled' ? 'danger' : 'success') ?>">
                    <?= $sow['status'] ?>
                </span>
            </div>
            <div class="col-6 col-md-3">
                <strong>Date of Birth</strong><br>
                <?= $sow['date_of_birth'] ?: '—' ?>
            </div>
        </div>

        <?php if (!empty($sow['notes'])): ?>
            <hr>
            <strong>Notes</strong>
            <p class="mb-0"><?= nl2br(htmlspecialchars($sow['notes'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Serving History -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>📅 Serving History</span>
        <a href="../servings/create.php?sow_id=<?= $sow['id'] ?>"
           class="btn btn-sm btn-success">
           + Record Serving
        </a>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Serving Date</th>
                    <th>Boar</th>
                    <th>Method</th>
                    <th>Expected Farrowing</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servings->num_rows === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            No serving records found
                        </td>
                    </tr>
                <?php endif; ?>

                <?php while ($row = $servings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['serving_date'] ?></td>
                        <td><?= $row['boar_name'] ?: '—' ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?= ucfirst($row['method']) ?>
                            </span>
                        </td>
                        <td><?= $row['expected_farrowing'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="list.php" class="btn btn-secondary">
    ← Back to Sows
</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
