<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location: Login.html"); exit();
}
$servername="sql209.infinityfree.com";
$username="if0_41361087";
$password="U0ty865HoKIgTg";
$dbname="if0_41361087_asa";
$conn = new mysqli($servername,$username,$password,$dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle approve/reject
if(isset($_GET['action']) && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $actionMap = ['approve' => 'Approved', 'reject' => 'Rejected'];
    $action = $actionMap[$_GET['action']] ?? null;
    if($action){
        $stmt=$conn->prepare("UPDATE loans SET status=? WHERE id=?");
        $stmt->bind_param("si",$action,$id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php"); exit();
    }
}

// --- CRUD: Delete Loan ---
if(isset($_GET['action']) && $_GET['action']=='delete' && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $stmt=$conn->prepare("DELETE FROM loans WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}

// --- CRUD: Edit Loan ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])){
    $id=intval($_POST['edit_id']);
    $fullname=trim($_POST['fullname']);
    $email=trim($_POST['email']);
    $amount=trim($_POST['amount']);
    $purpose=trim($_POST['purpose']);
    $duration=trim($_POST['duration']);
    $status=trim($_POST['status']);
    $stmt=$conn->prepare("UPDATE loans SET fullname=?,email=?,amount=?,purpose=?,duration=?,status=? WHERE id=?");
    $stmt->bind_param("ssdsssi",$fullname,$email,$amount,$purpose,$duration,$status,$id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}

// --- CRUD: Add Loan ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_loan'])){
    $fullname=trim($_POST['fullname']);
    $email=trim($_POST['email']);
    $amount=trim($_POST['amount']);
    $purpose=trim($_POST['purpose']);
    $duration=trim($_POST['duration']);
    $status='Pending';
    $stmt=$conn->prepare("INSERT INTO loans (fullname,email,amount,purpose,duration,status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssdsss",$fullname,$email,$amount,$purpose,$duration,$status);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php"); exit();
}

// --- Employee: Add ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_employee'])){
    $emp_name=trim($_POST['emp_name']);
    $emp_email=trim($_POST['emp_email']);
    $emp_role=trim($_POST['emp_role']);
    $emp_phone=trim($_POST['emp_phone']);
    $stmt=$conn->prepare("INSERT INTO employees (fullname,email,role,phone) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss",$emp_name,$emp_email,$emp_role,$emp_phone);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#employees"); exit();
}

// --- Employee: Delete ---
if(isset($_GET['action']) && $_GET['action']=='delete_emp' && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $stmt=$conn->prepare("DELETE FROM employees WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#employees"); exit();
}

// --- Payment: Add ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_payment'])){
    $loan_id=intval($_POST['loan_id']);
    $email=trim($_POST['pay_email']);
    $amount_paid=trim($_POST['amount_paid']);
    $payment_date=trim($_POST['payment_date']);
    $note=trim($_POST['note']);
    $stmt=$conn->prepare("INSERT INTO payments (loan_id,email,amount_paid,payment_date,note) VALUES (?,?,?,?,?)");
    $stmt->bind_param("isdss",$loan_id,$email,$amount_paid,$payment_date,$note);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#payments"); exit();
}

// --- Payment: Delete ---
if(isset($_GET['action']) && $_GET['action']=='delete_pay' && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $stmt=$conn->prepare("DELETE FROM payments WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#payments"); exit();
}

// --- Verify payment proof ---
if(isset($_GET['action']) && $_GET['action']=='verify_proof' && isset($_GET['id'])){
    $proof_id = intval($_GET['id']);
    $loan_id  = intval($_GET['loan_id']);
    $email    = urldecode($_GET['email']);
    $amount   = floatval($_GET['amount']);
    $today    = date('Y-m-d');
    $s = $conn->prepare("UPDATE payment_proofs SET status='Verified' WHERE id=?");
    $s->bind_param("i", $proof_id); $s->execute(); $s->close();
    $s2 = $conn->prepare("INSERT INTO payments (loan_id,email,amount_paid,payment_date,note) VALUES (?,?,?,?,'Verified from proof')");
    $s2->bind_param("isds", $loan_id, $email, $amount, $today);
    $s2->execute(); $s2->close();
    header("Location: admin_dashboard.php#payments"); exit();
}

// --- Reject payment proof ---
if(isset($_GET['action']) && $_GET['action']=='reject_proof' && isset($_GET['id'])){
    $proof_id = intval($_GET['id']);
    $s = $conn->prepare("UPDATE payment_proofs SET status='Rejected' WHERE id=?");
    $s->bind_param("i", $proof_id); $s->execute(); $s->close();
    header("Location: admin_dashboard.php#payments"); exit();
}

// --- Delete Feedback ---
if(isset($_GET['action']) && $_GET['action']=='delete_feedback' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#feedback"); exit();
}

// Fetch edit row
$edit_row = null;
if(isset($_GET['action']) && $_GET['action']=='edit' && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $stmt=$conn->prepare("SELECT * FROM loans WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $edit_row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// --- STATS ---
$total_clients   = $conn->query("SELECT COUNT(DISTINCT email) as c FROM users WHERE role='user'")->fetch_assoc()['c'] ?? 0;
$total_approved  = $conn->query("SELECT COUNT(*) as c FROM loans WHERE status='Approved'")->fetch_assoc()['c'] ?? 0;
$total_pending   = $conn->query("SELECT COUNT(*) as c FROM loans WHERE status='Pending'")->fetch_assoc()['c'] ?? 0;
$total_rejected  = $conn->query("SELECT COUNT(*) as c FROM loans WHERE status='Rejected'")->fetch_assoc()['c'] ?? 0;
$total_collected = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as s FROM payments")->fetch_assoc()['s'] ?? 0;
$total_loaned    = $conn->query("SELECT COALESCE(SUM(amount),0) as s FROM loans WHERE status='Approved'")->fetch_assoc()['s'] ?? 0;
$total_balance   = $total_loaned - $total_collected;
$pending_proofs_count = $conn->query("SELECT COUNT(*) as c FROM payment_proofs WHERE status='Pending'")->fetch_assoc()['c'] ?? 0;

// --- Feedback Stats ---
$feedback_total = $conn->query("SELECT COUNT(*) as c FROM feedback")->fetch_assoc()['c'] ?? 0;
$feedback_avg   = $conn->query("SELECT ROUND(AVG(rating),1) as a FROM feedback")->fetch_assoc()['a'] ?? 0;
$feedback_five  = $conn->query("SELECT COUNT(*) as c FROM feedback WHERE rating=5")->fetch_assoc()['c'] ?? 0;
$feedback_today = $conn->query("SELECT COUNT(*) as c FROM feedback WHERE DATE(submitted_at)=CURDATE()")->fetch_assoc()['c'] ?? 0;

// Fetch all data
$result         = $conn->query("SELECT * FROM loans ORDER BY id DESC");
$payments_all   = $conn->query("SELECT p.*, l.fullname FROM payments p LEFT JOIN loans l ON p.loan_id=l.id ORDER BY p.payment_date DESC");
$employees      = $conn->query("SELECT * FROM employees ORDER BY id DESC");
$approved_loans = $conn->query("SELECT id, fullname, email, amount FROM loans WHERE status='Approved' ORDER BY id DESC");
$proofs_all     = $conn->query("SELECT pp.*, l.fullname, l.weekly_payment FROM payment_proofs pp LEFT JOIN loans l ON pp.loan_id=l.id ORDER BY pp.submitted_at DESC");
$feedbacks_all  = $conn->query("SELECT * FROM feedback ORDER BY submitted_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #0a3d2b;
    min-height: 100vh;
    padding: 2rem;
}
h1 {
    font-size: 22px;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 4px;
}
h1::before {
    content: 'ASA Philippines Foundation';
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #9fe1cb;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.top-bar {
    font-size: 13px;
    color: #9fe1cb;
    margin-bottom: 1.5rem;
}
.top-bar a {
    color: #9fe1cb;
    font-weight: 600;
    text-decoration: none;
    padding: 0;
    background: none;
    border-radius: 0;
    font-size: 13px;
}
.top-bar a:hover { text-decoration: underline; }

/* STAT CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 2rem;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    border-left: 4px solid #0a3d2b;
}
.stat-card.green { border-left-color: #1a7a54; }
.stat-card.amber { border-left-color: #b97a00; }
.stat-card.red   { border-left-color: #c0392b; }
.stat-card.blue  { border-left-color: #1a5fa8; }
.stat-label {
    font-size: 11px;
    font-weight: 600;
    color: #5a8a72;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.stat-value { font-size: 22px; font-weight: 600; color: #0a3d2b; }
.stat-sub   { font-size: 12px; color: #7a9e8e; margin-top: 4px; }

/* TABS */
.tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.tab-btn {
    padding: 8px 18px;
    border-radius: 7px;
    border: none;
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.2s;
    position: relative;
}
.tab-btn:hover, .tab-btn.active { background: #ffffff; color: #0a3d2b; }
.badge {
    position: absolute;
    top: -6px; right: -6px;
    background: #c0392b;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 20px;
}

.tab-section { display: none; }
.tab-section.active { display: block; }

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.75rem;
    margin-top: 1.5rem;
}
.section-title:first-child { margin-top: 0; }

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
th, td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #e0ede7;
    font-size: 14px;
}
th {
    background: #0a3d2b;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f7fcf9; }

/* LINKS */
a {
    padding: 5px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    margin-right: 5px;
    font-size: 13px;
    font-weight: 500;
    display: inline-block;
}
a[href*="approve"]         { background-color: #1a7a54; }
a[href*="reject"]          { background-color: #c0392b; }
a[href*="edit"]            { background-color: #b97a00; }
a[href*="delete"]          { background-color: #7b2d2d; }
a[href*="delete_emp"]      { background-color: #7b2d2d; }
a[href*="delete_pay"]      { background-color: #7b2d2d; }
a[href*="delete_feedback"] { background-color: #7b2d2d; }
a[href*="verify_proof"]    { background-color: #1a7a54; }
a[href*="reject_proof"]    { background-color: #c0392b; }
a:hover { opacity: 0.85; }

.status-Pending  { color: #b97a00; font-weight: 600; }
.status-Approved { color: #1a7a54; font-weight: 600; }
.status-Rejected { color: #c0392b; font-weight: 600; }

/* CRUD FORMS */
.crud-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    border-top: 4px solid #0a3d2b;
}
.crud-section h3 {
    font-size: 15px;
    font-weight: 600;
    color: #0a3d2b;
    margin-bottom: 1rem;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 1rem;
}
.form-grid label {
    font-size: 12px;
    font-weight: 500;
    color: #2d6b4f;
    display: block;
    margin-bottom: 4px;
}
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1.5px solid #c8ddd5;
    border-radius: 7px;
    font-size: 13px;
    color: #1a3d2b;
    background: #f7fcf9;
    font-family: inherit;
    outline: none;
}
.form-grid input:focus,
.form-grid select:focus,
.form-grid textarea:focus {
    border-color: #0a3d2b;
    background: #fff;
}
.btn-submit {
    padding: 9px 22px;
    background: #0a3d2b;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.2s;
}
.btn-submit:hover { background: #0f5c3f; }
.btn-cancel {
    padding: 9px 22px;
    background: #aab8b2;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    margin-left: 8px;
    text-decoration: none;
    display: inline-block;
}

/* COLLECTION REPORT */
.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 1.5rem;
}
.report-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    border-top: 4px solid #0a3d2b;
}
.report-card.green { border-top-color: #1a7a54; }
.report-card.red   { border-top-color: #c0392b; }
.report-card.amber { border-top-color: #b97a00; }
.report-card.blue  { border-top-color: #1a5fa8; }
.report-label { font-size: 12px; color: #5a8a72; font-weight: 600; text-transform: uppercase; margin-bottom: 6px; }
.report-value { font-size: 22px; font-weight: 600; color: #0a3d2b; }

/* PROOF STATUS */
.proof-pending  { color: #b97a00; font-weight: 600; }
.proof-verified { color: #1a7a54; font-weight: 600; }
.proof-rejected { color: #c0392b; font-weight: 600; }

/* IMAGE PREVIEW */
.proof-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #c8ddd5;
    cursor: pointer;
    transition: transform 0.2s;
}
.proof-thumb:hover { transform: scale(1.1); }

/* MODAL */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    max-width: 600px;
    width: 90%;
    position: relative;
    text-align: center;
}
.modal-box img { max-width: 100%; border-radius: 8px; }
.modal-close {
    position: absolute;
    top: 10px; right: 14px;
    font-size: 20px;
    cursor: pointer;
    color: #0a3d2b;
    font-weight: 700;
    background: none;
    border: none;
}
</style>
</head>
<body>

<h1>Admin Dashboard</h1>
<div class="top-bar">Welcome, <?php echo $_SESSION['fullname'];?> | <a href="index.html">Logout</a></div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Clients</div>
        <div class="stat-value"><?php echo $total_clients; ?></div>
        <div class="stat-sub">Registered users</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Approved Loans</div>
        <div class="stat-value"><?php echo $total_approved; ?></div>
        <div class="stat-sub">out of <?php echo $total_approved+$total_pending+$total_rejected; ?> total</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?php echo $total_pending; ?></div>
        <div class="stat-sub">Awaiting review</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?php echo $total_rejected; ?></div>
        <div class="stat-sub">Declined applications</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Collected</div>
        <div class="stat-value">₱<?php echo number_format($total_collected,2); ?></div>
        <div class="stat-sub">All payments received</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Outstanding Balance</div>
        <div class="stat-value">₱<?php echo number_format($total_balance,2); ?></div>
        <div class="stat-sub">Remaining to collect</div>
    </div>
</div>

<!-- TABS -->
<div class="tabs">
    <button class="tab-btn active" onclick="showTab('loans', this)">Loan Applications</button>
    <button class="tab-btn" onclick="showTab('payments', this)">
        Payment Tracking
        <?php if($pending_proofs_count > 0): ?>
        <span class="badge"><?php echo $pending_proofs_count; ?></span>
        <?php endif; ?>
    </button>
    <button class="tab-btn" onclick="showTab('collection', this)">Collection Report</button>
    <button class="tab-btn" onclick="showTab('employees', this)">Employee Management</button>
    <button class="tab-btn" onclick="showTab('feedback', this)">
        Client Feedback
        <?php if($feedback_today > 0): ?>
        <span class="badge"><?php echo $feedback_today; ?></span>
        <?php endif; ?>
    </button>
</div>

<!-- ===== TAB: LOANS ===== -->
<div id="tab-loans" class="tab-section active">

    <div class="crud-section">
        <h3>+ Add New Loan Record</h3>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label>Amount (PHP)</label>
                    <input type="number" name="amount" min="1" required>
                </div>
                <div>
                    <label>Purpose</label>
                    <input type="text" name="purpose" required>
                </div>
                <div>
                    <label>Duration</label>
                    <select name="duration" required>
                        <option value="">--Select--</option>
                        <option value="1 month">1 Month</option>
                        <option value="3 months">3 Months</option>
                        <option value="6 months">6 Months</option>
                        <option value="12 months">12 Months</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_loan" class="btn-submit">Add Loan</button>
        </form>
    </div>

    <?php if($edit_row): ?>
    <div class="crud-section" style="border-top-color:#b97a00;">
        <h3>Edit Loan Record #<?php echo $edit_row['id']; ?></h3>
        <form method="POST">
            <input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>">
            <div class="form-grid">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($edit_row['fullname']); ?>" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_row['email']); ?>" required>
                </div>
                <div>
                    <label>Amount (PHP)</label>
                    <input type="number" name="amount" value="<?php echo $edit_row['amount']; ?>" required>
                </div>
                <div>
                    <label>Purpose</label>
                    <input type="text" name="purpose" value="<?php echo htmlspecialchars($edit_row['purpose']); ?>" required>
                </div>
                <div>
                    <label>Duration</label>
                    <select name="duration" required>
                        <option value="1 month"   <?php if($edit_row['duration']=='1 month') echo 'selected'; ?>>1 Month</option>
                        <option value="3 months"  <?php if($edit_row['duration']=='3 months') echo 'selected'; ?>>3 Months</option>
                        <option value="6 months"  <?php if($edit_row['duration']=='6 months') echo 'selected'; ?>>6 Months</option>
                        <option value="12 months" <?php if($edit_row['duration']=='12 months') echo 'selected'; ?>>12 Months</option>
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Pending"  <?php if($edit_row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Approved" <?php if($edit_row['status']=='Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Rejected" <?php if($edit_row['status']=='Rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-submit">Save Changes</button>
            <a href="admin_dashboard.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
    <?php endif; ?>

    <div class="section-title">All Loan Applications</div>
    <table>
        <tr>
            <th>ID</th><th>Full Name</th><th>Email</th><th>Amount</th>
            <th>Purpose</th><th>Duration</th><th>Status</th><th>Actions</th>
        </tr>
        <?php while($row=$result->fetch_assoc()): ?>
        <?php $statusClass = ucfirst(strtolower($row['status'])); ?>
        <tr>
            <td><?php echo $row['id'];?></td>
            <td><?php echo $row['fullname'];?></td>
            <td><?php echo $row['email'];?></td>
            <td>₱<?php echo number_format($row['amount'],2);?></td>
            <td><?php echo htmlspecialchars($row['purpose']);?></td>
            <td><?php echo $row['duration'];?></td>
            <td class="status-<?php echo $statusClass; ?>"><?php echo $statusClass; ?></td>
            <td>
                <?php if(strtolower($row['status'])=='pending'): ?>
                    <a href="admin_dashboard.php?action=approve&id=<?php echo $row['id'];?>">Approve</a>
                    <a href="admin_dashboard.php?action=reject&id=<?php echo $row['id'];?>">Reject</a>
                <?php elseif(strtolower($row['status'])=='approved'): ?>
                    <span style="color:#1a7a54;font-weight:600;font-size:13px;">✓ Approved</span>
                <?php elseif(strtolower($row['status'])=='rejected'): ?>
                    <span style="color:#c0392b;font-weight:600;font-size:13px;">✗ Rejected</span>
                <?php endif; ?>
                <a href="admin_dashboard.php?action=edit&id=<?php echo $row['id'];?>">Edit</a>
                <a href="admin_dashboard.php?action=delete&id=<?php echo $row['id'];?>" onclick="return confirm('Delete this record?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- ===== TAB: PAYMENT TRACKING ===== -->
<div id="tab-payments" class="tab-section">

    <div class="crud-section">
        <h3>+ Record a Payment Manually</h3>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Loan ID</label>
                    <select name="loan_id" required>
                        <option value="">-- Select Loan --</option>
                        <?php while($al = $approved_loans->fetch_assoc()): ?>
                        <option value="<?php echo $al['id']; ?>">
                            #<?php echo $al['id']; ?> — <?php echo htmlspecialchars($al['fullname']); ?> (₱<?php echo number_format($al['amount'],2); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Member Email</label>
                    <input type="email" name="pay_email" required>
                </div>
                <div>
                    <label>Amount Paid (PHP)</label>
                    <input type="number" name="amount_paid" min="1" step="0.01" required>
                </div>
                <div>
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" required>
                </div>
                <div>
                    <label>Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Weekly payment">
                </div>
            </div>
            <button type="submit" name="add_payment" class="btn-submit">Record Payment</button>
        </form>
    </div>

    <!-- PAYMENT PROOFS -->
    <div class="section-title">
        Payment Proofs — Member Uploads
        <?php if($pending_proofs_count > 0): ?>
        <span style="background:#c0392b;color:#fff;font-size:11px;padding:2px 8px;border-radius:20px;margin-left:8px;"><?php echo $pending_proofs_count; ?> Pending</span>
        <?php endif; ?>
    </div>
    <table>
        <tr>
            <th>Member</th><th>Loan ID</th><th>Week</th><th>Amount</th>
            <th>Receipt</th><th>Note</th><th>Status</th><th>Submitted</th><th>Action</th>
        </tr>
        <?php if($proofs_all && $proofs_all->num_rows > 0):
              while($pf = $proofs_all->fetch_assoc()):
              $ext = strtolower(pathinfo($pf['proof_file'], PATHINFO_EXTENSION));
        ?>
        <tr>
            <td><?php echo htmlspecialchars($pf['fullname'] ?? $pf['email']); ?><br>
                <small style="color:#7a9e8e;"><?php echo $pf['email']; ?></small></td>
            <td>#<?php echo $pf['loan_id']; ?></td>
            <td>Week <?php echo $pf['week_number']; ?></td>
            <td>₱<?php echo number_format($pf['amount'],2); ?></td>
            <td>
                <?php if($pf['proof_file']): ?>
                    <?php if(in_array($ext,['jpg','jpeg','png'])): ?>
                        <img src="<?php echo $pf['proof_file']; ?>" class="proof-thumb"
                             onclick="openModal('<?php echo $pf['proof_file']; ?>')" alt="Receipt">
                    <?php else: ?>
                        <a href="<?php echo $pf['proof_file']; ?>" target="_blank"
                           style="background:#1a5fa8;">View PDF</a>
                    <?php endif; ?>
                <?php else: echo '—'; endif; ?>
            </td>
            <td><?php echo htmlspecialchars($pf['note'] ?? '—'); ?></td>
            <td class="proof-<?php echo strtolower($pf['status']); ?>"><?php echo $pf['status']; ?></td>
            <td><?php echo date('M d, Y', strtotime($pf['submitted_at'])); ?></td>
            <td>
                <?php if($pf['status']=='Pending'): ?>
                <a href="admin_dashboard.php?action=verify_proof&id=<?php echo $pf['id']; ?>&loan_id=<?php echo $pf['loan_id']; ?>&email=<?php echo urlencode($pf['email']); ?>&amount=<?php echo $pf['amount']; ?>"
                   onclick="return confirm('Verify and record this payment?')">Verify</a>
                <a href="admin_dashboard.php?action=reject_proof&id=<?php echo $pf['id']; ?>"
                   onclick="return confirm('Reject this proof?')">Reject</a>
                <?php else: ?>
                <span style="color:#aab8b2;font-size:13px;">Done</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="9" style="color:#7a9e8e;text-align:center;padding:1.5rem;">No payment proofs submitted yet.</td></tr>
        <?php endif; ?>
    </table>

    <!-- VERIFIED PAYMENT HISTORY -->
    <div class="section-title">Verified Payment History</div>
    <table>
        <tr>
            <th>#</th><th>Loan ID</th><th>Member</th><th>Amount Paid</th><th>Date</th><th>Note</th><th>Action</th>
        </tr>
        <?php if($payments_all && $payments_all->num_rows > 0):
              $pi=1; while($pay=$payments_all->fetch_assoc()): ?>
        <tr>
            <td><?php echo $pi++; ?></td>
            <td>#<?php echo $pay['loan_id']; ?></td>
            <td><?php echo htmlspecialchars($pay['fullname'] ?? $pay['email']); ?></td>
            <td>₱<?php echo number_format($pay['amount_paid'],2); ?></td>
            <td><?php echo $pay['payment_date']; ?></td>
            <td><?php echo htmlspecialchars($pay['note'] ?? '—'); ?></td>
            <td><a href="admin_dashboard.php?action=delete_pay&id=<?php echo $pay['id']; ?>" onclick="return confirm('Delete this payment?')">Delete</a></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" style="color:#7a9e8e;text-align:center;padding:1.5rem;">No payments recorded yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- ===== TAB: COLLECTION REPORT ===== -->
<div id="tab-collection" class="tab-section">
    <div class="report-grid">
        <div class="report-card green">
            <div class="report-label">Total Amount Loaned</div>
            <div class="report-value">₱<?php echo number_format($total_loaned,2); ?></div>
        </div>
        <div class="report-card green">
            <div class="report-label">Total Collected</div>
            <div class="report-value">₱<?php echo number_format($total_collected,2); ?></div>
        </div>
        <div class="report-card red">
            <div class="report-label">Outstanding Balance</div>
            <div class="report-value">₱<?php echo number_format($total_balance,2); ?></div>
        </div>
        <div class="report-card">
            <div class="report-label">Collection Rate</div>
            <div class="report-value"><?php echo $total_loaned > 0 ? round(($total_collected/$total_loaned)*100,1).'%' : '0%'; ?></div>
        </div>
    </div>

    <div class="section-title">Per-Loan Collection Status</div>
    <?php
    $loan_report = $conn->query("
        SELECT l.id, l.fullname, l.email, l.amount,
               COALESCE(l.total_payable, l.amount) as total_payable,
               COALESCE(l.weeks, 23) as weeks,
               COALESCE(l.weekly_payment, 0) as weekly_payment,
               l.due_date, l.duration,
               COALESCE(SUM(p.amount_paid),0) as total_paid
        FROM loans l
        LEFT JOIN payments p ON p.loan_id = l.id
        WHERE l.status='Approved'
        GROUP BY l.id
        ORDER BY l.id DESC
    ");
    ?>
    <table>
        <tr>
            <th>Loan ID</th><th>Member</th><th>Loan Amount</th><th>Total Payable</th>
            <th>Weekly</th><th>Weeks</th><th>Total Paid</th><th>Balance</th><th>Due Date</th><th>Status</th>
        </tr>
        <?php if($loan_report && $loan_report->num_rows > 0):
              while($lr=$loan_report->fetch_assoc()):
              $bal = $lr['total_payable'] - $lr['total_paid'];
        ?>
        <tr>
            <td>#<?php echo $lr['id']; ?></td>
            <td><?php echo htmlspecialchars($lr['fullname']); ?><br>
                <small style="color:#7a9e8e;"><?php echo $lr['email']; ?></small></td>
            <td>₱<?php echo number_format($lr['amount'],2); ?></td>
            <td>₱<?php echo number_format($lr['total_payable'],2); ?></td>
            <td>₱<?php echo number_format($lr['weekly_payment'],2); ?></td>
            <td><?php echo $lr['weeks']; ?> wks</td>
            <td>₱<?php echo number_format($lr['total_paid'],2); ?></td>
            <td>₱<?php echo number_format($bal,2); ?></td>
            <td><?php echo $lr['due_date'] ? date('M d, Y', strtotime($lr['due_date'])) : '—'; ?></td>
            <td><?php if($bal <= 0): ?>
                <span style="color:#1a7a54;font-weight:600;">Fully Paid</span>
                <?php else: ?>
                <span style="color:#c0392b;font-weight:600;">Outstanding</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="10" style="color:#7a9e8e;text-align:center;padding:1.5rem;">No approved loans yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- ===== TAB: EMPLOYEE MANAGEMENT ===== -->
<div id="tab-employees" class="tab-section">
    <div class="crud-section">
        <h3>+ Add Employee</h3>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="emp_name" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="emp_email" required>
                </div>
                <div>
                    <label>Role / Position</label>
                    <select name="emp_role" required>
                        <option value="">-- Select Role --</option>
                        <option value="Loan Officer">Loan Officer</option>
                        <option value="Collector">Collector</option>
                        <option value="Branch Manager">Branch Manager</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Admin Staff">Admin Staff</option>
                    </select>
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="emp_phone" placeholder="09XXXXXXXXX">
                </div>
            </div>
            <button type="submit" name="add_employee" class="btn-submit">Add Employee</button>
        </form>
    </div>

    <div class="section-title">Employee List</div>
    <table>
        <tr>
            <th>#</th><th>Full Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Action</th>
        </tr>
        <?php if($employees && $employees->num_rows > 0):
              $ei=1; while($emp=$employees->fetch_assoc()): ?>
        <tr>
            <td><?php echo $ei++; ?></td>
            <td><?php echo htmlspecialchars($emp['fullname']); ?></td>
            <td><?php echo htmlspecialchars($emp['email']); ?></td>
            <td><?php echo htmlspecialchars($emp['role']); ?></td>
            <td><?php echo htmlspecialchars($emp['phone']); ?></td>
            <td><a href="admin_dashboard.php?action=delete_emp&id=<?php echo $emp['id']; ?>" onclick="return confirm('Remove this employee?')">Delete</a></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6" style="color:#7a9e8e;text-align:center;padding:1.5rem;">No employees added yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- ===== TAB: CLIENT FEEDBACK ===== -->
<div id="tab-feedback" class="tab-section">

    <div class="report-grid">
        <div class="report-card blue">
            <div class="report-label">Total Feedback</div>
            <div class="report-value"><?php echo $feedback_total; ?></div>
        </div>
        <div class="report-card green">
            <div class="report-label">Average Rating</div>
            <div class="report-value"><?php echo $feedback_avg; ?> ★</div>
        </div>
        <div class="report-card amber">
            <div class="report-label">5-Star Reviews</div>
            <div class="report-value"><?php echo $feedback_five; ?></div>
        </div>
        <div class="report-card green">
            <div class="report-label">Today</div>
            <div class="report-value"><?php echo $feedback_today; ?></div>
        </div>
    </div>

    <div class="section-title">All Client Feedback</div>
    <table>
        <tr>
            <th>#</th><th>Name</th><th>Service</th><th>Rating</th><th>Message</th><th>Date</th><th>Action</th>
        </tr>
        <?php if($feedbacks_all && $feedbacks_all->num_rows > 0):
              $fi=1; while($fb = $feedbacks_all->fetch_assoc()): ?>
        <tr>
            <td style="color:#7a9e8e"><?php echo $fi++; ?></td>
            <td>
                <?php echo htmlspecialchars($fb['name']); ?><br>
                <small style="color:#7a9e8e"><?php echo htmlspecialchars($fb['email']); ?></small>
            </td>
            <td>
                <?php if(!empty($fb['service'])): ?>
                <span style="background:#e6f5ee;border:1px solid #a8d5bc;border-radius:20px;padding:2px 10px;font-size:12px;color:#1a7a54;font-weight:600">
                    <?php echo htmlspecialchars($fb['service']); ?>
                </span>
                <?php else: echo '<span style="color:#aab8b2">—</span>'; endif; ?>
            </td>
            <td style="color:#b97a00;font-size:1rem;letter-spacing:1px">
                <?php for($i=1;$i<=5;$i++) echo $i<=$fb['rating'] ? '★' : '☆'; ?>
            </td>
            <td style="color:#3a5a4a;max-width:250px;font-size:13px;line-height:1.4">
                <?php echo htmlspecialchars($fb['message']); ?>
            </td>
            <td style="color:#7a9e8e;font-size:12px;white-space:nowrap">
                <?php echo date('M d, Y g:i A', strtotime($fb['submitted_at'])); ?>
            </td>
            <td>
                <a href="admin_dashboard.php?action=delete_feedback&id=<?php echo $fb['id']; ?>"
                   onclick="return confirm('Delete this feedback?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" style="color:#7a9e8e;text-align:center;padding:1.5rem;">No feedback submitted yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- IMAGE MODAL -->
<div class="modal-overlay" id="imgModal" onclick="closeModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <img id="modalImg" src="" alt="Payment Receipt">
    </div>
</div>

<script>
function showTab(name, btn){
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}
function openModal(src){
    document.getElementById('modalImg').src = src;
    document.getElementById('imgModal').classList.add('show');
}
function closeModal(){
    document.getElementById('imgModal').classList.remove('show');
}
window.addEventListener('DOMContentLoaded', function(){
    const hash = window.location.hash;
    if(hash === '#payments')  document.querySelectorAll('.tab-btn')[1].click();
    else if(hash === '#collection') document.querySelectorAll('.tab-btn')[2].click();
    else if(hash === '#employees')  document.querySelectorAll('.tab-btn')[3].click();
    else if(hash === '#feedback')   document.querySelectorAll('.tab-btn')[4].click();
});
</script>

</body>
</html>
<?php $conn->close(); ?>