<?php
require_once 'config/db.php';

include 'includes/menu.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$profile = getUserProfile($pdo, $user_id);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $error = null;
    
    // Валидация телефона
    if (!empty($phone) && !validatePhone($phone)) {
        $error = "Некорректный номер телефона! Буквы запрещены. Используйте только цифры, +, пробелы и дефисы.";
    }
    
    // Проверка уникальности email (исключая текущего пользователя)
    if (!$error && !empty($email) && !isEmailUnique($pdo, $email, $user_id)) {
        $error = "Этот email уже используется другим пользователем!";
    }
    
    if (!$error) {
        $formatted_phone = formatPhone($phone);
        $stmt = $pdo->prepare("UPDATE user_profiles SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE user_id=?");
        $stmt->execute([$first_name, $last_name, $email, $formatted_phone, $address, $user_id]);
        $success = "Профиль обновлён!";
        $profile = getUserProfile($pdo, $user_id);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old = md5($_POST['old_password']);
    $new = md5($_POST['new_password']);
    $confirm = md5($_POST['confirm_password']);
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch();
    
    if ($current['password'] != $old) $error = "Неверный текущий пароль!";
    elseif ($new != $confirm) $error = "Пароли не совпадают!";
    else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new, $user_id]);
        $success = "Пароль изменён!";
    }
}

// Заказы пользователя
$orders = $pdo->prepare("
    SELECT o.*, s.name as stone_name 
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

$avatar_path = !empty($profile['avatar']) && file_exists("uploads/avatars/" . $profile['avatar']) 
    ? "uploads/avatars/" . $profile['avatar'] 
    : "uploads/avatars/default_avatar.png";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .avatar-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,0.8); }
        .navbar-dark .navbar-nav .nav-link:hover { color: #fff; }
        .navbar-dark .navbar-nav .nav-link.active { color: #ffc107; }
        .search-form { min-width: 250px; }
        .error-text { font-size: 0.8rem; margin-top: 5px; }
        .is-invalid { border-color: #dc3545; }
        .is-valid { border-color: #28a745; }
        @media (max-width: 768px) { .search-form { margin: 10px 0; width: 100%; } }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="<?= $avatar_path ?>" id="avatar-img" class="avatar-preview mb-3" alt="Avatar" onclick="document.getElementById('avatarInput').click()" title="Нажмите для смены аватара">
                        <form id="avatarForm" enctype="multipart/form-data">
                            <input type="file" name="avatar" id="avatarInput" style="display: none;" accept="image/*">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="document.getElementById('avatarInput').click()"><i class="fas fa-camera"></i> Сменить аватар</button>
                            <div id="uploadProgress" class="mt-2" style="display:none"><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%">Загрузка...</div></div></div>
                        </form>
                        <hr>
                        <div class="text-start">
                            <p><i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($profile['email'] ?: 'Не указан') ?></p>
                            <p><i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($profile['phone'] ?: 'Не указан') ?></p>
                            <p><i class="fas fa-calendar-alt text-muted"></i> Регистрация: <?= date('d.m.Y', strtotime($_SESSION['reg_date'] ?? 'now')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-user-edit"></i> Личная информация</h5></div>
                    <div class="card-body">
                        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                        
                        <form method="POST" id="profileForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Имя *</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($profile['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Фамилия *</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($profile['last_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" required>
                                    <div id="emailError" class="error-text text-danger" style="display:none"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="tel" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($profile['phone']) ?>" placeholder="+7 (999) 123-45-67">
                                    <div id="phoneError" class="error-text text-danger" style="display:none"></div>
                                    <small class="text-muted">Только цифры, +, пробелы и дефисы. Буквы запрещены!</small>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Адрес</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($profile['address']) ?></textarea>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить изменения</button>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning"><h5 class="mb-0"><i class="fas fa-key"></i> Смена пароля</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4 mb-3"><label>Текущий пароль</label><input type="password" name="old_password" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label>Новый пароль</label><input type="password" name="new_password" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label>Подтверждение</label><input type="password" name="confirm_password" class="form-control" required></div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning"><i class="fas fa-key"></i> Сменить пароль</button>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Мои заказы</h5></div>
                    <div class="card-body">
                        <?php if(empty($orders)): ?>
                            <div class="alert alert-info text-center mb-0">У вас пока нет заказов. <a href="store/index.php">Перейти в магазин</a></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light"><tr><th>№ заказа</th><th>Товар</th><th>Кол-во</th><th>Сумма</th><th>Статус</th><th>Дата</th></tr></thead>
                                    <tbody>
                                        <?php foreach($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['order_number'] ?></strong></td>
                                            <td><?= htmlspecialchars($order['stone_name']) ?></td>
                                            <td><?= $order['quantity'] ?> шт.</td>
                                            <td><?= number_format($order['total_price'], 2) ?> ₽</td>
                                            <td><span class="badge bg-<?= $order['status'] == 'new' ? 'primary' : ($order['status'] == 'processing' ? 'warning' : ($order['status'] == 'completed' ? 'success' : 'danger')) ?>"><?= $order['status'] ?></span></td>
                                            <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Проверка уникальности email
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        let emailTimeout;
        const userId = <?= $user_id ?>;
        
        function checkEmailUnique(email) {
            if (!email) return;
            fetch('check_email.php?email=' + encodeURIComponent(email) + '&user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (!data.unique) {
                        emailError.textContent = '❌ Этот email уже используется другим пользователем!';
                        emailError.style.display = 'block';
                        emailInput.classList.add('is-invalid');
                        emailInput.classList.remove('is-valid');
                    } else {
                        emailError.style.display = 'none';
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                    }
                });
        }
        
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            emailTimeout = setTimeout(() => checkEmailUnique(this.value), 500);
        });
        
        // Валидация телефона (без букв)
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phoneError');
        
        function validatePhoneNumber(phone) {
            if (!phone) return true;
            if (/[a-zA-Zа-яА-ЯёЁ]/.test(phone)) return false;
            if (!/^[0-9+\-\s\(\)]*$/.test(phone)) return false;
            const digits = phone.replace(/[^0-9]/g, '');
            return digits.length >= 10 && digits.length <= 15;
        }
        
        phoneInput.addEventListener('input', function() {
            const value = this.value;
            if (value && !validatePhoneNumber(value)) {
                phoneError.textContent = '❌ Буквы запрещены! Используйте только цифры, +, пробелы и дефисы.';
                phoneError.style.display = 'block';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                phoneError.style.display = 'none';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        // Загрузка аватара
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('avatar', file);
            document.getElementById('uploadProgress').style.display = 'block';
            fetch('upload_avatar.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('uploadProgress').style.display = 'none';
                    if (data.success) document.getElementById('avatar-img').src = data.avatar_url + '?t=' + new Date().getTime();
                    else alert(data.error);
                });
        });
    </script>
</body>
</html>