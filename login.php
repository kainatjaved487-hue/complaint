<?php
require_once 'core/session.php'; // Use the updated session file above
require_once 'core/auth.php';
$auth = new Auth($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... rest of your login logic
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if ($auth->login($identifier, $password)) {
        require_once 'core/functions.php';
        logActivity($_SESSION['user_id'], 'User Login', 'User successfully signed in via web interface.');
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Credentials or Account Suspended.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Complaint System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css" />
</head>
<body class="login-page bg-body-secondary">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="link-dark text-decoration-none">
                    <h1 class="mb-0"><b>Complaint</b>ERP</h1>
                </a>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>
                
                <?php if($error): ?>
                    <div class="alert alert-danger text-center p-2"><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="text" name="identifier" class="form-control" id="loginId" placeholder="" required>
                            <label for="loginId">Email / CNIC / Reg No</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-person"></span></div>
                    </div>
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="password" name="password" class="form-control" id="loginPass" placeholder="" required>
                            <label for="loginPass">Password</label>
                        </div>
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="flexCheckDefault">
                                <label class="form-check-label" for="flexCheckDefault">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary w-100">Sign In</button>
                        </div>
                    </div>
                </form>

                <p class="mb-0 mt-3">
                    <a href="register.php" class="text-center">Register a new membership</a>
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>