<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) { header('Location: index.php'); exit; }

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$users = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare('UPDATE projects SET project_name = ?, customer_id = ?, start_date = ?, end_date = ?, budget_amount = ?, amount_paid = ?, status = ?, description = ?, assigned_to = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['project_name'] ?? ''),
            filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            trim($_POST['start_date'] ?? '') ?: null,
            trim($_POST['end_date'] ?? '') ?: null,
            (float)($_POST['budget_amount'] ?? 0),
            (float)($_POST['amount_paid'] ?? 0),
            trim($_POST['status'] ?? 'Planning'),
            trim($_POST['description'] ?? ''),
            filter_var($_POST['assigned_to'] ?? null, FILTER_VALIDATE_INT) ?: null,
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
  <section class="content-header"><h1>Edit Project</h1></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div><?php endif; ?>
      <form method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Project Name</label><input name="project_name" class="form-control" value="<?= htmlspecialchars($project['project_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach ($customers as $c) echo '<option value="' . (int)$c['id'] . '"' . (($project['customer_id'] == $c['id']) ? ' selected' : '') . '>' . htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control" value="<?= htmlspecialchars($project['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="form-group"><label>End Date</label><input name="end_date" type="date" class="form-control" value="<?= htmlspecialchars($project['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Assigned To</label><select name="assigned_to" class="form-control"><option value="">-- None --</option><?php foreach ($users as $u) echo '<option value="' . (int)$u['id'] . '"' . (($project['assigned_to'] == $u['id']) ? ' selected' : '') . '>' . htmlspecialchars($u['fullname'], ENT_QUOTES, 'UTF-8') . '</option>'; ?></select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option <?= ($project['status'] === 'Planning') ? 'selected' : '' ?>>Planning</option><option <?= ($project['status'] === 'Running') ? 'selected' : '' ?>>Running</option><option <?= ($project['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option><option <?= ($project['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option></select></div>
            <div class="form-group"><label>Budget Amount</label><input name="budget_amount" type="number" step="0.01" class="form-control" value="<?= (float)($project['budget_amount'] ?? 0) ?>"></div>
            <div class="form-group"><label>Amount Paid</label><input name="amount_paid" type="number" step="0.01" class="form-control" value="<?= (float)($project['amount_paid'] ?? 0) ?>"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"><?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
          </div>
        </div>
        <button class="btn btn-primary">Save Changes</button> <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
      </form>
    </div></div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
