<?php
require_once 'config/db.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] == 'admin') header('Location: admin/index.php');
    else header('Location: store/index.php');
    exit();
}

$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$isFirstUser = ($userCount == 0);
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $error = null;
    
    // Валидация телефона
    if (!empty($phone) && !validatePhone($phone)) {
        $error = "Некорректный номер телефона! Разрешены только цифры, +, пробелы и дефисы. Буквы запрещены!";
    }
    
    // Проверка уникальности email
    if (!$error && !empty($email) && !isEmailUnique($pdo, $email)) {
        $error = "Этот email уже зарегистрирован!";
    }
    
    if (!$error) {
        try {
            $pdo->beginTransaction();
            $role = $isFirstUser ? 'admin' : 'user';
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);
            $user_id = $pdo->lastInsertId();
            
            $formatted_phone = formatPhone($phone);
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $email, $formatted_phone]);
            $pdo->commit();
            
            $success = "Регистрация успешна! " . ($role == 'admin' ? "Вы первый пользователь - вы администратор!" : "");
        } catch(PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                if (strpos($e->getMessage(), 'username') !== false) {
                    $error = "Этот логин уже занят!";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $error = "Этот email уже зарегистрирован!";
                } else {
                    $error = "Логин или email уже существует!";
                }
            } else {
                $error = "Ошибка регистрации: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh}
        .register-card{max-width:550px;margin:50px auto}
        .error-text{font-size:0.8rem;margin-top:5px}
        .is-invalid{border-color:#dc3545}
        .is-valid{border-color:#28a745}
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow register-card">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-gem"></i> StoneProcessing</h3>
                <p>Регистрация</p>
            </div>
            <div class="card-body">
                <?php if($success): ?>
                    <div class="alert alert-success"><?= $success ?> <a href="login.php">Войти</a></div>
                <?php else: ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if($isFirstUser): ?>
                        <div class="alert alert-info">⭐ Вы первый пользователь! Вам будут выданы права АДМИНИСТРАТОРА.</div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Логин *</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Пароль *</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Имя *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                                <div id="emailError" class="error-text text-danger" style="display:none"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="tel" name="phone" id="phone" class="form-control" placeholder="+7 (999) 123-45-67">
                                <div id="phoneError" class="error-text text-danger" style="display:none"></div>
                                <small class="text-muted">Только цифры, +, пробелы и дефисы. Буквы запрещены!</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    </form>
                    <hr>
                    <div class="text-center"><a href="login.php">Уже есть аккаунт? Войти</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Проверка уникальности email
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        let emailTimeout;
        
        function checkEmailUnique(email) {
            if (!email) return;
            
            fetch('check_email.php?email=' + encodeURIComponent(email))
                .then(response => response.json())
                .then(data => {
                    if (!data.unique) {
                        emailError.textContent = '❌ Этот email уже зарегистрирован!';
                        emailError.style.display = 'block';
                        emailInput.classList.add('is-invalid');
                        emailInput.classList.remove('is-valid');
                    } else {
                        emailError.style.display = 'none';
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                    }
                })
                .catch(err => console.error(err));
        }
        
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            emailTimeout = setTimeout(() => {
                checkEmailUnique(this.value);
            }, 500);
        });
        
        // Валидация телефона (без букв)
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phoneError');
        
        function validatePhoneNumber(phone) {
            if (!phone) return true;
            // Проверка на наличие букв (латиница и кириллица)
            if (/[a-zA-Zа-яА-ЯёЁ]/.test(phone)) {
                return false;
            }
            // Разрешенные символы: цифры, +, -, пробел, (, )
            if (!/^[0-9+\-\s\(\)]*$/.test(phone)) {
                return false;
            }
            const digits = phone.replace(/[^0-9]/g, '');
            return digits.length >= 10 && digits.length <= 15;
        }
        
        phoneInput.addEventListener('input', function() {
            const value = this.value;
            if (value && !validatePhoneNumber(value)) {
                phoneError.textContent = '❌ Некорректный номер! Буквы запрещены. Используйте только цифры, +, пробелы и дефисы.';
                phoneError.style.display = 'block';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                phoneError.style.display = 'none';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    </script>
</body>
</html>