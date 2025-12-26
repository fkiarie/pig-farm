<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Farrowings with Statistics
|--------------------------------------------------------------------------
*/


// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_farrowings,
        SUM(total_born) as total_piglets,
        SUM(piglets_alive) as total_alive,
        SUM(stillbirths) as total_stillbirths,
        ROUND(AVG(piglets_alive), 1) as avg_litter_size
    FROM farrowings
")->fetch_assoc();

// Calculate survival rate
$survival_rate = $stats['total_piglets'] > 0 
    ? round(($stats['total_alive'] / $stats['total_piglets']) * 100, 1) 
    : 0;

// Fetch all farrowings
$sql = "
SELECT f.*, 
       s.tag_no AS sow_tag,
       sv.serving_date,
       sv.method
FROM farrowings f
JOIN sows s ON f.sow_id = s.id
JOIN servings sv ON f.serving_id = sv.id
ORDER BY f.farrowing_date DESC
";
$result = $conn->query($sql);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐣</span> Farrowing Records
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            <span class="d-none d-sm-inline">+ Record New Farrowing</span>
            <span class="d-inline d-sm-none">+ Add</span>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_farrowings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📋</span> Total Births
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_piglets'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐽</span> Total Piglets
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_alive'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">✅</span> Born Alive
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['avg_litter_size'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📊</span> Avg Litter
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $survival_rate ?>%</h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">💚</span> Survival Rate
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
                <label for="searchFarrowing" class="form-label">Search Farrowings</label>
                <input type="text" 
                       class="form-control" 
                       id="searchFarrowing" 
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

<!-- Farrowings Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Farrowing Records</span>
        <span class="badge bg-secondary" id="recordCount"><?= $result->num_rows ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="farrowingsTable">
            <thead>
                <tr>
                    <th>Farrowing Date</th>
                    <th>
                        <span class="emoji-icon">🐷</span> Sow
                    </th>
                    <th class="d-none d-lg-table-cell">Serving Date</th>
                    <th class="d-none d-md-table-cell">Total Born</th>
                    <th>Alive</th>
                    <th class="d-none d-xl-table-cell">Stillbirths</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr class="no-results">
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🐣</span>
                                <h5>No farrowing records found</h5>
                                <p class="mb-3">Start by recording your first farrowing event.</p>
                                <a href="add.php" class="btn btn-success">+ Record First Farrowing</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Calculate gestation period
                        $servingDate = new DateTime($row['serving_date']);
                        $farrowingDate = new DateTime($row['farrowing_date']);
                        $gestationDays = $servingDate->diff($farrowingDate)->days;
                        
                        // Calculate survival rate for this litter
                        $litterSurvival = $row['total_born'] > 0 
                            ? round(($row['piglets_alive'] / $row['total_born']) * 100, 1) 
                            : 0;
                        
                        // Determine performance badge
                        $performance = $row['piglets_alive'] >= 12 ? 'excellent' : 
                                     ($row['piglets_alive'] >= 10 ? 'good' : 'average');
                        ?>
                        <tr class="farrowing-row" 
                            data-sow="<?= strtolower(htmlspecialchars($row['sow_tag'])) ?>"
                            data-date="<?= date('Y-m', strtotime($row['farrowing_date'])) ?>">
                            
                            <td>
                                <div>
                                    <strong class="d-block"><?= date('d M Y', strtotime($row['farrowing_date'])) ?></strong>
                                    <small class="text-muted">
                                        <?php
                                        $today = new DateTime();
                                        $daysSince = $today->diff($farrowingDate)->days;
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
                                            Served: <?= date('d M Y', strtotime($row['serving_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="d-none d-lg-table-cell">
                                <div>
                                    <span class="d-block"><?= date('d M Y', strtotime($row['serving_date'])) ?></span>
                                    <small class="text-muted">
                                        <?= $gestationDays ?> days gestation
                                    </small>
                                </div>
                            </td>
                            
                            <td class="d-none d-md-table-cell">
                                <div class="d-flex align-items-center">
                                    <span class="fs-5 fw-bold text-primary me-2"><?= $row['total_born'] ?></span>
                                    <small class="text-muted">piglets</small>
                                </div>
                            </td>
                            
                            <td>
                                <div>
                                    <div class="d-flex align-items-center">
                                        <span class="fs-5 fw-bold text-success me-2"><?= $row['piglets_alive'] ?></span>
                                        <span class="badge bg-success"><?= $litterSurvival ?>%</span>
                                    </div>
                                    <small class="text-muted d-md-none">
                                        of <?= $row['total_born'] ?> born
                                    </small>
                                </div>
                            </td>
                            
                            <td class="d-none d-xl-table-cell">
                                <?php if ($row['stillbirths'] > 0): ?>
                                    <span class="badge bg-danger">
                                        ⚠️ <?= $row['stillbirths'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success fw-bold">None</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="btn-group d-flex justify-content-end" role="group">
                                    <a href="view.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip"
                                       title="View farrowing details">
                                       <span class="d-none d-lg-inline">View</span>
                                       <span class="d-inline d-lg-none">👁️</span>
                                    </a>
                                    <a href="edit.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip"
                                       title="Edit farrowing record">
                                       <span class="d-none d-lg-inline">Edit</span>
                                       <span class="d-inline d-lg-none">✏️</span>
                                    </a>
                                    <a href="delete.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       data-bs-toggle="tooltip"
                                       title="Delete record"
                                       data-confirm-delete="Are you sure you want to delete this farrowing record for sow '<?= htmlspecialchars($row['sow_tag']) ?>'?">
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

<!-- Performance Guide -->
<div class="card mt-4">
    <div class="card-body">
        <h6 class="card-title">
            <span class="emoji-icon">📊</span> Litter Performance Guide
        </h6>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2" style="font-size: 1.5rem;">12+</span>
                    <div>
                        <strong class="d-block">Excellent</strong>
                        <small class="text-muted">12 or more piglets alive</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2" style="font-size: 1.5rem;">10-11</span>
                    <div>
                        <strong class="d-block">Good</strong>
                        <small class="text-muted">10-11 piglets alive</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning me-2" style="font-size: 1.5rem;">&lt;10</span>
                    <div>
                        <strong class="d-block">Average</strong>
                        <small class="text-muted">Below 10 piglets alive</small>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <small class="text-muted">
                    <strong>Normal Gestation:</strong> 114 days (3 months, 3 weeks, 3 days)
                </small>
            </div>
            <div class="col-12 col-md-6">
                <small class="text-muted">
                    <strong>Survival Rate:</strong> Percentage of piglets born alive out of total born
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Search/Filter JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchFarrowing');
    const monthFilter = document.getElementById('filterMonth');
    const tableRows = document.querySelectorAll('.farrowing-row');
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
                const tbody = document.querySelector('#farrowingsTable tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
                            <h5>No farrowings match your search</h5>
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