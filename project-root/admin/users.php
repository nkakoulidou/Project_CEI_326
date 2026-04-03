<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';
$editUser = null;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $password = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            $error = 'All fields are required.';
            $action = 'add';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
            $action = 'add';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
            $action = 'add';
        } else {
            try {
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
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Username or email already exists.';
                $action = 'add';
            }
        }
    }

    if ($postAction === 'update') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $username === '' || $email === '') {
            $error = 'Username and email are required.';
            $action = 'edit';
            $_GET['id'] = (string) $id;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
            $action = 'edit';
            $_GET['id'] = (string) $id;
        } else {
            try {
                if ($password !== '') {
                    if (strlen($password) < 8) {
                        $error = 'Password must be at least 8 characters.';
                        $action = 'edit';
                        $_GET['id'] = (string) $id;
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE users SET username = :username, email = :email, role = :role, password_hash = :password_hash WHERE id = :id'
                        );
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':role' => $role,
                            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            ':id' => $id,
                        ]);
                        $message = 'User updated successfully.';
                        $action = 'list';
                    }
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id'
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':role' => $role,
                        ':id' => $id,
                    ]);
                    $message = 'User updated successfully.';
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $error = 'Username or email already exists.';
                $action = 'edit';
                $_GET['id'] = (string) $id;
            }
        }
    }

    if ($postAction === 'delete') {
        $id = (int) ($_POST['user_id'] ?? 0);

        if ($id === (int) $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $message = 'User deleted successfully.';
        }

        $action = 'list';
    }
}

if ($action === 'edit') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $editUser = $stmt->fetch();

        if (!$editUser) {
            $error = 'User not found.';
            $action = 'list';
        }
    } else {
        $error = 'Invalid user id.';
        $action = 'list';
    }
}

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE username LIKE :q OR email LIKE :q ORDER BY created_at DESC'
    );
    $stmt->execute([':q' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
}

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
    <main class="page-shell">
        <section class="welcome-panel">
            <h1>Manage Users</h1>
            <p>Admin panel for creating, editing, and deleting user accounts.</p>
            <p><a href="dashboard.php">Back to dashboard</a></p>
        </section>

        <?php if ($message !== ''): ?>
            <p style="color: green;"><?= h($message) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p style="color: red;"><?= h($error) ?></p>
        <?php endif; ?>

        <section>
            <h2><?= $action === 'edit' ? 'Edit User' : 'Add User' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'add' ?>">
                <?php if ($action === 'edit' && $editUser): ?>
                    <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                <?php endif; ?>

                <p>
                    <label for="username">Username</label><br>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= h($editUser['username'] ?? '') ?>"
                        required
                    >
                </p>

                <p>
                    <label for="email">Email</label><br>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= h($editUser['email'] ?? '') ?>"
                        required
                    >
                </p>

                <p>
                    <label for="role">Role</label><br>
                    <select id="role" name="role">
                        <option value="user" <?= (($editUser['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </p>

                <p>
                    <label for="password">
                        Password<?= $action === 'edit' ? ' (leave blank to keep current password)' : '' ?>
                    </label><br>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        <?= $action === 'edit' ? '' : 'required' ?>
                    >
                </p>

                <p>
                    <button type="submit"><?= $action === 'edit' ? 'Update User' : 'Create User' ?></button>
                    <?php if ($action === 'edit'): ?>
                        <a href="users.php">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </section>

        <section>
            <h2>User List</h2>
            <form method="get">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?= h($search) ?>" placeholder="Search by username or email">
                <button type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="users.php">Clear</a>
                <?php endif; ?>
            </form>

            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr>
                            <td colspan="6">No users found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= (int) $user['id'] ?></td>
                            <td><?= h($user['username']) ?></td>
                            <td><?= h($user['email']) ?></td>
                            <td><?= h($user['role']) ?></td>
                            <td><?= h($user['created_at']) ?></td>
                            <td>
                                <a href="users.php?action=edit&id=<?= (int) $user['id'] ?>">Edit</a>
                                <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
