<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Обработка действий с пользователями
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Добавление пользователя
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = md5(trim($_POST['password']));
        $role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $role]);
            
            // Создаём профиль пользователя
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, email) VALUES (?, ?)");
            $stmt->execute([$user_id, $email]);
            
            $success = "Пользователь добавлен!";
        } catch(PDOException $e) {
            $error = "Ошибка: такой логин или email уже существует";
        }
    }
    
    // Редактирование роли
    if (isset($_POST['edit_role'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['role'], $_POST['user_id']]);
        $success = "Роль пользователя обновлена!";
    }
    
    // Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Проверяем, не удаляем ли сами себя
        if ($user_id == $_SESSION['user_id']) {
            $error = "Нельзя удалить самого себя!";
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $success = "Пользователь удалён!";
        }
    }
}

// Получаем список пользователей с количеством заказов
$users = $pdo->query("
    SELECT 
        u.id, 
        u.username, 
        u.email, 
        u.role, 
        u.created_at,
        COUNT(o.id) as orders_count,
        up.phone,
        up.first_name,
        up.last_name
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - StoneProcessing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
        .status-admin { background: #dc3545; color: white; }
        .status-user { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Боковое меню -->
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
                    <a class="nav-link" href="messages.php"><i class="fas fa-envelope"></i> Сообщения</a>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </nav>
            </div>
            
            <!-- Основной контент -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> Управление пользователями</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus"></i> Добавить пользователя
                    </button>
                </div>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Список пользователей</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Пользователь</th>
                                        <th>Email</th>
                                        <th>Роль</th>
                                        <th>Заказов</th>
                                        <th>Дата регистрации</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role" class="form-select form-select-sm" style="width: 100px;" onchange="this.form.submit()">
                                                    <option value="user" <?php if($user['role'] == 'user') echo 'selected'; ?>>Пользователь</option>
                                                    <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>>Администратор</option>
                                                </select>
                                                <input type="hidden" name="edit_role" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $user['orders_count'] ?? 0 ?> заказов</span>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($user['created_at'] ?? 'now')) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" <?php if($user['id'] == $_SESSION['user_id']) echo 'disabled'; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal добавления пользователя -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Добавить пользователя</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Логин *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                            <select name="role" class="form-select">
                                <option value="user">Пользователь</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>