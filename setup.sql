-- ============================================
-- setup.sql — ShopEase v2 Full Database Setup
-- Run this once in phpMyAdmin → SQL tab
-- ============================================

CREATE DATABASE IF NOT EXISTS ecommerce_db;
USE ecommerce_db;

-- ---- PRODUCTS TABLE ----
CREATE TABLE IF NOT EXISTS products (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    product_name     VARCHAR(150)   NOT NULL,
    product_price    DECIMAL(10,2)  NOT NULL,
    product_category VARCHAR(100)   NOT NULL,
    product_image    TEXT           NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- USERS TABLE (registered customers) ----
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- stored as MD5 hash (simple, beginner-friendly)
    phone      VARCHAR(20)  DEFAULT '',
    address    TEXT         DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- ADMIN TABLE ----
CREATE TABLE IF NOT EXISTS admin (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL             -- MD5 hash
);

-- ---- CART TABLE (session-based, DB-backed) ----
CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT  NOT NULL,
    product_id INT  NOT NULL,
    quantity   INT  NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---- ORDERS TABLE ----
CREATE TABLE IF NOT EXISTS orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT            NOT NULL,
    total_price  DECIMAL(10,2)  NOT NULL,
    name         VARCHAR(150)   NOT NULL,
    address      TEXT           NOT NULL,
    phone        VARCHAR(20)    NOT NULL,
    payment      VARCHAR(50)    DEFAULT 'Cash on Delivery',
    status       VARCHAR(50)    DEFAULT 'Pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---- ORDER ITEMS TABLE (products inside each order) ----
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    product_id INT           NOT NULL,
    quantity   INT           NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- Default Admin Account
-- Username: admin  |  Password: admin123
-- ============================================
INSERT IGNORE INTO admin (username, password)
VALUES ('admin', MD5('admin123'));

-- ============================================
-- Sample Products
-- ============================================
INSERT INTO products (product_name, product_price, product_category, product_image) VALUES
('Wireless Headphones', 59.99, 'Electronics', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400'),
('Running Shoes',       89.00, 'Sports',       'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'),
('Leather Notebook',   14.50, 'Books',         'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400'),
('Smart Watch',        129.00,'Electronics',   'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'),
('Yoga Mat',            29.99,'Sports',        'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400'),
('Desk Lamp',           39.99,'Home & Garden', 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=400');
