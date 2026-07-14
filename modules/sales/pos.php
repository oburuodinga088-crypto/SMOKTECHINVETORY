<?php
require '../../includes/auth.php';
require_once '../../config/database.php';
requireLogin();

$stmt = $conn->query("SELECT id, product_name, selling_price, current_stock FROM products WHERE current_stock > 0 ORDER BY product_name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$customers = $conn->query("SELECT id, customer_name, phone FROM customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header"><h1><i class="fas fa-cash-register"></i> Point of Sale</h1></section>
    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                            <input id="productSearch" class="form-control" placeholder="Search products or scan a barcode">
                        </div>
                        <table class="table table-bordered">
                            <thead><tr><th>Product</th><th>Stock</th><th>Price</th><th>Qty</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($products as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td><?= $row['current_stock'] ?></td>
                                    <td><?= number_format($row['selling_price'],2) ?></td>
                                    <td><input type="number" class="form-control product-qty" value="1" min="1" max="<?= (int)$row['current_stock'] ?>"></td>
                                    <td><button type="button" class="btn btn-success btn-sm addCart" data-id="<?= (int)$row['id'] ?>" data-name="<?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= (float)$row['selling_price'] ?>" data-stock="<?= (int)$row['current_stock'] ?>"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-body">
                        <table class="table table-sm" id="cartTable"><thead><tr><th>Item</th><th>Qty</th><th>Total</th><th></th></tr></thead><tbody><tr><td colspan="4" class="text-center text-muted">Your cart is empty.</td></tr></tbody></table>
                        <hr>
                        <h4>Total: KSh <span id="grandTotal">0.00</span></h4>
                        <div class="form-group"><label>Customer <span id="creditCustomerHint" class="text-danger d-none">(required for unpaid credit)</span></label><select id="customerId" class="form-control"><option value="">Walk-in customer</option><?php foreach ($customers as $customer): ?><option value="<?= (int)$customer['id'] ?>"><?= htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8') ?><?= $customer['phone'] ? ' — ' . htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') : '' ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label>Payment Method</label><select id="paymentMethod" class="form-control"><option>Cash</option><option>M-Pesa</option><option>Credit</option></select></div>
                        <div class="form-group"><label>Amount Paid</label><input type="number" id="amountPaid" class="form-control" min="0" step="0.01"></div>
                        <div class="form-group"><label>M-Pesa Code (optional)</label><input type="text" id="mpesaCode" class="form-control"></div>
                        <button class="btn btn-primary btn-block" id="checkoutBtn">Complete Sale</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let cart = [];

    $(document).on("click", ".addCart", function() {
        let row = $(this).closest('tr');
        let qty = parseInt(row.find('input').val());
        let id = $(this).data("id");
        let name = $(this).data("name");
        let price = parseFloat($(this).data("price"));

        if (!Number.isInteger(qty) || qty < 1) return alert('Enter a valid quantity.');
        let stock = parseInt($(this).data("stock"));
        let found = cart.find(i => i.id == id);
        if(found) found.qty += qty;
        else cart.push({id, name, price, qty, stock});
        let cartItem = cart.find(i => i.id == id);
        if (cartItem.qty > stock) {
            cartItem.qty = stock;
            alert(`Only ${stock} units are available.`);
        }
        
        updateUI();
    });

    function updateUI() {
        let tbody = $("#cartTable tbody");
        tbody.empty();
        if (!cart.length) {
            tbody.append($('<tr>').append($('<td>', { colspan: 4, class: 'text-center text-muted', text: 'Your cart is empty.' })));
            $("#grandTotal").text('0.00');
            return;
        }
        let total = 0;
        cart.forEach(item => {
            total += (item.qty * item.price);
            let quantityControl = $('<div>', { class: 'btn-group btn-group-sm' })
                .append($('<button>', { type: 'button', class: 'btn btn-outline-secondary cart-decrease', text: '−', 'data-id': item.id }))
                .append($('<span>', { class: 'btn btn-light disabled', text: item.qty }))
                .append($('<button>', { type: 'button', class: 'btn btn-outline-secondary cart-increase', text: '+', 'data-id': item.id }));
            tbody.append($('<tr>')
                .append($('<td>', { text: item.name }))
                .append($('<td>').append(quantityControl))
                .append($('<td>', { text: (item.qty * item.price).toFixed(2) }))
                .append($('<td>').append($('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger cart-remove', html: '<i class="fas fa-trash"></i>', 'data-id': item.id, title: 'Remove item' }))));
        });
        $("#grandTotal").text(total.toFixed(2));
    }

    $(document).on('click', '.cart-decrease, .cart-increase, .cart-remove', function() {
        let id = $(this).data('id');
        let item = cart.find(i => i.id == id);
        if ($(this).hasClass('cart-remove')) cart = cart.filter(i => i.id != id);
        else if ($(this).hasClass('cart-decrease') && item) item.qty--;
        else if (item) item.qty = Math.min(item.qty + 1, item.stock);
        cart = cart.filter(i => i.qty > 0);
        updateUI();
    });

    $('#productSearch').on('input', function() {
        const term = $(this).val().toLowerCase().trim();
        $('tbody tr', '.table-bordered').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });

    $('#paymentMethod, #amountPaid').on('change keyup', function() {
        const isCredit = $('#paymentMethod').val() === 'Credit';
        const total = parseFloat($('#grandTotal').text()) || 0;
        const paid = parseFloat($('#amountPaid').val()) || 0;
        $('#creditCustomerHint').toggleClass('d-none', !isCredit || paid >= total);
    });

    $("#checkoutBtn").click(function() {
        if(cart.length == 0) return alert("Cart is empty");
        const paymentMethod = $('#paymentMethod').val();
        const total = parseFloat($('#grandTotal').text());
        const amountPaid = parseFloat($('#amountPaid').val()) || (paymentMethod === 'Credit' ? 0 : total);
        if (paymentMethod === 'Credit' && amountPaid < total && !$('#customerId').val()) return alert('Select a customer for an unpaid credit sale.');
        $('#checkoutBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: "save_sale.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({ cart: cart, customer_id: $('#customerId').val() || null, amount_paid: amountPaid, payment_method: paymentMethod, mpesa_code: $('#mpesaCode').val() }),
            success: function(res) {
                let data = (typeof res === 'string') ? JSON.parse(res) : res;
                if(data.success) { alert("Sale Saved!"); window.location = "pos.php?status=success"; }
                else alert(data.message || 'Unable to save sale.');
            },
            error: function(xhr) {
                let message = 'Unable to save sale.';
                try { message = xhr.responseJSON.message || message; } catch (e) {}
                alert(message);
            },
            complete: function() { $('#checkoutBtn').prop('disabled', false).html('Complete Sale'); }
        });
    });
});
</script>
