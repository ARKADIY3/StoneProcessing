<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $stmt = $pdo->prepare("INSERT INTO stone_categories (name, description, hardness, density) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['hardness'], $_POST['density']]);
    $success = "Категория добавлена!";
}

// Обновление категории
if (isset($_POST['edit_category'])) {
    $stmt = $pdo->prepare("UPDATE stone_categories SET name=?, description=?, hardness=?, density=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['hardness'], $_POST['density'], $_POST['id']]);
    $success = "Категория обновлена!";
}

// Удаление категории
if (isset($_GET['delete'])) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM stones WHERE stone_category_id = ?");
    $check->execute([$_GET['delete']]);
    if ($check->fetchColumn() > 0) {
        $error = "Нельзя удалить категорию, в ней есть камни!";
    } else {
        $pdo->prepare("DELETE FROM stone_categories WHERE id = ?")->execute([$_GET['delete']]);
        $success = "Категория удалена!";
    }
}

$categories = $pdo->query("SELECT * FROM stone_categories ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Категории камней - Админка</title>
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
                    <h2>Категории камней</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Добавить категорию</button>
                </div>
                
                <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Название</th><th>Описание</th><th>Твёрдость</th><th>Плотность</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <td><?= $cat['id'] ?></td>
                                <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($cat['name']) ?>" required></td>
                                <td><input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars($cat['description']) ?>"></td>
                                <td><input type="number" step="0.1" name="hardness" class="form-control form-control-sm" style="width:80px" value="<?= $cat['hardness'] ?>"></td>
                                <td><input type="number" step="10" name="density" class="form-control form-control-sm" style="width:100px" value="<?= $cat['density'] ?>"></td>
                                <td>
                                    <button type="submit" name="edit_category" class="btn btn-sm btn-warning"><i class="fas fa-save"></i></button>
                                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')"><i class="fas fa-trash"></i></a>
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
                    <div class="modal-header bg-primary text-white"><h5>Добавить категорию</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Название</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label>Описание</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="row">
                            <div class="col-md-6"><label>Твёрдость (по Моосу)</label><input type="number" step="0.1" name="hardness" class="form-control" value="5"></div>
                            <div class="col-md-6"><label>Плотность (кг/м³)</label><input type="number" step="10" name="density" class="form-control" value="2600"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>