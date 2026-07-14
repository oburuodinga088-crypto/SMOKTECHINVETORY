<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name  = trim($_POST['product_name']);
    $barcode       = trim($_POST['barcode']);
    $stock         = (int)$_POST['current_stock']; // Ensure it's an integer

    if (empty($product_name)) {
        $message = "<div class='alert alert-danger'>Product name is required.</div>";
    } else {
        try {
            $conn->beginTransaction();

            // 1. Insert Product
            $stmt = $conn->prepare("INSERT INTO products 
                (category_id, supplier_id, product_name, barcode, buying_price, selling_price, current_stock, reorder_level) 
                VALUES (?,?,?,?,?,?,?,?)");
            
            $stmt->execute([
                $_POST['category_id'] ?: null,
                $_POST['supplier_id'] ?: null,
                $product_name,
                $barcode,
                $_POST['buying_price'],
                $_POST['selling_price'],
                $stock,
                $_POST['reorder_level']
            ]);

            $productId = $conn->lastInsertId();

            // 2. If opening stock > 0, record a valid purchase and purchase item.
            if ($stock > 0) {
                $buyingPrice = (float)$_POST['buying_price'];
                $pStmt = $conn->prepare("INSERT INTO purchases (supplier_id, purchase_date, total_amount) VALUES (?, NOW(), ?)");
                $pStmt->execute([$_POST['supplier_id'] ?: null, $stock * $buyingPrice]);
                $purchaseId = $conn->lastInsertId();
                $itemStmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, buying_price) VALUES (?, ?, ?, ?)");
                $itemStmt->execute([$purchaseId, $productId, $stock, $buyingPrice]);
            }

            $conn->commit();
            header("Location: index.php?status=success");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $conn->query("SELECT id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header"><h1>Add Product</h1></section>
    <section class="content">
        <?= $message ?>
        <div class="card card-primary">
            <form method="POST">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Product Name</label><input type="text" name="product_name" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Barcode</label><input type="text" name="barcode" class="form-control"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Category</label><select name="category_id" class="form-control"><option value="">Select</option><?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option><?php endforeach; ?></select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">Select</option><?php foreach($suppliers as $sup): ?><option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['supplier_name']) ?></option><?php endforeach; ?></select></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Buying Price</label><input type="number" step="0.01" name="buying_price" class="form-control" value="0"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Selling Price</label><input type="number" step="0.01" name="selling_price" class="form-control" value="0"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Opening Stock</label><input type="number" name="current_stock" class="form-control" value="0"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="5"></div></div>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Product</button></div>
            </form>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
