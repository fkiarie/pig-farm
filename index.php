<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

/* =====================
   DASHBOARD METRICS
===================== */

// Total sows
$totalSows = $conn->query("SELECT COUNT(*) total FROM sows")->fetch_assoc()['total'];

// Active boars
$activeBoars = $conn->query("SELECT COUNT(*) total FROM boars WHERE status='Active'")
                    ->fetch_assoc()['total'];

// Pregnant sows
$pregnantSows = $conn->query("SELECT COUNT(*) total FROM sows WHERE status='Pregnant'")
                     ->fetch_assoc()['total'];

// Total piglets born
$totalPiglets = $conn->query("
    SELECT COALESCE(SUM(piglets_alive),0) total 
    FROM farrowings
")->fetch_assoc()['total'];

// Recent activities
$recentActivities = $conn->query("
    SELECT *
    FROM daily_activities
    ORDER BY activity_date DESC, id DESC
    LIMIT 5
");

// Upcoming farrowings (next 7 days)
$upcomingFarrowings = $conn->query("
    SELECT s.tag_no, sv.expected_farrowing
    FROM servings sv
    JOIN sows s ON s.id = sv.sow_id
    WHERE sv.expected_farrowing BETWEEN CURDATE() 
      AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY sv.expected_farrowing ASC
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">📊 Dashboard</h1>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $totalSows ?></h3>
                <p class="text-muted mb-0">🐷 Total Sows</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $activeBoars ?></h3>
                <p class="text-muted mb-0">🐗 Active Boars</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $pregnantSows ?></h3>
                <p class="text-muted mb-0">🤰 Pregnant</p>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3><?= $totalPiglets ?></h3>
                <p class="text-muted mb-0">🐽 Total Piglets</p>
            </div>
        </div>
    </div>

</div>

<!-- Recent Activities -->
<div class="row mb-4">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                Recent Activities
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th class="d-none d-md-table-cell">Animal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentActivities->num_rows > 0): ?>
                            <?php while ($row = $recentActivities->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d', strtotime($row['activity_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['activity_type']) ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?= $row['animal_type'] === 'General'
                                            ? 'General'
                                            : $row['animal_type'] . ' #' . $row['animal_id']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    No activities logged yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upcoming Farrowings -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                Upcoming Farrowings
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php if ($upcomingFarrowings->num_rows > 0): ?>
                        <?php while ($row = $upcomingFarrowings->fetch_assoc()): ?>
                            <li class="mb-3 pb-3 border-bottom">
                                <strong>Sow <?= $row['tag_no'] ?></strong><br>
                                <small class="text-muted">
                                    <?= date('M d, Y', strtotime($row['expected_farrowing'])) ?>
                                </small>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="text-muted">
                            No farrowings due in the next 7 days
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
