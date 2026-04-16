<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'inventory_db');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Create DB and tables if they don't exist
function initializeDatabase() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) return false;

    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);

    // Admin users table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category_id INT,
        sku VARCHAR(50) UNIQUE,
        quantity INT DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        cost_price DECIMAL(10,2) DEFAULT 0.00,
        low_stock_threshold INT DEFAULT 10,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // Sales table
    $conn->query("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        customer_name VARCHAR(100),
        total_amount DECIMAL(10,2),
        payment_method ENUM('cash','card','upi') DEFAULT 'cash',
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        admin_id INT
    )");

    // Sale items table
    $conn->query("CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Insert default admin if not exists
    $check = $conn->query("SELECT id FROM admin_users WHERE username='admin'");
    if ($check->num_rows === 0) {
        $hashed = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO admin_users (username, password, full_name) VALUES ('admin', '$hashed', 'Administrator')");
    }

    // Insert default categories
    $catCheck = $conn->query("SELECT COUNT(*) as cnt FROM categories");
    $row = $catCheck->fetch_assoc();
    if ($row['cnt'] == 0) {
        $conn->query("INSERT INTO categories (name) VALUES ('Groceries'),('Beverages'),('Snacks'),('Dairy'),('Personal Care'),('Household')");
    }

    // Insert sample products
    $prodCheck = $conn->query("SELECT COUNT(*) as cnt FROM products");
    $row = $prodCheck->fetch_assoc();
    if ($row['cnt'] == 0) {
        $conn->query("INSERT INTO products (name, category_id, sku, quantity, price, cost_price, low_stock_threshold) VALUES
            ('Basmati Rice 5kg', 1, 'GRC001', 50, 350.00, 280.00, 10),
            ('Wheat Flour 10kg', 1, 'GRC002', 30, 420.00, 340.00, 8),
            ('Tata Salt 1kg', 1, 'GRC003', 8, 28.00, 20.00, 10),
            ('Coca Cola 2L', 2, 'BEV001', 60, 95.00, 72.00, 15),
            ('Mineral Water 1L', 2, 'BEV002', 5, 20.00, 14.00, 20),
            ('Lays Chips Classic', 3, 'SNK001', 45, 20.00, 14.00, 15),
            ('Amul Butter 500g', 4, 'DAI001', 3, 260.00, 220.00, 5),
            ('Dove Soap Bar', 5, 'PC001', 35, 65.00, 48.00, 10),
            ('Surf Excel 1kg', 6, 'HH001', 20, 135.00, 105.00, 8),
            ('Maggi Noodles 70g', 3, 'SNK002', 80, 14.00, 10.00, 20)
        ");
    }

    $conn->close();
    return true;
}

initializeDatabase();
?>
