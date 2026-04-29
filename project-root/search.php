<?php
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/db.php';

$q = trim((string) ($_GET['q'] ?? ''));
$specialty = trim((string) ($_GET['specialty'] ?? ''));
$results = [];
$hasFilters = ($q !== '' || $specialty !== '');

function firstLetter(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, 1);
    }

    return substr($value, 0, 1);
}

if ($hasFilters && hasDatabaseConnection() && tableExists($pdo, 'candidates')) {
    $where = [];
    $params = [];

    if ($q !== '') {
        $parts = preg_split('/\s+/', $q) ?: [];
        foreach ($parts as $idx => $part) {
            if ($part === '') {
                continue;
            }

            $key = ':name_' . $idx;
            $where[] = "(candidates.first_name LIKE {$key} OR candidates.last_name LIKE {$key})";
            $params[$key] = '%' . $part . '%';
        }
    }

    if ($specialty !== '') {
        $where[] = 'COALESCE(candidates.specialty, \'\') LIKE :specialty';
        $params[':specialty'] = '%' . $specialty . '%';
    }

    $sql = "
        SELECT
            candidates.first_name,
            candidates.last_name,
            candidates.specialty,
            candidates.district
        FROM candidates
    ";

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY candidates.first_name ASC, candidates.last_name ASC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(currentLocale(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('search.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="admin-page">
        <section class="search-public-title">
            <h1><?php echo htmlspecialchars(t('search.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo htmlspecialchars(t('search.form_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <form class="admin-form search-public-form" method="get">
                <label>
                    <span><?php echo htmlspecialchars(t('search.full_name_label'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(t('search.full_name_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>
                    <span><?php echo htmlspecialchars(t('search.specialty_label'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <input type="text" name="specialty" value="<?php echo htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(t('search.specialty_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <div class="admin-form__actions">
                    <button class="button button--primary" type="submit"><?php echo htmlspecialchars(t('search.search_button'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <a class="button button--secondary" href="search.php"><?php echo htmlspecialchars(t('candidate.common.clear'), ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </form>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo htmlspecialchars(t('search.results_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>

            <?php if ($hasFilters): ?>
                <div class="search-public-count">
                    <?php echo htmlspecialchars(t('search.results_count', ['count' => (string) count($results)]), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($hasFilters && !empty($results)): ?>
                <div class="search-public-grid">
                    <?php foreach ($results as $row): ?>
                        <?php
                        $firstName = trim((string) ($row['first_name'] ?? ''));
                        $lastName = trim((string) ($row['last_name'] ?? ''));
                        $fullName = trim($firstName . ' ' . $lastName);
                        $initials = strtoupper(firstLetter($firstName) . firstLetter($lastName));
                        ?>
                        <article class="search-public-card">
                            <div class="search-public-card__avatar" aria-hidden="true"><?php echo htmlspecialchars($initials !== '' ? $initials : '?', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="search-public-card__body">
                                <h3><?php echo htmlspecialchars($fullName !== '' ? $fullName : t('candidate.common.not_available'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><strong><?php echo htmlspecialchars(t('search.specialty_label'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($row['specialty'] ?: t('candidate.common.no_specialty'), ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong><?php echo htmlspecialchars(t('candidate.profile.district'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($row['district'] ?: t('candidate.common.not_available'), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
