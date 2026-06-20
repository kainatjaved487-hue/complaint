<?php
session_start();
require_once 'core/auth.php';
$auth = new Auth($pdo);
$roles = $auth->getPublicRoles();

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'identity_no' => trim($_POST['identity_no']),
        'registration_no' => trim($_POST['registration_no'])
    ];

    // Basic Backend Validation
    if ($data['password'] !== $_POST['retype_password']) {
        $msg = "Passwords do not match!";
        $msgType = "danger";
    } else {
        $result = $auth->register($data);
        if ($result === true) {
            $msg = "Registration successful! <a href='login.php'>Login now</a>";
            $msgType = "success";
        } else {
            $msg = $result;
            $msgType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Complaint System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css" />
</head>
<body class="register-page bg-body-secondary">
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="link-dark text-decoration-none">
                    <h1 class="mb-0"><b>Complaint</b>ERP</h1>
                </a>
            </div>
            <div class="card-body register-card-body">
                <p class="login-box-msg">Register a new membership</p>

                <?php if($msg): ?>
                    <div class="alert alert-<?= $msgType ?>"><?= $msg ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="name" class="form-control" id="regName" placeholder="" required>
                            <label for="regName">Full Name</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-person"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="email" name="email" class="form-control" id="regEmail" placeholder="" required>
                            <label for="regEmail">Email</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="identity_no" class="form-control" id="regCnic" placeholder="">
                            <label for="regCnic">CNIC / Identity No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-card-text"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="registration_no" class="form-control" id="regNo" placeholder="">
                            <label for="regNo">Student/Employee Reg No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-upc-scan"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <select name="role" class="form-select" id="regRole" required>
                                <option value="" disabled selected>Select Role</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="regRole">I am a...</label>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password" name="password" class="form-control" id="regPass" placeholder="" required>
                            <label for="regPass">Password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-lock"></span></div>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password" name="retype_password" class="form-control" id="regRetype" placeholder="" required>
                            <label for="regRetype">Retype password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </div>
                    </div>
                </form>

                <a href="login.php" class="text-center mt-3 d-block">I already have a membership</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>