<?php
require_once '../includes/candidate.php';

$userId = requireCandidateLogin();
$message = '';
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $candidateId = (int) ($_POST['candidate_id'] ?? 0);

    if ($action === 'track' && $candidateId > 0) {
        addTrackedCandidate($pdo, $userId, $candidateId);
        $message = t('candidate.others.success.tracked');
    }

    if ($action === 'untrack' && $candidateId > 0) {
        removeTrackedCandidate($pdo, $userId, $candidateId);
        $message = t('candidate.others.success.untracked');
    }
}

$results = searchCandidates($pdo, $userId, $search);
$trackedCandidates = fetchTrackedCandidates($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(currentLocale(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('candidate.others.title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="admin-page">
        <?php if ($message !== ''): ?>
            <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="admin-two-column">
            <article class="admin-panel">
                <div class="admin-panel__header">
                    <h2><?php echo htmlspecialchars(t('candidate.others.find_candidates'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <div class="candidate-panel-body">
                    <form class="admin-search" method="get">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(t('candidate.others.search_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="button button--primary" type="submit"><?php echo htmlspecialchars(t('admin.users.search'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>

                    <div class="candidate-results">
                        <?php if (empty($results)): ?>
                            <p class="admin-empty"><?php echo htmlspecialchars(t('candidate.others.no_candidates_found'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php foreach ($results as $row): ?>
                            <article class="candidate-result-card">
                                <div>
                                    <h3><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($row['specialty'] ?? t('candidate.common.no_specialty'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><?php echo htmlspecialchars(t('candidate.others.ranking_label'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars(isset($row['ranking']) ? (string) $row['ranking'] : t('candidate.common.not_available'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="candidate_id" value="<?php echo (int) $row['id']; ?>">
                                    <?php if ((int) $row['is_tracked'] === 1): ?>
                                        <input type="hidden" name="action" value="untrack">
                                        <button class="admin-link-button admin-link-button--danger" type="submit"><?php echo htmlspecialchars(t('candidate.others.untrack'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="track">
                                        <button class="admin-link-button" type="submit"><?php echo htmlspecialchars(t('candidate.others.track'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    <?php endif; ?>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="admin-panel">
                <div class="admin-panel__header">
                    <h2><?php echo htmlspecialchars(t('candidate.others.tracked_candidates'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <div class="candidate-panel-body">
                    <?php if (empty($trackedCandidates)): ?>
                        <p class="admin-empty"><?php echo htmlspecialchars(t('candidate.others.no_tracked_candidates'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <div class="candidate-results">
                        <?php foreach ($trackedCandidates as $tracked): ?>
                            <article class="candidate-result-card candidate-result-card--stacked">
                                <div>
                                    <h3><?php echo htmlspecialchars($tracked['first_name'] . ' ' . $tracked['last_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($tracked['specialty'] ?? t('candidate.common.no_specialty'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <div class="candidate-meta">
                                    <span class="candidate-badge"><?php echo htmlspecialchars($tracked['academic_year'] ?? t('candidate.common.no_year'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="candidate-badge candidate-badge--status"><?php echo htmlspecialchars($tracked['current_status'] ?? t('candidate.common.no_status'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <p class="candidate-timeline"><?php echo htmlspecialchars($tracked['timeline_note'] ?? t('candidate.others.no_timeline_notes'), ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="candidate-subtle">
                                    <?php echo htmlspecialchars($tracked['service_title'] ?? t('candidate.others.service_not_assigned'), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
