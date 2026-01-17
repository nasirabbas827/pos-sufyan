<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "cashier") {
    header("Location: ../login.php");
    exit();
}

$cashier_id = $_SESSION["id"];
$full_name = $_SESSION["full_name"] ?? 'Cashier';

// 1. Fetch Today's Stats for this Cashier
$today = date('Y-m-d');
$stats_query = "SELECT 
                    COUNT(id) as total_orders, 
                    SUM(payable_amount) as total_revenue 
                FROM sales 
                WHERE cashier_id = ? AND DATE(created_at) = ?";
$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("is", $cashier_id, $today);
$stmt_stats->execute();
$stats_res = $stmt_stats->get_result()->fetch_assoc();
$today_sales_count = $stats_res['total_orders'] ?? 0;
$today_revenue = $stats_res['total_revenue'] ?? 0;

// 2. Fetch Recent Sales
$sql_recent = "SELECT s.*, c.name as customer_name 
               FROM sales s 
               JOIN customers c ON s.customer_id = c.id 
               WHERE s.cashier_id = ? 
               ORDER BY s.created_at DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $cashier_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();

// 3. Fetch Low Stock Alerts (Limited to 5 for sidebar)
$sql_low_stock = "SELECT product_name, stock_quantity FROM products WHERE stock_quantity <= low_stock_threshold LIMIT 5";
$result_low_stock = $conn->query($sql_low_stock);

// 4. Get total count for global notification
$count_res = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= low_stock_threshold");
$low_stock_total = $count_res->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Cashier Dashboard • Karobar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; margin-left: 280px; transition: 0.3s; }
        .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; }
        .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); color: white; }
        
        /* Low Stock Blinking Effect */
        .blink-alert {
            animation: blink-animation 2s steps(5, start) infinite;
        }
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        @media (max-width: 992px) { body { margin-left: 0; } }
    </style>
</head>
<body>

    <?php include('cashier_sidebar.php'); ?>

    <div class="container py-4">

        <?php if ($low_stock_total > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-4 me-3 text-danger blink-alert"></i>
            <div>
                <strong>Inventory Alert:</strong> There are <?php echo $low_stock_total; ?> items running low on stock. Please inform the manager.
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
                <p class="text-muted">Here is what's happening today, <?php echo date('D, d M Y'); ?></p>
            </div>
            <a href="sales.php" class="btn btn-primary btn-lg shadow-sm px-4">
                <i class="fas fa-plus me-2"></i>New Sale
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-primary shadow p-3 text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small text-uppercase opacity-75">Today's Sales</div>
                            <div class="h3 fw-bold mb-0"><?php echo $today_sales_count; ?></div>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-gradient-success shadow p-3 text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small text-uppercase opacity-75">Today's Revenue</div>
                            <div class="h3 fw-bold mb-0">RS <?php echo number_format($today_revenue, 2); ?></div>
                        </div>
                        <i class="fas fa-coins fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card <?php echo ($low_stock_total > 0) ? 'bg-danger' : 'bg-gradient-warning'; ?> shadow p-3 text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small text-uppercase opacity-75">Critical Stock</div>
                            <div class="h3 fw-bold mb-0"><?php echo $low_stock_total; ?></div>
                        </div>
                        <i class="fas fa-box-open fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-primary"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($sale = $result_recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $sale['invoice_number']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                        <td class="fw-bold">RS <?php echo number_format($sale['payable_amount'], 2); ?></td>
                                        <td><span class="badge bg-info-subtle text-info border border-info px-3"><?php echo $sale['payment_method']; ?></span></td>
                                        <td>
                                            <a href="print_invoice.php?sale_id=<?php echo $sale['id']; ?>" target="_blank" class="btn btn-sm btn-light border text-primary">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if($result_recent->num_rows == 0): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No sales today yet. Click "New Sale" to begin.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-bell me-2"></i>Stock Alerts</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php 
                        $result_low_stock->data_seek(0); // Reset result pointer
                        while($low = $result_low_stock->fetch_assoc()): 
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-dark fw-medium"><?php echo htmlspecialchars($low['product_name']); ?></span>
                            <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3">
                                <?php echo $low['stock_quantity']; ?> left
                            </span>
                        </li>
                        <?php endwhile; ?>
                        
                        <?php if($low_stock_total == 0): ?>
                            <li class="list-group-item text-center py-4">
                                <i class="fas fa-check-circle text-success fs-3 mb-2"></i>
                                <p class="text-muted mb-0">All stock levels are healthy</p>
                            </li>
                        <?php else: ?>
                            <li class="list-group-item bg-light text-center small">
                                <a href="products.php" class="text-decoration-none">View full inventory</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>