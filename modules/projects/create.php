<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$users = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = getNextSequentialCode('projects', 'project_code', 'PRJ');
        $stmt = $conn->prepare('INSERT INTO projects (project_code, project_name, customer_id, start_date, end_date, budget_amount, amount_paid, status, description, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $code,
            trim($_POST['project_name'] ?? ''),
            filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['start_date'] ?? '') ?: null,
            trim($_POST['end_date'] ?? '') ?: null,
            (float)($_POST['budget_amount'] ?? 0),
            (float)($_POST['amount_paid'] ?? 0),
            trim($_POST['status'] ?? 'Planning'),
            trim($_POST['description'] ?? ''),
            filter_var($_POST['assigned_to'] ?? null, FILTER_VALIDATE_INT) ?: null,
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
  <section class="content-header"><h1>Create Project</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Project Name</label><input name="project_name" class="form-control" required></div>
            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach ($customers as $c) echo '<option value="' . (int)$c['id'] . '">' . htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control"></div>
            <div class="form-group"><label>End Date</label><input name="end_date" type="date" class="form-control"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Assigned To</label><select name="assigned_to" class="form-control"><option value="">-- None --</option><?php foreach ($users as $u) echo '<option value="' . (int)$u['id'] . '">' . htmlspecialchars($u['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Planning</option><option>Running</option><option>Completed</option><option>Cancelled</option></select></div>
            <div class="form-group"><label>Budget Amount</label><input name="budget_amount" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Amount Paid</label><input name="amount_paid" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Project</button> <a href="index.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
