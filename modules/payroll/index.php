<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$payroll = $conn->query("SELECT p.*, u.fullname FROM payroll p LEFT JOIN users u ON u.id = p.employee_id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Payroll</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Payroll Entries</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Payroll</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="payrollTable">
          <thead><tr><th>Code</th><th>Employee</th><th>Period</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($payroll as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['payroll_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['fullname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(($row['pay_period_start'] ?? '-') . ' - ' . ($row['pay_period_end'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['basic_salary'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($row['allowances'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($row['deductions'] ?? 0), 2) ?></td>
                <td>KSh <?= number_format((float)($row['net_pay'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($row['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#payrollTable').DataTable({responsive:true, autoWidth:false}); });</script>
