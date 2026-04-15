<?php
// store/menu.php - меню для страниц в папке store
// Этот файл подключается из store/index.php, store/stone.php, store/cart.php

// Получаем количество товаров в корзине
$cart_count = 0;
$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetch()['total'] ?? 0;

// Получаем профиль пользователя
$profile_name = '';
$profile = getUserProfile($pdo, $_SESSION['user_id']);
$profile_name = $profile['first_name'] ?: $_SESSION['username'];

// Непрочитанные сообщения
$unread_messages = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE (user_id = ? OR email = ?) AND status = 'replied' AND is_read_by_user = 0");
$stmt->execute([$_SESSION['user_id'], $profile['email']]);
$unread_messages = $stmt->fetchColumn();
?>
<!-- Меню для страниц в папке store/ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-gem"></i> StoneProcessing
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Поисковая строка -->
            <form class="d-flex mx-auto" method="GET" action="index.php" style="max-width: 400px; width: 100%;">
                <input class="form-control me-2" type="search" name="search" 
                       placeholder="🔍 Поиск камней по названию, цвету..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn btn-outline-light" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-store"></i> Магазин
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Корзина
                        <?php if($cart_count > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <?php if(isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($profile_name) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php">
                            <i class="fas fa-id-card"></i> Личные данные
                        </a></li>
                        <li><a class="dropdown-item" href="../profile_messages.php">
                            <i class="fas fa-envelope"></i> Сообщения
                            <?php if($unread_messages > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $unread_messages ?></span>
                            <?php endif; ?>
                        </a></li>
                        <li><a class="dropdown-item" href="../orders.php">
                            <i class="fas fa-shopping-bag"></i> Мои заказы
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Выйти
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="../login.php">
                        <i class="fas fa-sign-in-alt"></i> Вход
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../register.php">
                        <i class="fas fa-user-plus"></i> Регистрация
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="../contact.php">
                        <i class="fas fa-phone-alt"></i> Контакты
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>