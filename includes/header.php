<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
$applicationName = appSetting('company_name', 'SmokeTech Technology & Innovation Hub');
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars($applicationName, ENT_QUOTES, 'UTF-8') ?> ERP</title>

<!-- Google Font -->
<link rel="stylesheet"
href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">

<!-- Font Awesome -->
<link rel="stylesheet"
href="/smoketech_inventory/plugins/fontawesome-free/css/all.min.css">

<!-- DataTables -->
<link rel="stylesheet"
href="/smoketech_inventory/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<link rel="stylesheet"
href="/smoketech_inventory/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">

<link rel="stylesheet"
href="/smoketech_inventory/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

<!-- AdminLTE -->
<link rel="stylesheet"
href="/smoketech_inventory/dist/css/adminlte.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="/smoketech_inventory/assets/css/theme.css">
<style>

body{
    font-family:'Source Sans Pro',sans-serif;
}

.brand-text{
    font-weight:bold;
}

.content-wrapper{
    min-height:100vh;
}

.small-box{
    border-radius:10px;
}

.card{
    border-radius:10px;
}

.table th {
    background-color: var(--sidebar-bg);
    color: var(--sidebar-active);
}

body.dark-mode .table th {
    background-color: rgba(23, 27, 39, 0.9);
    color: #fff;
}

</style>

</head>

<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">
<?php
if (!empty($_SESSION['flash'])): ?>
    <div class="container mt-3">
        <div class="alert alert-info alert-dismissible" role="alert">
            <?= htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
<?php unset($_SESSION['flash']); endif; ?>
