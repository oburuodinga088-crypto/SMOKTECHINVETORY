<?php
require 'includes/auth.php';
require 'includes/functions.php';

requireLogin();
ensureErpTables();

function getCount($conn, $table) {
    try { return $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn(); }
    catch (Exception $e) { return 0; }
}

// Primary dashboard metrics
$productCount      = getCount($conn, "products");
$categoryCount     = getCount($conn, "categories");
$salesCount        = getCount($conn, "sales");
$customerCount     = getCount($conn, "customers");
$employeeCount     = getCount($conn, "users");
$lowStockCount     = $conn->query("SELECT COUNT(*) FROM products WHERE current_stock <= reorder_level")->fetchColumn();
$outOfStockCount   = $conn->query("SELECT COUNT(*) FROM products WHERE current_stock = 0")->fetchColumn();

$today = date('Y-m-d');
$dailySales = 0;
$todayProfit = 0;
$todayExpenses = 0;
$cashAvailable = 0;
$netProfit = 0;
$pendingRepairs = 0;
$projectsRunning = 0;
$extraCashEarned = 0;
$topCustomers = [];
$topSalesperson = [];
$topTechnician = [];
$fastMovingProducts = [];
$slowMovingProducts = [];
$recentSales = [];
$lowStock = [];
$outstanding = 0;
$todayServices = 0;
$serviceRevenue = 0;
$serviceProfit = 0;
$pendingServices = 0;
$completedServices = 0;
$quotationCount = 0;
$invoiceCount = 0;
$purchaseOrderCount = 0;
$deliveryCount = 0;

