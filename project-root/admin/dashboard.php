<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$basePath = getProjectBasePath();
$cards = [
    [
        'title' => t('admin.dashboard.manage_users_title'),
        'description' => t('admin.dashboard.manage_users_desc'),
        'icon' => 'U',
        'href' => $basePath . '/admin/users.php',
    ],
    [
        'title' => t('admin.dashboard.manage_lists_title'),
        'description' => t('admin.dashboard.manage_lists_desc'),
        'icon' => 'L',
        'href' => $basePath . '/admin/lists.php',
    ],
    [
        'title' => t('admin.dashboard.reports_title'),
        'description' => t('admin.dashboard.reports_desc'),
        'icon' => 'R',
        'href' => $basePath . '/admin/reports.php',
    ],
];

adminPageStart('admin.dashboard.title');
?>

<main class="admin-page">
    <section class="admin-hero">
        <div>
            <h1><?php echo h(t('admin.dashboard.heading', ['username' => ($_SESSION['username'] ?? 'Admin')])); ?></h1>
            <p><?php echo h(t('admin.dashboard.intro')); ?></p>
        </div>
        <a class="button button--secondary" href="<?php echo h($basePath . '/admin/profile.php'); ?>"><?php echo h(t('admin.dashboard.edit_profile')); ?></a>
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
