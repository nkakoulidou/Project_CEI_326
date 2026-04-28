<?php
require_once '../includes/candidate.php';

$userId = requireCandidateLogin();
$summary = fetchCandidateSummary($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>

    <main class="admin-page">
        <section class="admin-hero">
            <div>
                <p class="admin-eyebrow">Candidate Module</p>
                <h1>Welcome, <?php echo htmlspecialchars($summary['first_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Open your profile, monitor your applications, or track other candidates from one place.</p>
            </div>
        </section>

        <section class="admin-stats-grid">
            <article class="admin-stat-card">
                <span>Applications</span>
                <strong><?php echo $summary['application_count']; ?></strong>
            </article>
            <article class="admin-stat-card">
                <span>Tracked Candidates</span>
                <strong><?php echo $summary['tracked_count']; ?></strong>
            </article>
            <article class="admin-stat-card">
                <span>Current Specialty</span>
                <strong class="admin-stat-card__compact"><?php echo htmlspecialchars($summary['specialty'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </article>
        </section>

        <section class="admin-card-grid">
            <a class="admin-action-card" href="profile.php">
                <span class="admin-action-card__icon">P</span>
                <h2>My Profile</h2>
                <p>Review your candidate details, update personal information, set notifications, and change your password.</p>
            </a>
            <a class="admin-action-card" href="list.php">
                <span class="admin-action-card__icon">A</span>
                <h2>Track My Applications</h2>
                <p>See your applications, status timeline, and list-related updates in one table.</p>
            </a>
            <a class="admin-action-card" href="track-others.php">
                <span class="admin-action-card__icon">T</span>
                <h2>Track Others</h2>
                <p>Search candidates and follow the ones you want to monitor from the published lists.</p>
            </a>
        </section>
    </main>
</body>
</html>
