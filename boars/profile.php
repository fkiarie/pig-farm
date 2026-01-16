<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: list.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Boar Details
|--------------------------------------------------------------------------
*/
$boarResult = $conn->query("
    SELECT * FROM boars WHERE id = $id LIMIT 1
");

if ($boarResult->num_rows === 0) {
    header('Location: list.php');
    exit;
}

$boar = $boarResult->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_servings,
        COUNT(DISTINCT s.sow_id) as unique_sows,
        SUM(CASE WHEN s.method = 'Natural' THEN 1 ELSE 0 END) as natural_count,
        SUM(CASE WHEN s.method = 'AI' THEN 1 ELSE 0 END) as ai_count
    FROM servings s
    WHERE s.boar_id = $id
")->fetch_assoc();

// Count successful farrowings
$farrowing_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT f.id) as total_farrowings,
        COALESCE(SUM(f.piglets_alive), 0) as total_piglets
    FROM servings s
    LEFT JOIN farrowings f ON f.serving_id = s.id
    WHERE s.boar_id = $id
")->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$totalResult = $conn->query("
    SELECT COUNT(*) as total 
    FROM servings s
    WHERE s.boar_id = $id
");
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

/*
|--------------------------------------------------------------------------
| Serving History with Pagination
|--------------------------------------------------------------------------
*/
$servings = $conn->query("
    SELECT 
        s.id as serving_id,
        s.serving_date,
        s.method,
        s.expected_farrowing,
        sw.tag_no AS sow_tag,
        sw.status as sow_status,
        f.id as farrowing_id,
        f.piglets_alive
    FROM servings s
    JOIN sows sw ON sw.id = s.sow_id
    LEFT JOIN farrowings f ON f.serving_id = s.id
    WHERE s.boar_id = $id
    ORDER BY s.serving_date DESC
    LIMIT $perPage OFFSET $offset
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <span class="emoji-icon">🐗</span> Boar Profile
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="edit.php?id=<?= $boar['id'] ?>" class="btn btn-outline-primary">
                <span class="d-none d-sm-inline">✏️ Edit Profile</span>
                <span class="d-inline d-sm-none">✏️ Edit</span>
            </a>
        </div>
        <a href="list.php" class="btn btn-outline-secondary">
            <span class="d-none d-sm-inline">← Back to List</span>
            <span class="d-inline d-sm-none">← Back</span>
        </a>
    </div>
</div>

<!-- Boar Summary Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="emoji-icon">ℹ️</span> Basic Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-start">
                    <span class="emoji-icon me-2" style="font-size: 1.5rem;">🏷️</span>
                    <div>
                        <small class="text-muted d-block">Tag/Name</small>
                        <strong class="fs-5"><?= htmlspecialchars($boar['name']) ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-start">
                    <span class="emoji-icon me-2" style="font-size: 1.5rem;">🧬</span>
                    <div>
                        <small class="text-muted d-block">Breed</small>
                        <strong><?= htmlspecialchars($boar['breed']) ?: '—' ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-start">
                    <span class="emoji-icon me-2" style="font-size: 1.5rem;">
                        <?php
                        $statusIcon = match($boar['status']) {
                            'Active' => '✅',
                            'Resting' => '😴',
                            'Sold' => '💰',
                            'Inactive' => '❌',
                            default => '•'
                        };
                        echo $statusIcon;
                        ?>
                    </span>
                    <div>
                        <small class="text-muted d-block">Status</small>
                        <?php
                        $badgeClass = match($boar['status']) {
                            'Active' => 'success',
                            'Resting' => 'warning',
                            'Sold' => 'secondary',
                            'Inactive' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>">
                            <?= $boar['status'] ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-start">
                    <span class="emoji-icon me-2" style="font-size: 1.5rem;">📅</span>
                    <div>
                        <small class="text-muted d-block">Date Added</small>
                        <strong><?= date('d M Y', strtotime($boar['created_at'])) ?></strong>
                        <small class="text-muted d-block">
                            <?php
                            $addedDate = new DateTime($boar['created_at']);
                            $today = new DateTime();
                            $daysInFarm = $today->diff($addedDate)->days;
                            echo $daysInFarm . ' days in farm';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($boar['notes']): ?>
            <hr class="my-4">
            <div class="d-flex align-items-start">
                <span class="emoji-icon me-2" style="font-size: 1.5rem;">📝</span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-2">Notes</strong>
                    <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($boar['notes'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['total_servings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">📋</span> Total Servings
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $stats['unique_sows'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐷</span> Unique Sows
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $farrowing_stats['total_farrowings'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐣</span> Farrowings
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h3 class="mb-2"><?= $farrowing_stats['total_piglets'] ?></h3>
                <p class="text-muted mb-0">
                    <span class="emoji-icon">🐽</span> Total Offspring
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Serving History -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><span class="emoji-icon">📅</span> Serving History</span>
        <span class="badge bg-secondary"><?= $totalRecords ?> total</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Serving Date</th>
                    <th>
                        <span class="emoji-icon">🐷</span> Sow
                    </th>
                    <th class="d-none d-md-table-cell">Method</th>
                    <th class="d-none d-lg-table-cell">Expected Farrowing</th>
                    <th class="d-none d-xl-table-cell">Result</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servings->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📅</span>
                                <h5>No serving records yet</h5>
                                <p class="mb-3">This boar hasn't been used for any servings.</p>
                                <a href="../breeding/serve.php" class="btn btn-success">+ Record First Serving</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $servings->fetch_assoc()): ?>
                        <?php
                        $servingDate = new DateTime($row['serving_date']);
                        $today = new DateTime();
                        $daysSince = $today->diff($servingDate)->days;
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong class="d-block"><?= date('d M Y', strtotime($row['serving_date'])) ?></strong>
                                    <small class="text-muted"><?= $daysSince ?> days ago</small>
                                </div>
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="emoji-icon me-2">🐷</span>
                                    <div>
                                        <strong><?= htmlspecialchars($row['sow_tag']) ?></strong>
                                        <small class="text-muted d-md-none d-block mt-1">
                                            <?= $row['method'] === 'Natural' ? '🐗 Natural' : '🔬 AI' ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="d-none d-md-table-cell">
                                <?php
                                $methodIcon = $row['method'] === 'Natural' ? '🐗' : '🔬';
                                $methodClass = $row['method'] === 'Natural' ? 'success' : 'info';
                                ?>
                                <span class="badge bg-<?= $methodClass ?>">
                                    <?= $methodIcon ?> <?= $row['method'] ?>
                                </span>
                            </td>
                            
                            <td class="d-none d-lg-table-cell">
                                <div>
                                    <span class="d-block"><?= date('d M Y', strtotime($row['expected_farrowing'])) ?></span>
                                    <?php
                                    $expectedDate = new DateTime($row['expected_farrowing']);
                                    $daysUntil = $today->diff($expectedDate)->days;
                                    $isPast = $today > $expectedDate;
                                    ?>
                                    <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                        <small class="<?= $isPast ? 'text-danger' : 'text-muted' ?>">
                                            <?= $isPast ? 'Overdue' : $daysUntil . ' days' ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="d-none d-xl-table-cell">
                                <?php if ($row['farrowing_id']): ?>
                                    <span class="badge bg-success">
                                        ✓ <?= $row['piglets_alive'] ?> piglets
                                    </span>
                                <?php elseif ($row['sow_status'] === 'Pregnant'): ?>
                                    <span class="badge bg-warning">
                                        ⏳ Pending
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                    <span class="badge bg-warning">🤰 Pregnant</span>
                                <?php elseif ($row['farrowing_id']): ?>
                                    <span class="badge bg-success">✅ Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav aria-label="Serving history pagination">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                
                <!-- Previous Button -->
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?id=<?= $id ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">«</span>
                    </a>
                </li>

                <?php
                // Smart pagination: show first, last, current and nearby pages
                $range = 2; // Pages to show on each side of current page
                
                for ($i = 1; $i <= $totalPages; $i++):
                    // Show first page, last page, current page and nearby pages
                    if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?id=<?= $id ?>&page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php
                    // Show ellipsis
                    elseif ($i == $page - $range - 1 || $i == $page + $range + 1):
                ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php
                    endif;
                endfor;
                ?>

                <!-- Next Button -->
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?id=<?= $id ?>&page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">»</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="text-center mt-2">
            <small class="text-muted">
                Showing <?= min($offset + 1, $totalRecords) ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> servings
            </small>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>