<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');

$user = apiRequireCandidateUser();
$search = trim($_GET['search'] ?? '');

apiSuccess([
    'candidates' => searchCandidates($pdo, $user['id'], $search),
    'filters' => ['search' => $search],
]);
