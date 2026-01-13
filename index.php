<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

/* =====================
   DASHBOARD METRICS
===================== */

// Total sows
$totalSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status != 'Culled'")->fetch_assoc()['total'];

// Active boars
$activeBoars = $conn->query("SELECT COUNT(*) total FROM boars WHERE status='Active'")
                    ->fetch_assoc()['total'];

// Pregnant sows
$pregnantSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Pregnant'")
                     ->fetch_assoc()['total'];

// Total piglets born (this year)
$totalPiglets = $conn->query("
    SELECT COALESCE(SUM(piglets_alive),0) total 
    FROM farrowings
    WHERE YEAR(farrowing_date) = YEAR(CURDATE())
")->fetch_assoc()['total'];

// Nursing sows
$nursingSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Nursing'")->fetch_assoc()['total'];

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

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">📊</span> Dashboard Overview
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <span class="d-none d-sm-inline">Today: <?= date('d M Y') ?></span>
                <span class="d-inline d-sm-none"><?= date('d M') ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Alert for Overdue Farrowings -->
<?php if ($overdueCount > 0): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong>⚠️ Attention Required!</strong> 
    You have <?= $overdueCount ?> overdue farrowing<?= $overdueCount > 1 ? 's' : '' ?>. 
    <a href="/breeding/serve.php" class="alert-link">View details</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $totalSows ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐷</span> Total Sows
                </p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $activeBoars ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐗</span> Active Boars
                </p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $pregnantSows ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🤰</span> Pregnant
                </p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $nursingSows ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🍼</span> Nursing
                </p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $activeSows ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">✅</span> Ready
                </p>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $totalPiglets ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐽</span> Piglets (<?= date('Y') ?>)
                </p>
            </div>
        </div>
    </div>

</div>

<!-- Main Content Row -->
<div class="row mb-4">
    
    <!-- Upcoming Farrowings (Priority) -->
    <div class="col-12 col-lg-4 mb-4 mb-lg-0">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="emoji-icon">🔔</span> Upcoming Farrowings</span>
                <span class="badge bg-warning"><?= $upcomingFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($upcomingFarrowings->num_rows > 0): ?>
                    <ul class="list-unstyled mb-0">
                        <?php while ($row = $upcomingFarrowings->fetch_assoc()): ?>
                            <?php
                            $daysUntil = $row['days_until'];
                            $isUrgent = $daysUntil <= 3;
                            $badgeClass = $isUrgent ? 'danger' : 'warning';
                            ?>
                            <li class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="d-block">
                                            <span class="emoji-icon">🐷</span> Sow <?= htmlspecialchars($row['tag_no']) ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?= date('d M Y', strtotime($row['expected_farrowing'])) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?php if ($daysUntil == 0): ?>
                                            Today!
                                        <?php elseif ($daysUntil == 1): ?>
                                            Tomorrow
                                        <?php else: ?>
                                            <?= $daysUntil ?> days
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">📅</span>
                        <p class="mb-0">No farrowings due in the next 14 days</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($upcomingFarrowings->num_rows > 0): ?>
            <div class="card-footer text-center">
                <a href="<?= BASE_URL ?>/serving/list.php" class="btn btn-sm btn-outline-success">View All Servings</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Farrowings -->
    <div class="col-12 col-lg-4 mb-4 mb-lg-0">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="emoji-icon">🐣</span> Recent Farrowings</span>
                <span class="badge bg-success"><?= $recentFarrowings->num_rows ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($recentFarrowings->num_rows > 0): ?>
                    <ul class="list-unstyled mb-0">
                        <?php while ($row = $recentFarrowings->fetch_assoc()): ?>
                            <li class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong class="d-block">
                                            <span class="emoji-icon">🐷</span> Sow <?= htmlspecialchars($row['tag_no']) ?>
                                        </strong>
                                        <small class="text-muted d-block">
                                            <?= date('d M Y', strtotime($row['farrowing_date'])) ?>
                                            (<?= $row['days_ago'] ?> day<?= $row['days_ago'] != 1 ? 's' : '' ?> ago)
                                        </small>
                                        <div class="mt-2">
                                            <span class="badge bg-success me-1">
                                                ✓ <?= $row['piglets_alive'] ?> alive
                                            </span>
                                            <?php if ($row['stillbirths'] > 0): ?>
                                                <span class="badge bg-danger">
                                                    ✗ <?= $row['stillbirths'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">🐣</span>
                        <p class="mb-0">No farrowings in the last 7 days</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($recentFarrowings->num_rows > 0): ?>
            <div class="card-footer text-center">
                <a href="<?= BASE_URL ?>/farrowing/list.php" class="btn btn-sm btn-outline-success">View All Farrowings</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="emoji-icon">📝</span> Recent Activities</span>
                <span class="badge bg-secondary"><?= $recentActivities->num_rows ?></span>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th style="width: 30%;">Date</th>
                                <th>Activity</th>
                                <th class="d-none d-xl-table-cell">Animal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentActivities->num_rows > 0): ?>
                                <?php while ($row = $recentActivities->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block"><?= date('M d', strtotime($row['activity_date'])) ?></strong>
                                            <small class="text-muted"><?= date('Y', strtotime($row['activity_date'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="d-block"><?= htmlspecialchars($row['activity_type']) ?></span>
                                            <small class="text-muted d-xl-none">
                                                <?= $row['animal_type'] === 'General'
                                                    ? 'General'
                                                    : $row['animal_type'] . ' #' . $row['animal_id']; ?>
                                            </small>
                                        </td>
                                        <td class="d-none d-xl-table-cell">
                                            <span class="badge bg-light text-dark">
                                                <?= $row['animal_type'] === 'General'
                                                    ? 'General'
                                                    : $row['animal_type'] . ' #' . $row['animal_id']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">📝</span>
                                        <p class="mb-0">No activities logged yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($recentActivities->num_rows > 0): ?>
            <div class="card-footer text-center">
                <a href="<?= BASE_URL ?>/activities/list.php" class="btn btn-sm btn-outline-secondary">View All Activities</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Quick Actions Card -->
<div class="card">
    <div class="card-header">
        <span class="emoji-icon">⚡</span> Quick Actions
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/sows/add.php" class="btn btn-outline-success w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">🐷</span>
                    <small>Add Sow</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/boars/add.php" class="btn btn-outline-success w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">🐗</span>
                    <small>Add Boar</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/serving/add.php" class="btn btn-outline-primary w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">❤️</span>
                    <small>Record Serving</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/farrowing/add.php" class="btn btn-outline-primary w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">🐣</span>
                    <small>Record Farrowing</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/weaning/add.php" class="btn btn-outline-primary w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">🐷</span>
                    <small>Record Weaning</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= BASE_URL ?>/activities/add.php" class="btn btn-outline-secondary w-100">
                    <span class="d-block mb-1" style="font-size: 1.5rem;">📝</span>
                    <small>Add Activity</small>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>