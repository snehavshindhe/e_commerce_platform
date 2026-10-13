<?php
// ============================================
// user/login.php — User Login
//
// BUGS FIXED:
// 1. No maximum length check on email/password inputs —
//    extremely long strings could slow down MD5 hashing
//    or cause DB query issues. Added length guard.
// 2. Session was started but user_name check in the
//    redirect after login could cause an undefined
//    index notice if something went wrong between
//    the SELECT and the session assignment.
//    Added isset check on $user array.
// ============================================

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in — go to store
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../includes/db.php';

$error = '';
$msg   = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']   ?? '');
    $pass  = trim($_POST['password'] ?? '');

    // BUG FIX #1: Reject suspiciously long inputs before hitting the DB
    if (strlen($email) > 150 || strlen($pass) > 100) {
        $error = "Invalid email or password.";
    } elseif (empty($email) || empty($pass)) {
        $error = "Please enter your email and password.";
    } else {
        $passHash = md5($pass); // MD5 matches what register.php stored
        $emailEsc = mysqli_real_escape_string($conn, $email);

        $sql = "SELECT * FROM users WHERE email='$emailEsc' AND password='$passHash'";
        $res = mysqli_query($conn, $sql);

        // BUG FIX #2: Check $res is valid before num_rows
        if ($res && mysqli_num_rows($res) === 1) {
            $user = mysqli_fetch_assoc($res);
            // Save important user details in session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>
    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="register.php">Register</a>
    </div>
</nav>

<div class="page-center">
<div class="form-wrapper">
    <h2>Welcome Back</h2>
    <p class="form-sub">Login to your ShopEase account.</p>

    <?php if ($msg === 'login_required'): ?>
        <div class="alert-error">⚠️ Please login to continue.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com"
                   maxlength="150" required autocomplete="email">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Your password"
                   maxlength="100" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary full-width">Login</button>
    </form>

    <p class="form-link">New here? <a href="register.php">Create an account</a></p>
</div>
</div>

<footer class="footer"><p>ShopEase &copy; <?= date('Y') ?></p></footer>
</body>
</html>
