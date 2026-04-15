-- ============================================
-- StoneProcessing Database Schema
-- Основной продукт - КАМЕНЬ с физическими параметрами
-- Обработка/гравировка - дополнительные услуги
-- ============================================

DROP DATABASE IF EXISTS stone_processing;
CREATE DATABASE stone_processing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stone_processing;

-- ============================================
-- 1. Таблица пользователей
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(32) NOT NULL, -- MD5 для простоты
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. Таблица профилей пользователей
-- ============================================
CREATE TABLE user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    middle_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    birth_date DATE,
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 3. Таблица категорий камней (типы камня)
-- ============================================
CREATE TABLE stone_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL, -- Мрамор, Гранит, Оникс, Травертин и т.д.
    description TEXT,
    hardness DECIMAL(3,1) DEFAULT 0, -- Твердость по Моосу
    density DECIMAL(5,2) DEFAULT 0, -- Плотность кг/м³
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 4. ГЛАВНАЯ ТАБЛИЦА: КАМНИ (основной товар)
-- ============================================
CREATE TABLE stones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL, -- Название камня
    stone_category_id INT NOT NULL, -- Тип камня (мрамор/гранит)
    
    -- Физические параметры
    length_cm DECIMAL(10,2) DEFAULT 0, -- Длина (см)
    width_cm DECIMAL(10,2) DEFAULT 0,  -- Ширина (см)
    height_cm DECIMAL(10,2) DEFAULT 0, -- Высота/толщина (см)
    weight_kg DECIMAL(10,2) DEFAULT 0, -- Вес (кг)
    volume_m3 DECIMAL(10,4) DEFAULT 0, -- Объём (м³)
    
    -- Дополнительные параметры
    color VARCHAR(50), -- Цвет
    origin VARCHAR(100), -- Месторождение
    texture VARCHAR(100), -- Текстура
    
    -- Цена за единицу
    price_per_sqm DECIMAL(12,2) DEFAULT 0, -- Цена за м² (если плита)
    price_per_unit DECIMAL(12,2) DEFAULT 0, -- Цена за штуку
    price_per_kg DECIMAL(12,2) DEFAULT 0,  -- Цена за кг
    
    -- Изображения
    main_image VARCHAR(255) DEFAULT 'default_stone.png',
    gallery_images TEXT, -- JSON с путями к изображениям
    
    -- Описание
    short_description TEXT,
    full_description LONGTEXT,
    
    -- Характеристики (JSON для гибкости)
    specifications JSON,
    
    -- Склад
    quantity INT DEFAULT 0,
    min_order_quantity INT DEFAULT 1,
    
    -- Статус
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stone_category_id) REFERENCES stone_categories(id),
    INDEX idx_category (stone_category_id),
    INDEX idx_price (price_per_sqm),
    INDEX idx_weight (weight_kg)
);

-- ============================================
-- 5. Таблица ДОПОЛНИТЕЛЬНЫХ УСЛУГ (обработка, гравировка, полировка)
-- ============================================
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL, -- Например: "Гравировка", "Полировка", "Шлифовка"
    description TEXT,
    price_type ENUM('fixed', 'per_sqm', 'per_kg', 'percentage') DEFAULT 'fixed',
    price_value DECIMAL(12,2) NOT NULL, -- Цена или процент
    unit VARCHAR(50), -- Единица измерения (см², кг, шт)
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 6. Связь камней с услугами (какие услуги доступны для камня)
-- ============================================
CREATE TABLE stone_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stone_id INT NOT NULL,
    service_id INT NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    custom_price DECIMAL(12,2) NULL, -- Индивидуальная цена для этого камня
    FOREIGN KEY (stone_id) REFERENCES stones(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stone_service (stone_id, service_id)
);

