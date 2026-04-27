<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$message = '';
$error = '';
$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($username === '' || $email === '') {
            $error = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':id' => $adminId,
                ]);
                $_SESSION['username'] = $username;
                $message = 'Profile details updated successfully.';
            } catch (PDOException $exception) {
                $error = 'This username or email is already in use.';
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => $adminId]);
        $passwordHash = $stmt->fetchColumn();

        if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'The new passwords do not match.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $stmt->execute([
                ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => $adminId,
            ]);
            $message = 'Password changed successfully.';
        }
    }
}

$stmt = $pdo->prepare('SELECT username, email, role, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch();

adminPageStart('Admin Profile');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow">Profile</p>
            <h1>Admin Account Settings</h1>
            <p>Update your basic details and change your password from this page.</p>
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
                <h2>Basic Information</h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="update_profile">

                <label>
                    <span>Username</span>
                    <input type="text" name="username" value="<?php echo h($admin['username'] ?? ''); ?>" required>
                </label>

                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo h($admin['email'] ?? ''); ?>" required>
                </label>

                <label>
                    <span>Role</span>
                    <input type="text" value="<?php echo h(ucfirst($admin['role'] ?? 'admin')); ?>" disabled>
                </label>

                <label>
                    <span>Account Created</span>
                    <input type="text" value="<?php echo h(isset($admin['created_at']) ? date('d/m/Y', strtotime($admin['created_at'])) : ''); ?>" disabled>
                </label>

                <button class="button button--primary" type="submit">Save Profile</button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>Change Password</h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="change_password">

                <label>
                    <span>Current Password</span>
                    <input type="password" name="current_password" required>
                </label>

                <label>
                    <span>New Password</span>
                    <input type="password" name="new_password" minlength="8" required>
                </label>

                <label>
                    <span>Confirm New Password</span>
                    <input type="password" name="confirm_password" minlength="8" required>
                </label>

                <button class="button button--secondary" type="submit">Update Password</button>
            </form>
        </article>
    </section>
</main>

<?php adminPageEnd(); ?>
