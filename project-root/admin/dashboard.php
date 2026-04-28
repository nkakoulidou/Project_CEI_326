<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$stats = [
    'users' => countRows($pdo, 'users'),
    'services' => countRows($pdo, 'services'),
    'lists' => countRows($pdo, 'lists'),
    'candidates' => countRows($pdo, 'candidates'),
];

$basePath = getProjectBasePath();
$cards = [
    [
        'title' => 'Manage Users',
        'description' => 'View all registered users and manage their account details.',
        'icon' => 'U',
        'href' => $basePath . '/admin/users.php',
    ],
    [
        'title' => 'Manage Lists',
        'description' => 'Create and manage the available appointment lists and their service details.',
        'icon' => 'L',
        'href' => $basePath . '/admin/lists.php',
    ],
    [
        'title' => 'Reports',
        'description' => 'See dashboard statistics and summary charts for the lists.',
        'icon' => 'R',
        'href' => $basePath . '/admin/reports.php',
    ],
];

adminPageStart('Admin Dashboard');
?>

<main class="admin-page">
    <section class="admin-hero">
        <div>
            <p class="admin-eyebrow">Admin Module</p>
            <h1>Welcome back, <?php echo h($_SESSION['username'] ?? 'Admin'); ?></h1>
            <p>Use the shortcuts below to manage users, lists, reports and your profile from one place.</p>
        </div>
        <a class="button button--secondary" href="<?php echo h($basePath . '/admin/profile.php'); ?>">Edit Profile</a>
    </section>

    <section class="admin-stats-grid">
        <article class="admin-stat-card">
            <span>Total Users</span>
            <strong><?php echo $stats['users']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Services</span>
            <strong><?php echo $stats['services']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Lists</span>
            <strong><?php echo $stats['lists']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Candidates</span>
            <strong><?php echo $stats['candidates']; ?></strong>
        </article>
    </section>

    <section class="admin-card-grid">
        <?php foreach ($cards as $card): ?>
            <a class="admin-action-card" href="<?php echo h($card['href']); ?>">
                <span class="admin-action-card__icon"><?php echo h($card['icon']); ?></span>
                <h2><?php echo h($card['title']); ?></h2>
                <p><?php echo h($card['description']); ?></p>
            </a>
        <?php endforeach; ?>
    </section>
</main>

<?php adminPageEnd(); ?>
