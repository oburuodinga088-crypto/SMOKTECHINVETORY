<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

ensureServiceSchema();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$service = $conn->prepare('SELECT s.*, c.customer_name, u.fullname AS technician_name FROM services s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN users u ON u.id = s.technician_id WHERE s.id = ?');
$service->execute([$id]);
$s = $service->fetch(PDO::FETCH_ASSOC);
if (!$s) { header('Location: index.php'); exit; }

$parts = $conn->prepare('SELECT sp.*, p.product_name FROM service_parts sp JOIN products p ON p.id = sp.product_id WHERE sp.service_id = ?');
$parts->execute([$id]);
$partsList = $parts->fetchAll(PDO::FETCH_ASSOC);

// detect schema variance for totals and sale link
$svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
$totalCol = in_array('total_amount', $svcFields, true) ? 'total_amount' : (in_array('standard_price', $svcFields, true) ? 'standard_price' : (in_array('estimated_cost', $svcFields, true) ? 'estimated_cost' : null));

// fetch audit logs related to this service
$auditStmt = $conn->prepare("SELECT * FROM audit_logs WHERE entity_type = 'service' AND (reference = ? OR reference = ?) ORDER BY id DESC LIMIT 10");
$auditStmt->execute([$s['service_code'] ?? '', (string)$s['id']]);
$audits = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Service Details</h1></section>
    <section class="content">
        <div class="card">
            <div class="card-body">
                <h4><?= htmlspecialchars($s['service_name'], ENT_QUOTES, 'UTF-8') ?> <small class="text-muted"><?= htmlspecialchars($s['service_code'], ENT_QUOTES, 'UTF-8') ?></small></h4>
                <p><strong>Category:</strong> <?= htmlspecialchars($s['service_category'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Customer:</strong> <?= htmlspecialchars($s['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Technician:</strong> <?= htmlspecialchars($s['technician_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($s['service_status'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Total:</strong> KSh <?= number_format((float)($s[$totalCol] ?? 0),2) ?></p>
                <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($s['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                <h5>Parts Used</h5>
                <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody>
                    <?php foreach ($partsList as $p): ?>
                        <tr><td><?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$p['quantity'] ?></td><td>KSh <?= number_format((float)$p['unit_price'],2) ?></td><td>KSh <?= number_format((float)$p['total_price'],2) ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
                <a href="index.php" class="btn btn-secondary">Back</a>
                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-outline-secondary">Edit</a>
                <?php $cur = currentUser(); $adminRoles = ['admin','administrator','superuser']; if (in_array(strtolower($cur['role'] ?? ''), $adminRoles, true)): ?>
                    <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this service and return parts to stock?')">Delete</a>
                <?php endif; ?>
                <?php if (!empty($s['sale_id'])): ?>
                    <a href="../sales/view.php?id=<?= (int)$s['sale_id'] ?>" class="btn btn-outline-success">View Linked Sale #<?= (int)$s['sale_id'] ?></a>
                <?php else: ?>
                    <a href="create_sale.php?id=<?= (int)$s['id'] ?>" class="btn btn-success">Create Linked Sale</a>
                <?php endif; ?>

                <?php if (!empty($s['is_deleted'])): ?>
                    <a href="undo_delete.php?id=<?= (int)$s['id'] ?>" class="btn btn-warning">Undo Delete</a>
                <?php endif; ?>

                <?php if ($audits): ?>
                <h5 class="mt-4">Recent Audit Events</h5>
                <ul>
                    <?php foreach ($audits as $a): ?>
                        <li><?= htmlspecialchars($a['created_at'] ?? $a['id']) ?> - <?= htmlspecialchars($a['action'], ENT_QUOTES,'UTF-8') ?> by <?= htmlspecialchars($a['actor_id'] ?? 'system') ?>: <?= htmlspecialchars($a['details'] ?? $a['reference'] ?? '', ENT_QUOTES,'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>