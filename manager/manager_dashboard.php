<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "manager") {
    header("Location: ../login.php");
    exit();
}

include('config.php');

$manager_id = $_SESSION["id"];
$full_name = $_SESSION["full_name"] ?? 'Manager';

// 1. Fetch High-Level Metrics
$metrics_query = "SELECT 
    (SELECT SUM(payable_amount) FROM sales) as total_revenue,
    (SELECT COUNT(id) FROM sales) as total_orders,
    (SELECT COUNT(id) FROM customers) as total_customers,
    (SELECT SUM(stock_quantity * cost_price) FROM products) as inventory_value";
$metrics = $conn->query($metrics_query)->fetch_assoc();

// NEW: Fetch Low Stock Count for Notifications
$low_stock_count_query = "SELECT COUNT(*) as low_count FROM products WHERE stock_quantity <= low_stock_threshold";
$low_stock_count = $conn->query($low_stock_count_query)->fetch_assoc()['low_count'];

// 2. Fetch Sales by Cashier (Staff Performance)
$staff_query = "SELECT u.full_name, COUNT(s.id) as sales_count, SUM(s.payable_amount) as total_generated
                FROM users u
                LEFT JOIN sales s ON u.id = s.cashier_id
                WHERE u.usertype = 'cashier'
                GROUP BY u.id";
$staff_performance = $conn->query($staff_query);

// 3. Fetch Products with Profit Analysis
$sql_products = "
    SELECT p.*, c.name AS category_name, 
           (p.selling_price - p.cost_price) as profit_margin
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.stock_quantity ASC LIMIT 10
";
$result_products = $conn->query($sql_products);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manager Dashboard • Karobar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', system-ui, sans-serif; margin-left: 280px; }
        .metric-card { border: none; border-left: 5px solid #0d6efd; border-radius: 12px; transition: 0.3s; }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
        .table-container { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .profit-text { color: #198754; font-weight: bold; }
        /* Animation for notification */
        .pulse-alert { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        @media (max-width: 992px) { body { margin-left: 0; } }
    </style>
</head>
<body>

    <?php include('manager_sidebar.php'); ?>

    <div class="container py-4">
        
        <?php if ($low_stock_count > 0): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center pulse-alert mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
            <div class="flex-grow-1">
                <h6 class="mb-0 fw-bold">Stock Warning</h6>
                <span>There are <strong><?php echo $low_stock_count; ?></strong> products running below the minimum threshold.</span>
            </div>
            <a href="#inventory-table" class="btn btn-danger btn-sm rounded-pill px-3">View Details</a>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0 text-dark">Managerial Overview</h2>
                <p class="text-muted">Business Analytics & Inventory Control</p>
            </div>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-file-export me-2"></i>Export Report
            </button>
        </div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="manager_reports.php" class="text-decoration-none">
            <div class="card metric-card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle text-primary me-3"><i class="fas fa-dollar-sign"></i></div>
                    <div>
                        <div class="small text-muted">Total Revenue</div>
                        <div class="h5 fw-bold mb-0 text-dark">RS <?php echo number_format($metrics['total_revenue'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="products.php" class="text-decoration-none">
            <div class="card metric-card shadow-sm p-3" style="border-left-color: #198754;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success-subtle text-success me-3"><i class="fas fa-warehouse"></i></div>
                    <div>
                        <div class="small text-muted">Inventory Value</div>
                        <div class="h5 fw-bold mb-0 text-dark">RS <?php echo number_format($metrics['inventory_value'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="manager_reports.php" class="text-decoration-none">
            <div class="card metric-card shadow-sm p-3" style="border-left-color: #fd7e14;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning-subtle text-warning me-3"><i class="fas fa-shopping-bag"></i></div>
                    <div>
                        <div class="small text-muted">Total Orders</div>
                        <div class="h5 fw-bold mb-0 text-dark"><?php echo $metrics['total_orders'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="customers.php" class="text-decoration-none">
            <div class="card metric-card shadow-sm p-3" style="border-left-color: #6f42c1;">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-purple-subtle text-purple me-3"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="small text-muted">Customers</div>
                        <div class="h5 fw-bold mb-0 text-dark"><?php echo $metrics['total_customers'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

        <div class="row">
            <div class="col-lg-8" id="inventory-table">
                <div class="table-container mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-exclamation-circle text-danger me-2"></i>Low Stock & Profit Analysis</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Cost</th>
                                    <th>Price</th>
                                    <th>Profit/Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset pointer if needed, though $result_products is fresh here
                                while ($row = $result_products->fetch_assoc()): 
                                    $is_low = ($row['stock_quantity'] <= $row['low_stock_threshold']);
                                ?>
                                    <tr class="<?php echo $is_low ? 'table-danger' : ''; ?>">
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['category_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $is_low ? 'bg-danger' : 'bg-secondary'; ?>">
                                                <?php echo $row['stock_quantity']; ?>
                                            </span>
                                            <?php if($is_low): ?>
                                                <i class="fas fa-arrow-down text-danger ms-1 small"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>RS <?php echo number_format($row['cost_price'], 2); ?></td>
                                        <td>RS <?php echo number_format($row['selling_price'], 2); ?></td>
                                        <td class="profit-text">+RS <?php echo number_format($row['profit_margin'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-container">
                    <h5 class="fw-bold mb-3"><i class="fas fa-user-tie text-primary me-2"></i>Staff Performance</h5>
                    <?php while($staff = $staff_performance->fetch_assoc()): ?>
                        <div class="d-flex align-items-center mb-3 p-2 border-bottom">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <?php echo strtoupper(substr($staff['full_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($staff['full_name']); ?></div>
                                <small class="text-muted"><?php echo $staff['sales_count']; ?> Sales Processed</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">RS <?php echo number_format($staff['total_generated'], 2); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>