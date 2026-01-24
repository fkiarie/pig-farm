<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

/* =====================
   DASHBOARD METRICS
===================== */

// Total active sows
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

// Nursing sows
$nursingSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Lactating'")->fetch_assoc()['total'];

// Active sows (ready for breeding)
$activeSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Active'")->fetch_assoc()['total'];

/* =====================
   RECENT ACTIVITIES
===================== */
$recentActivities = $conn->query("
    SELECT 
        da.*,
        s.tag_no AS sow_tag,
        b.name AS boar_name
    FROM daily_activities da
    LEFT JOIN sows s ON da.animal_type = 'Sow' AND da.animal_id = s.id
    LEFT JOIN boars b ON da.animal_type = 'Boar' AND da.animal_id = b.id
    ORDER BY da.activity_date DESC, da.id DESC
    LIMIT 10
");

/* =====================
   UPCOMING FARROWINGS
===================== */
$upcomingFarrowings = $conn->query("
    SELECT 
        s.id as sow_id,
        s.tag_no, 
        s.breed,
        sv.expected_farrowing,
        DATEDIFF(sv.expected_farrowing, CURDATE()) AS days_until
    FROM servings sv
    JOIN sows s ON s.id = sv.sow_id
    WHERE s.status = 'Pregnant'
      AND sv.expected_farrowing BETWEEN CURDATE() 
      AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ORDER BY sv.expected_farrowing ASC
");

/* =====================
   RECENT FARROWINGS
===================== */
$recentFarrowings = $conn->query("
    SELECT 
        s.id as sow_id,
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

// Overdue farrowings
$overdueCount = $conn->query("
    SELECT COUNT(*) total
    FROM servings sv
    JOIN sows s ON s.id = sv.sow_id
    WHERE s.status = 'Pregnant'
      AND sv.expected_farrowing < CURDATE()
")->fetch_assoc()['total'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .stat-card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .stat-card h3 { font-size: 1.5rem; font-weight: 700; }
    .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .bg-soft-primary { background: #e7f1ff; color: #0d6efd; }
    .bg-soft-success { background: #e1f6e1; color: #198754; }
    .bg-soft-warning { background: #fff3cd; color: #856404; }
    .bg-soft-info { background: #cff4fc; color: #055160; }
    .activity-dot { position: absolute; left: 0; top: 5px; width: 10px; height: 10px; background: #dee2e6; border-radius: 50%; }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h4 fw-bold">
        <i class="bi bi-speedometer2 me-2 text-primary"></i>Farm Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
        </button>
    </div>
</div>

<?php if ($overdueCount > 0): ?>
<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Attention Required!</strong> 
    You have <?= $overdueCount ?> overdue farrowing<?= $overdueCount > 1 ? 's' : '' ?>. 
    <a href="<?= BASE_URL ?>/breeding/serve.php" class="alert-link">Review now</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-primary"><i class="bi bi-gender-female"></i></div>
                <h3 class="mb-1"><?= $totalSows ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total Sows</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-info"><i class="bi bi-gender-male"></i></div>
                <h3 class="mb-1"><?= $activeBoars ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Active Boars</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-warning"><i class="bi bi-calendar-check"></i></div>
                <h3 class="mb-1"><?= $pregnantSows ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Pregnant</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-success"><i class="bi bi-droplet"></i></div>
                <h3 class="mb-1"><?= $nursingSows ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Nursing</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-primary"><i class="bi bi-check-circle"></i></div>
                <h3 class="mb-1"><?= $activeSows ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Ready</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="icon-circle bg-soft-success"><i class="bi bi-stars"></i></div>
                <h3 class="mb-1"><?= $totalPiglets ?></h3>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Piglets (<?= date('Y') ?>)</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    
    <div class="col-12 col-lg-4 mb-4 mb-lg-0">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bell me-2 text-warning"></i>Upcoming</span>
                <span class="badge rounded-pill bg-light text-dark border"><?= $upcomingFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($upcomingFarrowings->num_rows > 0): ?>
                    <ul class="list-unstyled mb-0">
                        <?php while ($row = $upcomingFarrowings->fetch_assoc()): ?>
                            <?php
                            $daysUntil = $row['days_until'];
                            $badgeClass = ($daysUntil <= 3) ? 'danger' : 'warning';
                            ?>
                            <li class="mb-3 pb-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <a href="<?= BASE_URL ?>/sows/profile.php?id=<?= $row['sow_id'] ?>" class="text-decoration-none text-dark fw-bold">
                                            Sow #<?= htmlspecialchars($row['tag_no']) ?>
                                        </a>
                                        <small class="text-muted d-block small">Due: <?= date('d M', strtotime($row['expected_farrowing'])) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $badgeClass ?> rounded-pill">
                                        <?= $daysUntil == 0 ? 'Today' : ($daysUntil == 1 ? 'Tomorrow' : $daysUntil . ' days') ?>
                                    </span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-4 small">No upcoming farrowings.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4 mb-4 mb-lg-0">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-house-heart me-2 text-success"></i>Recent Farrow</span>
                <span class="badge rounded-pill bg-light text-dark border"><?= $recentFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($recentFarrowings->num_rows > 0): ?>
                    <?php while ($row = $recentFarrowings->fetch_assoc()): ?>
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/sows/profile.php?id=<?= $row['sow_id'] ?>" class="text-decoration-none text-dark fw-bold">Sow #<?= htmlspecialchars($row['tag_no']) ?></a>
                                <small class="text-muted small"><?= $row['days_ago'] ?>d ago</small>
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-soft-success text-success border-0 px-2">✓ <?= $row['piglets_alive'] ?> Alive</span>
                                <?php if ($row['stillbirths'] > 0): ?>
                                    <span class="badge bg-soft-warning text-danger border-0 px-2">✗ <?= $row['stillbirths'] ?> Still</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4 small">No farrowings this week.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Activity Log</span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($recentActivities->num_rows > 0): ?>
                    <?php while ($act = $recentActivities->fetch_assoc()): ?>
                        <div class="position-relative ps-3 mb-3 border-bottom pb-2">
                            <div class="activity-dot"></div>
                            <small class="text-muted d-block small" style="font-size: 10px;">
                                <?= date('d M, H:i', strtotime($act['activity_date'])) ?>
                            </small>
                            <span class="fw-bold small d-block"><?= htmlspecialchars($act['activity_type']) ?></span>
                            <small class="text-muted">
                                <?= $act['animal_type'] ?> #<?= htmlspecialchars($act['sow_tag'] ?? $act['boar_name'] ?? 'General') ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-4 small">No activities logged.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <span class="fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Quick Actions</span>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/sows/add.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-plus-circle d-block mb-1 fs-4 text-success"></i>
                    <small class="fw-bold">Add Sow</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/breeding/serve.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-heart d-block mb-1 fs-4 text-danger"></i>
                    <small class="fw-bold">Record Serve</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/breeding/farrowing.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-house-heart d-block mb-1 fs-4 text-primary"></i>
                    <small class="fw-bold">Farrowing</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/breeding/weaning.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-box-arrow-right d-block mb-1 fs-4 text-info"></i>
                    <small class="fw-bold">Weaning</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/activities/add.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-pencil-square d-block mb-1 fs-4 text-secondary"></i>
                    <small class="fw-bold">Activity</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/reports/general.php" class="btn btn-light w-100 py-3 border">
                    <i class="bi bi-graph-up d-block mb-1 fs-4 text-dark"></i>
                    <small class="fw-bold">Reports</small>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>