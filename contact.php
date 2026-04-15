<?php
require_once 'config/db.php';

include 'includes/menu.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Валидация
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email адрес';
    } else {
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'new')");
        $stmt->execute([$user_id, $name, $email, $subject, $message]);
        
        $success = 'Ваше сообщение отправлено! Мы свяжемся с вами в ближайшее время.';
        
        // Очистка формы
        $name = $email = $subject = $message = '';
    }
}

$profile = isLoggedIn() ? getUserProfile($pdo, $_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-envelope"></i> Свяжитесь с нами</h4>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ваше имя *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Тема *</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Выберите тему...</option>
                                    <option value="Вопрос о камне">Вопрос о камне</option>
                                    <option value="Услуги обработки">Услуги обработки</option>
                                    <option value="Доставка и оплата">Доставка и оплата</option>
                                    <option value="Сотрудничество">Сотрудничество</option>
                                    <option value="Другое">Другое</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Сообщение *</label>
                                <textarea name="message" class="form-control" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Отправить сообщение
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>