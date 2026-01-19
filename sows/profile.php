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

/* Pagination for History */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;
$totalRecords = $conn->query("SELECT COUNT(*) as total FROM servings WHERE sow_id = $id")->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

/* Fetch History */
$servings = $conn->query("
    SELECT s.*, b.name as boar_name, f.id as farrowing_id, f.piglets_alive, f.farrowing_date, w.piglets_weaned
    FROM servings s
    LEFT JOIN boars b ON s.boar_id = b.id
    LEFT JOIN farrowings f ON f.serving_id = s.id
    LEFT JOIN weanings w ON w.farrowing_id = f.id
    WHERE s.sow_id = $id
    ORDER BY s.serving_date DESC
    LIMIT $perPage OFFSET $offset
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
    .stat-box h3 { font-weight: 700; color: #0d6efd; margin-bottom: 5px; }
    .stat-box small { color: #6c757d; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; font-weight: 600; }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-info { background-color: #cff4fc; color: #055160; }
    .bg-soft-secondary { background-color: #f0f2f4; color: #495057; }
    .history-table thead { background-color: #f8f9fa; font-size: 0.8rem; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-person-badge me-2"></i>Sow Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="list.php">Sows</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($sow['tag_no']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="btn-group shadow-sm">
            <a href="edit.php?id=<?= $sow['id'] ?>" class="btn btn-white border"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="list.php" class="btn btn-white border"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card profile-card h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-4 rounded-circle bg-light mb-3">
                            <i class="bi bi-piggy-bank text-primary fs-1"></i>
                        </div>
                        <h2 class="h4 mb-1"><?= htmlspecialchars($sow['tag_no']) ?></h2>
                        <?php
                            $statusClass = match($sow['status']) {
                                'Active' => 'bg-soft-success',
                                'Pregnant' => 'bg-soft-warning',
                                'Lactating' => 'bg-soft-info',
                                default => 'bg-soft-secondary'
                            };
                        ?>
                        <span class="badge <?= $statusClass ?> fs-6 px-3 py-2 rounded-pill">
                            <i class="bi bi-dot"></i> <?= $sow['status'] ?>
                        </span>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted small">Breed</span>
                            <span class="fw-bold"><?= htmlspecialchars($sow['breed']) ?: '—' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted small">Current Age</span>
                            <span class="fw-bold"><?= $age_display ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted small">Date Added</span>
                            <span class="fw-bold"><?= date('d M Y', strtotime($sow['created_at'])) ?></span>
                        </li>
                    </ul>

                    <?php if (!empty($sow['notes'])): ?>
                    <div class="mt-4 p-3 bg-light rounded border-start border-primary border-4">
                        <small class="text-muted d-block mb-1 fw-bold text-uppercase">Health Notes</small>
                        <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($sow['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Servings</small>
                        <h3><?= $stats['total_servings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Farrowings</small>
                        <h3><?= $stats['total_farrowings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Avg Litter</small>
                        <h3><?= $avgLitterSize ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Total Weaned</small>
                        <h3><?= $stats['total_weaned'] ?></h3>
                    </div>
                </div>
            </div>

            <div class="card profile-card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i>Breeding History</h6>
                    <span class="badge bg-light text-dark"><?= $totalRecords ?> Records</span>
                </div>
                <div class="table-responsive">
                    <table class="table history-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Serving Date</th>
                                <th>Boar</th>
                                <th class="d-none d-md-table-cell text-center">Outcome</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($servings->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted small">No breeding history recorded.</td></tr>
                            <?php else: ?>
                                <?php while ($row = $servings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= date('d M Y', strtotime($row['serving_date'])) ?></div>
                                        <small class="text-muted d-md-none"><?= $row['boar_name'] ?></small>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-gender-male text-primary me-2"></i>
                                            <?= htmlspecialchars($row['boar_name']) ?: '—' ?>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php if ($row['farrowing_id']): ?>
                                            <span class="text-success small fw-bold"><i class="bi bi-check-circle"></i> <?= $row['piglets_alive'] ?> Born</span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($row['piglets_weaned']): ?>
                                            <span class="badge bg-soft-success border border-success border-opacity-10">Weaned</span>
                                        <?php elseif ($row['farrowing_id']): ?>
                                            <span class="badge bg-soft-info border border-info border-opacity-10">Nursing</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-warning border border-warning border-opacity-10">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top-0">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?id=<?= $id ?>&page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item disabled"><span class="page-link text-dark"><?= $page ?> / <?= $totalPages ?></span></li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?id=<?= $id ?>&page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>