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
  if ($action === 'toggle_admin') {
    $id = (int)($_POST['id'] ?? 0);
    $isAdmin = (int)($_POST['is_admin'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
      $stmt->execute([$isAdmin, $id]);
      $message = 'Updated admin flag';
    }
  }
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q !== '') {
  $like = '%' . $q . '%';
  $stmt = $pdo->prepare('SELECT id, name, email, address, is_admin FROM users WHERE name LIKE ? OR email LIKE ? OR address LIKE ? ORDER BY id');
  $stmt->execute([$like, $like, $like]);
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $users = $pdo->query('SELECT id, name, email, address, is_admin FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Users</title>
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
        <li class="nav-item"><a class="nav-link" href="admin_products.php">Products</a></li>
        <li class="nav-item"><a class="nav-link active" href="admin_users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Users</h2>
    <form method="get" class="d-flex gap-2" action="admin_users.php">
      <input class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name, email or address">
      <button class="btn btn-outline-secondary" type="submit">Search</button>
      <?php if ($q !== ''): ?>
        <a href="admin_users.php" class="btn btn-outline-dark" role="button">Clear</a>
      <?php endif; ?>
    </form>
  </div>
  <?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Address</th><th>Admin</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo (int)$u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td class="text-muted small" style="max-width:300px"><?php echo nl2br(htmlspecialchars($u['address'])); ?></td>
            <td><?php echo (int)$u['is_admin'] === 1 ? 'Yes' : 'No'; ?></td>
            <td class="text-end">
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="toggle_admin">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                <input type="hidden" name="is_admin" value="<?php echo (int)$u['is_admin'] === 1 ? 0 : 1; ?>">
                <button class="btn btn-sm btn-outline-primary"><?php echo (int)$u['is_admin'] === 1 ? 'Demote' : 'Promote'; ?></button>
              </form>
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