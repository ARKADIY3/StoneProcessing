-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Апр 15 2026 г., 18:56
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `stone_processing`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stone_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `selected_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_services`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read_by_user` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `admin_reply`, `replied_at`, `created_at`, `is_read_by_user`) VALUES
(1, 1, 'Администратор', 'admin@stoneprocessing.ru', 'Вопрос о камне', 'qwerqwer', 'replied', 'dsd', '2026-04-15 15:55:51', '2026-04-15 15:31:52', 0),
(2, 2, 'Аркадий', 'nagabedanarkadij2@gmail.com', 'Доставка и оплата', 'привет', 'replied', 'hello', '2026-04-15 15:57:11', '2026-04-15 15:56:41', 0),
(3, 2, 'Аркадий', 'nagabedanarkadij2@gmail.com', 'Вопрос о камне', 'привет 1', 'replied', 'hello 1', '2026-04-15 16:10:21', '2026-04-15 16:09:41', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stone_id` int(11) NOT NULL,
  `stone_name` varchar(200) DEFAULT NULL,
  `stone_price` decimal(12,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `selected_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_services`)),
  `stone_total` decimal(12,2) DEFAULT NULL,
  `services_total` decimal(12,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `status` enum('new','processing','paid','shipped','completed','cancelled') DEFAULT 'new',
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `stone_id`, `stone_name`, `stone_price`, `quantity`, `selected_services`, `stone_total`, `services_total`, `total_price`, `delivery_address`, `delivery_notes`, `status`, `customer_name`, `customer_email`, `customer_phone`, `created_at`, `updated_at`) VALUES
(1, 'SP-20260414-74F76E', 2, 2, 'Черный Абсолют гранит', 31875.00, 1, NULL, 31875.00, 0.00, 31875.00, NULL, NULL, 'new', 'Иван Петров', 'ivan@example.com', '+7 (999) 888-77-66', '2026-04-14 11:05:11', '2026-04-14 11:05:11'),
(2, 'SP-20260414-A08264', 2, 4, 'Серый сланец', 3200.00, 1, NULL, 3200.00, 0.00, 3200.00, NULL, NULL, 'new', 'Иван Петров', 'ivan@example.com', '+7 (999) 888-77-66', '2026-04-14 11:07:06', '2026-04-14 11:07:06');

-- --------------------------------------------------------

--
-- Структура таблицы `price_history`
--

