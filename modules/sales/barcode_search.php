<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

header('Content-Type: application/json');

// Ensure barcode is provided and not empty
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    echo json_encode(["success" => false, "message" => "Barcode is empty"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id, product_name, barcode, selling_price, current_stock 
        FROM products 
        WHERE barcode = ? AND current_stock > 0 
        LIMIT 1
    ");

    $stmt->execute([$barcode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode([
            "success" => true,
            "product" => $product
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Product not found"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
