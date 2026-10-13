<?php
// ============================================
// cart/update.php — Update Cart Quantity
//
// BUGS FIXED:
// 1. $action was used directly from $_POST without
//    sanitizing — an attacker could POST any string.
//    Fixed by only allowing 'increase' or 'decrease'.
// 2. Added check that mysqli_query result is valid
//    before calling mysqli_fetch_assoc on it.
// ============================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

include '../includes/db.php';

$cartId = (int)($_POST['cart_id'] ?? 0);
$uid    = (int)$_SESSION['user_id'];

// BUG FIX #1: Whitelist allowed actions — reject anything else
$action = $_POST['action'] ?? '';
if (!in_array($action, ['increase', 'decrease'])) {
    header("Location: ../index.php?section=cart");
    exit();
}

if ($cartId <= 0) {
    header("Location: ../index.php?section=cart");
    exit();
}

// Fetch current quantity — also confirms this cart item belongs to this user
$res = mysqli_query($conn,
    "SELECT quantity FROM cart WHERE id=$cartId AND user_id=$uid");

// BUG FIX #2: Check query succeeded AND row exists before using it
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $qty = (int)$row['quantity'];

    if ($action === 'increase') {
        mysqli_query($conn,
            "UPDATE cart SET quantity=" . ($qty + 1) . " WHERE id=$cartId");
    } elseif ($action === 'decrease') {
        if ($qty <= 1) {
            // Remove the item entirely if quantity would reach 0
            mysqli_query($conn, "DELETE FROM cart WHERE id=$cartId");
        } else {
            mysqli_query($conn,
                "UPDATE cart SET quantity=" . ($qty - 1) . " WHERE id=$cartId");
        }
    }
}

// Redirect back to cart section
header("Location: ../index.php?section=cart");
exit();
?>
