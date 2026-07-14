<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$projects = $conn->query("SELECT p.*, c.customer_name, u.fullname AS assigned_name FROM projects p LEFT JOIN customers c ON c.id = p.customer_id LEFT JOIN users u ON u.id = p.assigned_to ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Projects</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Project Portfolio</h3>
        <a href="create.php" class="btn btn-sm btn-primary">New Project</a>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="projectsTable">
          <thead><tr><th>Code</th><th>Name</th><th>Customer</th><th>Assigned</th><th>Status</th><th>Budget</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($projects as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['project_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['assigned_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>KSh <?= number_format((float)($row['budget_amount'] ?? 0), 2) ?></td>
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
<script>$(function(){ $('#projectsTable').DataTable({responsive:true, autoWidth:false}); });</script>
