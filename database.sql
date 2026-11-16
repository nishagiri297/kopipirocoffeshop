-- Kopipiro Coffee Ordering App - database schema + demo data
-- Import this file in phpMyAdmin (Import tab) or run: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS kopipiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kopipiro;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('owner','cashier','customer') NOT NULL DEFAULT 'customer',
  phone VARCHAR(30) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE,
  is_coffee TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  image VARCHAR(255) NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (product_id, user_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT NULL,
  customer_name VARCHAR(100) NOT NULL,
  customer_phone VARCHAR(30) NULL,
  cashier_id INT NULL,
  order_type ENUM('dine-in','takeaway') NOT NULL DEFAULT 'dine-in',
  table_no VARCHAR(10) NULL,
  note VARCHAR(255) NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('pending','preparing','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(120) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  unit_discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo accounts: owner123 / cashier123 / customer123
INSERT INTO users (id, name, email, password, role) VALUES (1, 'Kopipiro Owner', 'owner@kopipiro.com', '$2y$10$M.td9JDe/fqW4J1wJq5p3OHmDAnQH7oGWf0DMYIF9IMF9t3a826Ky', 'owner');
INSERT INTO users (id, name, email, password, role) VALUES (2, 'Sari (Cashier)', 'cashier1@kopipiro.com', '$2y$10$vmoTrAjHVmLUyoqmQl9Nx.wig0hmgErM14wYt3FJxeYNR4hNxNm8G', 'cashier');
INSERT INTO users (id, name, email, password, role) VALUES (3, 'Dimas (Cashier)', 'cashier2@kopipiro.com', '$2y$10$vmoTrAjHVmLUyoqmQl9Nx.wig0hmgErM14wYt3FJxeYNR4hNxNm8G', 'cashier');
INSERT INTO users (id, name, email, password, role) VALUES (4, 'Budi Santoso', 'customer@kopipiro.com', '$2y$10$k1P8cazYvghcXNIK3P5uyuRq9Va0fNBInkseqo0WTcvTt3oe78Eqa', 'customer');
INSERT INTO users (id, name, email, password, role) VALUES (5, 'Ayu Lestari', 'ayu@example.com', '$2y$10$k1P8cazYvghcXNIK3P5uyuRq9Va0fNBInkseqo0WTcvTt3oe78Eqa', 'customer');

INSERT INTO categories (id, name, slug, is_coffee) VALUES (1, 'Coffee', 'coffee', 1);
INSERT INTO categories (id, name, slug, is_coffee) VALUES (2, 'Non-Coffee', 'non-coffee', 0);
INSERT INTO categories (id, name, slug, is_coffee) VALUES (3, 'Toast & Bakery', 'food', 0);

INSERT INTO products (id, category_id, name, description, price, image) VALUES (1, 1, 'Espresso', 'A bold, concentrated double shot pulled from our house-roasted Arabica blend. Rich crema, chocolatey finish.', 18000, 'espresso.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (2, 1, 'Americano', 'Double espresso topped with hot water for a smooth, clean cup. Available hot or iced.', 22000, 'americano.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (3, 1, 'Cappuccino', 'Equal parts espresso, steamed milk and velvety foam, dusted with cocoa.', 28000, 'cappuccino.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (4, 1, 'Caffè Latte', 'Espresso with silky steamed milk and a thin layer of microfoam. Our most-loved classic.', 28000, 'latte.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (5, 1, 'Kopi Susu Gula Aren', 'Iced espresso with fresh milk and Javanese palm sugar. Sweet, creamy and refreshing.', 25000, 'kopi-susu.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (6, 1, 'Caramel Macchiato', 'Vanilla-scented milk marked with espresso and finished with a caramel drizzle.', 32000, 'caramel-macchiato.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (7, 1, 'Cold Brew', 'Steeped for 18 hours for a naturally sweet, low-acid coffee served over ice.', 30000, 'cold-brew.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (8, 2, 'Matcha Latte', 'Stone-ground Japanese matcha whisked with steamed milk. Earthy, smooth, caffeine-light.', 30000, 'matcha.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (9, 2, 'Signature Chocolate', 'Dark Belgian chocolate melted into creamy milk. Hot or iced, always indulgent.', 27000, 'chocolate.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (10, 2, 'Lychee Lemon Tea', 'Brewed black tea with lychee, fresh lemon and a touch of honey, served iced.', 20000, 'lemon-tea.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (11, 3, 'Butter Sugar Toast', 'Thick-cut milk bread toasted golden with butter and a crunchy sugar crust.', 18000, 'butter-toast.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (12, 3, 'Cheese Toast', 'Grilled milk bread loaded with melted cheddar and mozzarella.', 22000, 'cheese-toast.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (13, 3, 'Choco Banana Toast', 'Toasted bread with chocolate spread, caramelised banana and a sprinkle of nuts.', 24000, 'choco-toast.svg');
INSERT INTO products (id, category_id, name, description, price, image) VALUES (14, 3, 'Butter Croissant', 'Flaky, buttery croissant baked fresh every morning.', 20000, 'croissant.svg');

INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (1, 4, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (1, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (2, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (2, 5, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (3, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (3, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (4, 4, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (4, 5, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (5, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (5, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (6, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (7, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (7, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (8, 4, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (8, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (9, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (9, 5, 3, 'Decent.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (10, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (10, 5, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (11, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (11, 5, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (12, 4, 4, 'Really good.');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (12, 5, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (13, 4, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (13, 5, 5, 'Love it!');
INSERT INTO ratings (product_id, user_id, rating, comment) VALUES (14, 4, 4, 'Really good.');

INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (1, 'KP-00001', NULL, 'Rina', '08123456789', 3, 'takeaway', NULL, 146000, 0, 146000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 13 DAY), INTERVAL 9 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (2, 'KP-00002', 5, 'Ayu Lestari', '08123456789', 2, 'takeaway', NULL, 66000, 6600, 59400, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 13 DAY), INTERVAL 13 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (3, 'KP-00003', NULL, 'Maya', '08123456789', 3, 'takeaway', NULL, 20000, 0, 20000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 13 DAY), INTERVAL 12 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (4, 'KP-00004', NULL, 'Rina', '08123456789', 3, 'takeaway', NULL, 136000, 0, 136000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 12 DAY), INTERVAL 8 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (5, 'KP-00005', 4, 'Budi Santoso', '08123456789', 3, 'dine-in', '7', 56000, 5600, 50400, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 12 DAY), INTERVAL 10 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (6, 'KP-00006', 5, 'Ayu Lestari', '08123456789', 2, 'takeaway', NULL, 60000, 0, 60000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 12 DAY), INTERVAL 16 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (7, 'KP-00007', NULL, 'Maya', '08123456789', 3, 'dine-in', '3', 110000, 0, 110000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 11 DAY), INTERVAL 9 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (8, 'KP-00008', NULL, 'Rina', '08123456789', 3, 'takeaway', NULL, 101000, 0, 101000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 10 DAY), INTERVAL 16 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (9, 'KP-00009', NULL, 'Tono', '08123456789', 2, 'takeaway', NULL, 88000, 0, 88000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 9 DAY), INTERVAL 14 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (10, 'KP-00010', 5, 'Ayu Lestari', '08123456789', 2, 'dine-in', '2', 22000, 2200, 19800, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 9 DAY), INTERVAL 15 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (11, 'KP-00011', 4, 'Budi Santoso', '08123456789', 2, 'dine-in', '6', 20000, 0, 20000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 9 DAY), INTERVAL 16 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (12, 'KP-00012', 4, 'Budi Santoso', '08123456789', 3, 'takeaway', NULL, 36000, 0, 36000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 8 DAY), INTERVAL 13 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (13, 'KP-00013', 4, 'Budi Santoso', '08123456789', 3, 'dine-in', '12', 80000, 0, 80000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 8 DAY), INTERVAL 10 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (14, 'KP-00014', 5, 'Ayu Lestari', '08123456789', 3, 'dine-in', '9', 100000, 4600, 95400, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 8 DAY), INTERVAL 19 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (15, 'KP-00015', NULL, 'Rina', '08123456789', 3, 'dine-in', '4', 108000, 0, 108000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 7 DAY), INTERVAL 17 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (16, 'KP-00016', NULL, 'Tono', '08123456789', 2, 'takeaway', NULL, 106000, 0, 106000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 7 DAY), INTERVAL 15 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (17, 'KP-00017', NULL, 'Guest', '08123456789', 3, 'dine-in', '6', 74000, 0, 74000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 6 DAY), INTERVAL 15 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (18, 'KP-00018', 4, 'Budi Santoso', '08123456789', 3, 'dine-in', '8', 92000, 3200, 88800, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 DAY), INTERVAL 20 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (19, 'KP-00019', 5, 'Ayu Lestari', '08123456789', 2, 'dine-in', '3', 80000, 3000, 77000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 DAY), INTERVAL 10 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (20, 'KP-00020', NULL, 'Tono', '08123456789', 3, 'dine-in', '9', 87000, 0, 87000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 DAY), INTERVAL 20 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (21, 'KP-00021', NULL, 'Tono', '08123456789', 3, 'takeaway', NULL, 20000, 0, 20000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 DAY), INTERVAL 8 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (22, 'KP-00022', 4, 'Budi Santoso', '08123456789', 3, 'takeaway', NULL, 57000, 3000, 54000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 DAY), INTERVAL 19 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (23, 'KP-00023', NULL, 'Maya', '08123456789', 3, 'dine-in', '3', 102000, 0, 102000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 17 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (24, 'KP-00024', 5, 'Ayu Lestari', '08123456789', 2, 'dine-in', '5', 86000, 6800, 79200, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 11 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (25, 'KP-00025', NULL, 'Maya', '08123456789', 3, 'takeaway', NULL, 106000, 0, 106000, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 1 DAY), INTERVAL 19 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (26, 'KP-00026', 5, 'Ayu Lestari', '08123456789', 2, 'takeaway', NULL, 133000, 10600, 122400, 'completed', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 0 DAY), INTERVAL 9 HOUR));
INSERT INTO orders (id, order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, subtotal, discount, total, status, created_at) VALUES (27, 'KP-00027', 4, 'Budi Santoso', '08123456789', 2, 'dine-in', '3', 56000, 5600, 50400, 'preparing', DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 0 DAY), INTERVAL 20 HOUR));

INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (1, 8, 'Matcha Latte', 30000, 0, 2, 60000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (1, 6, 'Caramel Macchiato', 32000, 0, 2, 64000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (1, 12, 'Cheese Toast', 22000, 0, 1, 22000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (2, 7, 'Cold Brew', 30000, 3000, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (2, 1, 'Espresso', 18000, 1800, 2, 32400);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (3, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (4, 10, 'Lychee Lemon Tea', 20000, 0, 2, 40000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (4, 11, 'Butter Sugar Toast', 18000, 0, 2, 36000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (4, 8, 'Matcha Latte', 30000, 0, 2, 60000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (5, 4, 'Caffè Latte', 28000, 2800, 2, 50400);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (6, 8, 'Matcha Latte', 30000, 0, 2, 60000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (7, 7, 'Cold Brew', 30000, 0, 2, 60000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (7, 6, 'Caramel Macchiato', 32000, 0, 1, 32000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (7, 11, 'Butter Sugar Toast', 18000, 0, 1, 18000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (8, 3, 'Cappuccino', 28000, 0, 1, 28000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (8, 5, 'Kopi Susu Gula Aren', 25000, 0, 1, 25000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (8, 13, 'Choco Banana Toast', 24000, 0, 2, 48000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (9, 14, 'Butter Croissant', 20000, 0, 2, 40000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (9, 13, 'Choco Banana Toast', 24000, 0, 2, 48000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (10, 2, 'Americano', 22000, 2200, 1, 19800);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (11, 10, 'Lychee Lemon Tea', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (12, 11, 'Butter Sugar Toast', 18000, 0, 2, 36000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (13, 8, 'Matcha Latte', 30000, 0, 2, 60000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (13, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (14, 3, 'Cappuccino', 28000, 2800, 1, 25200);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (14, 9, 'Signature Chocolate', 27000, 0, 2, 54000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (14, 1, 'Espresso', 18000, 1800, 1, 16200);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (15, 6, 'Caramel Macchiato', 32000, 0, 1, 32000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (15, 3, 'Cappuccino', 28000, 0, 2, 56000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (15, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (16, 8, 'Matcha Latte', 30000, 0, 1, 30000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (16, 6, 'Caramel Macchiato', 32000, 0, 1, 32000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (16, 12, 'Cheese Toast', 22000, 0, 2, 44000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (17, 6, 'Caramel Macchiato', 32000, 0, 1, 32000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (17, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (17, 2, 'Americano', 22000, 0, 1, 22000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (18, 6, 'Caramel Macchiato', 32000, 3200, 1, 28800);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (18, 13, 'Choco Banana Toast', 24000, 0, 1, 24000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (18, 11, 'Butter Sugar Toast', 18000, 0, 2, 36000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (19, 7, 'Cold Brew', 30000, 3000, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (19, 8, 'Matcha Latte', 30000, 0, 1, 30000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (19, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (20, 6, 'Caramel Macchiato', 32000, 0, 1, 32000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (20, 3, 'Cappuccino', 28000, 0, 1, 28000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (20, 9, 'Signature Chocolate', 27000, 0, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (21, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (22, 9, 'Signature Chocolate', 27000, 0, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (22, 7, 'Cold Brew', 30000, 3000, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (23, 3, 'Cappuccino', 28000, 0, 1, 28000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (23, 9, 'Signature Chocolate', 27000, 0, 2, 54000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (23, 14, 'Butter Croissant', 20000, 0, 1, 20000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (24, 1, 'Espresso', 18000, 1800, 2, 32400);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (24, 6, 'Caramel Macchiato', 32000, 3200, 1, 28800);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (24, 11, 'Butter Sugar Toast', 18000, 0, 1, 18000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (25, 1, 'Espresso', 18000, 0, 2, 36000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (25, 13, 'Choco Banana Toast', 24000, 0, 2, 48000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (25, 2, 'Americano', 22000, 0, 1, 22000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (26, 5, 'Kopi Susu Gula Aren', 25000, 2500, 2, 45000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (26, 9, 'Signature Chocolate', 27000, 0, 1, 27000);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (26, 4, 'Caffè Latte', 28000, 2800, 2, 50400);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (27, 4, 'Caffè Latte', 28000, 2800, 2, 50400);
