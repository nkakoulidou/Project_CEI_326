<?php
require_once '../includes/candidate.php';

$userId = requireCandidateLogin();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $error = saveCandidateProfile($pdo, $userId, $_POST);
        if ($error === null) {
            $message = t('candidate.profile.success.updated');
        }
    }

    if ($action === 'change_password') {
        $error = updateCandidatePassword($pdo, $userId, $_POST);
        if ($error === null) {
            $message = t('candidate.profile.success.password_changed');
        }
    }
}

$profile = fetchCandidateProfile($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(currentLocale(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('candidate.profile.title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="admin-page">
        <?php if ($message !== ''): ?>
            <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="admin-alert admin-alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="admin-two-column">
            <article class="admin-panel">
                <div class="admin-panel__header">
                    <h2><?php echo htmlspecialchars(t('candidate.profile.basic_info'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <form class="admin-form" method="post">
                    <input type="hidden" name="action" value="update_profile">

                    <label>
                        <span><?php echo htmlspecialchars(t('home.username'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($profile['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('home.email'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.first_name'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.last_name'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.birth_date'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="date" name="birth_date" value="<?php echo htmlspecialchars($profile['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.phone'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.district'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="district" value="<?php echo htmlspecialchars($profile['district'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.specialty'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="specialty" value="<?php echo htmlspecialchars($profile['specialty'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <button class="button button--primary" type="submit"><?php echo htmlspecialchars(t('candidate.profile.save_profile'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </article>

            <article class="admin-panel">
                <div class="admin-panel__header">
                    <h2><?php echo htmlspecialchars(t('candidate.profile.change_password'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <form class="admin-form" method="post">
                    <input type="hidden" name="action" value="change_password">

                    <label>
                        <span><?php echo htmlspecialchars(t('candidate.profile.current_password'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="password-field">
                            <input type="password" name="current_password" class="js-password-input" required>
                            <button class="password-toggle" type="button" aria-label="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>">
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
                        <span><?php echo htmlspecialchars(t('candidate.profile.new_password'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="password-field">
                            <input type="password" name="new_password" class="js-password-input" minlength="8" required>
                            <button class="password-toggle" type="button" aria-label="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>">
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
                        <span><?php echo htmlspecialchars(t('candidate.profile.confirm_new_password'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="password-field">
                            <input type="password" name="confirm_password" class="js-password-input" minlength="8" required>
                            <button class="password-toggle" type="button" aria-label="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(t('home.show_password'), ENT_QUOTES, 'UTF-8'); ?>">
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

                    <button class="button button--secondary" type="submit"><?php echo htmlspecialchars(t('candidate.profile.update_password'), ENT_QUOTES, 'UTF-8'); ?></button>
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
</body>
</html>
