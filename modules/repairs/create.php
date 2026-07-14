<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$technicians = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = getNextSequentialCode('repairs', 'repair_code', 'REP');
        $stmt = $conn->prepare('INSERT INTO repairs (repair_code, customer_id, device_type, imei_serial, description, technician_id, status, priority, estimated_cost, total_amount, payment_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $code,
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
            $_SESSION['user_id'] ?? null,
        ]);
        header('Location: index.php');
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
  <section class="content-header"><h1>Create Repair</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach ($customers as $c) echo '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Device Type</label><input name="device_type" class="form-control"></div>
            <div class="form-group"><label>IMEI / Serial</label><input name="imei_serial" class="form-control"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Technician</label><select name="technician_id" class="form-control"><option value="">-- None --</option><?php foreach ($technicians as $t) echo '<option value="' . (int)$t['id'] . '">' . htmlspecialchars($t['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Pending</option><option>In Progress</option><option>Completed</option></select></div>
            <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option>Low</option><option selected>Medium</option><option>High</option></select></div>
            <div class="form-group"><label>Estimated Cost</label><input name="estimated_cost" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Total Amount</label><input name="total_amount" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option>Pending</option><option>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Repair</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
