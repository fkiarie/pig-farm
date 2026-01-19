<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/* Statistics */
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_farrowings,
        SUM(total_born) as total_piglets,
        SUM(piglets_alive) as total_alive,
        SUM(stillbirths) as total_stillbirths,
        ROUND(AVG(piglets_alive), 1) as avg_litter_size
    FROM farrowings
")->fetch_assoc();

$survival_rate = $stats['total_piglets'] > 0 
    ? round(($stats['total_alive'] / $stats['total_piglets']) * 100, 1) 
    : 0;

/* Fetch Records */
$sql = "SELECT f.*, s.tag_no AS sow_tag, sv.serving_date FROM farrowings f
        JOIN sows s ON f.sow_id = s.id
        JOIN servings sv ON f.serving_id = sv.id
        ORDER BY f.farrowing_date DESC";
$result = $conn->query($sql);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .stat-box { border-radius: 10px; padding: 15px; background: #f8f9fa; border: 1px solid #eee; text-align: center; height: 100%; }
    .stat-box h3 { font-weight: 700; color: #198754; margin-bottom: 5px; }
    .stat-box small { color: #6c757d; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; font-weight: 600; }
    
    /* Performance Heatmap Colors */
    .bg-soft-excellent { background-color: #d1e7dd; color: #0a3622; } /* 12+ Green */
    .bg-soft-good { background-color: #e2e3e5; color: #41464b; }      /* 10-11 Gray */
    .bg-soft-average { background-color: #fff3cd; color: #664d03; }   /* <10 Yellow */
    
    .table thead th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; border-top: none; }
    .gestation-tag { font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; background: #f0f2f4; color: #495057; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-egg-fried me-2 text-success"></i>Farrowing Records</h1>
            <p class="text-muted small mb-0">Monitor birth performance and litter survival rates.</p>
        </div>
        <a href="add.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Record New Farrowing</span><span class="d-inline d-sm-none">Add</span>
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="stat-box">
                <small>Total Births</small>
                <h3><?= $stats['total_farrowings'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="stat-box">
                <small>Piglets Born</small>
                <h3 class="text-primary"><?= $stats['total_piglets'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="stat-box">
                <small>Born Alive</small>
                <h3><?= $stats['total_alive'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-6 col-lg">
            <div class="stat-box">
                <small>Avg Litter</small>
                <h3 class="text-info"><?= $stats['avg_litter_size'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-6 col-lg">
            <div class="stat-box">
                <small>Survival Rate</small>
                <h3 class="text-success"><?= $survival_rate ?>%</h3>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-bold">Search Sow</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchFarrowing" class="form-control border-start-0" placeholder="Enter sow tag number...">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold">Month Filter</label>
                    <input type="month" id="filterMonth" class="form-control">
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <button onclick="resetFilters()" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="farrowingsTable">
                <thead>
                    <tr>
                        <th class="ps-4">Farrowing Date</th>
                        <th>Sow</th>
                        <th class="d-none d-lg-table-cell">Gestation</th>
                        <th>Total Born</th>
                        <th>Alive</th>
                        <th class="d-none d-xl-table-cell">Stillbirths</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No farrowing records found.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $servingDate = new DateTime($row['serving_date']);
                            $farrowDate = new DateTime($row['farrowing_date']);
                            $gestation = $servingDate->diff($farrowDate)->days;
                            $survival = $row['total_born'] > 0 ? round(($row['piglets_alive'] / $row['total_born']) * 100) : 0;
                            
                            // Performance Styling
                            $perfClass = $row['piglets_alive'] >= 12 ? 'bg-soft-excellent' : 
                                         ($row['piglets_alive'] >= 10 ? 'bg-soft-good' : 'bg-soft-average');
                        ?>
                        <tr class="farrowing-row" 
                            data-sow="<?= strtolower(htmlspecialchars($row['sow_tag'])) ?>"
                            data-date="<?= date('Y-m', strtotime($row['farrowing_date'])) ?>">
                            
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['farrowing_date'])) ?></div>
                                <small class="text-muted"><?= $farrowDate->diff(new DateTime())->days ?> days ago</small>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-light rounded-circle me-2 d-none d-sm-block">
                                        <i class="bi bi-gender-female text-danger"></i>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($row['sow_tag']) ?></span>
                                </div>
                            </td>

                            <td class="d-none d-lg-table-cell">
                                <span class="gestation-tag"><i class="bi bi-clock-history me-1"></i><?= $gestation ?> days</span>
                            </td>

                            <td>
                                <span class="fw-bold fs-6"><?= $row['total_born'] ?></span>
                            </td>

                            <td>
                                <span class="badge <?= $perfClass ?> px-3 py-2 rounded-pill shadow-sm">
                                    <i class="bi bi-heart-fill me-1" style="font-size: 0.7rem;"></i> <?= $row['piglets_alive'] ?>
                                </span>
                                <div class="small text-muted mt-1 ps-1" style="font-size: 0.7rem;"><?= $survival ?>% survival</div>
                            </td>

                            <td class="d-none d-xl-table-cell">
                                <?php if ($row['stillbirths'] > 0): ?>
                                    <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= $row['stillbirths'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted fw-light">0</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border text-danger" 
                                       onclick="return confirm('Delete this record for Sow <?= $row['sow_tag'] ?>?')" title="Delete"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12 col-md-6">
            <div class="card h-100 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Survival & Gestation</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bi bi-dot"></i> <strong>Gestation:</strong> Healthy range is typically 113-116 days.</li>
                        <li><i class="bi bi-dot"></i> <strong>Survival:</strong> Calculated as (Alive / Total Born) × 100.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mt-3 mt-md-0">
            <div class="card h-100 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2"></i>Performance Scale (Piglets Alive)</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-soft-excellent rounded-pill px-3">Excellent (12+)</span>
                        <span class="badge bg-soft-good rounded-pill px-3">Good (10-11)</span>
                        <span class="badge bg-soft-average rounded-pill px-3">Avg (<10)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchFarrowing');
    const monthFilter = document.getElementById('filterMonth');
    const rows = document.querySelectorAll('.farrowing-row');

    function filter() {
        const query = searchInput.value.toLowerCase();
        const month = monthFilter.value;

        rows.forEach(r => {
            const matchesSearch = !query || r.dataset.sow.includes(query);
            const matchesMonth = !month || r.dataset.date === month;
            r.style.display = (matchesSearch && matchesMonth) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filter);
    monthFilter.addEventListener('change', filter);
    window.resetFilters = () => { searchInput.value = ''; monthFilter.value = ''; filter(); };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>