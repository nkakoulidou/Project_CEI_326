<?php
require_once '../includes/candidate.php';

$userId = requireCandidateLogin();
$keyword = trim($_GET['keyword'] ?? '');
$rows = fetchCandidateApplications($pdo, $userId, $keyword);

$statusLabels = [
    'submitted' => 'Submitted',
    'under_review' => 'Under Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'appointed' => 'Appointed',
];
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

    <main class="admin-page">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h1>Track My Applications</h1>
                
                </div>
            </div>

            <section class="candidate-panel-body">
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
                                    <td><?php echo htmlspecialchars($statusLabels[$row['current_status']] ?? $row['current_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['timeline_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="candidate-results">
                    <?php foreach ($rows as $row): ?>
                        <article class="candidate-result-card candidate-result-card--stacked">
                            <div class="candidate-meta">
                                <span class="candidate-badge"><?php echo htmlspecialchars($row['application_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="candidate-badge candidate-badge--status"><?php echo htmlspecialchars($statusLabels[$row['current_status']] ?? $row['current_status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($row['service_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="candidate-subtle">
                                <?php echo htmlspecialchars($row['category'] . ' | ' . $row['district'] . ' | ' . $row['academic_year'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p class="candidate-timeline"><?php echo htmlspecialchars($row['timeline_note'] ?: 'Your application is being monitored by the system. Updates will appear here as the status changes.', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="candidate-subtle">
                                Submitted on <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['submitted_at'])), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
