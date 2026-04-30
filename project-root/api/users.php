<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');
apiRequireAdminUser();

$search = trim($_GET['search'] ?? '');
$sql = '
    SELECT
        users.id,
        users.username,
        users.email,
        users.role,
        users.status,
        users.created_at,
        COUNT(candidates.id) AS candidate_records
    FROM users
    LEFT JOIN candidates ON candidates.user_id = users.id
';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE users.username LIKE :search OR users.email LIKE :search ';
    $params[':search'] = '%' . $search . '%';
}

$sql .= ' GROUP BY users.id, users.username, users.email, users.role, users.status, users.created_at
          ORDER BY users.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

apiSuccess([
    'users' => $stmt->fetchAll(),
    'filters' => ['search' => $search],
]);
