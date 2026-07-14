<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();
ensureErpTables();

$conn = getDB();

$customerReceivables = $conn->query(
    "SELECT c.id, c.customer_name, COALESCE(SUM(CASE WHEN s.payment_status = 'Pending' THEN s.balance ELSE 0 END), 0) AS outstanding
     FROM customers c
     LEFT JOIN sales s ON s.customer_id = c.id
     GROUP BY c.id, c.customer_name
     HAVING outstanding > 0
     ORDER BY outstanding DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$invoiceReceivables = $conn->query(
    "SELECT i.id, i.invoice_code, c.customer_name, i.total_amount, i.amount_paid, i.balance, i.due_date, i.payment_status
     FROM invoices i
     LEFT JOIN customers c ON c.id = i.customer_id
     WHERE i.balance > 0
     ORDER BY i.due_date ASC, i.id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$supplierPayables = $conn->query(
    "SELECT s.id, s.supplier_name,
            GREATEST(
                COALESCE((SELECT SUM(po.total_amount) FROM purchase_orders po WHERE po.supplier_id = s.id AND po.status <> 'Completed'), 0)
                - COALESCE((SELECT SUM(sp.amount) FROM supplier_payments sp WHERE sp.supplier_id = s.id), 0),
                0
            ) AS outstanding
     FROM suppliers s
     HAVING outstanding > 0
     ORDER BY outstanding DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$overdueInvoices = array_filter($invoiceReceivables, function ($row) {
    return !empty($row['due_date']) && $row['due_date'] < date('Y-m-d');
});

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Receivables & Payables</h1></section>
  <section class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Customer Receivables</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Customer</th><th>Outstanding</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($customerReceivables as $row): ?>
                <tr><td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td>KSh <?= number_format((float)($row['outstanding'] ?? 0), 2) ?></td><td><a class="btn btn-sm btn-success" href="../customers/customer.php">Clear debt</a></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Supplier Payables</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Supplier</th><th>Outstanding</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($supplierPayables as $row): ?>
                <tr><td><?= htmlspecialchars($row['supplier_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td>KSh <?= number_format((float)($row['outstanding'] ?? 0), 2) ?></td><td><a class="btn btn-sm btn-success" href="../supplier_payments/index.php">Clear debt</a></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Invoice Receivables</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($invoiceReceivables as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['invoice_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                  <td>KSh <?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                  <td>KSh <?= number_format((float)($row['amount_paid'] ?? 0), 2) ?></td>
                  <td>KSh <?= number_format((float)($row['balance'] ?? 0), 2) ?></td>
                  <td><?= htmlspecialchars($row['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($row['payment_status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card card-warning">
          <div class="card-header"><h3 class="card-title">Overdue Invoices</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead><tr><th>Invoice</th><th>Customer</th><th>Due Date</th><th>Balance</th></tr></thead>
              <tbody>
                <?php foreach ($overdueInvoices as $row): ?>
                <tr><td><?= htmlspecialchars($row['invoice_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td>KSh <?= number_format((float)($row['balance'] ?? 0), 2) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
