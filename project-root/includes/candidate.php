<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

function requireCandidateLogin(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
        header('Location: ../index.php?form=login');
        exit;
    }

    return (int) $_SESSION['user_id'];
}

function candidateTrackedTable(PDO $pdo): string
{
    if (tableExists($pdo, 'tracked_candidates')) {
        return 'tracked_candidates';
    }

    if (tableExists($pdo, 'user_candidate_links')) {
        return 'user_candidate_links';
    }

    return 'tracked_candidates';
}

function candidateApplicationsTable(PDO $pdo): ?string
{
    if (tableExists($pdo, 'applications')) {
        return 'applications';
    }

    if (tableExists($pdo, 'list_entries')) {
        return 'list_entries';
    }

    return null;
}

function candidateHasListMetadata(PDO $pdo): bool
{
    return tableExists($pdo, 'lists') && tableExists($pdo, 'services');
}

function candidateApplicationSource(PDO $pdo): array
{
    $applicationsTable = candidateApplicationsTable($pdo);

    if ($applicationsTable === 'applications') {
        return [
            'table' => $applicationsTable,
            'code' => "{$applicationsTable}.application_code",
            'status' => "{$applicationsTable}.current_status",
            'note' => "{$applicationsTable}.timeline_note",
            'submitted_at' => "{$applicationsTable}.submitted_at",
            'candidate_join' => "INNER JOIN candidates ON candidates.id = {$applicationsTable}.candidate_id",
            'list_join' => "LEFT JOIN {$applicationsTable} ON {$applicationsTable}.candidate_id = candidates.id",
            'list_id' => "{$applicationsTable}.list_id",
        ];
    }

    return [
        'table' => 'list_entries',
        'code' => "CONCAT('LIST-', list_entries.list_id, '-', list_entries.candidate_id)",
        'status' => 'list_entries.status',
        'note' => "COALESCE(list_entries.remarks, '')",
        'submitted_at' => 'list_entries.created_at',
        'candidate_join' => 'INNER JOIN candidates ON candidates.id = list_entries.candidate_id',
        'list_join' => 'LEFT JOIN list_entries ON list_entries.candidate_id = candidates.id',
        'list_id' => 'list_entries.list_id',
    ];
}

function candidateApplicationMetadataSelect(PDO $pdo): string
{
    if (!candidateHasListMetadata($pdo)) {
        return "
            '' AS academic_year,
            '' AS service_title,
            '' AS category,
            '' AS district
        ";
    }

    return "
        lists.academic_year,
        services.title AS service_title,
        services.category,
        services.district
    ";
}

function candidateApplicationMetadataJoins(PDO $pdo, array $source, bool $leftJoin = false): string
{
    if (!candidateHasListMetadata($pdo)) {
        return '';
    }

    $joinType = $leftJoin ? 'LEFT JOIN' : 'INNER JOIN';

    return "
        {$joinType} lists ON lists.id = {$source['list_id']}
        {$joinType} services ON services.id = lists.service_id
    ";
}

function candidateApplicationSearchClause(PDO $pdo, string $codeField): string
{
    if (!candidateHasListMetadata($pdo)) {
        return "AND {$codeField} LIKE :keyword";
    }

    return "
        AND (
            {$codeField} LIKE :keyword
            OR services.title LIKE :keyword
            OR services.category LIKE :keyword
            OR services.district LIKE :keyword
        )
    ";
}

function fetchCandidateSummary(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT first_name, specialty FROM candidates WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $candidate = $stmt->fetch() ?: [];

    $source = candidateApplicationSource($pdo);
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM {$source['table']}
         {$source['candidate_join']}
         WHERE candidates.user_id = :user_id"
    );
    $stmt->execute([':user_id' => $userId]);
    $applicationCount = (int) $stmt->fetchColumn();

    $trackedTable = candidateTrackedTable($pdo);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$trackedTable} WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $trackedCount = (int) $stmt->fetchColumn();

    return [
        'first_name' => $candidate['first_name'] ?? ($_SESSION['username'] ?? ''),
        'specialty' => $candidate['specialty'] ?? 'Not set',
        'application_count' => $applicationCount,
        'tracked_count' => $trackedCount,
    ];
}

function fetchCandidateProfile(PDO $pdo, int $userId): array
{
    $birthDateSelect = columnExists($pdo, 'candidates', 'birth_date')
        ? 'candidates.birth_date'
        : 'NULL AS birth_date';

    $stmt = $pdo->prepare(
        "SELECT
            users.username,
            users.email,
            candidates.first_name,
            candidates.last_name,
            {$birthDateSelect},
            candidates.specialty,
            candidates.ranking,
            candidates.phone,
            candidates.district
         FROM users
         LEFT JOIN candidates ON candidates.user_id = users.id
         WHERE users.id = :id
         LIMIT 1"
    );
    $stmt->execute([':id' => $userId]);

    return $stmt->fetch() ?: [];
}

