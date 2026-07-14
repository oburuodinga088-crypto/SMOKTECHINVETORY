<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$repairs = $conn->query("SELECT r.*, c.customer_name, u.fullname AS technician_name FROM repairs r LEFT JOIN customers c ON c.id = r.customer_id LEFT JOIN users u ON u.id = r.technician_id ORDER BY r.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Repairs</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Repair Jobs</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Repair</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="repairsTable">
          <thead><tr><th>Code</th><th>Customer</th><th>Device</th><th>Technician</th><th>Status</th><th>Amount</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($repairs as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['repair_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['device_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['technician_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
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
<script>$(function(){ $('#repairsTable').DataTable({responsive:true, autoWidth:false}); });</script>
