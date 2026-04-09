<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "manager") {
    header("Location: ../login.php");
    exit();
}

/* AJAX HANDLER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CREATE
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);

        $stmt = $conn->prepare("INSERT INTO customers (name, address, email, phone_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $address, $email, $phone_number);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Customer added'])
            : json_encode(['status'=>'error','message'=>'Failed to add customer']);
        exit;
    }

    // UPDATE
    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);

        $stmt = $conn->prepare("UPDATE customers SET name=?, address=?, email=?, phone_number=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $address, $email, $phone_number, $id);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Customer updated'])
            : json_encode(['status'=>'error','message'=>'Update failed']);
        exit;
    }

    // DELETE
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
        $stmt->bind_param("i", $id);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Customer deleted'])
            : json_encode(['status'=>'error','message'=>'Delete failed']);
        exit;
    }
}

/* FETCH DATA */
$search = $_GET['search'] ?? '';
$search_sql = $search ? " AND name LIKE '%". $conn->real_escape_string($search) ."%' " : '';

$customers = $conn->query("
    SELECT * FROM customers
    WHERE 1 $search_sql
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include('manager_sidebar.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
        <h4>Customers</h4>
        <button class="btn btn-primary" onclick="openCreate()">Add Customer</button>
    </div>

    <div class="mb-3">
        <form method="get" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-secondary">Search</button>
        </form>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th width="140">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$customers->num_rows): ?>
            <tr><td colspan="5" class="text-center">No customers found</td></tr>
        <?php else: while($c = $customers->fetch_assoc()): ?>
            <tr data-id="<?= $c['id'] ?>">
                <td class="cust-name"><?= htmlspecialchars($c['id']) ?></td>
                <td class="cust-name"><?= htmlspecialchars($c['name']) ?></td>
                <td class="cust-phone"><?= htmlspecialchars($c['phone_number']) ?></td>
                <td>
                    <button class="btn btn-sm btn-info m-1" onclick="openEdit(this)">Edit</button>
                    <button class="btn btn-sm btn-danger m-1" onclick="deleteCustomer(<?= $c['id'] ?>)">Delete</button>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL -->
<div class="modal fade" id="customerModal">
<div class="modal-dialog">
<form id="customerForm" class="modal-content">
<div class="modal-header">
    <h5 id="modalTitle">Add Customer</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="id" id="custId">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" id="custName" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input class="form-control" name="phone_number" id="custPhone">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="custEmail">
        </div>
        <div class="col-md-6">
            <label class="form-label">Address</label>
            <input class="form-control" name="address" id="custAddress">
        </div>
    </div>
</div>

<div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-primary">Save</button>
</div>
</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('customerModal'));
const form = document.getElementById('customerForm');

function openCreate(){
    form.reset();
    custId.value='';
    modalTitle.innerText='Add Customer';
    modal.show();
}

function openEdit(btn){
    const row = btn.closest('tr');
    custId.value = row.dataset.id;
    custName.value = row.querySelector('.cust-name').innerText;
    custAddress.value = row.querySelector('.cust-address').innerText;
    custEmail.value = row.querySelector('.cust-email').innerText;
    custPhone.value = row.querySelector('.cust-phone').innerText;
    modalTitle.innerText='Edit Customer';
    modal.show();
}

function deleteCustomer(id){
    if(!confirm('Delete this customer?')) return;
    fetch('', {method:'POST', body:new URLSearchParams({action:'delete', id})})
    .then(()=>location.reload());
}

form.addEventListener('submit', e=>{
    e.preventDefault();
    const data = new FormData(form);
    data.append('action', custId.value ? 'update':'create');
    fetch('', {method:'POST', body:data})
    .then(()=>location.reload());
});
</script>
</body>
</html>
