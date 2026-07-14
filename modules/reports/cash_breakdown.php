<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
requireRole(['admin','administrator','superuser']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

// Sales received
$salesItems = $conn->prepare("SELECT id, sale_date, amount_paid, total_amount, payment_method FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC");
$salesItems->execute([$from, $to]);
$sales = $salesItems->fetchAll(PDO::FETCH_ASSOC);

// Purchases (paid where possible)
$purchases = [];
if (tableExists('purchases')) {
    $pFields = array_column($conn->query('DESCRIBE purchases')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (in_array('payment_status', $pFields, true)) {
        $pstmt = $conn->prepare("SELECT id, purchase_date, total_amount, payment_status FROM purchases WHERE DATE(purchase_date) BETWEEN ? AND ? AND payment_status = 'Paid' ORDER BY purchase_date DESC");
    } elseif (in_array('is_paid', $pFields, true) || in_array('paid', $pFields, true)) {
        $flag = in_array('is_paid', $pFields, true) ? 'is_paid' : 'paid';
        $pstmt = $conn->prepare("SELECT id, purchase_date, total_amount, $flag FROM purchases WHERE DATE(purchase_date) BETWEEN ? AND ? AND $flag = 1 ORDER BY purchase_date DESC");
    } else {
        $pstmt = $conn->prepare("SELECT id, purchase_date, total_amount FROM purchases WHERE DATE(purchase_date) BETWEEN ? AND ? ORDER BY purchase_date DESC");
    }
    $pstmt->execute([$from, $to]);
    $purchases = $pstmt->fetchAll(PDO::FETCH_ASSOC);
}

// Stock losses
$losses = [];
if (tableExists('stock_movements') && tableExists('products')) {
    $lossStmt = $conn->prepare("SELECT sm.*, p.product_name, p.buying_price FROM stock_movements sm JOIN products p ON p.id = sm.product_id WHERE sm.movement_type IN ('loss','damage','theft') AND DATE(sm.created_at) BETWEEN ? AND ? ORDER BY sm.created_at DESC");
    $lossStmt->execute([$from, $to]);
    $losses = $lossStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Refunds / returns
$refunds = [];
if (tableExists('refunds')) {
    $rstmt = $conn->prepare("SELECT id, created_at AS date, amount, reason FROM refunds WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
    $rstmt->execute([$from, $to]);
    $refunds = $rstmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (tableExists('returns')) {
    $rstmt = $conn->prepare("SELECT id, created_at AS date, refund_amount AS amount, reason FROM returns WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
    $rstmt->execute([$from, $to]);
    $refunds = $rstmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Cash Breakdown</h1></section>
    <section class="content">
        <div class="card"><div class="card-body">
            <form class="form-inline mb-3"><label class="mr-2">From</label><input type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES,'UTF-8') ?>" class="form-control mr-2"><label class="mr-2">To</label><input type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES,'UTF-8') ?>" class="form-control mr-2"><button class="btn btn-primary">Filter</button></form>

            <h5>Sales Received</h5>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Method</th><th>Paid</th><th>Total</th></tr></thead><tbody>
                <?php foreach ($sales as $s): ?><tr><td><?= htmlspecialchars($s['sale_date']) ?></td><td><?= htmlspecialchars($s['payment_method'] ?? '') ?></td><td>KSh <?= number_format((float)$s['amount_paid'],2) ?></td><td>KSh <?= number_format((float)$s['total_amount'],2) ?></td></tr><?php endforeach; ?>
            </tbody></table>

            <h5>Purchases (Paid)</h5>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($purchases as $p): ?><tr><td><?= htmlspecialchars($p['purchase_date'] ?? '') ?></td><td>KSh <?= number_format((float)$p['total_amount'],2) ?></td><td><?= htmlspecialchars($p['payment_status'] ?? ($p['is_paid'] ?? $p['paid'] ?? '')) ?></td></tr><?php endforeach; ?>
            </tbody></table>

            <h5>Inventory Losses</h5>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Unit Cost</th><th>Value</th><th>Note</th></tr></thead><tbody>
                <?php foreach ($losses as $l): ?><tr><td><?= htmlspecialchars($l['created_at'] ?? $l['date'] ?? '') ?></td><td><?= htmlspecialchars($l['product_name']) ?></td><td><?= (int)$l['quantity_change'] ?></td><td>KSh <?= number_format((float)$l['buying_price'],2) ?></td><td>KSh <?= number_format(abs((int)$l['quantity_change'] * (float)$l['buying_price']),2) ?></td><td><?= htmlspecialchars($l['note'] ?? '') ?></td></tr><?php endforeach; ?>
            </tbody></table>

            <h5>Refunds / Returns</h5>
            <table class="table table-sm"><thead><tr><th>Date</th><th>Amount</th><th>Reason</th></tr></thead><tbody>
                <?php foreach ($refunds as $r): ?><tr><td><?= htmlspecialchars($r['date'] ?? '') ?></td><td>KSh <?= number_format((float)$r['amount'],2) ?></td><td><?= htmlspecialchars($r['reason'] ?? '') ?></td></tr><?php endforeach; ?>
            </tbody></table>

        </div></div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
