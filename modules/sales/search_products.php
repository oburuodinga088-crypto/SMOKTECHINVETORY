<?php

require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

header('Content-Type: application/json');


if(!isset($_GET['search'])){

    echo json_encode([]);

    exit;

}


$search = "%".$_GET['search']."%";


$stmt = $conn->prepare("

SELECT

id,
product_name,
barcode,
selling_price,
current_stock

FROM products

WHERE 
(product_name LIKE ?
OR barcode LIKE ?)

AND current_stock > 0

ORDER BY product_name

LIMIT 20

");


$stmt->execute([

$search,
$search

]);


$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo json_encode($products);
