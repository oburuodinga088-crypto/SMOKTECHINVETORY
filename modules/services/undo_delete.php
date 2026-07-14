<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
requireRole(['admin','administrator','superuser']);

ensureServiceSchema();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

try {
    $conn->beginTransaction();
    // fetch delete records
    $rec = $conn->prepare('SELECT * FROM service_delete_records WHERE service_id = ?');
    $rec->execute([$id]);
    $rows = $rec->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        // subtract returned qty from stock (put back into service)
        adjustStock($r['product_id'], -(int)$r['quantity'], 'out', 'service_undo', $id, 'Undo service delete');
    }
    // remove records
    $del = $conn->prepare('DELETE FROM service_delete_records WHERE service_id = ?');
    $del->execute([$id]);
    // restore service
    $upd = $conn->prepare('UPDATE services SET is_deleted = 0, deleted_at = NULL WHERE id = ?');
    $upd->execute([$id]);
    $conn->commit();
    logAudit($_SESSION['user_id'] ?? null, 'undo-delete', 'service', (string)$id, 'Restored service and returned parts from stock');
    $_SESSION['flash'] = 'Service restored and parts re-consumed from stock.';
    header('Location: view.php?id=' . (int)$id);
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
