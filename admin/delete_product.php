<?php
// ============================================
// admin/delete_product.php — Delete a Product
//
// BUG FIXED:
// 1. Deletion was triggered by a simple GET request
//    (a link with ?id=X). This means anyone could
//    embed <img src="delete_product.php?id=5"> in a
//    page and trick an admin into deleting products.
//    Fixed: only allow DELETE via a POST request
//    (the dashboard confirm() JS still protects UX,
//    but now the server also requires POST).
//    The dashboard delete button now uses a mini-form.
//
// NOTE: dashboard.php delete links have been changed
//       to use <form method="POST"> with a hidden id
//       field. This file now only accepts POST.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

// BUG FIX: Only accept POST — reject GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php?panel=products");
    exit();
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Check product exists before trying to delete
    $check = mysqli_query($conn, "SELECT id FROM products WHERE id=$id");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    }
}

header("Location: dashboard.php?panel=products&del=1");
exit();
?>
