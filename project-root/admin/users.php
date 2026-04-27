<?php
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php?form=login');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$action = $_GET['action'] ?? 'list';
$search = trim($_GET['q'] ?? '');
$message = '';
$error = '';
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token.';
        $action = 'list';
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'add' || $postAction === 'update') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
            $password = $_POST['password'] ?? '';
            $userId = (int) ($_POST['user_id'] ?? 0);

            if ($username === '') {
                $error = 'Username is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Enter a valid email address.';
            } elseif ($postAction === 'add' && strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($postAction === 'update' && $password !== '' && strlen($password) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                try {
                    if ($postAction === 'add') {
                        $stmt = $pdo->prepare(
                            'INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :hash, :role)'
                        );
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':hash' => password_hash($password, PASSWORD_DEFAULT),
                            ':role' => $role,
                        ]);
                        $message = 'User created successfully.';
                    } else {
                        if ($password === '') {
                            $stmt = $pdo->prepare(
                                'UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id'
                            );
                            $stmt->execute([
                                ':username' => $username,
                                ':email' => $email,
                                ':role' => $role,
                                ':id' => $userId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare(
                                'UPDATE users
                                 SET username = :username, email = :email, role = :role, password_hash = :hash
                                 WHERE id = :id'
                            );
                            $stmt->execute([
                                ':username' => $username,
                                ':email' => $email,
                                ':role' => $role,
                                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                                ':id' => $userId,
                            ]);
                        }

                        $message = 'User updated successfully.';
                    }

                    $action = 'list';
                } catch (PDOException $exception) {
                    $error = 'Could not save the user. Username or email may already exist.';
                    $action = $postAction === 'add' ? 'add' : 'edit';
                }
            }
        } elseif ($postAction === 'delete') {
            $userId = (int) ($_POST['user_id'] ?? 0);

            if ($userId === (int) $_SESSION['user_id']) {
                $error = 'You cannot delete your own account.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $stmt->execute([':id' => $userId]);
                $message = 'User deleted successfully.';
            }

            $action = 'list';
        }
    }
}

if ($action === 'edit') {
    $userId = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $editUser = $stmt->fetch();

    if (!$editUser) {
        $error = 'User not found.';
        $action = 'list';
    }
}

$params = [];
$sql = 'SELECT id, username, email, role, created_at FROM users';

if ($search !== '') {
    $sql .= ' WHERE username LIKE :search OR email LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="page-shell">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h1>Manage Users</h1>
                    <p>Review registered accounts and maintain administrator access.</p>
                </div>
                <a class="button button--secondary" href="?action=add">Add User</a>
            </div>

            <?php if ($message !== ''): ?>
                <p class="auth-message auth-message--success"><?php echo e($message); ?></p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p class="auth-message auth-message--error"><?php echo e($error); ?></p>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <section class="admin-card">
                    <h2><?php echo $action === 'add' ? 'Create User' : 'Edit User'; ?></h2>
                    <form class="auth-form" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add' : 'update'; ?>">
                        <?php if ($editUser): ?>
                            <input type="hidden" name="user_id" value="<?php echo (int) $editUser['id']; ?>">
                        <?php endif; ?>

                        <label class="auth-form__field">
                            <span>Username</span>
                            <input
                                type="text"
                                name="username"
                                value="<?php echo e($editUser['username'] ?? ''); ?>"
                                required
                            >
                        </label>

                        <label class="auth-form__field">
                            <span>Email</span>
                            <input
                                type="email"
                                name="email"
                                value="<?php echo e($editUser['email'] ?? ''); ?>"
                                required
                            >
                        </label>

                        <label class="auth-form__field">
                            <span>Role</span>
                            <select name="role" class="admin-select">
                                <option value="user" <?php echo ($editUser['role'] ?? '') !== 'admin' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </label>

                        <label class="auth-form__field">
                            <span><?php echo $action === 'add' ? 'Password' : 'New Password'; ?></span>
                            <input
                                type="password"
                                name="password"
                                <?php echo $action === 'add' ? 'required minlength="8"' : 'minlength="8"'; ?>
                            >
                        </label>

                        <div class="admin-panel__actions">
                            <button class="button button--primary" type="submit">
                                <?php echo $action === 'add' ? 'Create Account' : 'Save Changes'; ?>
                            </button>
                            <a class="button button--secondary" href="users.php">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="admin-card">
                <form class="admin-search" method="get" action="">
                    <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search by username or email">
                    <button class="button button--primary" type="submit">Search</button>
                    <?php if ($search !== ''): ?>
                        <a class="button button--secondary" href="users.php">Clear</a>
                    <?php endif; ?>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
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
                                    <td><?php echo (int) $user['id']; ?></td>
                                    <td><?php echo e($user['username']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['role']); ?></td>
                                    <td><?php echo e(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                                    <td class="admin-table__actions">
                                        <a class="button button--secondary" href="?action=edit&id=<?php echo (int) $user['id']; ?>">Edit</a>
                                        <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                                            <form method="post" action="" onsubmit="return confirm('Delete this user?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                <button class="button button--primary admin-delete" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
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
