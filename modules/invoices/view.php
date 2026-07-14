<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT i.*, c.customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?');
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invoice) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Invoice Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($invoice['invoice_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($invoice['invoice_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Subtotal:</strong> KSh <?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></p>
      <p><strong>Discount:</strong> KSh <?= number_format((float)($invoice['discount'] ?? 0), 2) ?></p>
      <p><strong>Tax:</strong> KSh <?= number_format((float)($invoice['tax'] ?? 0), 2) ?></p>
      <p><strong>Total:</strong> KSh <?= number_format((float)($invoice['total_amount'] ?? 0), 2) ?></p>
      <p><strong>Amount Paid:</strong> KSh <?= number_format((float)($invoice['amount_paid'] ?? 0), 2) ?></p>
      <p><strong>Balance:</strong> KSh <?= number_format((float)($invoice['balance'] ?? 0), 2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($invoice['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($invoice['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
