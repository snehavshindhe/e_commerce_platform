<?php
// ============================================
// checkout.php — Checkout Page
//
// BUGS FIXED:
// 1. session_start() was called raw without checking
//    if a session already existed — causes PHP warnings.
//    Fixed with session_status() check.
// 2. No user-facing error message when redirected from
//    place_order.php due to empty fields. Added ?err=1
//    message display.
// 3. user['phone'] and user['address'] could be NULL
//    from DB — htmlspecialchars(null) throws a notice
//    in PHP 8.1+. Fixed with null-coalescing ?? ''.
// ============================================

// BUG FIX #1: Avoid double session_start() warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php?msg=login_required");
    exit();
}

include 'includes/db.php';

$uid = (int)$_SESSION['user_id'];

// Fetch user info to pre-fill the delivery form
$uRes = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($uRes);

// Fetch all cart items for this user
$cartItems = mysqli_query($conn,
    "SELECT c.quantity, p.product_name, p.product_price, p.id as pid
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = $uid");

// If cart is empty, redirect back to cart section
if (!$cartItems || mysqli_num_rows($cartItems) === 0) {
    header("Location: index.php?section=cart");
    exit();
}

// Calculate total and store items in array
$total = 0;
$items = [];
while ($item = mysqli_fetch_assoc($cartItems)) {
    $item['subtotal'] = $item['product_price'] * $item['quantity'];
    $total += $item['subtotal'];
    $items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>
    <div class="nav-links">
        <a href="index.php">← Back to Store</a>
        <a href="user/logout.php">Logout</a>
    </div>
</nav>

<div style="padding:60px 5% 80px;">
    <h2 style="font-family:var(--font-display);font-size:2rem;color:var(--charcoal);margin-bottom:32px;text-align:center;">🧾 Checkout</h2>

    <!-- BUG FIX #2: Show validation error if place_order.php sent back err=1 -->
    <?php if (isset($_GET['err']) && $_GET['err'] === '1'): ?>
        <div class="alert-error" style="max-width:700px;margin:0 auto 24px;">
            ❌ Please fill in all required fields (Name, Phone, Address).
        </div>
    <?php endif; ?>

    <div class="checkout-grid">
        <!-- LEFT: Delivery Form -->
        <div class="form-wrapper" style="margin:0;">
            <h2 style="font-size:1.4rem;margin-bottom:6px;">Delivery Details</h2>
            <p class="form-sub">Where should we deliver your order?</p>

            <form action="place_order.php" method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <!-- BUG FIX #3: ?? '' prevents htmlspecialchars(null) notice -->
                    <input type="text" name="name"
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="+91 XXXXX XXXXX" required>
                </div>
                <div class="form-group">
                    <label>Delivery Address *</label>
                    <textarea name="address" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment">
                        <option value="Cash on Delivery">💵 Cash on Delivery</option>
                        <option value="UPI (Demo)">📱 UPI (Demo)</option>
                        <option value="Card (Demo)">💳 Card (Demo)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary full-width">Place Order →</button>
            </form>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="checkout-summary">
            <h3>Order Summary</h3>
            <?php foreach ($items as $item): ?>
                <div class="checkout-item">
                    <span><?= htmlspecialchars($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span>
                    <span>$<?= number_format($item['subtotal'], 2) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="checkout-item" style="margin-top:12px;font-weight:600;">
                <span>Delivery</span><span>FREE</span>
            </div>
            <div class="checkout-item" style="font-size:1.1rem;font-weight:700;color:var(--accent);border-top:2px solid var(--border);padding-top:12px;">
                <span>Total</span><span>$<?= number_format($total, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<footer class="footer"><p>ShopEase &copy; <?= date('Y') ?></p></footer>
</body>
</html>
