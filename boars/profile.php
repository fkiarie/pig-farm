<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) { header('Location: list.php'); exit; }

/* Fetch Boar Details */
$boarResult = $conn->query("SELECT * FROM boars WHERE id = $id LIMIT 1");
if ($boarResult->num_rows === 0) { header('Location: list.php'); exit; }
$boar = $boarResult->fetch_assoc();

/* Statistics */
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_servings,
        COUNT(DISTINCT sow_id) as unique_sows
    FROM servings WHERE boar_id = $id
")->fetch_assoc();

$farrowing_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT f.id) as total_farrowings,
        COALESCE(SUM(f.piglets_alive), 0) as total_piglets
    FROM servings s
    LEFT JOIN farrowings f ON f.serving_id = s.id
    WHERE s.boar_id = $id
")->fetch_assoc();

/* Pagination */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;
$totalRecords = $conn->query("SELECT COUNT(*) as total FROM servings WHERE boar_id = $id")->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

/* Serving History */
$servings = $conn->query("
    SELECT s.*, sw.tag_no AS sow_tag, sw.status as sow_status, f.id as farrowing_id, f.piglets_alive
    FROM servings s
    JOIN sows sw ON sw.id = s.sow_id
    LEFT JOIN farrowings f ON f.serving_id = s.id
    WHERE s.boar_id = $id
    ORDER BY s.serving_date DESC
    LIMIT $perPage OFFSET $offset
");
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
            <h1 class="h3 mb-0"><i class="bi bi-gender-male me-2"></i>Boar Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="list.php">Boars</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($boar['name']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="btn-group shadow-sm">
            <a href="edit.php?id=<?= $boar['id'] ?>" class="btn btn-white border"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="list.php" class="btn btn-white border"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card profile-card h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-4 rounded-circle bg-light mb-3 text-primary">
                            <i class="bi bi-gender-male fs-1"></i>
                        </div>
                        <h2 class="h4 mb-1"><?= htmlspecialchars($boar['name']) ?></h2>
                        <?php
                            $statusClass = match($boar['status']) {
                                'Active' => 'bg-soft-success',
                                'Resting' => 'bg-soft-warning',
                                'Sold' => 'bg-soft-secondary',
                                'Inactive' => 'bg-soft-danger',
                                default => 'bg-light text-dark'
                            };
                        ?>
                        <span class="badge <?= $statusClass ?> fs-6 px-3 py-2 rounded-pill">
                            <i class="bi bi-dot"></i> <?= $boar['status'] ?>
                        </span>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted small">Breed</span>
                            <span class="fw-bold"><?= htmlspecialchars($boar['breed']) ?: '—' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted small">Added to Farm</span>
                            <span class="fw-bold"><?= date('d M Y', strtotime($boar['created_at'])) ?></span>
                        </li>
                    </ul>

                    <?php if ($boar['notes']): ?>
                    <div class="mt-4 p-3 bg-light rounded border-start border-primary border-4">
                        <small class="text-muted d-block mb-1 fw-bold text-uppercase">Breeder Notes</small>
                        <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($boar['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Total Servings</small>
                        <h3><?= $stats['total_servings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Unique Sows</small>
                        <h3><?= $stats['unique_sows'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Farrowings</small>
                        <h3><?= $farrowing_stats['total_farrowings'] ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <small>Total Piglets</small>
                        <h3><?= $farrowing_stats['total_piglets'] ?></h3>
                    </div>
                </div>
            </div>

            <div class="card profile-card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-muted"><i class="bi bi-calendar-check me-2"></i>Serving History</h6>
                </div>
                <div class="table-responsive">
                    <table class="table history-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sow</th>
                                <th class="d-none d-md-table-cell">Method</th>
                                <th class="text-end">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($servings->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted small">No servings recorded.</td></tr>
                            <?php else: ?>
                                <?php while ($row = $servings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold small"><?= date('d M Y', strtotime($row['serving_date'])) ?></div>
                                        <small class="text-muted"><?= $row['method'] ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-gender-female text-danger me-2"></i>
                                            <span class="fw-bold"><?= htmlspecialchars($row['sow_tag']) ?></span>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-light text-dark border"><?= $row['method'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($row['farrowing_id']): ?>
                                            <span class="badge bg-soft-success border border-success border-opacity-10">
                                                <i class="bi bi-check-circle-fill me-1"></i><?= $row['piglets_alive'] ?> Piglets
                                            </span>
                                        <?php elseif ($row['sow_status'] === 'Pregnant'): ?>
                                            <span class="badge bg-soft-warning border border-warning border-opacity-10">Pregnant</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-secondary border border-secondary border-opacity-10">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
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