<?php

$envPath = dirname(__DIR__, 2) . '/.env';
$dbConnectionError = null;
$pdo = null;

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbName = $_ENV['DB_NAME'] ?? '';
    $dsn = 'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=utf8mb4';

    if (!empty($_ENV['DB_PORT'])) {
        $dsn .= ';port=' . $_ENV['DB_PORT'];
    }

    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USER'] ?? '',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $e) {
    $dbConnectionError = 'Database connection failed. Check your .env settings and make sure MySQL is running.';
}

if ($pdo instanceof PDO) {
    try {
        ensureCandidateModuleSchema($pdo);
    } catch (PDOException $e) {
        $dbConnectionError = 'Database schema update failed. Please check database tables/columns.';
    }
}

function hasDatabaseConnection(): bool
{
    global $pdo;

    return $pdo instanceof PDO;
}

function ensureCandidateModuleSchema(PDO $pdo): void
{
    if (tableExists($pdo, 'candidates')) {
        $candidateColumns = [
            'user_id' => 'ALTER TABLE candidates ADD COLUMN user_id INT NULL UNIQUE',
            'email' => 'ALTER TABLE candidates ADD COLUMN email VARCHAR(120) NULL',
            'phone' => 'ALTER TABLE candidates ADD COLUMN phone VARCHAR(30) NULL',
            'district' => 'ALTER TABLE candidates ADD COLUMN district VARCHAR(100) NULL',
            'birth_date' => 'ALTER TABLE candidates ADD COLUMN birth_date DATE NULL',
        ];

        foreach ($candidateColumns as $column => $sql) {
            if (!columnExists($pdo, 'candidates', $column)) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $exception) {
                    // Keep schema migration resilient across different legacy layouts.
                }
            }
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS candidate_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            notify_new_lists TINYINT(1) NOT NULL DEFAULT 1,
            notify_status_changes TINYINT(1) NOT NULL DEFAULT 1,
            notify_rank_updates TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_candidate_preferences_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        )'
    );

    if (tableExists($pdo, 'candidates') && tableExists($pdo, 'lists')) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                candidate_id INT NOT NULL,
                list_id INT NOT NULL,
                application_code VARCHAR(30) NOT NULL UNIQUE,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                current_status ENUM(\'submitted\', \'under_review\', \'approved\', \'rejected\', \'appointed\') NOT NULL DEFAULT \'submitted\',
                timeline_note VARCHAR(255),
                CONSTRAINT fk_applications_candidate
                    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_applications_list
                    FOREIGN KEY (list_id) REFERENCES lists(id)
                    ON DELETE CASCADE
            )'
        );

        if (
            tableExists($pdo, 'list_entries') &&
            columnExists($pdo, 'list_entries', 'candidate_id') &&
            columnExists($pdo, 'list_entries', 'list_id') &&
            columnExists($pdo, 'list_entries', 'created_at') &&
            columnExists($pdo, 'list_entries', 'status') &&
            columnExists($pdo, 'list_entries', 'remarks')
        ) {
            $pdo->exec(
                'INSERT IGNORE INTO applications (
                    candidate_id,
                    list_id,
                    application_code,
                    submitted_at,
                    current_status,
                    timeline_note
                )
                SELECT
                    list_entries.candidate_id,
                    list_entries.list_id,
                    CONCAT(\'APP-\', LPAD(list_entries.id, 6, \'0\')),
                    list_entries.created_at,
                    CASE list_entries.status
                        WHEN \'appointed\' THEN \'appointed\'
                        WHEN \'removed\' THEN \'rejected\'
                        WHEN \'pending\' THEN \'under_review\'
                        ELSE \'submitted\'
                    END,
                    list_entries.remarks
                FROM list_entries'
            );
        }
    }

    if (!tableExists($pdo, 'tracked_candidates')) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS tracked_candidates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                candidate_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tracked_candidates_user_candidate (user_id, candidate_id)
            )'
        );
    }

    if (tableExists($pdo, 'tracked_candidates') && tableExists($pdo, 'user_candidate_links')) {
        $pdo->exec(
            'INSERT IGNORE INTO tracked_candidates (user_id, candidate_id, created_at)
             SELECT user_id, candidate_id, created_at FROM user_candidate_links'
        );
    }
}

function tableExists(PDO $pdo, string $tableName): bool
{
    static $knownTables = [];

    if (array_key_exists($tableName, $knownTables)) {
        return $knownTables[$tableName];
    }

    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute([':table_name' => $tableName]);

    $knownTables[$tableName] = $stmt->fetchColumn() !== false;

    return $knownTables[$tableName];
}

function countRows(PDO $pdo, string $tableName): int
{
    if (!tableExists($pdo, $tableName)) {
        return 0;
    }

    return (int) $pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    static $knownColumns = [];
    $cacheKey = $tableName . '.' . $columnName;

    if (array_key_exists($cacheKey, $knownColumns)) {
        return $knownColumns[$cacheKey];
    }

    if (!tableExists($pdo, $tableName)) {
        $knownColumns[$cacheKey] = false;
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName,
    ]);

    $knownColumns[$cacheKey] = (int) $stmt->fetchColumn() > 0;

    return $knownColumns[$cacheKey];
}
