<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Database connection
$servername = "sql209.infinityfree.com";
$username = "if0_41361087";
$password = "U0ty865HoKIgTg";
$dbname = "if0_41361087_asa";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check POST data
if (!isset($_POST['email']) || !isset($_POST['password'])) {
    die("Please enter email and password.");
}

$email = trim($_POST['email']);
$password_input = trim($_POST['password']);

/* =========================
   ADMIN LOGIN
========================= */

if ($email === "admin@gmail.com" && $password_input === "admin123") {

    $_SESSION['fullname'] = "Admin User";
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'admin';

    header("Location: admin_dashboard.php");
    exit();
}

/* =========================
   USER LOGIN
========================= */

$stmt = $conn->prepare("SELECT fullname, email, password FROM asa WHERE email=?");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $email);

$stmt->execute();

$stmt->store_result();

if ($stmt->num_rows === 1) {

    $stmt->bind_result($fullname, $email_db, $db_password);

    $stmt->fetch();

    // PASSWORD CHECK ONLY
    if ($password_input === $db_password) {

        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email_db;
        $_SESSION['role'] = 'user';

        header("Location: dashboard.php");
        exit();

    } else {

        echo "Incorrect Password!
        <br><br>
        <a href='Login.html'>Try Again</a>";

    }

} else {

    echo "Email not found!
    <br><br>
    <a href='Login.html'>Try Again</a>";

}

$stmt->close();

$conn->close();

?>