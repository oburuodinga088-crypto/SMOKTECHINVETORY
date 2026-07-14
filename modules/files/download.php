<?php
require '../../includes/auth.php'; requireLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid file request.'); }
try {
    $stmt = $conn->prepare('SELECT * FROM media_files WHERE id = ?'); $stmt->execute([$id]); $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) { http_response_code(404); exit('File not found.'); }
    $path = dirname(__DIR__, 2) . '/assets/uploads/' . basename($file['stored_name']);
    if (!is_file($path)) { http_response_code(404); exit('Stored file not found.'); }
    header('Content-Type: ' . $file['mime_type']); header('Content-Length: ' . filesize($path)); header('Content-Disposition: inline; filename="' . rawurlencode($file['original_name']) . '"'); header('X-Content-Type-Options: nosniff'); readfile($path); exit;
} catch (Throwable $e) { error_log('File download failed: ' . $e->getMessage()); http_response_code(500); exit('Unable to retrieve file.'); }
