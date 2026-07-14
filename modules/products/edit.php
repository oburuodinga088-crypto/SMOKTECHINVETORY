<?php
require '../../includes/auth.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $buying = filter_input(INPUT_POST, 'buying_price', FILTER_VALIDATE_FLOAT);
    $selling = filter_input(INPUT_POST, 'selling_price', FILTER_VALIDATE_FLOAT);
    $reorder = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_INT);
    if ($name === '' || $buying === false || $selling === false || $reorder === false || $buying < 0 || $selling < 0 || $reorder < 0) {
        $message = 'Enter valid product details.';
    } else {
        $stmt = $conn->prepare('UPDATE products SET category_id = ?, supplier_id = ?, product_name = ?, barcode = ?, buying_price = ?, selling_price = ?, reorder_level = ? WHERE id = ?');
        $stmt->execute([filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null, filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT) ?: null, $name, trim($_POST['barcode'] ?? '') ?: null, $buying, $selling, $reorder, $id]);
        header('Location: index.php?status=updated'); exit;
    }
}
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([$id]); $product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { header('Location: index.php'); exit; }
$categories = $conn->query('SELECT id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $conn->query('SELECT id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><h1>Edit Product</h1></section><section class="content"><div class="container-fluid"><?php if ($message): ?><div class="alert alert-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><div class="card card-primary"><form method="post"><div class="card-body row"><div class="col-md-6 form-group"><label>Product Name</label><input class="form-control" name="product_name" value="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>" required></div><div class="col-md-6 form-group"><label>Barcode</label><input class="form-control" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div><div class="col-md-6 form-group"><label>Category</label><select name="category_id" class="form-control"><option value="">Select</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="col-md-6 form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">Select</option><?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier['id'] ?>" <?= $product['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="col-md-4 form-group"><label>Buying Price</label><input type="number" step="0.01" min="0" class="form-control" name="buying_price" value="<?= htmlspecialchars($product['buying_price'], ENT_QUOTES, 'UTF-8') ?>"></div><div class="col-md-4 form-group"><label>Selling Price</label><input type="number" step="0.01" min="0" class="form-control" name="selling_price" value="<?= htmlspecialchars($product['selling_price'], ENT_QUOTES, 'UTF-8') ?>"></div><div class="col-md-4 form-group"><label>Reorder level</label><input type="number" min="0" class="form-control" name="reorder_level" value="<?= (int)$product['reorder_level'] ?>"></div></div><div class="card-footer"><button class="btn btn-success">Save</button> <a href="index.php" class="btn btn-secondary">Cancel</a></div></form></div></div></section></div>
<?php include '../../includes/footer.php'; ?>
