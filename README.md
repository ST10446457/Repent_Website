# REPEN† — Faith-Based Clothing & Spiritual Growth Web Application

REPEN† is a full-stack **PHP & MySQL web application** and **e-commerce platform** created for a Christian apparel brand and ministry based in South Africa.

The platform combines an online faith-inspired clothing store (t-shirts, hoodies, crewnecks, and caps) with digital spiritual growth features such as daily devotionals, prayer requests, and newsletter subscriptions.

---

# 🚀 Features

## 🛍️ E-Commerce & Storefront

### Dynamic Product Catalogue
- Browse Christian apparel products including:
  - T-Shirts
  - Hoodies
  - Crewnecks
  - Caps
- South African Rand (ZAR - R) pricing support.

### Interactive Shopping Cart
- Add and remove products.
- Adjust product quantities.
- Automatic subtotal calculations.
- Free shipping progress indicator.
- Free delivery available for orders over **R800**.

### Checkout & Order Processing
- Multi-step checkout process.
- Supports:
  - Instant EFT payments (OZOW/PayFast workflows)
  - Credit/Debit card payment flows
- Includes transactional fallback support for demonstration environments.

---

# 📖 Spiritual Growth & Ministry

## Daily Devotionals
Scripture-based devotionals with categories including:

- Transformation
- Repentance
- Peace
- Strength
- Identity
- Mission
- Faith

## Prayer Request Portal
Allows users to:
- Submit prayer requests.
- Choose public or private requests.
- Send requests to the prayer team.

## Community Newsletter
Subscription system supporting:
- Daily newsletters
- Weekly newsletters
- Monthly newsletters

Includes user preference tracking.

---

# 🛡️ Authentication & Administration

## Role-Based Access Control (RBAC)

The system supports multiple user roles:

### Customer
- Browse products.
- Manage shopping cart.
- Place orders.
- Submit prayer requests.
- Subscribe to newsletters.

### Administrator
- Manage products.
- View orders.
- Monitor inventory.
- Manage prayer requests.
- View newsletter subscribers.

---

## Admin Dashboard

The administration panel provides:

- Sales and order statistics.
- Order tracking.
- Inventory management.
- Prayer request management.
- Subscriber management.

---

## Dual Operating Modes

REPENT† can operate in two modes:

### Live Database Mode
- Uses MySQL/MariaDB.
- Stores real users, products, orders, and requests.

### Demo Mode
- Runs without database configuration.
- Uses mock data for presentations and demonstrations.

---

# 🛠️ Technology Stack

## Backend
- PHP 8.x

## Database
- MySQL / MariaDB

Database Name:

```
repent_db
```

## Frontend
- HTML5
- CSS3
- Responsive layouts using:
  - CSS Variables
  - Flexbox
  - CSS Grid
- Vanilla JavaScript

## Graphics
- Inline SVG icons
- Custom CSS logo rendering

## Security
Implemented security features:

- Password hashing using bcrypt.
- Prepared SQL statements to prevent SQL injection.
- Session-based authentication guards.
- Secure transaction handling.

---

# 📁 Repository Structure

```
REPENT/
│
├── assets/
│   └── images/              
│       └── Product images and brand visuals
│
├── includes/
│   ├── header.php           
│   ├── footer.php            
│   ├── db.php               
│   └── logo.php             
│
├── pages/
│   ├── shop.php             
│   ├── cart.php             
│   ├── checkout.php         
│   ├── devotional.php       
│   ├── prayer.php           
│   ├── newsletter.php       
│   ├── login.php            
│   ├── register.php         
│   ├── logout.php           
│   └── admin.php            
│
├── index.php                
│
└── repent_database.sql      
```

---

# ⚡ Installation & Setup

## Requirements

Before running the application, install:

- PHP 8.0 or higher
- MySQL / MariaDB
- Local web server environment:
  - XAMPP
  - WAMP
  - MAMP
  - Laragon
  - Apache/Nginx

---

# Option 1: Run Using XAMPP/WAMP

## 1. Clone or Download Repository

Place the project inside your server directory:

### XAMPP

```
C:/xampp/htdocs/repent-clothing
```

### WAMP

```
C:/wamp64/www/repent-clothing
```

### MAMP

```
/Applications/MAMP/htdocs/repent-clothing
```

---

## 2. Configure Database

1. Start Apache and MySQL.
2. Open:

```
http://localhost/phpmyadmin
```

3. Create a database:

```
repent_db
```

4. Import:

```
repent_database.sql
```

---

## 3. Configure Database Connection

Open:

```
includes/db.php
```

Update your database credentials:

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'repent_db';
```

---

## 4. Launch Application

Open:

```
http://localhost/repent-clothing/index.php
```

---

# Option 2: Run Using PHP Built-In Server

For quick demonstration without MySQL setup:

Navigate to the project folder:

```bash
cd path/to/repent-clothing
```

Start PHP server:

```bash
php -S localhost:8000
```

Open:

```
http://localhost:8000
```

The application will automatically switch to **Demo Mode** when no database connection is available.

---

# 🔑 Demo Accounts

## Administrator Account

| Role | Email | Password |
|---|---|---|
| Administrator | admin@repent.co.za | admin123 |

Access:

```
/pages/admin.php
```

Permissions:
- Full admin dashboard access
- Product management
- Order management
- User management

---

## Customer Account

| Role | Email | Password |
|---|---|---|
| Customer | user@repent.co.za | user123 |

Permissions:
- Shopping functionality
- Checkout process
- Prayer requests
- Newsletter subscription

---

# 👤 Project Information

**Platform:**  
REPENT† Faith-Based Clothing & Ministry

**Lead Developer & Data Administrator:**  
Unarine

---

# 📄 License

This project is licensed under the **MIT License**.

You are free to use, modify, and distribute this project according to the MIT License terms.
