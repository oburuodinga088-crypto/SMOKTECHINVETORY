<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM deliveries WHERE id = ?');
$stmt->execute([$id]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$delivery) {
    header('Location: index.php');
    exit;
}

$purchaseOrders = $conn->query('SELECT id, po_code FROM purchase_orders ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('UPDATE deliveries SET purchase_order_id = ?, supplier_id = ?, delivery_date = ?, status = ?, notes = ? WHERE id = ?');
    $stmt->execute([
        filter_var($_POST['purchase_order_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        trim($_POST['delivery_date'] ?? '') ?: null,
        trim($_POST['status'] ?? 'Pending'),
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
  <section class="content-header"><h1>Edit Delivery</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Purchase Order</label><select name="purchase_order_id" class="form-control"><option value="">-- None --</option><?php foreach ($purchaseOrders as $po) echo '<option value="' . (int)$po['id'] . '"' . (($delivery['purchase_order_id'] == $po['id']) ? ' selected' : '') . '>' . htmlspecialchars($po['po_code'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Supplier ID</label><input name="supplier_id" type="number" class="form-control" value="<?= htmlspecialchars($delivery['supplier_id'] ?? 0, ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Delivery Date</label><input name="delivery_date" type="date" class="form-control" value="<?= htmlspecialchars($delivery['delivery_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Pending" <?= ($delivery['status'] === 'Pending' ? 'selected' : '') ?>>Pending</option><option value="Received" <?= ($delivery['status'] === 'Received' ? 'selected' : '') ?>>Received</option><option value="Partially Received" <?= ($delivery['status'] === 'Partially Received' ? 'selected' : '') ?>>Partially Received</option><option value="Cancelled" <?= ($delivery['status'] === 'Cancelled' ? 'selected' : '') ?>>Cancelled</option></select></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"><?= htmlspecialchars($delivery['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
