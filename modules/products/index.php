<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

$stmt = $conn->query("
SELECT
products.*,
categories.category_name,
suppliers.supplier_name
FROM products
LEFT JOIN categories
ON products.category_id = categories.id
LEFT JOIN suppliers
ON products.supplier_id = suppliers.id
ORDER BY products.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>

<div class="content-wrapper">

<section class="content-header">

<div class="container-fluid">

<div class="row mb-2">

<div class="col-sm-6">

<h1>

<i class="fas fa-boxes"></i>

Products

</h1>

</div>

<div class="col-sm-6 text-right">

<a href="add.php" class="btn btn-success">

<i class="fas fa-plus-circle"></i>

New Product

</a>

</div>

</div>

</div>

</section>

<section class="content">

<div class="container-fluid">

<div class="card card-primary">

<div class="card-header">

<h3 class="card-title">

Product Inventory

</h3>

</div>

<div class="card-body">

<table id="productsTable"
class="table table-bordered table-striped">

<thead>

<tr>

<th>ID</th>

<th>Product</th>

<th>Category</th>

<th>Supplier</th>

<th>Buying</th>

<th>Selling</th>

<th>Stock</th>

<th>Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($products as $row): ?>

<tr>

<td><?= $row['id']; ?></td>

<td><?= htmlspecialchars($row['product_name']); ?></td>

<td><?= htmlspecialchars($row['category_name']); ?></td>

<td><?= htmlspecialchars($row['supplier_name']); ?></td>

<td>KSh <?= number_format($row['buying_price'],2); ?></td>

<td>KSh <?= number_format($row['selling_price'],2); ?></td>

<td>

<?php

if($row['current_stock'] <= $row['reorder_level']){

echo "<span class='badge badge-danger'>{$row['current_stock']}</span>";

}else{

echo "<span class='badge badge-success'>{$row['current_stock']}</span>";

}

?>

</td>

<td>

<a href="edit.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm">

<i class="fas fa-edit"></i>

</a>

<a href="delete.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete product?')">

<i class="fas fa-trash"></i>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</section>

</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(function () {
$('#productsTable').DataTable({
responsive:true,
autoWidth:false
});
});
</script>
