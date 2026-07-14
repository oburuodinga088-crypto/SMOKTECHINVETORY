<?php
require '../../includes/auth.php'; require '../../includes/functions.php'; requireLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid file request.'); }
$allowedMimes = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp'], 'application/pdf' => ['pdf'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'], 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx']];
$uploadDirectory = dirname(__DIR__, 2) . '/assets/uploads';
try { $stmt = $conn->prepare('SELECT * FROM media_files WHERE id = ?'); $stmt->execute([$id]); $file = $stmt->fetch(PDO::FETCH_ASSOC); if (!$file || (!isAdmin() && (int)$file['uploaded_by'] !== (int)$_SESSION['user_id'])) throw new RuntimeException('File not found or access denied.'); } catch (Throwable $e) { http_response_code(404); exit(e($e->getMessage())); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token expired.');
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['document']['tmp_name'])) throw new RuntimeException('Select a replacement file.');
        $new = $_FILES['document']; if ($new['size'] < 1 || $new['size'] > 10 * 1024 * 1024) throw new RuntimeException('Replacement file must be 10 MB or less.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($new['tmp_name']); $extension = strtolower(pathinfo($new['name'], PATHINFO_EXTENSION)); if (!isset($allowedMimes[$mime]) || !in_array($extension, $allowedMimes[$mime], true)) throw new RuntimeException('Only JPG, PNG, WEBP, PDF, DOCX, and XLSX files are allowed.');
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) throw new RuntimeException('Upload directory could not be created.');
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension; if (!move_uploaded_file($new['tmp_name'], $uploadDirectory . '/' . $storedName)) throw new RuntimeException('Replacement file could not be stored.');
        $conn->beginTransaction(); $conn->prepare('UPDATE media_files SET original_name = ?, stored_name = ?, mime_type = ?, file_size = ?, uploaded_by = ? WHERE id = ?')->execute([basename($new['name']), $storedName, $mime, (int)$new['size'], (int)$_SESSION['user_id'], $id]); $conn->commit();
        $oldPath = $uploadDirectory . '/' . basename($file['stored_name']); if (is_file($oldPath)) @unlink($oldPath); $_SESSION['flash'] = 'File replaced successfully.'; header('Location: index.php'); exit;
    } catch (Throwable $e) { if ($conn->inTransaction()) $conn->rollBack(); $error = $e->getMessage(); }
}
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php'; ?>
<div class="content-wrapper"><section class="content-header"><div class="container-fluid"><h1>Replace file</h1></div></section><section class="content"><div class="container-fluid"><div class="card card-primary"><form method="post" enctype="multipart/form-data"><div class="card-body"><?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?= csrfField() ?><p>Replacing <strong><?= e($file['original_name']) ?></strong>.</p><div class="form-group"><label>New file</label><input type="file" name="document" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx" required></div></div><div class="card-footer"><button class="btn btn-primary">Replace</button> <a href="index.php" class="btn btn-secondary">Cancel</a></div></form></div></div></section></div><?php include '../../includes/footer.php'; ?>
