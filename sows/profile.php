<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header('Location: list.php'); exit; }

/* Fetch Sow Details */
$sowResult = $conn->query("SELECT * FROM sows WHERE id = $id LIMIT 1");
if ($sowResult->num_rows === 0) { header('Location: list.php'); exit; }
$sow = $sowResult->fetch_assoc();

/* Statistics Calculation */
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM servings WHERE sow_id = $id) as total_servings,
        (SELECT COUNT(*) FROM farrowings WHERE sow_id = $id) as total_farrowings,
        COALESCE(SUM(f.piglets_alive), 0) as total_piglets_alive,
        COALESCE(SUM(w.piglets_weaned), 0) as total_weaned
    FROM farrowings f
    LEFT JOIN weanings w ON w.farrowing_id = f.id
    WHERE f.sow_id = $id
")->fetch_assoc();

$avgLitterSize = $stats['total_farrowings'] > 0 
    ? round($stats['total_piglets_alive'] / $stats['total_farrowings'], 1) 
    : 0;

/* Fetch Breeding History */
$servings = $conn->query("
    SELECT s.*, b.name as boar_name, f.id as farrowing_id, f.piglets_alive, f.farrowing_date, w.piglets_weaned
    FROM servings s
    LEFT JOIN boars b ON s.boar_id = b.id
    LEFT JOIN farrowings f ON f.serving_id = s.id
    LEFT JOIN weanings w ON w.farrowing_id = f.id
    WHERE s.sow_id = $id
    ORDER BY s.serving_date DESC
");

/* Fetch Daily Activities Tied to this Sow */
$activities = $conn->query("
    SELECT * FROM daily_activities 
    WHERE animal_type = 'Sow' AND animal_id = $id 
    ORDER BY activity_date DESC, id DESC
");

$age_display = '—';
if ($sow['date_of_birth']) {
    $dob = new DateTime($sow['date_of_birth']);
    $diff = $dob->diff(new DateTime());
    $age_display = $diff->y . 'y, ' . $diff->m . 'm';
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .profile-card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .stat-box { border-radius: 10px; padding: 15px; background: #f8f9fa; border: 1px solid #eee; text-align: center; height: 100%; }
    .stat-box h3 { font-weight: 700; color: #198754; margin-bottom: 5px; }
    .stat-box small { color: #6c757d; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; font-weight: 600; }
    .nav-pills .nav-link { color: #6c757d; font-weight: 600; border-radius: 8px; padding: 10px 20px; }
    .nav-pills .nav-link.active { background-color: #198754; }
    .activity-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 10px; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-person-badge text-success me-2"></i>Sow Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="list.php" class="text-success text-decoration-none">Sows</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($sow['tag_no']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="btn-group shadow-sm">
            <a href="edit.php?id=<?= $sow['id'] ?>" class="btn btn-white border border-secondary-subtle">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="list.php" class="btn btn-white border border-secondary-subtle">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card profile-card h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-4 rounded-circle bg-light mb-3">
                            <i class="bi bi-piggy-bank text-success fs-1"></i>
                        </div>
                        <h2 class="h4 mb-1"><?= htmlspecialchars($sow['tag_no']) ?></h2>
                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 rounded-pill">
                            <?= $sow['status'] ?>
                        </span>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Breed</span>
                            <span class="fw-bold"><?= htmlspecialchars($sow['breed']) ?: '—' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Age</span>
                            <span class="fw-bold"><?= $age_display ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Registered</span>
                            <span class="fw-bold"><?= date('d M Y', strtotime($sow['created_at'])) ?></span>
                        </li>
                    </ul>

                    <?php if (!empty($sow['notes'])): ?>
                    <div class="mt-4 p-3 bg-light rounded border-start border-success border-4 shadow-sm">
                        <small class="text-muted d-block mb-1 fw-bold text-uppercase">General Notes</small>
                        <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($sow['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="row g-3 mb-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="stat-box shadow-sm">
                        <small>Servings</small>
                        <h3><?= $stats['total_servings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box shadow-sm">
                        <small>Farrowings</small>
                        <h3><?= $stats['total_farrowings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box shadow-sm">
                        <small>Avg Litter</small>
                        <h3><?= $avgLitterSize ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box shadow-sm">
                        <small>Weaned</small>
                        <h3><?= $stats['total_weaned'] ?></h3>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills mb-3 bg-white p-2 rounded shadow-sm d-inline-flex" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-breeding-tab" data-bs-toggle="pill" data-bs-target="#pills-breeding" type="button" role="tab"><i class="bi bi-calendar-event me-2"></i>Breeding History</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-activity-tab" data-bs-toggle="pill" data-bs-target="#pills-activity" type="button" role="tab"><i class="bi bi-journal-text me-2"></i>Activity Logs</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-breeding" role="tabpanel">
                    <div class="card profile-card">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-4">Serving Date</th>
                                        <th>Boar</th>
                                        <th class="text-center">Born</th>
                                        <th class="text-end pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($servings->num_rows === 0): ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">No breeding history recorded.</td></tr>
                                    <?php else: ?>
                                        <?php while ($row = $servings->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?= date('d M Y', strtotime($row['serving_date'])) ?></td>
                                            <td><i class="bi bi-gender-male text-primary me-2"></i><?= htmlspecialchars($row['boar_name']) ?: '—' ?></td>
                                            <td class="text-center">
                                                <?php if ($row['farrowing_id']): ?>
                                                    <span class="text-success fw-bold"><?= $row['piglets_alive'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($row['piglets_weaned']): ?>
                                                    <span class="badge bg-success-subtle text-success">Weaned</span>
                                                <?php elseif ($row['farrowing_id']): ?>
                                                    <span class="badge bg-info-subtle text-info">Nursing</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-activity" role="tabpanel">
                    <div class="card profile-card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr class="small text-uppercase">
                                            <th class="ps-4">Date</th>
                                            <th>Type</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($activities->num_rows === 0): ?>
                                            <tr><td colspan="3" class="text-center py-5 text-muted">No activities recorded for this sow.</td></tr>
                                        <?php else: ?>
                                            <?php while ($act = $activities->fetch_assoc()): 
                                                $actType = strtolower($act['activity_type']);
                                                $dotColor = 'bg-primary';
                                                if (str_contains($actType, 'med') || str_contains($actType, 'vax')) $dotColor = 'bg-danger';
                                                if (str_contains($actType, 'heat')) $dotColor = 'bg-warning';
                                                if (str_contains($actType, 'feed')) $dotColor = 'bg-success';
                                            ?>
                                            <tr>
                                                <td class="ps-4"><?= date('d M Y', strtotime($act['activity_date'])) ?></td>
                                                <td>
                                                    <span class="activity-dot <?= $dotColor ?>"></span>
                                                    <span class="fw-bold"><?= htmlspecialchars($act['activity_type']) ?></span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= htmlspecialchars($act['notes']) ?: '—' ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center py-3">
                            <a href="../activities/add.php" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-plus-circle me-1"></i> Record New Activity
                            </a>
                        </div>
                    </div>
                </div>
            </div> </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>