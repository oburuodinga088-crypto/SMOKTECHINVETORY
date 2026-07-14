<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

ensureServiceSchema();

$services = [];
$svcFields = [];
if (tableExists('services')) {
    // adapt to schema: date_created or created_at, total_amount or standard_price
    $svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $dateCol = in_array('date_created', $svcFields, true) ? 'date_created' : (in_array('created_at', $svcFields, true) ? 'created_at' : null);
    $totalCol = in_array('total_amount', $svcFields, true) ? 'total_amount' : (in_array('standard_price', $svcFields, true) ? 'standard_price' : null);
    $dateOrder = $dateCol ? "s.$dateCol" : 's.id';
    // Support showing deleted rows via ?show_deleted=1 when soft-delete is present
    $showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';
    $where = '';
    if (in_array('is_deleted', $svcFields, true) && !$showDeleted) { $where = 'WHERE s.is_deleted = 0'; }
    $services = $conn->query("SELECT s.*, c.customer_name, u.fullname AS technician_name FROM services s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN users u ON u.id = s.technician_id $where ORDER BY $dateOrder DESC")->fetchAll(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Services</h1></section>
    <section class="content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">All Services</h3>
                <div>
                    <a href="create.php" class="btn btn-sm btn-primary">New Service</a>
                    <?php if (in_array('is_deleted', $svcFields, true)): ?>
                        <?php if (!empty($showDeleted)): ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary ml-2">Hide deleted</a>
                        <?php else: ?>
                            <a href="index.php?show_deleted=1" class="btn btn-sm btn-outline-secondary ml-2">Show deleted</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>
                <?php if (!tableExists('services')): ?>
                    <div class="alert alert-warning">Services tables are not created. Run <code>database/migrations/20260713_create_services_tables.sql</code>.</div>
                <?php else: ?>
                <table id="servicesTable" class="table table-striped table-bordered">
                    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Customer</th><th>Technician</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['service_code'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['service_category'] ?? ($s['service_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['technician_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['service_status'] ?? $s['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>KSh <?= number_format((float)($s[$totalCol] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($s[$dateCol] ?? $s['created_at'] ?? $s['date_created'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="view.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <?php if (empty($s['is_deleted'])): ?>
                                        <a href="edit.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <?php endif; ?>
                                    <?php $cur = currentUser(); $adminRoles = ['admin','administrator','superuser']; if (in_array(strtolower($cur['role'] ?? ''), $adminRoles, true)): ?>
                                        <?php if (!empty($s['is_deleted'])): ?>
                                            <a href="undo_delete.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Restore this service and re-consume parts from stock?')">Restore</a>
                                        <?php else: ?>
                                            <a href="delete.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service and return parts to stock?')">Delete</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
if (window.$ && $.fn.DataTable) {
    $(function(){ $('#servicesTable').DataTable({responsive:true, autoWidth:false, dom:'Bfrtip', buttons:['excel','csv','pdf','print']}); });
}
</script>