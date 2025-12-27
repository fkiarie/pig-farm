<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Weanings with Statistics
|--------------------------------------------------------------------------
*/

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_weanings,
        SUM(piglets_weaned) as total_weaned,
        ROUND(AVG(piglets_weaned), 1) as avg_weaned,
        ROUND(AVG(DATEDIFF(w.weaning_date, f.farrowing_date)), 1) as avg_nursing_days
    FROM weanings w
    JOIN farrowings f ON w.farrowing_id = f.id
")->fetch_assoc();

// Fetch all weanings
$sql = "
SELECT w.*, 
       s.tag_no AS sow_tag,
       s.breed AS sow_breed,
       f.farrowing_date,
       f.piglets_alive
FROM weanings w
JOIN sows s ON w.sow_id = s.id
JOIN farrowings f ON w.farrowing_id = f.id
ORDER BY w.weaning_date DESC
";
$result = $conn->query($sql);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐷</span> Weaning Records
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            <span class="d-none d-sm-inline">+ Record New Weaning</span>
            <span class="d-inline d-sm-none">+ Add</span>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_weanings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📋</span> Total Weanings
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_weaned'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐽</span> Piglets Weaned
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['avg_weaned'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📊</span> Avg per Litter
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['avg_nursing_days'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📅</span> Avg Nursing Days
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-lg-4">
                <label for="searchWeaning" class="form-label">Search Weanings</label>
                <input type="text" 
                       class="form-control" 
                       id="searchWeaning" 
                       placeholder="Search by sow tag...">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="filterMonth" class="form-label">Filter by Month</label>
                <input type="month" 
                       class="form-control" 
                       id="filterMonth">
            </div>
            <div class="col-12 col-md-2 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Weanings Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Weaning Records</span>
        <span class="badge bg-secondary" id="recordCount"><?= $result->num_rows ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="weaningsTable">
            <thead>
                <tr>
                    <th>Weaning Date</th>
                    <th>
                        <span class="emoji-icon">🐷</span> Sow
                    </th>
                    <th class="d-none d-lg-table-cell">Farrowing Date</th>
                    <th class="d-none d-md-table-cell">Nursing Period</th>
                    <th>Piglets Weaned</th>
                    <th class="d-none d-xl-table-cell">Survival Rate</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr class="no-results">
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🐷</span>
                                <h5>No weaning records found</h5>
                                <p class="mb-3">Start by recording your first weaning event.</p>
                                <a href="add.php" class="btn btn-success">+ Record First Weaning</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Calculate nursing period
                        $farrowingDate = new DateTime($row['farrowing_date']);
                        $weaningDate = new DateTime($row['weaning_date']);
                        $nursingDays = $farrowingDate->diff($weaningDate)->days;
                        
                        // Calculate survival rate (weaned vs born alive)
                        $survivalRate = $row['piglets_alive'] > 0 
                            ? round(($row['piglets_weaned'] / $row['piglets_alive']) * 100, 1) 
                            : 0;
                        
                        // Determine nursing period status
                        $nursingStatus = $nursingDays < 21 ? 'early' : 
                                       ($nursingDays > 28 ? 'extended' : 'optimal');
                        
                        // Badge colors
                        $nursingBadge = $nursingStatus === 'optimal' ? 'success' : 
                                      ($nursingStatus === 'early' ? 'warning' : 'info');
                        ?>
                        <tr class="weaning-row" 
                            data-sow="<?= strtolower(htmlspecialchars($row['sow_tag'])) ?>"
                            data-date="<?= date('Y-m', strtotime($row['weaning_date'])) ?>">
                            
                            <td>
                                <div>
                                    <strong class="d-block"><?= date('d M Y', strtotime($row['weaning_date'])) ?></strong>
                                    <small class="text-muted">
                                        <?php
                                        $today = new DateTime();
                                        $daysSince = $today->diff($weaningDate)->days;
                                        echo $daysSince . ' days ago';
                                        ?>
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="emoji-icon me-2">🐷</span>
                                    <div>
                                        <strong class="d-block"><?= htmlspecialchars($row['sow_tag']) ?></strong>
                                        <small class="text-muted d-lg-none">
                                            Born: <?= date('d M Y', strtotime($row['farrowing_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="d-none d-lg-table-cell">
                                <div>
                                    <span class="d-block"><?= date('d M Y', strtotime($row['farrowing_date'])) ?></span>
                                    <small class="text-muted">
                                        Farrowing date
                                    </small>
                                </div>
                            </td>
                            
                            <td class="d-none d-md-table-cell">
                                <div>
                                    <span class="badge bg-<?= $nursingBadge ?>" style="font-size: 0.9rem;">
                                        <?= $nursingDays ?> days
                                    </span>
                                    <small class="d-block text-muted mt-1">
                                        <?php if ($nursingStatus === 'optimal'): ?>
                                            ✓ Optimal period
                                        <?php elseif ($nursingStatus === 'early'): ?>
                                            ⚠️ Early weaning
                                        <?php else: ?>
                                            ℹ️ Extended nursing
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <div>
                                    <div class="d-flex align-items-center">
                                        <span class="fs-4 fw-bold text-success me-2"><?= $row['piglets_weaned'] ?></span>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-success" style="font-size: 0.75rem;">
                                                <?= $survivalRate ?>% survival
                                            </span>
                                            <small class="text-muted mt-1 d-md-none">
                                                <?= $nursingDays ?> days old
                                            </small>
                                        </div>
                                    </div>
                                    <small class="text-muted d-xl-none d-block mt-1">
                                        of <?= $row['piglets_alive'] ?> born alive
                                    </small>
                                </div>
                            </td>
                            
                            <td class="d-none d-xl-table-cell">
                                <div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: <?= $survivalRate ?>%"
                                             aria-valuenow="<?= $survivalRate ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $survivalRate ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <?= $row['piglets_weaned'] ?> of <?= $row['piglets_alive'] ?> survived
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <div class="btn-group d-flex justify-content-end" role="group">
                                    <a href="view.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip"
                                       title="View weaning details">
                                       <span class="d-none d-lg-inline">View</span>
                                       <span class="d-inline d-lg-none">👁️</span>
                                    </a>
                                    <a href="edit.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip"
                                       title="Edit weaning record">
                                       <span class="d-none d-lg-inline">Edit</span>
                                       <span class="d-inline d-lg-none">✏️</span>
                                    </a>
                                    <a href="delete.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       data-bs-toggle="tooltip"
                                       title="Delete record"
                                       data-confirm-delete="Are you sure you want to delete this weaning record for sow '<?= htmlspecialchars($row['sow_tag']) ?>'?">
                                       <span class="d-none d-lg-inline">Delete</span>
                                       <span class="d-inline d-lg-none">🗑️</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Nursing Period Guide -->
<div class="card mt-4">
    <div class="card-body">
        <h6 class="card-title">
            <span class="emoji-icon">📊</span> Nursing Period Guide
        </h6>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning me-2" style="font-size: 1.5rem;">&lt;21</span>
                    <div>
                        <strong class="d-block">Early Weaning</strong>
                        <small class="text-muted">Less than 21 days nursing</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2" style="font-size: 1.5rem;">21-28</span>
                    <div>
                        <strong class="d-block">Optimal Period</strong>
                        <small class="text-muted">21-28 days nursing (ideal)</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-info me-2" style="font-size: 1.5rem;">&gt;28</span>
                    <div>
                        <strong class="d-block">Extended Nursing</strong>
                        <small class="text-muted">More than 28 days nursing</small>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <small class="text-muted">
                    <strong>Optimal Weaning Age:</strong> 21-28 days for best piglet development
                </small>
            </div>
            <div class="col-12 col-md-6">
                <small class="text-muted">
                    <strong>Survival Rate:</strong> Percentage of born alive piglets that survived to weaning
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Search/Filter JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchWeaning');
    const monthFilter = document.getElementById('filterMonth');
    const tableRows = document.querySelectorAll('.weaning-row');
    const recordCount = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const monthValue = monthFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const sow = row.dataset.sow;
            const date = row.dataset.date;

            const matchesSearch = sow.includes(searchTerm);
            const matchesMonth = !monthValue || date === monthValue;

            if (matchesSearch && matchesMonth) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update record count
        if (recordCount) {
            recordCount.textContent = visibleCount + ' record' + (visibleCount !== 1 ? 's' : '');
        }

        // Show/hide no results message
        const noResultsRow = document.querySelector('.no-results');
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!noResultsRow) {
                const tbody = document.querySelector('#weaningsTable tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
                            <h5>No weanings match your search</h5>
                            <p class="mb-0">Try adjusting your filters or search term.</p>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        } else if (noResultsRow && visibleCount > 0) {
            noResultsRow.remove();
        }
    }

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

    if (monthFilter) {
        monthFilter.addEventListener('change', filterTable);
    }

    // Reset filters function
    window.resetFilters = function() {
        searchInput.value = '';
        monthFilter.value = '';
        filterTable();
    };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>