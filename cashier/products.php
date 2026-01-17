<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "cashier") {
    header("Location: ../login.php");
    exit();
}

$cashier_id = $_SESSION["id"];
$full_name  = $_SESSION["full_name"] ?? 'Cashier';

// ================================================
//          FILTER & SEARCH PARAMETERS
// ================================================
$search     = trim($_GET['search']     ?? '');
$category   = trim($_GET['category']   ?? '');
$stock      = trim($_GET['stock']      ?? '');   // all, in_stock, low_stock, out_of_stock

$where  = [];
$params = [];
$types  = '';

// Name / keyword search
if ($search !== '') {
    $where[] = "(p.product_name LIKE ? OR p.id = ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $search;   // allow searching by exact ID
    $types   .= 'ss';
}

// Category filter
if ($category !== '' && is_numeric($category)) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$category;
    $types   .= 'i';
}

// Stock status filter
switch ($stock) {
    case 'in_stock':
        $where[] = "p.stock_quantity > 0";
        break;
    case 'low_stock':
        $where[] = "p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold";
        break;
    case 'out_of_stock':
        $where[] = "p.stock_quantity = 0";
        break;
    // 'all' → no condition
}

// Build WHERE clause
$where_sql = '';
if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

// Get categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

// Main products query
$sql = "
    SELECT 
        p.id,
        p.image,
        p.product_name,
        p.selling_price,
        p.stock_quantity,
        p.low_stock_threshold,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where_sql
    ORDER BY 
        CASE 
            WHEN p.stock_quantity = 0 THEN 1
            WHEN p.stock_quantity <= p.low_stock_threshold THEN 2
            ELSE 3
        END,
        p.product_name
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Products - <?= htmlspecialchars($full_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            min-height: 100vh;
        }
        .sidebar-offset { margin-left: 280px; }
        .card { border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); border: none; }
        .table th { background: #f1f5f9; font-weight: 600; }
        .product-image { max-width: 70px; height: auto; border-radius: 6px; object-fit: cover; }
        .status-badge { font-size: 0.85rem; padding: 0.4em 0.8em; }
        @media (max-width: 992px) { .sidebar-offset { margin-left: 0; } }
    </style>
</head>
<body>

<?php include('cashier_sidebar.php'); ?>

<div class="sidebar-offset container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="mb-0">
            <i class="fas fa-boxes-stacked me-3 text-primary"></i>
            Product Catalog
        </h2>
        <a href="sales.php" class="btn btn-success">
            <i class="fas fa-cash-register me-2"></i>New Sale
        </a>
    </div>

    <!-- Filter & Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search product</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Name or ID..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" 
                                    <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                        <?php $categories->data_seek(0); // reset for reuse if needed ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Stock Status</label>
                    <select name="stock" class="form-select">
                        <option value="">All stock levels</option>
                        <option value="in_stock"   <?= $stock === 'in_stock'   ? 'selected' : '' ?>>In Stock</option>
                        <option value="low_stock"  <?= $stock === 'low_stock'  ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out_of_stock" <?= $stock === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <i class="fas fa-list-ul me-2"></i>
                Products (<?= $products->num_rows ?> found)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($row = $products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($row['image']): ?>
                                        <img src="../admin/uploads/<?= htmlspecialchars($row['image']) ?>" 
                                             class="product-image" alt="">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ?: '—') ?></td>
                                <td class="text-end"><?= number_format($row['selling_price'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= 
                                        $row['stock_quantity'] == 0 ? 'bg-danger' : 
                                        ($row['stock_quantity'] <= $row['low_stock_threshold'] ? 'bg-warning text-dark' : 'bg-success')
                                    ?>">
                                        <?= $row['stock_quantity'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['stock_quantity'] == 0): ?>
                                        <span class="status-badge bg-danger text-white">Out of Stock</span>
                                    <?php elseif ($row['stock_quantity'] <= $row['low_stock_threshold']): ?>
                                        <span class="status-badge bg-warning text-dark text-white">Low Stock</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-success text-white">Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 d-block opacity-50"></i>
                                No products match your filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>