<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    header("Location: Login.html"); exit();
}

$servername = "sql209.infinityfree.com";
$username   = "if0_41361087";
$password   = "U0ty865HoKIgTg";
$dbname     = "if0_41361087_asa";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Handle delete
if(isset($_GET['action']) && $_GET['action']=='delete_feedback' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_feedback.php"); exit();
}

$result    = mysqli_query($conn, "SELECT * FROM feedback ORDER BY submitted_at DESC");
$feedbacks = mysqli_fetch_all($result, MYSQLI_ASSOC);
$total     = count($feedbacks);
$avgRating = $total > 0 ? round(array_sum(array_column($feedbacks, 'rating')) / $total, 1) : 0;
$fiveStar  = count(array_filter($feedbacks, fn($f) => $f['rating'] == 5));
$today     = count(array_filter($feedbacks, fn($f) => date('Y-m-d', strtotime($f['submitted_at'])) === date('Y-m-d')));

mysqli_close($conn);

function renderStars($rating) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? "★" : "☆";
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Client Feedback — ASA Philippines</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #0a3d2b;
        min-height: 100vh;
        padding: 2rem;
        color: #fff;
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

    /* SECTION TITLE */
    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.75rem;
        margin-top: 1.5rem;
    }
    .section-title:first-child { margin-top: 0; }

    /* SEARCH BAR */
    .table-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .search-box {
        padding: 8px 14px;
        border-radius: 7px;
        border: 1.5px solid #c8ddd5;
        background: #f7fcf9;
        font-size: 13px;
        color: #1a3d2b;
        font-family: inherit;
        outline: none;
        width: 260px;
    }
    .search-box:focus { border-color: #0a3d2b; }

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

    td { color: #1a3d2b; }

    .td-email { color: #7a9e8e; font-size: 12px; }
    .td-stars { color: #b97a00; font-size: 1rem; letter-spacing: 1px; }
    .td-message { color: #3a5a4a; max-width: 280px; font-size: 0.83rem; line-height: 1.5; }

    .service-pill {
        display: inline-block;
        padding: 3px 10px;
        background: #e6f5ee;
        border: 1px solid #a8d5bc;
        border-radius: 20px;
        font-size: 12px;
        color: #1a7a54;
        font-weight: 600;
        white-space: nowrap;
    }

    /* DELETE LINK */
    a.btn-delete {
        padding: 5px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: white;
        font-size: 13px;
        font-weight: 500;
        display: inline-block;
        background-color: #7b2d2d;
    }
    a.btn-delete:hover { opacity: 0.85; }

    /* BACK BUTTON */
    .btn-back {
        display: inline-block;
        padding: 8px 18px;
        border-radius: 7px;
        background: rgba(255,255,255,0.15);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s;
        margin-bottom: 1.5rem;
    }
    .btn-back:hover { background: #ffffff; color: #0a3d2b; }

    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #7a9e8e;
    }
    .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

    @media (max-width: 768px) {
        .td-message { display: none; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .search-box { width: 100%; }
    }
  </style>
</head>
<body>

<h1>Client Feedback</h1>
<div class="top-bar">
  Welcome, <?php echo $_SESSION['fullname']; ?> |
  <a href="admin_dashboard.php">← Back to Dashboard</a> |
  <a href="index.html">Logout</a>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Feedback</div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Average Rating</div>
        <div class="stat-value"><?= $avgRating ?> ★</div>
        <div class="stat-sub">Out of 5.0</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">5-Star Reviews</div>
        <div class="stat-value"><?= $fiveStar ?></div>
        <div class="stat-sub">Perfect ratings</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Today</div>
        <div class="stat-value"><?= $today ?></div>
        <div class="stat-sub">New responses</div>
    </div>
</div>

<!-- TABLE -->
<div class="table-topbar">
    <div class="section-title" style="margin:0">All Responses</div>
    <input class="search-box" type="text" id="searchInput"
           placeholder="🔍 Search by name, service..."
           onkeyup="filterTable()"/>
</div>

<table id="feedbackTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Client Name</th>
            <th>Service</th>
            <th>Rating</th>
            <th>Message</th>
            <th>Date Submitted</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($total > 0): ?>
            <?php foreach ($feedbacks as $i => $row): ?>
            <tr>
                <td style="color:#7a9e8e"><?= $i + 1 ?></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="td-email"><?= htmlspecialchars($row['email']) ?></div>
                </td>
                <td>
                    <?php if (!empty($row['service'])): ?>
                        <span class="service-pill"><?= htmlspecialchars($row['service']) ?></span>
                    <?php else: ?>
                        <span style="color:#aab8b2">—</span>
                    <?php endif; ?>
                </td>
                <td class="td-stars"><?= renderStars($row['rating']) ?></td>
                <td class="td-message"><?= htmlspecialchars($row['message']) ?></td>
                <td style="color:#5a8a72;font-size:13px;white-space:nowrap">
                    <?= date('M d, Y g:i A', strtotime($row['submitted_at'])) ?>
                </td>
                <td>
                    <a class="btn-delete"
                       href="view_feedback.php?action=delete_feedback&id=<?= $row['id'] ?>"
                       onclick="return confirm('Delete this feedback?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <div class="icon">📭</div>
                        <p>No feedback submitted yet.</p>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
  function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#feedbackTable tbody tr').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
    });
  }
</script>

</body>
</html>