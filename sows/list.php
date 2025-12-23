<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Sows with Statistics
|--------------------------------------------------------------------------
| We show all sows except culled ones, for active management.
*/

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Pregnant' THEN 1 ELSE 0 END) as pregnant,
        SUM(CASE WHEN status = 'Nursing' THEN 1 ELSE 0 END) as nursing,
        SUM(CASE WHEN status = 'Resting' THEN 1 ELSE 0 END) as resting
    FROM sows 
    WHERE status != 'Culled'
")->fetch_assoc();

// Get all sows
$result = $conn->query("
    SELECT 
        id,
        tag_no,
        breed,
        status,
        date_of_birth,
        created_at
    FROM sows 
    WHERE status != 'Culled' 
    ORDER BY 
        CASE status
            WHEN 'Pregnant' THEN 1
            WHEN 'Nursing' THEN 2
            WHEN 'Active' THEN 3
            WHEN 'Resting' THEN 4
            ELSE 5
        END,
        created_at DESC
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐷</span> Sows Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            <span class="d-none d-sm-inline">+ Add New Sow</span>
            <span class="d-inline d-sm-none">+ Add</span>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐷</span> Total Sows
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['active'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">✅</span> Active
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['pregnant'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🤰</span> Pregnant
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['nursing'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🍼</span> Nursing
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['resting'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">😴</span> Resting
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
                <label for="searchSow" class="form-label">Search Sows</label>
                <input type="text" 
                       class="form-control" 
                       id="searchSow" 
                       placeholder="Search by tag number or breed...">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="filterStatus" class="form-label">Filter by Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Pregnant">Pregnant</option>
                    <option value="Nursing">Nursing</option>
                    <option value="Resting">Resting</option>
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

<!-- Sows Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Sows</span>
        <span class="badge bg-secondary" id="recordCount"><?= $result->num_rows ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="sowsTable">
            <thead>
                <tr>
                    <th>
                        <div class="d-flex align-items-center">
                            Name
                        </div>
                    </th>
                    <th class="d-none d-md-table-cell">Breed</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Date of Birth</th>
                    <th class="d-none d-xl-table-cell">Added On</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr class="no-results">
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🐷</span>
                            <h5>No sows found</h5>
                            <p class="mb-3">Start by adding your first sow to the system.</p>
                            <a href="add.php" class="btn btn-success">+ Add Your First Sow</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="sow-row" 
                    data-tag="<?= strtolower(htmlspecialchars($row['tag_no'])) ?>"
                    data-breed="<?= strtolower($row['breed'] ?: '') ?>"
                    data-status="<?= $row['status'] ?>">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="font-size: 1.5rem;">🐷</div>
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($row['tag_no']) ?></strong>
                                <small class="text-muted d-md-none">
                                    <?= htmlspecialchars($row['breed']) ?: 'No breed specified' ?>
                                </small>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="text-muted">
                            <?= htmlspecialchars($row['breed']) ?: '—' ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $status = $row['status'];
                        $badgeClass = match($status) {
                            'Active' => 'success',
                            'Pregnant' => 'warning',
                            'Nursing' => 'info',
                            'Resting' => 'secondary',
                            default => 'secondary'
                        };
                        
                        $statusIcon = match($status) {
                            'Active' => '✅',
                            'Pregnant' => '🤰',
                            'Nursing' => '🍼',
                            'Resting' => '😴',
                            default => '•'
                        };
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>">
                            <span class="d-none d-sm-inline"><?= $statusIcon ?> </span><?= $status ?>
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="text-muted">
                            <?php if ($row['date_of_birth']): ?>
                                <?= date('d M Y', strtotime($row['date_of_birth'])) ?>
                                <small class="d-block text-muted" style="font-size: 0.75rem;">
                                    <?php
                                    $dob = new DateTime($row['date_of_birth']);
                                    $now = new DateTime();
                                    $age = $dob->diff($now);
                                    echo $age->y . ' years, ' . $age->m . ' months';
                                    ?>
                                </small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="d-none d-xl-table-cell">
                        <span class="text-muted">
                            <?= date('d M Y', strtotime($row['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group d-flex justify-content-end" role="group">
                            <a href="profile.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-success"
                               data-bs-toggle="tooltip"
                               title="View sow profile">
                               <span class="d-none d-lg-inline">Profile</span>
                               <span class="d-inline d-lg-none">👁️</span>
                            </a>
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="Edit sow details">
                               <span class="d-none d-lg-inline">Edit</span>
                               <span class="d-inline d-lg-none">✏️</span>
                            </a>
                            <a href="cull.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-bs-toggle="tooltip"
                               title="Mark as culled"
                               data-confirm-delete="Are you sure you want to cull sow '<?= htmlspecialchars($row['tag_no']) ?>'? This action marks the sow as removed from breeding.">
                               <span class="d-none d-lg-inline">Cull</span>
                               <span class="d-inline d-lg-none">❌</span>
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

<!-- Search/Filter JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchSow');
    const statusFilter = document.getElementById('filterStatus');
    const tableRows = document.querySelectorAll('.sow-row');
    const recordCount = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const tag = row.dataset.tag;
            const breed = row.dataset.breed;
            const status = row.dataset.status;

            const matchesSearch = tag.includes(searchTerm) || breed.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesStatus) {
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
                const tbody = document.querySelector('#sowsTable tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
                            <h5>No sows match your search</h5>
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

    if (statusFilter) {
        statusFilter.addEventListener('change', filterTable);
    }

    // Reset filters function
    window.resetFilters = function() {
        searchInput.value = '';
        statusFilter.value = '';
        filterTable();
    };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>