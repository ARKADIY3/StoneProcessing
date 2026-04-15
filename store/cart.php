<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Добавление в корзину
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $stone_id = $_POST['stone_id'];
    $quantity = (int)$_POST['quantity'];
    $services = isset($_POST['services']) ? json_encode($_POST['services']) : null;
    
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND stone_id = ?");
    $stmt->execute([$user_id, $stone_id]);
    
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE cart SET quantity = quantity + ?, selected_services = ? WHERE user_id = ? AND stone_id = ?")
            ->execute([$quantity, $services, $user_id, $stone_id]);
    } else {
        $pdo->prepare("INSERT INTO cart (user_id, stone_id, quantity, selected_services) VALUES (?, ?, ?, ?)")
            ->execute([$user_id, $stone_id, $quantity, $services]);
    }
    header('Location: cart.php');
    exit();
}

// Обновление корзины
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        if ($quantity <= 0) {
            $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        } else {
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$quantity, $id, $user_id]);
        }
    }
    header('Location: cart.php');
    exit();
}

// Удаление из корзины
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$_GET['remove'], $user_id]);
    header('Location: cart.php');
    exit();
}

// Оформление заказа
if (isset($_POST['checkout'])) {
    $cart_items = $pdo->prepare("
        SELECT c.*, s.name, s.price_per_unit, s.price_per_sqm, s.quantity as stock, s.length_cm, s.width_cm
        FROM cart c JOIN stones s ON c.stone_id = s.id WHERE c.user_id = ?
    ");
    $cart_items->execute([$user_id]);
    $items = $cart_items->fetchAll();
    
    $profile = getUserProfile($pdo, $user_id);
    
    foreach ($items as $item) {
        if ($item['quantity'] <= $item['stock']) {
            $area = ($item['length_cm'] * $item['width_cm']) / 10000;
            $stone_total = $item['price_per_unit'] * $item['quantity'];
            
            $services_total = 0;
            $services_list = json_decode($item['selected_services'], true) ?: [];
            foreach ($services_list as $service_id) {
                $svc = $pdo->prepare("SELECT price_type, price_value FROM services WHERE id = ?");
                $svc->execute([$service_id]);
                $service = $svc->fetch();
                if ($service) {
                    if ($service['price_type'] == 'fixed') $services_total += $service['price_value'];
                    elseif ($service['price_type'] == 'per_sqm') $services_total += $service['price_value'] * $area * $item['quantity'];
                    elseif ($service['price_type'] == 'percentage') $services_total += $item['price_per_unit'] * ($service['price_value'] / 100) * $item['quantity'];
                }
            }
            
            $order_number = generateOrderNumber();
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, stone_id, stone_name, stone_price, quantity, selected_services, stone_total, services_total, total_price, customer_name, customer_email, customer_phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_number, $user_id, $item['stone_id'], $item['name'], $item['price_per_unit'],
                $item['quantity'], $item['selected_services'], $stone_total, $services_total,
                $stone_total + $services_total,
                $profile['first_name'] . ' ' . $profile['last_name'], $profile['email'], $profile['phone']
            ]);
            
            $pdo->prepare("UPDATE stones SET quantity = quantity - ? WHERE id = ?")->execute([$item['quantity'], $item['stone_id']]);
        }
    }
    
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    header('Location: ../orders.php?success=1');
    exit();
}

// Получение товаров в корзине
$cart_items = $pdo->prepare("
    SELECT c.*, s.name, s.price_per_unit, s.price_per_sqm, s.main_image, s.quantity as stock, s.length_cm, s.width_cm
    FROM cart c 
    JOIN stones s ON c.stone_id = s.id 
    WHERE c.user_id = ?
");
$cart_items->execute([$user_id]);
$items = $cart_items->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item['price_per_unit'] * $item['quantity'];
}

// Получаем профиль для отображения имени
$profile = getUserProfile($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .quantity-input { width: 80px; text-align: center; }
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,0.8); }
        .navbar-dark .navbar-nav .nav-link:hover { color: #fff; }
        .navbar-dark .navbar-nav .nav-link.active { color: #ffc107; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container my-5">
        <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Корзина</h2>
        
        <?php if(empty($items)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h4>Корзина пуста</h4>
                <p>Добавьте камни из <a href="index.php" class="alert-link">каталога</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Цена за шт</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/stones/<?= htmlspecialchars($item['main_image'] ?? 'default_stone.png') ?>" 
                                         class="cart-item-img" 
                                         onerror="this.src='../uploads/stones/default_stone.png'">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-ruler"></i> <?= $item['length_cm'] ?>×<?= $item['width_cm'] ?> см, 
                                        <i class="fas fa-weight-hanging"></i> <?= number_format($item['price_per_sqm'], 0) ?> ₽/м²
                                    </small>
                                </td>
                                <td><?= number_format($item['price_per_unit'], 2) ?> ₽</td>
                                <td>
                                    <input type="number" name="quantity[<?= $item['id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" 
                                           min="1" max="<?= $item['stock'] ?>"
                                           class="form-control quantity-input">
                                </td>
                                <td><strong><?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?> ₽</strong></td>
                                <td>
                                    <a href="?remove=<?= $item['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Удалить товар из корзины?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end"><strong>Итого:</strong></td>
                                <td><strong class="h4 text-primary"><?= number_format($total, 2) ?> ₽</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" name="update_cart" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Обновить корзину
                    </button>
                    <button type="submit" name="checkout" class="btn btn-success btn-lg" 
                            onclick="return confirm('Оформить заказ?')">
                        <i class="fas fa-check-circle"></i> Оформить заказ
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
<?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>