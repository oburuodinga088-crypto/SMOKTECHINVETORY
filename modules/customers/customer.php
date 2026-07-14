<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = '<div class="alert alert-danger">Your session form has expired. Please try again.</div>';
    } elseif ($action === 'add') {
        $name = trim($_POST['customer_name'] ?? '');
        if ($name === '') {
            $message = '<div class="alert alert-danger">Customer name is required.</div>';
        } else {
            $stmt = $conn->prepare('INSERT INTO customers (customer_name, phone, email, address) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, trim($_POST['phone'] ?? '') ?: null, trim($_POST['email'] ?? '') ?: null, trim($_POST['address'] ?? '') ?: null]);
            $message = '<div class="alert alert-success">Customer added.</div>';
        }
    } elseif ($action === 'collect_payment') {
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$customerId || $amount === false || $amount <= 0) {
            $message = '<div class="alert alert-danger">Enter a valid payment amount.</div>';
        } else {
            try {
                $conn->beginTransaction();
                $sales = $conn->prepare("SELECT id, amount_paid, balance FROM sales WHERE customer_id = ? AND payment_status = 'Pending' AND balance > 0 ORDER BY sale_date ASC, id ASC FOR UPDATE");
                $sales->execute([$customerId]);
                $remaining = $amount;
                $applied = 0.0;
                $update = $conn->prepare("UPDATE sales SET amount_paid = amount_paid + ?, balance = balance - ?, payment_status = CASE WHEN balance - ? <= 0 THEN 'Paid' ELSE 'Pending' END WHERE id = ?");
                foreach ($sales->fetchAll(PDO::FETCH_ASSOC) as $sale) {
                    if ($remaining <= 0) break;
                    $payment = min($remaining, (float)$sale['balance']);
                    $update->execute([$payment, $payment, $payment, $sale['id']]);
                    $remaining -= $payment;
                    $applied += $payment;
                }
                if ($applied <= 0) {
                    throw new RuntimeException('This customer has no outstanding balance.');
                }
                $conn->commit();
                $extra = $remaining > 0 ? ' KSh ' . number_format($remaining, 2) . ' was not applied because the balance is fully cleared.' : '';
            $message = '<div class="alert alert-success">KSh ' . number_format($applied, 2) . ' debt payment recorded.' . $extra . '</div>';
            } catch (Throwable $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $message = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
    } elseif ($action === 'delete' && ($id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))) {
        try {
            $stmt = $conn->prepare('DELETE FROM customers WHERE id = ?');
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">Customer deleted.</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-warning">Customers with sales cannot be deleted.</div>';
        }
    }
}

$customers = $conn->query("SELECT c.*, COUNT(s.id) AS order_count, COALESCE(SUM(s.total_amount), 0) AS total_spent, COALESCE(SUM(CASE WHEN s.payment_status = 'Pending' THEN s.balance ELSE 0 END), 0) AS outstanding_balance FROM customers c LEFT JOIN sales s ON s.customer_id = c.id GROUP BY c.id, c.customer_name, c.phone, c.email, c.address, c.created_at ORDER BY c.customer_name")->fetchAll(PDO::FETCH_ASSOC);
include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Customers</h1></div></section>
    <section class="content"><div class="container-fluid">
        <?= $message ?>
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Customer</h3></div>
            <form method="post"><?= csrfField() ?><div class="card-body row">
                <div class="col-md-3 form-group"><label>Name</label><input name="customer_name" class="form-control" required></div>
                <div class="col-md-2 form-group"><label>Phone</label><input name="phone" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Email</label><input name="email" type="email" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Address</label><input name="address" class="form-control"></div>
                <div class="col-md-1 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" name="action" value="add">Add</button></div>
            </div></form>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Customer Accounts</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover">
                    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Outstanding</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$customer['order_count'] ?></td>
                            <td>KSh <?= number_format((float)$customer['total_spent'], 2) ?></td>
                            <td><?= (float)$customer['outstanding_balance'] > 0 ? '<span class="badge badge-warning">KSh ' . number_format((float)$customer['outstanding_balance'], 2) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-nowrap">
                                <?php if ((float)$customer['outstanding_balance'] > 0): ?>
                                    <form method="post" class="form-inline d-inline-flex mr-1">
                                        <?= csrfField() ?><input type="hidden" name="action" value="collect_payment"><input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                                        <div class="input-group input-group-sm"><input type="number" name="amount" min="0.01" step="0.01" max="<?= htmlspecialchars($customer['outstanding_balance'], ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Amount" required><div class="input-group-append"><button class="btn btn-success">Pay</button></div></div>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this customer?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$customer['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="delete"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div></section>
</div>
<?php include '../../includes/footer.php'; ?>
