<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Candidate Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<h1>Candidate Dashboard</h1>

<div style="display: flex; gap: 20px; margin-top: 30px;">

    <!-- My Profile -->
    <div style="border:1px solid black; padding:20px;">
        <h3>My Profile</h3>
        <p>View and edit your personal details</p>
        <a href="profile.php">
            <button>Go</button>
        </a>
    </div>

    <!-- Track My Applications -->
    <div style="border:1px solid black; padding:20px;">
        <h3>Track My Applications</h3>
        <p>See your ranking and specialty</p>
        <a href="track_my_applications.php">
            <button>Go</button>
        </a>
    </div>

    <!-- Track Others -->
    <div style="border:1px solid black; padding:20px;">
        <h3>Track Others</h3>
        <p>Search other candidates</p>
        <a href="track_others.php">
            <button>Go</button>
        </a>
    </div>

</div>

<br><br>

<a href="../auth/logout.php">Logout</a>

</body>
</html>