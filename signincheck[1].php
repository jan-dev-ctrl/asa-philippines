<?php
$servername = "sql209.infinityfree.com";
$username = "if0_41361087";
$password = "U0ty865HoKIgTg";
$dbname = "if0_41361087_asa";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Since this is a dedicated file, we don't need the if(isset) button check anymore
$fn = trim($_POST['fullname']);
$em = trim($_POST['email']);
$ps = trim($_POST['password']);
$ad = trim($_POST['address']);
// Note: Ensure your database table is exactly named 'asa' (without the 's')
$sql = "INSERT INTO asa (fullname, email, password, address) VALUES ('$fn', '$em', '$ps', '$ad')";
if ($conn->query($sql) === TRUE) {
   header("Location: dashboard.php");
    exit(); 
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>