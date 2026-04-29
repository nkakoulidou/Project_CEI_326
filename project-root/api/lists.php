<?php

require_once __DIR__ . '/../includes/api.php';

apiRequireMethod('GET');

$sql = tableExists($pdo, 'services')
    ? 'SELECT
            lists.id,
            lists.academic_year,
            lists.publication_date,
            lists.status,
            lists.notes,
            services.id AS service_id,
            services.service_code,
            services.title AS service_title,
            services.category,
            services.district
       FROM lists
       INNER JOIN services ON services.id = lists.service_id
       ORDER BY lists.publication_date DESC, lists.id DESC'
    : 'SELECT
            lists.id,
            lists.academic_year,
            lists.publication_date,
            lists.status,
            lists.notes,
            lists.service_id,
            "" AS service_code,
            "" AS service_title,
            "" AS category,
            "" AS district
       FROM lists
       ORDER BY lists.publication_date DESC, lists.id DESC';

$lists = $pdo->query($sql)->fetchAll();

apiSuccess(['lists' => $lists]);
