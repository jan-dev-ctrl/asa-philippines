<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

if($role === 'admin'){
    $sql = "SELECT * FROM loans";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM loans WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>
<tr>
<th>ID</th><th>Fullname</th><th>Email</th><th>Amount</th><th>Purpose</th><th>Duration</th><th>Status</th>";
if($role === 'admin') echo "<th>Action</th>";
echo "</tr>";

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td>".$row['id']."</td>";
    echo "<td>".$row['fullname']."</td>";
    echo "<td>".$row['email']."</td>";
    echo "<td>".$row['amount']."</td>";
    echo "<td>".$row['purpose']."</td>";
    echo "<td>".$row['duration']."</td>";
    echo "<td>".$row['status']."</td>";
    
    if($role === 'admin'){
        echo "<td>
        <a href='approve_loan.php?loan_id=".$row['id']."&action=accept'>Accept</a> |
        <a href='approve_loan.php?loan_id=".$row['id']."&action=reject'>Reject</a>
        </td>";
    }
    
    echo "</tr>";
}

echo "</table>";
?>