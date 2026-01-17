<?php
session_start();
include('config.php');

$email = $password = $role = "";
$email_err = $password_err = $role_err = $login_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate role
    $valid_roles = ['manager', 'cashier'];
    if (empty($_POST["role"]) || !in_array($_POST["role"], $valid_roles)) {
        $role_err = "Please select a valid role.";
    } else {
        $role = $_POST["role"];
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no errors, check credentials
    if (empty($email_err) && empty($password_err) && empty($role_err)) {
        $sql = "SELECT id, full_name, email, password, status FROM users WHERE email=? AND usertype=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_role);
        $param_email = $email;
        $param_role = $role;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $full_name, $email_db, $hashed_password, $status);
            if (mysqli_stmt_fetch($stmt)) {
                if ($status !== 'active') {
                    $login_msg = "Your account is not active. Current status: $status.";
                } elseif (password_verify($password, $hashed_password)) {
                    // Success! Store session and redirect
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email_db;
                    $_SESSION["role"] = $role;
                    $_SESSION["full_name"] = $full_name;

                    // Redirect based on role
                    $redirect_url = ($role === 'manager') ? "manager/manager_dashboard.php" : "cashier/cashier_dashboard.php";
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    $password_err = "The password you entered is incorrect.";
                }
            }
        } else {
            $email_err = "No account found with that email for the selected role.";
        }
        mysqli_stmt_close($stmt);
    }

    if (isset($conn)) mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Login • Karobar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #e6effc; font-family: Arial, sans-serif; }
        .container { padding: 3rem 1rem; }
        .card { border-radius: 10px; max-width: 450px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .card-body { padding: 2.5rem; }
        .card h3 { font-size: 1.6rem; font-weight: 700; color:#333; margin-bottom:1.5rem; }
        .form-label { font-size: 0.9rem; font-weight: 600; color:#444; }
        .input-group-text { background-color:#f8f9fa; border-right: none; }
        .form-control, .form-select { border-left: none; }
        .form-control:focus, .form-select:focus { box-shadow: none; border-color: #dee2e6; }
        .btn-primary { background-color:#007bff; border:none; padding:0.75rem; font-weight: 600; }
        .admin-link-box { border-top: 1px solid #eee; margin-top: 1.5rem; padding-top: 1rem; }
        .admin-link { color: #6c757d !important; font-size: 0.85rem; }
        .admin-link:hover { color: #dc3545 !important; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="text-center mb-4">User Login</h3>
                    
                    <?php if (!empty($login_msg)) echo '<div class="alert alert-danger">'.$login_msg.'</div>'; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                        <div class="mb-3">
                            <label for="role" class="form-label">Login as</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select id="role" name="role" class="form-select <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Role</option>
                                    <option value="manager" <?php echo ($role === 'manager') ? 'selected' : ''; ?>>Manager</option>
                                    <option value="cashier" <?php echo ($role === 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                                </select>
                                <div class="invalid-feedback"><?php echo $role_err; ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" id="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@example.com" />
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="••••••••" />
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Log In</button>
                            <a href="index.php" class="btn btn-light border">Back to Home</a>
                        </div>
                    </form>

                    <div class="text-center admin-link-box">
                        <a href="admin/admin_login.php" class="admin-link text-decoration-none">
                            <i class="fas fa-user-shield me-1"></i> Are you an Administrator? Login here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>