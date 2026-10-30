<?php
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_reset'])){
    $servername="sql209.infinityfree.com";
    $username="if0_41361087";
    $password="U0ty865HoKIgTg";
    $dbname="if0_41361087_asa";
    $conn = new mysqli($servername,$username,$password,$dbname);

    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to DB
        $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
        $stmt2->bind_param("sss", $token, $expires, $email);
        $stmt2->execute();
        $stmt2->close();

        // Send email
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
        $subject = "ASA Philippines - Password Reset";
        $body = "Hello,\n\nClick the link below to reset your password. This link expires in 1 hour.\n\n" . $reset_link . "\n\nIf you did not request this, ignore this email.";
        $headers = "From: no-reply@asaphilippines.com";
        mail($email, $subject, $body, $headers);

        $message = "A password reset link has been sent to your email.";
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
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
  input[type="email"] {
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
  input[type="email"]:focus {
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
    <h1>Forgot Password</h1>
    <p class="subtitle">Enter your email and we'll send you a reset link.</p>

    <?php if($message): ?>
        <div class="msg-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="msg-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" required>
        <input type="submit" name="send_reset" value="Send Reset Link">
    </form>
    <p><a href="Login.html">Back to Login</a></p>
</div>
</body>
</html>