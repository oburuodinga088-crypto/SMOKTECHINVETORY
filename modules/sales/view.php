<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$sale = $conn->prepare('SELECT s.*, c.customer_name, u.fullname AS cashier FROM sales s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN users u ON u.id = s.cashier_id WHERE s.id = ?');
$sale->execute([$id]);
$s = $sale->fetch(PDO::FETCH_ASSOC);
if (!$s) { header('Location: index.php'); exit; }

$items = $conn->prepare('SELECT si.*, p.product_name FROM sale_items si LEFT JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?');
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Sale #<?= (int)$id ?></h1></section>
    <section class="content">
        <div class="card"><div class="card-body">
            <p><strong>Date:</strong> <?= htmlspecialchars($s['sale_date'], ENT_QUOTES,'UTF-8') ?></p>
            <p><strong>Cashier:</strong> <?= htmlspecialchars($s['cashier'] ?? '-', ENT_QUOTES,'UTF-8') ?></p>
            <p><strong>Customer:</strong> <?= htmlspecialchars($s['customer_name'] ?? '-', ENT_QUOTES,'UTF-8') ?></p>
            <p><strong>Payment:</strong> <?= htmlspecialchars($s['payment_method'] ?? '-', ENT_QUOTES,'UTF-8') ?> — <?= htmlspecialchars($s['payment_status'] ?? '-', ENT_QUOTES,'UTF-8') ?></p>
            <h5>Items</h5>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>
                <?php foreach ($items as $it): ?>
                    <tr><td><?= htmlspecialchars($it['product_name'] ?? '—', ENT_QUOTES,'UTF-8') ?></td><td><?= (int)$it['quantity'] ?></td><td>KSh <?= number_format((float)$it['selling_price'],2) ?></td><td>KSh <?= number_format((float)$it['selling_price'] * (int)$it['quantity'],2) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <p><strong>Total:</strong> KSh <?= number_format((float)$s['total_amount'],2) ?></p>
            <a href="../../dashboard.php" class="btn btn-secondary">Back</a>
        </div></div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
