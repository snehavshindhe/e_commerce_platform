<?php
// ============================================
// admin/dashboard.php — Admin Dashboard
//
// BUGS FIXED:
// 1. Products, orders, and users were queried TWICE —
//    once at the top (for stats) and again inside the
//    HTML sections. The first result set was consumed
//    and empty by the time the HTML loop ran.
//    Fixed: removed the redundant top-level queries;
//    queries are done only once, right before their loop.
// 2. Revenue SUM could be NULL if no orders exist —
//    was already handled with ?? 0 but added explicit
//    (float) cast for safety.
// 3. order status badge classes could break CSS if status
//    had spaces (e.g. "Cash on Delivery"). status class
//    is fine — it's already lowercase status words.
//    But added htmlspecialchars on status display.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

include '../includes/db.php';

// ---- Stats (single-value queries — fine to run at top) ----
$totalProducts = (int)   mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
$totalUsers    = (int)   mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$totalOrders   = (int)   mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
$totalRevenue  = (float)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as s FROM orders"))['s'] ?? 0);

// BUG FIX #1: Do NOT fetch products/orders/users here at top level.
//             They will be queried right before each HTML table loop below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — ShopEase</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>

<!-- Admin Navbar -->
<nav class="navbar">
    <div class="nav-brand">⚙️ ShopEase Admin</div>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle menu">☰</button>
    <div class="nav-links" id="navLinks">
        <a href="#" onclick="adminShow('stats')">Dashboard</a>
        <a href="#" onclick="adminShow('products')">Products</a>
        <a href="#" onclick="adminShow('orders')">Orders</a>
        <a href="#" onclick="adminShow('users')">Users</a>
        <a href="add_product.php">+ Add Product</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div style="padding:40px 5% 80px;">

<!-- ======= STATS ======= -->
<div id="admin-stats" class="admin-panel">
    <h2 class="admin-section-title">📊 Dashboard Overview</h2>
    <div class="dash-stats">
        <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-num"><?= $totalProducts ?></div><div class="stat-label">Products</div></div>
        <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-num"><?= $totalUsers ?></div><div class="stat-label">Users</div></div>
        <div class="stat-card"><div class="stat-icon">🧾</div><div class="stat-num"><?= $totalOrders ?></div><div class="stat-label">Orders</div></div>
        <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-num">$<?= number_format($totalRevenue, 0) ?></div><div class="stat-label">Revenue</div></div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;">
        <button class="btn-primary" onclick="adminShow('products')">Manage Products</button>
        <button class="btn-outline" onclick="adminShow('orders')">View Orders</button>
    </div>
</div>

<!-- ======= PRODUCTS ======= -->
<div id="admin-products" class="admin-panel" style="display:none;">
    <h2 class="admin-section-title">📦 Manage Products</h2>

    <?php if (isset($_GET['del'])):     ?><div class="alert-success">✅ Product deleted.</div><?php endif; ?>
    <?php if (isset($_GET['added'])):   ?><div class="alert-success">✅ Product added.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert-success">✅ Product updated.</div><?php endif; ?>

    <div style="margin-bottom:18px;">
        <a href="add_product.php" class="btn-primary">+ Add New Product</a>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Category</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php
        // BUG FIX #1: Query ONLY here, not at the top of the file
        $products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
        if ($products && mysqli_num_rows($products) > 0):
            while ($p = mysqli_fetch_assoc($products)): ?>
            <tr>
                <td>#<?= (int)$p['id'] ?></td>
                <td>
                    <img src="<?= htmlspecialchars($p['product_image']) ?>"
                         style="width:52px;height:42px;object-fit:cover;border-radius:6px;"
                         onerror="this.src='https://placehold.co/52x42?text=?'">
                </td>
                <td><?= htmlspecialchars($p['product_name']) ?></td>
                <td>$<?= number_format($p['product_price'], 2) ?></td>
                <td><?= htmlspecialchars($p['product_category']) ?></td>
                <td style="display:flex;gap:6px;align-items:center;">
                    <a href="edit_product.php?id=<?= (int)$p['id'] ?>" class="btn-edit">Edit</a>
                    <!-- BUG FIX: Delete is now a POST form, not a GET link,
                         to prevent accidental/malicious deletion via URL -->
                    <form action="delete_product.php" method="POST" style="display:inline;"
                          onsubmit="return confirm('Delete this product? This cannot be undone.');">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile;
        else: ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:#999;">No products yet. <a href="add_product.php">Add one!</a></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ======= ORDERS ======= -->
<div id="admin-orders" class="admin-panel" style="display:none;">
    <h2 class="admin-section-title">🧾 All Orders</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>ID</th><th>Customer</th><th>Email</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Update</th></tr>
        </thead>
        <tbody>
        <?php
        // BUG FIX #1: Query only here
        $orders = mysqli_query($conn,
            "SELECT o.*, u.name as uname, u.email
             FROM orders o
             JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC");
        if ($orders && mysqli_num_rows($orders) > 0):
            while ($ord = mysqli_fetch_assoc($orders)):
                $cls = 'status-' . strtolower($ord['status']); ?>
            <tr>
                <td>#<?= (int)$ord['id'] ?></td>
                <td><?= htmlspecialchars($ord['uname']) ?></td>
                <td><?= htmlspecialchars($ord['email']) ?></td>
                <td>$<?= number_format($ord['total_price'], 2) ?></td>
                <td><?= htmlspecialchars($ord['payment']) ?></td>
                <!-- BUG FIX #3: htmlspecialchars on status display -->
                <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($ord['status']) ?></span></td>
                <td><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
                <td>
                    <form action="update_order.php" method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                        <select name="status" onchange="this.form.submit()"
                                style="padding:5px 8px;border-radius:6px;font-size:0.8rem;border:1px solid var(--border);">
                            <option <?= $ord['status']==='Pending'   ? 'selected' : '' ?>>Pending</option>
                            <option <?= $ord['status']==='Shipped'   ? 'selected' : '' ?>>Shipped</option>
                            <option <?= $ord['status']==='Delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </form>
                </td>
            </tr>
        <?php endwhile;
        else: ?>
            <tr><td colspan="8" style="text-align:center;padding:24px;color:#999;">No orders yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ======= USERS ======= -->
<div id="admin-users" class="admin-panel" style="display:none;">
    <h2 class="admin-section-title">👥 Registered Users</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th></tr>
        </thead>
        <tbody>
        <?php
        // BUG FIX #1: Query only here
        $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
        if ($users && mysqli_num_rows($users) > 0):
            while ($u = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td>#<?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            </tr>
        <?php endwhile;
        else: ?>
            <tr><td colspan="5" style="text-align:center;padding:24px;color:#999;">No users registered yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div><!-- /padding wrapper -->

<footer class="footer"><p>ShopEase Admin &copy; <?= date('Y') ?></p></footer>

<script>
// Show one admin panel section, hide all others
function adminShow(id) {
    document.querySelectorAll('.admin-panel').forEach(p => p.style.display = 'none');
    const el = document.getElementById('admin-' + id);
    if (el) el.style.display = 'block';
}

// On load — read ?panel= from URL to jump to right section
window.addEventListener('DOMContentLoaded', function () {
    const p = new URLSearchParams(window.location.search).get('panel') || 'stats';
    adminShow(p);
});

// Mobile nav toggle (same helper as front end)
function toggleNav() {
    const nav = document.getElementById('navLinks');
    if (nav) nav.classList.toggle('open');
}
</script>
</body>
</html>
