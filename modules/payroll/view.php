<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT p.*, u.fullname FROM payroll p LEFT JOIN users u ON u.id = p.employee_id WHERE p.id = ?');
$stmt->execute([$id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$payroll) { header('Location: index.php'); exit; }

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Payroll Details</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <p><strong>Code:</strong> <?= htmlspecialchars($payroll['payroll_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Employee:</strong> <?= htmlspecialchars($payroll['fullname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Period:</strong> <?= htmlspecialchars(($payroll['pay_period_start'] ?? '-') . ' - ' . ($payroll['pay_period_end'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Basic Salary:</strong> KSh <?= number_format((float)($payroll['basic_salary'] ?? 0), 2) ?></p>
      <p><strong>Allowances:</strong> KSh <?= number_format((float)($payroll['allowances'] ?? 0), 2) ?></p>
      <p><strong>Deductions:</strong> KSh <?= number_format((float)($payroll['deductions'] ?? 0), 2) ?></p>
      <p><strong>Net Pay:</strong> KSh <?= number_format((float)($payroll['net_pay'] ?? 0), 2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($payroll['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
      <a href="index.php" class="btn btn-secondary">Back</a>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
