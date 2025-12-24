<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Servings with Statistics
|--------------------------------------------------------------------------
*/

// Get statistics
$stats = $conn->query("
   SELECT 
        COUNT(*) AS total_servings,
        SUM(CASE WHEN sv.method = 'Natural' THEN 1 ELSE 0 END) AS natural_count,
        SUM(CASE WHEN sv.method = 'AI' THEN 1 ELSE 0 END) AS ai_count
    FROM servings sv"
)->fetch_assoc();

// Fetch all servings with sow & boar info
$query = "
    SELECT 
        sv.id,
        sv.serving_date,
        sv.expected_farrowing,
        sv.method,
        s.tag_no AS sow_tag,
        s.status AS sow_status,
        b.name AS boar_name
    FROM servings sv
    JOIN sows s ON sv.sow_id = s.id
    JOIN boars b ON sv.boar_id = b.id
    ORDER BY sv.serving_date DESC
";

$servings = $conn->query($query);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        Breeding Records
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            <span class="d-none d-sm-inline">+ Record New Serving</span>
            <span class="d-inline d-sm-none">+ Add</span>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_servings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📋</span> Total Servings
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_servings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🤰</span> Pregnant
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_servings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">✅</span> Completed
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['natural_count'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐗</span> Natural
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['ai_count'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🔬</span> AI Method
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
                <label for="searchServing" class="form-label">Search</label>
                <input type="text" 
                       class="form-control" 
                       id="searchServing" 
                       placeholder="Search by sow or boar...">
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <label for="filterMethod" class="form-label">Method</label>
                <select class="form-select" id="filterMethod">
                    <option value="">All Methods</option>
                    <option value="Natural">Natural</option>
                    <option value="AI">AI</option>
                </select>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <label for="filterStatus" class="form-label">Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="Pregnant">Pregnant</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div class="col-12 col-md-2 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Servings Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Breeding Records</span>
        <span class="badge bg-secondary" id="recordCount"><?= $servings->num_rows ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="servingsTable">
            <thead>
                <tr>
                    <th>Serving Date</th>
                    <th>
                        <span class="emoji-icon">🐷</span> Sow
                    </th>
                    <th class="d-none d-md-table-cell">
                        <span class="emoji-icon">🐗</span> Boar
                    </th>
                    <th class="d-none d-lg-table-cell">Method</th>
                    <th class="d-none d-xl-table-cell">Expected Farrowing</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servings->num_rows === 0): ?>
                    <tr class="no-results">
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">❤️</span>
                                <h5>No serving records found</h5>
                                <p class="mb-3">Start by recording your first breeding.</p>
                                <a href="serve.php" class="btn btn-success">+ Record First Serving</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $servings->fetch_assoc()): ?>
                        <?php
                        // Calculate days until farrowing
                        $today = new DateTime();
                        $farrowingDate = new DateTime($row['expected_farrowing']);
                        $daysUntil = $today->diff($farrowingDate)->days;
                        $isPast = $today > $farrowingDate;
                        
                        // Check if farrowing is soon (within 7 days)
                        $isSoon = !$isPast && $daysUntil <= 7;
                        ?>
                        <tr class="serving-row" 
                            data-sow="<?= strtolower(htmlspecialchars($row['sow_tag'])) ?>"
                            data-boar="<?= strtolower(htmlspecialchars($row['boar_name'])) ?>"
                            data-method="<?= $row['method'] ?>"
                            data-status="<?= $row['sow_status'] === 'Pregnant' ? 'Pregnant' : 'Completed' ?>">
                            
                            <td>
                                <div>
                                    <strong class="d-block"><?= date('d M Y', strtotime($row['serving_date'])) ?></strong>
                                    <small class="text-muted">
                                        <?php
                                        $servingDate = new DateTime($row['serving_date']);
                                        $daysSince = $today->diff($servingDate)->days;
                                        echo $daysSince . ' days ago';
                                        ?>
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="emoji-icon me-2">🐷</span>
                                    <strong><?= htmlspecialchars($row['sow_tag']) ?></strong>
                                </div>
                                <small class="text-muted d-md-none d-block mt-1">
                                    <span class="emoji-icon">🐗</span> <?= htmlspecialchars($row['boar_name']) ?>
                                </small>
                            </td>
                            
                            <td class="d-none d-md-table-cell">
                                <div class="d-flex align-items-center">
                                    <span class="emoji-icon me-2">🐗</span>
                                    <?= htmlspecialchars($row['boar_name']) ?>
                                </div>
                            </td>
                            
                            <td class="d-none d-lg-table-cell">
                                <?php
                                $methodIcon = $row['method'] === 'Natural' ? '🐗' : '🔬';
                                $methodClass = $row['method'] === 'Natural' ? 'success' : 'info';
                                ?>
                                <span class="badge bg-<?= $methodClass ?>">
                                    <?= $methodIcon ?> <?= $row['method'] ?>
                                </span>
                            </td>
                            
                            <td class="d-none d-xl-table-cell">
                                <div>
                                    <strong class="d-block"><?= date('d M Y', strtotime($row['expected_farrowing'])) ?></strong>
                                    <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                        <small class="<?= $isSoon ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?php if ($isPast): ?>
                                                ⚠️ Overdue by <?= $daysUntil ?> days
                                            <?php elseif ($isSoon): ?>
                                                🔔 Due in <?= $daysUntil ?> days
                                            <?php else: ?>
                                                <?= $daysUntil ?> days remaining
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td>
                                <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                    <span class="badge bg-warning">
                                        <span class="d-none d-sm-inline">🤰 </span>Pregnant
                                    </span>
                                    <?php if ($isSoon && !$isPast): ?>
                                        <span class="badge bg-danger ms-1">
                                            <span class="d-none d-sm-inline">🔔 </span>Soon
                                        </span>
                                    <?php elseif ($isPast): ?>
                                        <span class="badge bg-danger ms-1">
                                            <span class="d-none d-sm-inline">⚠️ </span>Overdue
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <span class="d-none d-sm-inline">✅ </span>Completed
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="btn-group d-flex justify-content-end" role="group">
                                    <a href="view.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip"
                                       title="View serving details">
                                       <span class="d-none d-lg-inline">View Details</span>
                                       <span class="d-inline d-lg-none">👁️</span>
                                    </a>
                                    <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                        <a href="../breeding/farrowing.php?serving_id=<?= $row['id'] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="tooltip"
                                           title="Record farrowing">
                                           <span class="d-none d-lg-inline">Record Birth</span>
                                           <span class="d-inline d-lg-none">🐣</span>
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

