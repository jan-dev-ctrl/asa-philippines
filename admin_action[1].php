<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Connect to DB
$servername = "sql209.infinityfree.com";
$username = "if0_41361087";
$password = "U0ty865HoKIgTg";
$dbname = "if0_41361087_asa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Process Approve/Reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == "approve") {
        $stmt = $conn->prepare("UPDATE loans SET status='Approved' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action == "reject") {
        $stmt = $conn->prepare("UPDATE loans SET status='Rejected' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all pending loans
$result = $conn->query("SELECT * FROM loans ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - ASA Foundation</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 0;}
header { background: #1a73e8; color: white; padding: 15px; text-align: center; }
.container { padding: 20px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
th { background: #1a73e8; color: white; }
a.button { padding: 5px 10px; color: white; text-decoration: none; border-radius: 4px; }
a.approve { background: #28a745; }
a.reject { background: #dc3545; }
a.button:hover { opacity: 0.8; }
</style>
</head>
<body>
<header>
<h1>Admin Dashboard - New Loan Applicants</h1>
<p>Welcome, <?php echo $_SESSION['fullname']; ?> | <a href="logout.php" style="color:white;">Logout</a></p>
</header>

<div class="container">
<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Amount</th>
<th>Purpose</th>
<th>Duration</th>
<th>Status</th>
<th>Actions</th>
</tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['fullname']); ?></td>
<td><?php echo htmlspecialchars($row['email']); ?></td>
<td><?php echo number_format($row['amount'],2); ?></td>
<td><?php echo htmlspecialchars($row['purpose']); ?></td>
<td><?php echo $row['duration']; ?></td>
<td><?php echo $row['status']; ?></td>
<td>
<?php if ($row['status'] == 'Pending'): ?>
<a href="admin_dashboard.php?action=approve&id=<?php echo $row['id']; ?>" class="button approve">Approve</a>
<a href="admin_dashboard.php?action=reject&id=<?php echo $row['id']; ?>" class="button reject">Reject</a>
<?php else: ?>
<span style="color:gray;">No actions</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>

<?php $conn->close(); ?>