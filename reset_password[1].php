<?php
$message = '';
$error = '';
$valid_token = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$servername="sql209.infinityfree.com";
$username="if0_41361087";
$password="U0ty865HoKIgTg";
$dbname="if0_41361087_asa";
$conn = new mysqli($servername,$username,$password,$dbname);

// Validate token
if($token){
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > ?");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0) $valid_token = true;
    $stmt->close();
}

// Handle new password submission
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['reset_pass']) && $valid_token){
    $new_pass = trim($_POST['new_password']);
    $confirm  = trim($_POST['confirm_password']);

    if(strlen($new_pass) < 6){
        $error = "Password must be at least 6 characters.";
    } elseif($new_pass !== $confirm){
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE reset_token=?");
        $stmt2->bind_param("ss", $hashed, $token);
        $stmt2->execute();
        $stmt2->close();
        $message = "Password updated successfully! You can now login.";
        $valid_token = false;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #0a3d2b;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card {
    background: #ffffff;
    border-radius: 12px;
    padding: 2.5rem 2rem;
    width: 100%;
    max-width: 380px;
    border-top: 5px solid #0a3d2b;
  }
  h1 {
    font-size: 20px;
    font-weight: 600;
    color: #0a3d2b;
    margin-bottom: 0.5rem;
    text-align: center;
  }
  h1::before {
    content: 'ASA Philippines Foundation';
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #5a8a72;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .subtitle {
    font-size: 13px;
    color: #7a9e8e;
    text-align: center;
    margin-bottom: 1.5rem;
  }
  label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #2d6b4f;
    margin-bottom: 4px;
  }
  input[type="password"] {
    display: block;
    width: 100%;
    padding: 9px 12px;
    margin-bottom: 14px;
    border: 1.5px solid #c8ddd5;
    border-radius: 7px;
    font-size: 14px;
    color: #1a3d2b;
    background: #f7fcf9;
    outline: none;
    font-family: inherit;
    transition: border-color 0.2s;
  }
  input[type="password"]:focus {
    border-color: #0a3d2b;
    background: #fff;
  }
  input[type="submit"] {
    width: 100%;
    padding: 10px;
    background: #0a3d2b;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.2s;
  }
  input[type="submit"]:hover { background: #0f5c3f; }
  .msg-success {
    background: #e8f5ef;
    color: #1a7a54;
    border-radius: 7px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 1rem;
    text-align: center;
  }
  .msg-error {
    background: #fdecea;
    color: #c0392b;
    border-radius: 7px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 1rem;
    text-align: center;
  }
  p {
    text-align: center;
    margin-top: 1.25rem;
    font-size: 13px;
    color: #7a9e8e;
  }
  p a {
    color: #0a3d2b;
    font-weight: 600;
    text-decoration: none;
  }
  p a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
    <h1>Reset Password</h1>
    <p class="subtitle">Enter your new password below.</p>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
        <p><a href="Login.html">Back to Login</a></p>
    <?php elseif($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($valid_token): ?>
    <form method="POST">
        <label>New Password</label>
        <input type="password" name="new_password" minlength="6" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="6" required>
        <input type="submit" name="reset_pass" value="Update Password">
    </form>
    <?php elseif(!$message): ?>
        <div class="msg-error">This reset link is invalid or has expired.</div>
        <p><a href="forgot_password.php">Request a new link</a></p>
    <?php endif; ?>
</div>
</body>
</html>