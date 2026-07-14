<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$conn = getDB();

$balances = $conn->query(
    "SELECT account_name, SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END) AS debit_total,
            SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) AS credit_total
     FROM account_ledger_entries
     GROUP BY account_name
     ORDER BY account_name"
)->fetchAll(PDO::FETCH_ASSOC);

$assets = 0;
$liabilities = 0;
$equity = 0;
$income = 0;
$expenses = 0;

foreach ($balances as $row) {
    $name = strtolower((string)($row['account_name'] ?? ''));
    if (strpos($name, 'cash') !== false || strpos($name, 'inventory') !== false || strpos($name, 'receivable') !== false || strpos($name, 'asset') !== false) {
        $assets += (float)($row['debit_total'] ?? 0) - (float)($row['credit_total'] ?? 0);
    } elseif (strpos($name, 'payable') !== false || strpos($name, 'loan') !== false || strpos($name, 'liability') !== false) {
        $liabilities += (float)($row['credit_total'] ?? 0) - (float)($row['debit_total'] ?? 0);
    } elseif (strpos($name, 'capital') !== false || strpos($name, 'equity') !== false || strpos($name, 'retained') !== false) {
        $equity += (float)($row['credit_total'] ?? 0) - (float)($row['debit_total'] ?? 0);
    } elseif (strpos($name, 'revenue') !== false || strpos($name, 'sales') !== false || strpos($name, 'income') !== false) {
        $income += (float)($row['credit_total'] ?? 0) - (float)($row['debit_total'] ?? 0);
    } elseif (strpos($name, 'expense') !== false || strpos($name, 'rent') !== false || strpos($name, 'salary') !== false || strpos($name, 'cost') !== false) {
        $expenses += (float)($row['debit_total'] ?? 0) - (float)($row['credit_total'] ?? 0);
    }
}

$netIncome = $income - $expenses;

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Financial Statement</h1></section>
  <section class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Balance Sheet</h3></div>
          <div class="card-body">
            <p><strong>Assets:</strong> KSh <?= number_format($assets, 2) ?></p>
            <p><strong>Liabilities:</strong> KSh <?= number_format($liabilities, 2) ?></p>
            <p><strong>Equity:</strong> KSh <?= number_format($equity, 2) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Profit & Loss</h3></div>
          <div class="card-body">
            <p><strong>Income:</strong> KSh <?= number_format($income, 2) ?></p>
            <p><strong>Expenses:</strong> KSh <?= number_format($expenses, 2) ?></p>
            <p><strong>Net Income:</strong> KSh <?= number_format($netIncome, 2) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Account Balances</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Account</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
          <tbody>
            <?php foreach ($balances as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['account_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td>KSh <?= number_format((float)($row['debit_total'] ?? 0), 2) ?></td>
              <td>KSh <?= number_format((float)($row['credit_total'] ?? 0), 2) ?></td>
              <td>KSh <?= number_format(((float)($row['debit_total'] ?? 0)) - ((float)($row['credit_total'] ?? 0)), 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
