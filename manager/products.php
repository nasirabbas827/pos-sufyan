<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "manager") {
    header("Location: ../login.php");
    exit();
}

/* ──────────────── AJAX HANDLERS ──────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CREATE
    if ($_POST['action'] === 'create') {
        $product_name       = trim($_POST['product_name']);
        $category_id        = (int)$_POST['category_id'];
        $cost_price         = (float)$_POST['cost_price'];
        $selling_price      = (float)$_POST['selling_price'];
        $stock_quantity     = (int)$_POST['stock_quantity'];
        $low_stock_threshold = (int)$_POST['low_stock_threshold'];
        $image_name = '';

        if (!empty($_FILES['image']['name'])) {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
        }

        $stmt = $conn->prepare("INSERT INTO products 
            (image, category_id, product_name, cost_price, selling_price, stock_quantity, low_stock_threshold)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisddii", $image_name, $category_id, $product_name, $cost_price, $selling_price, $stock_quantity, $low_stock_threshold);

        $success = $stmt->execute();
        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Product added successfully' : 'Failed to add product'
        ]);
        exit;
    }

    // UPDATE
    if ($_POST['action'] === 'update') {
        $id                 = (int)$_POST['id'];
        $product_name       = trim($_POST['product_name']);
        $category_id        = (int)$_POST['category_id'];
        $cost_price         = (float)$_POST['cost_price'];
        $selling_price      = (float)$_POST['selling_price'];
        $stock_quantity     = (int)$_POST['stock_quantity'];
        $low_stock_threshold = (int)$_POST['low_stock_threshold'];
        $image_name         = $_POST['old_image'] ?? '';

        if (!empty($_FILES['image']['name'])) {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
        }

        $stmt = $conn->prepare("UPDATE products SET 
            image=?, category_id=?, product_name=?, cost_price=?, selling_price=?, stock_quantity=?, low_stock_threshold=?
            WHERE id=?");
        $stmt->bind_param("sisddiii", $image_name, $category_id, $product_name, $cost_price, $selling_price, $stock_quantity, $low_stock_threshold, $id);

        $success = $stmt->execute();
        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Product updated' : 'Update failed'
        ]);
        exit;
    }

    // DELETE
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Product deleted' : 'Delete failed'
        ]);
        exit;
    }
}

/* ──────────────── FILTER & SEARCH ──────────────── */
$search     = trim($_GET['search'] ?? '');
$filter     = $_GET['filter'] ?? 'all';

