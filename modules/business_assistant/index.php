<?php
/**
 * Read-only decision support built from the ERP's operational data.
 * It intentionally contains no INSERT, UPDATE, DELETE, or stock-adjustment actions.
 */
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

$period = $_GET['period'] ?? 'monthly';
$periods = ['daily', 'weekly', 'monthly'];
if (!in_array($period, $periods, true)) {
    $period = 'monthly';
}

switch ($period) {
    case 'daily':
        $from = date('Y-m-d');
        $label = 'Today';
        break;
    case 'weekly':
        $from = date('Y-m-d', strtotime('monday this week'));
        $label = 'This week';
        break;
    default:
        $from = date('Y-m-01');
        $label = 'This month';
}
$to = date('Y-m-d');

function assistantValue(PDO $pdo, string $sql, array $params = []): float
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (float) $statement->fetchColumn();
}

function assistantRows(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

$metrics = ['revenue' => 0.0, 'gross_profit' => 0.0, 'expenses' => 0.0, 'cash_in' => 0.0, 'outstanding' => 0.0, 'sales_count' => 0];
$topProducts = $slowProducts = $lowStock = $topCustomers = $topEmployees = $topSuppliers = [];
$repairSummary = ['open' => 0, 'completed' => 0, 'average_days' => null];
$errors = [];

try {
    $metrics['revenue'] = assistantValue($conn, 'SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?', [$from, $to]);
    $metrics['sales_count'] = (int) assistantValue($conn, 'SELECT COUNT(*) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?', [$from, $to]);
    $metrics['cash_in'] = assistantValue($conn, 'SELECT COALESCE(SUM(amount_paid), 0) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?', [$from, $to]);
    $metrics['outstanding'] = assistantValue($conn, "SELECT COALESCE(SUM(balance), 0) FROM sales WHERE payment_status = 'Pending' AND balance > 0");
    if (tableExists('sale_items')) {
        $metrics['gross_profit'] = assistantValue($conn, 'SELECT COALESCE(SUM((si.selling_price - si.buying_price) * si.quantity), 0) FROM sale_items si INNER JOIN sales s ON s.id = si.sale_id WHERE DATE(s.sale_date) BETWEEN ? AND ?', [$from, $to]);
        $topProducts = assistantRows($conn, 'SELECT p.product_name, SUM(si.quantity) AS quantity, SUM(si.quantity * si.selling_price) AS revenue FROM sale_items si INNER JOIN sales s ON s.id = si.sale_id INNER JOIN products p ON p.id = si.product_id WHERE DATE(s.sale_date) BETWEEN ? AND ? GROUP BY p.id, p.product_name ORDER BY quantity DESC LIMIT 5', [$from, $to]);
        $slowProducts = assistantRows($conn, 'SELECT p.product_name, p.current_stock, COALESCE(SUM(CASE WHEN DATE(s.sale_date) BETWEEN ? AND ? THEN si.quantity ELSE 0 END), 0) AS quantity FROM products p LEFT JOIN sale_items si ON si.product_id = p.id LEFT JOIN sales s ON s.id = si.sale_id GROUP BY p.id, p.product_name, p.current_stock HAVING quantity = 0 AND p.current_stock > 0 ORDER BY p.current_stock DESC LIMIT 5', [$from, $to]);
    }
    $topCustomers = assistantRows($conn, 'SELECT c.customer_name, SUM(s.total_amount) AS total_spent, COUNT(s.id) AS visits FROM sales s INNER JOIN customers c ON c.id = s.customer_id WHERE DATE(s.sale_date) BETWEEN ? AND ? GROUP BY c.id, c.customer_name ORDER BY total_spent DESC LIMIT 5', [$from, $to]);
    $topEmployees = assistantRows($conn, 'SELECT u.fullname, SUM(s.total_amount) AS revenue, COUNT(s.id) AS transactions FROM sales s INNER JOIN users u ON u.id = s.cashier_id WHERE DATE(s.sale_date) BETWEEN ? AND ? GROUP BY u.id, u.fullname ORDER BY revenue DESC LIMIT 5', [$from, $to]);
    $lowStock = assistantRows($conn, 'SELECT product_name, current_stock, reorder_level, supplier_id FROM products WHERE current_stock <= reorder_level ORDER BY (current_stock = 0) DESC, current_stock ASC LIMIT 10');
    $topSuppliers = assistantRows($conn, 'SELECT sp.supplier_name, COUNT(p.id) AS purchases, COALESCE(SUM(p.total_amount), 0) AS value FROM suppliers sp LEFT JOIN purchases p ON p.supplier_id = sp.id AND DATE(p.purchase_date) BETWEEN ? AND ? GROUP BY sp.id, sp.supplier_name ORDER BY value DESC LIMIT 5', [$from, $to]);
    if (tableExists('expenses')) {
        $metrics['expenses'] = assistantValue($conn, 'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?', [$from, $to]);
    }
    if (tableExists('repairs')) {
        $repairSummary['open'] = (int) assistantValue($conn, "SELECT COUNT(*) FROM repairs WHERE status <> 'Completed'");
        $repairSummary['completed'] = (int) assistantValue($conn, "SELECT COUNT(*) FROM repairs WHERE status = 'Completed' AND DATE(completion_date) BETWEEN ? AND ?", [$from, $to]);
        $average = assistantValue($conn, "SELECT COALESCE(AVG(TIMESTAMPDIFF(DAY, start_date, completion_date)), 0) FROM repairs WHERE status = 'Completed' AND completion_date IS NOT NULL AND DATE(completion_date) BETWEEN ? AND ?", [$from, $to]);
        $repairSummary['average_days'] = $average > 0 ? $average : null;
    }
} catch (Throwable $exception) {
    error_log('Business assistant query failed: ' . $exception->getMessage());
    $errors[] = 'Some insights could not be calculated because the corresponding data is unavailable.';
}

$netProfit = $metrics['gross_profit'] - $metrics['expenses'];
$margin = $metrics['revenue'] > 0 ? ($metrics['gross_profit'] / $metrics['revenue']) * 100 : 0;
$insights = [];
if ($metrics['revenue'] > 0) {
    $insights[] = ['success', 'Sales performance', sprintf('%s revenue is KSh %s across %d transactions, with a gross margin of %.1f%%.', $label, money($metrics['revenue']), $metrics['sales_count'], $margin)];
}
if ($metrics['outstanding'] > 0) {
    $insights[] = ['warning', 'Cash-flow follow-up', 'KSh ' . money($metrics['outstanding']) . ' remains outstanding. Prioritize customer follow-up before extending additional credit.'];
}
if ($metrics['expenses'] > $metrics['gross_profit'] && $metrics['expenses'] > 0) {
    $insights[] = ['danger', 'Profitability risk', 'Recorded expenses exceed gross profit for the selected period. Review discretionary expenses and pricing.'];
}
if ($lowStock) {
    $insights[] = ['warning', 'Smart reorder suggestion', count($lowStock) . ' product(s) are at or below their reorder level. Review the reorder list before stock-outs affect sales.'];
}
if ($slowProducts) {
    $insights[] = ['info', 'Dead-stock watch', count($slowProducts) . ' stocked product(s) had no sales in the selected period. Consider promotion, bundling, or a purchasing pause.'];
}
if ($repairSummary['open'] > 0) {
    $insights[] = ['info', 'Repair turnaround', $repairSummary['open'] . ' repair job(s) are open' . ($repairSummary['average_days'] ? '; completed jobs average ' . number_format($repairSummary['average_days'], 1) . ' days.' : '.')];
}
if (!$insights) {
    $insights[] = ['info', 'Data needed', 'Record sales, expenses, and stock activity to unlock recommendations for this period.'];
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><div><h1><i class="fas fa-robot"></i> Business Assistant</h1><p class="text-muted mb-0">Read-only recommendations based on recorded ERP data. No data is changed automatically.</p></div></div></div></section>
    <section class="content"><div class="container-fluid">
        <div class="btn-group mb-3" role="group" aria-label="Analysis period">
            <?php foreach ($periods as $option): ?><a class="btn btn-<?= $period === $option ? 'primary' : 'outline-primary' ?>" href="?period=<?= e($option) ?>"><?= ucfirst($option) ?></a><?php endforeach; ?>
        </div>
        <?php foreach ($errors as $error): ?><div class="alert alert-warning"><?= e($error) ?></div><?php endforeach; ?>
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= money($metrics['revenue']) ?></h3><p><?= e($label) ?> revenue</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>KSh <?= money($metrics['gross_profit']) ?></h3><p>Gross profit</p></div><div class="icon"><i class="fas fa-coins"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>KSh <?= money($metrics['expenses']) ?></h3><p>Expenses</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>KSh <?= money($netProfit) ?></h3><p>Estimated net profit</p></div><div class="icon"><i class="fas fa-wallet"></i></div></div></div>
        </div>
        <div class="card card-primary"><div class="card-header"><h3 class="card-title">Recommendations</h3></div><div class="card-body"><div class="row"><?php foreach ($insights as [$type, $title, $body]): ?><div class="col-md-6"><div class="alert alert-<?= e($type) ?>"><h5><?= e($title) ?></h5><p class="mb-0"><?= e($body) ?></p></div></div><?php endforeach; ?></div></div></div>
        <div class="row">
            <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Best-selling products</h3></div><div class="card-body"><?php if ($topProducts): ?><table class="table table-sm"><thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead><tbody><?php foreach ($topProducts as $row): ?><tr><td><?= e($row['product_name']) ?></td><td><?= (int)$row['quantity'] ?></td><td>KSh <?= money($row['revenue']) ?></td></tr><?php endforeach; ?></tbody></table><?php else: ?><p class="text-muted mb-0">No product sales for this period.</p><?php endif; ?></div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Reorder and slow-moving stock</h3></div><div class="card-body"><table class="table table-sm"><thead><tr><th>Product</th><th>Stock</th><th>Signal</th></tr></thead><tbody><?php foreach ($lowStock as $row): ?><tr><td><?= e($row['product_name']) ?></td><td><?= (int)$row['current_stock'] ?>/<?= (int)$row['reorder_level'] ?></td><td><span class="badge badge-warning">Reorder</span></td></tr><?php endforeach; ?><?php foreach ($slowProducts as $row): ?><tr><td><?= e($row['product_name']) ?></td><td><?= (int)$row['current_stock'] ?></td><td><span class="badge badge-secondary">No sales</span></td></tr><?php endforeach; ?><?php if (!$lowStock && !$slowProducts): ?><tr><td colspan="3" class="text-muted">No stock alerts.</td></tr><?php endif; ?></tbody></table></div></div></div>
        </div>
        <div class="row">
            <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">Customer insights</h3></div><div class="card-body"><ol class="pl-3 mb-0"><?php foreach ($topCustomers as $row): ?><li><?= e($row['customer_name']) ?> — KSh <?= money($row['total_spent']) ?></li><?php endforeach; ?><?php if (!$topCustomers): ?><li class="text-muted">No customer-linked sales.</li><?php endif; ?></ol></div></div></div>
            <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">Employee performance</h3></div><div class="card-body"><ol class="pl-3 mb-0"><?php foreach ($topEmployees as $row): ?><li><?= e($row['fullname']) ?> — KSh <?= money($row['revenue']) ?></li><?php endforeach; ?><?php if (!$topEmployees): ?><li class="text-muted">No cashier sales.</li><?php endif; ?></ol></div></div></div>
            <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">Supplier activity</h3></div><div class="card-body"><ol class="pl-3 mb-0"><?php foreach ($topSuppliers as $row): ?><li><?= e($row['supplier_name']) ?> — KSh <?= money($row['value']) ?></li><?php endforeach; ?><?php if (!$topSuppliers): ?><li class="text-muted">No purchases in this period.</li><?php endif; ?></ol></div></div></div>
        </div>
    </div></section>
</div>
<?php include '../../includes/footer.php'; ?>
