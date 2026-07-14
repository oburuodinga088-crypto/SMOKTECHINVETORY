<?php
// Automated test: create a sample service, force-create sale, report results
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
if (php_sapi_name() !== 'cli') { echo "Run from CLI only.\n"; exit(1); }
$pdo = getDB();
// create sample service
passthru(escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . '/create_sample_service.php'), $rc1);
if ($rc1 !== 0) { echo "Failed to create sample service\n"; exit(2); }
// find last inserted service id
$sid = $pdo->query("SELECT id FROM services ORDER BY id DESC LIMIT 1")->fetchColumn();
if (!$sid) { echo "No service found after creation\n"; exit(3); }
echo "Created service id: $sid\n";
// force create sale
passthru(escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . '/force_create_sale.php') . ' ' . (int)$sid, $rc2);
if ($rc2 !== 0) { echo "Failed to force create sale\n"; exit(4); }
// verify
$saleId = $pdo->query('SELECT sale_id FROM services WHERE id = ' . (int)$sid)->fetchColumn();
echo "Service linked sale_id: " . ($saleId ?: 'NULL') . "\n";
$items = $pdo->query('SELECT COUNT(*) FROM sale_items WHERE sale_id = ' . (int)$saleId)->fetchColumn();
echo "Sale items count: $items\n";
$parts = $pdo->query('SELECT COUNT(*) FROM service_parts WHERE service_id = ' . (int)$sid)->fetchColumn();
echo "Service parts count: $parts\n";
$prod = $pdo->query('SELECT p.product_name, p.current_stock FROM products p JOIN service_parts sp ON sp.product_id = p.id WHERE sp.service_id = ' . (int)$sid . ' LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($prod) echo "Product {$prod['product_name']} current_stock={$prod['current_stock']}\n";
exit(0);
