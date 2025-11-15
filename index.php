<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$products = get_products($pdo);
$count = cart_count();
$user = current_user($pdo);
$notifCount = $user ? unread_notifications_count($pdo, (int)$user['id']) : 0;
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modern Store</title>
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

<section class="hero py-5 text-center text-white">
  <div class="container">
    <h1 class="display-5 fw-bold">Shop Beautifully</h1>
    <p class="lead">Curated products with a clean, aesthetic design.</p>
    <a href="#products" class="btn btn-light btn-lg">Browse Products</a>
    <?php if (!$user): ?>
      <a href="register.php" class="btn btn-outline-light btn-lg ms-2">Create Account</a>
    <?php endif; ?>
    <p class="mt-3 text-white-50">Community size: <?php echo number_format($userCount); ?> users</p>
  </div>
</section>


<div class="container my-5" id="products">
  <div class="row g-4">
    <?php foreach ($products as $p): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card product-card h-100">
          <img src="<?php echo htmlspecialchars($p['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
            <p class="card-text text-muted small"><?php echo htmlspecialchars($p['description']); ?></p>
            <div class="mt-auto d-flex align-items-center justify-content-between">
              <span class="price h6 mb-0">$<?php echo number_format($p['price'], 2); ?></span>
              <form method="post" action="cart.php" class="ms-2">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-primary btn-sm">Add to Cart</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<footer class="py-4 bg-dark text-white-50">
  <div class="container text-center">
    <small>© <?php echo date('Y'); ?> Modern Store</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>