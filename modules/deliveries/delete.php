<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $conn->prepare('DELETE FROM deliveries WHERE id = ?')->execute([$id]);
}
header('Location: index.php');
exit;
