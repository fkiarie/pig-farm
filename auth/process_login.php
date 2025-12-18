<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email and password are required';
    header('Location: login.php');
    exit;
}

$sql = "SELECT id, name, email, password, role, is_active 
        FROM users WHERE email = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {

    if (!$user['is_active']) {
        $_SESSION['error'] = 'Account is disabled';
        header('Location: login.php');
        exit;
    }

    if (password_verify($password, $user['password'])) {

        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Update last login
        $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

        header('Location: ../index.php');
        exit;
    }
}

// Failed login
$_SESSION['error'] = 'Invalid login credentials';
header('Location: login.php');
exit;
