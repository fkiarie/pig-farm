<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$breed = trim($_POST['breed']);
$date_of_birth = $_POST['date_of_birth'];
$status = $_POST['status'];
$notes = trim($_POST['notes']);

$stmt = $conn->prepare("
    UPDATE boars
    SET name=?, breed=?, date_of_birth=?, status=?, notes=?
    WHERE id=?
");

$stmt->bind_param(
    "sssssi",
    $name, $breed, $date_of_birth, $status, $notes, $id
);

$stmt->execute();

header('Location: list.php');
exit;