CREATE TABLE `price_history` (
  `id` int(11) NOT NULL,
  `stone_id` int(11) NOT NULL,
  `old_price` decimal(12,2) DEFAULT NULL,
  `new_price` decimal(12,2) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `stone_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price_type` enum('fixed','per_sqm','per_kg','percentage') DEFAULT 'fixed',
  `price_value` decimal(12,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price_type`, `price_value`, `unit`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Гравировка', 'Гравировка текста или изображения на поверхности камня', 'per_sqm', 1500.00, 'м²', 1, 1, '2026-04-14 10:39:30'),
(2, 'Полировка', 'Полировка поверхности до глянцевого блеска', 'per_sqm', 800.00, 'м²', 1, 2, '2026-04-14 10:39:30'),
(3, 'Шлифовка', 'Матовая шлифовка поверхности', 'per_sqm', 500.00, 'м²', 1, 3, '2026-04-14 10:39:30'),
(4, 'Термообработка', 'Обработка камня для повышения прочности', 'percentage', 15.00, '% от цены камня', 1, 4, '2026-04-14 10:39:30'),
(5, 'Водоотталкивающая пропитка', 'Защита от влаги и пятен', 'per_sqm', 400.00, 'м²', 1, 5, '2026-04-14 10:39:30'),
(6, 'Фацет', 'Снятие фаски с края плиты', '', 300.00, 'пог.м', 1, 6, '2026-04-14 10:39:30'),
(7, 'Антискользящее покрытие', 'Нанесение текстуры против скольжения', 'per_sqm', 600.00, 'м²', 1, 7, '2026-04-14 10:39:30');

-- --------------------------------------------------------

--
-- Структура таблицы `stones`
--

CREATE TABLE `stones` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `stone_category_id` int(11) NOT NULL,
  `length_cm` decimal(10,2) DEFAULT 0.00,
  `width_cm` decimal(10,2) DEFAULT 0.00,
  `height_cm` decimal(10,2) DEFAULT 0.00,
  `weight_kg` decimal(10,2) DEFAULT 0.00,
  `volume_m3` decimal(10,4) DEFAULT 0.0000,
  `color` varchar(50) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `texture` varchar(100) DEFAULT NULL,
  `price_per_sqm` decimal(12,2) DEFAULT 0.00,
  `price_per_unit` decimal(12,2) DEFAULT 0.00,
  `price_per_kg` decimal(12,2) DEFAULT 0.00,
  `main_image` varchar(255) DEFAULT 'default_stone.png',
  `gallery_images` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `full_description` longtext DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `quantity` int(11) DEFAULT 0,
  `min_order_quantity` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `stones`
--

INSERT INTO `stones` (`id`, `name`, `stone_category_id`, `length_cm`, `width_cm`, `height_cm`, `weight_kg`, `volume_m3`, `color`, `origin`, `texture`, `price_per_sqm`, `price_per_unit`, `price_per_kg`, `main_image`, `gallery_images`, `short_description`, `full_description`, `specifications`, `quantity`, `min_order_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Белый Каррарский мрамор', 1, 200.00, 100.00, 2.00, 108.00, 0.0400, 'Белый с серыми прожилками', 'Италия, Каррара', 'Кристаллический', 12500.00, 25000.00, 231.48, 'default_stone.png', NULL, 'Элитный итальянский мрамор', 'Классический белый мрамор из Каррары. Идеален для столешниц, полов и декоративных элементов. Имеет характерные серые прожилки.', NULL, 50, 1, 1, '2026-04-14 10:39:30', '2026-04-14 10:39:30'),
(2, 'Черный Абсолют гранит', 2, 250.00, 150.00, 3.00, 309.00, 0.1125, 'Черный', 'Индия', 'Зернистый', 8500.00, 31875.00, 275.00, 'default_stone.png', NULL, 'Глубокий черный гранит', 'Однородный черный гранит с минимальными вкраплениями. Отличная прочность и износостойкость.', NULL, 29, 1, 1, '2026-04-14 10:39:30', '2026-04-14 11:05:11'),
(3, 'Золотой Оникс', 3, 180.00, 90.00, 2.00, 84.00, 0.0324, 'Золотисто-коричневый', 'Иран', 'Полосчатый', 35000.00, 56700.00, 675.00, 'default_stone.png', NULL, 'Роскошный полупрозрачный оникс', 'Уникальный камень с золотистыми и коричневыми полосами. Пропускает свет, создавая волшебное свечение.', NULL, 10, 1, 1, '2026-04-14 10:39:30', '2026-04-14 10:55:44'),
(4, 'Серый сланец', 5, 100.00, 100.00, 1.50, 42.00, 0.0150, 'Серый', 'Бразилия', 'Слоистый', 3200.00, 3200.00, 76.19, 'default_stone.png', NULL, 'Натуральный сланец для фасадов', 'Экологичный камень для облицовки фасадов и внутренних стен. Морозостойкий.', NULL, 99, 1, 1, '2026-04-14 10:39:30', '2026-04-14 11:07:06');

-- --------------------------------------------------------

--
-- Структура таблицы `stone_categories`
--

CREATE TABLE `stone_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `hardness` decimal(3,1) DEFAULT 0.0,
  `density` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `stone_categories`
--

INSERT INTO `stone_categories` (`id`, `name`, `description`, `hardness`, `density`, `created_at`) VALUES
(1, 'Мрамор', 'Натуральный камень с красивой текстурой, используется для интерьеров', 3.5, 999.99, '2026-04-14 10:39:30'),
(2, 'Гранит', 'Очень прочный камень, устойчив к истиранию', 7.0, 999.99, '2026-04-14 10:39:30'),
(3, 'Оникс', 'Полудрагоценный камень с полупрозрачной структурой', 4.5, 999.99, '2026-04-14 10:39:30'),
(4, 'Травертин', 'Известковый туф, пористый камень', 4.0, 999.99, '2026-04-14 10:39:30'),
(5, 'Сланец', 'Слоистый камень, используется для облицовки', 5.0, 999.99, '2026-04-14 10:39:30'),
(6, 'Кварцит', 'Очень твердый метаморфический камень', 7.5, 999.99, '2026-04-14 10:39:30');

-- --------------------------------------------------------

--
-- Структура таблицы `stone_services`
--

CREATE TABLE `stone_services` (
  `id` int(11) NOT NULL,
  `stone_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `custom_price` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `stone_services`
--

INSERT INTO `stone_services` (`id`, `stone_id`, `service_id`, `is_available`, `custom_price`) VALUES
(1, 1, 1, 1, NULL),
(2, 1, 2, 1, NULL),
(3, 1, 3, 1, NULL),
(4, 1, 4, 1, NULL),
(5, 1, 5, 1, NULL),
(6, 1, 6, 1, NULL),
(7, 2, 1, 1, NULL),
(8, 2, 2, 1, NULL),
(9, 2, 3, 1, NULL),
(10, 2, 4, 0, NULL),
(11, 2, 5, 1, NULL),
(12, 2, 6, 1, NULL),
(13, 3, 1, 1, NULL),
(14, 3, 2, 1, NULL),
(15, 3, 3, 0, NULL),
(16, 3, 5, 1, NULL),
(17, 3, 6, 1, NULL),
(18, 4, 1, 0, NULL),
(19, 4, 2, 1, NULL),
(20, 4, 3, 1, NULL),
(21, 4, 5, 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `email`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin', '2026-04-14 10:39:30', NULL),
(2, 'user', 'f67c683f0f3e98cb9dd5582e8cbbcd04', 'user', '2026-04-14 10:39:30', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `middle_name`, `email`, `phone`, `address`, `birth_date`, `avatar`) VALUES
(1, 1, 'Администратор', 'Системы', NULL, 'admin@stoneprocessing.ru', '+7 (999) 123-45-67', NULL, NULL, 'default_avatar.png'),
(2, 2, 'Аркадий', 'Робертович', NULL, 'nagabedanarkadij2@gmail.com', '+7 (918) 277-98-68', 'Белореченск , Школьное 18', NULL, '1776244222_2d679062577203ba.png');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stone_id` (`stone_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `stone_id` (`stone_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Индексы таблицы `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stone_id` (`stone_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_stone` (`stone_id`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stones`
--
ALTER TABLE `stones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`stone_category_id`),
  ADD KEY `idx_price` (`price_per_sqm`),
  ADD KEY `idx_weight` (`weight_kg`);

--
-- Индексы таблицы `stone_categories`
--
ALTER TABLE `stone_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stone_services`
--
ALTER TABLE `stone_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stone_service` (`stone_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `stones`
--
ALTER TABLE `stones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `stone_categories`
--
ALTER TABLE `stone_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `stone_services`
--
ALTER TABLE `stone_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`stone_id`) REFERENCES `stones` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`stone_id`) REFERENCES `stones` (`id`);

--
-- Ограничения внешнего ключа таблицы `price_history`
--
ALTER TABLE `price_history`
  ADD CONSTRAINT `price_history_ibfk_1` FOREIGN KEY (`stone_id`) REFERENCES `stones` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`stone_id`) REFERENCES `stones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `stones`
--
ALTER TABLE `stones`
  ADD CONSTRAINT `stones_ibfk_1` FOREIGN KEY (`stone_category_id`) REFERENCES `stone_categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `stone_services`
--
ALTER TABLE `stone_services`
  ADD CONSTRAINT `stone_services_ibfk_1` FOREIGN KEY (`stone_id`) REFERENCES `stones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stone_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
