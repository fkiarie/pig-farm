<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch Sows with Statistics
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

$result = $conn->query("
    SELECT id, tag_no, breed, status, date_of_birth, created_at
    FROM sows
    WHERE status != 'Culled'
    ORDER BY 
        CASE status
            WHEN 'Pregnant' THEN 1
            WHEN 'Lactating' THEN 2
            WHEN 'Dry' THEN 3
            WHEN 'Active' THEN 4
            ELSE 5
        END,
        created_at DESC
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-3 border-bottom">
    <h1 class="h2">🐷 Sows Management</h1>
    <a href="add.php" class="btn btn-success">+ Add Sow</a>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['🐷','Total',$stats['total']],
        ['✅','Active',$stats['active']],
        ['🤰','Pregnant',$stats['pregnant']],
        ['🍼','Lactating',$stats['lactating']],
        ['😴','Dry',$stats['dry']],
    ];
    foreach ($cards as [$icon,$label,$count]):
    ?>
    <div class="col-6 col-lg">
        <div class="card">
            <div class="card-body text-center">
                <h3><?= $count ?></h3>
                <p class="text-muted mb-0"><?= $icon ?> <?= $label ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-md-4">
            <input type="text" id="searchSow" class="form-control" placeholder="Search tag or breed">
        </div>
        <div class="col-md-3">
            <select id="filterStatus" class="form-select">
                <option value="">All Statuses</option>
                <option>Active</option>
                <option>Pregnant</option>
                <option>Lactating</option>
                <option>Dry</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">Reset</button>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle" id="sowsTable">
<thead>
<tr>
    <th>Tag</th>
    <th class="d-none d-md-table-cell">Breed</th>
    <th>Status</th>
    <th class="d-none d-lg-table-cell">DOB</th>
    <th class="text-end">Actions</th>
</tr>
</thead>
<tbody>

<?php if ($result->num_rows === 0): ?>
<tr><td colspan="5" class="text-center text-muted py-4">No sows found</td></tr>
<?php endif; ?>

<?php while ($row = $result->fetch_assoc()):
$status = $row['status'];
$badge = match($status) {
    'Active' => 'success',
    'Pregnant' => 'warning',
    'Lactating' => 'info',
    'Dry' => 'secondary',
    default => 'secondary'
};
$icon = match($status) {
    'Active' => '✅',
    'Pregnant' => '🤰',
    'Lactating' => '🍼',
    'Dry' => '😴',
    default => '•'
};
?>

<tr class="sow-row"
    data-tag="<?= strtolower($row['tag_no']) ?>"
    data-breed="<?= strtolower($row['breed'] ?? '') ?>"
    data-status="<?= $status ?>">

<td><strong><?= htmlspecialchars($row['tag_no']) ?></strong></td>
<td class="d-none d-md-table-cell"><?= $row['breed'] ?: '—' ?></td>
<td>
    <span class="badge bg-<?= $badge ?>">
        <?= $icon ?> <?= $status ?>
    </span>
</td>
<td class="d-none d-lg-table-cell">
    <?= $row['date_of_birth'] ? date('d M Y', strtotime($row['date_of_birth'])) : '—' ?>
</td>
<td class="text-end">
    <div class="btn-group">
        <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success">Profile</a>
        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <a href="cull.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Cull this sow?')">Cull</a>
    </div>
</td>
</tr>

<?php endwhile; ?>

</tbody>
</table>
</div>
</div>

<script>
const search = document.getElementById('searchSow');
const filter = document.getElementById('filterStatus');
const rows = document.querySelectorAll('.sow-row');

function filterTable() {
    const s = search.value.toLowerCase();
    const f = filter.value;
    rows.forEach(r => {
        const ok =
            (r.dataset.tag.includes(s) || r.dataset.breed.includes(s)) &&
            (!f || r.dataset.status === f);
        r.style.display = ok ? '' : 'none';
    });
}
search.oninput = filter.onchange = filterTable;
function resetFilters() {
    search.value = '';
    filter.value = '';
    filterTable();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
