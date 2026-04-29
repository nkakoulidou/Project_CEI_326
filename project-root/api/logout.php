<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('POST');
apiCurrentUser();

session_unset();
session_destroy();

apiSuccess(['message' => 'Logout successful.']);
