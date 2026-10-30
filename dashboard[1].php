<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'user'){
    header("Location: Login.html");
    exit();
}
$servername="sql209.infinityfree.com";
$username="if0_41361087";
$password="U0ty865HoKIgTg";
$dbname="if0_41361087_asa";
$conn = new mysqli($servername,$username,$password,$dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle payment proof submission
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['submit_proof'])){
    $loan_id = intval($_POST['loan_id']);
    $amount  = floatval($_POST['proof_amount']);
    $week_no = intval($_POST['week_number']);
    $note    = trim($_POST['proof_note']);
    $email   = $_SESSION['email'];
    $file_path = '';

    if(isset($_FILES['proof_file']) && $_FILES['proof_file']['error']==0){
        $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','pdf'];
        if(in_array(strtolower($ext), $allowed)){
            $filename = 'proof_' . $loan_id . '_' . time() . '.' . $ext;
            $upload_dir = 'uploads/proofs/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            move_uploaded_file($_FILES['proof_file']['tmp_name'], $upload_dir . $filename);
            $file_path = $upload_dir . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO payment_proofs (loan_id,email,amount,week_number,proof_file,note,status) VALUES (?,?,?,?,?,?,'Pending')");
    $stmt->bind_param("isdiss", $loan_id, $email, $amount, $week_no, $file_path, $note);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?proof=sent"); exit();
}

// Fetch loans
$stmt = $conn->prepare("SELECT id, amount, purpose, duration, status, created_at, weeks, weekly_payment, total_payable, due_date FROM loans WHERE email=? ORDER BY id DESC");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Fetch active loan
$stmt2 = $conn->prepare("SELECT * FROM loans WHERE email=? AND status='Approved' ORDER BY id DESC LIMIT 1");
$stmt2->bind_param("s", $_SESSION['email']);
$stmt2->execute();
$active_loan = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Fetch profile — UPDATED: now includes address
$stmt3 = $conn->prepare("SELECT fullname, email, address FROM asa WHERE email=?");
$stmt3->bind_param("s", $_SESSION['email']);
$stmt3->execute();
$profile = $stmt3->get_result()->fetch_assoc();
$stmt3->close();

// Payments made
$total_paid = 0;
$payments_list = [];
if($active_loan){
    $stmt4 = $conn->prepare("SELECT * FROM payments WHERE loan_id=? ORDER BY payment_date ASC");
    $stmt4->bind_param("i", $active_loan['id']);
    $stmt4->execute();
    $pr = $stmt4->get_result();
    while($p = $pr->fetch_assoc()){
        $payments_list[] = $p;
        $total_paid += $p['amount_paid'];
    }
    $stmt4->close();
}

// Pending proofs
$pending_proofs = [];
if($active_loan){
    $stmt5 = $conn->prepare("SELECT * FROM payment_proofs WHERE loan_id=? ORDER BY submitted_at DESC");
    $stmt5->bind_param("i", $active_loan['id']);
    $stmt5->execute();
    $ppr = $stmt5->get_result();
    while($pp = $ppr->fetch_assoc()) $pending_proofs[] = $pp;
    $stmt5->close();
}

$balance_remaining = $active_loan ? ($active_loan['total_payable'] - $total_paid) : 0;
$weeks_paid = $active_loan && $active_loan['weekly_payment'] > 0 ? floor($total_paid / $active_loan['weekly_payment']) : 0;
$total_weeks = $active_loan['weeks'] ?? 23;
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #0a3d2b;
    min-height: 100vh;
    padding: 2rem;
}
h1 {
    font-size: 22px; font-weight: 600; color: #ffffff; margin-bottom: 4px;
}
h1::before {
    content: 'ASA Philippines Foundation';
    display: block; font-size: 11px; font-weight: 500;
    color: #9fe1cb; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px;
}
h2 { font-size: 16px; font-weight: 600; color: #ffffff; margin-top: 2rem; margin-bottom: 0.75rem; }
a {
    text-decoration: none; padding: 7px 16px; border-radius: 7px;
    background: #ffffff; color: #0a3d2b; font-size: 13px; font-weight: 600;
    margin-right: 8px; margin-top: 1rem; display: inline-block; transition: opacity 0.2s;
}
a:hover { opacity: 0.85; }
p { color: #9fe1cb; font-size: 14px; margin-top: 1rem; }
table {
    width: 100%; border-collapse: collapse; background: #ffffff;
    border-radius: 12px; overflow: hidden; margin-top: 1rem;
}
th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e0ede7; font-size: 14px; }
th { background: #0a3d2b; color: #ffffff; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f7fcf9; }
.status-Pending  { color: #b97a00; font-weight: 600; }
.status-Approved { color: #1a7a54; font-weight: 600; }
.status-Rejected { color: #c0392b; font-weight: 600; }

.cards-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 12px; margin-top: 0.75rem;
}
.card {
    background: #ffffff; border-radius: 12px; padding: 1.25rem 1.5rem; border-left: 4px solid #0a3d2b;
}
.card.danger { border-left-color: #c0392b; }
.card.danger .card-value { color: #c0392b; }
.card.warning { border-left-color: #b97a00; }
.card.warning .card-value { color: #b97a00; }
.card-label { font-size: 11px; font-weight: 600; color: #5a8a72; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.card-value { font-size: 20px; font-weight: 600; color: #0a3d2b; }
.card-sub { font-size: 12px; color: #7a9e8e; margin-top: 4px; }

/* Progress bar */
.progress-wrap { background: #ffffff; border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 0.75rem; }
.progress-label { font-size: 13px; font-weight: 600; color: #0a3d2b; margin-bottom: 8px; display: flex; justify-content: space-between; }
.progress-bar { background: #e0ede7; border-radius: 20px; height: 12px; overflow: hidden; }
.progress-fill { background: #1a7a54; height: 100%; border-radius: 20px; transition: width 0.5s; }

/* Weekly schedule */
.weeks-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(70px,1fr)); gap: 8px; margin-top: 0.75rem;
}
.week-box {
    background: #ffffff; border-radius: 8px; padding: 8px; text-align: center;
}
.week-box .wk-num { font-size: 11px; color: #7a9e8e; font-weight: 600; }
.week-box .wk-status { font-size: 18px; margin-top: 2px; }
.week-box .wk-amt { font-size: 10px; color: #7a9e8e; }
.week-box.paid { background: #e8f5ef; }
.week-box.current { background: #fff8e1; border: 2px solid #b97a00; }

/* Payment proof form */
.proof-form {
    background: #ffffff; border-radius: 12px; padding: 1.5rem 2rem;
    border-top: 4px solid #1a7a54; margin-top: 0.75rem;
}
.proof-form h3 { font-size: 15px; font-weight: 600; color: #0a3d2b; margin-bottom: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.form-row label, .proof-form label { font-size: 12px; font-weight: 500; color: #2d6b4f; display: block; margin-bottom: 4px; }
.form-row input, .form-row select, .form-row textarea,
.proof-form input[type="number"], .proof-form input[type="file"],
.proof-form input[type="text"], .proof-form select {
    width: 100%; padding: 8px 10px; border: 1.5px solid #c8ddd5; border-radius: 7px;
    font-size: 13px; color: #1a3d2b; background: #f7fcf9; font-family: inherit; outline: none;
}
.btn-proof {
    padding: 9px 22px; background: #1a7a54; color: #fff; border: none;
    border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: inherit; margin-top: 4px; transition: background 0.2s;
}
.btn-proof:hover { background: #0f5c3f; }

.proof-status-Pending  { color: #b97a00; font-weight: 600; }
.proof-status-Verified { color: #1a7a54; font-weight: 600; }
.proof-status-Rejected { color: #c0392b; font-weight: 600; }

.alert-success {
    background: #e8f5ef; color: #1a7a54; border-radius: 8px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 1rem; font-weight: 500;
}
.no-loan { background: #ffffff; border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 0.75rem; font-size: 14px; color: #7a9e8e; }

/* PROFILE CARD — UPDATED */
.profile-card {
    background: #ffffff; border-radius: 12px; padding: 1.5rem;
    margin-top: 0.75rem; display: flex; align-items: flex-start; gap: 1rem;
}
.avatar {
    width: 52px; height: 52px; border-radius: 50%; background: #0a3d2b; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 600; flex-shrink: 0;
}
.profile-info .name { font-size: 16px; font-weight: 600; color: #0a3d2b; }
.profile-info .email-txt { font-size: 13px; color: #7a9e8e; margin-top: 2px; }
.profile-info .address-txt {
    font-size: 13px; color: #5a8a72; margin-top: 4px;
    display: flex; align-items: flex-start; gap: 5px;
}
.profile-info .address-txt span { color: #2d6b4f; }
.role-badge {
    display: inline-block; background: #e8f5ef; color: #1a7a54;
    font-size: 11px; font-weight: 600; padding: 2px 10px;
    border-radius: 20px; margin-top: 6px;
}

@media screen and (max-width: 768px) {
    table, thead, tbody, th, td, tr { display: block; }
    th { display: none; }
    td { position: relative; padding-left: 50%; border: none; border-bottom: 1px solid #e0ede7; }
    td:before { position: absolute; top: 12px; left: 10px; width: 45%; white-space: nowrap; font-weight: 600; color: #2d6b4f; font-size: 12px; }
    td:nth-of-type(1):before { content: "ID"; }
    td:nth-of-type(2):before { content: "Amount"; }
    td:nth-of-type(3):before { content: "Purpose"; }
    td:nth-of-type(4):before { content: "Duration"; }
    td:nth-of-type(5):before { content: "Status"; }
    td:nth-of-type(6):before { content: "Date Submitted"; }
    .form-row { grid-template-columns: 1fr; }
    .profile-card { flex-direction: column; }
}
</style>
</head>
<body>

<h1>Welcome, <?php echo $_SESSION['fullname']; ?>!</h1>
<a href="applyloan.php">Apply Loan</a> | <a href="index.html">Logout</a>

<?php if(isset($_GET['proof']) && $_GET['proof']=='sent'): ?>
<div class="alert-success" style="margin-top:1rem;">✓ Payment proof submitted! Waiting for admin verification.</div>
<?php endif; ?>

<!-- PROFILE -->
<h2>Profile Information</h2>
<div class="profile-card">
    <div class="avatar"><?php echo strtoupper(substr($_SESSION['fullname'],0,1)); ?></div>
    <div class="profile-info">
        <div class="name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
        <div class="email-txt"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
        <div class="address-txt">
            📍 <span><?php echo !empty($profile['address']) ? htmlspecialchars($profile['address']) : 'No address on file'; ?></span>
        </div>
        <span class="role-badge">Member</span>
    </div>
</div>

<!-- CURRENT LOAN -->
<h2>Current Loan</h2>
<?php if($active_loan): ?>
<div class="cards-grid">
    <div class="card">
        <div class="card-label">Loan Amount</div>
        <div class="card-value">₱<?php echo number_format($active_loan['amount'],2); ?></div>
        <div class="card-sub"><?php echo htmlspecialchars($active_loan['purpose']); ?></div>
    </div>
    <div class="card">
        <div class="card-label">Total Payable (w/ 15% interest)</div>
        <div class="card-value">₱<?php echo number_format($active_loan['total_payable'],2); ?></div>
        <div class="card-sub">Interest: ₱<?php echo number_format($active_loan['amount']*0.15,2); ?></div>
    </div>
    <div class="card warning">
        <div class="card-label">Weekly Payment</div>
        <div class="card-value">₱<?php echo number_format($active_loan['weekly_payment'],2); ?></div>
        <div class="card-sub">Every week for <?php echo $active_loan['weeks']; ?> weeks</div>
    </div>
    <div class="card warning">
        <div class="card-label">Due Date</div>
        <div class="card-value" style="font-size:14px;"><?php echo date('F d, Y', strtotime($active_loan['due_date'])); ?></div>
        <div class="card-sub"><?php echo $active_loan['weeks']; ?>-week term</div>
    </div>
    <div class="card <?php echo $balance_remaining > 0 ? 'danger' : ''; ?>">
        <div class="card-label">Balance Remaining</div>
        <div class="card-value">₱<?php echo number_format($balance_remaining,2); ?></div>
        <div class="card-sub"><?php echo $balance_remaining <= 0 ? '🎉 Fully paid!' : 'Outstanding'; ?></div>
    </div>
    <div class="card">
        <div class="card-label">Total Paid</div>
        <div class="card-value">₱<?php echo number_format($total_paid,2); ?></div>
        <div class="card-sub">Week <?php echo $weeks_paid; ?> of <?php echo $total_weeks; ?></div>
    </div>
</div>

<!-- PROGRESS BAR -->
<?php
$progress = $active_loan['total_payable'] > 0 ? min(100, round(($total_paid / $active_loan['total_payable']) * 100)) : 0;
?>
<div class="progress-wrap" style="margin-top:1rem;">
    <div class="progress-label">
        <span>Payment Progress</span>
        <span><?php echo $progress; ?>%</span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width:<?php echo $progress; ?>%"></div>
    </div>
</div>

<!-- WEEKLY SCHEDULE -->
<h2>Weekly Payment Schedule</h2>
<div class="weeks-grid">
<?php for($w=1; $w<=$total_weeks; $w++):
    $is_paid = $w <= $weeks_paid;
    $is_current = $w == $weeks_paid + 1;
    $cls = $is_paid ? 'paid' : ($is_current ? 'current' : '');
?>
<div class="week-box <?php echo $cls; ?>">
    <div class="wk-num">Wk <?php echo $w; ?></div>
    <div class="wk-status"><?php echo $is_paid ? '✅' : ($is_current ? '⏳' : '⬜'); ?></div>
    <div class="wk-amt">₱<?php echo number_format($active_loan['weekly_payment'],0); ?></div>
</div>
<?php endfor; ?>
</div>

<!-- SUBMIT PAYMENT PROOF -->
<h2>Submit Payment Proof</h2>
<div class="proof-form">
    <h3>Upload GCash / Bank Transfer Receipt</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="loan_id" value="<?php echo $active_loan['id']; ?>">
        <div class="form-row">
            <div>
                <label>Week Number</label>
                <select name="week_number" required>
                    <?php for($w=1; $w<=$total_weeks; $w++): ?>
                    <option value="<?php echo $w; ?>" <?php if($w==$weeks_paid+1) echo 'selected'; ?>>
                        Week <?php echo $w; ?> — ₱<?php echo number_format($active_loan['weekly_payment'],2); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Amount Paid (PHP)</label>
                <input type="number" name="proof_amount" step="0.01"
                    value="<?php echo number_format($active_loan['weekly_payment'],2,'.',''); ?>" required>
            </div>
        </div>
        <div style="margin-bottom:12px;">
            <label>Receipt / Proof of Payment (JPG, PNG, PDF)</label>
            <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
        <div style="margin-bottom:12px;">
            <label>Note (optional)</label>
            <input type="text" name="proof_note" placeholder="e.g. GCash ref #12345">
        </div>
        <button type="submit" name="submit_proof" class="btn-proof">Submit Payment Proof</button>
    </form>
</div>

<!-- PROOF SUBMISSION HISTORY -->
<h2>My Submitted Proofs</h2>
<?php if(count($pending_proofs) > 0): ?>
<table>
    <tr><th>Week</th><th>Amount</th><th>Receipt</th><th>Note</th><th>Status</th><th>Submitted</th></tr>
    <?php foreach($pending_proofs as $pp): ?>
    <tr>
        <td>Week <?php echo $pp['week_number']; ?></td>
        <td>₱<?php echo number_format($pp['amount'],2); ?></td>
        <td>
            <?php if($pp['proof_file']): ?>
            <a href="<?php echo $pp['proof_file']; ?>" target="_blank" style="background:#0a3d2b;color:#fff;padding:4px 10px;border-radius:5px;font-size:12px;">View</a>
            <?php else: echo '—'; endif; ?>
        </td>
        <td><?php echo htmlspecialchars($pp['note'] ?? '—'); ?></td>
        <td class="proof-status-<?php echo $pp['status']; ?>"><?php echo $pp['status']; ?></td>
        <td><?php echo date('M d, Y', strtotime($pp['submitted_at'])); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<div class="no-loan">No payment proofs submitted yet.</div>
<?php endif; ?>

<!-- VERIFIED PAYMENT HISTORY -->
<h2>Payment History</h2>
<?php if(count($payments_list) > 0): ?>
<table>
    <tr><th>#</th><th>Amount Paid</th><th>Date</th><th>Note</th></tr>
    <?php $i=1; foreach($payments_list as $pay): ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td>₱<?php echo number_format($pay['amount_paid'],2); ?></td>
        <td><?php echo $pay['payment_date']; ?></td>
        <td><?php echo htmlspecialchars($pay['note'] ?? '—'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<div class="no-loan">No verified payments yet.</div>
<?php endif; ?>

<?php else: ?>
<div class="no-loan">You have no active approved loan at the moment.</div>
<?php endif; ?>

<!-- LOAN APPLICATIONS TABLE -->
<h2>Your Loan Applications</h2>
<?php if($result->num_rows > 0): ?>
<table>
    <tr><th>ID</th><th>Amount</th><th>Purpose</th><th>Duration</th><th>Status</th><th>Date Submitted</th></tr>
    <?php while($row = $result->fetch_assoc()):
    $statusClass = ucfirst(strtolower($row['status'])); ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td>₱<?php echo number_format($row['amount'],2); ?></td>
        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
        <td><?php echo $row['duration']; ?></td>
        <td class="status-<?php echo $statusClass; ?>"><?php echo $statusClass; ?></td>
        <td><?php echo $row['created_at']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>You have not applied for any loans yet.</p>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>