<?php 
include "config.php";

$name = $_GET['name'];

$sql = "SELECT * FROM candidates 
        WHERE first_name LIKE '%$name%' 
        OR last_name LIKE '%$name%'";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo $row['first_name'] . " " . $row['last_name'] . "<br>";
}
?>