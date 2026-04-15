<?php
require_once 'config/db.php';

include 'includes/menu.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$profile = getUserProfile($pdo, $user_id);
$success = isset($_GET['success']);

// Получаем заказы пользователя
$orders = $pdo->prepare("
    SELECT o.*, s.name as stone_name, s.main_image
    FROM orders o
    JOIN stones s ON o.stone_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$orders->execute([$user_id]);
$orders = $orders->fetchAll();

// Количество товаров в корзине
$cart_count = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$cart_count->execute([$user_id]);
$cart_count = $cart_count->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-card {
            transition: transform 0.2s;
            border-left: 4px solid;
        }
        .order-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .navbar-dark .navbar-nav .nav-link:hover {
            color: #fff;
        }
        .navbar-dark .navbar-nav .nav-link.active {
            color: #ffc107;
        }
        .search-form {
            min-width: 250px;
        }
        @media (max-width: 768px) {
            .search-form {
                margin: 10px 0;
                width: 100%;
            }
        }
        .order-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Уведомление об успешном заказе -->
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4 class="mb-2">Заказ успешно оформлен!</h4>
                <p>Спасибо за покупку. Мы свяжемся с вами в ближайшее время для уточнения деталей.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list-alt"></i> Мои заказы</h2>
            <a href="store/index.php" class="btn btn-outline-primary">
                <i class="fas fa-shopping-bag"></i> Продолжить покупки
            </a>
        </div>
        
        <?php if(empty($orders)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h4>У вас пока нет заказов</h4>
                <p>Перейдите в <a href="store/index.php" class="alert-link">магазин</a>, чтобы сделать первый заказ</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($orders as $order): ?>
                <div class="col-12 mb-4">
                    <div class="card order-card shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Изображение товара -->
                                <div class="col-md-2 text-center">
                                    <img src="uploads/stones/<?= htmlspecialchars($order['main_image'] ?? 'default_stone.png') ?>" 
                                         class="order-img"
                                         onerror="this.src='uploads/stones/default_stone.png'"
                                         alt="<?= htmlspecialchars($order['stone_name']) ?>">
                                </div>
                                
                                <!-- Информация о заказе -->
                                <div class="col-md-7">
                                    <h5 class="mb-1"><?= htmlspecialchars($order['stone_name']) ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-hashtag"></i> Заказ #<?= $order['order_number'] ?>
                                    </p>
                                    <div class="row">
                                        <div class="col-4">
                                            <small class="text-muted">Количество:</small>
                                            <div><strong><?= $order['quantity'] ?> шт.</strong></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Стоимость камня:</small>
                                            <div><strong><?= number_format($order['stone_total'], 2) ?> ₽</strong></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Услуги:</small>
                                            <div><strong><?= number_format($order['services_total'], 2) ?> ₽</strong></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Статус и сумма -->
                                <div class="col-md-3 text-md-end">
                                    <div class="mb-2">
                                        <?php
                                        $status_colors = [
                                            'new' => 'primary',
                                            'processing' => 'warning',
                                            'paid' => 'info',
                                            'shipped' => 'secondary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $status_labels = [
                                            'new' => '🆕 Новый',
                                            'processing' => '⚙️ В обработке',
                                            'paid' => '💰 Оплачен',
                                            'shipped' => '📦 Отправлен',
                                            'completed' => '✅ Выполнен',
                                            'cancelled' => '❌ Отменён'
                                        ];
                                        ?>
                                        <span class="status-badge bg-<?= $status_colors[$order['status']] ?> text-white">
                                            <?= $status_labels[$order['status']] ?>
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <strong class="h5 text-primary"><?= number_format($order['total_price'], 2) ?> ₽</strong>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Детали заказа (раскрывающийся блок) -->
                            <div class="mt-3 pt-3 border-top">
                                <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#orderDetails<?= $order['id'] ?>">
                                    <i class="fas fa-chevron-down"></i> Подробнее о заказе
                                </button>
                                
                                <div class="collapse mt-2" id="orderDetails<?= $order['id'] ?>">
                                    <div class="card card-body bg-light">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Информация о доставке:</strong>
                                                <p class="mb-1">Получатель: <?= htmlspecialchars($order['customer_name']) ?></p>
                                                <p class="mb-1">Email: <?= htmlspecialchars($order['customer_email']) ?></p>
                                                <p class="mb-1">Телефон: <?= htmlspecialchars($order['customer_phone']) ?></p>
                                                <?php if($order['delivery_address']): ?>
                                                <p class="mb-0">Адрес: <?= htmlspecialchars($order['delivery_address']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Детали заказа:</strong>
                                                <p class="mb-1">Номер заказа: #<?= $order['order_number'] ?></p>
                                                <p class="mb-1">Дата заказа: <?= date('d.m.Y H:i:s', strtotime($order['created_at'])) ?></p>
                                                <p class="mb-0">Последнее обновление: <?= date('d.m.Y H:i:s', strtotime($order['updated_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Автоматическое скрытие уведомления через 5 секунд
        setTimeout(function() {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>