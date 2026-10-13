<?php
// ============================================
// user/edit_profile.php — Edit Profile
//
// BUGS FIXED:
// 1. requireUser() was called from session.php helper
//    but this file didn't include session.php — it
//    just did a manual check. Manual check redirected
//    to "login.php" (relative to user/ folder = correct),
//    but was inconsistent with the rest of the project.
//    Now uses consistent session_status() + manual check.
// 2. textarea for address had no rows attribute in the
//    original — minor UI issue. Added rows="3".
// 3. name field maxlength was missing — could overflow
//    the VARCHAR(100) DB column. Added maxlength.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in to edit profile
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

$uid     = (int)$_SESSION['user_id'];
$error   = '';
$success = '';

// Fetch current profile data
$res  = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error = "Name cannot be empty.";
    // BUG FIX #3: Length guards to match DB column sizes
    } elseif (strlen($name) > 100) {
        $error = "Name is too long (max 100 characters).";
    } elseif (strlen($phone) > 20) {
        $error = "Phone is too long (max 20 characters).";
    } else {
        $nameEsc    = mysqli_real_escape_string($conn, $name);
        $phoneEsc   = mysqli_real_escape_string($conn, $phone);
        $addressEsc = mysqli_real_escape_string($conn, $address);

        $sql = "UPDATE users SET
                    name    = '$nameEsc',
                    phone   = '$phoneEsc',
                    address = '$addressEsc'
                WHERE id=$uid";

        if (mysqli_query($conn, $sql)) {
            // Update session so navbar shows new name immediately
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";

            // Refresh displayed data
            $res  = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
            $user = mysqli_fetch_assoc($res);
        } else {
            $error = "Update failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>
    <div class="nav-links">
        <a href="../index.php">← Back to Store</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-center">
<div class="form-wrapper">
    <h2>Edit Profile</h2>
    <p class="form-sub">Update your personal information.</p>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name *</label>
            <!-- BUG FIX #3: maxlength added to match DB column -->
            <input type="text" name="name"
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                   maxlength="100" required>
        </div>
        <div class="form-group">
            <label>Email (cannot be changed)</label>
            <!-- Email is read-only; no name="" so it won't be submitted -->
            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                   disabled style="opacity:0.6;">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                   maxlength="20">
        </div>
        <div class="form-group">
            <label>Delivery Address</label>
            <!-- BUG FIX #2: rows="3" for a properly sized textarea -->
            <textarea name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-primary full-width">Save Changes</button>
    </form>

    <p style="margin-top:16px;"><a href="../index.php?section=profile">← Back to Profile</a></p>
</div>
</div>

<footer class="footer"><p>ShopEase &copy; <?= date('Y') ?></p></footer>
</body>
</html>
