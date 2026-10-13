<?php
// ============================================
// includes/session.php — Session Helpers
// BUG FIXED: requireUser() redirect path was wrong
//            when called from root-level pages like
//            checkout.php (it used ../user/login.php
//            which would go UP from root — 404 error).
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if a normal user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect user to login if not logged in.
// BUG FIX: $loginPath lets each page pass the correct
//          relative path to user/login.php so this helper
//          works from BOTH root pages and sub-folders.
function requireUser($loginPath = "user/login.php") {
    if (!isUserLoggedIn()) {
        header("Location: " . $loginPath . "?msg=login_required");
        exit();
    }
}

// Redirect to admin login if not admin.
// BUG FIX: Same fix as above for admin pages.
function requireAdmin($loginPath = "login.php") {
    if (!isAdminLoggedIn()) {
        header("Location: " . $loginPath . "?msg=login_required");
        exit();
    }
}
?>
