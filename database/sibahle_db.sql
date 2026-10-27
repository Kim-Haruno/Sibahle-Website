
CREATE DATABASE IF NOT EXISTS sibahle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sibahle_db;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    category_slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (category_name, category_slug, description) VALUES
('Clothes', 'clothes', 'African inspired apparel'),
('Herbal Products', 'herbal-products', 'Handcrafted herbal teas'),
('Bath Salts', 'bath-salts', 'Natural bath salts'),
('Oils', 'oils', 'Magnesium oil and body oils'),
('Foot Soaks', 'foot-soaks', 'Relaxing foot soaks');

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    base_price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    sku VARCHAR(50) UNIQUE,
    image_url VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active)
);

CREATE TABLE product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_type ENUM('size', 'flavour', 'colour', 'volume') NOT NULL,
    variant_value VARCHAR(50) NOT NULL,
    price_adjustment DECIMAL(10,2) DEFAULT 0.00,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(50) UNIQUE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);

CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
);

CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    shipping_first_name VARCHAR(50) NOT NULL,
    shipping_last_name VARCHAR(50) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_suburb VARCHAR(100),
    shipping_city VARCHAR(100) NOT NULL,
    shipping_province VARCHAR(50) NOT NULL,
    shipping_postal_code VARCHAR(10) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('payfast', 'cod') DEFAULT 'payfast',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('new', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'new',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (order_status),
    INDEX idx_payment (payment_status),
    INDEX idx_created (created_at)
);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    product_name VARCHAR(200) NOT NULL,
    variant_description VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL
);

CREATE TABLE newsletter_subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);


-- Admin user (password: Admin@123)
INSERT INTO users (first_name, last_name, email, password_hash, role, is_verified) VALUES
('Sibahle', 'Admin', 'admin@sibahle.co.za', '$2y$12$hsfjAb4umF2Fc.c76oItzekZlQSBSG6SatORurQJKAs9uenRj84pO', 'admin', 1);

-- Sample products
INSERT INTO products (category_id, product_name, product_slug, description, base_price, sale_price, stock_quantity, sku, is_featured) VALUES
(1, 'African Print Wrap Dress', 'african-print-wrap-dress', 'Beautiful Ankara print wrap dress, handcrafted in Mpumalanga.', 899.00, 799.00, 15, 'CLO-001', 1),
(2, 'Rooibos & Ginger Herbal Tea', 'rooibos-ginger-tea', 'Organic rooibos blended with fresh ginger.', 120.00, NULL, 50, 'TEA-001', 1),
(3, 'Lavender Bath Salts', 'lavender-bath-salts', 'Relaxing lavender-infused bath salts.', 85.00, NULL, 40, 'SALT-001', 1),
(4, 'Pure Magnesium Oil', 'magnesium-oil', 'Therapeutic-grade magnesium oil for muscle recovery.', 150.00, 129.00, 30, 'OIL-001', 1),
(5, 'Herbal Foot Soak', 'herbal-foot-soak', 'Refreshing foot soak with African herbs.', 95.00, NULL, 35, 'SOAK-001', 0);

-- Sample variants
INSERT INTO product_variants (product_id, variant_type, variant_value, price_adjustment, stock_quantity) VALUES
(1, 'size', 'S', 0.00, 5),
(1, 'size', 'M', 0.00, 5),
(1, 'size', 'L', 0.00, 3),
(1, 'size', 'XL', 50.00, 2),
(2, 'flavour', 'Original', 0.00, 25),
(2, 'flavour', 'Extra Spicy', 10.00, 25),
(4, 'volume', '100ml', 0.00, 15),
(4, 'volume', '200ml', 80.00, 15);