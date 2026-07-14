<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- Brand Logo -->
    <?php $companyName = appSetting('company_name', 'SmokeTech Technology & Innovation Hub'); $companyLogo = appSetting('company_logo', '/smoketech_inventory/assets/images/moketech-logo.png'); ?>
    <a href="/smoketech_inventory/dashboard.php" class="brand-link smoketech-brand-link" aria-label="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> dashboard">
        <img src="<?= htmlspecialchars($companyLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>" class="smoketech-sidebar-logo">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">

        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">

            <div class="image">

                <i class="fas fa-user-circle fa-2x text-white"></i>

            </div>

            <div class="info">

                <a href="#" class="d-block">

                    <?= htmlspecialchars($_SESSION['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                    <br>

                    <small><?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>

                </a>

            </div>

        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">

            <ul class="nav nav-pills nav-sidebar flex-column"
                data-widget="treeview"
                role="menu"
                data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/dashboard.php" class="nav-link">

                        <i class="nav-icon fas fa-tachometer-alt"></i>

                        <p>Dashboard</p>

                    </a>

                </li>

                <!-- Categories -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/categories/index.php" class="nav-link">

                        <i class="nav-icon fas fa-tags"></i>

                        <p>Categories</p>

                    </a>

                </li>

                <!-- Products -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/products/index.php" class="nav-link">

                        <i class="nav-icon fas fa-boxes"></i>

                        <p>Products</p>

                    </a>

                </li>

                <!-- Suppliers -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/suppliers/index.php" class="nav-link">

                        <i class="nav-icon fas fa-truck"></i>

                        <p>Suppliers</p>

                    </a>

                </li>

                <!-- Customers -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/customers/index.php" class="nav-link">

                        <i class="nav-icon fas fa-users"></i>

                        <p>Customers</p>

                    </a>

                </li>

                <!-- Purchases -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/purchases/index.php" class="nav-link">

                        <i class="nav-icon fas fa-dolly"></i>

                        <p>Purchases</p>

                    </a>

                </li>

                <!-- Sales -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/sales/index.php" class="nav-link">

                        <i class="nav-icon fas fa-shopping-cart"></i>

                        <p>Sales</p>

                    </a>

                </li>

                <!-- Reports -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                            Reports
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="/smoketech_inventory/modules/reports/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Overview</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/reports/cash_breakdown.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Cash Breakdown</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/reports/export_overpaid.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Export Overpaid</p></a></li>
                    </ul>
                </li>

                <!-- Services (new) -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-concierge-bell"></i>
                        <p>
                            Services
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="/smoketech_inventory/modules/services/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>All Services</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/services/create.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Create Service</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/services/reports.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Service Reports</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/business_assistant/index.php" class="nav-link"><i class="fas fa-robot nav-icon"></i><p>Business Assistant</p></a></li>
                    </ul>
                </li>

                <!-- ERP Modules -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-layer-group"></i>
                        <p>
                            ERP Modules
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="/smoketech_inventory/modules/repairs/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Repairs</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/projects/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Projects</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/expenses/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Expenses</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/employees/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Employees</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/payroll/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Payroll</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/quotations/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Quotations</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/invoices/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Invoices</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/purchase_orders/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Purchase Orders</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/deliveries/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Deliveries</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/supplier_payments/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Supplier Payments</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/cash_book/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Cash Book</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/receivables_payables/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Receivables & Payables</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/general_ledger/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>General Ledger</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/financial_statement/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Financial Statement</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/budgets/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Budgets & Planning</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/assets_register/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Asset Register</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/erp_reports/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>ERP Reports</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/inventory_valuation/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Inventory Valuation</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/low_stock_alerts/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Low Stock Alerts</p></a></li>
                        <li class="nav-item"><a href="/smoketech_inventory/modules/files/index.php" class="nav-link"><i class="far fa-circle nav-icon"></i><p>File Manager</p></a></li>
                    </ul>
                </li>

                <!-- Users -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/modules/users/index.php" class="nav-link">

                        <i class="nav-icon fas fa-user-shield"></i>

                        <p>Users</p>

                    </a>

                </li>

                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a href="/smoketech_inventory/modules/settings/index.php" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/smoketech_inventory/modules/admin_reset/index.php" class="nav-link">
                        <i class="nav-icon fas fa-exclamation-triangle text-warning"></i>
                        <p>Admin Reset</p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Logout -->
                <li class="nav-item">

                    <a href="/smoketech_inventory/logout.php" class="nav-link">

                        <i class="nav-icon fas fa-sign-out-alt text-danger"></i>

                        <p>Logout</p>

                    </a>

                </li>

            </ul>

        </nav>

    </div>

</aside>
