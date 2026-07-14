<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$employees = $conn->query("SELECT id, fullname, username, role FROM users ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Employees</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Staff Directory</h3></div>
      <div class="card-body">
        <table class="table table-bordered table-striped" id="employeesTable">
          <thead><tr><th>Name</th><th>Username</th><th>Role</th></tr></thead>
          <tbody>
            <?php foreach ($employees as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['fullname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['username'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['role'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#employeesTable').DataTable({responsive:true, autoWidth:false}); });</script>
