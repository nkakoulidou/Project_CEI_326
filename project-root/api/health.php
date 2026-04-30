<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');

apiSuccess([
    'message' => 'API is running.',
    'database_connected' => hasDatabaseConnection(),
    'authenticated' => isset($_SESSION['user_id']),
]);
