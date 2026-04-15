<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Обновление статуса
if (isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['status'], $_POST['order_id']]);
    $success = "Статус обновлён!";
}

$orders = $pdo->query("SELECT o.*, s.name as stone_name FROM orders o JOIN stones s ON o.stone_id = s.id ORDER BY o.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы - Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3 text-white text-center bg-dark"><h5><i class="fas fa-gem"></i> StoneProcessing</h5><small>Admin Panel</small></div>
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
                <h2 class="mb-4">Заказы</h2>
                
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>№ заказа</th><th>Товар</th><th>Покупатель</th><th>Кол-во</th><th>Итого</th><th>Статус</th><th>Дата</th><th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_number'] ?></td>
                                <td><?= htmlspecialchars($order['stone_name']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?><br><small><?= htmlspecialchars($order['customer_email']) ?></small></td>
                                <td><?= $order['quantity'] ?> шт.</td>
                                <td><strong><?= number_format($order['total_price'], 2) ?> ₽</strong></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm" style="width: 130px;">
                                            <option value="new" <?= $order['status']=='new'?'selected':'' ?>>Новый</option>
                                            <option value="processing" <?= $order['status']=='processing'?'selected':'' ?>>В обработке</option>
                                            <option value="paid" <?= $order['status']=='paid'?'selected':'' ?>>Оплачен</option>
                                            <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>Отправлен</option>
                                            <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Выполнен</option>
                                            <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Отменён</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="alert('Заказ #<?= $order['order_number'] ?>\nТовар: <?= htmlspecialchars($order['stone_name']) ?>\nКол-во: <?= $order['quantity'] ?> шт.\nИтого: <?= number_format($order['total_price'], 2) ?> ₽\nУслуги: <?= number_format($order['services_total'], 2) ?> ₽')">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>