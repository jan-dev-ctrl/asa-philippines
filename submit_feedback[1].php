<?php
$servername = "sql209.infinityfree.com";
$username = "if0_41361087";
$password = "U0ty865HoKIgTg";
$dbname = "if0_41361087_asa";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = trim($_POST["name"]);
    $email   = trim($_POST["email"]);
    $service = trim($_POST["service"]);
    $rating  = intval($_POST["rating"]);
    $message = trim($_POST["message"]);

    if (empty($name) || empty($message) || $rating < 1) {
        echo "<script>alert('Please fill in all required fields and select a rating.'); window.history.back();</script>";
        exit;
    }

    $name    = mysqli_real_escape_string($conn, $name);
    $email   = mysqli_real_escape_string($conn, $email);
    $service = mysqli_real_escape_string($conn, $service);
    $message = mysqli_real_escape_string($conn, $message);

    $sql = "INSERT INTO feedback (name, email, service, rating, message)
            VALUES ('$name', '$email', '$service', $rating, '$message')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Thank you for your feedback, $name! We appreciate you taking the time.'); window.location.href='index.html';</script>";
    } else {
        echo "<script>alert('Something went wrong. Please try again.'); window.history.back();</script>";
    }
}

mysqli_close($conn);
?>