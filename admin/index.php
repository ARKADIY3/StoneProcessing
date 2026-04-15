<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$stats = [
    'stones' => $pdo->query("SELECT COUNT(*) FROM stones")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM stone_categories")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM stones WHERE quantity < 10")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .stat-card { transition: transform 0.3s; cursor: pointer; border-radius: 10px; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3 text-white text-center bg-dark">
                    <h5><i class="fas fa-gem"></i> StoneProcessing</h5>
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a>
                    <a class="nav-link" href="stones.php"><i class="fas fa-gem"></i> Камни</a>
                    <a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Категории</a>
                    <a class="nav-link" href="services.php"><i class="fas fa-cogs"></i> Услуги</a>
                    <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
                    <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Пользователи</a>
                    <a class="nav-link active" href="messages.php"><i class="fas fa-envelope"></i> Сообщения</a>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </nav>
            </div>
            
            <div class="col-md-10 p-4">
                <h2>Панель управления</h2>
                
                <div class="row mt-4">
                    <div class="col-md-3"><div class="card text-white bg-primary stat-card"><div class="card-body"><h5>Камни</h5><h2><?= $stats['stones'] ?></h2></div></div></div>
                    <div class="col-md-3"><div class="card text-white bg-success stat-card"><div class="card-body"><h5>Категории</h5><h2><?= $stats['categories'] ?></h2></div></div></div>
                    <div class="col-md-3"><div class="card text-white bg-info stat-card"><div class="card-body"><h5>Заказы</h5><h2><?= $stats['orders'] ?></h2></div></div></div>
                    <div class="col-md-3"><div class="card text-white bg-warning stat-card"><div class="card-body"><h5>Пользователи</h5><h2><?= $stats['users'] ?></h2></div></div></div>
                    <div class="col-md-3 mt-3"><div class="card text-white bg-danger stat-card"><div class="card-body"><h5>Низкий запас</h5><h2><?= $stats['low_stock'] ?></h2></div></div></div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>