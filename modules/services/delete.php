<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
// Only allow admins to delete services
requireRole(['admin','administrator','superuser']);

ensureServiceSchema();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

try {
    // ensure soft-delete columns exist
    $svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('is_deleted', $svcFields, true)) {
        $conn->exec("ALTER TABLE services ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN deleted_at DATETIME NULL");
    }
    // ensure deletion records table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS service_delete_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->beginTransaction();
    // fetch parts to return stock
    $parts = $conn->prepare('SELECT * FROM service_parts WHERE service_id = ?');
    $parts->execute([$id]);
    $parts = $parts->fetchAll(PDO::FETCH_ASSOC);
    foreach ($parts as $p) {
        adjustStock($p['product_id'], (int)$p['quantity'], 'in', 'service_delete', $id, 'Returned from deleted service');
        $ins = $conn->prepare('INSERT INTO service_delete_records (service_id, product_id, quantity) VALUES (?, ?, ?)');
        $ins->execute([$id, $p['product_id'], (int)$p['quantity']]);
    }

    // soft-delete service
    $upd = $conn->prepare('UPDATE services SET is_deleted = 1, deleted_at = NOW() WHERE id = ?');
    $upd->execute([$id]);
    $conn->commit();
    logAudit($_SESSION['user_id'] ?? null, 'soft-delete', 'service', (string)$id, 'Soft-deleted service and returned parts to stock');
    $_SESSION['flash'] = 'Service deleted and parts returned to stock. You can undo from the service view.';
    header('Location: index.php');
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
