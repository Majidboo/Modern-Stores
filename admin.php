<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$user = current_user($pdo);
if (!$user || (int)$user['is_admin'] !== 1) { http_response_code(403); echo 'Access denied'; exit; }
$count = cart_count();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'update_order') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($id > 0 && $name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $address !== '') {
      $stmt = $pdo->prepare('UPDATE orders SET customer_name = ?, email = ?, address = ? WHERE id = ?');
      $stmt->execute([$name, $email, $address, $id]);
      $message = 'Order updated';
    } else { $message = 'Invalid order data'; }
  } elseif ($action === 'delete_order') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
      $stmt->execute([$id]);
      $message = 'Order deleted';
    }
  } elseif ($action === 'mark_paid') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
      $stmt->execute(['paid', $id]);
      $message = 'Order marked as paid';
      $su = $pdo->prepare('SELECT user_id FROM orders WHERE id = ?');
      $su->execute([$id]);
      $uid = (int)$su->fetchColumn();
      if ($uid > 0) {
        $ins = $pdo->prepare('INSERT INTO notifications (user_id, order_id, message, is_read, created_at) VALUES (?, ?, ?, 0, ?)');
        $ins->execute([$uid, $id, 'Your order has been marked as paid.', date('Y-m-d H:i:s')]);
      }
    }
  } elseif ($action === 'mark_done') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
      $stmt->execute(['done', $id]);
      $message = 'Order marked as done';
      $su = $pdo->prepare('SELECT user_id FROM orders WHERE id = ?');
      $su->execute([$id]);
      $uid = (int)$su->fetchColumn();
      if ($uid > 0) {
        $ins = $pdo->prepare('INSERT INTO notifications (user_id, order_id, message, is_read, created_at) VALUES (?, ?, ?, 0, ?)');
        $ins->execute([$uid, $id, 'Your order has been delivered.', date('Y-m-d H:i:s')]);
      }
    }
  }
}

$ordersStmt = $pdo->query('SELECT o.id, o.user_id, o.customer_name, o.email, o.address, o.total, o.created_at, o.status, u.name AS account_name, u.email AS account_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$itemsStmt = $pdo->query('SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON p.id = oi.product_id ORDER BY oi.order_id, oi.id');
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
$itemsByOrder = [];
foreach ($items as $it) { $itemsByOrder[$it['order_id']][] = $it; }
$incomeAll = (float)($pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn());
$incomePaid = (float)($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','done')")->fetchColumn());
$incomePending = (float)($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'pending'")->fetchColumn());
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">Modern Store</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="cart.php">Cart <span class="badge bg-primary ms-1"><?php echo $count; ?></span></a></li>
        <li class="nav-item"><a class="nav-link active" href="admin.php">Orders</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Orders</h2>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-semibold">Total Income</div>
          <div class="h4 mb-0">$<?php echo number_format($incomeAll, 2); ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-semibold">Paid/Done Income</div>
          <div class="h4 mb-0">$<?php echo number_format($incomePaid, 2); ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-semibold">Pending Value</div>
          <div class="h4 mb-0">$<?php echo number_format($incomePending, 2); ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <?php if (empty($orders)): ?>
    <div class="alert alert-info">No orders yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Email</th>
            <th>Address</th>
            <th>Status</th>
            <th class="text-end">Total</th>
            <th>Created</th>
            <th>Account</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?php echo (int)$o['id']; ?></td>
              <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($o['email']); ?></td>
              <td><?php echo nl2br(htmlspecialchars($o['address'])); ?></td>
              <td>
                <?php $st = isset($o['status']) ? $o['status'] : 'pending'; ?>
                <?php $cls = ($st === 'done') ? 'bg-success' : (($st === 'paid') ? 'bg-warning text-dark' : 'bg-secondary'); ?>
                <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($st); ?></span>
              </td>
              <td class="text-end">$<?php echo number_format($o['total'], 2); ?></td>
              <td><?php echo htmlspecialchars($o['created_at']); ?></td>
              <td><?php echo $o['account_name'] ? htmlspecialchars($o['account_name']) : '—'; ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#order-<?php echo (int)$o['id']; ?>">Details</button>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo (int)$o['id']; ?>">Edit</button>
                <?php if (($o['status'] ?? 'pending') === 'pending'): ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="mark_paid">
                    <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                    <button class="btn btn-sm btn-outline-success">Mark Paid</button>
                  </form>
                <?php elseif (($o['status'] ?? 'pending') === 'paid'): ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="mark_done">
                    <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                    <button class="btn btn-sm btn-outline-success">Mark Done</button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-sm btn-success" disabled>Done</button>
                <?php endif; ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="delete_order">
                  <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
          </tr>
            <tr class="collapse" id="edit-<?php echo (int)$o['id']; ?>">
              <td colspan="8">
                <div class="card">
                  <div class="card-body">
                    <form method="post">
                      <input type="hidden" name="action" value="update_order">
                      <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                      <div class="row g-3">
                        <div class="col-md-4"><input class="form-control" name="customer_name" value="<?php echo htmlspecialchars($o['customer_name']); ?>" required></div>
                        <div class="col-md-4"><input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($o['email']); ?>" required></div>
                        <div class="col-md-4"><textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($o['address']); ?></textarea></div>
                      </div>
                      <div class="text-end mt-3"><button class="btn btn-success">Save</button></div>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="collapse" id="order-<?php echo (int)$o['id']; ?>">
              <td colspan="8">
                <?php $list = $itemsByOrder[$o['id']] ?? []; ?>
                <?php if (empty($list)): ?>
                  <div class="text-muted">No items.</div>
                <?php else: ?>
                  <div class="row g-3">
                    <?php foreach ($list as $it): ?>
                      <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                          <img src="<?php echo htmlspecialchars($it['image']); ?>" class="card-img-top" alt="">
                          <div class="card-body">
                            <div class="fw-semibold mb-1"><?php echo htmlspecialchars($it['name']); ?></div>
                            <div class="text-muted small">Qty: <?php echo (int)$it['quantity']; ?></div>
                            <div class="text-muted small">Price: $<?php echo number_format($it['price'], 2); ?></div>
                            <div class="mt-2">Subtotal: $<?php echo number_format($it['price'] * $it['quantity'], 2); ?></div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<footer class="py-4 bg-dark text-white-50">
  <div class="container text-center">
    <small>© <?php echo date('Y'); ?> Modern Store</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>