<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        UPDATE weanings
        SET weaning_date = ?, piglets_weaned = ?, notes = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sisi",
        $_POST['weaning_date'],
        $_POST['piglets_weaned'],
        $_POST['notes'],
        $id
    );
    $stmt->execute();

    header("Location: list.php");
    exit;
}

$data = $conn->query("SELECT * FROM weanings WHERE id = $id")->fetch_assoc();
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">✏ Edit Weaning</h1>

<form method="post">
    <div class="mb-3">
        <label>Weaning Date</label>
        <input type="date" name="weaning_date" class="form-control"
               value="<?= $data['weaning_date'] ?>">
    </div>

    <div class="mb-3">
        <label>Piglets Weaned</label>
        <input type="number" name="piglets_weaned" class="form-control"
               value="<?= $data['piglets_weaned'] ?>">
    </div>

    <div class="mb-3">
        <label>Notes</label>
        <textarea name="notes" class="form-control"><?= $data['notes'] ?></textarea>
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="list.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