<!-- Info Card -->
<div class="card mt-4">
    <div class="card-body">
        <h6 class="card-title">
            <span class="emoji-icon">💡</span> Breeding Status Guide
        </h6>
        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-3">
                <span class="badge bg-warning">🤰 Pregnant</span>
                <small class="d-block text-muted mt-1">Sow is currently pregnant</small>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <span class="badge bg-success">✅ Completed</span>
                <small class="d-block text-muted mt-1">Farrowing has occurred</small>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <span class="badge bg-danger">🔔 Soon</span>
                <small class="d-block text-muted mt-1">Due within 7 days</small>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <span class="badge bg-danger">⚠️ Overdue</span>
                <small class="d-block text-muted mt-1">Past expected date</small>
            </div>
        </div>
    </div>
</div>

<!-- Search/Filter JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchServing');
    const methodFilter = document.getElementById('filterMethod');
    const statusFilter = document.getElementById('filterStatus');
    const tableRows = document.querySelectorAll('.serving-row');
    const recordCount = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const methodValue = methodFilter.value;
        const statusValue = statusFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const sow = row.dataset.sow;
            const boar = row.dataset.boar;
            const method = row.dataset.method;
            const status = row.dataset.status;

            const matchesSearch = sow.includes(searchTerm) || boar.includes(searchTerm);
            const matchesMethod = !methodValue || method === methodValue;
            const matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesMethod && matchesStatus) {
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
                const tbody = document.querySelector('#servingsTable tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
                            <h5>No servings match your search</h5>
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

    if (methodFilter) {
        methodFilter.addEventListener('change', filterTable);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', filterTable);
    }

    // Reset filters function
    window.resetFilters = function() {
        searchInput.value = '';
        methodFilter.value = '';
        statusFilter.value = '';
        filterTable();
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>