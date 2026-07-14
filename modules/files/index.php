<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

$types = [
    'product' => 'Product image', 'employee' => 'Employee photo', 'customer' => 'Customer photo',
    'supplier' => 'Supplier document', 'repair' => 'Repair photo', 'warranty' => 'Warranty document',
    'purchase' => 'Purchase invoice', 'receipt' => 'Receipt', 'project' => 'Project document',
];
$allowedMimes = [
    'image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp'],
    'application/pdf' => ['pdf'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
];
$maxBytes = 10 * 1024 * 1024;
$uploadDirectory = dirname(__DIR__, 2) . '/assets/uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token expired. Please try again.');
        $entityType = (string) ($_POST['entity_type'] ?? '');
        $entityId = filter_var($_POST['entity_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!isset($types[$entityType]) || $entityId === false) throw new InvalidArgumentException('Select a valid file category and record ID.');
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Select a file to upload.');
        $file = $_FILES['document'];
        if (!is_uploaded_file($file['tmp_name']) || $file['size'] < 1 || $file['size'] > $maxBytes) throw new RuntimeException('File must be between 1 byte and 10 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($allowedMimes[$mime]) || !in_array($extension, $allowedMimes[$mime], true)) throw new RuntimeException('Only JPG, PNG, WEBP, PDF, DOCX, and XLSX files are allowed.');
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) throw new RuntimeException('Upload directory could not be created.');
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $storedName)) throw new RuntimeException('The uploaded file could not be stored.');
        try {
            $stmt = $conn->prepare('INSERT INTO media_files (entity_type, entity_id, original_name, stored_name, mime_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$entityType, $entityId, basename($file['name']), $storedName, $mime, (int) $file['size'], (int) $_SESSION['user_id']]);
        } catch (Throwable $e) {
            @unlink($uploadDirectory . '/' . $storedName);
            throw $e;
        }
        $_SESSION['flash'] = 'File uploaded successfully.';
        header('Location: index.php'); exit;
    } catch (Throwable $e) { $error = $e->getMessage(); }
}

$files = [];
try {
    $files = $conn->query('SELECT m.*, u.fullname AS uploader_name FROM media_files m LEFT JOIN users u ON u.id = m.uploaded_by ORDER BY m.created_at DESC, m.id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $error = 'File metadata table is unavailable. Apply the ERP foundation migration first.'; }
include '../../includes/header.php'; include '../../includes/navbar.php'; include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><div class="container-fluid"><h1><i class="fas fa-folder-open"></i> File Manager</h1></div></section><section class="content"><div class="container-fluid"><div class="card card-primary"><div class="card-header"><h3 class="card-title">Upload document or image</h3></div><form method="post" enctype="multipart/form-data"><div class="card-body"><?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?= csrfField() ?><div class="row"><div class="col-md-4"><div class="form-group"><label>Category</label><select name="entity_type" class="form-control" required><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div></div><div class="col-md-3"><div class="form-group"><label>Record ID</label><input type="number" name="entity_id" min="1" class="form-control" required></div></div><div class="col-md-5"><div class="form-group"><label>File</label><input type="file" name="document" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx" required><small class="form-text text-muted">Maximum 10 MB. JPG, PNG, WEBP, PDF, DOCX, XLSX.</small></div></div></div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-upload"></i> Upload file</button></div></form></div>
<div class="card"><div class="card-header"><h3 class="card-title">Managed files</h3></div><div class="card-body"><table id="filesTable" class="table table-bordered table-striped"><thead><tr><th>Preview / Name</th><th>Category</th><th>Record</th><th>Type</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody><?php foreach ($files as $file): ?><tr><td><?php if (str_starts_with($file['mime_type'], 'image/')): ?><a href="download.php?id=<?= (int)$file['id'] ?>" target="_blank"><img src="download.php?id=<?= (int)$file['id'] ?>" alt="<?= e($file['original_name']) ?>" style="width:48px;height:48px;object-fit:cover" class="mr-2 rounded"></a><?php endif; ?><?= e($file['original_name']) ?></td><td><?= e($types[$file['entity_type']] ?? $file['entity_type']) ?></td><td><?= (int)$file['entity_id'] ?></td><td><?= e($file['mime_type']) ?></td><td><?= number_format(((int)$file['file_size']) / 1024, 1) ?> KB</td><td><?= e($file['created_at']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="download.php?id=<?= (int)$file['id'] ?>" target="_blank" title="Download"><i class="fas fa-download"></i></a><?php if (isAdmin() || (int)$file['uploaded_by'] === (int)$_SESSION['user_id']): ?><a class="btn btn-sm btn-outline-secondary" href="replace.php?id=<?= (int)$file['id'] ?>" title="Replace"><i class="fas fa-sync"></i></a><form class="d-inline" method="post" action="delete.php" onsubmit="return confirm('Delete this file permanently?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$file['id'] ?>"><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></section></div><?php include '../../includes/footer.php'; ?>
<script>$(function(){ $('#filesTable').DataTable({responsive:true,autoWidth:false}); });</script>
