<?php

require_once __DIR__ . '/includes/navbar.php';

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
$locale = is_array($data) ? (string) ($data['locale'] ?? '') : '';

if (!in_array($locale, supportedLocales(), true)) {
    http_response_code(422);
    echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

setAppLocale($locale);

echo json_encode([
    'success' => true,
    'locale' => currentLocale(),
    'translations' => translations(),
], JSON_UNESCAPED_UNICODE);
