<?php
session_start();
include('config.php');

// 1. Session Protection
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// 2. Fetch Summary Stats
$userCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$productCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$categoryCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
$totalSales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(payable_amount) as total FROM sales"))['total'] ?? 0;

// 3. Fetch Low Stock Items
$lowStockQuery = mysqli_query($conn, "SELECT product_name, stock_quantity, low_stock_threshold FROM products WHERE stock_quantity <= low_stock_threshold");
$lowStockItems = [];
while($row = mysqli_fetch_assoc($lowStockQuery)) {
    $lowStockItems[] = $row;
}

// 4. Data for Charts
// Category Distribution
$catData = mysqli_query($conn, "SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id");
$catLabels = []; $catCounts = [];
while($r = mysqli_fetch_assoc($catData)) { $catLabels[] = $r['name']; $catCounts[] = $r['count']; }

// User Types
$userTypeData = mysqli_query($conn, "SELECT usertype, COUNT(*) as count FROM users GROUP BY usertype");
$utLabels = []; $utCounts = [];
while($r = mysqli_fetch_assoc($userTypeData)) { $utLabels[] = $r['usertype']; $utCounts[] = $r['count']; }

// New Chart: Sales Trend (Last 7 Days)
$salesTrend = mysqli_query($conn, "SELECT DATE(created_at) as date, SUM(payable_amount) as daily_total FROM sales GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7");
$trendLabels = []; $trendData = [];
while($r = mysqli_fetch_assoc($salesTrend)) { 
    $trendLabels[] = date('M d', strtotime($r['date'])); 
    $trendData[] = $r['daily_total']; 
}
// Reverse to show oldest to newest in chart
$trendLabels = array_reverse($trendLabels);
$trendData = array_reverse($trendData);

// Recent Sales (Last 5)
$recentSales = mysqli_query($conn, "SELECT s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id ORDER BY s.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard | POS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-icon { font-size: 3rem; opacity: 0.2; position: absolute; right: 10px; bottom: 5px; }
        .stats-card { border: none; border-radius: 12px; transition: transform 0.3s; }
        .stats-card:hover { transform: scale(1.03); }
        .chart-container { position: relative; height: 300px; }
    </style>
</head>
<body>
<?php include('admin_sidebar.php');   ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-chart-line text-primary"></i> Admin Panel</h2>
        <div class="text-end">
            <p class="mb-0 text-muted small">Current Date</p>
            <h6 class="mb-0 fw-bold"><?php echo date('D, d M Y'); ?></h6>
        </div>
    </div>

    <?php if (count($lowStockItems) > 0): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
        <div>
            <strong>Attention Required!</strong> There are <?php echo count($lowStockItems); ?> products with critically low stock.
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white shadow">
                <div class="card-body">
                    <h6 class="text-uppercase small">Total Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo $userCount; ?></h2>
                    <i class="fa-solid fa-users card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white shadow">
                <div class="card-body">
                    <h6 class="text-uppercase small">Total Sales</h6>
                    <h2 class="fw-bold mb-0">Rs. <?php echo number_format($totalSales); ?></h2>
                    <i class="fa-solid fa-indian-rupee-sign card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white shadow">
                <div class="card-body">
                    <h6 class="text-uppercase small">Total Products</h6>
                    <h2 class="fw-bold mb-0"><?php echo $productCount; ?></h2>
                    <i class="fa-solid fa-boxes-stacked card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-dark shadow">
                <div class="card-body">
                    <h6 class="text-uppercase small">Categories</h6>
                    <h2 class="fw-bold mb-0"><?php echo $categoryCount; ?></h2>
                    <i class="fa-solid fa-tags card-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Recent Sales Trend (Last 7 Days)</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Staff Distribution</div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="userTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
         <div class="col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">Stock Inventory by Category</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white fw-bold">Low Stock Alerts</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Stock</th>
                                    <th>Threshold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lowStockItems as $item): ?>
                                <tr>
                                    <td><?php echo $item['product_name']; ?></td>
                                    <td><span class="badge bg-danger">Only <?php echo $item['stock_quantity']; ?></span></td>
                                    <td><?php echo $item['low_stock_threshold']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Recent Sales Transactions</span>
                    <a href="admin_reports.php" class="btn btn-sm btn-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark text-white">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($sale = mysqli_fetch_assoc($recentSales)): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $sale['invoice_number']; ?></td>
                                    <td><?php echo $sale['customer_name']; ?></td>
                                    <td><span class="badge rounded-pill bg-light text-dark border"><?php echo $sale['payment_method']; ?></span></td>
                                    <td class="fw-bold text-success">Rs. <?php echo number_format($sale['payable_amount'], 2); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($sale['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart 1: Sales Trend (Line Chart)
new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trendLabels); ?>,
        datasets: [{
            label: 'Daily Sales (Rs.)',
            data: <?php echo json_encode($trendData); ?>,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Chart 2: Category Bar Chart
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($catLabels); ?>,
        datasets: [{
            label: 'Total Products',
            data: <?php echo json_encode($catCounts); ?>,
            backgroundColor: '#0dcaf0'
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Chart 3: User Type Pie Chart
new Chart(document.getElementById('userTypeChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($utLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($utCounts); ?>,
            backgroundColor: ['#0d6efd', '#20c997']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>