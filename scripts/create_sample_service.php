<?php
// CLI helper: create a sample service and consume one part from inventory
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

if (php_sapi_name() !== 'cli') {
    echo "Run from CLI only.\n"; exit(1);
}

$pdo = getDB();

// find a product with stock
$prod = $pdo->query('SELECT id, product_name, current_stock, selling_price FROM products WHERE current_stock > 0 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$prod) { echo "No product with stock available to use as a part.\n"; exit(1); }

try {
    $svcCode = getNextSequentialCode('services', 'service_code', 'SVC');
    $svcName = 'Sample Service ' . date('YmdHis');
    $total = (float)$prod['selling_price'];

    // Inspect services table to choose compatible columns
    $cols = $pdo->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC);
    $fields = array_column($cols, 'Field');

    if (in_array('total_amount', $fields, true)) {
        $ins = $pdo->prepare('INSERT INTO services (service_code, service_name, total_amount, payment_status, service_status, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([$svcCode, $svcName, $total, 'Pending', 'Pending', null]);
    } elseif (in_array('standard_price', $fields, true)) {
        // older schema: insert into standard_price and description
        $ins = $pdo->prepare('INSERT INTO services (service_code, service_name, description, standard_price, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $ins->execute([$svcCode, $svcName, 'Sample service generated for testing', $total, 'Active']);
    } else {
        // fallback: attempt minimal insert
        $ins = $pdo->prepare('INSERT INTO services (service_code, service_name) VALUES (?, ?)');
        $ins->execute([$svcCode, $svcName]);
    }
    $serviceId = $pdo->lastInsertId();

    // use 1 unit of the product
    $qty = 1;
    $lineTotal = $qty * (float)$prod['selling_price'];
    adjustStock($prod['id'], -$qty, 'out', 'service', $serviceId, 'Sample service creation');
    $si = $pdo->prepare('INSERT INTO service_parts (service_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
    $si->execute([$serviceId, $prod['id'], $qty, $prod['selling_price'], $lineTotal]);

    echo "Created service {$svcCode} (ID: {$serviceId}). Product used: {$prod['product_name']} x{$qty}.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
