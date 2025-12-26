<?php
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'];

$conn->query("DELETE FROM farrowings WHERE id=$id");

header("Location: list.php");
exit;
