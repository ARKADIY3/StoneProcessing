<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Обработка загрузки изображения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_image'])) {
    $stone_id = $_POST['stone_id'];
    $result = uploadFile($_FILES['stone_image'], '../uploads/stones/');
    
    if ($result['success']) {
        $pdo->prepare("UPDATE stones SET main_image = ? WHERE id = ?")->execute([$result['filename'], $stone_id]);
        $success = "Изображение загружено!";
    } else {
        $error = $result['error'];
    }
}

// Добавление камня
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stone'])) {
    $stmt = $pdo->prepare("
        INSERT INTO stones (name, stone_category_id, length_cm, width_cm, height_cm, weight_kg, volume_m3, color, origin, texture, price_per_sqm, price_per_unit, price_per_kg, quantity, short_description, full_description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['name'], $_POST['stone_category_id'], $_POST['length_cm'], $_POST['width_cm'],
        $_POST['height_cm'], $_POST['weight_kg'], $_POST['volume_m3'], $_POST['color'],
        $_POST['origin'], $_POST['texture'], $_POST['price_per_sqm'], $_POST['price_per_unit'],
        $_POST['price_per_kg'], $_POST['quantity'], $_POST['short_description'], $_POST['full_description']
    ]);
    $success = "Камень добавлен!";
}

// Обновление камня
if (isset($_POST['edit_stone'])) {
    $stmt = $pdo->prepare("
        UPDATE stones SET name=?, stone_category_id=?, length_cm=?, width_cm=?, height_cm=?, weight_kg=?, volume_m3=?, color=?, origin=?, texture=?, price_per_sqm=?, price_per_unit=?, price_per_kg=?, quantity=?, short_description=?, full_description=?
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['name'], $_POST['stone_category_id'], $_POST['length_cm'], $_POST['width_cm'],
        $_POST['height_cm'], $_POST['weight_kg'], $_POST['volume_m3'], $_POST['color'],
        $_POST['origin'], $_POST['texture'], $_POST['price_per_sqm'], $_POST['price_per_unit'],
        $_POST['price_per_kg'], $_POST['quantity'], $_POST['short_description'], $_POST['full_description'],
        $_POST['id']
    ]);
    $success = "Камень обновлён!";
}

// Удаление камня
if (isset($_GET['delete'])) {
    // Получаем изображение для удаления
    $img = $pdo->prepare("SELECT main_image FROM stones WHERE id = ?");
    $img->execute([$_GET['delete']]);
    $main_image = $img->fetchColumn();
    
    if ($main_image && $main_image != 'default_stone.png' && file_exists("../uploads/stones/" . $main_image)) {
        unlink("../uploads/stones/" . $main_image);
    }
    
    $pdo->prepare("DELETE FROM stones WHERE id = ?")->execute([$_GET['delete']]);
    $success = "Камень удалён!";
}

