<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$message = '';
$error = '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $password = $_POST['password'] ?? '';

    if ($action === 'create' || $action === 'update') {
        if ($username === '' || $email === '') {
            $error = t('admin.users.error.required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('admin.users.error.valid_email');
        } elseif ($action === 'create' && strlen($password) < 8) {
            $error = t('admin.users.error.password_length');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)'
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => $role,
                    ]);
                    $message = t('admin.users.success.created');
                } else {
                    $userId = (int) ($_POST['user_id'] ?? 0);
                    if ($password !== '') {
                        $stmt = $pdo->prepare(
                            'UPDATE users SET username = :username, email = :email, role = :role, password_hash = :password_hash WHERE id = :id'
                        );
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':role' => $role,
                            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            ':id' => $userId,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id'
                        );
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':role' => $role,
                            ':id' => $userId,
                        ]);
                    }
                    $message = t('admin.users.success.updated');
                    $editId = 0;
                }
            } catch (PDOException $exception) {
                $error = t('admin.users.error.duplicate');
            }
        }
    }

    if ($action === 'delete') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === (int) ($_SESSION['user_id'] ?? 0)) {
            $error = t('admin.users.error.self_delete');
        } else {
            $pdo->prepare('DELETE FROM candidates WHERE user_id = :user_id')->execute([':user_id' => $userId]);
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
            $message = t('admin.users.success.deleted');
        }
    }

    if ($action === 'link_candidate') {
        $linkUserId = (int) ($_POST['link_user_id'] ?? 0);
        $candidateEmail = strtolower(trim((string) ($_POST['candidate_email'] ?? '')));

        if ($linkUserId <= 0 || $candidateEmail === '') {
            $error = t('admin.users.link.error.required');
        } elseif (!filter_var($candidateEmail, FILTER_VALIDATE_EMAIL)) {
            $error = t('admin.users.link.error.valid_email');
        } elseif (!columnExists($pdo, 'candidates', 'user_id')) {
            $error = t('admin.users.link.error.schema');
        } else {
            $userStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $userStmt->execute([':id' => $linkUserId]);
            $targetUser = $userStmt->fetch();

            $emailConditions = [];
            if (columnExists($pdo, 'candidates', 'email')) {
                $emailConditions[] = "LOWER(TRIM(COALESCE(candidates.email, ''))) = :email";
            }
            $emailConditions[] = "LOWER(TRIM(COALESCE(candidate_users.email, ''))) = :email";
            $emailConditions[] = "LOWER(TRIM(COALESCE(legacy_users.email, ''))) = :email";

            $candidateStmt = $pdo->prepare(
                'SELECT candidates.id, candidates.user_id
                 FROM candidates
                 LEFT JOIN users AS candidate_users ON candidate_users.id = candidates.user_id
                 LEFT JOIN user_candidate_links ON user_candidate_links.candidate_id = candidates.id
                 LEFT JOIN users AS legacy_users ON legacy_users.id = user_candidate_links.user_id
                 WHERE ' . implode(' OR ', $emailConditions) . '
                 LIMIT 1'
            );
            $candidateStmt->execute([':email' => $candidateEmail]);
            $candidate = $candidateStmt->fetch();

            if (!$targetUser || !$candidate) {
                $error = t('admin.users.link.error.not_found');
            } else {
                $existingLinkStmt = $pdo->prepare('SELECT id FROM candidates WHERE user_id = :user_id LIMIT 1');
                $existingLinkStmt->execute([':user_id' => $linkUserId]);
                $existingForUser = $existingLinkStmt->fetch();

                if ($candidate['user_id'] !== null && (int) $candidate['user_id'] !== $linkUserId) {
                    $error = t('admin.users.link.error.candidate_already_linked');
                } elseif ($existingForUser && (int) $existingForUser['id'] !== (int) $candidate['id']) {
                    $error = t('admin.users.link.error.user_already_linked');
                } else {
                    $updateStmt = $pdo->prepare('UPDATE candidates SET user_id = :user_id WHERE id = :id');
                    $updateStmt->execute([
                        ':user_id' => $linkUserId,
                        ':id' => (int) $candidate['id'],
                    ]);
                    $message = t('admin.users.link.success');
                }
            }
        }
    }
}

$editUser = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editUser = $stmt->fetch();
}

$query = '
    SELECT
        users.id,
        users.username,
        users.email,
        users.role,
        users.created_at,
        COUNT(candidates.id) AS candidate_records
    FROM users
    LEFT JOIN candidates ON candidates.user_id = users.id
';

$params = [];
if ($search !== '') {
    $query .= ' WHERE users.username LIKE :search OR users.email LIKE :search ';
    $params[':search'] = '%' . $search . '%';
}

