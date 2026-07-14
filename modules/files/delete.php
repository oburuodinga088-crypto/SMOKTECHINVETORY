<?php
require '../../includes/auth.php'; require '../../includes/functions.php'; requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) { http_response_code(400); exit('Invalid request.'); }
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid file request.'); }
try {
    $conn->beginTransaction(); $stmt = $conn->prepare('SELECT * FROM media_files WHERE id = ? FOR UPDATE'); $stmt->execute([$id]); $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file || (!isAdmin() && (int)$file['uploaded_by'] !== (int)$_SESSION['user_id'])) throw new RuntimeException('You are not allowed to delete this file.');
    $conn->prepare('DELETE FROM media_files WHERE id = ?')->execute([$id]); $conn->commit();
    $path = dirname(__DIR__, 2) . '/assets/uploads/' . basename($file['stored_name']); if (is_file($path) && !unlink($path)) error_log('Unable to remove managed file: ' . $path);
    $_SESSION['flash'] = 'File deleted.';
} catch (Throwable $e) { if ($conn->inTransaction()) $conn->rollBack(); $_SESSION['flash'] = $e->getMessage(); }
header('Location: index.php'); exit;
