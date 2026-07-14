<?php
require '../../includes/auth.php';
requireLogin();

// Security: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
    if (!$product_id || $qty === false || $qty <= 0) {
        header("Location: ../../dashboard.php?status=invalid_stock");
        exit();
    }

    // Update the database by adding the new quantity to the existing stock
    $stmt = $conn->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
    $stmt->execute([$qty, $product_id]);

    // Return to dashboard
    header("Location: ../../dashboard.php?status=success");
    exit();
}
?>
