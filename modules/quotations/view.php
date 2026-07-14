<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT q.*, c.customer_name FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.id = ?');
$stmt->execute([$id]);
$quotation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quotation) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Quotation Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($quotation['quotation_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($quotation['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($quotation['quotation_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Valid Until:</strong> <?= htmlspecialchars($quotation['valid_until'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Subtotal:</strong> KSh <?= number_format((float)($quotation['subtotal'] ?? 0), 2) ?></p>
      <p><strong>Discount:</strong> KSh <?= number_format((float)($quotation['discount'] ?? 0), 2) ?></p>
      <p><strong>Tax:</strong> KSh <?= number_format((float)($quotation['tax'] ?? 0), 2) ?></p>
      <p><strong>Total:</strong> KSh <?= number_format((float)($quotation['total_amount'] ?? 0), 2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($quotation['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($quotation['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