$where = "1=1";
if ($search !== '') {
    $where .= " AND p.product_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

if ($filter === 'low') {
    $where .= " AND p.stock_quantity <= p.low_stock_threshold AND p.stock_quantity > 0";
} elseif ($filter === 'out') {
    $where .= " AND p.stock_quantity <= 0";
} elseif ($filter === 'in') {
    $where .= " AND p.stock_quantity > p.low_stock_threshold";
}

$products = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $where
    ORDER BY 
        CASE 
            WHEN p.stock_quantity <= 0 THEN 1
            WHEN p.stock_quantity <= p.low_stock_threshold THEN 2
            ELSE 3
        END,
        p.created_at DESC
");

$categories = $conn->query("SELECT * FROM categories");

// Quick counts for alerts
$low_count  = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock_quantity > 0 AND stock_quantity <= low_stock_threshold")->fetch_assoc()['cnt'];
$out_count  = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock_quantity <= 0")->fetch_assoc()['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        img.product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
        .stock-low  { background-color: #fff3cd !important; }
        .stock-out  { background-color: #f8d7da !important; }
        .badge-lg   { font-size: 0.95rem; padding: 0.45em 0.9em; }
    </style>
</head>
<body class="bg-light">

<?php include('manager_sidebar.php'); ?>

<div class="container-fluid py-4" style="max-width: 1400px;">

    <!-- Alerts -->
    <?php if ($low_count > 0 || $out_count > 0): ?>
    <div class="alert alert-warning border-warning mb-4">
        <h5 class="alert-heading mb-2">
            <i class="fas fa-exclamation-triangle me-2"></i>Stock Alerts
        </h5>
        <?php if ($out_count > 0): ?>
        <div class="mb-1">
            <span class="badge bg-danger badge-lg me-2">Out of Stock: <?= $out_count ?></span>
            <small>Products with zero or negative stock — urgent restock needed.</small>
        </div>
        <?php endif; ?>
        <?php if ($low_count > 0): ?>
        <div>
            <span class="badge bg-warning text-dark badge-lg me-2">Low Stock: <?= $low_count ?></span>
            <small>Products below or at threshold — consider reordering soon.</small>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-success mb-4">
        <i class="fas fa-check-circle me-2"></i>All products are well-stocked. No alerts at this time.
    </div>
    <?php endif; ?>

    <!-- Header + Add button -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
        <h4 class="mb-0">Product Management</h4>
        <button class="btn btn-primary" onclick="openCreate()">
            <i class="fas fa-plus me-1"></i> Add New Product
        </button>
    </div>

    <!-- Search + Filter -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-5 col-lg-4">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search by product name..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4 col-lg-3">
            <select name="filter" class="form-select">
                <option value="all"  <?= $filter==='all'  ? 'selected' : '' ?>>All Products</option>
                <option value="low"  <?= $filter==='low'  ? 'selected' : '' ?>>Low Stock Only</option>
                <option value="out"  <?= $filter==='out'  ? 'selected' : '' ?>>Out of Stock</option>
                <option value="in"   <?= $filter==='in'   ? 'selected' : '' ?>>In Stock (Good)</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <!-- Products Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Cost</th>
                            <th>Selling</th>
                            <th>Stock</th>
                            <th>Threshold</th>
                            <th>Status</th>
                            <th width="130">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($products->num_rows === 0): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No products found matching your criteria.</td></tr>
                    <?php else: while($p = $products->fetch_assoc()): 
                        $status_class = $p['stock_quantity'] <= 0 ? 'stock-out' : 
                                       ($p['stock_quantity'] <= $p['low_stock_threshold'] ? 'stock-low' : '');
                        $status_text  = $p['stock_quantity'] <= 0 ? 'Out of Stock' : 
                                       ($p['stock_quantity'] <= $p['low_stock_threshold'] ? 'Low Stock' : 'OK');
                        $status_color = $p['stock_quantity'] <= 0 ? 'danger' : 
                                       ($p['stock_quantity'] <= $p['low_stock_threshold'] ? 'warning' : 'success');
                    ?>
                        <tr class="<?= $status_class ?>" data-id="<?= $p['id'] ?>">
                            <td>
                                <?php if($p['image']): ?>
                                    <img src="../admin/uploads/<?= htmlspecialchars($p['image']) ?>" class="product-thumb" alt="">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-medium prod-name"><?= htmlspecialchars($p['product_name']) ?></td>
                            <td class="prod-cat" data-cat="<?= $p['category_id'] ?>"><?= htmlspecialchars($p['category_name']) ?></td>
                            <td><?= number_format($p['cost_price'], 2) ?></td>
                            <td><?= number_format($p['selling_price'], 2) ?></td>
                            <td class="fw-bold"><?= $p['stock_quantity'] ?></td>
                            <td><?= $p['low_stock_threshold'] ?></td>
                            <td><span class="badge bg-<?= $status_color ?>"><?= $status_text ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="openEdit(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?= $p['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="productForm" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="prodId">
                <input type="hidden" name="old_image" id="oldImage">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_name" id="prodName" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" id="prodCat" required>
                            <option value="">— Select category —</option>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; $categories->data_seek(0); ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cost Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="cost_price" id="prodCost" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="selling_price" id="prodPrice" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control" name="stock_quantity" id="prodStock" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Low Stock Threshold <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control" name="low_stock_threshold" id="prodLow" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="image" id="prodImage" accept="image/*">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// FontAwesome (optional - for icons)
if (!document.querySelector('link[href*="font-awesome"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
    document.head.appendChild(link);
}

const modal = new bootstrap.Modal(document.getElementById('productModal'));
const form  = document.getElementById('productForm');

function openCreate() {
    form.reset();
    document.getElementById('prodId').value = '';
    document.getElementById('oldImage').value = '';
    document.getElementById('modalTitle').innerText = 'Add New Product';
    modal.show();
}

function openEdit(btn) {
    const row = btn.closest('tr');
    document.getElementById('prodId').value    = row.dataset.id;
    document.getElementById('prodName').value  = row.querySelector('.prod-name').innerText;
    document.getElementById('prodCat').value   = row.querySelector('.prod-cat').dataset.cat;
    document.getElementById('prodCost').value  = row.cells[3].innerText;
    document.getElementById('prodPrice').value = row.cells[4].innerText;
    document.getElementById('prodStock').value = row.cells[5].innerText;
    document.getElementById('prodLow').value   = row.cells[6].innerText;

    const img = row.querySelector('img');
    document.getElementById('oldImage').value = img ? img.src.split('/').pop() : '';

    document.getElementById('modalTitle').innerText = 'Edit Product';
    modal.show();
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

form.addEventListener('submit', e => {
    e.preventDefault();
    const data = new FormData(form);
    data.append('action', document.getElementById('prodId').value ? 'update' : 'create');

    fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(() => alert('Network error. Please try again.'));
});
</script>
</body>
</html>