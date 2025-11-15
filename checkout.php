<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$items = get_cart_items_detailed($pdo);
$count = cart_count();
$total = cart_total($pdo);
$user = current_user($pdo);
if (!$user) {
  header('Location: login.php?redirect=checkout.php');
  exit;
}
$success = false;
$orderId = null;
$errors = [];
$nameVal = $user ? $user['name'] : '';
$emailVal = $user ? $user['email'] : '';
$addressVal = $user ? $user['address'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $address = isset($_POST['address']) ? trim($_POST['address']) : '';
  if ($name === '') $errors[] = 'Name is required';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if ($address === '') $errors[] = 'Address is required';
  if (empty($items)) $errors[] = 'Cart is empty';
  if (!$errors) {
    update_user_profile($pdo, (int)$user['id'], $name, $email, $address);
    $orderId = create_order($pdo, $name, $email, $address, $_SESSION['cart'], (int)$user['id']);
    $_SESSION['cart'] = [];
    $success = true;
    $nameVal = $name; $emailVal = $email; $addressVal = $address;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout</title>
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
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-5">
  <h2 class="mb-4">Checkout</h2>
  <?php if ($success): ?>
    <div class="alert alert-success">Order placed successfully. Your order ID is <?php echo (int)$orderId; ?>.</div>
    <a class="btn btn-primary" href="index.php">Back to Store</a>
  <?php elseif (empty($items)): ?>
    <div class="alert alert-info">Your cart is empty.</div>
    <a class="btn btn-primary" href="index.php">Continue Shopping</a>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
              </div>
            <?php endif; ?>
            <form method="post">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($nameVal); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emailVal); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($addressVal); ?></textarea>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <a href="cart.php" class="btn btn-outline-secondary">Back to Cart</a>
                <button class="btn btn-success">Place Order</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">Order Summary</div>
          <div class="card-body">
            <?php foreach ($items as $it): ?>
              <div class="d-flex justify-content-between mb-2">
                <div><?php echo htmlspecialchars($it['name']); ?> × <?php echo (int)$it['quantity']; ?></div>
                <div>$<?php echo number_format($it['price'] * $it['quantity'], 2); ?></div>
              </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between fw-semibold">
              <div>Total</div>
              <div>$<?php echo number_format($total, 2); ?></div>
            </div>
            <hr>
            <div class="text-muted small">
              <div>Client: <?php echo htmlspecialchars($nameVal); ?></div>
              <div>Email: <?php echo htmlspecialchars($emailVal); ?></div>
              <div>Address: <?php echo nl2br(htmlspecialchars($addressVal)); ?></div>
            </div>
          </div>
        </div>
      </div>
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