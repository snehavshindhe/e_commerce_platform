<?php
// ============================================
// admin/logout.php — Admin Logout
//
// BUG FIXED: Original used session_destroy() which
// would wipe out any active user session too.
// Now only removes admin-specific session keys.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only remove admin session keys
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// If nothing else in session, fully destroy
if (empty($_SESSION)) {
    session_destroy();
}

// Redirect to admin login
header("Location: login.php");
exit();
?>
