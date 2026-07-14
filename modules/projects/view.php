<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT p.*, c.customer_name, u.fullname AS assigned_name FROM projects p LEFT JOIN customers c ON c.id = p.customer_id LEFT JOIN users u ON u.id = p.assigned_to WHERE p.id = ?');
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Project Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($project['project_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Name:</strong> <?= htmlspecialchars($project['project_name'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($project['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Assigned To:</strong> <?= htmlspecialchars($project['assigned_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($project['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Budget:</strong> KSh <?= number_format((float)($project['budget_amount'] ?? 0), 2) ?></p>
      <p><strong>Paid:</strong> KSh <?= number_format((float)($project['amount_paid'] ?? 0), 2) ?></p>
      <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($project['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
