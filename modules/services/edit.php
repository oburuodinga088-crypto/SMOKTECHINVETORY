<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireLogin();

ensureServiceSchema();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$service) { header('Location: index.php'); exit; }

$parts = $conn->prepare('SELECT sp.*, p.product_name FROM service_parts sp LEFT JOIN products p ON p.id = sp.product_id WHERE sp.service_id = ?');
$parts->execute([$id]);
$parts = $parts->fetchAll(PDO::FETCH_ASSOC);

// detect schema variations
$svcFields = array_column($conn->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC), 'Field');
$paymentCol = in_array('payment_status', $svcFields, true) ? 'payment_status' : (in_array('status', $svcFields, true) ? 'status' : null);
$serviceStatusCol = in_array('service_status', $svcFields, true) ? 'service_status' : $paymentCol;
$totalCol = in_array('total_amount', $svcFields, true) ? 'total_amount' : (in_array('standard_price', $svcFields, true) ? 'standard_price' : (in_array('estimated_cost', $svcFields, true) ? 'estimated_cost' : null));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $svcName = trim($_POST['service_name'] ?? $service['service_name']);
        $svcCategory = trim($_POST['service_category'] ?? $service['service_category']);
        $newServiceStatus = trim($_POST['service_status'] ?? ($service[$serviceStatusCol] ?? ''));
        $newPaymentStatus = trim($_POST['payment_status'] ?? ($service[$paymentCol] ?? ''));

        $conn->beginTransaction();
        // Build update dynamically depending on available columns
        $updateParts = ['service_name = ?', 'service_category = ?'];
        $params = [$svcName, $svcCategory];
        if ($serviceStatusCol) { $updateParts[] = "$serviceStatusCol = ?"; $params[] = $newServiceStatus; }
        if ($paymentCol && $paymentCol !== $serviceStatusCol) { $updateParts[] = "$paymentCol = ?"; $params[] = $newPaymentStatus; }
        $params[] = $id;
        $updSql = 'UPDATE services SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
        $upd = $conn->prepare($updSql);
        $upd->execute($params);

        // Decide if we should create a sale: if payment column exists and equals 'Paid', or if payment_status not present but status was changed to 'Active' and no sale linked
        $shouldCreateSale = false;
        $currentPaymentVal = $service[$paymentCol] ?? null;
        $updatedPaymentVal = $newPaymentStatus ?: ($newServiceStatus ?? null);
        if ($paymentCol && strtolower($updatedPaymentVal) === 'paid' && empty($service['sale_id'])) {
            $shouldCreateSale = true;
        } elseif (!$paymentCol && $serviceStatusCol && strtolower($newServiceStatus) === 'active' && empty($service['sale_id'])) {
            $shouldCreateSale = true;
        }

        if ($shouldCreateSale) {
            $total = $totalCol ? (float)($service[$totalCol] ?? 0) : 0.0;
            $amountPaid = $total; $balance = 0; $paymentMethod = 'Cash';
            $saleIns = $conn->prepare('INSERT INTO sales (customer_id, sale_date, total_amount, subtotal, amount_paid, balance, payment_method, mpesa_code, payment_status, cashier_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)');
            $saleIns->execute([$service['customer_id'] ?: null, $total, $total, $amountPaid, $balance, $paymentMethod, null, 'Paid', $_SESSION['user_id'] ?? null]);
            $saleId = $conn->lastInsertId();

            $itemIns = $conn->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, selling_price, buying_price) VALUES (?, ?, ?, ?, ?)');
            foreach ($parts as $p) {
                $prod = $conn->prepare('SELECT buying_price FROM products WHERE id = ?');
                $prod->execute([$p['product_id']]);
                $prodRow = $prod->fetch(PDO::FETCH_ASSOC);
                $buying = $prodRow['buying_price'] ?? 0;
                $itemIns->execute([$saleId, $p['product_id'], $p['quantity'], $p['unit_price'], $buying]);
            }

            $link = $conn->prepare('UPDATE services SET sale_id = ? WHERE id = ?');
            $link->execute([$saleId, $id]);
        }

        $conn->commit();
        logAudit($_SESSION['user_id'] ?? null, 'update', 'service', $service['service_code'] ?? (string)$service['id'], 'Updated service record');
        header('Location: view.php?id=' . $id);
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
    <section class="content-header"><h1>Edit Service</h1></section>
    <section class="content">
        <div class="card"><div class="card-body">
            <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $err) echo '<div>'.htmlspecialchars($err,ENT_QUOTES,'UTF-8').'</div>'; ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Service Name</label><input name="service_name" class="form-control" value="<?= htmlspecialchars($service['service_name'], ENT_QUOTES,'UTF-8') ?>"></div>
                <div class="form-group"><label>Category</label><input name="service_category" class="form-control" value="<?= htmlspecialchars($service['service_category'], ENT_QUOTES,'UTF-8') ?>"></div>
                <div class="form-group"><label>Service Status</label><input name="service_status" class="form-control" value="<?= htmlspecialchars($service['service_status'], ENT_QUOTES,'UTF-8') ?>"></div>
                <div class="form-group"><label>Payment Status</label><select name="payment_status" class="form-control"><option value="Pending" <?= $service['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option><option value="Paid" <?= $service['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option></select></div>
                <div><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="view.php?id=<?= $id ?>">Cancel</a></div>
            </form>
        </div></div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
