<?php
require_once __DIR__ . '/../config/database.php';

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token): bool {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function money($amount) {
    return number_format((float)$amount, 2);
}

function validateIdentifier(string $value): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $value)) {
        throw new InvalidArgumentException('Invalid identifier for database object.');
    }
    return $value;
}

function generateSequentialCode(string $prefix, int $counter): string {
    return sprintf('%s%06d', strtoupper($prefix), $counter);
}

/**
 * Allocates a unique annual document number using a row lock. The sequence table
 * prevents two concurrent requests from receiving the same number.
 */
function getNextDocumentNumber(string $prefix, ?int $year = null): string {
    $prefix = strtoupper(trim($prefix));
    if (!preg_match('/^[A-Z]{2,12}$/', $prefix)) {
        throw new InvalidArgumentException('Invalid document number prefix.');
    }
    $year = $year ?? (int) date('Y');
    $sequenceKey = $prefix . '-' . $year;
    $pdo = getDB();
    if (!tableExists('document_sequences')) {
        throw new RuntimeException('Document sequences are unavailable. Apply the ERP foundation migration first.');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO document_sequences (sequence_key, current_value) VALUES (?, 0)')->execute([$sequenceKey]);
        $select = $pdo->prepare('SELECT current_value FROM document_sequences WHERE sequence_key = ? FOR UPDATE');
        $select->execute([$sequenceKey]);
        $next = ((int) $select->fetchColumn()) + 1;
        $pdo->prepare('UPDATE document_sequences SET current_value = ? WHERE sequence_key = ?')->execute([$next, $sequenceKey]);
        if ($ownsTransaction) $pdo->commit();
        return sprintf('%s-%d-%06d', $prefix, $year, $next);
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function getNextSequentialCode(string $table, string $column, string $prefix): string {
    if (tableExists('document_sequences')) {
        return getNextDocumentNumber($prefix);
    }
    $table = validateIdentifier($table);
    $column = validateIdentifier($column);
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY $column DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    if (!$last) {
        return generateSequentialCode($prefix, 1);
    }
    $number = (int) substr($last, strlen($prefix));
    return generateSequentialCode($prefix, $number + 1);
}

function tableExists(string $tableName): bool {
    $pdo = getDB();
    // Some MySQL/MariaDB versions don't accept parameter placeholders with SHOW statements.
    // Use a quoted literal to avoid syntax errors and SQL injection.
    $q = $pdo->quote($tableName);
    $stmt = $pdo->query("SHOW TABLES LIKE $q");
    return (bool) ($stmt && $stmt->fetchColumn());
}

function ensureAuditLogsTable(): void {
    $pdo = getDB();

    // Prefer the application audit table already present in upgraded databases.
    if (tableExists('system_audit_log')) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        actor_id INT DEFAULT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(100) NOT NULL,
        reference VARCHAR(255) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (entity_type),
        INDEX (reference)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureStockMovementsTable(): void {
    $pdo = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        quantity_change INT NOT NULL,
        movement_type VARCHAR(50) DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        note TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureServiceSchema(): void {
    $pdo = getDB();

    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_code VARCHAR(50) DEFAULT NULL,
        service_name VARCHAR(255) NOT NULL,
        service_category VARCHAR(150) DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        device_type VARCHAR(100) DEFAULT NULL,
        imei_serial VARCHAR(100) DEFAULT NULL,
        technician_id INT DEFAULT NULL,
        labour_cost DECIMAL(12,2) DEFAULT 0,
        service_charge DECIMAL(12,2) DEFAULT 0,
        discount DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        payment_status VARCHAR(50) DEFAULT 'Pending',
        warranty_period VARCHAR(100) DEFAULT NULL,
        service_status VARCHAR(50) DEFAULT 'Pending',
        date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
        completion_date DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        sale_id INT DEFAULT NULL,
        is_deleted TINYINT(1) NOT NULL DEFAULT 0,
        deleted_at DATETIME DEFAULT NULL,
        INDEX (customer_id),
        INDEX (technician_id),
        INDEX (sale_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $serviceColumns = array_column($pdo->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $serviceColumnsToAdd = [
        ['service_code', 'VARCHAR(50) DEFAULT NULL'],
        ['service_name', 'VARCHAR(255) NOT NULL'],
        ['service_category', 'VARCHAR(150) DEFAULT NULL'],
        ['customer_id', 'INT DEFAULT NULL'],
        ['device_type', 'VARCHAR(100) DEFAULT NULL'],
        ['imei_serial', 'VARCHAR(100) DEFAULT NULL'],
        ['technician_id', 'INT DEFAULT NULL'],
        ['labour_cost', 'DECIMAL(12,2) DEFAULT 0'],
        ['service_charge', 'DECIMAL(12,2) DEFAULT 0'],
        ['discount', 'DECIMAL(12,2) DEFAULT 0'],
        ['tax', 'DECIMAL(12,2) DEFAULT 0'],
        ['total_amount', 'DECIMAL(12,2) DEFAULT 0'],
        ['payment_status', 'VARCHAR(50) DEFAULT "Pending"'],
        ['warranty_period', 'VARCHAR(100) DEFAULT NULL'],
        ['service_status', 'VARCHAR(50) DEFAULT "Pending"'],
        ['date_created', 'DATETIME DEFAULT CURRENT_TIMESTAMP'],
        ['completion_date', 'DATETIME DEFAULT NULL'],
        ['notes', 'TEXT DEFAULT NULL'],
        ['created_by', 'INT DEFAULT NULL'],
        ['sale_id', 'INT DEFAULT NULL'],
        ['is_deleted', 'TINYINT(1) NOT NULL DEFAULT 0'],
        ['deleted_at', 'DATETIME DEFAULT NULL'],
    ];
    foreach ($serviceColumnsToAdd as [$column, $definition]) {
        if (!in_array($column, $serviceColumns, true)) {
            $pdo->exec("ALTER TABLE services ADD COLUMN `$column` $definition");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS service_parts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(12,2) DEFAULT 0,
        total_price DECIMAL(12,2) DEFAULT 0,
        INDEX (service_id),
        INDEX (product_id),
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $partsColumns = array_column($pdo->query('DESCRIBE service_parts')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $partsColumnsToAdd = [
        ['service_id', 'INT NOT NULL'],
        ['product_id', 'INT NOT NULL'],
        ['quantity', 'INT NOT NULL DEFAULT 1'],
        ['unit_price', 'DECIMAL(12,2) DEFAULT 0'],
        ['total_price', 'DECIMAL(12,2) DEFAULT 0'],
    ];
    foreach ($partsColumnsToAdd as [$column, $definition]) {
        if (!in_array($column, $partsColumns, true)) {
            $pdo->exec("ALTER TABLE service_parts ADD COLUMN `$column` $definition");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS service_delete_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureErpTables(): void {
    $pdo = getDB();

    $pdo->exec("CREATE TABLE IF NOT EXISTS repairs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        repair_code VARCHAR(50) DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        service_id INT DEFAULT NULL,
        device_type VARCHAR(100) DEFAULT NULL,
        imei_serial VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        technician_id INT DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        priority VARCHAR(50) DEFAULT 'Medium',
        estimated_cost DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        payment_status VARCHAR(50) DEFAULT 'Pending',
        start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        completion_date DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (customer_id),
        INDEX (technician_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_code VARCHAR(50) DEFAULT NULL,
        project_name VARCHAR(255) NOT NULL,
        customer_id INT DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        budget_amount DECIMAL(12,2) DEFAULT 0,
        amount_paid DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Planning',
        description TEXT DEFAULT NULL,
        assigned_to INT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (customer_id),
        INDEX (assigned_to)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_code VARCHAR(50) DEFAULT NULL,
        expense_date DATE DEFAULT NULL,
        category VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        amount DECIMAL(12,2) DEFAULT 0,
        payment_method VARCHAR(50) DEFAULT NULL,
        vendor VARCHAR(150) DEFAULT NULL,
        reference_no VARCHAR(100) DEFAULT NULL,
        payment_status VARCHAR(50) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payroll (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payroll_code VARCHAR(50) DEFAULT NULL,
        employee_id INT NOT NULL,
        pay_period_start DATE DEFAULT NULL,
        pay_period_end DATE DEFAULT NULL,
        basic_salary DECIMAL(12,2) DEFAULT 0,
        allowances DECIMAL(12,2) DEFAULT 0,
        deductions DECIMAL(12,2) DEFAULT 0,
        net_pay DECIMAL(12,2) DEFAULT 0,
        payment_status VARCHAR(50) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (employee_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quotations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quotation_code VARCHAR(50) DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        quotation_date DATE DEFAULT NULL,
        valid_until DATE DEFAULT NULL,
        subtotal DECIMAL(12,2) DEFAULT 0,
        discount DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Draft',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_code VARCHAR(50) DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        invoice_date DATE DEFAULT NULL,
        due_date DATE DEFAULT NULL,
        subtotal DECIMAL(12,2) DEFAULT 0,
        discount DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        amount_paid DECIMAL(12,2) DEFAULT 0,
        balance DECIMAL(12,2) DEFAULT 0,
        payment_status VARCHAR(50) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_code VARCHAR(50) DEFAULT NULL,
        supplier_id INT DEFAULT NULL,
        order_date DATE DEFAULT NULL,
        expected_date DATE DEFAULT NULL,
        subtotal DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Draft',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS deliveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_code VARCHAR(50) DEFAULT NULL,
        delivery_ref VARCHAR(50) DEFAULT NULL,
        po_id INT DEFAULT NULL,
        purchase_order_id INT DEFAULT NULL,
        supplier_id INT DEFAULT NULL,
        delivery_date DATE DEFAULT NULL,
        received_by VARCHAR(150) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (po_id),
        INDEX (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $deliveryColumns = array_column($pdo->query('DESCRIBE deliveries')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $deliveryColumnsToAdd = [
        ['delivery_ref', 'VARCHAR(50) DEFAULT NULL'],
        ['purchase_order_id', 'INT DEFAULT NULL'],
        ['delivery_date', 'DATE DEFAULT NULL'],
        ['status', 'VARCHAR(50) DEFAULT "Pending"'],
        ['notes', 'TEXT DEFAULT NULL'],
        ['created_by', 'INT DEFAULT NULL'],
        ['created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP'],
    ];
    foreach ($deliveryColumnsToAdd as [$column, $definition]) {
        if (!in_array($column, $deliveryColumns, true)) {
            $pdo->exec("ALTER TABLE deliveries ADD COLUMN `$column` $definition");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT NOT NULL,
        product_id INT DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(12,2) DEFAULT 0,
        tax DECIMAL(12,2) DEFAULT 0,
        total_price DECIMAL(12,2) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (purchase_order_id),
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_id INT NOT NULL,
        purchase_order_item_id INT DEFAULT NULL,
        product_id INT DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        quantity INT NOT NULL DEFAULT 1,
        received_quantity INT NOT NULL DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (delivery_id),
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT DEFAULT NULL,
        payment_date DATE DEFAULT NULL,
        amount DECIMAL(12,2) DEFAULT 0,
        payment_method VARCHAR(50) DEFAULT NULL,
        reference_no VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cash_book_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_date DATE DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        entry_type VARCHAR(50) DEFAULT 'income',
        amount DECIMAL(12,2) DEFAULT 0,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cash_opening_balance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        opening_date DATE DEFAULT NULL,
        amount DECIMAL(12,2) DEFAULT 0,
        description VARCHAR(255) DEFAULT 'Opening balance',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS account_ledger_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_date DATE DEFAULT NULL,
        account_name VARCHAR(150) NOT NULL,
        entry_type VARCHAR(20) DEFAULT 'debit',
        amount DECIMAL(12,2) DEFAULT 0,
        description VARCHAR(255) DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (entry_date),
        INDEX (account_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function logAudit(?int $actorId, string $action, string $entityType, ?string $reference = null, ?string $details = null): void {
    $pdo = getDB();
    if (tableExists('system_audit_log')) {
        $targetId = filter_var($reference, FILTER_VALIDATE_INT) ?: null;
        $stmt = $pdo->prepare('INSERT INTO system_audit_log (user_id, action, target_table, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$actorId, $action, $entityType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? 'CLI']);
        return;
    }

    ensureAuditLogsTable();
    $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_id, action, entity_type, reference, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$actorId, $action, $entityType, $reference, $details]);
}

/** Return an application setting without making the application depend on the settings table. */
function appSetting(string $key, string $default = ''): string {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            if (tableExists('app_settings')) {
                $rows = getDB()->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
                $settings = is_array($rows) ? $rows : [];
            }
        } catch (Throwable $e) {
            error_log('Unable to load application settings: ' . $e->getMessage());
        }
    }

    return isset($settings[$key]) && $settings[$key] !== '' ? (string) $settings[$key] : $default;
}

/** Save a whitelisted collection of application settings as one transaction. */
function saveAppSettings(array $settings, ?int $userId = null): void {
    $pdo = getDB();
    if (!tableExists('app_settings')) {
        throw new RuntimeException('Application settings are unavailable. Apply the ERP foundation migration first.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
    );
    $pdo->beginTransaction();
    try {
        foreach ($settings as $key => $value) {
            $statement->execute([$key, $value, $userId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function generateReference($prefix) {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Adjust product stock and log the movement (Phase 6: Automatic Stock Update)
 * $type: 'in' | 'out' | 'adjustment' | 'sale' | 'purchase'
 */
function adjustStock($productId, $qtyChange, $type, $referenceType, $referenceId = null, $note = null) {
    ensureStockMovementsTable();
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT current_stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) throw new Exception("Product not found");

        $typeKey = strtolower((string) $type);
        $allowNegativeStock = in_array($typeKey, ['loss', 'damage', 'theft', 'write_off', 'negative_adjustment'], true);

        $newQty = $product['current_stock'] + $qtyChange;
        if ($newQty < 0 && !$allowNegativeStock) {
            throw new Exception("Insufficient stock");
        }

        $upd = $pdo->prepare("UPDATE products SET current_stock = ? WHERE id = ?");
        $upd->execute([$newQty, $productId]);

        $movement = $pdo->prepare('INSERT INTO stock_movements (product_id, quantity_change, movement_type, reference_type, reference_id, note) VALUES (?, ?, ?, ?, ?, ?)');
        $movement->execute([$productId, $qtyChange, $type, $referenceType, $referenceId, $note]);

        $pdo->commit();
        return $newQty;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getLowStockProducts() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM products WHERE current_stock <= reorder_level ORDER BY current_stock ASC");
    return $stmt->fetchAll();
}

function getOpeningCashBalance(?PDO $pdo = null): float {
    $pdo = $pdo ?: getDB();
    try {
        if (tableExists('cash_opening_balance')) {
            $stmt = $pdo->query("SELECT COALESCE(amount, 0) FROM cash_opening_balance ORDER BY id DESC LIMIT 1");
            return (float) ($stmt ? $stmt->fetchColumn() : 0);
        }
    } catch (Throwable $e) {
        // Ignore and fall back to zero.
    }
    return 0.0;
}

function saveOpeningCashBalance(float $amount, ?string $date = null, ?string $description = 'Opening balance'): void {
    $pdo = getDB();
    ensureErpTables();
    $stmt = $pdo->prepare('INSERT INTO cash_opening_balance (opening_date, amount, description) VALUES (?, ?, ?)');
    $stmt->execute([
        $date ?: date('Y-m-d'),
        max(0.0, $amount),
        $description ?: 'Opening balance',
    ]);
}

function getCashAvailableBalance(?PDO $pdo = null): float {
    $pdo = $pdo ?: getDB();

    try {
        if (tableExists('cash_book_entries')) {
            $openingBalance = getOpeningCashBalance($pdo);
            $income = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount ELSE 0 END), 0) FROM cash_book_entries")->fetchColumn();
            $expense = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN entry_type = 'expense' THEN amount ELSE 0 END), 0) FROM cash_book_entries")->fetchColumn();
            return max(0.0, $openingBalance + $income - $expense);
        }
    } catch (Throwable $e) {
        // Fall back to legacy calculations below.
    }

    try {
        $salesReceived = (float) $pdo->query("SELECT COALESCE(SUM(LEAST(amount_paid, COALESCE(total_amount, 0))), 0) FROM sales")->fetchColumn();
        $overchargeTotal = (float) $pdo->query("SELECT COALESCE(SUM(GREATEST(amount_paid - COALESCE(total_amount, 0), 0)), 0) FROM sales")->fetchColumn();

        $ledgerIn = 0.0;
        $ledgerOut = 0.0;
        if (tableExists('cash_ledger')) {
            try {
                $ledgerIn = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN direction IN ('in','credit','deposit') THEN amount ELSE 0 END), 0) FROM cash_ledger")->fetchColumn();
                $ledgerOut = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN direction IN ('out','debit','withdraw') THEN amount ELSE 0 END), 0) FROM cash_ledger")->fetchColumn();
            } catch (Throwable $e) {
                $ledgerIn = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_ledger")->fetchColumn();
            }
        }

        $mpesaTotal = 0.0;
        if (tableExists('mpesa_transactions')) {
            try {
                $mpesaTotal = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN status = 'Confirmed' THEN amount ELSE 0 END), 0) FROM mpesa_transactions")->fetchColumn();
            } catch (Throwable $e) {
                $mpesaTotal = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM mpesa_transactions")->fetchColumn();
            }
        }

        $paymentsIn = 0.0;
        $paymentsOut = 0.0;
        if (tableExists('payments')) {
            try {
                $paymentsIn = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END), 0) FROM payments")->fetchColumn();
                $paymentsOut = (float) $pdo->query("SELECT COALESCE(SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END), 0) FROM payments")->fetchColumn();
            } catch (Throwable $e) {
                $paymentsIn = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
            }
        }

        $purchasesOut = 0.0;
        if (tableExists('purchases')) {
            try {
                $pFields = array_column($pdo->query('DESCRIBE purchases')->fetchAll(PDO::FETCH_ASSOC), 'Field');
                if (in_array('payment_status', $pFields, true)) {
                    $purchasesOut = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE payment_status = 'Paid'")->fetchColumn();
                } elseif (in_array('is_paid', $pFields, true) || in_array('paid', $pFields, true)) {
                    $flag = in_array('is_paid', $pFields, true) ? 'is_paid' : 'paid';
                    $purchasesOut = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE $flag = 1")->fetchColumn();
                } else {
                    $purchasesOut = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchases")->fetchColumn();
                }
            } catch (Throwable $e) {
                $purchasesOut = 0.0;
            }
        }

        $stockLosses = 0.0;
        if (tableExists('stock_movements') && tableExists('products')) {
            try {
                $stockLosses = (float) $pdo->query("SELECT COALESCE(SUM(ABS(sm.quantity_change) * COALESCE(p.buying_price, 0)), 0) FROM stock_movements sm JOIN products p ON p.id = sm.product_id WHERE sm.movement_type IN ('loss','damage','theft')")->fetchColumn();
            } catch (Throwable $e) {
                $stockLosses = 0.0;
            }
        }

        $refunds = 0.0;
        if (tableExists('refunds')) {
            try {
                $refunds = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM refunds")->fetchColumn();
            } catch (Throwable $e) {
                $refunds = 0.0;
            }
        } elseif (tableExists('returns')) {
            try {
                $refunds = (float) $pdo->query("SELECT COALESCE(SUM(refund_amount), 0) FROM returns")->fetchColumn();
            } catch (Throwable $e) {
                $refunds = 0.0;
            }
        }

        $raw = $salesReceived + $overchargeTotal + $ledgerIn - $ledgerOut + $mpesaTotal + $paymentsIn - $paymentsOut - $purchasesOut - $stockLosses - $refunds;
        return max(0.0, getOpeningCashBalance($pdo) + $raw);
    } catch (Throwable $e) {
        return 0.0;
    }
}

/**
 * Creates a SQL snapshot in the project's exports directory before destructive
 * administration tasks. The file can be imported with MariaDB/MySQL.
 */
function createDatabaseBackup(string $label = 'manual'): string {
    $pdo = getDB();
    $backupDirectory = __DIR__ . '/../exports/backups';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0750, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException('Unable to create the backup directory.');
    }

    $safeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', $label) ?: 'backup';
    $filename = sprintf('%s_%s.sql', $safeLabel, date('Ymd_His'));
    $path = $backupDirectory . '/' . $filename;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dump = [
        '-- SmokeTech ERP database backup',
        '-- Created: ' . date('c'),
        'SET NAMES utf8mb4;',
        'SET FOREIGN_KEY_CHECKS=0;',
        '',
    ];

    foreach ($tables as $table) {
        $table = validateIdentifier((string) $table);
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $create['Create Table'] ?? array_values($create ?: [])[1] ?? null;
        if (!$createSql) {
            throw new RuntimeException("Unable to read schema for backup table $table.");
        }
        $dump[] = "DROP TABLE IF EXISTS `$table`;";
        $dump[] = $createSql . ';';

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
            }
            $dump[] = "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ');';
        }
        $dump[] = '';
    }

    $dump[] = 'SET FOREIGN_KEY_CHECKS=1;';
    if (file_put_contents($path, implode(PHP_EOL, $dump) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write the database backup.');
    }

    return 'exports/backups/' . $filename;
}

function resetSystemData(string $adminPassword, int $actorId): array {
    $pdo = getDB();

    $userStmt = $pdo->prepare('SELECT id, password FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$actorId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($adminPassword, $user['password'] ?? '')) {
        throw new RuntimeException('Admin password is incorrect.');
    }

    $backupPath = createDatabaseBackup('before_system_reset');

    $tablesToReset = [
        'sale_items',
        'mpesa_transactions',
        'sales',
        'purchase_items',
        'purchases',
        'purchase_order_items',
        'purchase_orders',
        'delivery_items',
        'deliveries',
        'supplier_payments',
        'cash_book_entries',
        'cash_opening_balance',
        'account_ledger_entries',
        'expenses',
        'repair_parts',
        'repairs',
        'damaged_goods',
        'project_expenses',
        'external_projects',
        'projects',
        'quotations',
        'invoices',
        'stock_movements',
    ];

    foreach ($tablesToReset as $table) {
        try {
            $pdo->exec("DELETE FROM `$table`");
            $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
        } catch (Throwable $e) {
            // Ignore missing tables or unsupported operations and continue.
        }
    }

    $pdo->exec("UPDATE products SET current_stock = 0, reorder_level = 0, buying_price = 0, selling_price = 0");

    $cashLikeColumns = ['cash_available', 'available_cash', 'cash_balance', 'cash_on_hand', 'closing_balance', 'balance'];
    $cashTables = ['customers', 'sales', 'invoices', 'users', 'suppliers', 'products'];

    foreach ($cashTables as $table) {
        try {
            $colsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $columns = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
            foreach ($cashLikeColumns as $column) {
                if (in_array($column, $columns, true)) {
                    $pdo->exec("UPDATE `$table` SET `$column` = GREATEST(COALESCE(`$column`, 0), 0)");
                }
            }
        } catch (Throwable $e) {
            // Ignore missing tables or unavailable columns.
        }
    }

    foreach (['customers', 'suppliers'] as $table) {
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            foreach (['current_balance', 'balance', 'outstanding_balance'] as $column) {
                if (in_array($column, $columns, true)) {
                    $pdo->exec("UPDATE `$table` SET `$column` = 0");
                }
            }
        } catch (Throwable $e) {
            // Keep the reset compatible with earlier schemas.
        }
    }

    try {
        $pdo->exec("UPDATE sales SET amount_paid = 0, balance = 0, payment_status = 'Pending'");
    } catch (Throwable $e) {
        // Ignore if those columns are unavailable.
    }

    try {
        $pdo->exec("UPDATE invoices SET amount_paid = 0, balance = 0, payment_status = 'Pending'");
    } catch (Throwable $e) {
        // Ignore if those columns are unavailable.
    }

    logAudit($actorId, 'SYSTEM_RESET', 'system', null, 'Operational data reset after backup: ' . $backupPath);

    return ['success' => true, 'backup_path' => $backupPath];
}
