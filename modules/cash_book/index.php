<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$entries = $conn->query('SELECT * FROM cash_book_entries ORDER BY entry_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
$openingBalance = getOpeningCashBalance($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['set_opening_balance'])) {
        saveOpeningCashBalance(
            (float)($_POST['opening_balance_amount'] ?? 0),
            trim($_POST['opening_balance_date'] ?? '') ?: null,
            trim($_POST['opening_balance_description'] ?? 'Opening balance')
        );
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO cash_book_entries (entry_date, description, entry_type, amount, reference_type, reference_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        trim($_POST['entry_date'] ?? '') ?: null,
        trim($_POST['description'] ?? ''),
        trim($_POST['entry_type'] ?? 'income'),
        (float)($_POST['amount'] ?? 0),
        trim($_POST['reference_type'] ?? ''),
        filter_var($_POST['reference_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        trim($_POST['notes'] ?? ''),
        $_SESSION['user_id'] ?? null,
    ]);
    header('Location: index.php');
    exit;
}

$incomeTotal = (float) $conn->query("SELECT COALESCE(SUM(amount), 0) FROM cash_book_entries WHERE entry_type = 'income'")->fetchColumn();
$expenseTotal = (float) $conn->query("SELECT COALESCE(SUM(amount), 0) FROM cash_book_entries WHERE entry_type = 'expense'")->fetchColumn();
$balance = max(0.0, $openingBalance + $incomeTotal - $expenseTotal);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Cash Book</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Cash Book Summary</h3></div>
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= number_format($openingBalance, 2) ?></h3><p>Opening Balance</p></div></div></div>
          <div class="col-md-4"><div class="small-box bg-danger"><div class="inner"><h3>KSh <?= number_format($expenseTotal, 2) ?></h3><p>Total Expense</p></div></div></div>
          <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>KSh <?= number_format($balance, 2) ?></h3><p>Cash Available</p></div></div></div>
        </div>

        <form method="post" class="mb-4 border rounded p-3">
          <input type="hidden" name="set_opening_balance" value="1">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Date</label><input name="opening_balance_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Opening Amount</label><input name="opening_balance_amount" type="number" step="0.01" class="form-control" value="<?= number_format($openingBalance, 2, '.', '') ?>"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Description</label><input name="opening_balance_description" class="form-control" value="Opening balance"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button class="btn btn-secondary btn-block">Set Opening Balance</button></div></div>
          </div>
        </form>

        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Date</label><input name="entry_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Description</label><input name="description" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Type</label><select name="entry_type" class="form-control"><option value="income">Income</option><option value="expense">Expense</option></select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Reference ID</label><input name="reference_id" type="number" class="form-control" value="0"></div></div>
          </div>
          <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Reference Type</label><input name="reference_type" class="form-control"></div></div>
            <div class="col-md-6"><div class="form-group"><label>Notes</label><input name="notes" class="form-control"></div></div>
          </div>
          <button class="btn btn-primary">Add Entry</button>
        </form>

        <table class="table table-bordered table-striped" id="cashBookTable">
          <thead><tr><th>Date</th><th>Description</th><th>Type</th><th>Amount</th><th>Reference</th><th>Notes</th></tr></thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
              <tr>
                <td><?= htmlspecialchars($entry['entry_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($entry['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($entry['entry_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($entry['amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars(($entry['reference_type'] ?? '-') . ' #' . ($entry['reference_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($entry['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#cashBookTable').DataTable({responsive:true, autoWidth:false}); });</script>
