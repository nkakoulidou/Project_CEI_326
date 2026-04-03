<?php
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/db.php';

$isLoggedIn = isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
$role = $_SESSION['role'] ?? '';
$dashboardLink = $role === 'admin' ? 'admin/dashboard.php' : 'modules/dashboard.php';
$activeForm = $_GET['form'] ?? '';
$loginError = '';
$registerErrors = [];
$successMessage = '';
$loginEmail = '';
$registerUsername = '';
$registerEmail = '';

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $authAction = $_POST['auth_action'] ?? '';

    if ($authAction === 'login') {
        $activeForm = 'login';
        $loginEmail = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $loginEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . $dashboardLink);
            exit;
        }

        $loginError = 'Incorrect login details.';
    }

    if ($authAction === 'register') {
        $activeForm = 'register';
        $registerUsername = trim($_POST['username'] ?? '');
        $registerEmail = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($registerUsername === '') {
            $registerErrors[] = 'Username is required.';
        }

        if (!filter_var($registerEmail, FILTER_VALIDATE_EMAIL)) {
            $registerErrors[] = 'Enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $registerErrors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirm) {
            $registerErrors[] = 'Passwords do not match.';
        }

        if (empty($registerErrors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute([':email' => $registerEmail]);

            if ($stmt->fetch()) {
                $registerErrors[] = 'This email is already in use.';
            }
        }

        if (empty($registerErrors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
            );
            $stmt->execute([
                ':username' => $registerUsername,
                ':email' => $registerEmail,
                ':hash' => $hash,
            ]);

            header('Location: index.php?form=login&registered=1');
            exit;
        }
    }
}

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $activeForm = 'login';
    $successMessage = 'Registration completed successfully. You can now sign in.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Tracking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="preloader" id="preloader" aria-hidden="true">
        <div class="preloader__halo"></div>
        <div class="preloader__logo-wrap">
            <img class="preloader__logo" src="assets/images/owlogo.png" alt="EduTrack logo">
        </div>
        <p class="preloader__brand">EduTrack</p>
    </div>

    <?php renderNavbar(); ?>

    <main class="page-shell">
        <?php if (!$isLoggedIn): ?>
            <section class="auth-panel" id="auth-panel">
                <div class="auth-panel__switch">
                    <a class="auth-tab <?php echo $activeForm !== 'register' ? 'auth-tab--active' : ''; ?>" href="index.php?form=login#auth-panel">Login</a>
                    <a class="auth-tab <?php echo $activeForm === 'register' ? 'auth-tab--active' : ''; ?>" href="index.php?form=register#auth-panel">Register</a>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <p class="auth-message auth-message--success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($activeForm === 'register'): ?>
                    <div class="auth-heading">
                        <h1>Create Your Account</h1>
                        <p>Join the platform and start managing your candidate journey.</p>
                    </div>

                    <?php if (!empty($registerErrors)): ?>
                        <div class="auth-message auth-message--error">
                            <?php foreach ($registerErrors as $error): ?>
                                <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form class="auth-form" method="post" action="index.php?form=register#auth-panel">
                        <input type="hidden" name="auth_action" value="register">

                        <label class="auth-form__field">
                            <span>Username</span>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($registerUsername, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>

                        <label class="auth-form__field">
                            <span>Email</span>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($registerEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>

                        <label class="auth-form__field">
                            <span>Password</span>
                            <div class="password-field">
                                <input type="password" name="password" class="js-password-input" required>
                                <button class="password-toggle" type="button" aria-label="Show password" title="Show password">
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

                        <label class="auth-form__field">
                            <span>Confirm Password</span>
                            <div class="password-field">
                                <input type="password" name="confirm" class="js-password-input" required>
                                <button class="password-toggle" type="button" aria-label="Show password" title="Show password">
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

                        <button class="button button--primary" type="submit">Create Account</button>
                    </form>
                <?php else: ?>
                    <div class="auth-heading">
                        <h1>Welcome Back!</h1>
                        <p>Sign in to continue to your dashboard and account tools.</p>
                    </div>

                    <?php if ($loginError !== ''): ?>
                        <p class="auth-message auth-message--error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <form class="auth-form" method="post" action="index.php?form=login#auth-panel">
                        <input type="hidden" name="auth_action" value="login">

                        <label class="auth-form__field">
                            <span>Email</span>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($loginEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </label>

                        <label class="auth-form__field">
                            <span>Password</span>
                            <div class="password-field">
                                <input type="password" name="password" class="js-password-input" required>
                                <button class="password-toggle" type="button" aria-label="Show password" title="Show password">
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

                        <button class="button button--secondary" type="submit">Sign In</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="welcome-panel">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <a class="button button--secondary" href="<?php echo htmlspecialchars($dashboardLink, ENT_QUOTES, 'UTF-8'); ?>">Go to Dashboard</a>
            </section>
        <?php endif; ?>
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
                button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                button.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
            });
        });

        window.addEventListener('load', function () {
            var preloader = document.getElementById('preloader');
            if (!preloader) {
                return;
            }

            setTimeout(function () {
                preloader.classList.add('preloader--hidden');

                setTimeout(function () {
                    preloader.style.display = 'none';
                }, 900);
            }, 1400);
        });
    </script>
</body>
</html>
