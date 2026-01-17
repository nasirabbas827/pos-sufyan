<?php
session_start();
include('config.php');

// Enable error reporting during development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =========================================================================
// Handle successful sale redirect (very top - no output before this)
if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['just_completed_sale_id'])) {
    $sale_id = (int)$_SESSION['just_completed_sale_id'];
    unset($_SESSION['just_completed_sale_id']);

    // Show nice confirmation page with manual print button
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sale Completed</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <style>
            body { background: linear-gradient(135deg, #e0f7fa, #f0f4ff); min-height: 100vh; }
            .success-card { max-width: 500px; margin: 100px auto; }
        </style>
    </head>
    <body class="d-flex align-items-center justify-content-center">
        <div class="card shadow-lg success-card text-center p-5">
            <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
            <h2 class="text-success mb-3">Sale Completed!</h2>
            <p class="lead mb-4">Transaction successful.<br>Invoice has been created.</p>
            
            <a href="print_invoice.php?sale_id=<?= $sale_id ?>" target="_blank" 
               class="btn btn-success btn-lg w-100 mb-3 py-3">
                <i class="fas fa-print me-2"></i> Print Receipt Now
            </a>
            
            <a href="sales.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-plus me-2"></i> New Sale
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================================================

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "cashier") {
    header("Location: ../login.php");
    exit();
}

$cashier_id = $_SESSION["id"];

// =========================================================================
// AJAX: Search Customers
if (isset($_GET['action']) && $_GET['action'] === 'search_customers') {
    header('Content-Type: application/json');
    $term = isset($_GET['term']) ? "%" . trim($_GET['term']) . "%" : "%";

    $sql = "SELECT id, name, phone_number 
            FROM customers 
            WHERE name LIKE ? OR phone_number LIKE ? 
               OR id = 1
            ORDER BY CASE WHEN id = 1 THEN 0 ELSE 1 END, name
            LIMIT 12";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $display = ($row['id'] == 1) ? "Walk-in / Cash Customer" : $row['name'];
        if ($row['phone_number'] && $row['id'] != 1) $display .= " • " . $row['phone_number'];
        
        $customers[] = [
            'id'   => $row['id'],
            'text' => htmlspecialchars($display),
            'name' => htmlspecialchars($row['name'])
        ];
    }
    echo json_encode(['results' => $customers]);
    $stmt->close();
    exit;
}

// =========================================================================
// AJAX: Search Products (name or category)
if (isset($_GET['action']) && $_GET['action'] === 'search_products') {
    header('Content-Type: application/json');
    $term = isset($_GET['term']) ? "%" . trim($_GET['term']) . "%" : "%";

    $sql = "SELECT p.id, p.product_name, p.selling_price, p.stock_quantity, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.product_name LIKE ? 
               OR c.name LIKE ?
               OR CAST(p.id AS CHAR) LIKE ?
            LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $display = $row['product_name'];
        if ($row['category_name']) $display .= " • " . $row['category_name'];
        $display .= "  (Stock: " . max(0, $row['stock_quantity']) . ")";

        $products[] = [
            'id'                => $row['id'],
            'text'              => htmlspecialchars($display),
            'name'              => htmlspecialchars($row['product_name']),
            'price'             => (float)$row['selling_price'],
            'quantity_available'=> (int)max(0, $row['stock_quantity'])
        ];
    }
    echo json_encode(['results' => $products]);
    $stmt->close();
    exit;
}

