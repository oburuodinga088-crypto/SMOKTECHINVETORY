<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request.";
    } else {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {

            $error = "Please enter both username and password.";

        } else {

            if (attemptLogin($username, $password)) {

                session_regenerate_id(true);

                header("Location: dashboard.php");
                exit();

            } else {

                $error = "Invalid username or password.";

            }

        }

    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>SmokeTech ERP Login</title>

<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">

<style>

body{
background:#f4f6f9;
}

.login-box{
max-width:400px;
margin:7% auto;
}

.logo{
font-size:34px;
font-weight:bold;
color:#007bff;
}

.login-brand-logo {
display:block;
width:100%;
max-width:300px;
height:auto;
margin:0 auto 10px;
}

</style>

</head>

<body>

<div class="login-box">

<div class="card card-primary card-outline">

<div class="card-header text-center">

<img src="assets/images/moketech-logo.png" class="login-brand-logo" alt="SmokeTech — Care Beyond Repair">

<p class="mb-0">

Inventory • Repairs • POS

</p>

</div>

<div class="card-body">

<?php if($error): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>

<form method="POST" autocomplete="off">

<input
type="hidden"
name="csrf_token"
value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<div class="form-group">

<label>Username</label>

<input
type="text"
name="username"
class="form-control"
required
autofocus>

</div>

<div class="form-group">

<label>Password</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button
type="submit"
class="btn btn-primary btn-block">

<i class="fas fa-sign-in-alt"></i>

 Login

</button>

</form>

</div>

</div>

</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.min.js"></script>

</body>

</html>
