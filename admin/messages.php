<?php
require_once '../config/db.php';
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Функция сохранения ответа (без отправки email на локалке)
function saveReply($pdo, $message_id, $admin_reply, $user_email) {
    $stmt = $pdo->prepare("UPDATE messages SET admin_reply = ?, status = 'replied', replied_at = NOW() WHERE id = ?");
    return $stmt->execute([$admin_reply, $message_id]);
}

// Обработка ответа на сообщение
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_message'])) {
    $message_id = $_POST['message_id'];
    $admin_reply = trim($_POST['admin_reply']);
    
    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT m.*, u.email as user_email FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = ?");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();
    
    if ($msg && !empty($admin_reply)) {
        // Сохраняем ответ в базе данных
        if (saveReply($pdo, $message_id, $admin_reply, $msg['email'])) {
            $success = "Ответ сохранён! Пользователь увидит его в личном кабинете.";
        } else {
            $error = "Не удалось сохранить ответ";
        }
    } else {
        $error = "Не удалось отправить ответ";
    }
}

// Отметить как прочитанное
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?")->execute([$_GET['mark_read']]);
    header('Location: messages.php');
    exit();
}

// Удаление сообщения
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([$_GET['delete']]);
    $success = "Сообщение удалено!";
}

// Получаем список сообщений
$messages = $pdo->query("
    SELECT m.*, u.username 
    FROM messages m
    LEFT JOIN users u ON m.user_id = u.id
    ORDER BY 
        CASE WHEN m.status = 'new' THEN 0 ELSE 1 END,
        m.created_at DESC
")->fetchAll();

// Статистика по сообщениям
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'new' => $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn(),
    'read' => $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'read'")->fetchColumn(),
    'replied' => $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'replied'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения - StoneProcessing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .message-card { transition: all 0.3s; cursor: pointer; }
        .message-card:hover { transform: translateX(5px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .message-new { border-left: 4px solid #dc3545; background: #fff5f5; }
        .message-read { border-left: 4px solid #28a745; }
        .message-replied { border-left: 4px solid #17a2b8; opacity: 0.8; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 500; }
        .status-new { background: #dc3545; color: white; }
        .status-read { background: #28a745; color: white; }
        .status-replied { background: #17a2b8; color: white; }
        .stat-card { transition: transform 0.3s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-3px); }
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
                    <a class="nav-link active" href="messages.php"><i class="fas fa-envelope"></i> Сообщения</a>
                    <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </nav>
            </div>
            
            <!-- Основной контент -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-envelope"></i> Сообщения пользователей</h2>
                    <a href="messages.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt"></i> Обновить
                    </a>
                </div>
                
                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-secondary">
                            <div class="card-body">
                                <h6 class="card-title">Всего сообщений</h6>
                                <h3 class="mb-0"><?= $stats['total'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-danger">
                            <div class="card-body">
                                <h6 class="card-title">Новые</h6>
                                <h3 class="mb-0"><?= $stats['new'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-success">
                            <div class="card-body">
                                <h6 class="card-title">Прочитанные</h6>
                                <h3 class="mb-0"><?= $stats['read'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-info">
                            <div class="card-body">
                                <h6 class="card-title">Ответы отправлены</h6>
                                <h3 class="mb-0"><?= $stats['replied'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <!-- Список сообщений -->
                <?php if(empty($messages)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h5>Нет сообщений</h5>
                        <p>Сообщения от пользователей будут отображаться здесь.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $msg): ?>
                    <div class="card mb-3 message-card message-<?= $msg['status'] ?>">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <strong><i class="fas fa-user"></i> <?= htmlspecialchars($msg['name']) ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-envelope"></i> <?= htmlspecialchars($msg['email']) ?></small>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-tag"></i> <?= htmlspecialchars($msg['subject']) ?></strong><br>
                                    <small class="text-muted"><?= mb_substr(htmlspecialchars($msg['message']), 0, 60) ?>...</small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="status-badge status-<?= $msg['status'] ?>">
                                        <?php if($msg['status'] == 'new'): ?>🆕 Новое
                                        <?php elseif($msg['status'] == 'read'): ?>✓ Прочитано
                                        <?php else: ?>✉ Ответ отправлен<?php endif; ?>
                                    </span>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $msg['id'] ?>">
                                            <i class="fas fa-eye"></i> Просмотр
                                        </button>
                                        <a href="?delete=<?= $msg['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить сообщение?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Модальное окно просмотра сообщения -->
                    <div class="modal fade" id="viewModal<?= $msg['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="fas fa-envelope"></i> Сообщение от <?= htmlspecialchars($msg['name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Отправитель:</strong> <?= htmlspecialchars($msg['name']) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Email:</strong> <?= htmlspecialchars($msg['email']) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Дата:</strong> <?= date('d.m.Y H:i:s', strtotime($msg['created_at'])) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Тема:</strong> <?= htmlspecialchars($msg['subject']) ?>
                                        </div>
                                    </div>
                                    <div class="alert alert-light">
                                        <strong>Сообщение:</strong>
                                        <p class="mt-2"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                    </div>
                                    
                                    <?php if($msg['admin_reply']): ?>
                                    <div class="alert alert-info">
                                        <strong><i class="fas fa-reply"></i> Ваш ответ:</strong>
                                        <p class="mt-2"><?= nl2br(htmlspecialchars($msg['admin_reply'])) ?></p>
                                        <small class="text-muted">Отправлено: <?= date('d.m.Y H:i', strtotime($msg['replied_at'])) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label"><i class="fas fa-reply"></i> Ответить пользователю</label>
                                            <textarea name="admin_reply" class="form-control" rows="4" placeholder="Введите ответ..."></textarea>
                                            <small class="text-muted">Ответ будет сохранён и отобразится в личном кабинете пользователя</small>
                                        </div>
                                        <button type="submit" name="reply_message" class="btn btn-success">
                                            <i class="fas fa-paper-plane"></i> Отправить ответ
                                        </button>
                                        <a href="?mark_read=<?= $msg['id'] ?>" class="btn btn-secondary">
                                            <i class="fas fa-check"></i> Отметить как прочитанное
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>