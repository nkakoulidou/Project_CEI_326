<?php
require_once '../includes/candidate.php';

$userId = requireCandidateLogin();
$keyword = trim($_GET['keyword'] ?? '');
$rows = fetchCandidateApplications($pdo, $userId, $keyword);

$statusLabels = [
    'submitted' => t('candidate.status.submitted'),
    'under_review' => t('candidate.status.under_review'),
    'approved' => t('candidate.status.approved'),
    'rejected' => t('candidate.status.rejected'),
    'appointed' => t('candidate.status.appointed'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(currentLocale(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('candidate.applications.title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="admin-page">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h1><?php echo htmlspecialchars(t('candidate.applications.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p><?php echo htmlspecialchars(t('candidate.applications.intro'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <section class="candidate-panel-body">
                <div class="admin-stats-grid candidate-stats-grid">
                    <article class="admin-stat-card">
                        <span><?php echo htmlspecialchars(t('candidate.applications.total_applications'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <strong><?php echo count($rows); ?></strong>
                    </article>
                    <article class="admin-stat-card">
                        <span><?php echo htmlspecialchars(t('candidate.applications.latest_status'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <strong class="admin-stat-card__compact">
                            <?php echo htmlspecialchars($statusLabels[$rows[0]['current_status'] ?? ''] ?? t('candidate.common.no_status'), ENT_QUOTES, 'UTF-8'); ?>
                        </strong>
                    </article>
                </div>

                <form class="admin-search" method="get" action="">
                    <input
                        type="text"
                        name="keyword"
                        value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="<?php echo htmlspecialchars(t('candidate.applications.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <button class="button button--primary" type="submit"><?php echo htmlspecialchars(t('admin.users.search'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <?php if ($keyword !== ''): ?>
                        <a class="button button--secondary" href="list.php"><?php echo htmlspecialchars(t('candidate.common.clear'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_application'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_service'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_category'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_district'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_academic_year'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('candidate.applications.col_timeline'), ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="7"><?php echo htmlspecialchars(t('candidate.applications.no_results'), ENT_QUOTES, 'UTF-8'); ?></td>
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
                            <p class="candidate-timeline"><?php echo htmlspecialchars($row['timeline_note'] ?: t('candidate.applications.default_timeline_note'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="candidate-subtle">
                                <?php echo htmlspecialchars(t('candidate.applications.submitted_on'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['submitted_at'])), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
