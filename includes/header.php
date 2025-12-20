<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pig Farm Dashboard</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    
    <!-- Custom Dashboard CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/main/dashboard.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <?php require_once __DIR__ . '/topnav.php'; ?>

    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/sidenav.php'; ?>

    <!-- Mobile Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content Area -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">