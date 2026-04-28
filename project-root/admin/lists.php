<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$message = '';
$error = '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? '');
        $publicationDate = trim($_POST['publication_date'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $notes = trim($_POST['notes'] ?? '');

        $allowedStatuses = ['draft', 'published', 'archived'];
        if ($serviceId <= 0 || $academicYear === '' || $publicationDate === '') {
            $error = 'Service, academic year and publication date are required.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = 'Invalid list status selected.';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO lists (service_id, academic_year, publication_date, status, notes)
                     VALUES (:service_id, :academic_year, :publication_date, :status, :notes)'
                );
                $stmt->execute([
                    ':service_id' => $serviceId,
                    ':academic_year' => $academicYear,
                    ':publication_date' => $publicationDate,
                    ':status' => $status,
                    ':notes' => $notes !== '' ? $notes : null,
                ]);
                $message = 'List created successfully.';
            } else {
                $listId = (int) ($_POST['list_id'] ?? 0);
                $stmt = $pdo->prepare(
                    'UPDATE lists
                     SET service_id = :service_id, academic_year = :academic_year, publication_date = :publication_date,
                         status = :status, notes = :notes
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':service_id' => $serviceId,
                    ':academic_year' => $academicYear,
                    ':publication_date' => $publicationDate,
                    ':status' => $status,
                    ':notes' => $notes !== '' ? $notes : null,
                    ':id' => $listId,
                ]);
                $message = 'List updated successfully.';
                $editId = 0;
            }
        }
    }

    if ($action === 'delete') {
        $listId = (int) ($_POST['list_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM lists WHERE id = :id');
        $stmt->execute([':id' => $listId]);
        $message = 'List deleted successfully.';
        $editId = 0;
    }
}

$services = $pdo->query('SELECT id, title FROM services ORDER BY title ASC')->fetchAll();

$editList = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, service_id, academic_year, publication_date, status, notes FROM lists WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editList = $stmt->fetch();
}

$stmt = $pdo->query(
    'SELECT
        lists.id,
        lists.academic_year,
        lists.publication_date,
        lists.status,
        lists.notes,
        services.title AS service_title,
        services.category,
        services.district
     FROM lists
     INNER JOIN services ON services.id = lists.service_id
     ORDER BY lists.publication_date DESC, lists.id DESC'
);
$lists = $stmt->fetchAll();

adminPageStart('Manage Lists');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow">Manage Lists</p>
            <h1>Appointment Lists</h1>
            <p>Create, update and review the published appointment lists for each service.</p>
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
                <h2><?php echo $editList ? 'Update List' : 'Create List'; ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="<?php echo $editList ? 'update' : 'create'; ?>">
                <?php if ($editList): ?>
                    <input type="hidden" name="list_id" value="<?php echo (int) $editList['id']; ?>">
                <?php endif; ?>

                <label>
                    <span>Service</span>
                    <select name="service_id" required>
                        <option value="">Select service</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo (int) $service['id']; ?>" <?php echo ((int) ($editList['service_id'] ?? 0) === (int) $service['id']) ? 'selected' : ''; ?>>
                                <?php echo h($service['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>Academic Year</span>
                    <input type="text" name="academic_year" value="<?php echo h($editList['academic_year'] ?? ''); ?>" placeholder="2025-2026" required>
                </label>

                <label>
                    <span>Publication Date</span>
                    <input type="date" name="publication_date" value="<?php echo h($editList['publication_date'] ?? ''); ?>" required>
                </label>

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="draft" <?php echo (($editList['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo (($editList['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo (($editList['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </label>

                <label>
                    <span>Notes</span>
                    <textarea name="notes" rows="4" placeholder="Optional notes"><?php echo h($editList['notes'] ?? ''); ?></textarea>
                </label>

                <div class="admin-form__actions">
                    <button class="button button--primary" type="submit"><?php echo $editList ? 'Save Changes' : 'Create List'; ?></button>
                    <?php if ($editList): ?>
                        <a class="button button--secondary" href="<?php echo h(getProjectBasePath() . '/admin/lists.php'); ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>Existing Lists</h2>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Category</th>
                            <th>District</th>
                            <th>Academic Year</th>
                            <th>Publication Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lists)): ?>
                            <tr>
                                <td colspan="7">No lists found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($lists as $list): ?>
                            <tr>
                                <td><?php echo h($list['service_title']); ?></td>
                                <td><?php echo h($list['category']); ?></td>
                                <td><?php echo h($list['district']); ?></td>
                                <td><?php echo h($list['academic_year']); ?></td>
                                <td><?php echo h(date('d/m/Y', strtotime($list['publication_date']))); ?></td>
                                <td><?php echo h(ucfirst($list['status'])); ?></td>
                                <td class="admin-table__actions">
                                    <a class="admin-link-button" href="<?php echo h(getProjectBasePath() . '/admin/lists.php?edit=' . (int) $list['id']); ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this list?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="list_id" value="<?php echo (int) $list['id']; ?>">
                                        <button class="admin-link-button admin-link-button--danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>

<?php adminPageEnd(); ?>
