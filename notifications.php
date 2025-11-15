<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$user = current_user($pdo);
$count = cart_count();
$message = '';
if (!$user) { $notifications = []; $notifCount = 0; }
else {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_read') {
      mark_all_notifications_read($pdo, (int)$user['id']);
      $message = 'Notifications marked as read';
    } elseif ($action === 'mark_read') {
      $nid = (int)($_POST['id'] ?? 0);
      if ($nid > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$nid, (int)$user['id']]);
        $message = 'Notification marked as read';
      }
    }
  }
  $notifCount = unread_notifications_count($pdo, (int)$user['id']);
  $notifications = get_notifications($pdo, (int)$user['id']);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notifications</title>
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
        <?php if ($user): ?>
          <li class="nav-item"><span class="nav-link">Hi, <?php echo htmlspecialchars($user['name']); ?></span></li>
          <?php if ((int)$user['is_admin'] === 1): ?>
            <li class="nav-item"><a class="nav-link" href="admin.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_products.php">Products</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_users.php">Users</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link active" href="notifications.php">Notifications <?php if (!empty($notifCount)): ?><span class="badge bg-warning text-dark ms-1"><?php echo (int)$notifCount; ?></span><?php endif; ?></a></li>
          <li class="nav-item"><a class="nav-link" href="checkout.php">Checkout</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Notifications</h2>
    <?php if ($user): ?>
      <form method="post" class="d-inline">
        <input type="hidden" name="action" value="mark_all_read">
        <button class="btn btn-sm btn-outline-secondary">Mark all as read</button>
      </form>
    <?php endif; ?>
  </div>
  <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if (!$user): ?>
    <div class="alert alert-light border">Please <a href="login.php">log in</a> to view notifications.</div>
  <?php else: ?>
    <?php if (empty($notifications)): ?>
      <div class="alert alert-light border">No notifications.</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($notifications as $n): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div><?php echo htmlspecialchars($n['message']); ?> (Order #<?php echo (int)$n['order_id']; ?>)</div>
              <div class="text-muted small"><?php echo htmlspecialchars($n['created_at']); ?></div>
            </div>
            <div>
              <?php if ((int)$n['is_read'] === 0): ?>
                <span class="badge bg-warning text-dark me-2">New</span>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                  <button class="btn btn-sm btn-outline-secondary">Mark read</button>
                </form>
              <?php else: ?>
                <span class="badge bg-secondary">Read</span>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
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