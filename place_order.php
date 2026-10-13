<?php
// ============================================
// place_order.php — Save Order to Database
//
// BUGS FIXED:
// 1. On validation failure the redirect went to
//    checkout.php with NO error message — user had
//    no idea what went wrong. Fixed: redirect with
//    ?err=1 so checkout.php can show a message.
// 2. Order could be half-saved if a DB error happened
//    mid-way (order header inserted but items missing).
//    Fixed: wrapped in a mysqli transaction so either
//    EVERYTHING saves or NOTHING does.
// 3. order_success.php only showed "Cash on Delivery"
//    no matter what payment was chosen. Fixed by storing
//    payment method in session and using it on the
//    success page.
// ============================================

// BUG FIX: Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php?msg=login_required");
    exit();
}

include 'includes/db.php';

$uid = (int)$_SESSION['user_id'];

// Read and sanitize POST data
$name    = mysqli_real_escape_string($conn, trim($_POST['name']    ?? ''));
$phone   = mysqli_real_escape_string($conn, trim($_POST['phone']   ?? ''));
$address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
$payment = mysqli_real_escape_string($conn, trim($_POST['payment'] ?? 'Cash on Delivery'));

// BUG FIX #1: If fields are empty, go back to checkout WITH an error message
if (empty($name) || empty($phone) || empty($address)) {
    header("Location: checkout.php?err=1");
    exit();
}

// Validate payment method to only allow known options
$allowedPayments = ['Cash on Delivery', 'UPI (Demo)', 'Card (Demo)'];
if (!in_array($payment, $allowedPayments)) {
    $payment = 'Cash on Delivery';
}

// Fetch the user's cart items
$cartItems = mysqli_query($conn,
    "SELECT c.quantity, p.product_price, p.id as pid
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = $uid");

// If cart is empty, go back
if (!$cartItems || mysqli_num_rows($cartItems) === 0) {
    header("Location: index.php?section=cart");
    exit();
}

// Calculate total and save all rows
$total = 0;
$rows  = [];
while ($r = mysqli_fetch_assoc($cartItems)) {
    $total += $r['product_price'] * $r['quantity'];
    $rows[] = $r;
}

// BUG FIX #2: Use a transaction so order header + items are saved together.
// If any query fails, we roll back so the DB stays consistent.
mysqli_begin_transaction($conn);

try {
    // Insert the order header
    $orderSql = "INSERT INTO orders (user_id, total_price, name, address, phone, payment)
                 VALUES ($uid, $total, '$name', '$address', '$phone', '$payment')";

    if (!mysqli_query($conn, $orderSql)) {
        throw new Exception("Failed to insert order: " . mysqli_error($conn));
    }

    $orderId = mysqli_insert_id($conn); // Get the new order's auto-increment ID

    // Insert each product line into order_items
    foreach ($rows as $r) {
        $price = (float)$r['product_price'];
        $qty   = (int)$r['quantity'];
        $pid   = (int)$r['pid'];

        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES ($orderId, $pid, $qty, $price)";

        if (!mysqli_query($conn, $itemSql)) {
            throw new Exception("Failed to insert order item: " . mysqli_error($conn));
        }
    }

    // Clear the cart after successful order
    mysqli_query($conn, "DELETE FROM cart WHERE user_id=$uid");

    // Commit everything to the database
    mysqli_commit($conn);

    // BUG FIX #3: Store payment method in session so success page can show it
    $_SESSION['last_order_payment'] = $payment;

    // Redirect to success page
    header("Location: order_success.php?order_id=$orderId");
    exit();

} catch (Exception $e) {
    // Something went wrong — undo everything
    mysqli_rollback($conn);
    // Redirect to checkout with a generic error
    header("Location: checkout.php?err=1");
    exit();
}
?>
