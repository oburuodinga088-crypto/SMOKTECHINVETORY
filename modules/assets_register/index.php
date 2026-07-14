<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$conn = getDB();

if (!tableExists('asset_register')) {
    $conn->exec("CREATE TABLE asset_register (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_name VARCHAR(150) NOT NULL,
        asset_type VARCHAR(100) DEFAULT NULL,
        purchase_date DATE DEFAULT NULL,
        purchase_cost DECIMAL(12,2) DEFAULT 0,
        current_value DECIMAL(12,2) DEFAULT 0,
        depreciation DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Active',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('INSERT INTO asset_register (asset_name, asset_type, purchase_date, purchase_cost, current_value, depreciation, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        trim($_POST['asset_name'] ?? ''),
        trim($_POST['asset_type'] ?? ''),
        trim($_POST['purchase_date'] ?? '') ?: null,
        (float)($_POST['purchase_cost'] ?? 0),
        (float)($_POST['current_value'] ?? 0),
        (float)($_POST['depreciation'] ?? 0),
        trim($_POST['status'] ?? 'Active'),
        trim($_POST['notes'] ?? ''),
        $_SESSION['user_id'] ?? null,
    ]);
    header('Location: index.php');
    exit;
}

$assets = $conn->query('SELECT * FROM asset_register ORDER BY purchase_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Asset Register</h1></section>
  <section class="content">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Add Asset</h3></div>
      <div class="card-body">
        <form method="post" class="mb-4">
          <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Name</label><input name="asset_name" class="form-control" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Type</label><input name="asset_type" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Purchase Date</label><input name="purchase_date" type="date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Purchase Cost</label><input name="purchase_cost" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Current Value</label><input name="current_value" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-1"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary">Add</button></div></div>
          </div>
          <div class="row">
            <div class="col-md-2"><div class="form-group"><label>Depreciation</label><input name="depreciation" type="number" step="0.01" class="form-control" value="0"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option>Active</option><option>Disposed</option><option>Maintenance</option></select></div></div>
            <div class="col-md-8"><div class="form-group"><label>Notes</label><input name="notes" class="form-control"></div></div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Registered Assets</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Name</th><th>Type</th><th>Purchase Cost</th><th>Current Value</th><th>Depreciation</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($assets as $asset): ?>
            <tr>
              <td><?= htmlspecialchars($asset['asset_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($asset['asset_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td>KSh <?= number_format((float)($asset['purchase_cost'] ?? 0), 2) ?></td>
              <td>KSh <?= number_format((float)($asset['current_value'] ?? 0), 2) ?></td>
              <td>KSh <?= number_format((float)($asset['depreciation'] ?? 0), 2) ?></td>
              <td><?= htmlspecialchars($asset['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
