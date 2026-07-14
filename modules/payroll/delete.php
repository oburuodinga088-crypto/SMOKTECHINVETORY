<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$conn->prepare('DELETE FROM payroll WHERE id = ?')->execute([$id]);
header('Location: index.php');
exit;
