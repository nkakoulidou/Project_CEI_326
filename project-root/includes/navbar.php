<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function getProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $needle = '/project-root/';
    $position = strpos($scriptName, $needle);

    if ($position === false) {
        return '/project-root';
    }

    return substr($scriptName, 0, $position) . '/project-root';
}

function renderNavbar(): void
{
    $isLoggedIn = isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    $role = $_SESSION['role'] ?? '';
    $username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8');
    $basePath = getProjectBasePath();

    $links = [
        ['label' => 'Home', 'href' => $basePath . '/index.php'],
    ];

    if (!$isLoggedIn) {
        $links[] = ['label' => 'Search', 'href' => $basePath . '/search/dashboard.html'];
        $links[] = ['label' => 'Login', 'href' => $basePath . '/index.php?form=login#auth-panel'];
        $links[] = ['label' => 'Register', 'href' => $basePath . '/index.php?form=register#auth-panel'];
    } elseif ($role === 'admin') {
        $links[] = ['label' => 'Dashboard', 'href' => $basePath . '/admin/dashboard.php'];
        $links[] = ['label' => 'Manage Users', 'href' => $basePath . '/admin/users.php'];
        $links[] = ['label' => 'Manage Lists', 'href' => $basePath . '/admin/manage_lists/manage_lists.php'];
        $links[] = ['label' => 'Logout', 'href' => $basePath . '/auth/logout.php'];
    } else {
        $links[] = ['label' => 'Dashboard', 'href' => $basePath . '/modules/dashboard.php'];
        $links[] = ['label' => 'Logout', 'href' => $basePath . '/auth/logout.php'];
    }
    ?>
    <nav class="navbar">
        <div class="navbar__inner">
            <a class="navbar__brand" href="<?php echo htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
                <img class="navbar__logo" src="<?php echo htmlspecialchars($basePath . '/assets/images/owlogo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="OW logo">
                <span>EduTrack</span>
            </a>
            <div class="navbar__menu">
                <?php foreach ($links as $link): ?>
                    <a class="navbar__link" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($isLoggedIn): ?>
                <p class="navbar__welcome">Welcome, <?php echo $username; ?></p>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}