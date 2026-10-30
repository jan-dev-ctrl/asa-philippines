<?php
$servername="sql209.infinityfree.com";
$username="if0_41361087";
$password="U0ty865HoKIgTg";
$dbname="if0_41361087_asa";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>