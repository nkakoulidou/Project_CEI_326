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
            $message = 'Profile updated successfully.';
        }
    }

    if ($action === 'change_password') {
        $error = updateCandidatePassword($pdo, $userId, $_POST);
        if ($error === null) {
            $message = 'Password changed successfully.';
        }
    }
}

$profile = fetchCandidateProfile($pdo, $userId);
$preferences = fetchCandidatePreferences($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
                    <h2>Basic Information</h2>
                </div>
                <form class="admin-form" method="post">
                    <input type="hidden" name="action" value="update_profile">

                    <label>
                        <span>Username</span>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($profile['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span>Email</span>
                        <input type="email" value="<?php echo htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    </label>

                    <label>
                        <span>First Name</span>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span>Last Name</span>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span>Phone</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span>District</span>
                        <input type="text" name="district" value="<?php echo htmlspecialchars($profile['district'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span>Specialty</span>
                        <input type="text" name="specialty" value="<?php echo htmlspecialchars($profile['specialty'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>

                    <label>
                        <span>Current Ranking</span>
                        <input type="text" value="<?php echo htmlspecialchars(isset($profile['ranking']) ? (string) $profile['ranking'] : 'Not available', ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    </label>

                    <fieldset class="candidate-preferences">
                        <legend>Notifications</legend>
                        <label class="candidate-check">
                            <input type="checkbox" name="notify_new_lists" <?php echo !empty($preferences['notify_new_lists']) ? 'checked' : ''; ?>>
                            <span>Notify me when a new list is published</span>
                        </label>
                        <label class="candidate-check">
                            <input type="checkbox" name="notify_status_changes" <?php echo !empty($preferences['notify_status_changes']) ? 'checked' : ''; ?>>
                            <span>Notify me when my application status changes</span>
                        </label>
                        <label class="candidate-check">
                            <input type="checkbox" name="notify_rank_updates" <?php echo !empty($preferences['notify_rank_updates']) ? 'checked' : ''; ?>>
                            <span>Notify me when my ranking changes</span>
                        </label>
                    </fieldset>

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
</body>
</html>
