<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = trim($_POST['confirm'] ?? '');
    $confirm2 = trim($_POST['confirm2'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session form has expired. Please try again.';
    } elseif ($confirm !== 'RESET') {
        $error = 'Please type RESET to confirm.';
    } elseif ($confirm2 !== 'RESET EVERYTHING') {
        $error = 'Please type RESET EVERYTHING for the second confirmation step.';
    } elseif ($password === '') {
        $error = 'Admin password is required.';
    } else {
        try {
            $result = resetSystemData($password, (int)($_SESSION['user_id'] ?? 0));
            $message = 'System data reset completed successfully. Backup created: ' . ($result['backup_path'] ?? 'available in exports/backups');
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Admin Reset</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Reset System Data</h3></div>
      <div class="card-body">
        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <div class="alert alert-danger" style="font-size: 1rem; line-height: 1.6;">
          <h5><i class="fas fa-exclamation-triangle"></i> WARNING: This will permanently clear the current operational data.</h5>
          <p><strong>It will delete or reset:</strong></p>
          <ul>
            <li>sales and sale items</li>
            <li>purchase orders and delivery records</li>
            <li>expenses, invoices, quotations, repairs, services and projects</li>
            <li>supplier payments, cash book entries, audit logs and stock movements</li>
            <li>customer balances, sales balances, invoice balances, cash-like balances and product stock values</li>
          </ul>
          <p><strong>After reset:</strong> stock values, cash-like balances and key transaction records will be set to zero or kept non-negative.</p>
          <p>This requires the logged-in admin password plus the confirmation phrases <strong>RESET</strong> and <strong>RESET EVERYTHING</strong>.</p>
        </div>
        <form method="post">
          <?= csrfField() ?>
          <div class="form-group">
            <label>Admin Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Type RESET to confirm</label>
            <input type="text" name="confirm" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Type RESET EVERYTHING for final confirmation</label>
            <input type="text" name="confirm2" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-danger">Reset System</button>
        </form>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
