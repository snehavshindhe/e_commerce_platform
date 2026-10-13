<?php
// ============================================
// index.php — ShopEase v2 Main Storefront
//
// BUGS FIXED:
// 1. session_start() was called WITHOUT checking if a
//    session already exists — caused "headers already sent"
//    warnings when session.php was also included. Fixed by
//    using session_status() check.
// 2. Cart count could be NULL when cart is empty — used
//    (int) cast to always return 0 safely.
// 3. User name in navbar was missing htmlspecialchars()
//    in one place — potential XSS. Fixed.
// 4. Profile section queried DB without error check.
//    Added guard in case user row is missing.
// ============================================

// BUG FIX #1: Check before starting session to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';

// Fetch all products (newest first)
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");

// BUG FIX #2: Cast to int so cart badge never shows NULL
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid  = (int)$_SESSION['user_id'];
    $cRes = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id=$uid");
    $cRow = mysqli_fetch_assoc($cRes);
    $cartCount = (int)($cRow['total'] ?? 0);
}

// Distinct categories for the filter dropdown
$catRes = mysqli_query($conn, "SELECT DISTINCT product_category FROM products ORDER BY product_category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopEase — Store</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ======= NAVBAR ======= -->
<nav class="navbar">
    <div class="nav-brand">🛍️ ShopEase</div>

    <!-- BUG FIX: Added hamburger button for mobile nav toggle -->
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle menu">☰</button>

    <div class="nav-links" id="navLinks">
        <a href="#" onclick="showSection('home')">Home</a>
        <a href="#" onclick="showSection('products')">Products</a>
        <a href="#" onclick="showSection('cart')" class="cart-link">
            🛒 Cart
            <?php if ($cartCount > 0): ?>
                <!-- BUG FIX #2: cartCount is now always an int, never null -->
                <span class="cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#" onclick="showSection('orders')">My Orders</a>
            <!-- BUG FIX #3: htmlspecialchars prevents XSS in username -->
            <a href="#" onclick="showSection('profile')">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></a>
            <a href="user/logout.php">Logout</a>
        <?php else: ?>
            <a href="user/login.php">Login</a>
            <a href="user/register.php">Register</a>
        <?php endif; ?>
        <a href="admin/login.php" style="opacity:0.6;font-size:0.78rem;">Admin</a>
    </div>
</nav>

<!-- ======= HOME SECTION ======= -->
<section id="home" class="section active">
    <div class="hero">
        <div class="hero-text">
            <h1>Shop Your <span>Favourites</span> Today</h1>
            <p>Browse hundreds of products, add to cart, and checkout in seconds — all in one place.</p>
            <div class="hero-btns">
                <button class="btn-primary" onclick="showSection('products')">Browse Products</button>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="user/register.php" class="btn-outline">Create Account</a>
                <?php else: ?>
                    <button class="btn-outline" onclick="showSection('cart')">View Cart 🛒</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-art">
            <div class="art-card"><span>📦</span><p>Easy Product Management</p></div>
            <div class="art-card"><span>💰</span><p>Best Prices Guaranteed</p></div>
            <div class="art-card"><span>🚚</span><p>Cash on Delivery</p></div>
        </div>
    </div>
</section>

<!-- ======= PRODUCTS SECTION ======= -->
<section id="products" class="section">
    <div class="products-header">
        <h2>All Products</h2>
        <p>Find what you love — search, filter, add to cart.</p>
    </div>

    <!-- Search + Category filter -->
    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="🔍 Search products…" onkeyup="filterProducts()">
        <select id="categoryFilter" onchange="filterProducts()">
            <option value="">All Categories</option>
            <?php while ($cat = mysqli_fetch_assoc($catRes)): ?>
                <option value="<?= strtolower(htmlspecialchars($cat['product_category'])) ?>">
                    <?= htmlspecialchars($cat['product_category']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- BUG FIX: cart_msg alert is shown and auto-hidden by JS -->
    <?php if (isset($_GET['cart_msg'])): ?>
        <div class="alert-success" id="cartAlert" style="max-width:500px;margin:0 auto 24px;">
            ✅ Product added to cart!
        </div>
    <?php endif; ?>

    <div class="product-grid" id="productGrid">
        <?php if ($products && mysqli_num_rows($products) > 0):
            while ($row = mysqli_fetch_assoc($products)): ?>
            <div class="product-card"
                 data-name="<?= strtolower(htmlspecialchars($row['product_name'])) ?>"
                 data-category="<?= strtolower(htmlspecialchars($row['product_category'])) ?>">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($row['product_image']) ?>"
                         alt="<?= htmlspecialchars($row['product_name']) ?>"
                         onerror="this.src='https://placehold.co/300x200?text=No+Image'">
                    <span class="badge"><?= htmlspecialchars($row['product_category']) ?></span>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($row['product_name']) ?></h3>
                    <p class="price">$<?= number_format($row['product_price'], 2) ?></p>
                    <div class="card-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- POST form to add to cart -->
                            <form action="cart/add.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:0.82rem;">+ Cart</button>
                            </form>
                        <?php else: ?>
                            <a href="user/login.php" class="btn-primary" style="padding:8px 16px;font-size:0.82rem;">+ Cart</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="empty-state"><span>📭</span><p>No products found.</p></div>
        <?php endif; ?>
    </div>
</section>

<!-- ======= CART SECTION ======= -->
<section id="cart" class="section">
<?php
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    // Fetch cart items joined with product details
    $cartItems = mysqli_query($conn,
        "SELECT c.id as cart_id, c.quantity,
                p.product_name, p.product_price, p.product_image, p.id as pid
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = $uid");

    $total = 0;
    echo '<div class="cart-wrapper">';
    echo '<h2>&#128722; Your Cart</h2>';

    if ($cartItems && mysqli_num_rows($cartItems) > 0) {
        // Collect all rows first so we can show the total correctly
        $cartRows = [];
        while ($item = mysqli_fetch_assoc($cartItems)) {
            $item['subtotal'] = $item['product_price'] * $item['quantity'];
            $total += $item['subtotal'];
            $cartRows[] = $item;
        }

        foreach ($cartRows as $item) {
?>
            <div class="cart-item">
                <img src="<?= htmlspecialchars($item['product_image']) ?>"
                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                     onerror="this.src='https://placehold.co/80x80?text=?'">
                <div class="cart-item-info">
                    <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                    <p>$<?= number_format($item['product_price'], 2) ?> each</p>
                    <!-- Quantity controls via POST forms -->
                    <div class="qty-control">
                        <form action="cart/update.php" method="POST" style="display:inline">
                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" class="qty-btn">&#8722;</button>
                        </form>
                        <span class="qty-num"><?= (int)$item['quantity'] ?></span>
                        <form action="cart/update.php" method="POST" style="display:inline">
                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                        <form action="cart/remove.php" method="POST" style="display:inline;margin-left:8px;">
                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                            <button type="submit" class="btn-danger">Remove</button>
                        </form>
                    </div>
                </div>
                <div style="font-weight:700;color:var(--accent);font-size:1rem;white-space:nowrap;">
                    $<?= number_format($item['subtotal'], 2) ?>
                </div>
            </div>
<?php
        } // end foreach
?>
        <div class="cart-summary">
            <h3>Order Summary</h3>
            <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($total, 2) ?></span></div>
            <div class="summary-row"><span>Delivery</span><span>FREE</span></div>
            <div class="summary-row summary-total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
            <a href="checkout.php" class="btn-primary full-width" style="margin-top:18px;text-align:center;display:block;">
                Proceed to Checkout &#8594;
            </a>
        </div>
<?php
    } else {
        // Empty cart
        echo '<div class="empty-state">
                <span>&#128722;</span>
                <p>Your cart is empty. <a href="#" onclick="showSection(\'products\')">Start shopping!</a></p>
              </div>';
    }
    echo '</div>'; // .cart-wrapper

} else {
    // Not logged in
    echo '<div class="empty-state" style="margin-top:60px;">
            <span>&#128274;</span>
            <p>Please <a href="user/login.php">login</a> to view your cart.</p>
          </div>';
}
?>
</section>

<!-- ======= MY ORDERS SECTION ======= -->
<section id="orders" class="section">
<?php
if (isset($_SESSION['user_id'])) {
    $uid    = (int)$_SESSION['user_id'];
    $orders = mysqli_query($conn,
        "SELECT * FROM orders WHERE user_id=$uid ORDER BY created_at DESC");
?>
    <div class="orders-wrapper">
        <h2>&#128203; My Orders</h2>
        <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
        <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Order ID</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php while ($ord = mysqli_fetch_assoc($orders)):
                $cls = 'status-' . strtolower($ord['status']); ?>
                <tr>
                    <td>#<?= $ord['id'] ?></td>
                    <td><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
                    <td>$<?= number_format($ord['total_price'], 2) ?></td>
                    <td><?= htmlspecialchars($ord['payment']) ?></td>
                    <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($ord['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <span>&#128230;</span>
                <p>No orders yet. <a href="#" onclick="showSection('products')">Shop now!</a></p>
            </div>
        <?php endif; ?>
    </div>
<?php } else { ?>
    <div class="empty-state" style="margin-top:60px;">
        <span>&#128274;</span>
        <p>Please <a href="user/login.php">login</a> to see orders.</p>
    </div>
<?php } ?>
</section>

<!-- ======= PROFILE SECTION ======= -->
<section id="profile" class="section">
<?php
if (isset($_SESSION['user_id'])):
    $uid  = (int)$_SESSION['user_id'];
    $uRes = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
    // BUG FIX #4: Guard in case the user row somehow doesn't exist
    $u    = $uRes ? mysqli_fetch_assoc($uRes) : null;
    if ($u):
?>
    <div style="max-width:540px;margin:0 auto;">
        <h2 style="font-family:var(--font-display);font-size:2rem;color:var(--charcoal);margin-bottom:24px;">👤 My Profile</h2>
        <div class="profile-card">
            <div class="profile-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
            <div class="profile-name"><?= htmlspecialchars($u['name']) ?></div>
            <div class="profile-email"><?= htmlspecialchars($u['email']) ?></div>
            <div class="profile-row"><span>Phone</span><span><?= htmlspecialchars($u['phone'] ?: '—') ?></span></div>
            <div class="profile-row"><span>Address</span><span><?= htmlspecialchars($u['address'] ?: '—') ?></span></div>
            <div class="profile-row"><span>Member Since</span><span><?= date('d M Y', strtotime($u['created_at'])) ?></span></div>
        </div>
        <a href="user/edit_profile.php" class="btn-outline" style="margin-right:10px;">Edit Profile</a>
        <a href="user/logout.php" class="btn-danger" style="text-decoration:none;padding:11px 26px;border-radius:8px;font-size:0.95rem;">Logout</a>
    </div>
<?php
    endif; // end if $u
else: ?>
    <div class="empty-state" style="margin-top:60px;">
        <span>🔒</span><p>Please <a href="user/login.php">login</a>.</p>
    </div>
<?php endif; ?>
</section>

<footer class="footer">
    <p>ShopEase &copy; <?= date('Y') ?> &nbsp;·&nbsp; Built with PHP &amp; MySQL</p>
</footer>

<script src="script.js"></script>
</body>
</html>
