<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT p.*, s.supplier_name FROM purchase_orders p LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?');
$stmt->execute([$id]);
$purchaseOrder = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$purchaseOrder) {
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Purchase Order Details</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-body">
        <p><strong>Code:</strong> <?= htmlspecialchars($purchaseOrder['po_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Supplier:</strong> <?= htmlspecialchars($purchaseOrder['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Order Date:</strong> <?= htmlspecialchars($purchaseOrder['order_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Expected Date:</strong> <?= htmlspecialchars($purchaseOrder['expected_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Subtotal:</strong> KSh <?= number_format((float)($purchaseOrder['subtotal'] ?? 0), 2) ?></p>
        <p><strong>Tax:</strong> KSh <?= number_format((float)($purchaseOrder['tax'] ?? 0), 2) ?></p>
        <p><strong>Total:</strong> KSh <?= number_format((float)($purchaseOrder['total_amount'] ?? 0), 2) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($purchaseOrder['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($purchaseOrder['notes'] ?? '-', ENT_QUOTES, 'UTF-8')) ?></p>
        <a href="items/index.php?purchase_order_id=<?= (int)$purchaseOrder['id'] ?>" class="btn btn-info">Manage Items</a>
        <a href="index.php" class="btn btn-secondary">Back</a>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
