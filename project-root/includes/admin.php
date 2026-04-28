<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireAdmin(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . getProjectBasePath() . '/index.php?form=login');
        exit;
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminPageStart(string $title): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?></title>
        <link rel="stylesheet" href="<?php echo h(getProjectBasePath() . '/assets/css/style.css'); ?>">
    </head>
    <body>
        <?php renderNavbar(); ?>
    <?php
}

function adminPageEnd(): void
{
    echo '</body></html>';
}
