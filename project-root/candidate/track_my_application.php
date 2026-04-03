<?php 
session_start();
include "config.php";

$user_id = $_SESSION['user_id'];

$sql = "SELECT ranking, specialty 
        FROM candidates 
        WHERE user_id = $user_id";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "Your ranking: " . $row['ranking'];
echo "<br>Specialty: " . $row['specialty'];
?>