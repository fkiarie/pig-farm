<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = (int) $_GET['id'];

$conn->query("DELETE FROM weanings WHERE id = $id");

header("Location: list.php");
exit;
