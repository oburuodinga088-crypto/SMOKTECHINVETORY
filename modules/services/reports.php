<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

ensureServiceSchema();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

$daily = [];
if (tableExists('services')) {
    $stmt = $conn->prepare("SELECT DATE(date_created) AS day, COUNT(*) AS count, COALESCE(SUM(total_amount),0) AS revenue FROM services WHERE DATE(date_created) BETWEEN ? AND ? GROUP BY DATE(date_created) ORDER BY DATE(date_created)");
    $stmt->execute([$from, $to]);
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$labels = json_encode(array_column($daily, 'day'));
$data = json_encode(array_map('floatval', array_column($daily, 'revenue')));

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Service Reports</h1></section>
    <section class="content">
        <div class="card">
            <div class="card-body">
                <form class="form-inline mb-3" method="get">
                    <label class="mr-2">From</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" class="form-control mr-2">
                    <label class="mr-2">To</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" class="form-control mr-2">
                    <button class="btn btn-primary">Filter</button>
                </form>
                <?php if (!tableExists('services')): ?><div class="alert alert-warning">No services table found. Run migration.</div><?php else: ?>
                <canvas id="serviceRevenueChart" style="min-height:300px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script>
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('serviceRevenueChart');
    if (ctx) new Chart(ctx, {type:'bar', data:{labels: <?= $labels ?>, datasets:[{label:'Revenue', data: <?= $data ?>, backgroundColor:'#17a2b8'}]}, options:{responsive:true, maintainAspectRatio:false}});
}
</script>