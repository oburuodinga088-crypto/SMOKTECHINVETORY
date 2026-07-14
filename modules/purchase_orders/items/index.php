<?php
require '../../../includes/auth.php';
require '../../../includes/functions.php';
requireLogin();
ensureErpTables();

$purchaseOrderId = (int)($_GET['purchase_order_id'] ?? 0);
if ($purchaseOrderId <= 0) {
    header('Location: ../index.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM purchase_orders WHERE id = ?');
$stmt->execute([$purchaseOrderId]);
$purchaseOrder = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$purchaseOrder) {
    header('Location: ../index.php');
    exit;
}

$items = $conn->prepare('SELECT * FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id');
$items->execute([$purchaseOrderId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query('SELECT id, product_name FROM products ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $description = trim($_POST['description'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $unitPrice = (float)($_POST['unit_price'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $totalPrice = ($quantity * $unitPrice) + $tax;
    $stmt = $conn->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, description, quantity, unit_price, tax, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$purchaseOrderId, $productId, $description, $quantity, $unitPrice, $tax, $totalPrice]);
    header('Location: index.php?purchase_order_id=' . $purchaseOrderId);
    exit;
}

include '../../../includes/header.php';
include '../../../includes/navbar.php';
include '../../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Purchase Order Items</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">PO <?= htmlspecialchars($purchaseOrder['po_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="../view.php?id=<?= (int)$purchaseOrderId ?>" class="btn btn-sm btn-secondary">Back</a>
      </div>
      <div class="card-body">
        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Product</label><select name="product_id" class="form-control"><option value="">-- None --</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Description</label><input name="description" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Quantity</label><input name="quantity" type="number" class="form-control" value="1"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Unit Price</label><input name="unit_price" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Tax</label><input name="tax" type="number" step="0.01" class="form-control" value="0"></div></div>
          </div>
          <button class="btn btn-primary">Add Item</button>
        </form>
        <table class="table table-bordered table-striped">
          <thead><tr><th>Description</th><th>Quantity</th><th>Unit Price</th><th>Tax</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td>KSh <?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($item['tax'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($item['total_price'] ?? 0), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../../includes/footer.php'; ?>
