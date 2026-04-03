<?php
session_start();
require_once '../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?form=login');
    exit;
}
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

    <main class="page-shell">
        <section class="welcome-panel">
            <h1>Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>.</p>
        </section>
    </main>
</body>
</html>
