<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$totals = [];
$totals['sales'] = (float) $conn->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales')->fetchColumn();
$totals['expenses'] = (float) $conn->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();
$totals['purchases'] = (float) $conn->query('SELECT COALESCE(SUM(total_amount), 0) FROM purchases')->fetchColumn();
$totals['supplier_payments'] = (float) $conn->query('SELECT COALESCE(SUM(amount), 0) FROM supplier_payments')->fetchColumn();
$totals['cash_income'] = (float) $conn->query("SELECT COALESCE(SUM(amount), 0) FROM cash_book_entries WHERE entry_type = 'income'")->fetchColumn();
$totals['cash_expense'] = (float) $conn->query("SELECT COALESCE(SUM(amount), 0) FROM cash_book_entries WHERE entry_type = 'expense'")->fetchColumn();
$totals['profit'] = $totals['sales'] - $totals['expenses'] - $totals['purchases'];

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>ERP Reports</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Business Summary</h3></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= number_format($totals['sales'], 2) ?></h3><p>Total Sales</p></div></div></div>
          <div class="col-md-4"><div class="small-box bg-danger"><div class="inner"><h3>KSh <?= number_format($totals['expenses'], 2) ?></h3><p>Total Expenses</p></div></div></div>
          <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>KSh <?= number_format($totals['profit'], 2) ?></h3><p>Estimated Profit</p></div></div></div>
        </div>

        <div class="row mt-4">
          <div class="col-md-6">
            <div class="card card-outline card-primary">
              <div class="card-header"><h3 class="card-title">Procurement</h3></div>
              <div class="card-body">
                <p><strong>Purchases:</strong> KSh <?= number_format($totals['purchases'], 2) ?></p>
                <p><strong>Supplier Payments:</strong> KSh <?= number_format($totals['supplier_payments'], 2) ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-outline card-secondary">
              <div class="card-header"><h3 class="card-title">Cash Flow</h3></div>
              <div class="card-body">
                <p><strong>Cash Income:</strong> KSh <?= number_format($totals['cash_income'], 2) ?></p>
                <p><strong>Cash Expense:</strong> KSh <?= number_format($totals['cash_expense'], 2) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
