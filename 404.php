<?php
http_response_code(404);
// Не подключаем БД, чтобы избежать ошибок
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-404 {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            margin: 40px 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .error-404 h1 {
            font-size: 120px;
            font-weight: bold;
            margin: 0;
            color: #667eea;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.1);
        }
        .error-404 h2 {
            font-size: 32px;
            margin: 20px 0;
            color: #333;
        }
        .error-404 p {
            color: #666;
            font-size: 18px;
        }
        .search-box-404 {
            max-width: 500px;
            margin: 30px auto;
        }
        .suggestion-card {
            transition: transform 0.3s;
            height: 100%;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .suggestion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .btn-custom {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-404">
            <i class="fas fa-gem fa-4x mb-3" style="color: #667eea;"></i>
            <h1>404</h1>
            <h2><i class="fas fa-exclamation-triangle"></i> Страница не найдена</h2>
            <p>К сожалению, запрашиваемая страница не существует или была перемещена.</p>
            <p>Возможно, вы ищете один из наших камней или услуг обработки?</p>
        
            
            <!-- Кнопки действий -->
            <div class="mt-4">
                <a href="../store/index.php" class="btn btn-custom">
                    <i class="fas fa-store"></i> Перейти в магазин
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

   