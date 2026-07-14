<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM expenses WHERE id = ?');
$stmt->execute([$id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$expense) { header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Your session form has expired. Please try again.');
        }

        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $paymentStatus = trim($_POST['payment_status'] ?? 'Pending');
        if ($amount === false || $amount < 0) {
            throw new InvalidArgumentException('Amount must be a non-negative number.');
        }
        if (!in_array($paymentStatus, ['Pending', 'Paid'], true)) {
            throw new InvalidArgumentException('Invalid payment status.');
        }

        $stmt = $conn->prepare('UPDATE expenses SET expense_date = ?, expense_category = ?, expense_name = ?, amount = ?, payment_method = ?, vendor = ?, reference_no = ?, payment_status = ?, notes = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['expense_date'] ?? '') ?: null,
            trim($_POST['category'] ?? ''),
            trim($_POST['description'] ?? ''),
            $amount,
            trim($_POST['payment_method'] ?? ''),
            trim($_POST['vendor'] ?? ''),
            trim($_POST['reference_no'] ?? ''),
            $paymentStatus,
            trim($_POST['notes'] ?? ''),
            $id,
        ]);
        header('Location: view.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Edit Expense</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <?= csrfField() ?>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Date</label><input name="expense_date" type="date" class="form-control" value="<?= htmlspecialchars($expense['expense_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Category</label><input name="category" class="form-control" value="<?= htmlspecialchars($expense['expense_category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"><?= htmlspecialchars($expense['expense_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="<?= (float)($expense['amount'] ?? 0) ?>"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Payment Method</label><input name="payment_method" class="form-control" value="<?= htmlspecialchars($expense['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Vendor</label><input name="vendor" class="form-control" value="<?= htmlspecialchars($expense['vendor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Reference No.</label><input name="reference_no" class="form-control" value="<?= htmlspecialchars($expense['reference_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option <?= ($expense['payment_status'] === 'Pending') ? 'selected' : '' ?>>Pending</option><option <?= ($expense['payment_status'] === 'Paid') ? 'selected' : '' ?>>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"><?= htmlspecialchars($expense['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
