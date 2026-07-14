<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$quotations = $conn->query("SELECT q.*, c.customer_name FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id ORDER BY q.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Quotations</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Customer Quotations</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Quotation</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="quotationsTable">
          <thead><tr><th>Code</th><th>Customer</th><th>Date</th><th>Valid Until</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($quotations as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['quotation_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['quotation_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['valid_until'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#quotationsTable').DataTable({responsive:true, autoWidth:false}); });</script>
