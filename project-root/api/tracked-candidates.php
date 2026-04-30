<?php

require_once __DIR__ . '/../includes/api.php';

$user = apiRequireCandidateUser();

if (apiMethod() === 'GET') {
    apiSuccess([
        'tracked_candidates' => fetchTrackedCandidates($pdo, $user['id']),
    ]);
}

$input = apiInput();
$candidateId = (int) ($input['candidate_id'] ?? $_GET['candidate_id'] ?? 0);

if ($candidateId <= 0) {
    apiError('candidate_id is required.', 422);
}

if (apiMethod() === 'POST') {
    addTrackedCandidate($pdo, $user['id'], $candidateId);
    apiSuccess([
        'message' => 'Candidate added to tracking list.',
        'tracked_candidates' => fetchTrackedCandidates($pdo, $user['id']),
    ]);
}

if (apiMethod() === 'DELETE') {
    removeTrackedCandidate($pdo, $user['id'], $candidateId);
    apiSuccess([
        'message' => 'Candidate removed from tracking list.',
        'tracked_candidates' => fetchTrackedCandidates($pdo, $user['id']),
    ]);
}

apiError('Method not allowed.', 405, ['allowed_methods' => ['GET', 'POST', 'DELETE']]);