-- ============================================
-- 7. Корзина
-- ============================================
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    stone_id INT NOT NULL,
    quantity INT DEFAULT 1,
    
    -- Параметры для услуг (выбранные услуги для этого камня)
    selected_services JSON, -- [{service_id: 1, quantity: 1, price: 500}]
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (stone_id) REFERENCES stones(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ============================================
-- 8. Заказы
-- ============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    
    -- Информация о заказе
    stone_id INT NOT NULL,
    stone_name VARCHAR(200),
    stone_price DECIMAL(12,2), -- Цена камня на момент заказа
    quantity INT NOT NULL,
    
    -- Выбранные услуги (JSON)
    selected_services JSON, -- Полная информация об услугах на момент заказа
    
    -- Финансы
    stone_total DECIMAL(12,2), -- Стоимость камней
    services_total DECIMAL(12,2) DEFAULT 0, -- Стоимость услуг
    total_price DECIMAL(12,2), -- Итоговая сумма
    
    -- Информация о доставке
    delivery_address TEXT,
    delivery_notes TEXT,
    
    -- Статус заказа
    status ENUM('new', 'processing', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'new',
    
    -- Покупатель
    customer_name VARCHAR(150),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (stone_id) REFERENCES stones(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);

-- ============================================
-- 9. История цен (для аналитики)
-- ============================================
CREATE TABLE price_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stone_id INT NOT NULL,
    old_price DECIMAL(12,2),
    new_price DECIMAL(12,2),
    changed_by INT,
    change_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stone_id) REFERENCES stones(id) ON DELETE CASCADE
);

-- ============================================
-- 10. Отзывы
-- ============================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stone_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stone_id) REFERENCES stones(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_stone (stone_id),
    INDEX idx_approved (is_approved)
);

-- ============================================
-- НАЧАЛЬНЫЕ ДАННЫЕ
-- ============================================

-- Категории камней
INSERT INTO stone_categories (name, description, hardness, density) VALUES
('Мрамор', 'Натуральный камень с красивой текстурой, используется для интерьеров', 3.5, 2700),
('Гранит', 'Очень прочный камень, устойчив к истиранию', 7.0, 2750),
('Оникс', 'Полудрагоценный камень с полупрозрачной структурой', 4.5, 2600),
('Травертин', 'Известковый туф, пористый камень', 4.0, 2400),
('Сланец', 'Слоистый камень, используется для облицовки', 5.0, 2800),
('Кварцит', 'Очень твердый метаморфический камень', 7.5, 2650);

-- Дополнительные услуги
INSERT INTO services (name, description, price_type, price_value, unit, sort_order) VALUES
('Гравировка', 'Гравировка текста или изображения на поверхности камня', 'per_sqm', 1500.00, 'м²', 1),
('Полировка', 'Полировка поверхности до глянцевого блеска', 'per_sqm', 800.00, 'м²', 2),
('Шлифовка', 'Матовая шлифовка поверхности', 'per_sqm', 500.00, 'м²', 3),
('Термообработка', 'Обработка камня для повышения прочности', 'percentage', 15.00, '% от цены камня', 4),
('Водоотталкивающая пропитка', 'Защита от влаги и пятен', 'per_sqm', 400.00, 'м²', 5),
('Фацет', 'Снятие фаски с края плиты', 'per_meter', 300.00, 'пог.м', 6),
('Антискользящее покрытие', 'Нанесение текстуры против скольжения', 'per_sqm', 600.00, 'м²', 7);

