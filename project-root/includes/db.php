<?php

$envPath = dirname(__DIR__, 2) . '/.env';

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
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') .
        ';dbname=' . ($_ENV['DB_NAME'] ?? '') .
        ';charset=utf8mb4',
        $_ENV['DB_USER'] ?? '',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed.');
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