function fetchCandidatePreferences(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT notify_new_lists, notify_status_changes, notify_rank_updates
         FROM candidate_preferences
         WHERE user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetch() ?: [
        'notify_new_lists' => 1,
        'notify_status_changes' => 1,
        'notify_rank_updates' => 0,
    ];
}

function saveCandidateProfile(PDO $pdo, int $userId, array $data): ?string
{
    $username = trim($data['username'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');

    if ($username === '' || $email === '' || $firstName === '' || $lastName === '') {
        return t('candidate.profile.error.required_names');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return t('candidate.profile.error.invalid_email');
    }

    $birthDate = trim((string) ($data['birth_date'] ?? ''));
    $birthDate = $birthDate !== '' ? $birthDate : null;
    $hasBirthDateColumn = columnExists($pdo, 'candidates', 'birth_date');
    $hasCandidateEmailColumn = columnExists($pdo, 'candidates', 'email');

    try {
        $stmt = $pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':id' => $userId,
        ]);
        $_SESSION['username'] = $username;

        $candidateData = [
            ':user_id' => $userId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':specialty' => trim($data['specialty'] ?? '') ?: null,
            ':phone' => trim($data['phone'] ?? '') ?: null,
            ':district' => trim($data['district'] ?? '') ?: null,
        ];
        if ($hasBirthDateColumn) {
            $candidateData[':birth_date'] = $birthDate;
        }
        if ($hasCandidateEmailColumn) {
            $candidateData[':email'] = $email;
        }

        $stmt = $pdo->prepare('SELECT id FROM candidates WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);

        if ($stmt->fetchColumn()) {
            if ($hasBirthDateColumn && $hasCandidateEmailColumn) {
                $stmt = $pdo->prepare(
                    'UPDATE candidates
                     SET first_name = :first_name, last_name = :last_name, birth_date = :birth_date, email = :email, specialty = :specialty, phone = :phone, district = :district
                     WHERE user_id = :user_id'
                );
            } elseif ($hasBirthDateColumn) {
                $stmt = $pdo->prepare(
                    'UPDATE candidates
                     SET first_name = :first_name, last_name = :last_name, birth_date = :birth_date, specialty = :specialty, phone = :phone, district = :district
                     WHERE user_id = :user_id'
                );
            } elseif ($hasCandidateEmailColumn) {
                $stmt = $pdo->prepare(
                    'UPDATE candidates
                     SET first_name = :first_name, last_name = :last_name, email = :email, specialty = :specialty, phone = :phone, district = :district
                     WHERE user_id = :user_id'
                );
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE candidates
                     SET first_name = :first_name, last_name = :last_name, specialty = :specialty, phone = :phone, district = :district
                     WHERE user_id = :user_id'
                );
            }
        } else {
            if ($hasBirthDateColumn && $hasCandidateEmailColumn) {
                $stmt = $pdo->prepare(
                    'INSERT INTO candidates (user_id, first_name, last_name, birth_date, email, specialty, phone, district)
                     VALUES (:user_id, :first_name, :last_name, :birth_date, :email, :specialty, :phone, :district)'
                );
            } elseif ($hasBirthDateColumn) {
                $stmt = $pdo->prepare(
                    'INSERT INTO candidates (user_id, first_name, last_name, birth_date, specialty, phone, district)
                     VALUES (:user_id, :first_name, :last_name, :birth_date, :specialty, :phone, :district)'
                );
            } elseif ($hasCandidateEmailColumn) {
                $stmt = $pdo->prepare(
                    'INSERT INTO candidates (user_id, first_name, last_name, email, specialty, phone, district)
                     VALUES (:user_id, :first_name, :last_name, :email, :specialty, :phone, :district)'
                );
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO candidates (user_id, first_name, last_name, specialty, phone, district)
                     VALUES (:user_id, :first_name, :last_name, :specialty, :phone, :district)'
                );
            }
        }
        $stmt->execute($candidateData);

        $stmt = $pdo->prepare(
            'INSERT INTO candidate_preferences (user_id, notify_new_lists, notify_status_changes, notify_rank_updates)
             VALUES (:user_id, :notify_new_lists, :notify_status_changes, :notify_rank_updates)
             ON DUPLICATE KEY UPDATE
                notify_new_lists = VALUES(notify_new_lists),
                notify_status_changes = VALUES(notify_status_changes),
                notify_rank_updates = VALUES(notify_rank_updates)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':notify_new_lists' => isset($data['notify_new_lists']) ? 1 : 0,
            ':notify_status_changes' => isset($data['notify_status_changes']) ? 1 : 0,
            ':notify_rank_updates' => isset($data['notify_rank_updates']) ? 1 : 0,
        ]);
    } catch (PDOException $exception) {
        return t('candidate.profile.error.username_or_email_in_use');
    }

    return null;
}

