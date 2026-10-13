<?php
// ============================================
// admin/login.php — Admin Login
// Default credentials: username=admin, password=admin123
//
// BUGS FIXED:
// 1. No input length check — very long strings could
//    slow down MD5 or cause issues. Added maxlength.
// 2. $res was used without checking if the query
//    succeeded. Added $res check before num_rows.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in as admin — go to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

include '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // BUG FIX #1: Reject suspiciously long inputs
    if (strlen($username) > 100 || strlen($password) > 100) {
        $error = "Invalid credentials.";
    } elseif (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } else {
        $passHash = md5($password);
        $userEsc  = mysqli_real_escape_string($conn, $username);

        $res = mysqli_query($conn,
            "SELECT * FROM admin WHERE username='$userEsc' AND password='$passHash'");

        // BUG FIX #2: Check $res before calling num_rows
        if ($res && mysqli_num_rows($res) === 1) {
            $admin = mysqli_fetch_assoc($res);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase Admin</div>
    <div class="nav-links">
        <a href="../index.php">← Store</a>
    </div>
</nav>

<div class="page-center">
<div class="form-wrapper">
    <h2>Admin Login</h2>
    <p class="form-sub">Default: admin / admin123</p>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
        <div class="alert-error">⚠️ Admin login required.</div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="admin"
                   maxlength="100" required autocomplete="username">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••"
                   maxlength="100" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary full-width">Login as Admin</button>
    </form>
</div>
</div>

<footer class="footer"><p>ShopEase Admin &copy; <?= date('Y') ?></p></footer>
</body>
</html>
