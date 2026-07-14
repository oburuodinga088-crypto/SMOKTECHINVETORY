<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$employees = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = getNextSequentialCode('payroll', 'payroll_code', 'PAY');
        $stmt = $conn->prepare('INSERT INTO payroll (payroll_code, employee_id, pay_period_start, pay_period_end, basic_salary, allowances, deductions, net_pay, payment_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $code,
            filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['pay_period_start'] ?? '') ?: null,
            trim($_POST['pay_period_end'] ?? '') ?: null,
            (float)($_POST['basic_salary'] ?? 0),
            (float)($_POST['allowances'] ?? 0),
            (float)($_POST['deductions'] ?? 0),
            (float)($_POST['net_pay'] ?? 0),
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
  <section class="content-header"><h1>Create Payroll</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Employee</label><select name="employee_id" class="form-control" required><?php foreach ($employees as $emp) echo '<option value="' . (int)$emp['id'] . '">' . htmlspecialchars($emp['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Period Start</label><input name="pay_period_start" type="date" class="form-control"></div>
            <div class="form-group"><label>Period End</label><input name="pay_period_end" type="date" class="form-control"></div>
            <div class="form-group"><label>Basic Salary</label><input name="basic_salary" type="number" step="0.01" class="form-control" value="0"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Allowances</label><input name="allowances" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Deductions</label><input name="deductions" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Net Pay</label><input name="net_pay" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option>Pending</option><option>Paid</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Payroll</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
