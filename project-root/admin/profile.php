<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$message = '';
$error = '';
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, username, email, created_at, password_hash FROM users WHERE id = :id AND role = :role LIMIT 1');
$stmt->execute([
    ':id' => $adminId,
    ':role' => 'admin',
]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: ' . getProjectBasePath() . '/index.php?form=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($username === '' || $email === '') {
            $error = t('admin.profile.error.required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('admin.profile.error.valid_email');
        } else {
            try {
                $updateStmt = $pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
                $updateStmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':id' => $adminId,
                ]);

                $_SESSION['username'] = $username;
                $admin['username'] = $username;
                $admin['email'] = $email;
                $message = t('admin.profile.success.updated');
            } catch (PDOException $exception) {
                $error = t('admin.profile.error.duplicate');
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $admin['password_hash'])) {
            $error = t('admin.profile.error.current_password');
        } elseif (strlen($newPassword) < 8) {
            $error = t('admin.profile.error.password_length');
        } elseif ($newPassword !== $confirmPassword) {
            $error = t('admin.profile.error.password_mismatch');
        } else {
            $passwordStmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $passwordStmt->execute([
                ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => $adminId,
            ]);
            $message = t('admin.profile.success.password_changed');
        }
    }
}

adminPageStart('admin.profile.title');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow"><?php echo h(t('admin.profile.eyebrow')); ?></p>
            <h1><?php echo h(t('admin.profile.heading')); ?></h1>
            <p><?php echo h(t('admin.profile.intro')); ?></p>
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
                <h2><?php echo h(t('admin.profile.basic_info')); ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="update_profile">

                <label>
                    <span><?php echo h(t('home.username')); ?></span>
                    <input type="text" name="username" value="<?php echo h($admin['username']); ?>" required>
                </label>

                <label>
                    <span><?php echo h(t('home.email')); ?></span>
                    <input type="email" name="email" value="<?php echo h($admin['email']); ?>" required>
                </label>

                <label>
                    <span><?php echo h(t('admin.profile.account_created')); ?></span>
                    <input type="text" value="<?php echo h(date('d/m/Y', strtotime($admin['created_at']))); ?>" disabled>
                </label>

                <button class="button button--primary" type="submit"><?php echo h(t('admin.profile.save')); ?></button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo h(t('admin.profile.change_password')); ?></h2>
            </div>
            <form class="admin-form" method="post">
                <input type="hidden" name="action" value="change_password">

                <label>
                    <span><?php echo h(t('admin.profile.current_password')); ?></span>
                    <div class="password-field">
                        <input type="password" name="current_password" class="js-password-input" required>
                        <button class="password-toggle" type="button" aria-label="<?php echo h(t('home.show_password')); ?>" title="<?php echo h(t('home.show_password')); ?>">
                            <span class="password-toggle__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-3.04 4.36"></path>
                                    <path d="M6.61 6.61A11.84 11.84 0 0 0 1 11.5C2.73 15.89 7 19 12 19a10.93 10.93 0 0 0 5.39-1.39"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </label>

                <label>
                    <span><?php echo h(t('admin.profile.new_password')); ?></span>
                    <div class="password-field">
                        <input type="password" name="new_password" class="js-password-input" minlength="8" required>
                        <button class="password-toggle" type="button" aria-label="<?php echo h(t('home.show_password')); ?>" title="<?php echo h(t('home.show_password')); ?>">
                            <span class="password-toggle__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-3.04 4.36"></path>
                                    <path d="M6.61 6.61A11.84 11.84 0 0 0 1 11.5C2.73 15.89 7 19 12 19a10.93 10.93 0 0 0 5.39-1.39"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </label>

                <label>
                    <span><?php echo h(t('admin.profile.confirm_password')); ?></span>
                    <div class="password-field">
                        <input type="password" name="confirm_password" class="js-password-input" minlength="8" required>
                        <button class="password-toggle" type="button" aria-label="<?php echo h(t('home.show_password')); ?>" title="<?php echo h(t('home.show_password')); ?>">
                            <span class="password-toggle__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3l18 18"></path>
                                    <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-3.04 4.36"></path>
                                    <path d="M6.61 6.61A11.84 11.84 0 0 0 1 11.5C2.73 15.89 7 19 12 19a10.93 10.93 0 0 0 5.39-1.39"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </label>

                <button class="button button--secondary" type="submit"><?php echo h(t('admin.profile.update_password')); ?></button>
            </form>
        </article>
    </section>
</main>

<script>
    var closedEyeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path><path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-3.04 4.36"></path><path d="M6.61 6.61A11.84 11.84 0 0 0 1 11.5C2.73 15.89 7 19 12 19a10.93 10.93 0 0 0 5.39-1.39"></path></svg>';
    var openEyeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';

    document.querySelectorAll('.password-field').forEach(function (field) {
        var input = field.querySelector('.js-password-input');
        var button = field.querySelector('.password-toggle');
        var icon = field.querySelector('.password-toggle__icon');

        if (!input || !button || !icon) {
            return;
        }

        button.addEventListener('click', function () {
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden ? openEyeIcon : closedEyeIcon;
            button.setAttribute('aria-label', isHidden ? <?php echo json_encode(t('home.hide_password')); ?> : <?php echo json_encode(t('home.show_password')); ?>);
            button.setAttribute('title', isHidden ? <?php echo json_encode(t('home.hide_password')); ?> : <?php echo json_encode(t('home.show_password')); ?>);
        });
    });
</script>

<?php adminPageEnd(); ?>
