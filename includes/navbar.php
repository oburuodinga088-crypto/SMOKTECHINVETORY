<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <!-- Left navbar links -->
    <ul class="navbar-nav">

        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>

        <li class="nav-item d-none d-sm-inline-block">
            <a href="/smoketech_inventory/dashboard.php" class="nav-link">
                Dashboard
            </a>
        </li>

    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- Theme Toggle -->
        <li class="nav-item">
            <a class="nav-link" href="#" id="themeToggle" role="button">
                <i class="fas fa-moon"></i> Dark
            </a>
        </li>

        <!-- Logged-in User -->
        <li class="nav-item dropdown">

            <a class="nav-link" data-toggle="dropdown" href="#">

                <i class="fas fa-user-circle"></i>

                <?= htmlspecialchars($_SESSION['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>

            </a>

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">

                <span class="dropdown-item dropdown-header">

                    Logged in as

                    <strong><?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>

                </span>

                <div class="dropdown-divider"></div>

                <a href="/smoketech_inventory/dashboard.php" class="dropdown-item">

                    <i class="fas fa-home mr-2"></i>

                    Dashboard

                </a>

                <div class="dropdown-divider"></div>

                <a href="/smoketech_inventory/logout.php" class="dropdown-item text-danger">

                    <i class="fas fa-sign-out-alt mr-2"></i>

                    Logout

                </a>

            </div>

        </li>

    </ul>

</nav>
<!-- /.navbar -->
