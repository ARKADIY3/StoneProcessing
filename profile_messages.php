<?php
require_once 'config/db.php';

include 'includes/menu.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$profile = getUserProfile($pdo, $user_id);

// Получаем сообщения пользователя
$messages = $pdo->prepare("
    SELECT * FROM messages 
    WHERE user_id = ? OR email = ?
    ORDER BY created_at DESC
");
$messages->execute([$user_id, $profile['email']]);
$messages = $messages->fetchAll();

// Количество товаров в корзине
$cart_count = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$cart_count->execute([$user_id]);
$cart_count = $cart_count->fetch()['total'] ?? 0;

// Непрочитанные сообщения
$unread_count = $pdo->prepare("
    SELECT COUNT(*) FROM messages 
    WHERE (user_id = ? OR email = ?) AND status = 'replied' AND is_read_by_user = 0
");
$unread_count->execute([$user_id, $profile['email']]);
$unread_count = $unread_count->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои сообщения - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-card { transition: all 0.3s; margin-bottom: 15px; }
        .message-new { border-left: 4px solid #dc3545; background: #fff5f5; }
        .message-replied { border-left: 4px solid #28a745; }
        .message-read { border-left: 4px solid #6c757d; }
        .reply-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px; }
        .unread-badge { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-envelope"></i> Мои сообщения</h2>
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Новое сообщение
                    </a>
                </div>
                
                <?php if(empty($messages)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h4>У вас нет сообщений</h4>
                        <p>Вы можете задать вопрос через <a href="contact.php">форму обратной связи</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $msg): 
                        $isUnread = ($msg['status'] == 'replied' && (!$msg['is_read_by_user']));
                    ?>
                    <div class="card message-card <?= $isUnread ? 'message-new' : ($msg['status'] == 'replied' ? 'message-replied' : 'message-read') ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($msg['subject']) ?>
                                        <?php if($isUnread): ?>
                                            <span class="badge bg-danger ms-2">Новый ответ</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                    </small>
                                    <br>
                                    <?php if($msg['status'] == 'new'): ?>
                                        <span class="badge bg-warning mt-2">Ожидает ответа</span>
                                    <?php elseif($msg['status'] == 'read'): ?>
                                        <span class="badge bg-secondary mt-2">Прочитано</span>
                                    <?php else: ?>
                                        <span class="badge bg-success mt-2">Ответ получен</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if($msg['admin_reply']): ?>
                            <div class="reply-box mt-3">
                                <strong><i class="fas fa-reply"></i> Ответ администратора:</strong>
                                <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($msg['admin_reply'])) ?></p>
                                <small class="text-muted">Получено: <?= date('d.m.Y H:i', strtotime($msg['replied_at'])) ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>