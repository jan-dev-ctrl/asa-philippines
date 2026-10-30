<?php
include 'db.php'; // include database connection
session_start();

// Ensure only admin can approve/reject
if($_SESSION['role'] !== 'admin') {
    die("Only admins can approve/reject loans.");
}

if(isset($_GET['loan_id']) && isset($_GET['action'])) {
    $loan_id = intval($_GET['loan_id']);
    $action = $_GET['action']; // accept or reject

    if(!in_array($action, ['accept', 'reject'])){
        die("Invalid action.");
    }

    $status = $action === 'accept' ? 'accepted' : 'rejected';

    $stmt = $conn->prepare("UPDATE loans SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $loan_id);
    $stmt->execute();

    echo "Loan #$loan_id has been $status. <a href='view_loans.php'>Back to Loans</a>";
} else {
    echo "Missing parameters.";
}
?>