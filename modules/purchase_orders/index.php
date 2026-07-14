<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$purchaseOrders = $conn->query("SELECT p.*, s.supplier_name FROM purchase_orders p LEFT JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Purchase Orders</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Supplier Orders</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Purchase Order</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="purchaseOrdersTable">
          <thead><tr><th>Code</th><th>Supplier</th><th>Order Date</th><th>Expected Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($purchaseOrders as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['po_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['order_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['expected_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <a href="view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                  <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                  <a href="delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this purchase order?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#purchaseOrdersTable').DataTable({responsive:true, autoWidth:false}); });</script>
