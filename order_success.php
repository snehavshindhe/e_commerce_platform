<?php
// ============================================
// order_success.php — Order Placed Successfully
//
// BUGS FIXED:
// 1. Payment method was HARDCODED as "Cash on Delivery"
//    even if user chose UPI or Card. Fixed by reading
//    the payment from the session (set in place_order.php).
// 2. No check that the order_id in the URL actually
//    belongs to the logged-in user. Anyone could type
//    any order_id and see this page. Fixed with a DB check.
// 3. order_id of 0 was allowed — now we redirect home
//    if order_id is invalid.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php");
    exit();
}

$uid     = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

// BUG FIX #3: Redirect if order_id is missing
if ($orderId <= 0) {
    header("Location: index.php");
    exit();
}

include 'includes/db.php';

// BUG FIX #2: Verify this order belongs to the logged-in user
$verify = mysqli_query($conn,
    "SELECT id, payment FROM orders WHERE id=$orderId AND user_id=$uid");

if (!$verify || mysqli_num_rows($verify) === 0) {
    // Order not found for this user — go home
    header("Location: index.php");
    exit();
}

$orderRow = mysqli_fetch_assoc($verify);

// BUG FIX #1: Use the payment stored in the DB (set during place_order.php)
$paymentMethod = htmlspecialchars($orderRow['payment']);

// Clean up the session variable we set in place_order.php
unset($_SESSION['last_order_payment']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Placed — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="user/logout.php">Logout</a>
    </div>
</nav>

<div style="padding:60px 5%;">
    <div class="success-box">
        <div class="icon">🎉</div>
        <h2>Order Placed!</h2>
        <p>
            Thank you for your order. Your Order ID is <strong>#<?= $orderId ?></strong>.<br>
            <!-- BUG FIX #1: Now shows the ACTUAL payment method chosen -->
            Payment method: <strong><?= $paymentMethod ?></strong>.
        </p>
        <a href="index.php?section=orders" class="btn-primary" style="margin-right:10px;">View My Orders</a>
        <a href="index.php?section=products" class="btn-outline">Continue Shopping</a>
    </div>
</div>

<footer class="footer"><p>ShopEase &copy; <?= date('Y') ?></p></footer>
</body>
</html>
