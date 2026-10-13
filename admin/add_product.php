<?php
// ============================================
// admin/add_product.php — Admin Add Product
//
// BUGS FIXED:
// 1. Price was inserted as a raw string — if someone
//    typed "abc" the DB would store 0.00 silently.
//    Added is_numeric() check to validate it is a number.
// 2. No minimum price validation — negative prices were
//    possible. Added check for price >= 0.
// 3. Image URL had no basic format check. Added
//    filter_var with FILTER_VALIDATE_URL.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['product_name']     ?? '');
    $price = trim($_POST['product_price']    ?? '');
    $cat   = trim($_POST['product_category'] ?? '');
    $img   = trim($_POST['product_image']    ?? '');

    if (empty($name) || empty($price) || empty($cat) || empty($img)) {
        $error = "All fields are required.";
    // BUG FIX #1 & #2: Validate price is a real positive number
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $error = "Price must be a valid positive number.";
    // BUG FIX #3: Validate image URL format
    } elseif (!filter_var($img, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid image URL (must start with http:// or https://).";
    } else {
        $priceFloat = (float)$price; // Safe float conversion
        $nameEsc    = mysqli_real_escape_string($conn, $name);
        $catEsc     = mysqli_real_escape_string($conn, $cat);
        $imgEsc     = mysqli_real_escape_string($conn, $img);

        $sql = "INSERT INTO products (product_name, product_price, product_category, product_image)
                VALUES ('$nameEsc', $priceFloat, '$catEsc', '$imgEsc')";

        if (mysqli_query($conn, $sql)) {
            header("Location: dashboard.php?panel=products&added=1");
            exit();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">⚙️ ShopEase Admin</div>
    <div class="nav-links">
        <a href="dashboard.php?panel=products">← Products</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-center">
<div class="form-wrapper">
    <h2>Add New Product</h2>
    <p class="form-sub">Fill in the details to list a new product.</p>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name"
                   placeholder="e.g. Wireless Headphones"
                   maxlength="150" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price ($) *</label>
                <!-- BUG FIX: min="0" + step="0.01" enforced in browser too -->
                <input type="number" name="product_price"
                       placeholder="49.99" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="product_category" required>
                    <option value="" disabled selected>Select…</option>
                    <option>Electronics</option>
                    <option>Clothing</option>
                    <option>Books</option>
                    <option>Home &amp; Garden</option>
                    <option>Sports</option>
                    <option>Toys</option>
                    <option>Beauty</option>
                    <option>Other</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Image URL *</label>
            <input type="url" id="product_image" name="product_image"
                   placeholder="https://example.com/image.jpg" required>
        </div>

        <!-- Live image preview (handled by script.js) -->
        <div id="imgPreviewBox" class="img-preview hidden">
            <p>Preview:</p>
            <img id="imgPreview" src="" alt="Preview"
                 onerror="this.parentElement.classList.add('hidden')">
        </div>

        <button type="submit" class="btn-primary full-width">Save Product</button>
    </form>
</div>
</div>

<footer class="footer"><p>ShopEase Admin &copy; <?= date('Y') ?></p></footer>
<script src="../script.js"></script>
</body>
</html>
