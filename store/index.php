<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$profile = getUserProfile($pdo, $user_id);

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = ["s.is_active = 1"];
$params = [];

if ($category_filter) {
    $where[] = "s.stone_category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $where[] = "(s.name LIKE ? OR s.full_description LIKE ? OR s.origin LIKE ? OR s.color LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stones = $pdo->prepare("
    SELECT s.*, sc.name as category_name
    FROM stones s
    JOIN stone_categories sc ON s.stone_category_id = sc.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY s.id DESC
");
$stones->execute($params);
$stones = $stones->fetchAll();

$categories = $pdo->query("SELECT * FROM stone_categories ORDER BY name")->fetchAll();

$cart_count = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$cart_count->execute([$user_id]);
$cart_count = $cart_count->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог камней - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stone-card { transition: transform 0.3s; height: 100%; }
        .stone-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .stone-img { height: 200px; object-fit: cover; width: 100%; }
        .price { font-size: 1.3rem; font-weight: bold; color: #28a745; }
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,0.8); }
        .navbar-dark .navbar-nav .nav-link:hover { color: #fff; }
        .search-form { min-width: 250px; }
        @media (max-width: 768px) {
            .search-form { margin: 10px 0; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container my-4">
        <div class="row">
            <!-- Боковая панель с фильтрами -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-filter"></i> Фильтры
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filterForm">
                            <?php if($search): ?>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Категория камня</label>
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все категории</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Применить</button>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Сбросить</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Каталог камней -->
            <div class="col-md-9">
                <?php if($search): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-search"></i> Результаты поиска: "<strong><?= htmlspecialchars($search) ?></strong>"
                        <a href="index.php" class="float-end">Сбросить</a>
                    </div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <?php if(count($stones) > 0): ?>
                        <?php foreach($stones as $stone): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card stone-card shadow-sm">
                                <img src="../uploads/stones/<?= htmlspecialchars($stone['main_image'] ?? 'default_stone.png') ?>" 
                                     class="stone-img" 
                                     alt="<?= htmlspecialchars($stone['name']) ?>"
                                     onerror="this.src='../uploads/stones/default_stone.png'">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($stone['name']) ?></h5>
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-weight-hanging"></i> <?= $stone['weight_kg'] ?> кг | 
                                        <i class="fas fa-ruler"></i> <?= $stone['length_cm'] ?>×<?= $stone['width_cm'] ?> см
                                    </p>
                                    <div class="price"><?= number_format($stone['price_per_sqm'], 2) ?> ₽/м²</div>
                                    <div class="text-muted small">или <?= number_format($stone['price_per_unit'], 2) ?> ₽/шт</div>
                                    
                                    <div class="mt-3 d-grid">
                                        <a href="stone.php?id=<?= $stone['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-info-circle"></i> Подробнее
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center py-5">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h4>Камни не найдены</h4>
                                <p>Попробуйте изменить параметры поиска</p>
                                <a href="index.php" class="btn btn-primary">Смотреть все камни</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
<?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>