$stones = $pdo->query("SELECT s.*, sc.name as category_name FROM stones s LEFT JOIN stone_categories sc ON s.stone_category_id = sc.id ORDER BY s.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM stone_categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление камнями - Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .stone-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .table-actions form { display: inline-block; }
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
                    <h2><i class="fas fa-gem"></i> Управление камнями</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Добавить камень
                    </button>
                </div>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Изобр.</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Размеры (см)</th>
                                <th>Вес (кг)</th>
                                <th>Цена (₽/м²)</th>
                                <th>Кол-во</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stones as $stone): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $stone['id'] ?>">
                                    <td><?= $stone['id'] ?></td>
                                    <td>
                                        <img src="../uploads/stones/<?= htmlspecialchars($stone['main_image'] ?? 'default_stone.png') ?>" 
                                             class="stone-thumb" 
                                             onerror="this.src='../uploads/stones/default_stone.png'">
                                    </td>
                                    <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($stone['name']) ?>" required></td>
                                    <td>
                                        <select name="stone_category_id" class="form-select form-select-sm">
                                            <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $stone['stone_category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="length_cm" class="form-control form-control-sm" style="width:60px;display:inline" value="<?= $stone['length_cm'] ?>">×
                                        <input type="text" name="width_cm" class="form-control form-control-sm" style="width:60px;display:inline" value="<?= $stone['width_cm'] ?>">×
                                        <input type="text" name="height_cm" class="form-control form-control-sm" style="width:60px;display:inline" value="<?= $stone['height_cm'] ?>">
                                    </td>
                                    <td><input type="text" name="weight_kg" class="form-control form-control-sm" style="width:80px" value="<?= $stone['weight_kg'] ?>"></td>
                                    <td><input type="text" name="price_per_sqm" class="form-control form-control-sm" style="width:100px" value="<?= $stone['price_per_sqm'] ?>"></td>
                                    <td><input type="text" name="quantity" class="form-control form-control-sm" style="width:70px" value="<?= $stone['quantity'] ?>"></td>
                                    <td>
                                        <input type="hidden" name="volume_m3" value="<?= $stone['volume_m3'] ?>">
                                        <input type="hidden" name="color" value="<?= htmlspecialchars($stone['color']) ?>">
                                        <input type="hidden" name="origin" value="<?= htmlspecialchars($stone['origin']) ?>">
                                        <input type="hidden" name="texture" value="<?= htmlspecialchars($stone['texture']) ?>">
                                        <input type="hidden" name="price_per_unit" value="<?= $stone['price_per_unit'] ?>">
                                        <input type="hidden" name="price_per_kg" value="<?= $stone['price_per_kg'] ?>">
                                        <input type="hidden" name="short_description" value="<?= htmlspecialchars($stone['short_description']) ?>">
                                        <input type="hidden" name="full_description" value="<?= htmlspecialchars($stone['full_description']) ?>">
                                        <button type="submit" name="edit_stone" class="btn btn-sm btn-warning" title="Сохранить"><i class="fas fa-save"></i></button>
                                        <button type="button" class="btn btn-sm btn-info" title="Загрузить фото" data-bs-toggle="modal" data-bs-target="#uploadModal" data-stone-id="<?= $stone['id'] ?>" data-stone-name="<?= htmlspecialchars($stone['name']) ?>"><i class="fas fa-image"></i></button>
                                        <a href="?delete=<?= $stone['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить камень?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal добавления камня -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white"><h5><i class="fas fa-plus"></i> Добавить камень</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Название *</label><input type="text" name="name" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label>Категория</label><select name="stone_category_id" class="form-select"><?php foreach($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3 mb-3"><label>Длина (см)</label><input type="number" step="0.01" name="length_cm" class="form-control" value="0"></div>
                            <div class="col-md-3 mb-3"><label>Ширина (см)</label><input type="number" step="0.01" name="width_cm" class="form-control" value="0"></div>
                            <div class="col-md-3 mb-3"><label>Высота (см)</label><input type="number" step="0.01" name="height_cm" class="form-control" value="0"></div>
                            <div class="col-md-3 mb-3"><label>Вес (кг)</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="0"></div>
                            <div class="col-md-3 mb-3"><label>Объём (м³)</label><input type="number" step="0.0001" name="volume_m3" class="form-control" value="0"></div>
                            <div class="col-md-3 mb-3"><label>Цвет</label><input type="text" name="color" class="form-control"></div>
                            <div class="col-md-3 mb-3"><label>Месторождение</label><input type="text" name="origin" class="form-control"></div>
                            <div class="col-md-3 mb-3"><label>Текстура</label><input type="text" name="texture" class="form-control"></div>
                            <div class="col-md-3 mb-3"><label>Цена за м² (₽)</label><input type="number" step="0.01" name="price_per_sqm" class="form-control" required></div>
                            <div class="col-md-3 mb-3"><label>Цена за шт (₽)</label><input type="number" step="0.01" name="price_per_unit" class="form-control" required></div>
                            <div class="col-md-3 mb-3"><label>Цена за кг (₽)</label><input type="number" step="0.01" name="price_per_kg" class="form-control"></div>
                            <div class="col-md-3 mb-3"><label>Количество</label><input type="number" name="quantity" class="form-control" value="0"></div>
                            <div class="col-12 mb-3"><label>Краткое описание</label><textarea name="short_description" class="form-control" rows="2"></textarea></div>
                            <div class="col-12 mb-3"><label>Полное описание</label><textarea name="full_description" class="form-control" rows="3"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_stone" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal загрузки изображения -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-info text-white"><h5><i class="fas fa-image"></i> Загрузить изображение</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p>Камень: <strong id="stoneName"></strong></p>
                        <input type="hidden" name="stone_id" id="stoneId">
                        <div class="mb-3">
                            <label class="form-label">Выберите изображение</label>
                            <input type="file" name="stone_image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Рекомендуемый размер: 500x500px. Поддерживаются JPG, PNG, GIF, WEBP</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="upload_image" class="btn btn-primary">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Передача данных в модальное окно загрузки
        const uploadModal = document.getElementById('uploadModal');
        if (uploadModal) {
            uploadModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const stoneId = button.getAttribute('data-stone-id');
                const stoneName = button.getAttribute('data-stone-name');
                document.getElementById('stoneId').value = stoneId;
                document.getElementById('stoneName').textContent = stoneName;
            });
        }
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>