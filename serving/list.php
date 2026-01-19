<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

/* Pagination Logic */
$limit = 10;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalRows = $conn->query("SELECT COUNT(*) AS total FROM servings")->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* Fetch Servings */
$query = "
    SELECT 
        sv.id AS serving_id, sv.serving_date, sv.expected_farrowing, sv.method,
        s.id AS sow_id, s.tag_no AS sow_tag, s.status AS sow_status,
        b.name AS boar_name
    FROM servings sv
    JOIN sows s ON sv.sow_id = s.id
    JOIN boars b ON sv.boar_id = b.id
    ORDER BY sv.serving_date DESC
    LIMIT $limit OFFSET $offset
";
$servings = $conn->query($query);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-info { background-color: #cff4fc; color: #055160; }
    .bg-soft-danger { background-color: #f8d7da; color: #842029; }
    .bg-soft-primary { background-color: #e7f1ff; color: #0d6efd; }
    .table thead th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
    .overdue-pulse { animation: pulse-red 2s infinite; }
    @keyframes pulse-red { 0% { color: #dc3545; } 50% { color: #ff8080; } 100% { color: #dc3545; } }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Breeding Records</h1>
            <p class="text-muted small mb-0">Track sow servings and expected farrowing dates.</p>
        </div>
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Record New Serving</span><span class="d-inline d-sm-none">Record</span>
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchServing" class="form-control border-start-0" placeholder="Sow tag or boar name...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Method</label>
                    <select id="filterMethod" class="form-select">
                        <option value="">All Methods</option>
                        <option value="Natural">Natural</option>
                        <option value="AI">AI</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pregnant">Pregnant</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button onclick="resetFilters()" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="servingsTable">
                <thead>
                    <tr>
                        <th class="ps-4">Serving Date</th>
                        <th>Sow Tag</th>
                        <th class="d-none d-md-table-cell">Boar</th>
                        <th class="d-none d-lg-table-cell">Method</th>
                        <th class="d-none d-xl-table-cell">Due Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($servings->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No breeding records found.</td></tr>
                <?php else: ?>
                    <?php while ($row = $servings->fetch_assoc()): 
                        $today = new DateTime();
                        $expected = new DateTime($row['expected_farrowing']);
                        $daysUntil = $today->diff($expected)->days;
                        $isPast = $today > $expected;
                    ?>
                    <tr class="serving-row"
                        data-sow="<?= strtolower($row['sow_tag']) ?>"
                        data-boar="<?= strtolower($row['boar_name']) ?>"
                        data-method="<?= $row['method'] ?>"
                        data-status="<?= $row['sow_status'] === 'Pregnant' ? 'Pregnant' : 'Completed' ?>">
                        
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['serving_date'])) ?></div>
                            <small class="text-muted d-xl-none">Due: <?= date('d M', strtotime($row['expected_farrowing'])) ?></small>
                        </td>

                        <td>
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-soft-primary rounded-circle me-2 d-none d-sm-block">
                                    <i class="bi bi-gender-female" style="font-size: 0.8rem;"></i>
                                </div>
                                <span class="fw-bold"><?= htmlspecialchars($row['sow_tag']) ?></span>
                            </div>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <span class="text-secondary small"><i class="bi bi-gender-male me-1"></i><?= htmlspecialchars($row['boar_name']) ?></span>
                        </td>

                        <td class="d-none d-lg-table-cell">
                            <?php $mthdCol = $row['method'] === 'Natural' ? 'success' : 'info'; ?>
                            <span class="badge bg-soft-<?= $mthdCol ?> rounded-pill px-3">
                                <?= $row['method'] ?>
                            </span>
                        </td>

                        <td class="d-none d-xl-table-cell">
                            <div class="small fw-bold"><?= date('d M Y', strtotime($row['expected_farrowing'])) ?></div>
                            <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                <small class="<?= $isPast ? 'overdue-pulse fw-bold' : 'text-muted' ?>">
                                    <i class="bi bi-clock me-1"></i><?= $isPast ? 'Overdue' : $daysUntil . ' days left' ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php $stsCol = $row['sow_status'] === 'Pregnant' ? 'warning' : 'success'; ?>
                            <span class="badge bg-soft-<?= $stsCol ?> px-3">
                                <i class="bi bi-dot"></i> <?= $row['sow_status'] ?>
                            </span>
                        </td>

                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="edit.php?id=<?= $row['serving_id'] ?>" class="btn btn-sm btn-outline-secondary border-light-subtle shadow-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/sows/profile.php?id=<?= $row['sow_id'] ?>" class="btn btn-sm btn-outline-primary border-light-subtle shadow-sm" title="View Sow">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                    <a href="<?= BASE_URL ?>/farrowing/list.php?serving_id=<?= $row['serving_id'] ?>" class="btn btn-sm btn-primary shadow-sm ms-1" title="Log Farrowing">
                                        <i class="bi bi-egg-fried"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top-0 py-3">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <li class="page-item disabled"><span class="page-link text-dark">Page <?= $page ?> of <?= $totalPages ?></span></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchServing');
    const method = document.getElementById('filterMethod');
    const status = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.serving-row');

    function filter() {
        const query = search.value.toLowerCase();
        rows.forEach(r => {
            const matchesSearch = !query || r.dataset.sow.includes(query) || r.dataset.boar.includes(query);
            const matchesMethod = !method.value || r.dataset.method === method.value;
            const matchesStatus = !status.value || r.dataset.status === status.value;
            r.style.display = (matchesSearch && matchesMethod && matchesStatus) ? '' : 'none';
        });
    }

    search.oninput = filter;
    method.onchange = filter;
    status.onchange = filter;

    window.resetFilters = () => {
        search.value = method.value = status.value = '';
        filter();
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>