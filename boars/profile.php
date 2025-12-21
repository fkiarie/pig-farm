<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) die('Invalid boar ID');

/*
|--------------------------------------------------------------------------
| Boar Details
|--------------------------------------------------------------------------
*/
$boarResult = $conn->query("
    SELECT * FROM boars WHERE id = $id LIMIT 1
");

if ($boarResult->num_rows === 0) {
    die('Boar not found');
}

$boar = $boarResult->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Serving History
|--------------------------------------------------------------------------
*/
$servings = $conn->query("
    SELECT 
        s.serving_date,
        s.method,
        sw.tag_no AS sow_tag,
        s.expected_farrowing
    FROM servings s
    JOIN sows sw ON sw.id = s.sow_id
    WHERE s.boar_id = $id
    ORDER BY s.serving_date DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>🐗 Boar Profile</h2>
    <a href="edit.php?id=<?= $boar['id'] ?>" class="btn btn-sm btn-outline-primary">
        Edit
    </a>
</div>

<!-- Boar Summary -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <strong>Tag</strong><br>
                <?= htmlspecialchars($boar['name']) ?>
            </div>
            <div class="col-6 col-md-3">
                <strong>Breed</strong><br>
                <?= $boar['breed'] ?: '—' ?>
            </div>
            <div class="col-6 col-md-3">
                <strong>Status</strong><br>
                <span class="badge bg-secondary">
                    <?= $boar['status'] ?>
                </span>
            </div>
            <div class="col-6 col-md-3">
                <strong>Date Added</strong><br>
                <?= date('d M Y', strtotime($boar['created_at'])) ?>
            </div>
        </div>

        <?php if ($boar['notes']): ?>
            <hr>
            <strong>Notes</strong>
            <p class="mb-0"><?= nl2br(htmlspecialchars($boar['notes'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Serving History -->
<div class="card mb-4">
    <div class="card-header">📅 Serving History</div>

    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sow</th>
                    <th>Method</th>
                    <th>Expected Farrowing</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servings->num_rows === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            No serving records
                        </td>
                    </tr>
                <?php endif; ?>

                <?php while ($row = $servings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['serving_date'] ?></td>
                        <td><?= $row['sow_tag'] ?></td>
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
    ← Back to Boars
</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
