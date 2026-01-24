<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/
$stats = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(status = 'Active') AS active,
        SUM(status = 'Pregnant') AS pregnant,
        SUM(status = 'Lactating') AS lactating,
        SUM(status = 'Dry') AS dry
    FROM sows
    WHERE status != 'Culled'
")->fetch_assoc();

$totalRows = $conn->query("SELECT COUNT(*) AS total FROM sows WHERE status != 'Culled'")->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

$result = $conn->query("
    SELECT id, tag_no, breed, status, date_of_birth, created_at
    FROM sows
    WHERE status != 'Culled'
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .stat-card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-info { background-color: #cff4fc; color: #055160; }
    .bg-soft-secondary { background-color: #f0f2f4; color: #495057; }
    .bg-soft-primary { background-color: #e7f1ff; color: #0d6efd; }
    .table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; cursor: pointer; }
    .table thead th:hover { color: #0d6efd; }
</style>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h3"><i class="bi bi-gender-female me-2"></i>Sows Management</h1>
    <a href="add.php" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Add New Sow
    </a>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total', $stats['total'], 'bi-grid-fill', 'primary'],
        ['Active', $stats['active'], 'bi-check-circle-fill', 'success'],
        ['Pregnant', $stats['pregnant'], 'bi-heart-pulse-fill', 'warning'],
        ['Lactating', $stats['lactating'], 'bi-droplet-fill', 'info'],
        ['Dry', $stats['dry'], 'bi-moon-stars-fill', 'secondary'],
    ];
    foreach ($cards as [$label, $count, $icon, $color]):
    ?>
    <div class="col-6 col-lg">
        <div class="card stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="p-2 bg-soft-<?= $color ?> rounded-3 me-3">
                        <i class="bi <?= $icon ?> fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $count ?? 0 ?></h4>
                        <small class="text-muted"><?= $label ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchSow" class="form-control border-start-0" placeholder="Search by Tag Number or Breed...">
                </div>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-filter"></i></span>
                    <select id="filterStatus" class="form-select border-start-0">
                        <option value="">All Statuses</option>
                        <option>Active</option>
                        <option>Pregnant</option>
                        <option>Lactating</option>
                        <option>Dry</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="sowsTable">
            <thead class="bg-light">
                <tr>
                    <th onclick="sortTable(0)">Tag <i class="bi bi-arrow-down-up ms-1 small"></i></th>
                    <th class="d-none d-md-table-cell" onclick="sortTable(1)">Breed <i class="bi bi-arrow-down-up ms-1 small"></i></th>
                    <th onclick="sortTable(2)">Status <i class="bi bi-arrow-down-up ms-1 small"></i></th>
                    <th class="d-none d-lg-table-cell" onclick="sortTable(3)">Date of Birth <i class="bi bi-arrow-down-up ms-1 small"></i></th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted">No sows found in the database.</td></tr>
            <?php endif; ?>

            <?php while ($row = $result->fetch_assoc()):
                $status = $row['status'];
                $badgeClass = match($status) {
                    'Active' => 'bg-soft-success',
                    'Pregnant' => 'bg-soft-warning',
                    'Lactating' => 'bg-soft-info',
                    'Dry' => 'bg-soft-secondary',
                    default => 'bg-light text-dark'
                };
            ?>
            <tr class="sow-row" 
                data-tag="<?= strtolower($row['tag_no']) ?>" 
                data-breed="<?= strtolower($row['breed'] ?? '') ?>" 
                data-status="<?= $status ?>">
                
                <td>
                    <div class="d-flex align-items-center">
                        <div class="p-2 bg-light rounded-circle me-3"><i class="bi bi-hash"></i></div>
                        <span class="fw-bold"><?= htmlspecialchars($row['tag_no']) ?></span>
                    </div>
                </td>
                <td class="d-none d-md-table-cell"><?= $row['breed'] ?: '—' ?></td>
                <td>
                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                        <i class="bi bi-dot"></i> <?= $status ?>
                    </span>
                </td>
                <td class="d-none d-lg-table-cell text-muted">
                    <?= $row['date_of_birth'] ? date('d M Y', strtotime($row['date_of_birth'])) : '—'; ?>
                </td>
                <td class="text-end">
                    <div class="btn-group">
                        <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-light-subtle shadow-sm" title="View Profile">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-light-subtle shadow-sm" title="Edit Info">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="cull.php?id=<?= $row['id'] ?>" 
                           class="btn btn-sm btn-outline-danger border-light-subtle shadow-sm" 
                           onclick="return confirm('Cull this sow? This will move her to culled records.')" title="Cull Sow">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link shadow-sm" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link shadow-sm" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link shadow-sm" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
/**
 * Filtering Logic
 */
const search = document.getElementById('searchSow');
const filter = document.getElementById('filterStatus');
const rows = document.querySelectorAll('.sow-row');

function filterTable() {
    const s = search.value.toLowerCase();
    const f = filter.value;

    rows.forEach(r => {
        const ok = (r.dataset.tag.includes(s) || r.dataset.breed.includes(s)) && (!f || r.dataset.status === f);
        r.style.display = ok ? '' : 'none';
    });
}

// Event listeners for searching and filtering
if(search) search.oninput = filterTable;
if(filter) filter.onchange = filterTable;

function resetFilters() {
    search.value = '';
    filter.value = '';
    filterTable();
}

/**
 * Basic Table Sort
 */
function sortTable(n) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("sowsTable");
    switching = true;
    dir = "asc"; 
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount ++;      
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>