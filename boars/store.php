<?php
require_once __DIR__ . '/../config/db.php';

$name = trim($_POST['name'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? null;
$status = $_POST['status'] ?? 'Active';
$notes = trim($_POST['notes'] ?? '');

if ($name === '') {
    die('Tag number is required');
}

$stmt = $conn->prepare("
    INSERT INTO boars (name, breed, date_of_birth, status, notes)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("sssss",
    $name, $breed, $date_of_birth, $status, $notes
);

$stmt->execute();

header('Location: list.php');
exit;
