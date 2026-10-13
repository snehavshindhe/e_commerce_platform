<?php
// ============================================
// admin/update_order.php — Update Order Status
//
// This file was mostly correct.
// Minor improvement: added check that order_id > 0
// and that the order actually exists before updating.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

$orderId = (int)($_POST['order_id'] ?? 0);
$status  = mysqli_real_escape_string($conn, trim($_POST['status'] ?? ''));

// Only allow these three statuses — nothing else
$allowed = ['Pending', 'Shipped', 'Delivered'];

if ($orderId > 0 && in_array($status, $allowed)) {
    // Verify order exists before updating
    $check = mysqli_query($conn, "SELECT id FROM orders WHERE id=$orderId");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$orderId");
    }
}

// Redirect back to orders panel on dashboard
header("Location: dashboard.php?panel=orders");
exit();
?>
