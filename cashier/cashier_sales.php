<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "cashier") {
    header("Location: ../login.php");
    exit();
}

$cashier_id = $_SESSION["id"];
$full_name  = $_SESSION["full_name"] ?? 'Cashier';

// ======================================================================
// Filters & Pagination
// ======================================================================
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // records per page
$offset = ($page - 1) * $limit;

$where = "WHERE s.cashier_id = ?";
$params = [$cashier_id];
$types  = "i";

// Optional date filter
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

if ($start_date) {
    $where .= " AND s.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
    $types   .= "s";
}
if ($end_date) {
    $where .= " AND s.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
    $types   .= "s";
}

// Count total records for pagination
$count_sql = "SELECT COUNT(s.id) as total 
              FROM sales s $where";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages   = ceil($total_records / $limit);
$stmt_count->close();

// Fetch sales
$sql = "
    SELECT 
        s.id,
        s.invoice_number,
        s.created_at,
        s.payment_method,
        s.total_amount,
        s.discount_amount,
        s.payable_amount,
        c.name AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    $where
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Sales - <?= htmlspecialchars($full_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .sidebar-offset { margin-left: 280px; }
        .card { border: none; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .table th { background: #f1f5f9; font-weight: 600; }
        .badge-payment { font-size: 0.85rem; padding: 0.45em 0.9em; }
        .table-hover tbody tr:hover { background-color: #f8f9ff; }
        @media (max-width: 992px) { .sidebar-offset { margin-left: 0; } }
    </style>
</head>
<body>

<?php include('cashier_sidebar.php'); ?>

<div class="sidebar-offset container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="mb-0">
            <i class="fas fa-receipt me-3 text-primary"></i>
            My Sales History
        </h2>
        <div class="d-flex gap-2">
            <a href="sales.php" class="btn btn-success">
                <i class="fas fa-cash-register me-2"></i>New Sale
            </a>
            <a href="cashier_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4 col-sm-6">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                    <a href="cashier_sales.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <i class="fas fa-list-ul me-2"></i>
                Sales Records (<?= $total_records ?> total)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Payment</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $counter = $offset + 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($row['invoice_number']) ?></td>
                                <td><?= date('d M Y • h:i A', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?: 'Walk-in') ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['payment_method'] === 'Cash' ? 'success' : 
                                        ($row['payment_method'] === 'Card' ? 'info' : 'primary')
                                    ?> badge-payment">
                                        <?= htmlspecialchars($row['payment_method']) ?>
                                    </span>
                                </td>
                                <td class="text-end"><?= number_format($row['total_amount'], 2) ?></td>
                                <td class="text-end text-danger">
                                    <?= $row['discount_amount'] > 0 ? '− ' . number_format($row['discount_amount'], 2) : '—' ?>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    <?= number_format($row['payable_amount'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <a href="print_invoice.php?sale_id=<?= $row['id'] ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-3 d-block text-secondary"></i>
                                No sales found<?= $start_date || $end_date ? ' in selected date range' : '' ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-light">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php 
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end   = min($total_pages, $page + $range);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>