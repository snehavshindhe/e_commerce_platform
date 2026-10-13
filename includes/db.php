<?php
// ============================================
// includes/db.php — Database Connection
// BUG FIXED: Added mysqli_set_charset() to prevent
//            encoding issues / broken characters.
// Include this file in every PHP page.
// ============================================

$host     = "localhost";
$user     = "root";
$password = "";          // default XAMPP password is empty
$database = "ecommerce_db";

$conn = mysqli_connect($host, $user, $password, $database);

// If connection fails, stop everything and show a clear error
if (!$conn) {
    die("<h3 style='color:red;font-family:sans-serif;'>
         ❌ Database connection failed: " . mysqli_connect_error() . "<br>
         Make sure XAMPP is running and the database 'ecommerce_db' exists.<br>
         Run setup.sql in phpMyAdmin to create it.
         </h3>");
}

// BUG FIX: Set charset to utf8mb4 so special characters
//          (emoji, accents) are stored and retrieved correctly.
mysqli_set_charset($conn, "utf8mb4");
?>
