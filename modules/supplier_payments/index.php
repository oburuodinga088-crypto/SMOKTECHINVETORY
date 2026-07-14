<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$suppliers = $conn->query('SELECT id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll(PDO::FETCH_ASSOC);
$payments = $conn->query('SELECT sp.*, s.supplier_name FROM supplier_payments sp LEFT JOIN suppliers s ON s.id = sp.supplier_id ORDER BY sp.payment_date DESC, sp.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Your session form has expired. Please try again.');
        }
        $supplierId = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$supplierId || $amount === false || $amount <= 0) {
            throw new InvalidArgumentException('Select a supplier and enter a positive payment amount.');
        }

        $outstanding = $conn->prepare("SELECT GREATEST(COALESCE((SELECT SUM(total_amount) FROM purchase_orders WHERE supplier_id = ? AND status <> 'Completed'), 0) - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = ?), 0), 0)");
        $outstanding->execute([$supplierId, $supplierId]);
        $balance = (float) $outstanding->fetchColumn();
        if ($balance <= 0) {
            throw new RuntimeException('This supplier has no outstanding payable balance.');
        }
        if ($amount > $balance) {
            throw new InvalidArgumentException('Payment cannot exceed the supplier payable balance of KSh ' . number_format($balance, 2) . '.');
        }

        $stmt = $conn->prepare('INSERT INTO supplier_payments (supplier_id, payment_date, amount, payment_method, reference_no, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$supplierId, trim($_POST['payment_date'] ?? '') ?: null, $amount, trim($_POST['payment_method'] ?? ''), trim($_POST['reference_no'] ?? ''), trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        $message = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Supplier Payments</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Payments Ledger</h3>
      </div>
      <div class="card-body">
        <?= $message ?>
        <form method="post" class="mb-4">
          <?= csrfField() ?>
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">-- None --</option><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['supplier_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Date</label><input name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Method</label><input name="payment_method" class="form-control" value="Cash"></div></div>
            <div class="col-md-3"><div class="form-group"><label>Reference</label><input name="reference_no" class="form-control"></div></div>
          </div>
          <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          <button class="btn btn-primary">Record Payment</button>
        </form>

        <table class="table table-bordered table-striped" id="paymentsTable">
          <thead><tr><th>Supplier</th><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr></thead>
          <tbody>
            <?php foreach ($payments as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['payment_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['payment_method'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['reference_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
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
<script>$(function(){ $('#paymentsTable').DataTable({responsive:true, autoWidth:false}); });</script>
