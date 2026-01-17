<?php
session_start();
include('config.php');

if (!isset($_GET['sale_id']) || !is_numeric($_GET['sale_id'])) {
    die("Invalid sale ID");
}

$sale_id = (int)$_GET['sale_id'];

$sql = "
    SELECT s.*, c.name AS customer_name, u.full_name AS cashier_name , c.phone_number As customer_phone
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.cashier_id = u.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) {
    die("Sale not found");
}

$items_sql = "
    SELECT si.*, p.product_name
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
    ORDER BY si.id
";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->bind_param("i", $sale_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= htmlspecialchars($sale['invoice_number']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #2c3e50; }
        .meta { font-size: 0.95rem; color: #666; }
        .table th { background: #f1f5f9; }
        .total-row { font-weight: bold; font-size: 1.2rem; }
        .thankyou { text-align: center; margin-top: 40px; color: #555; font-style: italic; }
        @media print {
            body { background: white; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="invoice-box">
    <div class="header">
        <h1>Karobar.com</h1>
        <p>Lahore Near Shahi Mosque | Phone: +92 317-9990001 | Email: info@karobar.com</p>
        <h4>Tax Invoice</h4>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <strong>Bill To:</strong><br>
            <?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?><br>
            <?php if ($sale['customer_name'] !== 'Walk-in Customer'): ?>
                Phone: <?= htmlspecialchars($sale['customer_phone'] ?? 'N/A') ?>
            <?php endif; ?>
        </div>
        <div class="col-6 text-end meta">
            <strong>Invoice #:</strong> <?= htmlspecialchars($sale['invoice_number']) ?><br>
            <strong>Date:</strong> <?= date('d M Y  h:i A', strtotime($sale['created_at'])) ?><br>
            <strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier_name']) ?>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-end"><?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-end"><?= number_format($item['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"></td>
                <td class="text-end">Total Amount:</td>
                <td class="text-end"><?= number_format($sale['total_amount'], 2) ?></td>
            </tr>
            <?php if ($sale['discount_amount'] > 0): ?>
            <tr class="total-row">
                <td colspan="3"></td>
                <td class="text-end">Discount:</td>
                <td class="text-end text-danger">− <?= number_format($sale['discount_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="table-success total-row">
                <td colspan="3"></td>
                <td class="text-end">Amount Due:</td>
                <td class="text-end fw-bold"><?= number_format($sale['payable_amount'], 2) ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-center pt-3">
                    <strong>Payment Method:</strong> <?= htmlspecialchars($sale['payment_method']) ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="thankyou">
        Thank you for shopping with us!<br>
        Visit again • Have a great day!
    </div>

    <div class="text-center mt-5 no-print">
        <button onclick="window.print()" class="btn btn-primary px-5">Print Again</button>
        <a href="sales.php" class="btn btn-secondary px-5 ms-3">New Sale</a>
    </div>
</div>

</body>
</html>