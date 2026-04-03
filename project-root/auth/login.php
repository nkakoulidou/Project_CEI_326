<?php
session_start();
require_once '../includes/db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        $redirect = $user['role'] === 'admin'
            ? '../admin/dashboard.php'
            : '../modules/dashboard.php';

        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Incorrect login details.';
}

header('Location: ../index.php?form=login' . ($error !== '' ? '&login_error=1' : ''));
exit;
