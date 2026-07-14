<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = getNextSequentialCode('quotations', 'quotation_code', 'QTN');
        $stmt = $conn->prepare('INSERT INTO quotations (quotation_code, customer_id, quotation_date, valid_until, subtotal, discount, tax, total_amount, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $code,
            filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['quotation_date'] ?? '') ?: null,
            trim($_POST['valid_until'] ?? '') ?: null,
            (float)($_POST['subtotal'] ?? 0),
            (float)($_POST['discount'] ?? 0),
            (float)($_POST['tax'] ?? 0),
            (float)($_POST['total_amount'] ?? 0),
            trim($_POST['status'] ?? 'Draft'),
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
  <section class="content-header"><h1>Create Quotation</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach ($customers as $c) echo '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Quotation Date</label><input name="quotation_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Valid Until</label><input name="valid_until" type="date" class="form-control"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Draft</option><option>Sent</option><option>Approved</option><option>Rejected</option></select></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Subtotal</label><input name="subtotal" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Discount</label><input name="discount" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Tax</label><input name="tax" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Total Amount</label><input name="total_amount" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Quotation</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
