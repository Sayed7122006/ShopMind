<?php
session_start();
require_once __DIR__ . '/fun/db_connection.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: account/login.php'); exit; }
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) $message = 'Enter a category name (maximum 100 characters).';
        else { $stmt = mysqli_prepare($conn, 'INSERT INTO categories (name) VALUES (?)'); mysqli_stmt_bind_param($stmt, 's', $name); $message = mysqli_stmt_execute($stmt) ? 'Category added.' : 'Could not add category. It may already exist.'; mysqli_stmt_close($stmt); }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0); $stmt = mysqli_prepare($conn, 'DELETE FROM categories WHERE id = ?'); mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); $message = 'Category deleted.';
    }
}
$result = mysqli_query($conn, 'SELECT id, name, created_at FROM categories ORDER BY name');
include __DIR__ . '/includes/header.php'; include __DIR__ . '/includes/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column"><div id="content"><?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container-fluid py-4"><h1 class="h3 mb-4 text-gray-800">Categories Management</h1>
<?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="alert alert-warning">Categories are currently managed in this list. The existing products table stores category as text; this page does not yet link category records to products.</div>
<div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Add Category</h6></div><div class="card-body"><form method="post" class="form-inline"><input type="hidden" name="action" value="add"><label class="sr-only" for="categoryName">Category name</label><input id="categoryName" name="name" class="form-control mr-2 mb-2" maxlength="100" required placeholder="Category name"><button class="btn btn-primary mb-2" type="submit">Add Category</button></form></div></div>
<div class="card shadow"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Categories</h6></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>ID</th><th>Name</th><th>Created</th><th>Action</th></tr></thead><tbody><?php if ($result && mysqli_num_rows($result)): while ($row = mysqli_fetch_assoc($result)): ?><tr><td><?= (int)$row['id'] ?></td><td><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['created_at'] ?? '') ?></td><td><form method="post" onsubmit="return confirm('Delete this category?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="text-center">No categories yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div></div><?php include __DIR__ . '/includes/footer.php'; ?></div>