function updateCandidatePassword(PDO $pdo, int $userId, array $data): ?string
{
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $passwordHash = $stmt->fetchColumn();

    if (!$passwordHash || !password_verify($data['current_password'] ?? '', $passwordHash)) {
        return t('candidate.profile.error.current_password');
    }

    if (strlen($data['new_password'] ?? '') < 8) {
        return t('candidate.profile.error.password_length');
    }

    if (($data['new_password'] ?? '') !== ($data['confirm_password'] ?? '')) {
        return t('candidate.profile.error.password_mismatch');
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        ':password_hash' => password_hash($data['new_password'], PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    return null;
}

function fetchCandidateApplications(PDO $pdo, int $userId, string $keyword = ''): array
{
    if (candidateApplicationsTable($pdo) === null) {
        return [];
    }

    $source = candidateApplicationSource($pdo);
    $sql = "
        SELECT
            {$source['code']} AS application_code,
            {$source['status']} AS current_status,
            {$source['note']} AS timeline_note,
            {$source['submitted_at']} AS submitted_at,
            " . candidateApplicationMetadataSelect($pdo) . "
        FROM {$source['table']}
        {$source['candidate_join']}
        " . candidateApplicationMetadataJoins($pdo, $source) . "
        WHERE candidates.user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if ($keyword !== '') {
        $sql .= candidateApplicationSearchClause($pdo, $source['code']);
        $params[':keyword'] = '%' . $keyword . '%';
    }

    $sql .= " ORDER BY {$source['submitted_at']} DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function searchCandidates(PDO $pdo, int $userId, string $search = ''): array
{
    $trackedTable = candidateTrackedTable($pdo);
    $params = [':user_id' => $userId];
    $searchSql = '';

    if ($search !== '') {
        $searchSql = '
            AND (
                candidates.first_name LIKE :keyword
                OR candidates.last_name LIKE :keyword
                OR COALESCE(candidates.specialty, \'\') LIKE :keyword
            )
        ';
        $params[':keyword'] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare(
        "SELECT
            candidates.id,
            candidates.first_name,
            candidates.last_name,
            candidates.specialty,
            candidates.ranking,
            CASE WHEN tracked.candidate_id IS NULL THEN 0 ELSE 1 END AS is_tracked
         FROM candidates
         LEFT JOIN {$trackedTable} AS tracked
            ON tracked.candidate_id = candidates.id
            AND tracked.user_id = :user_id
         WHERE candidates.user_id IS NULL OR candidates.user_id <> :user_id
         {$searchSql}
         ORDER BY candidates.first_name ASC, candidates.last_name ASC
         LIMIT 25"
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetchTrackedCandidates(PDO $pdo, int $userId): array
{
    if (candidateApplicationsTable($pdo) === null) {
        return [];
    }

    $trackedTable = candidateTrackedTable($pdo);
    $source = candidateApplicationSource($pdo);
    $metadataSelect = candidateHasListMetadata($pdo)
        ? 'services.title AS service_title, lists.academic_year'
        : "'' AS service_title, '' AS academic_year";
    $sql = "
        SELECT
            candidates.first_name,
            candidates.last_name,
            candidates.specialty,
            {$source['status']} AS current_status,
            {$source['note']} AS timeline_note,
            {$metadataSelect}
         FROM {$trackedTable} AS tracked
         INNER JOIN candidates ON candidates.id = tracked.candidate_id
         {$source['list_join']}
         " . candidateApplicationMetadataJoins($pdo, $source, true) . "
         WHERE tracked.user_id = :user_id
         ORDER BY tracked.created_at DESC, {$source['submitted_at']} DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll();
}

function addTrackedCandidate(PDO $pdo, int $userId, int $candidateId): void
{
    $trackedTable = candidateTrackedTable($pdo);
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO {$trackedTable} (user_id, candidate_id) VALUES (:user_id, :candidate_id)"
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':candidate_id' => $candidateId,
    ]);
}

function removeTrackedCandidate(PDO $pdo, int $userId, int $candidateId): void
{
    $trackedTable = candidateTrackedTable($pdo);
    $stmt = $pdo->prepare(
        "DELETE FROM {$trackedTable} WHERE user_id = :user_id AND candidate_id = :candidate_id"
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':candidate_id' => $candidateId,
    ]);
}
