<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.html");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "asa_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$result = $conn->query("SELECT * FROM loans ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Loan Applications</title>
<style>
table { width:100%; border-collapse:collapse; margin-top:20px;}
th, td { padding:10px; border:1px solid #ccc; text-align:left;}
th { background:#1a73e8; color:white;}
</style>
</head>
<body>
<h1>All Loan Applications</h1>
<table>
<tr>
<th>ID</th><th>Name</th><th>Email</th><th>Amount</th><th>Purpose</th><th>Duration</th><th>Date</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['id'];?></td>
<td><?php echo $row['fullname'];?></td>
<td><?php echo $row['email'];?></td>
<td><?php echo $row['amount'];?></td>
<td><?php echo $row['purpose'];?></td>
<td><?php echo $row['duration'];?></td>
<td><?php echo $row['created_at'];?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
<?php $conn->