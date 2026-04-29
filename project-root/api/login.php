<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('POST');

$input = apiInput();
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    apiError('Email and password are required.', 422);
}

$stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    apiError('Invalid credentials.', 401);
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = (string) $user['username'];
$_SESSION['role'] = (string) ($user['role'] ?? 'user');

$role = (string) ($_SESSION['role'] ?? 'user');
$redirect = $role === 'admin' ? '/admin/dashboard.php' : '/modules/candidate/dashboard.php';

apiSuccess([
    'message' => 'Login successful.',
    'user' => [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'email' => (string) $user['email'],
        'role' => $role,
    ],
    'redirect' => $redirect,
]);
