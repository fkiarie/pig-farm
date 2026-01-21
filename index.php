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

// Lactating sows
$nursingSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Lactating'")->fetch_assoc()['total'];

// Active sows (ready for breeding)
$activeSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Active'")->fetch_assoc()['total'];

// Recent activities
$recentActivities = $conn->query("
    SELECT *
    FROM daily_activities
    ORDER BY activity_date DESC, id DESC
    LIMIT 8
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
    .stat-card { border: none; border-radius: 16px; transition: transform 0.2s; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .stat-card:hover { transform: translateY(-3px); }
    .icon-box { width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 0.75rem; }
    
    /* Soft color palette */
    .bg-soft-primary { background-color: #e7f1ff; color: #0d6efd; }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-info { background-color: #cff4fc; color: #055160; }
    
    .quick-btn { border: 1px solid #eee; border-radius: 12px; padding: 1rem; text-decoration: none; color: #333; transition: all 0.2s; background: #fff; }
    .quick-btn:hover { background: #f8f9fa; border-color: #198754; color: #198754; }
    
    .activity-feed { font-size: 0.85rem; }
    .activity-item { border-left: 2px solid #e9ecef; padding-left: 15px; position: relative; margin-bottom: 15px; }
    .activity-item::before { content: ""; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #0d6efd; }
</style>

<div class="container-fluid px-3 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0 text-dark">Farm Dashboard</h1>
            <p class="text-muted small mb-0"><?= date('l, d M Y') ?></p>
        </div>
        <div class="dropdown">
            <button class="btn btn-light btn-sm rounded-pill px-3 border shadow-sm" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-plus-lg me-1"></i> Quick Add
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/sow/add.php">New Sow</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/activities/add.php">Daily Activity</a></li>
            </ul>
        </div>
    </div>

    <?php if ($overdueCount > 0): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
        <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
        <div class="small">
            <span class="fw-bold">Overdue Farrowings!</span> <?= $overdueCount ?> sows are past their due date.
            <a href="<?= BASE_URL ?>/serving/list.php" class="alert-link d-block d-md-inline ms-md-2">Review now &raquo;</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-primary"><i class="bi bi-gender-female"></i></div>
                    <h4 class="fw-bold mb-0"><?= $totalSows ?></h4>
                    <span class="text-muted x-small">Total Sows</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-info"><i class="bi bi-gender-male"></i></div>
                    <h4 class="fw-bold mb-0"><?= $activeBoars ?></h4>
                    <span class="text-muted x-small">Boars</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-warning"><i class="bi bi-calendar-check"></i></div>
                    <h4 class="fw-bold mb-0 text-warning"><?= $pregnantSows ?></h4>
                    <span class="text-muted x-small">Pregnant</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-success"><i class="bi bi-droplet"></i></div>
                    <h4 class="fw-bold mb-0 text-success"><?= $nursingSows ?></h4>
                    <span class="text-muted x-small">Nursing</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-primary"><i class="bi bi-door-open"></i></div>
                    <h4 class="fw-bold mb-0"><?= $activeSows ?></h4>
                    <span class="text-muted x-small">Open/Ready</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="icon-box bg-soft-success"><i class="bi bi-stars"></i></div>
                    <h4 class="fw-bold mb-0"><?= $totalPiglets ?></h4>
                    <span class="text-muted x-small">Born (<?= date('Y') ?>)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-white pt-4 pb-0">
                    <h6 class="fw-bold"><i class="bi bi-alarm me-2 text-danger"></i>Upcoming Tasks</h6>
                </div>
                <div class="card-body">
                    <?php if ($upcomingFarrowings->num_rows > 0): ?>
                        <?php while ($row = $upcomingFarrowings->fetch_assoc()): ?>
                            <div class="d-flex align-items-center mb-3 p-2 border-bottom border-light">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 small fw-bold">Sow #<?= $row['tag_no'] ?></h6>
                                    <span class="text-muted x-small">Due: <?= date('d M', strtotime($row['expected_farrowing'])) ?></span>
                                </div>
                                <span class="badge <?= $row['days_until'] <= 3 ? 'bg-danger' : 'bg-light text-dark border' ?> rounded-pill">
                                    in <?= $row['days_until'] ?> days
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted small py-4">No farrowings due this week.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-white pt-4 pb-0">
                    <h6 class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Activity Log</h6>
                </div>
                <div class="card-body activity-feed">
                    <?php while ($act = $recentActivities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <span class="text-muted d-block x-small"><?= date('d M, H:i', strtotime($act['created_at'])) ?></span>
                            <span class="fw-semibold"><?= htmlspecialchars($act['activity_type']) ?></span>
                            <span class="text-muted">- <?= $act['animal_type'] ?> <?= $act['animal_id'] ? '(ID:'.$act['animal_id'].')' : '(General)' ?></span>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>/activities/list.php" class="btn btn-link btn-sm text-decoration-none">View full history &raquo;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-bold mb-3 px-1">Actions</h6>
    <div class="row g-2 mb-5">
        <div class="col-4">
            <a href="<?= BASE_URL ?>/sow/add.php" class="quick-btn d-flex flex-column align-items-center text-center">
                <i class="bi bi-plus-square fs-4 mb-1"></i>
                <span class="x-small">Add Sow</span>
            </a>
        </div>
        <div class="col-4">
            <a href="<?= BASE_URL ?>/serving/add.php" class="quick-btn d-flex flex-column align-items-center text-center">
                <i class="bi bi-heart fs-4 mb-1 text-danger"></i>
                <span class="x-small">Serving</span>
            </a>
        </div>
        <div class="col-4">
            <a href="<?= BASE_URL ?>/farrowing/add.php" class="quick-btn d-flex flex-column align-items-center text-center">
                <i class="bi bi-house-heart fs-4 mb-1 text-success"></i>
                <span class="x-small">Farrow</span>
            </a>
        </div>
    </div>

</div>

<style>
    /* Responsive Helper Classes */
    .x-small { font-size: 0.75rem; }
    @media (max-width: 576px) {
        .stat-card .card-body { padding: 0.75rem; }
        .stat-card h4 { font-size: 1.1rem; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>