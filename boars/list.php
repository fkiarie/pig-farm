<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Boars with Statistics
|--------------------------------------------------------------------------
| We show all boars, including inactive/sold, for record purposes.
*/

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Resting' THEN 1 ELSE 0 END) as resting,
        SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END) as sold
    FROM boars
")->fetch_assoc();

// Get all boars
$result = $conn->query("
    SELECT 
        id,
        name,
        breed,
        status,
        created_at
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

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐗</span> Boars Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-success">
            <span class="d-none d-sm-inline">+ Add New Boar</span>
            <span class="d-inline d-sm-none">+ Add</span>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐗</span> Total Boars
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['active'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">✅</span> Active
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['resting'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">😴</span> Resting
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['sold'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">💰</span> Sold
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
                <label for="searchBoar" class="form-label">Search Boars</label>
                <input type="text" 
                       class="form-control" 
                       id="searchBoar" 
                       placeholder="Search by name or breed...">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="filterStatus" class="form-label">Filter by Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Resting">Resting</option>
                    <option value="Sold">Sold</option>
                    <option value="Inactive">Inactive</option>
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

<!-- Boars Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Boars</span>
        <span class="badge bg-secondary" id="recordCount"><?= $result->num_rows ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="boarsTable">
            <thead>
                <tr>
                    <th>
                        <div class="d-flex align-items-center">
                            Name / Tag
                        </div>
                    </th>
                    <th class="d-none d-md-table-cell">Breed</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Added On</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr class="no-results">
                    <td colspan="5" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🐗</span>
                            <h5>No boars found</h5>
                            <p class="mb-3">Start by adding your first boar to the system.</p>
                            <a href="add.php" class="btn btn-success">+ Add Your First Boar</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="boar-row" 
                    data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>"
                    data-breed="<?= strtolower($row['breed'] ?: '') ?>"
                    data-status="<?= $row['status'] ?>">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="font-size: 1.5rem;">🐗</div>
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($row['name']) ?></strong>
                                <small class="text-muted d-md-none">
                                    <?= $row['breed'] ?: 'No breed specified' ?>
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
                            'Resting' => 'warning',
                            'Sold' => 'secondary',
                            'Inactive' => 'danger',
                            default => 'secondary'
                        };
                        
                        $statusIcon = match($status) {
                            'Active' => '✅',
                            'Resting' => '😴',
                            'Sold' => '💰',
                            'Inactive' => '❌',
                            default => '•'
                        };
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>">
                            <span class="d-none d-sm-inline"><?= $statusIcon ?> </span><?= $status ?>
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="text-muted">
                            <?= date('d M Y', strtotime($row['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group d-flex justify-content-end" role="group">
                            <a href="profile.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-success"
                               data-bs-toggle="tooltip"
                               title="View boar profile">
                               <span class="d-none d-lg-inline">Profile</span>
                               <span class="d-inline d-lg-none">👁️</span>
                            </a>
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="Edit boar details">
                               <span class="d-none d-lg-inline">Edit</span>
                               <span class="d-inline d-lg-none">✏️</span>
                            </a>
                            <?php if ($row['status'] !== 'Inactive' && $row['status'] !== 'Sold'): ?>
                                <a href="soft_delete.php?id=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-bs-toggle="tooltip"
                                   title="Mark as inactive"
                                   data-confirm-delete="Are you sure you want to deactivate '<?= htmlspecialchars($row['name']) ?>'?">
                                   <span class="d-none d-lg-inline">Deactivate</span>
                                   <span class="d-inline d-lg-none">🚫</span>
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

<!-- Search/Filter JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchBoar');
    const statusFilter = document.getElementById('filterStatus');
    const tableRows = document.querySelectorAll('.boar-row');
    const recordCount = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const name = row.dataset.name;
            const breed = row.dataset.breed;
            const status = row.dataset.status;

            const matchesSearch = name.includes(searchTerm) || breed.includes(searchTerm);
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
                const tbody = document.querySelector('#boarsTable tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `
                    <td colspan="5" class="text-center py-5">
                        <div class="text-muted">
                            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🔍</span>
                            <h5>No boars match your search</h5>
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