try {
    $lowStock = $conn->query("SELECT product_name, current_stock FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $outstanding = (float) $conn->query("SELECT COALESCE(SUM(CASE WHEN payment_status = 'Pending' THEN balance ELSE 0 END), 0) FROM sales")->fetchColumn();
} catch (Throwable $e) {
    // allow dashboard to render even if summary queries fail
}

try {
    $dailyStmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = ?");
    $dailyStmt->execute([$today]);
    $dailySales = (float) $dailyStmt->fetchColumn();

    $profitStmt = $conn->prepare(
        "SELECT COALESCE(SUM((si.selling_price - si.buying_price) * si.quantity), 0) FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        WHERE DATE(s.sale_date) = ?"
    );
    $profitStmt->execute([$today]);
    $todayProfit = (float) $profitStmt->fetchColumn();

    $expenseStmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE DATE(purchase_date) = ?");
    $expenseStmt->execute([$today]);
    $todayExpenses = (float) $expenseStmt->fetchColumn();

    // Calculate cash available from the cash book when present, and fall back to legacy accounting tables otherwise.
    $cashAvailable = getCashAvailableBalance($conn);
    $salesReceived = 0; $ledgerIn = 0; $ledgerOut = 0; $mpesaTotal = 0; $paymentsIn = 0; $paymentsOut = 0; $purchasesOut = 0; $stockLosses = 0; $refunds = 0; $overchargeTotal = 0; $overpaidSales = [];

    $netProfit = $todayProfit - $todayExpenses;
    // Extra cash earned comes from customer overpayments (amount_paid > total_amount).
    $extraCashEarned = $overchargeTotal ?? 0.0;

    $recentSales = $conn->query(
        "SELECT s.sale_date, s.total_amount, s.payment_status, u.fullname FROM sales s
        LEFT JOIN users u ON u.id = s.cashier_id
        ORDER BY s.sale_date DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (tableExists('repairs')) {
        $pendingRepairs = $conn->query("SELECT COUNT(*) FROM repairs WHERE status <> 'Completed'")->fetchColumn();
        $topTechnician = $conn->query(
            "SELECT u.fullname, COUNT(r.id) AS completed_repairs FROM users u
            LEFT JOIN repairs r ON r.technician_id = u.id
            WHERE r.status = 'Completed'
            GROUP BY u.id ORDER BY completed_repairs DESC LIMIT 1"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    if (tableExists('projects')) {
        $projectsRunning = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'Running'")->fetchColumn();
    }

    $topCustomers = $conn->query(
        "SELECT c.customer_name, COALESCE(SUM(s.total_amount), 0) AS total_spent FROM customers c
        LEFT JOIN sales s ON s.customer_id = c.id
        GROUP BY c.id ORDER BY total_spent DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $topSalesperson = $conn->query(
        "SELECT u.fullname, COALESCE(SUM(s.total_amount), 0) AS sales_total FROM users u
        LEFT JOIN sales s ON s.cashier_id = u.id
        GROUP BY u.id ORDER BY sales_total DESC LIMIT 1"
    )->fetchAll(PDO::FETCH_ASSOC);

    $fastMovingProducts = $conn->query(
        "SELECT p.product_name, COALESCE(SUM(si.quantity), 0) AS sold_qty FROM sale_items si
        JOIN products p ON p.id = si.product_id
        JOIN sales s ON s.id = si.sale_id
        WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id ORDER BY sold_qty DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $slowMovingProducts = $conn->query(
        "SELECT p.product_name, COALESCE(SUM(si.quantity), 0) AS sold_qty FROM products p
        LEFT JOIN sale_items si ON si.product_id = p.id
        LEFT JOIN sales s ON s.id = si.sale_id AND s.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id ORDER BY sold_qty ASC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Keep the dashboard available if metrics fail.
}

// Services metrics (optional - only if table exists)
try {
    if (tableExists('quotations')) {
        $quotationCount = (int) $conn->query('SELECT COUNT(*) FROM quotations')->fetchColumn();
    }

    if (tableExists('invoices')) {
        $invoiceCount = (int) $conn->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
    }

    if (tableExists('purchase_orders')) {
        $purchaseOrderCount = (int) $conn->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn();
    }

    if (tableExists('deliveries')) {
        $deliveryCount = (int) $conn->query('SELECT COUNT(*) FROM deliveries')->fetchColumn();
    }

    if (tableExists('services')) {
        $todayServices = $conn->prepare("SELECT COUNT(*) FROM services WHERE DATE(date_created) = ?");
        $todayServices->execute([$today]);
        $todayServices = $todayServices->fetchColumn();

        $srvRev = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) FROM services WHERE DATE(date_created) = ?");
        $srvRev->execute([$today]);
        $serviceRevenue = (float) $srvRev->fetchColumn();

        $srvProfit = $conn->prepare("SELECT COALESCE(SUM((sp.total_price) + (s.service_charge + s.labour_cost) - (sp.total_price * 0.0)), 0) FROM services s LEFT JOIN service_parts sp ON sp.service_id = s.id WHERE DATE(s.date_created) = ?");
        $srvProfit->execute([$today]);
        $serviceProfit = (float) $srvProfit->fetchColumn();

        $pendingServices = $conn->query("SELECT COUNT(*) FROM services WHERE service_status <> 'Completed'")->fetchColumn();
        $completedServices = $conn->query("SELECT COUNT(*) FROM services WHERE service_status = 'Completed'")->fetchColumn();
    }
} catch (Throwable $e) {
    // ignore
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header"><h1>Dashboard</h1></section>
    <section class="content">
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $productCount ?></h3><p>Total Products</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= number_format($dailySales ?: 0, 2) ?></h3><p>Today's Sales</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>KSh <?= number_format($todayProfit ?: 0, 2) ?></h3><p>Today's Profit</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>KSh <?= number_format($todayExpenses ?: 0, 2) ?></h3><p>Today's Expenses</p></div></div></div>
        </div>
        <?php if (tableExists('services')): ?>
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= (int)$todayServices ?></h3><p>Today's Services</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= number_format($serviceRevenue ?: 0, 2) ?></h3><p>Service Revenue</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>KSh <?= number_format($serviceProfit ?: 0, 2) ?></h3><p>Service Profit</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= (int)$pendingServices ?></h3><p>Pending Services</p></div></div></div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>KSh <?= number_format($cashAvailable ?: 0, 2) ?></h3><p>Cash Available</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?= (int)$quotationCount ?></h3><p>Quotations</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-dark"><div class="inner"><h3><?= (int)$invoiceCount ?></h3><p>Invoices</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-indigo"><div class="inner"><h3><?= (int)$purchaseOrderCount ?></h3><p>POs</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= (int)$deliveryCount ?></h3><p>Deliveries</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>KSh <?= number_format($netProfit ?: 0, 2) ?></h3><p>Net Profit</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?= (int)$lowStockCount ?></h3><p>Low Stock</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?= (int)$outOfStockCount ?></h3><p>Out of Stock</p></div></div></div>
        </div>
        <?php $current = currentUser(); ?>
        <?php $adminRoles = ['admin', 'administrator', 'superuser']; ?>
        <?php if (isset($_GET['debug_cash']) && $_GET['debug_cash'] == '1' && in_array(strtolower($current['role'] ?? ''), array_map('strtolower', $adminRoles), true)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card card-info">
                    <div class="card-header"><h3 class="card-title">Cash Breakdown (debug)</h3></div>
                    <div class="card-body">
                        <ul>
                            <li>Sales received: KSh <?= number_format($salesReceived ?: 0, 2) ?></li>
                            <li>Ledger In: KSh <?= number_format($ledgerIn ?: 0, 2) ?></li>
                            <li>Ledger Out: KSh <?= number_format($ledgerOut ?: 0, 2) ?></li>
                            <li>M-Pesa Confirmed: KSh <?= number_format($mpesaTotal ?: 0, 2) ?></li>
                            <li>Payments In: KSh <?= number_format($paymentsIn ?: 0, 2) ?></li>
                            <li>Payments Out: KSh <?= number_format($paymentsOut ?: 0, 2) ?></li>
                            <li>Purchases (cash out): KSh <?= number_format($purchasesOut ?: 0, 2) ?></li>
                            <li>Inventory Losses: KSh <?= number_format($stockLosses ?: 0, 2) ?></li>
                            <li>Refunds/Returns: KSh <?= number_format($refunds ?: 0, 2) ?></li>
                            <li><strong>Computed Cash Available:</strong> KSh <?= number_format($cashAvailable ?: 0, 2) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box bg-teal"><div class="inner"><h3><?= (int)$pendingRepairs ?></h3><p>Pending Repairs</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-navy"><div class="inner"><h3><?= (int)$projectsRunning ?></h3><p>Projects Running</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-indigo"><div class="inner"><h3><?= (int)$employeeCount ?></h3><p>Employees</p></div></div></div>
            <div class="col-lg-3 col-6"><div class="small-box bg-fuchsia"><div class="inner"><h3>KSh <?= number_format($extraCashEarned ?: 0, 2) ?></h3><p>Extra Cash Earned</p></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card card-danger">
                    <div class="card-header"><h3 class="card-title">Low Stock Alert</h3></div>
                    <div class="card-body p-0">
                        <table class="table">
                            <?php foreach($lowStock as $item): ?>
                            <tr><td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge bg-danger"><?= (int)$item['current_stock'] ?> Left</span></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Recent Sales</h3><div class="card-tools"><a href="modules/reports/index.php" class="btn btn-tool">View report</a></div></div>
                    <div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>Time</th><th>Cashier</th><th>Total</th><th>Status</th></tr></thead><tbody>
                    <?php if (!$recentSales): ?><tr><td colspan="4" class="text-center text-muted py-3">No sales recorded yet.</td></tr><?php endif; ?>
                    <?php foreach ($recentSales as $sale): ?><tr><td><?= htmlspecialchars($sale['sale_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($sale['fullname'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><td>KSh <?= number_format((float)$sale['total_amount'], 2) ?></td><td><span class="badge badge-<?= $sale['payment_status'] === 'Paid' ? 'success' : 'warning' ?>"><?= htmlspecialchars($sale['payment_status'], ENT_QUOTES, 'UTF-8') ?></span></td></tr><?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
