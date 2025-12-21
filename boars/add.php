<?php
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-3">➕ Add Boar</h2>

<div class="card">
    <div class="card-body">
        <form method="POST" action="store.php">

            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Breed</label>
                <input type="text" name="breed" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Active">Active</option>
                    <option value="Resting">Resting</option>
                    <option value="Sold">Sold</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>

            <button class="btn btn-success">Save</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
