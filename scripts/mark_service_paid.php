<?php
// CLI helper: mark a service as Paid and trigger auto-sale creation via edit logic
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

if (php_sapi_name() !== 'cli') { echo "Run from CLI only.\n"; exit(1); }

$id = $argv[1] ?? null;
if (!$id || !is_numeric($id)) { echo "Usage: php mark_service_paid.php <service_id>\n"; exit(1); }
$id = (int)$id;

// fetch service
$svc = $conn->prepare('SELECT * FROM services WHERE id = ?');
$svc->execute([$id]);
$s = $svc->fetch(PDO::FETCH_ASSOC);
if (!$s) { echo "Service not found.\n"; exit(1); }

// Determine payment/status column names in different schemas
$fields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
$paymentCol = in_array('payment_status', $fields, true) ? 'payment_status' : (in_array('status', $fields, true) ? 'status' : null);
$curStatus = strtolower(trim((string)($s[$paymentCol] ?? '')));
if ($paymentCol && in_array($curStatus, ['paid','active','completed','done'], true)) { echo "Service already marked paid/active.\n"; exit(0); }

// Simulate POST to edit.php by invoking same logic: set payment_status to Paid
try {
    $parts = $conn->prepare('SELECT sp.*, p.product_name FROM service_parts sp LEFT JOIN products p ON p.id = sp.product_id WHERE sp.service_id = ?');
    $parts->execute([$id]);
    $parts = $parts->fetchAll(PDO::FETCH_ASSOC);

    $total = (float)($s['total_amount'] ?? ($s['standard_price'] ?? 0));
    $amountPaid = $total;
    $balance = 0;
    $paymentMethod = 'Cash';

    $conn->beginTransaction();
    if ($paymentCol) {
        $upd = $conn->prepare("UPDATE services SET {$paymentCol} = ? WHERE id = ?");
        $upd->execute(['Paid', $id]);
    }

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
    echo "Service {$id} marked Paid. Created sale ID {$saleId}.\n";
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
