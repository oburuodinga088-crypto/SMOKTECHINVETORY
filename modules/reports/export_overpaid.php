<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../config/database.php';
requireLogin();
requireRole(['admin','administrator','superuser']);
// Reuse CLI exporter and show download link.
$exportScript = __DIR__ . '/../../scripts/export_overpaid_sales.php';
ob_start();
system("php " . escapeshellarg($exportScript), $rc);
$output = ob_get_clean();
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Export Overpaid Sales</title></head>
<body>
<h3>Export Overpaid Sales</h3>
<pre><?php echo htmlspecialchars($output); ?></pre>
<p><a href="/scripts/../exports/">Open exports folder</a> (server file listing)</p>
</body>
</html>
