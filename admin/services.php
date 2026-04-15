<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Добавление услуги
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $stmt = $pdo->prepare("INSERT INTO services (name, description, price_type, price_value, unit, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price_type'], $_POST['price_value'], $_POST['unit'], $_POST['sort_order']]);
    $success = "Услуга добавлена!";
}

// Обновление услуги
if (isset($_POST['edit_service'])) {
    $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price_type=?, price_value=?, unit=?, sort_order=?, is_active=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price_type'], $_POST['price_value'], $_POST['unit'], $_POST['sort_order'], $_POST['is_active'], $_POST['id']]);
    $success = "Услуга обновлена!";
}

// Удаление услуги
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$_GET['delete']]);
    $success = "Услуга удалена!";
}

$services = $pdo->query("SELECT * FROM services ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Услуги - Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
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
                <div class="d-flex justify-content-between mb-4">
                    <h2>Услуги обработки</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Добавить услугу</button>
                </div>
                
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Название</th><th>Описание</th><th>Тип цены</th><th>Цена</th><th>Активна</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($services as $service): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                <td><?= $service['id'] ?></td>
                                <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($service['name']) ?>" required></td>
                                <td><input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars($service['description']) ?>"></td>
                                <td>
                                    <select name="price_type" class="form-select form-select-sm" style="width:120px">
                                        <option value="fixed" <?= $service['price_type']=='fixed'?'selected':'' ?>>Фикс. цена</option>
                                        <option value="per_sqm" <?= $service['price_type']=='per_sqm'?'selected':'' ?>>За м²</option>
                                        <option value="percentage" <?= $service['price_type']=='percentage'?'selected':'' ?>>Процент</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="price_value" class="form-control form-control-sm" style="width:100px" value="<?= $service['price_value'] ?>"></td>
                                <td>
                                    <select name="is_active" class="form-select form-select-sm" style="width:80px">
                                        <option value="1" <?= $service['is_active']?'selected':'' ?>>Да</option>
                                        <option value="0" <?= !$service['is_active']?'selected':'' ?>>Нет</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="unit" value="<?= htmlspecialchars($service['unit']) ?>">
                                    <input type="hidden" name="sort_order" value="<?= $service['sort_order'] ?>">
                                    <button type="submit" name="edit_service" class="btn btn-sm btn-warning"><i class="fas fa-save"></i></button>
                                    <a href="?delete=<?= $service['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal добавления -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5>Добавить услугу</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Название</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label>Описание</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="row">
                            <div class="col-md-6"><label>Тип цены</label>
                                <select name="price_type" class="form-select">
                                    <option value="fixed">Фиксированная (₽)</option>
                                    <option value="per_sqm">За м² (₽/м²)</option>
                                    <option value="percentage">Процент от цены камня (%)</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label>Цена</label><input type="number" step="0.01" name="price_value" class="form-control" required></div>
                            <div class="col-md-6"><label>Единица измерения</label><input type="text" name="unit" class="form-control" placeholder="м², шт, %"></div>
                            <div class="col-md-6"><label>Порядок сортировки</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_service" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>