<?php
// ============================================
// user/logout.php — Destroys user session
//
// BUG FIXED: The original used session_destroy()
// which would also destroy an admin session if both
// were open in the same browser (edge case).
// Better practice: unset only user-specific keys,
// then destroy if no other session data remains.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove only user-related session keys
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['last_order_payment']);

// If nothing else is left in session, destroy it fully
if (empty($_SESSION)) {
    session_destroy();
}

// Redirect to the store home page
header("Location: ../index.php");
exit();
?>
