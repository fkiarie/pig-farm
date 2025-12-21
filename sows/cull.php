<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    die('Invalid request');
}

$sql = "UPDATE sows SET status = 'Culled' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: list.php');
exit;
