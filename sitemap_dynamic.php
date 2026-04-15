<?php
// sitemap_dynamic.php - запускать для обновления sitemap.xml
require_once 'config/db.php';

// Получаем все камни
$stones = $pdo->query("SELECT id, updated_at FROM stones WHERE is_active = 1 ORDER BY id")->fetchAll();

// Формируем XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Главная страница -->
    <url>
        <loc>https://stoneprocessing.ru/</loc>
        <lastmod>' . date('Y-m-d') . '</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Каталог -->
    <url>
        <loc>https://stoneprocessing.ru/store/index.php</loc>
        <lastmod>' . date('Y-m-d') . '</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>';

// Добавляем категории
$categories = $pdo->query("SELECT id, name FROM stone_categories")->fetchAll();
foreach ($categories as $cat) {
    $xml .= '
    <url>
        <loc>https://stoneprocessing.ru/store/index.php?category=' . $cat['id'] . '</loc>
        <lastmod>' . date('Y-m-d') . '</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>';
}

// Добавляем камни
foreach ($stones as $stone) {
    $lastmod = $stone['updated_at'] ? date('Y-m-d', strtotime($stone['updated_at'])) : date('Y-m-d');
    $xml .= '
    <url>
        <loc>https://stoneprocessing.ru/store/stone.php?id=' . $stone['id'] . '</loc>
        <lastmod>' . $lastmod . '</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>';
}

$xml .= '
</urlset>';

// Сохраняем файл
file_put_contents('sitemap.xml', $xml);
echo "Sitemap.xml успешно обновлён! Добавлено " . count($stones) . " камней и " . count($categories) . " категорий.";
?>