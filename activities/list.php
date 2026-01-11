<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Daily Activities
|--------------------------------------------------------------------------
*/
$query = "
    SELECT 
        da.id,
        da.activity_date,
        da.activity_type,
        da.animal_type,
        da.animal_id,
        da.notes,
        da.created_at,

        s.tag_no AS sow_tag,
        b.name   AS boar_name

    FROM daily_activities da
    LEFT JOIN sows s 
        ON da.animal_type = 'Sow' AND da.animal_id = s.id
    LEFT JOIN boars b 
        ON da.animal_type = 'Boar' AND da.animal_id = b.id

    ORDER BY da.activity_date DESC, da.id DESC
";

$activities = $conn->query($query);
$total = $activities->num_rows;
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        📝 Daily Activities
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            + Record Activity
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $total ?></h3>
                <p class="text-muted mb-0">Total Activities</p>
            </div>
        </div>
    </div>
</div>

<!-- Activities Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Activity Log</span>
        <span class="badge bg-secondary"><?= $total ?> records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Animal</th>
                    <th class="d-none d-md-table-cell">Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php if ($total === 0): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <h5>No activities recorded</h5>
                        <p>Start by recording daily farm activities.</p>
                        <a href="add.php" class="btn btn-success">+ Record First Activity</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $activities->fetch_assoc()): ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong><?= date('d M Y', strtotime($row['activity_date'])) ?></strong>
                        </td>

                        <!-- Activity Type -->
                        <td>
                            <span class="badge bg-info">
                                <?= htmlspecialchars($row['activity_type']) ?>
                            </span>
                        </td>

                        <!-- Animal -->
                        <td>
                            <?php if ($row['animal_type'] === 'Sow'): ?>
                                🐷 <?= htmlspecialchars($row['sow_tag'] ?? 'Unknown Sow') ?>
                            <?php elseif ($row['animal_type'] === 'Boar'): ?>
                                🐗 <?= htmlspecialchars($row['boar_name'] ?? 'Unknown Boar') ?>
                            <?php else: ?>
                                🌾 General
                            <?php endif; ?>
                        </td>

                        <!-- Notes -->
                        <td class="d-none d-md-table-cell">
                            <?= nl2br(htmlspecialchars($row['notes'] ?: '—')) ?>
                        </td>

                        <!-- Actions -->
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="edit.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="Edit activity">
                                   ✏️
                                </a>
                                <a href="delete.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this activity?')"
                                   data-bs-toggle="tooltip"
                                   title="Delete activity">
                                   🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
