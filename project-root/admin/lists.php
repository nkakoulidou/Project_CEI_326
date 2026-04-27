<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_specialty') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isSelected = isset($_POST['is_selected']) ? 1 : 0;

        if ($name === '') {
            $error = 'Specialty name is required.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO specialties (name, description, is_selected) VALUES (:name, :description, :is_selected)'
                );
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':is_selected' => $isSelected,
                ]);
                $message = 'Specialty added successfully.';
            } catch (PDOException $exception) {
                $error = 'This specialty already exists.';
            }
        }
    }

    if ($action === 'toggle_specialty') {
        $specialtyId = (int) ($_POST['specialty_id'] ?? 0);
        $pdo->prepare('UPDATE specialties SET is_selected = IF(is_selected = 1, 0, 1) WHERE id = :id')
            ->execute([':id' => $specialtyId]);
        $message = 'Specialty selection updated.';
    }

    if ($action === 'add_list') {
        $specialtyId = (int) ($_POST['specialty_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '');
        $publishedYear = (int) ($_POST['published_year'] ?? 0);
        $candidateCount = (int) ($_POST['candidate_count'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($specialtyId <= 0 || $title === '' || $publishedYear <= 0) {
            $error = 'Specialty, title and year are required for a list.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO committee_lists (specialty_id, title, source_url, published_year, candidate_count, notes)
                 VALUES (:specialty_id, :title, :source_url, :published_year, :candidate_count, :notes)'
            );
            $stmt->execute([
                ':specialty_id' => $specialtyId,
                ':title' => $title,
                ':source_url' => $sourceUrl !== '' ? $sourceUrl : null,
                ':published_year' => $publishedYear,
                ':candidate_count' => max(0, $candidateCount),
                ':notes' => $notes !== '' ? $notes : null,
            ]);
            $message = 'Committee list loaded successfully.';
        }
    }
}

$specialties = $pdo->query('SELECT * FROM specialties ORDER BY name ASC')->fetchAll();
$lists = [];

if (tableExists($pdo, 'committee_lists')) {
    $lists = $pdo->query(
        'SELECT committee_lists.*, specialties.name AS specialty_name
         FROM committee_lists
         INNER JOIN specialties ON specialties.id = committee_lists.specialty_id
         ORDER BY committee_lists.published_year DESC, committee_lists.created_at DESC'
    )->fetchAll();
}

adminPageStart('Manage Lists');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow">Manage Lists</p>
            <h1>Specialties And Available Tables</h1>
            <p>Select specialties and register the appointment lists that are available from the official committee source.</p>
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
                <h2>Add Specialty</h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="add_specialty">

                <label>
                    <span>Specialty Name</span>
                    <input type="text" name="name" required>
                </label>

                <label>
                    <span>Description</span>
                    <textarea name="description" rows="3" placeholder="Optional short description"></textarea>
                </label>

                <label class="admin-checkbox">
                    <input type="checkbox" name="is_selected" checked>
                    <span>Include this specialty in the active set</span>
                </label>

                <button class="button button--primary" type="submit">Save Specialty</button>
            </form>

            <div class="admin-list-block">
                <h3>Selected Specialties</h3>
                <?php if (empty($specialties)): ?>
                    <p class="admin-empty">No specialties available yet.</p>
                <?php endif; ?>

                <?php foreach ($specialties as $specialty): ?>
                    <div class="admin-list-item">
                        <div>
                            <strong><?php echo h($specialty['name']); ?></strong>
                            <p><?php echo h($specialty['description'] ?? 'No description provided.'); ?></p>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="toggle_specialty">
                            <input type="hidden" name="specialty_id" value="<?php echo (int) $specialty['id']; ?>">
                            <button class="admin-link-button" type="submit">
                                <?php echo (int) $specialty['is_selected'] === 1 ? 'Selected' : 'Inactive'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>Load Available List</h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="add_list">

                <label>
                    <span>Specialty</span>
                    <select name="specialty_id" required>
                        <option value="">Choose specialty</option>
                        <?php foreach ($specialties as $specialty): ?>
                            <option value="<?php echo (int) $specialty['id']; ?>"><?php echo h($specialty['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>List Title</span>
                    <input type="text" name="title" required>
                </label>

                <label>
                    <span>Source URL</span>
                    <input type="url" name="source_url" placeholder="https://www.eey.gov.cy/">
                </label>

                <label>
                    <span>Published Year</span>
                    <input type="number" name="published_year" min="2000" max="2100" value="<?php echo (int) date('Y'); ?>" required>
                </label>

                <label>
                    <span>Candidate Count</span>
                    <input type="number" name="candidate_count" min="0" value="0">
                </label>

                <label>
                    <span>Notes</span>
                    <textarea name="notes" rows="3" placeholder="Optional notes about the imported table"></textarea>
                </label>

                <button class="button button--primary" type="submit">Load List</button>
            </form>

            <div class="admin-table-wrap admin-table-wrap--compact">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Specialty</th>
                            <th>Year</th>
                            <th>Candidates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lists)): ?>
                            <tr>
                                <td colspan="4">No committee lists loaded yet.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($lists as $list): ?>
                            <tr>
                                <td><?php echo h($list['title']); ?></td>
                                <td><?php echo h($list['specialty_name']); ?></td>
                                <td><?php echo (int) $list['published_year']; ?></td>
                                <td><?php echo (int) $list['candidate_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>

<?php adminPageEnd(); ?>
