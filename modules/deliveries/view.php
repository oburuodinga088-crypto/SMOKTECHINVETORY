<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT d.*, p.po_code, s.supplier_name FROM deliveries d LEFT JOIN purchase_orders p ON p.id = d.purchase_order_id LEFT JOIN suppliers s ON s.id = d.supplier_id WHERE d.id = ?');
$stmt->execute([$id]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$delivery) {
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Delivery Details</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-body">
        <p><strong>Reference:</strong> <?= htmlspecialchars($delivery['delivery_ref'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Purchase Order:</strong> <?= htmlspecialchars($delivery['po_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Supplier:</strong> <?= htmlspecialchars($delivery['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Delivery Date:</strong> <?= htmlspecialchars($delivery['delivery_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($delivery['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($delivery['notes'] ?? '-', ENT_QUOTES, 'UTF-8')) ?></p>
        <a href="items/index.php?delivery_id=<?= (int)$delivery['id'] ?>" class="btn btn-info">Manage Items</a>
        <a href="index.php" class="btn btn-secondary">Back</a>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
