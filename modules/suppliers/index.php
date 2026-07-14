<?php
require '../../includes/auth.php';
requireLogin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['supplier_name'] ?? '');
        if ($name === '') {
            $message = '<div class="alert alert-danger">Supplier name is required.</div>';
        } else {
            $stmt = $conn->prepare('INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, trim($_POST['contact_person'] ?? '') ?: null, trim($_POST['phone'] ?? '') ?: null, trim($_POST['email'] ?? '') ?: null, trim($_POST['address'] ?? '') ?: null]);
            $message = '<div class="alert alert-success">Supplier added.</div>';
        }
    } elseif ($action === 'delete' && ($id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))) {
        try {
            $conn->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
            $message = '<div class="alert alert-success">Supplier deleted.</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-warning">This supplier is used by purchases and cannot be deleted.</div>';
        }
    }
}
$suppliers = $conn->query('SELECT s.*, COUNT(p.id) AS purchase_count FROM suppliers s LEFT JOIN purchases p ON p.supplier_id = s.id GROUP BY s.id, s.supplier_name, s.contact_person, s.phone, s.email, s.address, s.created_at ORDER BY s.supplier_name')->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><h1>Suppliers</h1></section><section class="content"><div class="container-fluid"><?= $message ?><div class="card card-primary"><form method="post"><div class="card-body row"><div class="col-md-3 form-group"><label>Supplier name</label><input name="supplier_name" class="form-control" required></div><div class="col-md-2 form-group"><label>Contact person</label><input name="contact_person" class="form-control"></div><div class="col-md-2 form-group"><label>Phone</label><input name="phone" class="form-control"></div><div class="col-md-2 form-group"><label>Email</label><input name="email" type="email" class="form-control"></div><div class="col-md-2 form-group"><label>Address</label><input name="address" class="form-control"></div><div class="col-md-1 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" name="action" value="add">Add</button></div></div></form></div><div class="card"><div class="card-body"><table class="table table-striped"><thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th>Purchases</th><th></th></tr></thead><tbody><?php foreach ($suppliers as $supplier): ?><tr><td><?= htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($supplier['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($supplier['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($supplier['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$supplier['purchase_count'] ?></td><td><form method="post" onsubmit="return confirm('Delete this supplier?')"><input type="hidden" name="id" value="<?= (int)$supplier['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="delete">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></section></div>
<?php include '../../includes/footer.php'; ?>
