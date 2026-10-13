<?php
// ============================================
// cart/remove.php — Remove Item from Cart
//
// BUGS FIXED:
// 1. No check that cart_id > 0 before running DELETE.
//    If cart_id was 0 or missing, the WHERE clause
//    "id=0 AND user_id=X" would simply match nothing —
//    harmless but sloppy. Now we exit early if invalid.
// ============================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

include '../includes/db.php';

$cartId = (int)($_POST['cart_id'] ?? 0);
$uid    = (int)$_SESSION['user_id'];

// BUG FIX #1: Only run DELETE if we have a real cart ID
if ($cartId > 0) {
    // The AND user_id=$uid ensures a user can only remove THEIR OWN items
    mysqli_query($conn, "DELETE FROM cart WHERE id=$cartId AND user_id=$uid");
}

// Redirect back to cart section
header("Location: ../index.php?section=cart");
exit();
?>
