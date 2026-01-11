<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    die('Invalid activity ID');
}

$stmt = $conn->prepare("
    DELETE FROM daily_activities WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list.php?deleted=1");
exit;
