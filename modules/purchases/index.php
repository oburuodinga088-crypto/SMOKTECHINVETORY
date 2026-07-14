<?php
require '../../includes/auth.php';
requireLogin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT) ?: null;
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $buyingPrice = filter_input(INPUT_POST, 'buying_price', FILTER_VALIDATE_FLOAT);
    if (!$productId || !$quantity || $quantity < 1 || $buyingPrice === false || $buyingPrice < 0) {
        $message = '<div class="alert alert-danger">Enter a product, a positive quantity, and a valid buying price.</div>';
    } else {
        try {
            $conn->beginTransaction();
            $product = $conn->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
            $product->execute([$productId]);
            if (!$product->fetch()) {
                throw new RuntimeException('Product not found.');
            }
            $purchase = $conn->prepare('INSERT INTO purchases (supplier_id, purchase_date, total_amount) VALUES (?, NOW(), ?)');
            $purchase->execute([$supplierId, $quantity * $buyingPrice]);
            $purchaseId = $conn->lastInsertId();
            $item = $conn->prepare('INSERT INTO purchase_items (purchase_id, product_id, quantity, buying_price) VALUES (?, ?, ?, ?)');
            $item->execute([$purchaseId, $productId, $quantity, $buyingPrice]);
            $stock = $conn->prepare('UPDATE products SET current_stock = current_stock + ?, buying_price = ? WHERE id = ?');
            $stock->execute([$quantity, $buyingPrice, $productId]);
            $conn->commit();
            $message = '<div class="alert alert-success">Purchase saved and stock updated.</div>';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $message = '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</div>';
        }
    }
}
$products = $conn->query('SELECT id, product_name, current_stock, buying_price FROM products ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $conn->query('SELECT id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll(PDO::FETCH_ASSOC);
$purchases = $conn->query('SELECT p.id, p.purchase_date, p.total_amount, s.supplier_name, GROUP_CONCAT(CONCAT(pr.product_name, " × ", pi.quantity) SEPARATOR ", ") AS items FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN purchase_items pi ON pi.purchase_id = p.id LEFT JOIN products pr ON pr.id = pi.product_id GROUP BY p.id, p.purchase_date, p.total_amount, s.supplier_name ORDER BY p.id DESC')->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><h1>Purchases</h1></section><section class="content"><div class="container-fluid"><?= $message ?><div class="card card-primary"><form method="post"><div class="card-body row"><div class="col-md-4 form-group"><label>Product</label><select name="product_id" class="form-control" required><option value="">Select product</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?> (stock: <?= (int)$product['current_stock'] ?>)</option><?php endforeach; ?></select></div><div class="col-md-3 form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">No supplier</option><?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="col-md-2 form-group"><label>Quantity</label><input name="quantity" type="number" min="1" class="form-control" required></div><div class="col-md-3 form-group"><label>Buying price</label><input name="buying_price" type="number" min="0" step="0.01" class="form-control" required></div></div><div class="card-footer"><button class="btn btn-success">Save Purchase</button></div></form></div><div class="card"><div class="card-body"><table class="table table-striped"><thead><tr><th>Date</th><th>Supplier</th><th>Items</th><th>Total</th></tr></thead><tbody><?php foreach ($purchases as $purchase): ?><tr><td><?= htmlspecialchars($purchase['purchase_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($purchase['supplier_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($purchase['items'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td>KSh <?= number_format((float)$purchase['total_amount'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></section></div>
<?php include '../../includes/footer.php'; ?>
