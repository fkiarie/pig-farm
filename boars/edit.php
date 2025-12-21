<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$result = $conn->query("SELECT * FROM boars WHERE id = $id");

if ($result->num_rows === 0) {
    die('Boar not found');
}

$boar = $result->fetch_assoc();
?>

<h2 class="mb-3">✏️ Edit Boar</h2>

<div class="card">
    <div class="card-body">
        <form method="POST" action="update.php">

            <input type="hidden" name="id" value="<?= $boar['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($boar['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Breed</label>
                <input type="text" name="breed" class="form-control"
                       value="<?= $boar['breed'] ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control"
                       value="<?= $boar['date_of_birth'] ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php
                    $statuses = ['Active','Resting','Sold','Inactive'];
                    foreach ($statuses as $status):
                    ?>
                        <option value="<?= $status ?>"
                            <?= $boar['status'] === $status ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control"
                          rows="3"><?= htmlspecialchars($boar['notes']) ?></textarea>
            </div>

            <button class="btn btn-success">Update</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
