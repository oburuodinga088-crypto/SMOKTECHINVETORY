<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('INSERT INTO account_ledger_entries (entry_date, account_name, entry_type, amount, description, reference_type, reference_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        trim($_POST['entry_date'] ?? '') ?: null,
        trim($_POST['account_name'] ?? ''),
        in_array(strtolower(trim($_POST['entry_type'] ?? 'debit')), ['credit', 'debit'], true) ? strtolower(trim($_POST['entry_type'] ?? 'debit')) : 'debit',
        (float)($_POST['amount'] ?? 0),
        trim($_POST['description'] ?? ''),
        trim($_POST['reference_type'] ?? ''),
        filter_var($_POST['reference_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        $_SESSION['user_id'] ?? null,
    ]);
    header('Location: index.php');
    exit;
}

$entries = $conn->query('SELECT * FROM account_ledger_entries ORDER BY entry_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>General Ledger</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Manual Journal Entries</h3></div>
      <div class="card-body">
        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-2"><div class="form-group"><label>Date</label><input name="entry_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Account</label><input name="account_name" class="form-control" placeholder="Cash, Rent, Sales"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Type</label><select name="entry_type" class="form-control"><option value="debit">Debit</option><option value="credit">Credit</option></select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Reference</label><input name="reference_type" class="form-control" placeholder="Invoice"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Ref ID</label><input name="reference_id" type="number" class="form-control" value="0"></div></div>
          </div>
          <div class="row">
            <div class="col-md-10"><div class="form-group"><label>Description</label><input name="description" class="form-control" placeholder="Narration"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block">Add Entry</button></div></div>
          </div>
        </form>

        <table class="table table-bordered table-striped">
          <thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Amount</th><th>Description</th><th>Reference</th></tr></thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
              <td><?= htmlspecialchars($entry['entry_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($entry['account_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($entry['entry_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td>KSh <?= number_format((float)($entry['amount'] ?? 0), 2) ?></td>
              <td><?= htmlspecialchars($entry['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars(($entry['reference_type'] ?? '-') . ' #' . ($entry['reference_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
