<?php
session_start();
include('config.php');

// 1. Session Protection
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Product Name', 'Category', 'Current Stock', 'Low Threshold', 'Status']);

$result = $conn->query("
    SELECT 
        p.id,
        p.product_name,
        c.name AS category,
        p.stock_quantity,
        p.low_stock_threshold,
        CASE 
            WHEN p.stock_quantity <= 0 THEN 'Out of Stock'
            WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
            ELSE 'OK'
        END AS status
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.stock_quantity ASC
");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

$conn->close();
exit;