<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$expenses = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Expenses</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Business Expenses</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Expense</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="expensesTable">
          <thead><tr><th>Code</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Vendor</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($expenses as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['expense_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['expense_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['expense_category'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['expense_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['vendor'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
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
<script>$(function(){ $('#expensesTable').DataTable({responsive:true, autoWidth:false}); });</script>
