-- ============================================================
-- REPENT† — Database Schema
-- File: repent_database.sql
--
-- PURPOSE: Creates all necessary database tables for the
-- REPENT† web application. Run this file in phpMyAdmin or
-- MySQL to set up the database.
--
-- HOW TO USE:
--   1. Open phpMyAdmin
--   2. Create a new database called 'repent_db'
--   3. Click "Import" and upload this file
--   4. Update includes/db.php with your credentials
--
-- MANAGED BY: Unarine (Data Administrator)
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS repent_db;
USE repent_db;

-- ============================================================
-- TABLE: users
-- Stores all registered customer and admin accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,  -- Unique user ID
    fullname    VARCHAR(100)  NOT NULL,           -- User's full name
    email       VARCHAR(150)  NOT NULL UNIQUE,    -- Email (used for login)
    password    VARCHAR(255)  NOT NULL,           -- Bcrypt hashed password
    role        ENUM('customer','admin') DEFAULT 'customer', -- User role
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP           -- Registration date
);

-- Insert a default admin user (password: admin123)
INSERT INTO users (fullname, email, password, role) VALUES
('Admin User', 'admin@repent.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Demo User',  'user@repent.co.za',  '$2y$10$TKh8H1.PfbuBz0xCJJe7VuAR3GPSMRkrPbFRflEdFIDHJoF7LroOy', 'customer');
-- admin123 hash | user123 hash

-- ============================================================
-- TABLE: products
-- Stores all clothing products in the store
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)   NOT NULL,            -- Product name
    description TEXT,                               -- Full product description
    price       DECIMAL(10,2)  NOT NULL,            -- Price in Rands
    category    ENUM('tshirts','hoodies','caps') NOT NULL, -- Product category
    image       VARCHAR(255),                       -- Image filename
    stock       INT DEFAULT 0,                      -- Stock quantity
    badge       VARCHAR(50),                        -- e.g. 'New', 'Popular'
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample products
INSERT INTO products (name, description, price, category, image, stock, badge) VALUES
('Disciples Tee',        'Go and Make Disciples of All Nations — Matthew 28:19.',  350.00, 'tshirts', 'tshirt-white.jpg',       20, 'New'),
('Be Still Tee (Pink)',  'Be Still and Know That I Am God — Psalm 46:10.',          320.00, 'tshirts', 'products-collection.jpg', 15, 'Popular'),
('Be Still Tee (Blue)',  'Be Still and Know That I Am God — Psalm 46:10.',          320.00, 'tshirts', 'products-flat.jpg',       12, NULL),
('REPENT† Classic Tee',  'REPENT† classic bold statement tee.',                     320.00, 'tshirts', 'people-wearing.jpg',      18, NULL),
('REPENT† Hoodie (Red)', 'Heavyweight hoodie with globe print on back.',            550.00, 'hoodies', 'hoodies-collection.jpg',  10, 'Bestseller'),
('REPENT† Hoodie (Pink)','Soft pink hoodie with REPENT† logo on chest.',            550.00, 'hoodies', 'hoodies-collection.jpg',  8,  NULL),
('REPENT† Crewneck',     'Red crewneck sweatshirt in multiple colours.',            480.00, 'hoodies', 'hoodies-collection.jpg',  14, NULL),
('Cap — Coral',          'Washed coral dad cap with embroidered REPENT† logo.',     280.00, 'caps',    'cap-coral.jpg',           25, NULL),
('Cap — Olive',          'Washed olive/khaki dad cap.',                             280.00, 'caps',    'cap-olive.jpg',           22, NULL),
('Cap — Yellow',         'Vintage yellow washed dad cap.',                          280.00, 'caps',    'cap-yellow.jpg',          20, NULL),
('Cap — Mint',           'Fresh mint/teal washed dad cap.',                         280.00, 'caps',    'cap-mint.jpg',            18, 'New'),
('Cap — Khaki',          'Stone washed khaki dad cap.',                             280.00, 'caps',    'cap-khaki.jpg',           24, NULL);

-- ============================================================
-- TABLE: orders
-- Stores customer purchase orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,                          -- References users.id
    total       DECIMAL(10,2) NOT NULL,                -- Order total in Rands
    shipping    DECIMAL(10,2) DEFAULT 80.00,           -- Shipping cost
    status      ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- TABLE: order_items
-- Stores individual items within each order
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,                          -- References orders.id
    product_id  INT NOT NULL,                          -- References products.id
    product_name VARCHAR(150),                         -- Snapshot of name at time of order
    price       DECIMAL(10,2) NOT NULL,                -- Price at time of order
    quantity    INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id)   REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- TABLE: prayer_requests
-- Stores prayer requests submitted via the website
-- ============================================================
CREATE TABLE IF NOT EXISTS prayer_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,                -- Submitter's name
    email        VARCHAR(150),                         -- Optional contact email
    subject      VARCHAR(100),                         -- Prayer topic
    prayer       TEXT NOT NULL,                        -- The prayer request text
    is_private   TINYINT(1) DEFAULT 0,                 -- 1 = private, 0 = public
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: newsletter_subscribers
-- Stores email newsletter subscriptions
-- ============================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,               -- Subscriber name
    email         VARCHAR(150) NOT NULL UNIQUE,        -- Email (must be unique)
    frequency     ENUM('daily','weekly','monthly') DEFAULT 'weekly',
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: devotionals
-- Stores daily devotional articles
-- ============================================================
CREATE TABLE IF NOT EXISTS devotionals (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,                  -- Devotional title
    verse      TEXT NOT NULL,                          -- Scripture verse
    reference  VARCHAR(100),                           -- Book/chapter/verse ref
    content    TEXT NOT NULL,                          -- Full article body
    topic      VARCHAR(100),                           -- Tag/topic
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sample devotional
INSERT INTO devotionals (title, verse, reference, content, topic) VALUES
('Strength in Surrender',
 'I can do all things through Christ who strengthens me.',
 'Philippians 4:13',
 'True strength is not found in our own abilities, but in yielding ourselves entirely to God. When we acknowledge our weakness, His power is made perfect in us. Paul wrote this verse from a prison cell — not a stage. Whatever you are facing today, Christ\'s strength is available to you.',
 'Strength');
