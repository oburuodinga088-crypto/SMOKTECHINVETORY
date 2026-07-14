<?php
require '../../includes/auth.php';
requireLogin();

$categories = $conn->query('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.category_name, c.description, c.created_at ORDER BY c.id DESC')->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>Categories</h1></div><div class="col-sm-6 text-right"><a href="add.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> New Category</a></div></div></div></section>
  <section class="content"><div class="container-fluid"><div class="card card-primary"><div class="card-body">
    <?php if (($_GET['status'] ?? '') === 'in_use'): ?><div class="alert alert-warning">A category with products cannot be deleted.</div><?php endif; ?>
    <table id="categoryTable" class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Category</th><th>Description</th><th>Products</th><th>Created</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($categories as $row): ?><tr><td><?= (int)$row['id'] ?></td><td><?= htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$row['product_count'] ?></td><td><?= htmlspecialchars(date('d M Y', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td><td><a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> <a href="delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this empty category?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach; ?>
    </tbody></table>
  </div></div></div></section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function () { $('#categoryTable').DataTable({ responsive: true, autoWidth: false }); });</script>
