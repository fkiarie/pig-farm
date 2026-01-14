<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../auth/auth_check.php';
require_once '../config/db.php';
require_once '../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch All Servings
|--------------------------------------------------------------------------
*/
$query = "
    SELECT 
        sv.id AS serving_id,
        sv.serving_date,
        sv.expected_farrowing,
        sv.method,
        s.id AS sow_id,
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
    <h1 class="h2">Breeding Records</h1>
    <a href="add.php" class="btn btn-success">+ Record New Serving</a>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="searchServing" placeholder="Search by sow or boar">
            </div>
            <div class="col-md-3">
                <label class="form-label">Method</label>
                <select class="form-select" id="filterMethod">
                    <option value="">All</option>
                    <option value="Natural">Natural</option>
                    <option value="AI">AI</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">All</option>
                    <option value="Pregnant">Pregnant</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>
</div>

<!-- Servings Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Serving Records</span>
        <span class="badge bg-secondary" id="recordCount"><?= $servings->num_rows ?> records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="servingsTable">
            <thead>
                <tr>
                    <th>Serving Date</th>
                    <th>Sow</th>
                    <th class="d-none d-md-table-cell">Boar</th>
                    <th class="d-none d-lg-table-cell">Method</th>
                    <th class="d-none d-xl-table-cell">Expected Farrowing</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php if ($servings->num_rows === 0): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        No serving records found.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $servings->fetch_assoc()): ?>
                    <?php
                        $today = new DateTime();
                        $expected = new DateTime($row['expected_farrowing']);
                        $daysUntil = $today->diff($expected)->days;
                        $isPast = $today > $expected;
                        $isSoon = !$isPast && $daysUntil <= 7;
                    ?>

                    <tr class="serving-row"
                        data-sow="<?= strtolower($row['sow_tag']) ?>"
                        data-boar="<?= strtolower($row['boar_name']) ?>"
                        data-method="<?= $row['method'] ?>"
                        data-status="<?= $row['sow_status'] === 'Pregnant' ? 'Pregnant' : 'Completed' ?>">

                        <!-- Serving Date -->
                        <td>
                            <strong><?= date('d M Y', strtotime($row['serving_date'])) ?></strong>
                        </td>

                        <!-- Sow + MOBILE Farrowing Info -->
                        <td>
                            🐷 <strong><?= htmlspecialchars($row['sow_tag']) ?></strong>

                            <!-- MOBILE ONLY: Expected Farrowing -->
                            <div class="d-md-none mt-1">
                                <small class="text-muted d-block">
                                    🐣 <?= date('d M Y', strtotime($row['expected_farrowing'])) ?>
                                </small>

                                <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                    <small class="<?= $isPast || $isSoon ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= $isPast ? 'Overdue' : $daysUntil . ' days remaining' ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Boar -->
                        <td class="d-none d-md-table-cell">
                            🐗 <?= htmlspecialchars($row['boar_name']) ?>
                        </td>

                        <!-- Method -->
                        <td class="d-none d-lg-table-cell">
                            <span class="badge bg-<?= $row['method'] === 'Natural' ? 'success' : 'info' ?>">
                                <?= $row['method'] ?>
                            </span>
                        </td>

                        <!-- Desktop Farrowing -->
                        <td class="d-none d-xl-table-cell">
                            <?= date('d M Y', strtotime($row['expected_farrowing'])) ?>
                            <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                <br>
                                <small class="<?= $isSoon || $isPast ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $isPast ? 'Overdue' : $daysUntil . ' days left' ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span class="badge bg-<?= $row['sow_status'] === 'Pregnant' ? 'warning' : 'success' ?>">
                                <?= $row['sow_status'] === 'Pregnant' ? 'Pregnant' : 'Completed' ?>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td>
                            <div class="btn-group d-flex justify-content-end">
                                <a href="<?= BASE_URL ?>/sows/profile.php?id=<?= $row['sow_id'] ?>"
                                   class="btn btn-sm btn-outline-success">
                                    View Sow
                                </a>

                                <?php if ($row['sow_status'] === 'Pregnant'): ?>
                                    <a href="<?= BASE_URL ?>/farrowing/list.php?serving_id=<?= $row['serving_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        Record Birth
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

<!-- Filtering JS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchServing');
    const method = document.getElementById('filterMethod');
    const status = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.serving-row');
    const count = document.getElementById('recordCount');

    function filter() {
        let visible = 0;
        rows.forEach(r => {
            const ok =
                (!search.value || r.dataset.sow.includes(search.value.toLowerCase()) || r.dataset.boar.includes(search.value.toLowerCase())) &&
                (!method.value || r.dataset.method === method.value) &&
                (!status.value || r.dataset.status === status.value);
            r.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        count.textContent = visible + ' records';
    }

    search.oninput = filter;
    method.onchange = filter;
    status.onchange = filter;

    window.resetFilters = () => {
        search.value = method.value = status.value = '';
        filter();
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>
