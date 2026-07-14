<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$deliveries = $conn->query("SELECT d.*, p.po_code, s.supplier_name FROM deliveries d LEFT JOIN purchase_orders p ON p.id = d.purchase_order_id LEFT JOIN suppliers s ON s.id = d.supplier_id ORDER BY d.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Deliveries</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Goods Received</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Delivery</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="deliveriesTable">
          <thead><tr><th>Ref</th><th>Purchase Order</th><th>Supplier</th><th>Delivery Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($deliveries as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['delivery_ref'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['po_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['delivery_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <a href="view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                  <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                  <a href="delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this delivery?');">Delete</a>
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
<script>$(function(){ $('#deliveriesTable').DataTable({responsive:true, autoWidth:false}); });</script>
