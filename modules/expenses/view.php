<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM expenses WHERE id = ?');
$stmt->execute([$id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$expense) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Expense Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($expense['expense_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($expense['expense_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Category:</strong> <?= htmlspecialchars($expense['expense_category'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($expense['expense_name'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      <p><strong>Amount:</strong> KSh <?= number_format((float)($expense['amount'] ?? 0), 2) ?></p>
      <p><strong>Vendor:</strong> <?= htmlspecialchars($expense['vendor'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($expense['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
