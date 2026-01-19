<?php
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .form-card { border: none; border-radius: 12px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; }
    .input-group-text { background-color: #f8f9fa; color: #6c757d; }
    .btn-save { padding: 0.6rem 2rem; border-radius: 8px; font-weight: 600; }
    .section-title { font-size: 1.1rem; font-weight: 700; color: #0d6efd; margin-bottom: 1.5rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
</style>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="list.php" class="text-decoration-none">Sows</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New Sow</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card form-card">
                <div class="card-body p-4">
                    
                    <div class="section-title">
                        <i class="bi bi-plus-circle-fill"></i> Sow Registration
                    </div>

                    <form method="POST" action="store.php" class="needs-validation">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tag Number / Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" name="tag_no" class="form-control" placeholder="e.g. SOW-001" required>
                                </div>
                                <div class="form-text small">Unique identifier for the sow.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Breed</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-info-circle"></i></span>
                                    <input type="text" name="breed" class="form-control" placeholder="e.g. Large White">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="date_of_birth" class="form-control">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Current Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-activity"></i></span>
                                    <select name="status" class="form-select">
                                        <option value="Active" selected>Active / Ready</option>
                                        <option value="Pregnant">Pregnant</option>
                                        <option value="Lactating">Lactating</option>
                                        <option value="Dry">Dry</option>
                                        <option value="Culled">Culled</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="4" placeholder="Mention health history, origins, or physical traits..."></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <hr class="my-4 text-muted opacity-25">
                                <div class="d-flex flex-column flex-md-row gap-2">
                                    <button type="submit" class="btn btn-primary btn-save shadow-sm">
                                        <i class="bi bi-check-lg me-1"></i> Save Sow Record
                                    </button>
                                    <a href="list.php" class="btn btn-outline-secondary btn-save">
                                        Cancel
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>