$query .= ' GROUP BY users.id, users.username, users.email, users.role, users.created_at ORDER BY users.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
$userOptions = $pdo->query("SELECT id, username, email FROM users WHERE role = 'user' ORDER BY username ASC")->fetchAll();

adminPageStart('admin.users.title');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <h1><?php echo h(t('admin.users.heading')); ?></h1>
            <p><?php echo h(t('admin.users.intro')); ?></p>
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
                <h2><?php echo $editUser ? h(t('admin.users.update_user')) : h(t('admin.users.add_user')); ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo (int) $editUser['id']; ?>">
                <?php endif; ?>

                <label>
                    <span><?php echo h(t('home.username')); ?></span>
                    <input type="text" name="username" value="<?php echo h($editUser['username'] ?? ''); ?>" required>
                </label>

                <label>
                    <span><?php echo h(t('home.email')); ?></span>
                    <input type="email" name="email" value="<?php echo h($editUser['email'] ?? ''); ?>" required>
                </label>

                <label>
                    <span><?php echo h(t('admin.users.role')); ?></span>
                    <select name="role">
                        <option value="user" <?php echo (($editUser['role'] ?? 'user') === 'user') ? 'selected' : ''; ?>><?php echo h(t('admin.users.role_user')); ?></option>
                        <option value="admin" <?php echo (($editUser['role'] ?? '') === 'admin') ? 'selected' : ''; ?>><?php echo h(t('admin.users.role_admin')); ?></option>
                    </select>
                </label>

                <label>
                    <span><?php echo $editUser ? h(t('admin.users.new_password_optional')) : h(t('admin.users.password')); ?></span>
                    <input type="password" name="password" <?php echo $editUser ? '' : 'required minlength="8"'; ?>>
                </label>

                <div class="admin-form__actions">
                    <button class="button button--primary" type="submit"><?php echo $editUser ? h(t('admin.users.save_changes')) : h(t('admin.users.create_user')); ?></button>
                    <?php if ($editUser): ?>
                        <a class="button button--secondary" href="<?php echo h(getProjectBasePath() . '/admin/users.php'); ?>"><?php echo h(t('admin.users.cancel')); ?></a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="admin-panel__header" style="margin-top: 1.2rem;">
                <h2><?php echo h(t('admin.users.link.title')); ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="link_candidate">

                <label>
                    <span><?php echo h(t('admin.users.link.user')); ?></span>
                    <select name="link_user_id" required>
                        <option value=""><?php echo h(t('admin.users.link.select_user')); ?></option>
                        <?php foreach ($userOptions as $option): ?>
                            <option value="<?php echo (int) $option['id']; ?>">
                                <?php echo h($option['username'] . ' (' . $option['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span><?php echo h(t('admin.users.link.candidate_email')); ?></span>
                    <input type="email" name="candidate_email" placeholder="<?php echo h(t('admin.users.link.candidate_email_placeholder')); ?>" required>
                </label>

                <div class="admin-form__actions">
                    <button class="button button--secondary" type="submit"><?php echo h(t('admin.users.link.submit')); ?></button>
                </div>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo h(t('admin.users.user_list')); ?></h2>
                <form class="admin-search" method="get">
                    <input type="text" name="search" placeholder="<?php echo h(t('admin.users.search_placeholder')); ?>" value="<?php echo h($search); ?>">
                    <button class="button button--secondary" type="submit"><?php echo h(t('admin.users.search')); ?></button>
                </form>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?php echo h(t('home.username')); ?></th>
                            <th><?php echo h(t('home.email')); ?></th>
                            <th><?php echo h(t('admin.users.role')); ?></th>
                            <th><?php echo h(t('admin.users.candidate_records')); ?></th>
                            <th><?php echo h(t('admin.users.created')); ?></th>
                            <th><?php echo h(t('admin.users.actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6"><?php echo h(t('admin.users.no_users')); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo h($user['username']); ?></td>
                                <td><?php echo h($user['email']); ?></td>
                                <td><?php echo h($user['role'] === 'admin' ? t('admin.users.role_admin') : t('admin.users.role_user')); ?></td>
                                <td><?php echo (int) $user['candidate_records']; ?></td>
                                <td><?php echo h(date('d/m/Y', strtotime($user['created_at']))); ?></td>
                                <td class="admin-table__actions">
                                    <a class="admin-link-button" href="<?php echo h(getProjectBasePath() . '/admin/users.php?edit=' . (int) $user['id']); ?>"><?php echo h(t('admin.users.edit')); ?></a>
                                    <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                        <form method="post" onsubmit="return confirm('<?php echo h(t('admin.users.confirm_delete')); ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <button class="admin-link-button admin-link-button--danger" type="submit"><?php echo h(t('admin.users.delete')); ?></button>
                                        </form>
                                    <?php endif; ?>
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
