<?php
require '../../includes/auth.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }
try {
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: index.php?status=deleted');
} catch (PDOException $e) {
    header('Location: index.php?status=in_use');
}
exit;
