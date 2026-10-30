<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='user'){
    header("Location: Login.html"); exit();
}
$servername="sql209.infinityfree.com";
$username="if0_41361087";
$password="U0ty865HoKIgTg";
$dbname="if0_41361087_asa";
$conn = new mysqli($servername,$username,$password,$dbname);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);
if($_SERVER['REQUEST_METHOD']=='POST'){
    $fullname=$_SESSION['fullname'];
    $email=$_SESSION['email'];
    $amount=floatval($_POST['amount']);
    $purpose=trim($_POST['purpose']);
    $weeks=intval($_POST['weeks']); // 11 or 23

    // Interest calculation
    $interest = $amount * 0.15;
    $total_payable = $amount + $interest;

    // Weekly payment
    if($weeks == 11){
        // Double weekly payment for 11 weeks
        $weekly_payment = ($total_payable / 23) * 2;
    } else {
        $weekly_payment = $total_payable / 23;
    }

    $due_date = date('Y-m-d', strtotime('+' . $weeks . ' weeks'));

    $stmt=$conn->prepare("INSERT INTO loans (fullname,email,amount,purpose,duration,status,weeks,weekly_payment,total_payable,due_date) VALUES (?,?,?,?,?,'Pending',?,?,?,?)");
    $duration = $weeks . ' weeks';
    $stmt->bind_param("ssdssidds",$fullname,$email,$amount,$purpose,$duration,$weeks,$weekly_payment,$total_payable,$due_date);
    if($stmt->execute()){
        header("Location: dashboard.php"); exit();
    } else {
        echo "Error: ".$stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Apply Loan</title>
    <style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #0a3d2b;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 2rem 1rem;
}
form {
    background: #ffffff;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    border-top: 5px solid #0a3d2b;
    width: 100%;
    max-width: 480px;
    display: flex;
    flex-direction: column;
}
h2 {
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    color: #0a3d2b;
    margin-bottom: 1.5rem;
}
h2::before {
    content: 'ASA Philippines Foundation';
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #5a8a72;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
label {
    font-size: 13px;
    font-weight: 500;
    color: #2d6b4f;
    margin-bottom: 4px;
    margin-top: 10px;
    display: block;
}
input[type="number"],
textarea,
select {
    padding: 9px 12px;
    border-radius: 7px;
    border: 1.5px solid #c8ddd5;
    font-size: 14px;
    color: #1a3d2b;
    background: #f7fcf9;
    width: 100%;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
    margin-bottom: 2px;
}
input[type="number"]:focus,
textarea:focus,
select:focus {
    border-color: #0a3d2b;
    background: #fff;
}
textarea { resize: vertical; min-height: 80px; }

/* Loan summary box */
.loan-summary {
    background: #e8f5ef;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    display: none;
}
.loan-summary.show { display: block; }
.loan-summary .sum-title {
    font-size: 12px;
    font-weight: 600;
    color: #2d6b4f;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.sum-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #1a3d2b;
    margin-bottom: 4px;
}
.sum-row.total {
    font-weight: 600;
    font-size: 15px;
    border-top: 1px solid #c8ddd5;
    padding-top: 6px;
    margin-top: 4px;
    color: #0a3d2b;
}
.week-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 2px;
}
.week-card {
    border: 2px solid #c8ddd5;
    border-radius: 9px;
    padding: 12px;
    cursor: pointer;
    background: #f7fcf9;
    transition: border-color 0.2s, background 0.2s;
}
.week-card:hover { border-color: #0a3d2b; }
.week-card.selected { border-color: #0a3d2b; background: #e8f5ef; }
.week-card input[type="radio"] { display: none; }
.week-card .wk-title { font-size: 15px; font-weight: 600; color: #0a3d2b; }
.week-card .wk-sub { font-size: 11px; color: #5a8a72; margin-top: 2px; }

input[type="submit"] {
    background: #0a3d2b;
    color: #ffffff;
    border: none;
    padding: 11px;
    border-radius: 7px;
    cursor: pointer;
    margin-top: 1.25rem;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    letter-spacing: 0.3px;
    transition: background 0.2s;
}
input[type="submit"]:hover { background: #0f5c3f; }
.back-link {
    text-align: center;
    margin-top: 1rem;
    font-size: 13px;
    color: #7a9e8e;
}
.back-link a { color: #0a3d2b; font-weight: 600; text-decoration: none; }
</style>
</head>
<body>
<form method="POST" id="loanForm">
    <h2>Apply for a Loan</h2>

    <label>Amount (PHP)</label>
    <input type="number" name="amount" id="amount" min="1000" required oninput="calcLoan()">

    <label>Purpose</label>
    <textarea name="purpose" required></textarea>

    <label>Payment Term</label>
    <div class="week-options">
        <label class="week-card" id="card23" onclick="selectWeek(23)">
            <input type="radio" name="weeks" value="23" checked>
            <div class="wk-title">23 Weeks</div>
            <div class="wk-sub">Standard — lower weekly payment</div>
        </label>
        <label class="week-card" id="card11" onclick="selectWeek(11)">
            <input type="radio" name="weeks" value="11">
            <div class="wk-title">11 Weeks</div>
            <div class="wk-sub">Fast track — double weekly payment</div>
        </label>
    </div>

    <!-- Live Loan Summary -->
    <div class="loan-summary" id="loanSummary">
        <div class="sum-title">Loan Breakdown</div>
        <div class="sum-row"><span>Principal</span><span id="s-principal">₱0.00</span></div>
        <div class="sum-row"><span>Interest (15%)</span><span id="s-interest">₱0.00</span></div>
        <div class="sum-row total"><span>Total Payable</span><span id="s-total">₱0.00</span></div>
        <div class="sum-row"><span>Weekly Payment</span><span id="s-weekly">₱0.00</span></div>
        <div class="sum-row"><span>Due Date</span><span id="s-due">—</span></div>
    </div>

    <input type="submit" value="Submit Loan Application">
    <div class="back-link"><a href="dashboard.php">← Back to Dashboard</a></div>
</form>

<script>
let selectedWeeks = 23;

function selectWeek(w){
    selectedWeeks = w;
    document.querySelectorAll('.week-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card'+w).classList.add('selected');
    document.querySelector('input[value="'+w+'"]').checked = true;
    calcLoan();
}

function calcLoan(){
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    if(amount <= 0){ document.getElementById('loanSummary').classList.remove('show'); return; }

    const interest = amount * 0.15;
    const total = amount + interest;
    let weekly = selectedWeeks == 11 ? (total/23)*2 : total/23;

    // Due date
    const now = new Date();
    now.setDate(now.getDate() + selectedWeeks*7);
    const due = now.toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'});

    document.getElementById('s-principal').textContent = '₱'+amount.toFixed(2);
    document.getElementById('s-interest').textContent  = '₱'+interest.toFixed(2);
    document.getElementById('s-total').textContent     = '₱'+total.toFixed(2);
    document.getElementById('s-weekly').textContent    = '₱'+weekly.toFixed(2)+' / week';
    document.getElementById('s-due').textContent       = due;
    document.getElementById('loanSummary').classList.add('show');
}

// Default select 23 weeks card
document.getElementById('card23').classList.add('selected');
</script>
</form>
</body>
</html>