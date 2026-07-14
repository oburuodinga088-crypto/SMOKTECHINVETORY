<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('INSERT INTO budgets (budget_name, budget_type, budget_amount, period_start, period_end, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        trim($_POST['budget_name'] ?? ''),
        trim($_POST['budget_type'] ?? 'expense'),
        (float)($_POST['budget_amount'] ?? 0),
        trim($_POST['period_start'] ?? '') ?: null,
        trim($_POST['period_end'] ?? '') ?: null,
        trim($_POST['notes'] ?? ''),
        $_SESSION['user_id'] ?? null,
    ]);
    header('Location: index.php');
    exit;
}

if (!tableExists('budgets')) {
    $conn->exec("CREATE TABLE budgets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_name VARCHAR(150) NOT NULL,
        budget_type VARCHAR(50) DEFAULT 'expense',
        budget_amount DECIMAL(12,2) DEFAULT 0,
        period_start DATE DEFAULT NULL,
        period_end DATE DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$budgets = $conn->query('SELECT * FROM budgets ORDER BY period_end DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
$actualExpense = (float) $conn->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn();
$actualIncome = (float) $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
$budgetTotal = (float) $conn->query("SELECT COALESCE(SUM(budget_amount), 0) FROM budgets")->fetchColumn();

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Budgets & Planning</h1></section>
  <section class="content">
    <div class="row">
      <div class="col-md-4">
        <div class="small-box bg-info">
          <div class="inner"><h3>KSh <?= number_format($budgetTotal, 2) ?></h3><p>Total Budget</p></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-danger">
          <div class="inner"><h3>KSh <?= number_format($actualExpense, 2) ?></h3><p>Actual Expense</p></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-success">
          <div class="inner"><h3>KSh <?= number_format($actualIncome, 2) ?></h3><p>Actual Income</p></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Create Budget</h3></div>
      <div class="card-body">
        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Name</label><input name="budget_name" class="form-control" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Type</label><select name="budget_type" class="form-control"><option value="expense">Expense</option><option value="income">Income</option></select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Amount</label><input name="budget_amount" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Start</label><input name="period_start" type="date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>End</label><input name="period_end" type="date" class="form-control"></div></div>
            <div class="col-md-1"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary">Add</button></div></div>
          </div>
          <div class="row">
            <div class="col-md-12"><div class="form-group"><label>Notes</label><input name="notes" class="form-control"></div></div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Budgets</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Name</th><th>Type</th><th>Amount</th><th>Period</th><th>Notes</th></tr></thead>
          <tbody>
            <?php foreach ($budgets as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['budget_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($row['budget_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td>KSh <?= number_format((float)($row['budget_amount'] ?? 0), 2) ?></td>
              <td><?= htmlspecialchars(($row['period_start'] ?? '-') . ' to ' . ($row['period_end'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($row['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
