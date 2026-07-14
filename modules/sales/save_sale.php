<?php
require '../../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $cart = $data['cart'] ?? [];
    if (!is_array($cart) || !$cart) {
        throw new InvalidArgumentException('Cart is empty.');
    }

    $paymentMethod = trim($data['payment_method'] ?? 'Cash');
    if (!in_array($paymentMethod, ['Cash', 'M-Pesa', 'Credit'], true)) {
        throw new InvalidArgumentException('Invalid payment method.');
    }
    $customerId = filter_var($data['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $amountPaid = filter_var($data['amount_paid'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($amountPaid === false || $amountPaid < 0) {
        throw new InvalidArgumentException('Invalid amount paid.');
    }

    $conn->beginTransaction();
    $items = [];
    $total = 0.0;
    $productStmt = $conn->prepare('SELECT id, product_name, current_stock, selling_price, buying_price FROM products WHERE id = ? FOR UPDATE');
    foreach ($cart as $item) {
        $productId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId || !$quantity || $quantity < 1) {
            throw new InvalidArgumentException('Invalid item in cart.');
        }
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product || (int)$product['current_stock'] < $quantity) {
            throw new RuntimeException('Insufficient stock for the selected product.');
        }
        $lineTotal = $quantity * (float)$product['selling_price'];
        $total += $lineTotal;
        $items[] = [$product, $quantity];
    }
    if ($paymentMethod !== 'Credit' && $amountPaid < $total) {
        throw new InvalidArgumentException('Amount paid is less than the sale total.');
    }
    if ($paymentMethod === 'Credit' && $amountPaid < $total && !$customerId) {
        throw new InvalidArgumentException('A customer is required for an unpaid credit sale.');
    }
    if ($amountPaid > $total) {
        $amountPaid = $total;
    }
    $balance = max(0, $total - $amountPaid);
    $status = $balance > 0 ? 'Pending' : 'Paid';
    $sale = $conn->prepare('INSERT INTO sales (customer_id, sale_date, total_amount, subtotal, amount_paid, balance, payment_method, mpesa_code, payment_status, cashier_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)');
    $sale->execute([$customerId, $total, $total, $amountPaid, $balance, $paymentMethod, trim($data['mpesa_code'] ?? '') ?: null, $status, $_SESSION['user_id']]);
    $saleId = $conn->lastInsertId();
    $itemStmt = $conn->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, selling_price, buying_price) VALUES (?, ?, ?, ?, ?)');
    $stockStmt = $conn->prepare('UPDATE products SET current_stock = current_stock - ? WHERE id = ?');
    foreach ($items as [$product, $quantity]) {
        $itemStmt->execute([$saleId, $product['id'], $quantity, $product['selling_price'], $product['buying_price']]);
        $stockStmt->execute([$quantity, $product['id']]);
    }
    $conn->commit();
    echo json_encode(['success' => true, 'sale_id' => $saleId]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
