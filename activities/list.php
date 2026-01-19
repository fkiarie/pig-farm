<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/* Fetch Activities with Animal Details */
$query = "
    SELECT 
        da.*,
        s.tag_no AS sow_tag,
        b.name   AS boar_name
    FROM daily_activities da
    LEFT JOIN sows s ON da.animal_type = 'Sow' AND da.animal_id = s.id
    LEFT JOIN boars b ON da.animal_type = 'Boar' AND da.animal_id = b.id
    ORDER BY da.activity_date DESC, da.id DESC
";

$activities = $conn->query($query);
$total = $activities->num_rows;

/* Helper function for color coding activity types */
function getActivityClass($type) {
    $type = strtolower($type);
    if (strpos($type, 'med') !== false || strpos($type, 'vax') !== false) return 'bg-danger-subtle text-danger border-danger';
    if (strpos($type, 'feed') !== false) return 'bg-success-subtle text-success border-success';
    if (strpos($type, 'heat') !== false || strpos($type, 'breed') !== false) return 'bg-warning-subtle text-dark border-warning';
    return 'bg-primary-subtle text-primary border-primary';
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .type-badge { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; border: 1px solid; }
    .activity-row:hover { background-color: #f8fafc !important; }
    .stat-card { background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); border: none; }
    .animal-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #f1f5f9; margin-right: 10px; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-journal-text text-success me-2"></i>Daily Activities</h1>
            <p class="text-muted small mb-0">Complete audit trail of farm operations and animal care.</p>
        </div>
        <a href="add.php" class="btn btn-success px-4 shadow-sm">
            <i class="bi bi-plus-lg me-2"></i>Record Activity
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="fs-2 text-success me-3"><i class="bi bi-list-check"></i></div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $total ?></h4>
                        <small class="text-muted text-uppercase fw-bold">Total Logs</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="mb-0 fw-bold">Activity Feed</h6>
                </div>
                <div class="col text-end">
                    <span class="badge bg-light text-dark border"><?= $total ?> entries</span>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 150px;">Date</th>
                        <th style="width: 180px;">Activity Type</th>
                        <th>Target Animal</th>
                        <th class="d-none d-lg-table-cell">Notes</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-clipboard-x fs-1 opacity-25"></i>
                                    <h5 class="mt-3">No activity logs found</h5>
                                    <p class="small">Start recording daily tasks like feeding or medications.</p>
                                    <a href="add.php" class="btn btn-sm btn-success mt-2">+ Record First Task</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $activities->fetch_assoc()): 
                            $dateObj = new DateTime($row['activity_date']);
                            $isToday = $dateObj->format('Y-m-d') === date('Y-m-d');
                        ?>
                            <tr class="activity-row">
                                <td class="ps-4">
                                    <div class="fw-bold <?= $isToday ? 'text-success' : '' ?>">
                                        <?= $dateObj->format('d M Y') ?>
                                    </div>
                                    <?php if ($isToday): ?>
                                        <span class="badge bg-success" style="font-size: 0.6rem;">TODAY</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="type-badge <?= getActivityClass($row['activity_type']) ?>">
                                        <?= htmlspecialchars($row['activity_type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="animal-icon">
                                            <?php if ($row['animal_type'] === 'Sow'): ?>
                                                <i class="bi bi-female text-danger"></i>
                                            <?php elseif ($row['animal_type'] === 'Boar'): ?>
                                                <i class="bi bi-male text-primary"></i>
                                            <?php else: ?>
                                                <i class="bi bi-house-gear text-secondary"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block">
                                                <?php 
                                                    if ($row['animal_type'] === 'Sow') echo htmlspecialchars($row['sow_tag'] ?? 'Unassigned');
                                                    elseif ($row['animal_type'] === 'Boar') echo htmlspecialchars($row['boar_name'] ?? 'Unassigned');
                                                    else echo 'General Maintenance';
                                                ?>
                                            </span>
                                            <small class="text-muted"><?= $row['animal_type'] ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td class="d-none d-lg-table-cell">
                                    <p class="mb-0 small text-muted text-truncate" style="max-width: 300px;">
                                        <?= htmlspecialchars($row['notes'] ?: 'No additional notes provided.') ?>
                                    </p>
                                </td>

                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm border rounded">
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border-0" title="Edit">
                                            <i class="bi bi-pencil text-primary"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border-0" 
                                           onclick="return confirm('Permanently delete this activity log?')" title="Delete">
                                            <i class="bi bi-trash text-danger"></i>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>