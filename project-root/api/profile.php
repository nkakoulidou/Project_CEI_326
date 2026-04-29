<?php

require_once __DIR__ . '/../includes/api.php';

$user = apiRequireCandidateUser();

if (apiMethod() === 'GET') {
    apiSuccess([
        'profile' => fetchCandidateProfile($pdo, $user['id']),
        'preferences' => fetchCandidatePreferences($pdo, $user['id']),
    ]);
}

apiRequireMethod('POST');

$input = apiInput();
$error = saveCandidateProfile($pdo, $user['id'], $input);

if ($error !== null) {
    apiError($error, 422);
}

apiSuccess([
    'message' => 'Profile updated successfully.',
    'profile' => fetchCandidateProfile($pdo, $user['id']),
    'preferences' => fetchCandidatePreferences($pdo, $user['id']),
]);
