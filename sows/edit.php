<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$result = $conn->query("SELECT * FROM sows WHERE id = $id");

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger m-3'>Sow not found. <a href='list.php'>Return to list</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$sow = $result->fetch_assoc();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; }
    .input-group-text { background-color: #f8f9fa; color: #6c757d; border-right: none; }
    .form-control, .form-select { border-left: none; }
    .form-control:focus, .form-select:focus { border-color: #dee2e6; box-shadow: none; }
    .input-group:focus-within .input-group-text { border-color: #86b7fe; }
    .input-group:focus-within .form-control { border-color: #86b7fe; }
    .btn-update { padding: 0.6rem 2rem; border-radius: 8px; font-weight: 600; }
    .section-title { font-size: 1.1rem; font-weight: 700; color: #0d6efd; margin-bottom: 1.5rem; display: flex; align-items: center; }
</style>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="list.php" class="text-decoration-none">Sows</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Sow: <?= htmlspecialchars($sow['tag_no']) ?></li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card form-card">
                <div class="card-body p-4">
                    
                    <div class="section-title">
                        <i class="bi bi-pencil-square me-2"></i> Update Sow Information
                    </div>

                    <form method="POST" action="update.php">
                        <input type="hidden" name="id" value="<?= $sow['id'] ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tag Number / Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" name="tag_no" class="form-control" 
                                           value="<?= htmlspecialchars($sow['tag_no']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Breed</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-info-circle"></i></span>
                                    <input type="text" name="breed" class="form-control" 
                                           value="<?= htmlspecialchars($sow['breed']) ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="date_of_birth" class="form-control" 
                                           value="<?= $sow['date_of_birth'] ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-activity"></i></span>
                                    <select name="status" class="form-select">
                                        <?php
                                        $statuses = ['Active','Pregnant','Lactating','Dry','Culled'];
                                        foreach ($statuses as $status):
                                        ?>
                                            <option value="<?= $status ?>" <?= $sow['status'] === $status ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="4" 
                                          style="border-left: 1px solid #dee2e6;"><?= htmlspecialchars($sow['notes']) ?></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <hr class="my-4 text-muted opacity-25">
                                <div class="d-flex flex-column flex-md-row gap-2">
                                    <button type="submit" class="btn btn-primary btn-update shadow-sm">
                                        <i class="bi bi-save me-1"></i> Update Changes
                                    </button>
                                    <a href="list.php" class="btn btn-outline-secondary btn-update">
                                        Cancel
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
            
            <div class="text-center mt-3 text-muted small">
                Record created on: <?= date('d M Y', strtotime($sow['created_at'] ?? 'now')) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>