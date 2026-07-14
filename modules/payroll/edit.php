<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM payroll WHERE id = ?');
$stmt->execute([$id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$payroll) { header('Location: index.php'); exit; }

$employees = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare('UPDATE payroll SET employee_id = ?, pay_period_start = ?, pay_period_end = ?, basic_salary = ?, allowances = ?, deductions = ?, net_pay = ?, payment_status = ?, notes = ? WHERE id = ?');
        $stmt->execute([
            filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['pay_period_start'] ?? '') ?: null,
            trim($_POST['pay_period_end'] ?? '') ?: null,
            (float)($_POST['basic_salary'] ?? 0),
            (float)($_POST['allowances'] ?? 0),
            (float)($_POST['deductions'] ?? 0),
            (float)($_POST['net_pay'] ?? 0),
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
  <section class="content-header"><h1>Edit Payroll</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Employee</label><select name="employee_id" class="form-control" required><?php foreach ($employees as $emp) echo '<option value="' . (int)$emp['id'] . '"' . (($payroll['employee_id'] == $emp['id']) ? ' selected' : '') . '>' . htmlspecialchars($emp['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Period Start</label><input name="pay_period_start" type="date" class="form-control" value="<?= htmlspecialchars($payroll['pay_period_start'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Period End</label><input name="pay_period_end" type="date" class="form-control" value="<?= htmlspecialchars($payroll['pay_period_end'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>Basic Salary</label><input name="basic_salary" type="number" step="0.01" class="form-control" value="<?= (float)($payroll['basic_salary'] ?? 0) ?>"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Allowances</label><input name="allowances" type="number" step="0.01" class="form-control" value="<?= (float)($payroll['allowances'] ?? 0) ?>"></div>
            <div class="form-group"><label>Deductions</label><input name="deductions" type="number" step="0.01" class="form-control" value="<?= (float)($payroll['deductions'] ?? 0) ?>"></div>
            <div class="form-group"><label>Net Pay</label><input name="net_pay" type="number" step="0.01" class="form-control" value="<?= (float)($payroll['net_pay'] ?? 0) ?>"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option <?= ($payroll['payment_status'] === 'Pending') ? 'selected' : '' ?>>Pending</option><option <?= ($payroll['payment_status'] === 'Paid') ? 'selected' : '' ?>>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"><?= htmlspecialchars($payroll['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
