<?php
require_once 'config/db.php';


if (isLoggedIn()) {
    if ($_SESSION['role'] == 'admin') header('Location: admin/index.php');
    else header('Location: store/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') header('Location: admin/index.php');
        else header('Location: store/index.php');
        exit();
    } else {
        $error = "Неверный логин или пароль!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh}.login-card{max-width:400px;margin:100px auto}</style>
</head>
<body>
    <div class="container">
        <div class="card shadow login-card">
            <div class="card-header bg-primary text-white text-center"><h3><i class="fas fa-gem"></i> StoneProcessing</h3><p>Вход в систему</p></div>
            <div class="card-body">
                <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><label>Логин</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label>Пароль</label><input type="password" name="password" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>
                <hr>
                <div class="text-center"><a href="register.php">Регистрация</a></div>
                <div class="text-center mt-2"><small class="text-muted">admin / admin123 | user / user123</small></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>