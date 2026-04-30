<?php

require_once __DIR__ . '/candidate.php';
require_once __DIR__ . '/admin.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function apiMethod(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

function apiInput(): array
{
    static $input = null;

    if ($input !== null) {
        return $input;
    }

    if (!empty($_POST)) {
        $input = $_POST;
        return $input;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        $input = [];
        return $input;
    }

    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];

    return $input;
}

function apiResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiSuccess(array $data = [], int $status = 200): never
{
    apiResponse(array_merge(['success' => true], $data), $status);
}

function apiError(string $message, int $status = 400, array $extra = []): never
{
    apiResponse(array_merge(['success' => false, 'message' => $message], $extra), $status);
}

function apiRequireMethod(string ...$allowedMethods): void
{
    if (!in_array(apiMethod(), $allowedMethods, true)) {
        apiError('Method not allowed.', 405, ['allowed_methods' => $allowedMethods]);
    }
}

function apiIsBrowserNavigation(): bool
{
    $mode = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
    $destination = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    if ($mode === 'navigate') {
        return true;
    }

    if ($destination === 'document') {
        return true;
    }

    return str_contains($accept, 'text/html');
}

function apiRedirectToLogin(): never
{
    $basePath = function_exists('getProjectBasePath') ? getProjectBasePath() : '';
    header('Location: ' . $basePath . '/index.php?form=login');
    exit;
}

function apiCurrentUser(): array
{
    if (!isset($_SESSION['user_id'])) {
        if (apiMethod() === 'GET' && apiIsBrowserNavigation()) {
            apiRedirectToLogin();
        }

        apiError('Authentication required.', 401);
    }

    $userId = (int) $_SESSION['user_id'];
    $username = isset($_SESSION['username']) ? (string) $_SESSION['username'] : '';
    $role = isset($_SESSION['role']) ? (string) $_SESSION['role'] : '';

    if (($username === '' || $role === '') && $GLOBALS['pdo'] instanceof PDO) {
        $stmt = $GLOBALS['pdo']->prepare('SELECT username, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $record = $stmt->fetch();

        if (!$record) {
            if (apiMethod() === 'GET' && apiIsBrowserNavigation()) {
                apiRedirectToLogin();
            }

            apiError('Authentication required.', 401);
        }

        $username = $username !== '' ? $username : (string) ($record['username'] ?? '');
        $role = $role !== '' ? $role : (string) ($record['role'] ?? '');

        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
    }

    if ($username === '') {
        if (apiMethod() === 'GET' && apiIsBrowserNavigation()) {
            apiRedirectToLogin();
        }

        apiError('Authentication required.', 401);
    }

    return [
        'id' => $userId,
        'username' => $username,
        'role' => $role,
    ];
}

function apiRequireCandidateUser(): array
{
    $user = apiCurrentUser();

    if ($user['role'] !== 'user') {
        apiError('Candidate access required.', 403);
    }

    return $user;
}

function apiRequireAdminUser(): array
{
    $user = apiCurrentUser();

    if ($user['role'] !== 'admin') {
        apiError('Admin access required.', 403);
    }

    return $user;
}
