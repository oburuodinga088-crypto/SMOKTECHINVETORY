<?php
require '../../includes/auth.php';
requireLogin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}

$summaryStmt = $conn->prepare(
    "SELECT COUNT(*) AS sale_count,
        COALESCE(SUM(total_amount), 0) AS revenue,
        COALESCE(SUM(CASE WHEN payment_status = 'Pending' THEN balance ELSE 0 END), 0) AS outstanding
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?"
);
$summaryStmt->execute([$from, $to]);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$averageSale = $summary['sale_count'] ? $summary['revenue'] / $summary['sale_count'] : 0;
$customerCountStmt = $conn->prepare(
    "SELECT COUNT(DISTINCT customer_id) FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? AND customer_id IS NOT NULL"
);
$customerCountStmt->execute([$from, $to]);
$customerCount = (int)$customerCountStmt->fetchColumn();

$salesTrendStmt = $conn->prepare(
    "SELECT DATE(sale_date) AS period,
        COALESCE(SUM(total_amount), 0) AS revenue,
        COUNT(*) AS sale_count
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date)"
);
$salesTrendStmt->execute([$from, $to]);
$salesTrend = $salesTrendStmt->fetchAll(PDO::FETCH_ASSOC);

$trendLabels = json_encode(array_column($salesTrend, 'period'));
$trendRevenue = json_encode(array_map('floatval', array_column($salesTrend, 'revenue')));
$trendCount = json_encode(array_map('intval', array_column($salesTrend, 'sale_count')));

$paymentMethodStmt = $conn->prepare(
    "SELECT payment_method, COALESCE(SUM(total_amount), 0) AS total
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total DESC"
);
$paymentMethodStmt->execute([$from, $to]);
$paymentMethods = $paymentMethodStmt->fetchAll(PDO::FETCH_ASSOC);
$paymentLabels = json_encode(array_column($paymentMethods, 'payment_method'));
$paymentTotals = json_encode(array_map('floatval', array_column($paymentMethods, 'total')));

$salesStmt = $conn->prepare(
    'SELECT s.*, u.fullname FROM sales s LEFT JOIN users u ON u.id = s.cashier_id WHERE DATE(s.sale_date) BETWEEN ? AND ? ORDER BY s.sale_date DESC'
);
$salesStmt->execute([$from, $to]);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

$lowStock = $conn->query(
    'SELECT product_name, current_stock, reorder_level FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC'
)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Reports Center</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form class="form-inline mb-4" method="get">
                <label class="mr-2">From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" class="form-control mr-3">
                <label class="mr-2">To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" class="form-control mr-3">
                <button type="submit" class="btn btn-primary">Update</button>
            </form>

            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= (int)$summary['sale_count'] ?></h3>
                            <p>Sales</p>
                        </div>
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>KSh <?= number_format((float)$summary['revenue'], 2) ?></h3>
                            <p>Revenue</p>
                        </div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>KSh <?= number_format((float)$summary['outstanding'], 2) ?></h3>
                            <p>Outstanding</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= (int)$customerCount ?></h3>
                            <p>Customers</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daily Sales Trend</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesTrendChart" style="min-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment Method Breakdown</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentMethodChart" style="min-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Sales</h3>
                    <button id="salesPrint" type="button" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card-body">
                    <table id="salesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Cashier</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['sale_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($sale['fullname'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($sale['payment_method'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($sale['payment_status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>KSh <?= number_format((float)$sale['total_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Low Stock</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Reorder level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStock as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)$product['current_stock'] ?></td>
                                    <td><?= (int)$product['reorder_level'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var trendLabels = <?= $trendLabels ?>;
        var trendRevenue = <?= $trendRevenue ?>;
        var paymentLabels = <?= $paymentLabels ?>;
        var paymentTotals = <?= $paymentTotals ?>;

        // Chart fallback: if Chart.js isn't loaded, show a friendly message
        var salesTrendContainer = document.getElementById('salesTrendChart');
        if (typeof Chart !== 'undefined' && salesTrendContainer) {
            try {
                new Chart(salesTrendContainer, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Daily Revenue',
                            data: trendRevenue,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            fill: true,
                            tension: 0.2,
                            pointRadius: 3,
                            pointBackgroundColor: '#007bff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {precision: 0}} },
                        plugins: {legend: {position: 'top'}, tooltip: {mode: 'index', intersect: false}}
                    }
                });
            } catch (e) {
                salesTrendContainer.parentElement.innerHTML = '<p class="text-danger">Unable to render sales chart.</p>';
                console.error('Chart error:', e);
            }
        } else if (salesTrendContainer) {
            salesTrendContainer.parentElement.innerHTML = '<p class="text-muted">Chart.js not loaded. Please ensure /plugins/chart.js/Chart.bundle.min.js is included.</p>';
        }

        var paymentMethodContainer = document.getElementById('paymentMethodChart');
        if (typeof Chart !== 'undefined' && paymentMethodContainer) {
            try {
                new Chart(paymentMethodContainer, {
                    type: 'doughnut',
                    data: { labels: paymentLabels, datasets: [{ data: paymentTotals, backgroundColor: ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#6c757d'], borderWidth: 1 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}, tooltip: {mode: 'nearest', intersect: true}} }
                });
            } catch (e) {
                paymentMethodContainer.parentElement.innerHTML = '<p class="text-danger">Unable to render payment chart.</p>';
                console.error('Chart error:', e);
            }
        } else if (paymentMethodContainer) {
            paymentMethodContainer.parentElement.innerHTML = '<p class="text-muted">Chart.js not loaded. Please ensure /plugins/chart.js/Chart.bundle.min.js is included.</p>';
        }

        // DataTables fallback: if jQuery or DataTables isn't available, show a notice and disable export button
        var salesPrintBtn = document.getElementById('salesPrint');
        if (window.$ && $.fn && $.fn.DataTable) {
            try {
                var salesTable = $('#salesTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    pageLength: 10,
                    dom: 'Bfrtip',
                    buttons: [
                        {extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel'},
                        {extend: 'csvHtml5', text: '<i class="fas fa-file-csv"></i> CSV'},
                        {extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF'},
                        {extend: 'print', text: '<i class="fas fa-print"></i> Print'}
                    ]
                });

                $('#salesPrint').on('click', function () { salesTable.button('.buttons-print').trigger(); });
            } catch (e) {
                console.error('DataTable init error:', e);
                if (salesPrintBtn) salesPrintBtn.disabled = true;
                var tbl = document.getElementById('salesTable');
                if (tbl) tbl.parentElement.insertAdjacentHTML('afterbegin', '<div class="alert alert-warning">DataTables failed to initialize.</div>');
            }
        } else {
            if (salesPrintBtn) salesPrintBtn.disabled = true;
            var tbl = document.getElementById('salesTable');
            if (tbl) tbl.parentElement.insertAdjacentHTML('afterbegin', '<div class="alert alert-secondary">jQuery/DataTables not loaded — exports are unavailable.</div>');
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>