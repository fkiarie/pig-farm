<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
$tag_no = trim($_POST['tag_no'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? null;
$status = $_POST['status'] ?? 'Active';
$notes = trim($_POST['notes'] ?? '');

if ($id === 0 || empty($tag_no)) {
    die('Invalid request');
}

$sql = "
    UPDATE sows
    SET tag_no = ?, breed = ?, date_of_birth = ?, status = ?, notes = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssi",
    $tag_no,
    $breed,
    $date_of_birth,
    $status,
    $notes,
    $id
);

$stmt->execute();

header('Location: list.php');
exit;
