<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

/* =====================
   DASHBOARD METRICS
===================== */

// Total sows
$totalSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status != 'Culled'")->fetch_assoc()['total'];

// Active boars
$activeBoars = $conn->query("SELECT COUNT(*) total FROM boars WHERE status='Active'")->fetch_assoc()['total'];

// Pregnant sows
$pregnantSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Pregnant'")->fetch_assoc()['total'];

// Total piglets born (this year)
$totalPiglets = $conn->query("
    SELECT COALESCE(SUM(piglets_alive),0) total 
    FROM farrowings
    WHERE YEAR(farrowing_date) = YEAR(CURDATE())
")->fetch_assoc()['total'];

// Lactating sows
$nursingSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Lactating'")->fetch_assoc()['total'];

// Active sows (ready for breeding)
$activeSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Active'")->fetch_assoc()['total'];

// Recent activities
$recentActivities = $conn->query("
    SELECT *
    FROM daily_activities
    ORDER BY activity_date DESC, id DESC
    LIMIT 10
");

// Upcoming farrowings (next 14 days)
$upcomingFarrowings = $conn->query("
    SELECT 
        s.tag_no, 
        sv.expected_farrowing,
        DATEDIFF(sv.expected_farrowing, CURDATE()) as days_until
    FROM servings sv
    JOIN sows s ON s.id = sv.sow_id
    WHERE s.status = 'Pregnant'
      AND sv.expected_farrowing BETWEEN CURDATE() 
      AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ORDER BY sv.expected_farrowing ASC
");

// Recent farrowings (last 7 days)
$recentFarrowings = $conn->query("
    SELECT 
        s.tag_no,
        f.farrowing_date,
        f.piglets_alive,
        f.stillbirths,
        DATEDIFF(CURDATE(), f.farrowing_date) as days_ago
    FROM farrowings f
    JOIN sows s ON s.id = f.sow_id
    WHERE f.farrowing_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY f.farrowing_date DESC
    LIMIT 5
");

// Health alerts (overdue farrowings)
$overdueCount = $conn->query("
    SELECT COUNT(*) as total
    FROM servings sv
    JOIN sows s ON s.id = sv.sow_id
    WHERE s.status = 'Pregnant' 
      AND sv.expected_farrowing < CURDATE()
")->fetch_assoc()['total'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; background: #fff; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .stat-card:hover { transform: translateY(-5px); }
    .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 10px; margin-bottom: 1rem; }
    .bg-soft-primary { background-color: #e7f1ff; color: #0d6efd; }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-info { background-color: #cff4fc; color: #055160; }
    .bg-soft-danger { background-color: #f8d7da; color: #842029; }
    .card-header { background: transparent; font-weight: 600; border-bottom: 1px solid #f0f0f0; }
    .activity-table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard Overview
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm">
            <i class="bi bi-calendar3 me-1"></i> Today: <?= date('d M Y') ?>
        </button>
    </div>
</div>

<?php if ($overdueCount > 0): ?>
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <div class="d-flex">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
        <div>
            <strong>Attention Required!</strong> You have <?= $overdueCount ?> overdue farrowing record(s).
            <a href="<?= BASE_URL ?>/serving/list.php" class="alert-link ms-1">Update Status Now</a>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-primary"><i class="bi bi-gender-female fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $totalSows ?></h3>
                <p class="text-muted small mb-0">Total Sows</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-info"><i class="bi bi-gender-male fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $activeBoars ?></h3>
                <p class="text-muted small mb-0">Active Boars</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-warning"><i class="bi bi-heart-pulse fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $pregnantSows ?></h3>
                <p class="text-muted small mb-0">Pregnant</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-success"><i class="bi bi-droplet fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $nursingSows ?></h3>
                <p class="text-muted small mb-0">Nursing</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-primary"><i class="bi bi-check-circle fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $activeSows ?></h3>
                <p class="text-muted small mb-0">Ready/Open</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-box bg-soft-success"><i class="bi bi-graph-up-arrow fs-4"></i></div>
                <h3 class="fw-bold mb-1"><?= $totalPiglets ?></h3>
                <p class="text-muted small mb-0">Piglets (<?= date('Y') ?>)</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    
    <div class="col-12 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm border-0 rounded-3">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-bell me-2 text-warning"></i>Upcoming Farrowings</span>
                <span class="badge rounded-pill bg-warning text-dark"><?= $upcomingFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($upcomingFarrowings->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($row = $upcomingFarrowings->fetch_assoc()): ?>
                            <?php
                            $daysUntil = $row['days_until'];
                            $isUrgent = $daysUntil <= 3;
                            $textClass = $isUrgent ? 'text-danger fw-bold' : '';
                            ?>
                            <div class="list-group-item px-0 py-3 border-bottom border-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Sow #<?= htmlspecialchars($row['tag_no']) ?></h6>
                                        <p class="text-muted small mb-0">
                                            <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($row['expected_farrowing'])) ?>
                                        </p>
                                    </div>
                                    <span class="badge <?= $isUrgent ? 'bg-danger' : 'bg-light text-dark border' ?> rounded-pill">
                                        <?= $daysUntil == 0 ? 'Today' : ($daysUntil == 1 ? 'Tomorrow' : "$daysUntil Days") ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-check fs-1 d-block mb-2 op-50"></i>
                        <p>No farrowings due soon.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm border-0 rounded-3">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-stars me-2 text-success"></i>Recent Farrowings</span>
                <span class="badge rounded-pill bg-success"><?= $recentFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($recentFarrowings->num_rows > 0): ?>
                    <?php while ($row = $recentFarrowings->fetch_assoc()): ?>
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold">Sow #<?= htmlspecialchars($row['tag_no']) ?></h6>
                                <small class="text-muted"><?= $row['days_ago'] == 0 ? 'Today' : $row['days_ago'] . " days ago" ?></small>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-soft-success border border-success border-opacity-25 px-2 py-1">
                                   <i class="bi bi-plus-circle me-1"></i><?= $row['piglets_alive'] ?> Alive
                                </span>
                                <?php if ($row['stillbirths'] > 0): ?>
                                <span class="badge bg-soft-danger border border-danger border-opacity-25 px-2 py-1">
                                   <i class="bi bi-dash-circle me-1"></i><?= $row['stillbirths'] ?> Dead
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clipboard2-minus fs-1 d-block mb-2 op-50"></i>
                        <p>No recent births logged.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm border-0 rounded-3">
            <div class="card-header py-3">
                <i class="bi bi-list-task me-2 text-primary"></i>Recent Activity
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table activity-table table-hover mb-0 align-middle">
                        <tbody>
                            <?php if ($recentActivities->num_rows > 0): ?>
                                <?php while ($row = $recentActivities->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3" style="width: 25%;">
                                            <span class="fw-bold d-block small"><?= date('M d', strtotime($row['activity_date'])) ?></span>
                                            <span class="text-muted x-small"><?= date('Y', strtotime($row['activity_date'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="d-block small"><?= htmlspecialchars($row['activity_type']) ?></span>
                                            <span class="text-muted x-small">
                                                <i class="bi bi-tag me-1"></i><?= $row['animal_type'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="text-center py-5">No activities logged</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header py-3">
        <i class="bi bi-lightning-charge me-2 text-primary"></i>Quick Management
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/sows/add.php" class="btn btn-outline-primary w-100 py-3 shadow-sm border-light-subtle">
                    <i class="bi bi-plus-lg d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Add Sow</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/boars/add.php" class="btn btn-outline-primary w-100 py-3 shadow-sm border-light-subtle">
                    <i class="bi bi-plus-circle d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Add Boar</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/serving/add.php" class="btn btn-outline-info w-100 py-3 shadow-sm border-light-subtle text-dark">
                    <i class="bi bi-pencil-square d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Serving</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/farrowing/add.php" class="btn btn-outline-success w-100 py-3 shadow-sm border-light-subtle">
                    <i class="bi bi-hospital d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Farrowing</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/weaning/add.php" class="btn btn-outline-warning w-100 py-3 shadow-sm border-light-subtle text-dark">
                    <i class="bi bi-scissors d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Weaning</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/activities/add.php" class="btn btn-outline-secondary w-100 py-3 shadow-sm border-light-subtle">
                    <i class="bi bi-journal-text d-block mb-1 fs-4"></i>
                    <small class="fw-semibold">Activity</small>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>