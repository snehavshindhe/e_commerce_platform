<?php
// ============================================
// user/register.php — User Registration
//
// BUGS FIXED:
// 1. No "confirm password" check — user could
//    mistype password and never know. Added
//    confirm_password field validation.
// 2. After successful registration, user had to
//    manually go to login.php and login again.
//    Fixed: auto-login after register by setting
//    session variables immediately.
// 3. phone field had no maxlength validation —
//    could overflow the VARCHAR(20) column.
//    Added length check.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in — go to store
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../includes/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']             ?? '');
    $email   = trim($_POST['email']            ?? '');
    $pass    = trim($_POST['password']         ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $phone   = trim($_POST['phone']            ?? '');

    // Basic required field validation
    if (empty($name) || empty($email) || empty($pass)) {
        $error = "Name, email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    // BUG FIX #1: Check passwords match
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    // BUG FIX #3: Phone length check
    } elseif (strlen($phone) > 20) {
        $error = "Phone number is too long (max 20 characters).";
    } else {
        $emailEsc = mysqli_real_escape_string($conn, $email);

        // Check if email already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$emailEsc'");
        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hash     = md5($pass); // simple MD5 for learning
            $nameEsc  = mysqli_real_escape_string($conn, $name);
            $phoneEsc = mysqli_real_escape_string($conn, $phone);

            $sql = "INSERT INTO users (name, email, password, phone)
                    VALUES ('$nameEsc', '$emailEsc', '$hash', '$phoneEsc')";

            if (mysqli_query($conn, $sql)) {
                $newId = mysqli_insert_id($conn);

                // BUG FIX #2: Auto-login — set session right after registering
                $_SESSION['user_id']    = $newId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;

                // Go straight to the store
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>
    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<div class="page-center">
<div class="form-wrapper">
    <h2>Create Account</h2>
    <p class="form-sub">Join ShopEase and start shopping today.</p>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" placeholder="e.g. John Doe"
                   maxlength="100" required>
        </div>
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="you@example.com"
                   maxlength="150" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password"
                       placeholder="Min. 6 characters" maxlength="100" required>
            </div>
            <div class="form-group">
                <!-- BUG FIX #1: Confirm password field added -->
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password"
                       placeholder="Repeat password" maxlength="100" required>
            </div>
        </div>
        <div class="form-group">
            <label>Phone (optional)</label>
            <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX"
                   maxlength="20">
        </div>
        <button type="submit" class="btn-primary full-width">Register</button>
    </form>

    <p class="form-link">Already have an account? <a href="login.php">Login here</a></p>
</div>
</div>

<footer class="footer"><p>ShopEase &copy; <?= date('Y') ?></p></footer>
</body>
</html>
