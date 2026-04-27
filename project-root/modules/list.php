<?php
session_start();
require_once '../includes/navbar.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?form=login');
    exit;
}

$keyword = trim($_GET['keyword'] ?? '');
$sql = '
    SELECT
        applications.application_code,
        applications.current_status,
        applications.timeline_note,
        applications.submitted_at,
        lists.academic_year,
        services.title AS service_title,
        services.category,
        services.district
    FROM applications
    INNER JOIN lists ON lists.id = applications.list_id
    INNER JOIN services ON services.id = lists.service_id
    INNER JOIN candidates ON candidates.id = applications.candidate_id
    WHERE candidates.user_id = :user_id
';

$params = [':user_id' => $_SESSION['user_id']];

if ($keyword !== '') {
    $sql .= '
        AND (
            applications.application_code LIKE :keyword
            OR services.title LIKE :keyword
            OR services.category LIKE :keyword
            OR services.district LIKE :keyword
        )
    ';
    $params[':keyword'] = '%' . $keyword . '%';
}

$sql .= ' ORDER BY applications.submitted_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Applications</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="page-shell">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h1>Track My Applications</h1>
                    <p>Monitor the progress of your submitted applications and list placements.</p>
                </div>
            </div>

            <section class="admin-card">
                <form class="admin-search" method="get" action="">
                    <input
                        type="text"
                        name="keyword"
                        value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Search by application code, service title, category or district"
                    >
                    <button class="button button--primary" type="submit">Search</button>
                    <?php if ($keyword !== ''): ?>
                        <a class="button button--secondary" href="list.php">Clear</a>
                    <?php endif; ?>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Service</th>
                                <th>Category</th>
                                <th>District</th>
                                <th>Academic Year</th>
                                <th>Status</th>
                                <th>Timeline</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="7">No applications found for this account.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['application_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['district'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['academic_year'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['current_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['timeline_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
