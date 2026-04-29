<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');

$user = apiRequireCandidateUser();
$keyword = trim($_GET['search'] ?? $_GET['keyword'] ?? '');

apiSuccess([
    'applications' => fetchCandidateApplications($pdo, $user['id'], $keyword),
    'filters' => ['search' => $keyword],
]);
