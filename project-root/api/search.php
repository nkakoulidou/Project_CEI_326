<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');

$q = trim((string) ($_GET['q'] ?? $_GET['search'] ?? ''));
$specialty = trim((string) ($_GET['specialty'] ?? ''));

if (!hasDatabaseConnection()) {
    apiError($GLOBALS['dbConnectionError'] ?? 'Database connection unavailable.', 500);
}

if (!tableExists($pdo, 'candidates')) {
    apiSuccess([
        'candidates' => [],
        'filters' => [
            'q' => $q,
            'search' => $q,
            'specialty' => $specialty,
        ],
    ]);
}

$where = [];
$params = [];

if ($q !== '') {
    $parts = preg_split('/\s+/', $q) ?: [];

    foreach ($parts as $idx => $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        $key = ':name_' . $idx;
        $where[] = "(candidates.first_name LIKE {$key} OR candidates.last_name LIKE {$key})";
        $params[$key] = '%' . $part . '%';
    }
}

if ($specialty !== '') {
    $where[] = 'COALESCE(candidates.specialty, \'\') LIKE :specialty';
    $params[':specialty'] = '%' . $specialty . '%';
}

$sql = "
    SELECT
        candidates.first_name,
        candidates.last_name,
        candidates.specialty,
        candidates.district
    FROM candidates
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY candidates.first_name ASC, candidates.last_name ASC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$candidates = array_map(
    static function (array $row): array {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'specialty' => (string) ($row['specialty'] ?? ''),
            'district' => (string) ($row['district'] ?? ''),
        ];
    },
    $rows
);

apiSuccess([
    'candidates' => $candidates,
    'filters' => [
        'q' => $q,
        'search' => $q,
        'specialty' => $specialty,
    ],
]);
