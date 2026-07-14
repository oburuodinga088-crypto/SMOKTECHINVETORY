<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$purchaseOrders = $conn->query('SELECT id, po_code FROM purchase_orders ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ref = getNextSequentialCode('deliveries', 'delivery_ref', 'DLV');
        $stmt = $conn->prepare('INSERT INTO deliveries (delivery_ref, purchase_order_id, supplier_id, delivery_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$ref, filter_var($_POST['purchase_order_id'] ?? null, FILTER_VALIDATE_INT) ?: null, filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT) ?: null, trim($_POST['delivery_date'] ?? '') ?: null, trim($_POST['status'] ?? 'Pending'), trim($_POST['notes'] ?? ''), $_SESSION['user_id'] ?? null]);
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
  <section class="content-header"><h1>Create Delivery</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Purchase Order</label><select name="purchase_order_id" class="form-control"><option value="">-- None --</option><?php foreach ($purchaseOrders as $po) echo '<option value="' . (int)$po['id'] . '">' . htmlspecialchars($po['po_code'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Supplier ID</label><input name="supplier_id" type="number" class="form-control" value="0"></div>
            <div class="form-group"><label>Delivery Date</label><input name="delivery_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Pending</option><option>Received</option><option>Partially Received</option><option>Cancelled</option></select></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Delivery</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
