<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/* Statistics */
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_weanings,
        SUM(piglets_weaned) as total_weaned,
        ROUND(AVG(piglets_weaned), 1) as avg_weaned,
        ROUND(AVG(DATEDIFF(w.weaning_date, f.farrowing_date)), 1) as avg_nursing_days
    FROM weanings w
    JOIN farrowings f ON w.farrowing_id = f.id
")->fetch_assoc();

/* Fetch Records */
$sql = "SELECT w.*, s.tag_no AS sow_tag, s.breed AS sow_breed, f.farrowing_date, f.piglets_alive
        FROM weanings w
        JOIN sows s ON w.sow_id = s.id
        JOIN farrowings f ON w.farrowing_id = f.id
        ORDER BY w.weaning_date DESC";
$result = $conn->query($sql);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    :root { --glass-bg: rgba(255, 255, 255, 0.9); }
    .card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    .stat-box { border-radius: 10px; padding: 20px; background: #fff; border: 1px solid #edf2f7; text-align: center; transition: transform 0.2s; }
    .stat-box:hover { transform: translateY(-3px); }
    .stat-box h3 { font-weight: 800; color: #2d3748; margin-bottom: 2px; }
    .stat-box small { color: #718096; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 1px; font-weight: 700; }
    
    .survival-track { height: 8px; border-radius: 10px; background: #edf2f7; overflow: hidden; margin-top: 8px; }
    .survival-fill { height: 100%; border-radius: 10px; transition: width 0.6s ease; }
    
    .badge-nursing { font-size: 0.75rem; padding: 5px 10px; border-radius: 6px; font-weight: 600; }
    .nursing-optimal { background-color: #d1e7dd; color: #0f5132; }
    .nursing-alert { background-color: #fff3cd; color: #664d03; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-box-arrow-right text-primary me-2"></i>Weaning Records</h1>
            <p class="text-muted small mb-0">Track litter survival and nursing efficiency across cycles.</p>
        </div>
        <a href="add.php" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-plus-lg me-2"></i>Record New Weaning
        </a>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <small>Total Batches</small>
                <h3><?= $stats['total_weanings'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <small>Piglets Weaned</small>
                <h3 class="text-success"><?= $stats['total_weaned'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <small>Avg per Litter</small>
                <h3 class="text-primary"><?= $stats['avg_weaned'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <small>Avg Nursing</small>
                <h3 class="text-info"><?= $stats['avg_nursing_days'] ?>d</h3>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchWeaning" class="form-control border-start-0" placeholder="Search sow tag...">
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="month" id="filterMonth" class="form-control">
                </div>
                <div class="col-md-3">
                    <button onclick="resetFilters()" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="weaningsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Weaning Date</th>
                        <th>Sow Detail</th>
                        <th class="d-none d-lg-table-cell">Nursing Period</th>
                        <th>Performance</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center py-5">No records found.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $fDate = new DateTime($row['farrowing_date']);
                            $wDate = new DateTime($row['weaning_date']);
                            $days = $fDate->diff($wDate)->days;
                            $survival = $row['piglets_alive'] > 0 ? round(($row['piglets_weaned'] / $row['piglets_alive']) * 100) : 0;
                            $isOptimal = ($days >= 21 && $days <= 28);
                        ?>
                        <tr class="weaning-row" data-sow="<?= strtolower(htmlspecialchars($row['sow_tag'])) ?>" data-date="<?= date('Y-m', strtotime($row['weaning_date'])) ?>">
                            <td class="ps-4">
                                <div class="fw-bold"><?= date('d M Y', strtotime($row['weaning_date'])) ?></div>
                                <small class="text-muted"><?= $wDate->diff(new DateTime())->days ?> days ago</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                        <i class="bi bi-female"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['sow_tag']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['sow_breed']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <span class="badge-nursing <?= $isOptimal ? 'nursing-optimal' : 'nursing-alert' ?>">
                                    <i class="bi <?= $isOptimal ? 'bi-check-circle' : 'bi-exclamation-circle' ?> me-1"></i>
                                    <?= $days ?> Days
                                </span>
                            </td>
                            <td style="min-width: 180px;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-success"><?= $row['piglets_weaned'] ?> Weaned</span>
                                    <small class="text-muted"><?= $survival ?>% Survival</small>
                                </div>
                                <div class="survival-track">
                                    <div class="survival-fill <?= $survival > 90 ? 'bg-success' : ($survival > 70 ? 'bg-primary' : 'bg-warning') ?>" style="width: <?= $survival ?>%"></div>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border"><i class="bi bi-pencil"></i></a>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border text-danger" onclick="return confirm('Delete this record?')"><i class="bi bi-trash"></i></a>
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
    const search = document.getElementById('searchWeaning');
    const filter = document.getElementById('filterMonth');
    const rows = document.querySelectorAll('.weaning-row');

    function applyFilters() {
        const q = search.value.toLowerCase();
        const m = filter.value;
        rows.forEach(r => {
            const matchesSearch = r.dataset.sow.includes(q);
            const matchesMonth = !m || r.dataset.date === m;
            r.style.display = (matchesSearch && matchesMonth) ? '' : 'none';
        });
    }

    search.addEventListener('input', applyFilters);
    filter.addEventListener('change', applyFilters);
    window.resetFilters = () => { search.value = ''; filter.value = ''; applyFilters(); };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>