-- Примеры камней
INSERT INTO stones (name, stone_category_id, length_cm, width_cm, height_cm, weight_kg, volume_m3, color, origin, texture, price_per_sqm, price_per_unit, price_per_kg, quantity, short_description, full_description) VALUES
('Белый Каррарский мрамор', 1, 200, 100, 2, 108, 0.04, 'Белый с серыми прожилками', 'Италия, Каррара', 'Кристаллический', 12500.00, 25000.00, 231.48, 50, 'Элитный итальянский мрамор', 'Классический белый мрамор из Каррары. Идеален для столешниц, полов и декоративных элементов. Имеет характерные серые прожилки.'),
('Черный Абсолют гранит', 2, 250, 150, 3, 309, 0.1125, 'Черный', 'Индия', 'Зернистый', 8500.00, 31875.00, 275.00, 30, 'Глубокий черный гранит', 'Однородный черный гранит с минимальными вкраплениями. Отличная прочность и износостойкость.'),
('Золотой Оникс', 3, 180, 90, 2, 84, 0.0324, 'Золотисто-коричневый', 'Иран', 'Полосчатый', 35000.00, 56700.00, 675.00, 10, 'Роскошный полупрозрачный оникс', 'Уникальный камень с золотистыми и коричневыми полосами. Пропускает свет, создавая волшебное свечение.'),
('Серый сланец', 5, 100, 100, 1.5, 42, 0.015, 'Серый', 'Бразилия', 'Слоистый', 3200.00, 3200.00, 76.19, 100, 'Натуральный сланец для фасадов', 'Экологичный камень для облицовки фасадов и внутренних стен. Морозостойкий.'),
('Розовый кварцит', 6, 220, 110, 2.5, 160, 0.0605, 'Розовый', 'Бразилия', 'Зернистый', 18900.00, 45738.00, 285.00, 20, 'Роскошный розовый кварцит', 'Очень твердый и красивый камень розового цвета. Идеален для кухонных столешниц.');

-- Связи камней с услугами
INSERT INTO stone_services (stone_id, service_id, is_available) VALUES
(1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 5, 1), (1, 6, 1), -- Мрамор - все услуги
(2, 1, 1), (2, 2, 1), (2, 3, 1), (2, 4, 0), (2, 5, 1), (2, 6, 1), -- Гранит - почти всё
(3, 1, 1), (3, 2, 1), (3, 3, 0), (3, 5, 1), (3, 6, 1), -- Оникс - без шлифовки
(4, 1, 0), (4, 2, 1), (4, 3, 1), (4, 5, 1), -- Сланец - без гравировки
(5, 1, 1), (5, 2, 1), (5, 3, 1), (5, 4, 1), (5, 5, 1), (5, 6, 1); -- Кварцит - все услуги

-- Создание тестового пользователя (admin / admin123)
INSERT INTO users (username, password, role) VALUES 
('admin', MD5('admin123'), 'admin'),
('user', MD5('user123'), 'user');

-- Профили пользователей
INSERT INTO user_profiles (user_id, first_name, last_name, email, phone) VALUES
(1, 'Администратор', 'Системы', 'admin@stoneprocessing.ru', '+7 (999) 123-45-67'),
(2, 'Иван', 'Петров', 'ivan@example.com', '+7 (999) 888-77-66');

-- ============================================
-- ПОЛЕЗНЫЕ ЗАПРОСЫ
-- ============================================

-- Получение камня с доступными услугами
/*
SELECT 
    s.*,
    sc.name as category_name,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'service_id', sv.id,
            'service_name', sv.name,
            'price', COALESCE(ss.custom_price, sv.price_value),
            'price_type', sv.price_type,
            'unit', sv.unit
        )
    ) as available_services
FROM stones s
JOIN stone_categories sc ON s.stone_category_id = sc.id
LEFT JOIN stone_services ss ON s.id = ss.stone_id AND ss.is_available = 1
LEFT JOIN services sv ON ss.service_id = sv.id AND sv.is_active = 1
WHERE s.id = 1
GROUP BY s.id;
*/

-- Расчет итоговой цены с услугами
/*
SELECT 
    s.id,
    s.name,
    s.price_per_sqm as base_price,
    -- Пример: добавляем услугу гравировки
    s.price_per_sqm + 1500 as price_with_engraving,
    -- Пример: добавляем услугу полировки
    s.price_per_sqm + 800 as price_with_polishing,
    -- Все услуги
    s.price_per_sqm + 1500 + 800 + 400 as price_with_all_services
FROM stones s
WHERE s.id = 1;
*/