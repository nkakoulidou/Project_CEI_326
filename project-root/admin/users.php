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
            $error = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($action === 'create' && strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
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
                    $message = 'User created successfully.';
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
                    $message = 'User updated successfully.';
                    $editId = 0;
                }
            } catch (PDOException $exception) {
                $error = 'Username or email already exists.';
            }
        }
    }

    if ($action === 'delete') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === (int) ($_SESSION['user_id'] ?? 0)) {
            $error = 'You cannot delete your own admin account.';
        } else {
            $pdo->prepare('DELETE FROM candidates WHERE user_id = :user_id')->execute([':user_id' => $userId]);
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
            $message = 'User deleted successfully.';
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

adminPageStart('Manage Users');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow">Manage Users</p>
            <h1>Registered Users</h1>
            <p>Add, update or remove users through a simple admin form.</p>
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
                <h2><?php echo $editUser ? 'Update User' : 'Add User'; ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo (int) $editUser['id']; ?>">
                <?php endif; ?>

                <label>
                    <span>Username</span>
                    <input type="text" name="username" value="<?php echo h($editUser['username'] ?? ''); ?>" required>
                </label>

                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo h($editUser['email'] ?? ''); ?>" required>
                </label>

                <label>
                    <span>Role</span>
                    <select name="role">
                        <option value="user" <?php echo (($editUser['role'] ?? 'user') === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo (($editUser['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </label>

                <label>
                    <span><?php echo $editUser ? 'New Password (optional)' : 'Password'; ?></span>
                    <input type="password" name="password" <?php echo $editUser ? '' : 'required minlength="8"'; ?>>
                </label>

                <div class="admin-form__actions">
                    <button class="button button--primary" type="submit"><?php echo $editUser ? 'Save Changes' : 'Create User'; ?></button>
                    <?php if ($editUser): ?>
                        <a class="button button--secondary" href="<?php echo h(getProjectBasePath() . '/admin/users.php'); ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>User List</h2>
                <form class="admin-search" method="get">
                    <input type="text" name="search" placeholder="Search username or email" value="<?php echo h($search); ?>">
                    <button class="button button--secondary" type="submit">Search</button>
                </form>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Candidate Records</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6">No users found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo h($user['username']); ?></td>
                                <td><?php echo h($user['email']); ?></td>
                                <td><?php echo h(ucfirst($user['role'])); ?></td>
                                <td><?php echo (int) $user['candidate_records']; ?></td>
                                <td><?php echo h(date('d/m/Y', strtotime($user['created_at']))); ?></td>
                                <td class="admin-table__actions">
                                    <a class="admin-link-button" href="<?php echo h(getProjectBasePath() . '/admin/users.php?edit=' . (int) $user['id']); ?>">Edit</a>
                                    <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                        <form method="post" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <button class="admin-link-button admin-link-button--danger" type="submit">Delete</button>
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
