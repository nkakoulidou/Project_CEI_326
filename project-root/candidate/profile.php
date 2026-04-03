<?php
include "../includes/db.php";
session_start();
include "config.php";

$user_id = $_SESSION['user_id'];

// 👉 Αν πατηθεί το κουμπί update
if (isset($_POST['update'])) {

    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $specialty = $_POST['specialty'];

    $sql = "UPDATE candidates 
            SET first_name='$first', 
                last_name='$last',
                specialty='$specialty'
            WHERE user_id=$user_id";

    $conn->query($sql);
}

// 👉 Φέρνουμε τα δεδομένα
$sql = "SELECT * FROM candidates WHERE user_id = $user_id";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
?>

<h2>My Profile</h2>

<form method="POST">
    First Name: <br>
    <input type="text" name="first_name" value="<?php echo $data['first_name']; ?>"><br><br>

    Last Name: <br>
    <input type="text" name="last_name" value="<?php echo $data['last_name']; ?>"><br><br>

    Specialty: <br>
    <input type="text" name="specialty" value="<?php echo $data['specialty']; ?>"><br><br>

    Ranking: <br>
    <input type="text" value="<?php echo $data['ranking']; ?>" disabled><br><br>

    <button type="submit" name="update">Update Profile</button>
</form>