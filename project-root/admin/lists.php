<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

// Keep Manage Lists self-contained and robust by ensuring required tables exist.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS specialties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS committee_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        specialty_id INT NOT NULL,
        title VARCHAR(180) NOT NULL,
        published_year INT NOT NULL,
        candidate_count INT NOT NULL DEFAULT 0,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_committee_lists_specialty
            FOREIGN KEY (specialty_id) REFERENCES specialties(id)
            ON DELETE CASCADE
    )'
);

$message = '';
$error = '';
$selectedListId = isset($_GET['details_list_id']) ? (int) $_GET['details_list_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_specialty') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = t('admin.lists.error.name_required');
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO specialties (name, description) VALUES (:name, :description)'
                );
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                ]);
                $message = t('admin.lists.success.added');
            } catch (PDOException $exception) {
                $error = t('admin.lists.error.duplicate');
            }
        }
    }

}

$specialties = $pdo->query(
    'SELECT id, name, description
     FROM specialties
     ORDER BY name ASC'
)->fetchAll();

$committeeLists = $pdo->query(
    'SELECT
        committee_lists.id,
        committee_lists.title,
        committee_lists.published_year,
        committee_lists.candidate_count,
        specialties.name AS specialty_name
     FROM committee_lists
     INNER JOIN specialties ON specialties.id = committee_lists.specialty_id
     ORDER BY committee_lists.created_at DESC, committee_lists.id DESC'
)->fetchAll();

$listDetailsRows = [];
$listDetailsError = '';
if ($selectedListId > 0) {
    if (!tableExists($pdo, 'list_entries')) {
        $listDetailsError = 'Ο πίνακας list_entries δεν υπάρχει.';
    } elseif (!tableExists($pdo, 'candidates')) {
        $listDetailsError = 'Ο πίνακας candidates δεν υπάρχει.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                list_entries.position,
                list_entries.total_score,
                list_entries.degree_date,
                list_entries.degree_grade,
                list_entries.service_points,
                list_entries.application_date,
                list_entries.notes,
                candidates.first_name,
                candidates.last_name,
                candidates.birth_date
             FROM list_entries
             INNER JOIN candidates ON candidates.id = list_entries.candidate_id
             WHERE list_entries.list_id = :list_id
             ORDER BY list_entries.position ASC, list_entries.id ASC'
        );
        $stmt->execute([':list_id' => $selectedListId]);
        $listDetailsRows = $stmt->fetchAll();
    }
}

adminPageStart('admin.lists.title');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <h1><?php echo h(t('admin.lists.heading')); ?></h1>
            <p><?php echo h(t('admin.lists.intro')); ?></p>
        </div>
    </section>

    <?php if ($message !== ''): ?>
        <div class="admin-alert admin-alert--success"><?php echo h($message); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="admin-alert admin-alert--error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <section class="admin-two-column">
        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo h(t('admin.lists.add_specialty')); ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="add_specialty">

                <label>
                    <span><?php echo h(t('admin.lists.specialty_name')); ?></span>
                    <input type="text" name="name" required>
                </label>

                <label>
                    <span><?php echo h(t('admin.lists.description')); ?></span>
                    <textarea name="description" rows="4" placeholder="<?php echo h(t('admin.lists.description_placeholder')); ?>"></textarea>
                </label>

                <button class="button button--primary" type="submit"><?php echo h(t('admin.lists.save_specialty')); ?></button>
            </form>

            <div class="admin-panel__header" style="margin-top:1.25rem;">
                <h2><?php echo h(t('admin.lists.selected_specialties')); ?></h2>
            </div>

            <?php if (empty($specialties)): ?>
                <p class="admin-empty" style="padding:0 1.25rem 1.25rem;"><?php echo h(t('admin.lists.no_specialties')); ?></p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo h(t('admin.lists.specialty_name')); ?></th>
                                <th><?php echo h(t('admin.lists.description')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($specialties as $specialty): ?>
                                <tr>
                                    <td><?php echo h($specialty['name']); ?></td>
                                    <td><?php echo h($specialty['description'] ?? t('admin.lists.no_description')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

    </section>

    <section class="admin-panel" style="margin-top:1.5rem;">
        <div class="admin-panel__header">
            <h2>Προβολή Στοιχείων Λίστας</h2>
        </div>
        <form class="admin-form" method="get" style="padding-bottom:0;">
            <label>
                <span>Λίστα</span>
                <select name="details_list_id" required>
                    <option value="">Επιλογή λίστας</option>
                    <?php foreach ($committeeLists as $list): ?>
                        <option value="<?php echo (int) $list['id']; ?>" <?php echo $selectedListId === (int) $list['id'] ? 'selected' : ''; ?>>
                            <?php echo h($list['title'] . ' - ' . $list['specialty_name'] . ' (' . $list['published_year'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="admin-form__actions">
                <button class="button button--secondary" type="submit">Προβολή</button>
            </div>
        </form>

        <?php if ($listDetailsError !== ''): ?>
            <div class="admin-alert admin-alert--error" style="margin:1rem 1.4rem 0;"><?php echo h($listDetailsError); ?></div>
        <?php endif; ?>

        <div class="admin-table-wrap" style="margin-top:1rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>A/A</th>
                        <th>Ονοματεπώνυμο</th>
                        <th>Σύνολο Μορίων</th>
                        <th>Ημερομηνία Τίτλου</th>
                        <th>Βαθμός Τίτλου</th>
                        <th>Μόρια Υπηρεσίας</th>
                        <th>Ημερομηνία Αίτησης</th>
                        <th>Ημερομηνία Γέννησης</th>
                        <th>Σημειώσεις</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($selectedListId <= 0): ?>
                        <tr>
                            <td colspan="9">Επίλεξε λίστα για να εμφανιστούν οι υποψήφιοι.</td>
                        </tr>
                    <?php elseif (empty($listDetailsRows)): ?>
                        <tr>
                            <td colspan="9">Δεν υπάρχουν υποψήφιοι για τη συγκεκριμένη λίστα.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($listDetailsRows as $row): ?>
                        <tr>
                            <td><?php echo (int) $row['position']; ?></td>
                            <td><?php echo h(trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''))); ?></td>
                            <td><?php echo h($row['total_score'] !== null ? (string) $row['total_score'] : '-'); ?></td>
                            <td><?php echo h($row['degree_date'] ? date('d/m/Y', strtotime($row['degree_date'])) : '-'); ?></td>
                            <td><?php echo h($row['degree_grade'] !== null ? (string) $row['degree_grade'] : '-'); ?></td>
                            <td><?php echo h($row['service_points'] !== null ? (string) $row['service_points'] : '-'); ?></td>
                            <td><?php echo h($row['application_date'] ? date('d/m/Y', strtotime($row['application_date'])) : '-'); ?></td>
                            <td><?php echo h($row['birth_date'] ? date('d/m/Y', strtotime($row['birth_date'])) : '-'); ?></td>
                            <td><?php echo h($row['notes'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php adminPageEnd(); ?>
