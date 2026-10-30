<?php
// Database connection
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

// Get form data safely
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $amount = trim($_POST['amount']);
    $purpose = trim($_POST['purpose']);
    $duration = trim($_POST['duration']);

    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO loans (fullname, email, amount, purpose, duration) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $fullname, $email, $amount, $purpose, $duration);

    if ($stmt->execute()) {
        echo "Loan application submitted successfully!";
        echo "<br><a href='dashboard.html'>Back to Dashboard</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>