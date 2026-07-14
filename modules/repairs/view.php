<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT r.*, c.customer_name, u.fullname AS technician_name FROM repairs r LEFT JOIN customers c ON c.id = r.customer_id LEFT JOIN users u ON u.id = r.technician_id WHERE r.id = ?');
$stmt->execute([$id]);
$repair = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$repair) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Repair Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($repair['repair_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($repair['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Device:</strong> <?= htmlspecialchars($repair['device_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Technician:</strong> <?= htmlspecialchars($repair['technician_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($repair['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Priority:</strong> <?= htmlspecialchars($repair['priority'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Estimated:</strong> KSh <?= number_format((float)($repair['estimated_cost'] ?? 0), 2) ?></p>
      <p><strong>Total:</strong> KSh <?= number_format((float)($repair['total_amount'] ?? 0), 2) ?></p>
      <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($repair['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
