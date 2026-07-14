<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$products = $conn->query('SELECT id, product_name, buying_price, selling_price, current_stock FROM products ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
$inventoryValue = 0;
foreach ($products as $product) {
    $inventoryValue += ((float)($product['buying_price'] ?? 0) * (int)($product['current_stock'] ?? 0));
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Inventory Valuation</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Stock Value Summary</h3></div>
      <div class="card-body">
        <div class="alert alert-info"><strong>Total Inventory Value:</strong> KSh <?= number_format($inventoryValue, 2) ?></div>
        <table class="table table-bordered table-striped" id="inventoryTable">
          <thead><tr><th>Product</th><th>Stock</th><th>Buying Price</th><th>Stock Value</th><th>Selling Price</th></tr></thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <?php $stockValue = ((float)($product['buying_price'] ?? 0) * (int)($product['current_stock'] ?? 0)); ?>
              <tr>
                <td><?= htmlspecialchars($product['product_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)($product['current_stock'] ?? 0) ?></td>
                <td>KSh <?= number_format((float)($product['buying_price'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format($stockValue, 2) ?></td>
                <td>KSh <?= number_format((float)($product['selling_price'] ?? 0), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#inventoryTable').DataTable({responsive:true, autoWidth:false}); });</script>
