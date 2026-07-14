<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$suppliers = $conn->query('SELECT id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = getNextSequentialCode('purchase_orders', 'po_code', 'PO');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $tax = (float)($_POST['tax'] ?? 0);
        $total = $subtotal + $tax;
        $stmt = $conn->prepare('INSERT INTO purchase_orders (po_code, supplier_id, order_date, expected_date, subtotal, tax, total_amount, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT) ?: null, trim($_POST['order_date'] ?? '') ?: null, trim($_POST['expected_date'] ?? '') ?: null, $subtotal, $tax, $total, trim($_POST['status'] ?? 'Draft'), trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
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
  <section class="content-header"><h1>Create Purchase Order</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">-- None --</option><?php foreach ($suppliers as $s) echo '<option value="' . (int)$s['id'] . '">' . htmlspecialchars($s['supplier_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Order Date</label><input name="order_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Expected Date</label><input name="expected_date" type="date" class="form-control"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Draft</option><option>Approved</option><option>Received</option><option>Cancelled</option></select></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Subtotal</label><input name="subtotal" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Tax</label><input name="tax" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Purchase Order</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
