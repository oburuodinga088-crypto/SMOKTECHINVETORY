<?php
require __DIR__ . '/../config/database.php';
$pdo = getDB();
try {
    $stmt = $pdo->query("SELECT id, sale_date, COALESCE(total_amount,0) AS total_amount, amount_paid FROM sales WHERE amount_paid > COALESCE(total_amount,0) ORDER BY id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo "Error checking sales integrity: " . $e->getMessage() . "\n";
    exit(2);
}
$count = count($rows);
echo "Found $count overpaid sales\n";
foreach ($rows as $r) {
    $over = $r['amount_paid'] - $r['total_amount'];
    echo "{$r['id']} | {$r['sale_date']} | total={$r['total_amount']} | paid={$r['amount_paid']} | over={$over}\n";
}
// exit status 0 if none, 1 if any
exit($count > 0 ? 1 : 0);
