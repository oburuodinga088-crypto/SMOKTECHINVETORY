<?php
require '../../includes/auth.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php?status=invalid');
    exit;
}

try {
    $check = $conn->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $check->execute([$id]);
    if ((int)$check->fetchColumn() > 0) {
        header('Location: index.php?status=in_use');
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: index.php?status=deleted');
} catch (PDOException $e) {
    header('Location: index.php?status=error');
}
exit;
