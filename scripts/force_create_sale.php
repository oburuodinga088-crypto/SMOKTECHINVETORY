<?php
// Force-create a sale for a given service id (ignores current payment/status)
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

if (php_sapi_name() !== 'cli') { echo "Run from CLI only.\n"; exit(1); }

$id = $argv[1] ?? null;
if (!$id || !is_numeric($id)) { echo "Usage: php force_create_sale.php <service_id>\n"; exit(1); }
$id = (int)$id;

$svc = $conn->prepare('SELECT * FROM services WHERE id = ?');
$svc->execute([$id]);
$s = $svc->fetch(PDO::FETCH_ASSOC);
if (!$s) { echo "Service not found.\n"; exit(1); }

try {
    $parts = $conn->prepare('SELECT sp.*, p.product_name FROM service_parts sp LEFT JOIN products p ON p.id = sp.product_id WHERE sp.service_id = ?');
    $parts->execute([$id]);
    $parts = $parts->fetchAll(PDO::FETCH_ASSOC);

    // detect total column
    $svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $totalCol = in_array('total_amount', $svcFields, true) ? 'total_amount' : (in_array('standard_price', $svcFields, true) ? 'standard_price' : (in_array('estimated_cost', $svcFields, true) ? 'estimated_cost' : null));
    $total = $totalCol ? (float)($s[$totalCol] ?? 0) : 0.0;

    $amountPaid = $total; $balance = 0; $paymentMethod = 'Cash';
    $conn->beginTransaction();
    $saleIns = $conn->prepare('INSERT INTO sales (customer_id, sale_date, total_amount, subtotal, amount_paid, balance, payment_method, mpesa_code, payment_status, cashier_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)');
    $saleIns->execute([$s['customer_id'] ?? null, $total, $total, $amountPaid, $balance, $paymentMethod, null, 'Paid', null]);
    $saleId = $conn->lastInsertId();

    $itemIns = $conn->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, selling_price, buying_price) VALUES (?, ?, ?, ?, ?)');
    foreach ($parts as $p) {
        $prod = $conn->prepare('SELECT buying_price FROM products WHERE id = ?');
        $prod->execute([$p['product_id']]);
        $prodRow = $prod->fetch(PDO::FETCH_ASSOC);
        $buying = $prodRow['buying_price'] ?? 0;
        $itemIns->execute([$saleId, $p['product_id'], $p['quantity'], $p['unit_price'], $buying]);
    }

    $link = $conn->prepare('UPDATE services SET sale_id = ? WHERE id = ?');
    $link->execute([$saleId, $id]);
    $conn->commit();
    echo "Forced sale created: {$saleId} for service {$id}\n";
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
