<?php
require_once __DIR__ . '/../config/db.php';

$tag_no = trim($_POST['tag_no'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? null;
$status = $_POST['status'] ?? 'Active';
$notes = trim($_POST['notes'] ?? '');

if (empty($tag_no)) {
    die('Tag number is required');
}

$sql = "
    INSERT INTO sows (tag_no, breed, date_of_birth, status, notes)
    VALUES (?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssss",
    $tag_no,
    $breed,
    $date_of_birth,
    $status,
    $notes
);

$stmt->execute();

header('Location: list.php');
exit;
