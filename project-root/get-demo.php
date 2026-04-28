<?php

$keyword = trim($_GET['keyword'] ?? '');
$year = (int) ($_GET['year'] ?? 0);
$role = trim($_GET['role'] ?? '');

if ($keyword === '' || $year === 0 || $role === '') {
    echo '';
    exit;
}

echo 'keyword:' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '<br>';
echo 'year:' . $year . '<br>';
echo 'role:' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '<br>';
