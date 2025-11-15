<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$user = current_user($pdo);
if (!$user || (int)$user['is_admin'] !== 1) { http_response_code(403); echo 'Access denied'; exit; }
$count = cart_count();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : '';
  if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    if ($name !== '' && $desc !== '' && $price > 0 && $image !== '') {
      $stmt = $pdo->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
      $stmt->execute([$name, $desc, $price, $image]);
      $message = 'Product created';
    } else {
      $message = 'Please fill all fields correctly';
    }
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    if ($id > 0 && $name !== '' && $desc !== '' && $price > 0 && $image !== '') {
      $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?');
      $stmt->execute([$name, $desc, $price, $image, $id]);
      $message = 'Product updated';
    } else { $message = 'Update failed: invalid data'; }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $used = (int)$pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?')->execute([$id]) ?: 0;
      $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
      $stmtCheck->execute([$id]);
      $used = (int)$stmtCheck->fetchColumn();
      if ($used > 0) {
        $message = 'Cannot delete: product used in orders';
      } else {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Product deleted';
      }
    }
  }
}

$products = get_products($pdo);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Products</title>
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
        <li class="nav-item"><a class="nav-link" href="admin.php">Orders</a></li>
        <li class="nav-item"><a class="nav-link active" href="admin_products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Products</h2>
    <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createForm">Add Product</button>
  </div>
  <?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <div class="collapse mb-4" id="createForm">
    <div class="card">
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="row g-3">
            <div class="col-md-6"><input class="form-control" name="name" placeholder="Name" required></div>
            <div class="col-md-3"><input class="form-control" name="price" type="number" step="0.01" min="0" placeholder="Price" required></div>
            <div class="col-md-3"><input class="form-control" name="image" placeholder="Image URL" required></div>
            <div class="col-12"><textarea class="form-control" name="description" rows="2" placeholder="Description" required></textarea></div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-success">Create</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo (int)$p['id']; ?></td>
            <td><img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" style="width:60px;height:60px;object-fit:cover" class="rounded"></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>$<?php echo number_format($p['price'],2); ?></td>
            <td class="text-muted small" style="max-width:300px"><?php echo htmlspecialchars($p['description']); ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo (int)$p['id']; ?>">Edit</button>
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
          <tr class="collapse" id="edit-<?php echo (int)$p['id']; ?>">
            <td colspan="6">
              <div class="card">
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                    <div class="row g-3">
                      <div class="col-md-6"><input class="form-control" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required></div>
                      <div class="col-md-3"><input class="form-control" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($p['price']); ?>" required></div>
                      <div class="col-md-3"><input class="form-control" name="image" value="<?php echo htmlspecialchars($p['image']); ?>" required></div>
                      <div class="col-12"><textarea class="form-control" name="description" rows="2" required><?php echo htmlspecialchars($p['description']); ?></textarea></div>
                    </div>
                    <div class="text-end mt-3"><button class="btn btn-success">Save</button></div>
                  </form>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
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