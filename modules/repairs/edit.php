<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM repairs WHERE id = ?');
$stmt->execute([$id]);
$repair = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$repair) { header('Location: index.php'); exit; }

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$technicians = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare('UPDATE repairs SET customer_id = ?, device_type = ?, imei_serial = ?, description = ?, technician_id = ?, status = ?, priority = ?, estimated_cost = ?, total_amount = ?, payment_status = ?, notes = ? WHERE id = ?');
        $stmt->execute([
            filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['device_type'] ?? ''),
            trim($_POST['imei_serial'] ?? ''),
            trim($_POST['description'] ?? ''),
            filter_var($_POST['technician_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['status'] ?? 'Pending'),
            trim($_POST['priority'] ?? 'Medium'),
            (float)($_POST['estimated_cost'] ?? 0),
            (float)($_POST['total_amount'] ?? 0),
            trim($_POST['payment_status'] ?? 'Pending'),
            trim($_POST['notes'] ?? ''),
            $id,
        ]);
        header('Location: view.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Edit Repair</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach ($customers as $c) echo '<option value="' . (int)$c['id'] . '"' . (($repair['customer_id'] == $c['id']) ? ' selected' : '') . '>' . htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Device Type</label><input name="device_type" class="form-control" value="<?= htmlspecialchars($repair['device_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>IMEI / Serial</label><input name="imei_serial" class="form-control" value="<?= htmlspecialchars($repair['imei_serial'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"><?= htmlspecialchars($repair['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Technician</label><select name="technician_id" class="form-control"><option value="">-- None --</option><?php foreach ($technicians as $t) echo '<option value="' . (int)$t['id'] . '"' . (($repair['technician_id'] == $t['id']) ? ' selected' : '') . '>' . htmlspecialchars($t['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option <?= ($repair['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option><option <?= ($repair['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option><option <?= ($repair['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option></select></div>
            <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option <?= ($repair['priority'] === 'Low') ? 'selected' : '' ?>>Low</option><option <?= ($repair['priority'] === 'Medium' || !$repair['priority']) ? 'selected' : '' ?>>Medium</option><option <?= ($repair['priority'] === 'High') ? 'selected' : '' ?>>High</option></select></div>
            <div class="form-group"><label>Estimated Cost</label><input name="estimated_cost" type="number" step="0.01" class="form-control" value="<?= (float)($repair['estimated_cost'] ?? 0) ?>"></div>
            <div class="form-group"><label>Total Amount</label><input name="total_amount" type="number" step="0.01" class="form-control" value="<?= (float)($repair['total_amount'] ?? 0) ?>"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option <?= ($repair['payment_status'] === 'Pending') ? 'selected' : '' ?>>Pending</option><option <?= ($repair['payment_status'] === 'Paid') ? 'selected' : '' ?>>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"><?= htmlspecialchars($repair['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
