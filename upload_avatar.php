<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    
    // Загружаем файл
    $upload_dir = 'uploads/avatars/';
    $result = uploadFile($_FILES['avatar'], $upload_dir);
    
    if ($result['success']) {
        // Получаем старый аватар
        $stmt = $pdo->prepare("SELECT avatar FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $old_avatar = $stmt->fetchColumn();
        
        // Удаляем старый аватар (если не дефолтный)
        if ($old_avatar && $old_avatar != 'default_avatar.png' && file_exists($upload_dir . $old_avatar)) {
            unlink($upload_dir . $old_avatar);
        }
        
        // Обновляем в БД
        $stmt = $pdo->prepare("UPDATE user_profiles SET avatar = ? WHERE user_id = ?");
        $stmt->execute([$result['filename'], $user_id]);
        
        echo json_encode([
            'success' => true,
            'avatar_url' => $upload_dir . $result['filename'],
            'message' => 'Аватар успешно обновлён'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Файл не получен']);
}
?>