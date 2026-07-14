<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
requireRole(['admin','administrator','superuser']);

ensureServiceSchema();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

try {
    // reuse force_create_sale logic inline to avoid shelling out
    $svc = $conn->prepare('SELECT * FROM services WHERE id = ?');
    $svc->execute([$id]);
    $s = $svc->fetch(PDO::FETCH_ASSOC);
    if (!$s) { header('Location: index.php'); exit; }

    $parts = $conn->prepare('SELECT sp.*, p.product_name FROM service_parts sp LEFT JOIN products p ON p.id = sp.product_id WHERE sp.service_id = ?');
    $parts->execute([$id]);
    $parts = $parts->fetchAll(PDO::FETCH_ASSOC);

    $svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $totalCol = in_array('total_amount', $svcFields, true) ? 'total_amount' : (in_array('standard_price', $svcFields, true) ? 'standard_price' : null);
    $total = $totalCol ? (float)($s[$totalCol] ?? 0) : 0.0;

    $conn->beginTransaction();
    $saleIns = $conn->prepare('INSERT INTO sales (customer_id, sale_date, total_amount, subtotal, amount_paid, balance, payment_method, mpesa_code, payment_status, cashier_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)');
    $saleIns->execute([$s['customer_id'] ?? null, $total, $total, $total, 0, 'Cash', null, 'Paid', null]);
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
    $_SESSION['flash'] = "Created sale #{$saleId} and linked to service.";
    header('Location: view.php?id=' . (int)$id);
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
