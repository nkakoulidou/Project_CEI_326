<?php
require_once '../includes/db.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $username = trim($_POST['username'] ?? '');
 $email = trim($_POST['email'] ?? '');
 $password = $_POST['password'] ?? '';
 $confirm = $_POST['confirm'] ?? '';
 if ($username === '') $errors[] = 'Username υποχρεωτικό.';
 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ȃη έγκυρο email.';
 if (strlen($password) < 8) $errors[] = 'Κωδικός τουλάχιστον 8 χαρακτήρες.';
 if ($password !== $confirm) $errors[] = 'Οι κωδικοί δεν ταιριάζουν.';
 if (empty($errors)) {
 $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
 $stmt->execute([':e' => $email]);
 if ($stmt->fetch()) $errors[] = 'Το email χρησιμοποιείται ήδη.';
 }
 if (empty($errors)) {
 $hash = password_hash($password, PASSWORD_DEFAULT);
 $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash) VALUES (:u,:e,:h)');
 $stmt->execute([':u'=>$username, ':e'=>$email, ':h'=>$hash]);
 header('Location: login.php?registered=1'); exit;
 }
}