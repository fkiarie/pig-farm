<?php
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    die('Invalid request');
}

$conn->query("DELETE FROM sows WHERE id = $id");

header('Location: list.php');
exit;
