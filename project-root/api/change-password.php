<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('POST');

$user = apiRequireCandidateUser();
$input = apiInput();
$error = updateCandidatePassword($pdo, $user['id'], $input);

if ($error !== null) {
    apiError($error, 422);
}

apiSuccess(['message' => 'Password changed successfully.']);
