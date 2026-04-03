<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$keyword = trim($_GET['keyword'] ?? '');
if ($keyword !== '') {
 $stmt = $pdo->prepare(
 'SELECT * FROM applications WHERE course_code LIKE :kw ORDER BY created_at DESC'
 );
 $stmt->execute([':kw' => '%' . $keyword . '%']);
} else {
 $stmt = $pdo->query('SELECT * FROM applications ORDER BY created_at DESC');
}
$rows = $stmt->fetchAll();
// foreach ($rows as $row) → htmlspecialchars($row['course_code']