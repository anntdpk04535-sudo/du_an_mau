-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 09:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

CREATE DATABASE IF NOT EXISTS daklak_travel
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE daklak_travel;

 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `daklak_travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Thác nước', 'thac-nuoc'),
(2, 'Hồ', 'ho'),
(3, 'Buôn làng - Văn hoá', 'buon-lang-van-hoa'),
(4, 'Vườn quốc gia', 'vuon-quoc-gia'),
(5, 'Ẩm thực', 'am-thuc');

-- --------------------------------------------------------

--
-- Table structure for table `chat_logs`
--

CREATE TABLE `chat_logs` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('user','assistant') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_desc` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `avg_visit_hours` decimal(4,1) DEFAULT 2.0,
  `price_level` enum('free','low','medium','high') DEFAULT 'low',
  `rating` decimal(2,1) DEFAULT 4.5,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `category_id`, `name`, `slug`, `short_desc`, `description`, `address`, `image_url`, `avg_visit_hours`, `price_level`, `rating`, `latitude`, `longitude`, `tags`, `created_at`) VALUES
(1, 2, 'Hồ Lắk', 'ho-lak', 'Hồ nước ngọt tự nhiên lớn thứ 2 Việt Nam, gắn với văn hoá M\'nông.', 'Hồ Lắk là một trong những hồ nước ngọt tự nhiên lớn nhất Việt Nam, nằm tại huyện Lắk, tỉnh Đắk Lắk. Du khách có thể đi thuyền độc mộc, cưỡi voi, tham quan Biệt điện Bảo Đại và trải nghiệm văn hoá người M\'nông quanh hồ.', 'Huyện Lắk, Đắk Lắk', 'https://nhanghitaynguyen.com/wp-content/uploads/2020/09/tham-quan-ho-lak.jpg', 3.0, 'low', 4.6, 12.420300, 108.185400, 'thiên nhiên,hồ,văn hoá,thuyền độc mộc', '2026-06-28 05:57:43'),
(2, 1, 'Thác Dray Nur', 'thac-dray-nur', 'Một trong những thác nước hùng vĩ nhất Tây Nguyên.', 'Thác Dray Nur nằm trên dòng sông Sêrêpốk, là một trong những thác nước đẹp và hùng vĩ nhất khu vực Tây Nguyên, thích hợp cho trekking, chụp ảnh và tắm thác.', 'Huyện Krông Ana, Đắk Lắk', 'https://trungnguyenhealing.com/resources/uploads/Dray%20-Nur.jpg', 2.5, 'low', 4.7, 12.566700, 108.116700, 'thiên nhiên,thác nước,trekking', '2026-06-28 05:57:43'),
(3, 1, 'Thác Dray Sáp', 'thac-dray-sap', 'Thác \"nước khói\" nổi tiếng gần Buôn Ma Thuột.', 'Thác Dray Sáp (nghĩa là \"thác khói\") nổi tiếng với dòng nước tung bọt trắng như sương khói, gần khu vực Dray Nur, thuận tiện kết hợp tham quan trong cùng ngày.', 'Huyện Krông Ana, Đắk Lắk', 'https://ticotravel.com.vn/wp-content/uploads/2022/10/Thac-Dray-Sap-5.jpg', 2.0, 'low', 4.5, 12.572800, 108.108300, 'thiên nhiên,thác nước', '2026-06-28 05:57:43'),
(4, 3, 'Buôn Đôn', 'buon-don', 'Làng văn hoá nổi tiếng với nghề săn bắt và thuần dưỡng voi.', 'Buôn Đôn là điểm đến văn hoá nổi tiếng của Đắk Lắk, nơi du khách tìm hiểu về nghề thuần dưỡng voi, tham quan nhà dài truyền thống, cầu treo và mộ vua voi Khunjunob.', 'Huyện Buôn Đôn, Đắk Lắk', 'https://ik.imagekit.io/tvlk/blog/2023/05/buon-don-1.jpg?tr=dpr-2,w-675', 3.0, 'medium', 4.4, 13.016700, 107.833300, 'văn hoá,voi,nhà dài,cầu treo', '2026-06-28 05:57:43'),
(5, 4, 'Vườn quốc gia Yok Đôn', 'vuon-quoc-gia-yok-don', 'Vườn quốc gia lớn nhất Việt Nam, nơi sinh sống của voi rừng.', 'Yok Đôn là vườn quốc gia lớn nhất Việt Nam, nổi bật với hệ sinh thái rừng khô đặc trưng Tây Nguyên và là nơi triển khai mô hình du lịch thân thiện với voi (không cưỡi voi).', 'Huyện Buôn Đôn, Đắk Lắk', 'https://luhanhvietnam.com.vn/du-lich/vnt_upload/news/04_2020/vuon-quoc-gia-yok-don.jpg', 4.0, 'medium', 4.5, 13.083300, 107.783300, 'thiên nhiên,rừng,voi,sinh thái', '2026-06-28 05:57:43'),
(6, 5, 'Cà phê Buôn Ma Thuột', 'ca-phe-buon-ma-thuot', 'Thủ phủ cà phê Việt Nam, trải nghiệm văn hoá cà phê đặc sắc.', 'Buôn Ma Thuột được mệnh danh là \"thủ phủ cà phê\" của Việt Nam. Du khách có thể tham quan các đồn điền cà phê, Bảo tàng Thế giới Cà phê và thưởng thức cà phê nguyên chất Đắk Lắk.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'https://th.bing.com/th/id/R.e44b2f35f39e2f95124b573a71df1395?rik=lad4Uf7u8%2fjhJQ&pid=ImgRaw&r=0', 2.0, 'free', 4.6, 12.666700, 108.050000, 'ẩm thực,cà phê,bảo tàng', '2026-06-28 05:57:43'),
(7, 3, 'Buôn Akô Dhông', 'buon-ako-dhong', 'Buôn làng Ê Đê đẹp nhất Buôn Ma Thuột.', 'Buôn Akô Dhông nằm ngay trong lòng thành phố Buôn Ma Thuột, nổi tiếng với những ngôi nhà dài truyền thống của người Ê Đê và không gian xanh yên bình.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'https://52hz.vn/wp-content/uploads/2021/12/1-buon-ako-dhong.jpg', 1.5, 'free', 4.3, 12.683300, 108.033300, 'văn hoá,nhà dài,Ê Đê', '2026-06-28 05:57:43'),
(8, 2, 'Hồ Ea Kao', 'ho-ea-kao', 'Hồ nước rộng lớn gần trung tâm thành phố, thích hợp cắm trại.', 'Hồ Ea Kao là hồ nhân tạo rộng lớn nằm gần TP. Buôn Ma Thuột, không khí mát mẻ, phù hợp cho dã ngoại, cắm trại và chụp ảnh.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'https://static-cms-vovworld.zadn.vn/uploaded/buihangm/2022_03_24/6_rgdj.jpg', 2.0, 'free', 4.2, 12.616700, 108.083300, 'thiên nhiên,hồ,cắm trại', '2026-06-28 05:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `itineraries`
--

CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `days` int(11) NOT NULL DEFAULT 1,
  `preferences` varchar(500) DEFAULT NULL,
  `ai_raw_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_items`
--

CREATE TABLE `itinerary_items` (
  `id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `day_number` int(11) NOT NULL DEFAULT 1,
  `time_slot` varchar(50) DEFAULT NULL,
  `activity` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'nguyenvana', 'vunamphi1202@gmail.com', '$2y$10$HRMc5Qg/SEJrsxzjajCyQuK0HWMOqwp.0ra9f3qPB7q2I8sKhh2rC', 'user', '2026-06-28 08:46:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itinerary_id` (`itinerary_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD CONSTRAINT `chat_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `destinations`
--
ALTER TABLE `destinations`
  ADD CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD CONSTRAINT `itineraries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  ADD CONSTRAINT `itinerary_items_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itinerary_items_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
