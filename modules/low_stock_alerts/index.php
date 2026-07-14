<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$lowStockProducts = $conn->query('SELECT id, product_name, current_stock, reorder_level FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC')->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Low Stock Alerts</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Products Below Reorder Level</h3></div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="lowStockTable">
          <thead><tr><th>Product</th><th>Current Stock</th><th>Reorder Level</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($lowStockProducts as $product): ?>
              <tr>
                <td><?= htmlspecialchars($product['product_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)($product['current_stock'] ?? 0) ?></td>
                <td><?= (int)($product['reorder_level'] ?? 0) ?></td>
                <td><span class="badge badge-warning">Low Stock</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#lowStockTable').DataTable({responsive:true, autoWidth:false}); });</script>
