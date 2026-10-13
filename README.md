# ShopEase

ShopEase is a PHP and MySQL storefront demo. It includes a product catalog, customer accounts, a session-based shopping cart backed by MySQL, checkout, and an admin area for managing products and orders.

## Requirements

- PHP with the `mysqli` extension
- MySQL or MariaDB
- A local PHP web server (XAMPP is used in the setup below)

## Setup with XAMPP

1. Copy the `ecommerce_fixed` folder into XAMPP's `htdocs` directory.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin` and import `setup.sql`. This creates the `ecommerce_db` database, tables, sample products, and a default admin account.
4. Check the database connection settings in `includes/db.php`. The defaults are host `localhost`, user `root`, an empty password, and database `ecommerce_db`; update them if your local MySQL settings differ.
5. Open the storefront at `http://localhost/ecommerce_fixed/`.

If you use a different local PHP server, configure it to serve this project directory and keep Apache/PHP and MySQL running while using the app.

## Admin account

The setup script creates this demo account:

- Username: `admin`
- Password: `admin123`

Change or remove this account before exposing the application to other users or deploying it. The project is for learning and local demonstration, not production use: it currently uses MD5 for password hashing and has not been hardened for production security.

## Project layout

- `index.php` - storefront and product catalog
- `cart/` - add, update, and remove cart items
- `user/` - customer registration, login, logout, and profile editing
- `checkout.php`, `place_order.php`, `order_success.php` - checkout and order placement
- `admin/` - admin login, product management, and order updates
- `includes/db.php` - MySQL connection
- `setup.sql` - database schema and sample data
- `style.css`, `script.js` - frontend styles and interactions
