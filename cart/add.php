<?php
// ============================================
// cart/add.php — Add Product to Cart
//
// BUGS FIXED:
// 1. No validation that the product_id actually exists
//    in the products table — could insert invalid IDs.
//    Fixed by checking the product exists first.
// 2. Redirect URL used ?section=products but JS reads
//    that param only after DOMContentLoaded. The param
//    name is correct; added cart_msg for the alert too.
// ============================================

session_start();

// If not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

$uid = (int)$_SESSION['user_id'];
// BUG FIX: Cast to int to prevent SQL injection (extra safety)
$pid = (int)($_POST['product_id'] ?? 0);

// BUG FIX #1: Make sure product_id is valid before touching cart
if ($pid <= 0) {
    header("Location: ../index.php?section=products");
    exit();
}

$productCheck = mysqli_query($conn, "SELECT id FROM products WHERE id=$pid");
if (!$productCheck || mysqli_num_rows($productCheck) === 0) {
    // Product does not exist — go back safely
    header("Location: ../index.php?section=products");
    exit();
}

// Check if this product is already in the user's cart
$check = mysqli_query($conn,
    "SELECT id, quantity FROM cart WHERE user_id=$uid AND product_id=$pid");

if ($check && mysqli_num_rows($check) > 0) {
    // Product already in cart — increase quantity by 1
    $row    = mysqli_fetch_assoc($check);
    $newQty = (int)$row['quantity'] + 1;
    mysqli_query($conn, "UPDATE cart SET quantity=$newQty WHERE id={$row['id']}");
} else {
    // New product — add to cart with quantity 1
    mysqli_query($conn,
        "INSERT INTO cart (user_id, product_id, quantity) VALUES ($uid, $pid, 1)");
}

// Redirect back to products section with success message
// BUG FIX #2: Both ?section and ?cart_msg are sent so JS shows the right section
//             AND the PHP alert message is visible
header("Location: ../index.php?section=products&cart_msg=1");
exit();
?>
