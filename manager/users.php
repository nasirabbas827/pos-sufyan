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

    /* CREATE */
    if ($_POST['action'] === 'create') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $usertype  = $_POST['usertype'] ?? '';
        $password  = $_POST['password'] ?? '';

        if ($full_name === '' || $email === '' || $password === '' || !in_array($usertype, ['manager','cashier'])) {
            echo json_encode(['status'=>'error','message'=>'Invalid or missing input']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, usertype) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $full_name, $email, $phone, $hash, $usertype);

        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'User created']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Creation failed - possibly duplicate email']);
        }
        exit;
    }

    /* UPDATE */
    if ($_POST['action'] === 'update') {
        $id        = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $usertype  = $_POST['usertype'] ?? '';
        $password  = trim($_POST['password'] ?? '');

        if ($id <= 0 || $full_name === '' || $email === '' || !in_array($usertype, ['manager','cashier'])) {
            echo json_encode(['status'=>'error','message'=>'Invalid or missing data']);
            exit;
        }

        // Prepare base query and params
        $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, usertype = ?";
        $types = "sssi";
        $params = [$full_name, $email, $phone, $usertype];

        // Add password only if provided
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $query .= ", password = ?";
            $types .= "s";
            $params[] = $hash;
        }

        $query .= " WHERE id = ? AND usertype != 'admin'";
        $types .= "i";
        $params[] = $id;

        $stmt = $conn->prepare($query);

        // Dynamic bind_param
        $bind_names = [$types];
        foreach ($params as $key => $value) {
            $bind_names[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'User updated']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Update failed - possibly duplicate email']);
        }
        exit;
    }

    /* DELETE */
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND usertype != 'admin'");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'User deleted']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Delete failed']);
        }
        exit;
    }
}

/* FETCH USERS */
$res = $conn->query("SELECT id, full_name, email, phone, usertype, created_at 
                     FROM users 
                     WHERE usertype IN ('cashier') 
                     ORDER BY created_at DESC");
$users = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('manager_sidebar.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Manage Users</h4>
        <button class="btn btn-primary" onclick="openCreate()">Add New User</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Created</th>
                    <th width="140">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center py-4">No users found</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr data-id="<?= $u['id'] ?>">
                        <td class="name"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td class="email"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="phone"><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                        <td class="type"><?= htmlspecialchars($u['usertype']) ?></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEdit(this)">Edit</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="userForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="userId">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="fullName" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" id="phone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">User Type</label>
                        <select class="form-select" name="usertype" id="usertype" required>
                            <option value="">Select type</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                    <div class="col-12" id="passwordField">
                        <label class="form-label">
                            Password 
                            <small class="text-muted">(leave blank to keep current password when editing)</small>
                        </label>
                        <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Enter new password (optional on edit)">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('userModal'));
const userForm = document.getElementById('userForm');
const modalTitle = document.getElementById('modalTitle');
const passwordField = document.getElementById('passwordField');

function openCreate() {
    userForm.reset();
    document.getElementById('userId').value = '';
    passwordField.style.display = 'block';
    document.getElementById('passwordInput').required = true;
    modalTitle.textContent = 'Add New User';
    modal.show();
}

function openEdit(btn) {
    const row = btn.closest('tr');
    document.getElementById('userId').value = row.dataset.id;
    document.getElementById('fullName').value = row.querySelector('.name').textContent;
    document.getElementById('email').value = row.querySelector('.email').textContent;
    document.getElementById('phone').value = row.querySelector('.phone').textContent;
    document.getElementById('usertype').value = row.querySelector('.type').textContent;

    passwordField.style.display = 'block';
    document.getElementById('passwordInput').required = false;
    document.getElementById('passwordInput').value = ''; // clear previous value
    modalTitle.textContent = 'Edit User';
    modal.show();
}

function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    fetch('', {
        method: 'POST',
        body: new URLSearchParams({ action: 'delete', id })
    })
    .then(() => location.reload())
    .catch(err => console.error('Delete failed:', err));
}

userForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(userForm);
    const isEdit = !!document.getElementById('userId').value;
    formData.append('action', isEdit ? 'update' : 'create');

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            alert(data.message || 'Operation failed');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Something went wrong');
    });
});
</script>

</body>
</html>