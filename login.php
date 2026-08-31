<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, full_name, username, password, role, section, research_group FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: index.php?error=invalid');
    exit;
}

unset($user['password']);
$_SESSION['user'] = $user;

header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
exit;
?>