// =========================================================================
// SALE PROCESSING
// =========================================================================
$sale_error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['finalize_sale'])) {

    $customer_id    = (int)($_POST['customer_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $grand_total    = (float)($_POST['grand_total'] ?? 0);
    $total_discount = (float)($_POST['total_discount'] ?? 0);
    $payable_amount = max(0, $grand_total - $total_discount);

    $items = $_POST['items'] ?? [];

    if ($customer_id <= 0 || $grand_total <= 0 || empty($items)) {
        $sale_error_msg = "Please select a customer and add at least one product.";
    } else {
        $conn->begin_transaction();
        try {
            // Invoice number format: INV-YYMMDD-HHMMSS-Cxx
            $invoice_number = 'INV-' . date('ymdHis') . '-C' . str_pad($cashier_id, 2, '0', STR_PAD_LEFT);

            $sql_sale = "INSERT INTO sales (
                invoice_number, customer_id, cashier_id,
                total_amount, discount_amount, payable_amount,
                payment_method, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql_sale);
            $stmt->bind_param("siiddds",
                $invoice_number,
                $customer_id,
                $cashier_id,
                $grand_total,
                $total_discount,
                $payable_amount,
                $payment_method
            );
            $stmt->execute();
            $sale_id = $conn->insert_id;
            $stmt->close();

            foreach ($items as $item) {
                $pid   = (int)($item['product_id']   ?? 0);
                $qty   = (int)($item['quantity']     ?? 0);
                $price = (float)($item['unit_price'] ?? 0);

                if ($pid > 0 && $qty > 0 && $price >= 0) {
                    $subtotal = $qty * $price;

                    // Insert item
                    $sql_item = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                                 VALUES (?, ?, ?, ?, ?)";
                    $si = $conn->prepare($sql_item);
                    $si->bind_param("iiidd", $sale_id, $pid, $qty, $price, $subtotal);
                    $si->execute();
                    $si->close();

                    // Reduce stock
                    $sql_stock = "UPDATE products 
                                  SET stock_quantity = stock_quantity - ? 
                                  WHERE id = ? AND stock_quantity >= ?";
                    $ss = $conn->prepare($sql_stock);
                    $ss->bind_param("iii", $qty, $pid, $qty);
                    $ss->execute();
                    if ($ss->affected_rows === 0) {
                        throw new Exception("Insufficient stock for product ID $pid");
                    }
                    $ss->close();
                }
            }

            $conn->commit();

            // Success → redirect to confirmation page
            $_SESSION['just_completed_sale_id'] = $sale_id;
            header("Location: sales.php?success=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $sale_error_msg = "Sale failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS • New Sale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .card-header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }
        .grand-total { font-size: 2.1rem; font-weight: 700; color: #198754; }
        .payable { 
            font-size: 2.3rem; 
            font-weight: 800; 
            color: #0d6efd; 
            background: #e7f1ff; 
            padding: 0.6rem 1.2rem; 
            border-radius: 12px; 
        }
        .select2-container--default .select2-selection--single {
            height: 42px;
            padding: 6px 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-cash-register me-3 text-primary"></i>New Sale</h2>
        <a href="cashier_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if ($sale_error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $sale_error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Sales Transaction</h5>
        </div>
        <div class="card-body p-4">

            <form method="POST" id="saleForm">

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Date & Time</label>
                        <input type="text" class="form-control" value="<?= date('d M Y  •  h:i A') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cashier</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Cashier') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Online">Online / Mobile</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-select" style="width:100%" required>
                        <option></option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-shopping-basket me-2"></i>Cart Items</h5>
                    <button type="button" class="btn btn-success" id="addItemBtn">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th width="110">Qty</th>
                                <th width="140">Unit Price</th>
                                <th width="140">Subtotal</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Start adding products...</td></tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2"></td>
                                <td class="text-end fw-bold">Total:</td>
                                <td class="grand-total" id="grandTotalDisplay">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <td class="text-end fw-bold">Discount:</td>
                                <td>
                                    <input type="number" name="total_discount" id="totalDiscount" 
                                           class="form-control text-end" value="0.00" step="0.01" min="0">
                                </td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="2"></td>
                                <td class="text-end fw-bold fs-4">Payable Amount:</td>
                                <td class="payable" id="payableDisplay">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <input type="hidden" name="grand_total" id="grandTotalInput" value="0">

                <div class="mt-4 text-end">
                    <button type="submit" name="finalize_sale" class="btn btn-lg btn-success px-5 py-3">
                        <i class="fas fa-check-circle me-2"></i> Complete & Print Receipt
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let rowCounter = 0;

$(function() {

    // Default to Walk-in customer
    $('#customer_id').select2({
        placeholder: "Search customer or select Walk-in...",
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '?action=search_customers',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term }),
            processResults: data => ({ results: data.results })
        }
    }).val(1).trigger('change');

    $('#addItemBtn').click(() => {
        if ($('#itemsTable tbody tr td').length === 1) {
            $('#itemsTable tbody').empty();
        }
        addRow();
    });

    function addRow() {
        rowCounter++;
        const html = `
        <tr data-row="${rowCounter}">
            <td>
                <select name="items[${rowCounter}][product_id]" class="form-select product-select" required>
                    <option value="">Search by name or category...</option>
                </select>
            </td>
            <td>
                <input type="number" name="items[${rowCounter}][quantity]" class="form-control text-center item-qty" value="1" min="1" required>
            </td>
            <td>
                <input type="number" name="items[${rowCounter}][unit_price]" class="form-control text-end item-price" readonly step="0.01">
            </td>
            <td class="text-end fw-bold subtotal">0.00</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
        $('#itemsTable tbody').append(html);

        $(`select[name="items[${rowCounter}][product_id]"]`).select2({
            placeholder: "Search product...",
            ajax: {
                url: '?action=search_products',
                dataType: 'json',
                delay: 250,
                data: params => ({ term: params.term }),
                processResults: data => ({ results: data.results })
            }
        }).on('select2:select', e => {
            const data = e.params.data;
            const row = $(e.target).closest('tr');
            row.find('.item-price').val(data.price.toFixed(2));
            row.find('.item-qty').val(1).trigger('input');
        });
    }

    $('#itemsTable').on('input', '.item-qty, .item-price', calculate);
    $('#totalDiscount').on('input', calculate);

    $('#itemsTable').on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#itemsTable tbody tr').length === 0) {
            $('#itemsTable tbody').html('<tr><td colspan="5" class="text-center py-5 text-muted">Start adding products...</td></tr>');
        }
        calculate();
    });

    function calculate() {
        let total = 0;
        $('#itemsTable tbody tr').each(function() {
            const qty   = parseInt($(this).find('.item-qty').val())   || 0;
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const sub   = qty * price;
            $(this).find('.subtotal').text(sub.toFixed(2));
            total += sub;
        });

        const disc    = parseFloat($('#totalDiscount').val()) || 0;
        const payable = Math.max(0, total - disc);

        $('#grandTotalDisplay').text(total.toFixed(2));
        $('#grandTotalInput').val(total);
        $('#payableDisplay').text(payable.toFixed(2));
    }
});
</script>
</body>
</html>