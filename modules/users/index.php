<?php
require '../../includes/auth.php';
requireRole(['Admin']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Cashier';
        if ($fullname === '' || $username === '' || strlen($password) < 6 || !in_array($role, ['Admin', 'Cashier', 'Manager'], true)) {
            $message = '<div class="alert alert-danger">Enter a name, username, valid role, and a password of at least six characters.</div>';
        } else {
            try {
                $stmt = $conn->prepare('INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$fullname, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $message = '<div class="alert alert-success">User created.</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">That username is already in use.</div>';
            }
        }
    }
}
$users = $conn->query('SELECT id, fullname, username, role, created_at FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><h1>Users</h1></section><section class="content"><div class="container-fluid"><?= $message ?><div class="card card-primary"><form method="post"><div class="card-body row"><div class="col-md-3 form-group"><label>Full name</label><input name="fullname" class="form-control" required></div><div class="col-md-2 form-group"><label>Username</label><input name="username" class="form-control" required></div><div class="col-md-2 form-group"><label>Password</label><input name="password" type="password" class="form-control" required></div><div class="col-md-2 form-group"><label>Role</label><select name="role" class="form-control"><option>Cashier</option><option>Manager</option><option>Admin</option></select></div><div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" name="action" value="add">Create</button></div></div></form></div><div class="card"><div class="card-body"><table class="table table-striped"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Created</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></section></div>
<?php include '../../includes/footer.php'; ?>
