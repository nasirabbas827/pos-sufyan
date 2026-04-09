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
        $name = trim($_POST['name']);

        if ($name === '') {
            echo json_encode(['status'=>'error','message'=>'Category name required']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Category added'])
            : json_encode(['status'=>'error','message'=>'Category already exists']);
        exit;
    }

    /* UPDATE */
    if ($_POST['action'] === 'update') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name']);

        if ($id <= 0 || $name === '') {
            echo json_encode(['status'=>'error','message'=>'Invalid data']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Category updated'])
            : json_encode(['status'=>'error','message'=>'Update failed']);
        exit;
    }

    /* DELETE */
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param("i", $id);

        echo $stmt->execute()
            ? json_encode(['status'=>'success','message'=>'Category deleted'])
            : json_encode(['status'=>'error','message'=>'Delete failed']);
        exit;
    }
}

/* FETCH CATEGORIES */
$res = $conn->query("SELECT id, name, created_at FROM categories ORDER BY created_at DESC");
$categories = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('manager_sidebar.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
        <h4>Categories</h4>
        <button class="btn btn-primary" onclick="openCreate()">Add Category</button>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Created</th>
                <th width="120">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$categories): ?>
            <tr><td colspan="3" class="text-center">No categories found</td></tr>
        <?php endif; foreach ($categories as $c): ?>
            <tr data-id="<?= $c['id'] ?>">
                <td class="cat-name"><?= htmlspecialchars($c['id']) ?></td>
                <td class="cat-name"><?= htmlspecialchars($c['name']) ?></td>
                <td><?= $c['created_at'] ?></td>
                <td>
                    <button class="btn btn-sm btn-info m-2" onclick="openEdit(this)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $c['id'] ?>)">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- MODAL -->
<div class="modal fade" id="categoryModal">
<div class="modal-dialog">
<form id="categoryForm" class="modal-content">
<div class="modal-header">
    <h5 id="modalTitle">Add Category</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="id" id="catId">
    <div class="row">
        <div class="col-md-12">
            <label class="form-label">Category Name</label>
            <input class="form-control" name="name" id="catName" required>
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
const modal = new bootstrap.Modal(document.getElementById('categoryModal'));

function openCreate(){
    categoryForm.reset();
    catId.value='';
    modalTitle.innerText='Add Category';
    modal.show();
}

function openEdit(btn){
    const row = btn.closest('tr');
    catId.value = row.dataset.id;
    catName.value = row.querySelector('.cat-name').innerText;
    modalTitle.innerText='Edit Category';
    modal.show();
}

function deleteCategory(id){
    if(!confirm('Delete this category?')) return;
    fetch('',{
        method:'POST',
        body:new URLSearchParams({action:'delete',id})
    }).then(()=>location.reload());
}

categoryForm.addEventListener('submit',e=>{
    e.preventDefault();
    const data = new FormData(categoryForm);
    data.append('action', catId.value ? 'update':'create');
    fetch('',{method:'POST',body:data}).then(()=>location.reload());
});
</script>

</body>
</html>
