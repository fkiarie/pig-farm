<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        UPDATE farrowings
        SET farrowing_date=?, total_born=?, piglets_alive=?, stillbirths=?, notes=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "siiisi",
        $_POST['farrowing_date'],
        $_POST['total_born'],
        $_POST['piglets_alive'],
        $_POST['stillbirths'],
        $_POST['notes'],
        $id
    );
    $stmt->execute();

    header("Location: list.php");
    exit;
}

$data = $conn->query("SELECT * FROM farrowings WHERE id=$id")->fetch_assoc();
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">✏ Edit Farrowing</h1>

<form method="post">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Total Born</label>
            <input type="number" name="total_born" class="form-control" value="<?= $data['total_born'] ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label>Alive</label>
            <input type="number" name="piglets_alive" class="form-control" value="<?= $data['piglets_alive'] ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label>Stillbirths</label>
            <input type="number" name="stillbirths" class="form-control" value="<?= $data['stillbirths'] ?>">
        </div>
    </div>

    <div class="mb-3">
        <label>Farrowing Date</label>
        <input type="date" name="farrowing_date" class="form-control" value="<?= $data['farrowing_date'] ?>">
    </div>

    <div class="mb-3">
        <label>Notes</label>
        <textarea name="notes" class="form-control"><?= $data['notes'] ?></textarea>
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="list.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
