<?php
require '../../../includes/auth.php';
require '../../../includes/functions.php';
requireLogin();
ensureErpTables();

$deliveryId = (int)($_GET['delivery_id'] ?? 0);
if ($deliveryId <= 0) {
    header('Location: ../index.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM deliveries WHERE id = ?');
$stmt->execute([$deliveryId]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$delivery) {
    header('Location: ../index.php');
    exit;
}

$items = $conn->prepare('SELECT * FROM delivery_items WHERE delivery_id = ? ORDER BY id');
$items->execute([$deliveryId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query('SELECT id, product_name FROM products ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $description = trim($_POST['description'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $receivedQty = (int)($_POST['received_quantity'] ?? 0);
    $stmt = $conn->prepare('INSERT INTO delivery_items (delivery_id, product_id, description, quantity, received_quantity, status) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$deliveryId, $productId, $description, $quantity, $receivedQty, trim($_POST['status'] ?? 'Pending')]);
    header('Location: index.php?delivery_id=' . $deliveryId);
    exit;
}

include '../../../includes/header.php';
include '../../../includes/navbar.php';
include '../../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Delivery Items</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Delivery <?= htmlspecialchars($delivery['delivery_ref'] ?? '-', ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="../view.php?id=<?= (int)$deliveryId ?>" class="btn btn-sm btn-secondary">Back</a>
      </div>
      <div class="card-body">
        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Product</label><select name="product_id" class="form-control"><option value="">-- None --</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Description</label><input name="description" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Quantity</label><input name="quantity" type="number" class="form-control" value="1"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Received</label><input name="received_quantity" type="number" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Pending</option><option>Received</option><option>Partially Received</option></select></div></div>
          </div>
          <button class="btn btn-primary">Add Item</button>
        </form>
        <table class="table table-bordered table-striped">
          <thead><tr><th>Description</th><th>Quantity</th><th>Received</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= (int)$item['received_quantity'] ?></td>
                <td><?= htmlspecialchars($item['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../../includes/footer.php'; ?>
