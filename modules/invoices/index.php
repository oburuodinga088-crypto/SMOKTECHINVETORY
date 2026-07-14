<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$invoices = $conn->query("SELECT i.*, c.customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id ORDER BY i.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Invoices</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Customer Invoices</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Invoice</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="invoicesTable">
          <thead><tr><th>Code</th><th>Customer</th><th>Date</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($invoices as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['invoice_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['invoice_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($row['amount_paid'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($row['balance'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
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
<script>$(function(){ $('#invoicesTable').DataTable({responsive:true, autoWidth:false}); });</script>
