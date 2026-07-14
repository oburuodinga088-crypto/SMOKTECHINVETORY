<?php
require __DIR__ . '/../config/database.php';
$pdo = getDB();
$issues = [];
// Sales overpaid
try {
    $rows = $pdo->query("SELECT id, sale_date, COALESCE(total_amount,0) AS total_amount, amount_paid, balance FROM sales WHERE amount_paid > COALESCE(total_amount,0)")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) $issues['sales_overpaid'] = $rows;
} catch (Throwable $e) {}
// Sales negative values
try {
    $rows = $pdo->query("SELECT id, sale_date, total_amount, amount_paid, balance FROM sales WHERE COALESCE(total_amount,0)<0 OR amount_paid<0 OR balance<0")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) $issues['sales_negative'] = $rows;
} catch (Throwable $e) {}
// Purchases negative totals or weird flags
try {
    if ($pdo->query("SHOW TABLES LIKE 'purchases'")->rowCount() > 0) {
        $rows = $pdo->query("SELECT id, purchase_date, COALESCE(total_amount,0) AS total_amount FROM purchases WHERE COALESCE(total_amount,0) < 0")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $issues['purchases_negative'] = $rows;
    }
} catch (Throwable $e) {}
// Payments anomalies
try {
    if ($pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0) {
        $rows = $pdo->query("SELECT id, payment_date, amount FROM payments WHERE amount < 0")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $issues['payments_negative'] = $rows;
    }
} catch (Throwable $e) {}
// Stock movements with missing product
try {
    if ($pdo->query("SHOW TABLES LIKE 'stock_movements'")->rowCount() > 0) {
        $rows = $pdo->query("SELECT sm.* FROM stock_movements sm LEFT JOIN products p ON p.id = sm.product_id WHERE p.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $issues['stock_orphan'] = $rows;
    }
} catch (Throwable $e) {}
// Print summary
echo "Integrity sweep: found " . count($issues) . " categories with issues.\n\n";
foreach ($issues as $k => $rows) {
    echo strtoupper($k) . " (" . count($rows) . " rows)\n";
    foreach ($rows as $r) {
        echo implode(' | ', array_map(function($v){ return is_null($v)?'NULL':$v; }, $r)) . "\n";
    }
    echo "\n";
}
if (empty($issues)) {
    echo "No integrity issues found.\n";
    exit(0);
}
// Attempt to send an alert to admin (best-effort)
try {
    require_once __DIR__ . '/send_integrity_alert.php';
    $sent = send_integrity_alert($issues);
    echo "Alert sent via mail(): " . ($sent ? 'OK' : 'FAILED') . "\n";
} catch (Throwable $e) {
    echo "Alerting failed: " . $e->getMessage() . "\n";
}

exit(1);
