<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/* Statistics */
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Resting' THEN 1 ELSE 0 END) as resting,
        SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END) as sold
    FROM boars
")->fetch_assoc();

/* Fetch All Boars */
$result = $conn->query("
    SELECT id, name, breed, status, created_at
    FROM boars
    ORDER BY 
        CASE status
            WHEN 'Active' THEN 1
            WHEN 'Resting' THEN 2
            WHEN 'Sold' THEN 3
            ELSE 4
        END,
        created_at DESC
");
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .stat-card:hover { transform: translateY(-3px); }
    .bg-soft-success { background-color: #e1f6e1; color: #198754; }
    .bg-soft-warning { background-color: #fff3cd; color: #856404; }
    .bg-soft-secondary { background-color: #f0f2f4; color: #495057; }
    .bg-soft-primary { background-color: #e7f1ff; color: #0d6efd; }
    .bg-soft-danger { background-color: #f8d7da; color: #842029; }
    .table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; border-top: none; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-gender-male me-2"></i>Boars Management</h1>
            <p class="text-muted small mb-0">Manage breeding males and track their availability.</p>
        </div>
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Add New Boar</span><span class="d-inline d-sm-none">Add</span>
        </a>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['Total Boars', $stats['total'], 'bi-grid-fill', 'primary'],
            ['Active', $stats['active'], 'bi-check-circle-fill', 'success'],
            ['Resting', $stats['resting'], 'bi-moon-stars-fill', 'warning'],
            ['Sold/Inactive', ($stats['total'] - ($stats['active'] + $stats['resting'])), 'bi- CASH-stack', 'secondary'],
        ];
        foreach ($cards as [$label, $count, $icon, $color]):
        ?>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-soft-<?= $color ?> rounded-3 me-3">
                            <i class="bi <?= $icon ?> fs-4"></i>
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
                        <input type="text" id="searchBoar" class="form-control border-start-0" placeholder="Search by name or breed...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-filter"></i></span>
                        <select id="filterStatus" class="form-select border-start-0">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Resting">Resting</option>
                            <option value="Sold">Sold</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-muted">Boar Inventory</h6>
            <span class="badge bg-light text-dark border" id="recordCount"><?= $result->num_rows ?> Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="boarsTable">
                <thead>
                    <tr>
                        <th>Name / Tag</th>
                        <th class="d-none d-md-table-cell">Breed</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell text-center">Added Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="bi bi-inbox text-muted fs-1 d-block mb-3"></i>
                            <h5 class="text-muted">No boars registered yet</h5>
                            <a href="add.php" class="btn btn-sm btn-primary mt-2">+ Add First Boar</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $badge = match($row['status']) {
                            'Active' => 'bg-soft-success',
                            'Resting' => 'bg-soft-warning',
                            'Sold' => 'bg-soft-secondary',
                            'Inactive' => 'bg-soft-danger',
                            default => 'bg-light text-dark'
                        };
                    ?>
                    <tr class="boar-row" 
                        data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>"
                        data-breed="<?= strtolower($row['breed'] ?: '') ?>"
                        data-status="<?= $row['status'] ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-light rounded-circle me-3 text-primary">
                                    <i class="bi bi-gender-male"></i>
                                </div>
                                <div>
                                    <span class="fw-bold d-block"><?= htmlspecialchars($row['name']) ?></span>
                                    <small class="text-muted d-md-none"><?= $row['breed'] ?: '—' ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="text-secondary"><?= htmlspecialchars($row['breed']) ?: '—' ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $badge ?> rounded-pill px-3">
                                <i class="bi bi-dot"></i> <?= $row['status'] ?>
                            </span>
                        </td>
                        <td class="d-none d-lg-table-cell text-center text-muted">
                            <?= date('d M Y', strtotime($row['created_at'])) ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-light-subtle shadow-sm" title="View Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-light-subtle shadow-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($row['status'] !== 'Inactive' && $row['status'] !== 'Sold'): ?>
                                    <a href="soft_delete.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger border-light-subtle shadow-sm"
                                       onclick="return confirm('Deactivate this boar?')">
                                        <i class="bi bi-slash-circle"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchBoar');
    const statusFilter = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.boar-row');
    const recordCount = document.getElementById('recordCount');

    function performFilter() {
        const query = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        let count = 0;

        rows.forEach(row => {
            const matchesSearch = row.dataset.name.includes(query) || row.dataset.breed.includes(query);
            const matchesStatus = !status || row.dataset.status === status;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        recordCount.textContent = count + ' Record' + (count !== 1 ? 's' : '');
    }

    searchInput.addEventListener('input', performFilter);
    statusFilter.addEventListener('change', performFilter);

    window.resetFilters = function() {
        searchInput.value = '';
        statusFilter.value = '';
        performFilter();
    };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>