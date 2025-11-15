<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : '';
  if ($action === 'add') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
      $_SESSION['cart'][$id]++;
    }
  } elseif ($action === 'inc') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
      if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
      $_SESSION['cart'][$id]++;
    }
  } elseif ($action === 'dec') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0 && isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = max(0, (int)$_SESSION['cart'][$id] - 1);
      if ($_SESSION['cart'][$id] === 0) unset($_SESSION['cart'][$id]);
    }
  } elseif ($action === 'update') {
    $qty = isset($_POST['qty']) ? $_POST['qty'] : [];
    foreach ($qty as $id => $q) {
      $id = (int)$id;
      $q = max(0, (int)$q);
      if ($q === 0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $q;
    }
  } elseif ($action === 'remove') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    unset($_SESSION['cart'][$id]);
  } elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
  }
  header('Location: cart.php');
  exit;
}
$items = get_cart_items_detailed($pdo);
$count = cart_count();
$total = cart_total($pdo);
$user = current_user($pdo);
$notifCount = $user ? unread_notifications_count($pdo, (int)$user['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Cart</title>
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
        <li class="nav-item"><a class="nav-link active" href="cart.php">Cart <span class="badge bg-primary ms-1"><?php echo $count; ?></span></a></li>
        <?php if ($user): ?>
          <li class="nav-item"><span class="nav-link">Hi, <?php echo htmlspecialchars($user['name']); ?></span></li>
          <?php if ((int)$user['is_admin'] === 1): ?>
            <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="notifications.php">Notifications <?php if ($notifCount > 0): ?><span class="badge bg-warning text-dark ms-1"><?php echo $notifCount; ?></span><?php endif; ?></a></li>
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
  <h2 class="mb-4">Your Cart</h2>
  <?php if (empty($items)): ?>
    <div class="alert alert-info">Your cart is empty.</div>
    <a href="index.php" class="btn btn-primary">Continue Shopping</a>
  <?php else: ?>
    <form method="post" class="table-responsive">
      <input type="hidden" name="action" value="update">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th width="140">Quantity</th>
            <th class="text-end">Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <img src="<?php echo htmlspecialchars($it['image']); ?>" alt="" class="me-3 rounded" style="width:64px;height:64px;object-fit:cover;">
                  <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($it['name']); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($it['description']); ?></div>
                  </div>
                </div>
              </td>
              <td>$<?php echo number_format($it['price'], 2); ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="dec">
                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">–</button>
                  </form>
                  <input type="number" min="0" class="form-control form-control-sm" name="qty[<?php echo (int)$it['id']; ?>]" value="<?php echo (int)$it['quantity']; ?>" style="width:80px">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="inc">
                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">+</button>
                  </form>
                </div>
              </td>
              <td class="text-end">$<?php echo number_format($it['price'] * $it['quantity'], 2); ?></td>
              <td class="text-end">
                <form method="post">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                  <button class="btn btn-outline-danger btn-sm">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="clear">
            <button class="btn btn-outline-secondary">Clear Cart</button>
          </form>
          <a href="index.php" class="btn btn-link">Continue Shopping</a>
        </div>
        <div class="h5 mb-0">Total: $<?php echo number_format($total, 2); ?></div>
      </div>
      <div class="text-end mt-3">
        <button class="btn btn-primary">Update Cart</button>
        <a href="checkout.php" class="btn btn-success">Checkout</a>
      </div>
    </form>
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