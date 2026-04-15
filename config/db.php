<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'stone_processing';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserProfile($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        return [
            'first_name' => '', 'last_name' => '', 'middle_name' => '',
            'email' => '', 'phone' => '', 'address' => '',
            'birth_date' => '1990-01-01', 'avatar' => 'default_avatar.png'
        ];
    }
    return $profile;
}

// Функция для валидации телефона (только цифры и +, -, пробелы, скобки - НЕТ БУКВ)
function validatePhone($phone) {
    if (empty($phone)) return true; // Пустое поле допустимо
    
    // Проверяем, есть ли буквы
    if (preg_match('/[a-zA-Zа-яА-Я]/u', $phone)) {
        return false;
    }
    
    // Разрешенные символы: цифры, +, -, пробел, (, )
    if (!preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
        return false;
    }
    
    // Подсчитываем количество цифр
    $digits = preg_replace('/[^0-9]/', '', $phone);
    $digitCount = strlen($digits);
    
    // Проверяем, что цифр от 10 до 15
    return $digitCount >= 10 && $digitCount <= 15;
}

// Функция для проверки уникальности email
function isEmailUnique($pdo, $email, $exclude_user_id = null) {
    if (empty($email)) return true;
    
    if ($exclude_user_id) {
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $exclude_user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE email = ?");
        $stmt->execute([$email]);
    }
    return $stmt->rowCount() == 0;
}

// Функция для форматирования телефона
function formatPhone($phone) {
    if (empty($phone)) return '';
    // Удаляем все нецифровые символы
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    // Форматируем как +7 (XXX) XXX-XX-XX для российских номеров
    if (strlen($digits) == 11 && substr($digits, 0, 1) == '7') {
        return '+' . substr($digits, 0, 1) . ' (' . substr($digits, 1, 3) . ') ' . 
               substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9, 2);
    }
    
    return $phone;
}

function getStoneWithServices($pdo, $stone_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, sc.name as category_name, sc.hardness, sc.density
        FROM stones s
        JOIN stone_categories sc ON s.stone_category_id = sc.id
        WHERE s.id = ? AND s.is_active = 1
    ");
    $stmt->execute([$stone_id]);
    $stone = $stmt->fetch();
    
    if ($stone) {
        $services_stmt = $pdo->prepare("
            SELECT sv.id, sv.name, sv.description, sv.price_type,
                   COALESCE(ss.custom_price, sv.price_value) as price_value, sv.unit
            FROM services sv
            JOIN stone_services ss ON sv.id = ss.service_id
            WHERE ss.stone_id = ? AND ss.is_available = 1 AND sv.is_active = 1
            ORDER BY sv.sort_order
        ");
        $services_stmt->execute([$stone_id]);
        $stone['available_services'] = $services_stmt->fetchAll();
    }
    return $stone;
}

function generateOrderNumber() {
    return 'SP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Функция для загрузки файлов
function uploadFile($file, $target_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $max_size = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Файл не загружен'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Разрешены только изображения (JPG, PNG, GIF, WEBP)'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Файл слишком большой. Максимум ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $filepath = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Ошибка при сохранении файла'];
    }
}
?>