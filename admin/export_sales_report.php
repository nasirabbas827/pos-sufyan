<?php
session_start();
include('config.php');

// 1. Session Protection
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}


$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date   = $_GET['end']   ?? date('Y-m-d');

// Sanitize & prepare full datetime range
$start = $start_date . ' 00:00:00';
$end   = $end_date   . ' 23:59:59';

// Set CSV headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM (helps Excel correctly display special characters)
echo "\xEF\xBB\xBF";

// CSV Header row
fputcsv($output, [
    'Invoice Number',
    'Date & Time',
    'Customer',
    'Cashier',
    'Payment Method',
    'Gross Total',
    'Discount',
    'Net Payable',
    'Items Count'
]);

// Main query - sales with basic item count
$sql = "
    SELECT 
        s.invoice_number,
        s.created_at,
        COALESCE(c.name, 'Walk-in / Cash Customer') AS customer_name,
        u.full_name AS cashier_name,
        s.payment_method,
        s.total_amount,
        s.discount_amount,
        s.payable_amount,
        (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.cashier_id = u.id
    WHERE s.created_at BETWEEN ? AND ?
    ORDER BY s.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['invoice_number'],
        date('d/m/Y H:i:s', strtotime($row['created_at'])),
        $row['customer_name'],
        $row['cashier_name'],
        $row['payment_method'],
        number_format($row['total_amount'], 2),
        number_format($row['discount_amount'], 2),
        number_format($row['payable_amount'], 2),
        $row['item_count']
    ]);
}

$stmt->close();
$conn->close();

fclose($output);
exit;