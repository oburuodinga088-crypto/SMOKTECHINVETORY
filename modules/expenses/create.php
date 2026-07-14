<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

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

        $code = getNextSequentialCode('expenses', 'expense_code', 'EXP');
        $stmt = $conn->prepare('INSERT INTO expenses (expense_code, expense_date, expense_category, expense_name, amount, payment_method, vendor, reference_no, payment_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $code,
            trim($_POST['expense_date'] ?? '') ?: null,
            trim($_POST['category'] ?? ''),
            trim($_POST['description'] ?? ''),
            $amount,
            trim($_POST['payment_method'] ?? ''),
            trim($_POST['vendor'] ?? ''),
            trim($_POST['reference_no'] ?? ''),
            $paymentStatus,
            trim($_POST['notes'] ?? ''),
            $_SESSION['user_id'] ?? null,
        ]);
        header('Location: index.php');
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
  <section class="content-header"><h1>Create Expense</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <?= csrfField() ?>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Date</label><input name="expense_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Category</label><input name="category" class="form-control"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
            <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="0"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Payment Method</label><input name="payment_method" class="form-control"></div>
            <div class="form-group"><label>Vendor</label><input name="vendor" class="form-control"></div>
            <div class="form-group"><label>Reference No.</label><input name="reference_no" class="form-control"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option>Pending</option><option>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Expense</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
