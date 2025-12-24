<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /pig-farm/auth/login.php');
    exit;
}
