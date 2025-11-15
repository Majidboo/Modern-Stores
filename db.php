<?php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'store';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);

$pdoServer = new PDO('mysql:host=' . $dbHost . ';port=' . $dbPort . ';charset=utf8mb4', $dbUser, $dbPass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$pdo = new PDO('mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4', $dbUser, $dbPass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  address TEXT NOT NULL DEFAULT "",
  is_admin TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB');

$pdo->exec('CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(512) NOT NULL
) ENGINE=InnoDB');

$pdo->exec('CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  customer_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  address TEXT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB');

$pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB');

$hasAdminCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'")->fetchColumn();
if ($hasAdminCol === false) {
  try { $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0'); } catch (Exception $e) {}
}

$hasOrderStatus = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'")->fetchColumn();
if ($hasOrderStatus === false) {
  try { $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending'"); } catch (Exception $e) {}
}

$pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_notifications_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB');

$countProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($countProducts === 0) {
  $stmt = $pdo->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
  $data = [
    ['Vintage Camera','Minimal retro style camera for creators.',129.00,'https://picsum.photos/seed/camera/600/400'],
    ['Leather Wallet','Premium leather wallet with slim profile.',49.00,'https://picsum.photos/seed/wallet/600/400'],
    ['Wireless Headphones','Comfortable, crisp sound, long battery.',199.00,'https://picsum.photos/seed/headphones/600/400'],
    ['Smart Lamp','Warm light with touch controls.',79.00,'https://picsum.photos/seed/lamp/600/400'],
    ['Ceramic Mug','Matte finish artisan mug.',18.50,'https://picsum.photos/seed/mug/600/400'],
    ['Canvas Backpack','Durable everyday carry.',89.00,'https://picsum.photos/seed/backpack/600/400'],
    ['Desk Plant','Low-maintenance greenery.',25.00,'https://picsum.photos/seed/plant/600/400'],
    ['Sketchbook','Thick paper, lay-flat binding.',22.00,'https://picsum.photos/seed/sketchbook/600/400']
  ];
  foreach ($data as $d) $stmt->execute($d);
}