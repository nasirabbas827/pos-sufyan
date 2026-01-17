<?php
session_start();
include('config.php');

// 1. Session Protection
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// ================================================
//          REPORT PERIOD SELECTION
// ================================================
$report_type = $_GET['report_type'] ?? 'monthly';
$start_date  = $_GET['start_date'] ?? date('Y-m-01');
$end_date    = $_GET['end_date']   ?? date('Y-m-d');

if ($report_type === 'daily') {
    $start_date = $end_date = date('Y-m-d');
} elseif ($report_type === 'weekly') {
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date   = date('Y-m-d', strtotime('sunday this week'));
} elseif ($report_type === 'monthly') {
    $start_date = date('Y-m-01');
    $end_date   = date('Y-m-t');
}

$start = $start_date . ' 00:00:00';
$end   = $end_date   . ' 23:59:59';

// ================================================
//          1. SALES SUMMARY
// ================================================
$sales_summary = $conn->prepare("
    SELECT 
        COUNT(id) AS total_transactions,
        COALESCE(SUM(payable_amount), 0) AS total_revenue,
        COALESCE(SUM(total_amount), 0) AS gross_revenue,
        COALESCE(SUM(discount_amount), 0) AS total_discount,
        AVG(payable_amount) AS avg_transaction_value,
        SUM(CASE WHEN payment_method = 'Cash' THEN 1 ELSE 0 END) AS cash_count,
        SUM(CASE WHEN payment_method = 'Card' THEN 1 ELSE 0 END) AS card_count,
        SUM(CASE WHEN payment_method = 'Online' THEN 1 ELSE 0 END) AS online_count
    FROM sales
    WHERE created_at BETWEEN ? AND ?
");
$sales_summary->bind_param("ss", $start, $end);
$sales_summary->execute();
$sales = $sales_summary->get_result()->fetch_assoc();
$sales_summary->close();

// ================================================
//          2. TOP SELLING PRODUCTS (by revenue)
// ================================================
$top_products = $conn->query("
    SELECT 
        p.product_name,
        SUM(si.quantity) AS units_sold,
        SUM(si.subtotal) AS revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.created_at BETWEEN '$start' AND '$end'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");

// ================================================
//          3. INVENTORY OVERVIEW (current status)
// ================================================
$inventory_summary = $conn->query("
    SELECT 
        COUNT(*) AS total_products,
        SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
        SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock,
        SUM(CASE WHEN stock_quantity > low_stock_threshold THEN 1 ELSE 0 END) AS good_stock
    FROM products
");

// ================================================
//          4. PRODUCTS WITH MOST SOLD UNITS (movement)
// ================================================
$most_sold = $conn->query("
    SELECT 
        p.product_name,
        p.stock_quantity AS current_stock,
        SUM(si.quantity) AS sold_qty
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.created_at BETWEEN '$start' AND '$end'
    GROUP BY p.id
    ORDER BY sold_qty DESC
    LIMIT 8
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reports </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .sidebar-offset { margin-left: 280px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .report-card { transition: transform 0.2s; }
        .report-card:hover { transform: translateY(-4px); }
        .table th { background: #f8f9fa; font-weight: 600; }
        .badge-lg { font-size: 0.95rem; padding: 0.55em 1.1em; }
        @media (max-width: 992px) { .sidebar-offset { margin-left: 0; } }
    </style>
</head>
<body>

<?php include('admin_sidebar.php');   ?>

<div class="sidebar-offset container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="mb-0">
            <i class="fas fa-chart-pie me-3 text-primary"></i>
            Sales & Inventory Reports
        </h2>
        <div>
            <a href="admin_home.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold">Report Period</label>
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="daily"   <?= $report_type === 'daily'   ? 'selected' : '' ?>>Today</option>
                        <option value="weekly"  <?= $report_type === 'weekly'  ? 'selected' : '' ?>>This Week</option>
                        <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>This Month</option>
                        <option value="custom"  <?= $report_type === 'custom'  ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold">From</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?= htmlspecialchars($start_date) ?>" <?= $report_type !== 'custom' ? 'disabled' : '' ?>>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold">To</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?= htmlspecialchars($end_date) ?>" <?= $report_type !== 'custom' ? 'disabled' : '' ?>>
                </div>

                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-chart-line me-2"></i> Generate
                    </button>
                    <a href="admin_reports.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Period Info -->
    <div class="alert alert-info mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <strong>Period:</strong> <?= date('d M Y', strtotime($start_date)) ?> 
            → <?= date('d M Y', strtotime($end_date)) ?>
        </div>
        <div>
            <strong>Generated on:</strong> <?= date('d M Y • h:i A') ?>
        </div>
    </div>

    <!-- Sales Cards -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card text-white bg-success report-card h-100">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h3 class="mb-1"><?= number_format($sales['total_revenue'], 2) ?></h3>
                    <small>After discount</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-white bg-primary report-card h-100">
                <div class="card-body">
                    <h6>Transactions</h6>
                    <h3 class="mb-1"><?= number_format($sales['total_transactions']) ?></h3>
                    <small>Avg: <?= number_format($sales['avg_transaction_value'], 2) ?></small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-white bg-warning report-card h-100">
                <div class="card-body">
                    <h6>Discount Given</h6>
                    <h3 class="mb-1">− <?= number_format($sales['total_discount'], 2) ?></h3>
                    <small>Total discount</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-white bg-info report-card h-100">
                <div class="card-body">
                    <h6>Gross Sales</h6>
                    <h3 class="mb-1"><?= number_format($sales['gross_revenue'], 2) ?></h3>
                    <small>Before discount</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Payment Methods -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-wallet me-2"></i>Payment Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span>Cash</span>
                            <span class="badge bg-success rounded-pill"><?= $sales['cash_count'] ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span>Card</span>
                            <span class="badge bg-info rounded-pill"><?= $sales['card_count'] ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span>Online</span>
                            <span class="badge bg-primary rounded-pill"><?= $sales['online_count'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-star me-2"></i>Top Selling Products (Period)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Units Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($top_products->num_rows > 0): ?>
                                <?php while ($row = $top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td class="text-center"><?= number_format($row['units_sold']) ?></td>
                                        <td class="text-end fw-bold"><?= number_format($row['revenue'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No sales recorded in this period</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Overview -->
    <?php $inv = $inventory_summary->fetch_assoc(); ?>
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card text-center report-card bg-light">
                <div class="card-body">
                    <h6>Total Products</h6>
                    <h3 class="text-primary"><?= number_format($inv['total_products']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-center report-card bg-success text-white">
                <div class="card-body">
                    <h6>In Good Stock</h6>
                    <h3><?= number_format($inv['good_stock']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-center report-card bg-warning text-dark">
                <div class="card-body">
                    <h6>Low Stock</h6>
                    <h3><?= number_format($inv['low_stock']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-center report-card bg-danger text-white">
                <div class="card-body">
                    <h6>Out of Stock</h6>
                    <h3><?= number_format($inv['out_of_stock']) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Products with Highest Movement -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Products with Highest Sales Movement</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Sold in Period</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($most_sold->num_rows > 0): ?>
                        <?php while ($row = $most_sold->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $row['current_stock'] <= 0 ? 'bg-danger' : ($row['current_stock'] <= 10 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                        <?= $row['current_stock'] ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($row['sold_qty']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted">No movement in selected period</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="text-end mt-4">
        <a href="export_sales_report.php?start=<?= urlencode($start_date) ?>&end=<?= urlencode($end_date) ?>" 
           class="btn btn-outline-primary btn-lg px-4 me-2">
            <i class="fas fa-file-csv me-2"></i> Export Sales CSV
        </a>
        <a href="export_inventory.php" class="btn btn-outline-secondary btn-lg px-4">
            <i class="fas fa-boxes me-2"></i> Export Inventory CSV
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>