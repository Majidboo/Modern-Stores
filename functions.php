<?php
function get_products(PDO $pdo): array {
  $stmt = $pdo->query('SELECT id, name, description, price, image FROM products ORDER BY id');
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_product(PDO $pdo, int $id): ?array {
  $stmt = $pdo->prepare('SELECT id, name, description, price, image FROM products WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function cart_count(): int {
  if (!isset($_SESSION['cart'])) return 0;
  $c = 0;
  foreach ($_SESSION['cart'] as $q) $c += (int)$q;
  return $c;
}

function get_cart_items_detailed(PDO $pdo): array {
  if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) return [];
  $ids = array_keys($_SESSION['cart']);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare('SELECT id, name, description, price, image FROM products WHERE id IN (' . $placeholders . ')');
  $stmt->execute($ids);
  $map = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) $map[$p['id']] = $p;
  $items = [];
  foreach ($_SESSION['cart'] as $id => $q) {
    if (isset($map[$id])) {
      $p = $map[$id];
      $p['quantity'] = (int)$q;
      $items[] = $p;
    }
  }
  usort($items, function($a,$b){return $a['id'] <=> $b['id'];});
  return $items;
}

function cart_total(PDO $pdo): float {
  $sum = 0.0;
  foreach (get_cart_items_detailed($pdo) as $it) $sum += $it['price'] * $it['quantity'];
  return $sum;
}

function create_order(PDO $pdo, string $name, string $email, string $address, array $cart, ?int $userId = null): int {
  $pdo->beginTransaction();
  $total = 0.0;
  foreach ($cart as $id => $q) {
    $p = get_product($pdo, (int)$id);
    if ($p) $total += $p['price'] * (int)$q;
  }
  $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, email, address, total, created_at) VALUES (?, ?, ?, ?, ?, ?)');
  $stmt->execute([$userId, $name, $email, $address, $total, date('Y-m-d H:i:s')]);
  $orderId = (int)$pdo->lastInsertId();
  $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
  foreach ($cart as $id => $q) {
    $p = get_product($pdo, (int)$id);
    if ($p) $itemStmt->execute([$orderId, (int)$id, (int)$q, $p['price']]);
  }
  $pdo->commit();
  return $orderId;
}

function find_user_by_email(PDO $pdo, string $email): ?array {
  $stmt = $pdo->prepare('SELECT id, name, email, password_hash, address, is_admin FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function find_user(PDO $pdo, int $id): ?array {
  $stmt = $pdo->prepare('SELECT id, name, email, password_hash, address, is_admin FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function current_user(PDO $pdo): ?array {
  if (!isset($_SESSION['user_id'])) return null;
  return find_user($pdo, (int)$_SESSION['user_id']);
}

function register_user(PDO $pdo, string $name, string $email, string $password, string $address): array {
  $errors = [];
  if ($name === '') $errors[] = 'Name is required';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
  if (!empty(find_user_by_email($pdo, $email))) $errors[] = 'Email already registered';
  if ($errors) return ['ok' => false, 'errors' => $errors];
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $adminEmail = getenv('ADMIN_EMAIL') ?: '';
  $isAdmin = ($adminEmail !== '' && strcasecmp($adminEmail, $email) === 0) ? 1 : 0;
  $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, address, is_admin) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$name, $email, $hash, $address, $isAdmin]);
  $id = (int)$pdo->lastInsertId();
  $_SESSION['user_id'] = $id;
  return ['ok' => true, 'user_id' => $id];
}

function authenticate_user(PDO $pdo, string $email, string $password): array {
  $u = find_user_by_email($pdo, $email);
  if (!$u || !password_verify($password, $u['password_hash'])) {
    return ['ok' => false, 'errors' => ['Invalid credentials']];
  }
  $_SESSION['user_id'] = (int)$u['id'];
  return ['ok' => true, 'user_id' => (int)$u['id']];
}

function update_user_profile(PDO $pdo, int $id, string $name, string $email, string $address): void {
  $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, address = ? WHERE id = ?');
  $stmt->execute([$name, $email, $address, $id]);
}

function get_notifications(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT id, order_id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function unread_notifications_count(PDO $pdo, int $userId): int {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
  $stmt->execute([$userId]);
  return (int)$stmt->fetchColumn();
}

function mark_all_notifications_read(PDO $pdo, int $userId): void {
  $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
  $stmt->execute([$userId]);
}