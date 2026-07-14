<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

ensureServiceSchema();

$customers = $conn->query('SELECT id, customer_name FROM customers ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query('SELECT id, product_name, current_stock, selling_price FROM products ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
$technicians = $conn->query('SELECT id, fullname FROM users ORDER BY fullname')->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $svcCode = getNextSequentialCode('services', 'service_code', 'SVC');
        $svcName = trim($_POST['service_name'] ?? '');
        $svcCategory = trim($_POST['service_category'] ?? '');
        $customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $deviceType = trim($_POST['device_type'] ?? '');
        $imei = trim($_POST['imei_serial'] ?? '');
        $technicianId = filter_var($_POST['technician_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $labour = floatval($_POST['labour_cost'] ?? 0);
        $serviceCharge = floatval($_POST['service_charge'] ?? 0);
        $discount = floatval($_POST['discount'] ?? 0);
        $tax = floatval($_POST['tax'] ?? 0);
        $warranty = trim($_POST['warranty_period'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $parts = $_POST['parts'] ?? [];

        // Calculate totals
        $partsTotal = 0.0;
        foreach ($parts as $p) {
            $pid = (int)($p['product_id'] ?? 0);
            $qty = (int)($p['quantity'] ?? 0);
            $price = floatval($p['unit_price'] ?? 0);
            $partsTotal += $qty * $price;
        }
        $total = $partsTotal + $labour + $serviceCharge - $discount + $tax;

        $conn->beginTransaction();
        $ins = $conn->prepare('INSERT INTO services (service_code, service_name, service_category, customer_id, device_type, imei_serial, technician_id, labour_cost, service_charge, discount, tax, total_amount, payment_status, warranty_period, service_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $status = 'Pending';
        $ins->execute([$svcCode, $svcName, $svcCategory, $customerId, $deviceType, $imei, $technicianId, $labour, $serviceCharge, $discount, $tax, $total, $status, $warranty, 'Pending', $notes, $_SESSION['user_id']]);
        $serviceId = $conn->lastInsertId();

        // Handle parts: reduce stock and record service_parts
        foreach ($parts as $p) {
            $pid = (int)($p['product_id'] ?? 0);
            $qty = (int)($p['quantity'] ?? 0);
            $price = floatval($p['unit_price'] ?? 0);
            if ($pid && $qty > 0) {
                // adjust stock (negative change)
                adjustStock($pid, -$qty, 'out', 'service', $serviceId, 'Part used for service ' . $svcCode);
                $lineTotal = $qty * $price;
                $si = $conn->prepare('INSERT INTO service_parts (service_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                $si->execute([$serviceId, $pid, $qty, $price, $lineTotal]);
            }
        }

        $conn->commit();
        logAudit($_SESSION['user_id'] ?? null, 'create', 'service', $svcCode, 'Created service record');
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $errors[] = $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><h1>Create Service</h1></section>
    <section class="content">
        <div class="card">
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger"><?php foreach($errors as $err) echo '<div>'.htmlspecialchars($err,ENT_QUOTES,'UTF-8').'</div>'; ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label>Service Name</label><input name="service_name" class="form-control" required></div>
                            <div class="form-group"><label>Category</label><input name="service_category" class="form-control"></div>
                            <div class="form-group"><label>Customer</label><select name="customer_id" class="form-control"><option value="">-- None --</option><?php foreach($customers as $c) echo "<option value=\"{$c['id']}\">".htmlspecialchars($c['customer_name'],ENT_QUOTES,'UTF-8')."</option>"; ?></select></div>
                            <div class="form-group"><label>Device Type</label><input name="device_type" class="form-control"></div>
                            <div class="form-group"><label>IMEI / Serial</label><input name="imei_serial" class="form-control"></div>
                            <div class="form-group"><label>Technician</label><select name="technician_id" class="form-control"><option value="">-- None --</option><?php foreach($technicians as $t) echo "<option value=\"{$t['id']}\">".htmlspecialchars($t['fullname'],ENT_QUOTES,'UTF-8')."</option>"; ?></select></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Labour Cost</label><input name="labour_cost" type="number" step="0.01" class="form-control" value="0"></div>
                            <div class="form-group"><label>Service Charge</label><input name="service_charge" type="number" step="0.01" class="form-control" value="0"></div>
                            <div class="form-group"><label>Discount</label><input name="discount" type="number" step="0.01" class="form-control" value="0"></div>
                            <div class="form-group"><label>Tax</label><input name="tax" type="number" step="0.01" class="form-control" value="0"></div>
                            <div class="form-group"><label>Warranty Period</label><input name="warranty_period" class="form-control"></div>
                            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
                        </div>
                    </div>

                    <h5>Parts Used</h5>
                    <div id="partsArea">
                        <div class="row part-row mb-2">
                            <div class="col-md-6"><select name="parts[0][product_id]" class="form-control"><?php foreach($products as $p) echo "<option value=\"{$p['id']}\">".htmlspecialchars($p['product_name'],ENT_QUOTES,'UTF-8')." (Stock: {$p['current_stock']})</option>"; ?></select></div>
                            <div class="col-md-2"><input name="parts[0][quantity]" type="number" class="form-control" value="1" min="1"></div>
                            <div class="col-md-2"><input name="parts[0][unit_price]" type="number" step="0.01" class="form-control" value="0"></div>
                            <div class="col-md-2"><button type="button" class="btn btn-danger removePart">Remove</button></div>
                        </div>
                    </div>
                    <div class="mb-3"><button type="button" id="addPart" class="btn btn-sm btn-outline-secondary">Add Part</button></div>

                    <div><button class="btn btn-primary">Save Service</button> <a href="index.php" class="btn btn-secondary">Cancel</a></div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
let partIndex = 1;
document.getElementById('addPart').addEventListener('click', function(){
    const tpl = document.querySelector('.part-row').cloneNode(true);
    tpl.querySelectorAll('input').forEach(i=>i.value='');
    tpl.querySelector('select').name = `parts[${partIndex}][product_id]`;
    tpl.querySelector('input[name^="parts"]').name = `parts[${partIndex}][quantity]`;
    tpl.querySelector('input[type="number"][step]')?.setAttribute('name', `parts[${partIndex}][unit_price]`);
    document.getElementById('partsArea').appendChild(tpl);
    partIndex++;
});

document.getElementById('partsArea').addEventListener('click', function(e){
    if (e.target && e.target.classList.contains('removePart')) {
        const row = e.target.closest('.part-row');
        if (row) row.remove();
    }
});
</script>