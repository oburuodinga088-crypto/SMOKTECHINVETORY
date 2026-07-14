<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM purchase_orders WHERE id = ?');
$stmt->execute([$id]);
$purchaseOrder = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$purchaseOrder) {
    header('Location: index.php');
    exit;
}

$suppliers = $conn->query('SELECT id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('UPDATE purchase_orders SET supplier_id = ?, order_date = ?, expected_date = ?, subtotal = ?, tax = ?, total_amount = ?, status = ?, notes = ? WHERE id = ?');
    $stmt->execute([
        filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        trim($_POST['order_date'] ?? '') ?: null,
        trim($_POST['expected_date'] ?? '') ?: null,
        (float)($_POST['subtotal'] ?? 0),
        (float)($_POST['tax'] ?? 0),
        ((float)($_POST['subtotal'] ?? 0) + (float)($_POST['tax'] ?? 0)),
        trim($_POST['status'] ?? 'Draft'),
        trim($_POST['notes'] ?? ''),
        $id,
    ]);
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Edit Purchase Order</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">-- None --</option><?php foreach ($suppliers as $s) echo '<option value="' . (int)$s['id'] . '"' . (($purchaseOrder['supplier_id'] == $s['id']) ? ' selected' : '') . '>' . htmlspecialchars($s['supplier_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Order Date</label><input name="order_date" type="date" class="form-control" value="<?= htmlspecialchars($purchaseOrder['order_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Expected Date</label><input name="expected_date" type="date" class="form-control" value="<?= htmlspecialchars($purchaseOrder['expected_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Draft" <?= ($purchaseOrder['status'] === 'Draft' ? 'selected' : '') ?>>Draft</option><option value="Approved" <?= ($purchaseOrder['status'] === 'Approved' ? 'selected' : '') ?>>Approved</option><option value="Received" <?= ($purchaseOrder['status'] === 'Received' ? 'selected' : '') ?>>Received</option><option value="Cancelled" <?= ($purchaseOrder['status'] === 'Cancelled' ? 'selected' : '') ?>>Cancelled</option></select></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Subtotal</label><input name="subtotal" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($purchaseOrder['subtotal'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Tax</label><input name="tax" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($purchaseOrder['tax'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"><?= htmlspecialchars($purchaseOrder['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
