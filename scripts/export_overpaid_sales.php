<?php
require __DIR__ . '/../config/database.php';
$pdo = getDB();
try {
    $rows = $pdo->query("SELECT * FROM sales_overpaid_backup")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo "Error reading backup table: " . $e->getMessage() . "\n";
    exit(2);
}
if (empty($rows)) {
    echo "No overpaid backup rows found.\n";
    exit(0);
}
$dir = __DIR__ . '/../exports';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$fname = $dir . '/overpaid_sales_' . date('Ymd_His') . '.csv';
$fh = fopen($fname, 'w');
if ($fh === false) { echo "Unable to open file for writing: $fname\n"; exit(3); }
// header
fputcsv($fh, array_keys($rows[0]));
foreach ($rows as $r) fputcsv($fh, $r);
fclose($fh);
echo "Exported " . count($rows) . " rows to: $fname\n";
exit(0);
