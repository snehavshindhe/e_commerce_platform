<?php
// ============================================
// admin/edit_product.php — Edit Product
//
// BUGS FIXED:
// 1. Same as add_product.php — price was stored as
//    raw string; "abc" would silently save as 0.
//    Added is_numeric() check.
// 2. Image URL had no format validation. Added
//    filter_var FILTER_VALIDATE_URL check.
// 3. Category select did not show the current category
//    as selected when the saved category was a value
//    that happened to contain "&" (e.g. "Home & Garden").
//    Fixed: htmlspecialchars comparison in option loop.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

$id    = (int)($_GET['id'] ?? 0);
$error = '';

// Fetch the existing product — redirect if not found
$res = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: dashboard.php?panel=products");
    exit();
}
$p = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['product_name']     ?? '');
    $price = trim($_POST['product_price']    ?? '');
    $cat   = trim($_POST['product_category'] ?? '');
    $img   = trim($_POST['product_image']    ?? '');

    if (empty($name) || empty($price) || empty($cat) || empty($img)) {
        $error = "All fields are required.";
    // BUG FIX #1: Validate price
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $error = "Price must be a valid positive number.";
    // BUG FIX #2: Validate image URL
    } elseif (!filter_var($img, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid image URL (must start with http:// or https://).";
    } else {
        $priceFloat = (float)$price;
        $nameEsc    = mysqli_real_escape_string($conn, $name);
        $catEsc     = mysqli_real_escape_string($conn, $cat);
        $imgEsc     = mysqli_real_escape_string($conn, $img);

        $sql = "UPDATE products SET
                    product_name     = '$nameEsc',
                    product_price    = $priceFloat,
                    product_category = '$catEsc',
                    product_image    = '$imgEsc'
                WHERE id=$id";

        if (mysqli_query($conn, $sql)) {
            header("Location: dashboard.php?panel=products&updated=1");
            exit();
        } else {
            $error = "Update failed: " . mysqli_error($conn);
        }
    }
}

// Categories list (same as add_product)
$categories = ['Electronics','Clothing','Books','Home & Garden','Sports','Toys','Beauty','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product — Admin</title>
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
    <h2>Edit Product</h2>
    <p class="form-sub">Modify the product details below.</p>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name"
                   value="<?= htmlspecialchars($p['product_name']) ?>"
                   maxlength="150" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" name="product_price"
                       value="<?= htmlspecialchars($p['product_price']) ?>"
                       step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="product_category" required>
                    <?php foreach ($categories as $c): ?>
                        <!-- BUG FIX #3: Compare properly with htmlspecialchars -->
                        <option value="<?= htmlspecialchars($c) ?>"
                                <?= $p['product_category'] === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Image URL *</label>
            <input type="url" id="product_image" name="product_image"
                   value="<?= htmlspecialchars($p['product_image']) ?>" required>
        </div>

        <!-- Current image preview -->
        <div id="imgPreviewBox" class="img-preview">
            <p>Current Image:</p>
            <img id="imgPreview"
                 src="<?= htmlspecialchars($p['product_image']) ?>"
                 onerror="this.src='https://placehold.co/300x180?text=No+Image'"
                 alt="Preview">
        </div>

        <button type="submit" class="btn-primary full-width">Update Product</button>
    </form>
</div>
</div>

<footer class="footer"><p>ShopEase Admin &copy; <?= date('Y') ?></p></footer>
<script src="../script.js"></script>
</body>
</html>
