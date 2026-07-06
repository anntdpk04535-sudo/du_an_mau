-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2026 at 10:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

--
-- Dumping data for table `chat_logs`
--

INSERT INTO `chat_logs` (`id`, `session_id`, `user_id`, `role`, `message`, `created_at`) VALUES
(73, 'dc9c488de2b5d15dbf6a20523bfe9d35', 2, 'user', 'chào bạn', '2026-07-06 08:03:39'),
(74, 'dc9c488de2b5d15dbf6a20523bfe9d35', 2, 'assistant', 'Chào bạn! Rất vui được hỗ trợ bạn khám phá Đắk Lắk. Bạn đang muốn tìm hiểu về điều gì ở vùng đất Tây Nguyên hùng vĩ này?', '2026-07-06 08:03:39'),
(75, 'dc9c488de2b5d15dbf6a20523bfe9d35', 2, 'user', 'tôi muốn đi du lịch 1 mình trong 7 ngày thì tôi nên đi những đâu và ngày nào?', '2026-07-06 08:04:41'),
(76, 'dc9c488de2b5d15dbf6a20523bfe9d35', 2, 'assistant', 'Chào bạn! 7 ngày khám phá Đắk Lắk một mình là một hành trình tuyệt vời đó. Bạn có thể tham khảo lịch trình gợi ý sau nhé:\n\n*   **Ngày 1-2: Buôn Ma Thuột:** Khám phá văn hóa cà phê ở **Cà phê Buôn Ma Thuột**, thăm **Buôn Akô Dhông** để tìm hiểu về người Ê Đê.\n*   **Ngày 3: Thác nước:** Đến **Thác Dray Nur** và **Thác Dray Sáp** hùng vĩ, trải nghiệm trekking và hòa mình vào thiên nhiên.\n*   **Ngày 4: Hồ Lắk:** Di chuyển đến **Hồ Lắk**, hồ nước ngọt tự nhiên lớn thứ hai Việt Nam, trải nghiệm chèo thuyền độc mộc và tìm hiểu văn hóa M\'nông.\n*   **Ngày 5-6: Buôn Đôn & Yok Đôn:** Ghé thăm **Buôn Đôn** nổi tiếng với nghề săn bắt voi, sau đó khám phá **Vườn quốc gia Yok Đôn** để hòa mình vào rừng nguyên sinh.\n*   **Ngày 7: Về lại Buôn Ma Thuột:** Thư giãn tại **Hồ Ea Kao** hoặc mua sắm đặc sản trước khi kết thúc chuyến đi.\n\nLịch trình này khá đa dạng, giúp bạn trải nghiệm cả thiên nhiên, văn hóa và ẩm thực Đắk Lắk đó. Nếu bạn muốn điều chỉnh hay tìm hiểu sâu hơn về điểm nào, cứ hỏi mình nhé!', '2026-07-06 08:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `itineraries`
--

INSERT INTO `itineraries` (`id`, `user_id`, `title`, `days`, `preferences`, `ai_raw_response`, `created_at`) VALUES
(4, 2, 'Lịch trình 2 ngày - văn hoá, ẩm thực', 2, 'văn hoá, ẩm thực', '{\n  \"itinerary\": [\n    {\n      \"day\": 1,\n      \"title\": \"Khám phá văn hoá và ẩm thực phố núi Buôn Ma Thuột\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Thưởng thức Cà phê Buôn Ma Thuột tại thủ phủ cà phê, tìm hiểu về văn hóa cà phê bản địa và dùng bữa sáng với món bún đỏ đặc sản.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc taxi trong khu vực trung tâm thành phố Buôn Ma Thuột.\",\n          \"price\": \"40.000 - 80.000 VNĐ/người\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Ăn trưa với các món ăn truyền thống của người Ê Đê như vếch bò, gà nướng bản địa.\",\n          \"address\": \"\",\n          \"transport\": \"Đi bộ hoặc đi xe máy khoảng 5-10 phút từ quán cà phê trung tâm.\",\n          \"price\": \"150.000 - 250.000 VNĐ/người\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Ghé thăm Buôn Akô Dhông để chiêm ngưỡng những ngôi nhà dài truyền thống của người Ê Đê và tìm hiểu đời sống văn hóa đặc sắc nơi đây.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc ô tô khoảng 3km từ trung tâm thành phố.\",\n          \"price\": \"Miễn phí (vé vào buôn tự do, chi phí mua sắm/nước uống tự túc khoảng 30.000 - 50.000 VNĐ)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Thư giãn, ngắm hoàng hôn và dạo mát tại Hồ Ea Kao, sau đó thưởng thức ẩm thực đường phố quanh hồ.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc taxi khoảng 20 phút (~12km) từ Buôn Akô Dhông.\",\n          \"price\": \"50.000 - 100.000 VNĐ/người (chi phí ăn vặt và nước uống)\"\n        }\n      ]\n    },\n    {\n      \"day\": 2,\n      \"title\": \"Trải nghiệm văn hóa M\'nông và thiên nhiên hùng vĩ\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Khám phá Hồ Lắk, trải nghiệm đi thuyền độc mộc trên hồ nước ngọt tự nhiên lớn thứ hai Việt Nam và tìm hiểu văn hoá của người M\'nông Gar.\",\n          \"address\": \"Huyện Lắk, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng ô tô hoặc xe máy từ trung tâm TP. Buôn Ma Thuột đi theo quốc lộ 27 khoảng 1 giờ 15 phút (~56km).\",\n          \"price\": \"150.000 - 200.000 VNĐ/người (bao gồm vé thuê thuyền độc mộc)\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Thưởng thức bữa trưa với các món đặc sản Hồ Lắk như chả cá thát lát, cá bống kho tộ.\",\n          \"address\": \"Huyện Lắk, Đắk Lắk\",\n          \"transport\": \"Đi bộ từ khu vực ven hồ đến các nhà hàng ẩm thực lân cận.\",\n          \"price\": \"120.000 - 200.000 VNĐ/người\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Di chuyển tham quan Thác Dray Nur, chiêm ngưỡng sự hùng vĩ của một trong những thác nước đẹp nhất Tây Nguyên và chụp ảnh lưu niệm.\",\n          \"address\": \"Huyện Krông Ana, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng ô tô hoặc xe máy khoảng 1 giờ 30 phút (~60km) từ Hồ Lắk theo hướng về Krông Ana.\",\n          \"price\": \"30.000 VNĐ/người (vé vào cổng thác)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Trở về Buôn Ma Thuột, ăn tối nhẹ nhàng với món bánh ướt chồng dĩa đặc sản và mua sắm quà lưu niệm, cà phê trước khi kết thúc hành trình.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển khoảng 45 phút (~30km) từ Thác Dray Nur về lại trung tâm TP. Buôn Ma Thuột.\",\n          \"price\": \"50.000 - 100.000 VNĐ/người\"\n        }\n      ]\n    }\n  ]\n}', '2026-07-06 07:39:09'),
(5, 2, 'Lịch trình 2 ngày - văn hoá, trekking, gia đình', 2, 'văn hoá, trekking, gia đình', '{\n  \"itinerary\": [\n    {\n      \"day\": 1,\n      \"title\": \"Hành trình Văn hoá Ê Đê và Hương vị Cao nguyên\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Khởi đầu ngày mới tại TP. Buôn Ma Thuột bằng việc thưởng thức Cà phê Buôn Ma Thuột, tìm hiểu về lịch sử và văn hoá cà phê của thủ phủ Tây Nguyên.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc taxi trong khu vực nội thành TP. Buôn Ma Thuột.\",\n          \"price\": \"40.000 - 100.000 VNĐ/người (chi phí đồ uống và ăn sáng nhẹ)\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Ghé thăm Buôn Akô Dhông để chiêm ngưỡng những ngôi nhà dài truyền thống của người Ê Đê, tìm hiểu văn hóa bản địa và dùng bữa trưa với các món ăn đặc sản Tây Nguyên.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng ô tô hoặc xe máy khoảng 10 phút (~3km) từ trung tâm thành phố.\",\n          \"price\": \"150.000 - 250.000 VNĐ/người (miễn phí vé vào buôn, chi phí ăn trưa ẩm thực bản địa)\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Thư giãn và dạo chơi tại Hồ Ea Kao, ngắm cảnh hoàng hôn yên bình, thích hợp cho các hoạt động gia đình và chụp ảnh lưu niệm.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng ô tô hoặc xe máy khoảng 20 phút (~9km) về hướng Đông Nam từ Buôn Akô Dhông.\",\n          \"price\": \"30.000 - 50.000 VNĐ/người (chi phí nước uống hoặc thuê bạt ngắm cảnh)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Trở về trung tâm thành phố ăn tối, tự do dạo phố đêm Buôn Ma Thuột và thưởng thức các món ăn đường phố.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng taxi hoặc xe máy cá nhân.\",\n          \"price\": \"100.000 - 150.000 VNĐ/người (chi phí ăn tối tự túc)\"\n        }\n      ]\n    },\n    {\n      \"day\": 2,\n      \"title\": \"Trekking Hùng Vĩ và Trải nghiệm Thác Nguồn\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Hành trình trekking, khám phá vẻ đẹp hoang sơ và kỳ vĩ của Thác Dray Nur. Gia đình có thể đi dạo trên các lối mòn, chụp ảnh check-in và cảm nhận hơi nước mát lạnh.\",\n          \"address\": \"Huyện Krông Ana, Đắk Lắk\",\n          \"transport\": \"Thuê ô tô tự lái hoặc xe máy di chuyển khoảng 50 phút (~30km) từ trung tâm TP. Buôn Ma Thuột theo hướng quốc lộ 14 rẽ đường tỉnh 682.\",\n          \"price\": \"50.000 VNĐ/người (vé vào cổng tham quan thác)\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Thưởng thức bữa trưa dã ngoại hoặc dùng bữa tại nhà hàng ngay cạnh Thác Dray Nur với các món đặc sản như cơm lam, gà nướng.\",\n          \"address\": \"Huyện Krông Ana, Đắk Lắk\",\n          \"transport\": \"Đi bộ trong khu vực cụm thác.\",\n          \"price\": \"150.000 - 200.000 VNĐ/người (chi phí ăn trưa)\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Tiếp tục hành trình trekking ngắn sang Thác Dray Sáp lân cận, chiêm ngưỡng dòng thác khói huyền thoại và khám phá hệ sinh thái rừng đặc dụng.\",\n          \"address\": \"Huyện Krông Ana, Đắk Lắk\",\n          \"transport\": \"Đi bộ xuyên rừng qua cầu treo kết nối hoặc di chuyển xe máy/ô tô khoảng 5 phút giữa hai khu vực thác.\",\n          \"price\": \"40.000 VNĐ/người (vé vào cổng Thác Dray Sáp)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Di chuyển về lại trung tâm TP. Buôn Ma Thuột, mua sắm quà lưu niệm (cà phê, đồ thổ cẩm) và kết thúc chuyến hành trình 2 ngày 1 đêm.\",\n          \"address\": \"\",\n          \"transport\": \"Di chuyển bằng ô tô hoặc xe máy khoảng 50 phút (~30km) để về lại thành phố.\",\n          \"price\": \"100.000 - 300.000 VNĐ/người (chi phí mua quà và ăn uống nhẹ trước khi ra sân bay/bến xe)\"\n        }\n      ]\n    }\n  ]\n}', '2026-07-06 07:43:26'),
(6, 2, 'Lịch trình 2 ngày - văn hoá, ẩm thực', 2, 'văn hoá, ẩm thực', '{\n  \"itinerary\": [\n    {\n      \"day\": 1,\n      \"title\": \"Khám phá Văn hóa Cà phê và Buôn làng Ê Đê\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Bắt đầu ngày mới với trải nghiệm văn hóa cà phê đặc trưng của Buôn Ma Thuột. Thưởng thức cà phê tại một quán cà phê địa phương, tìm hiểu về quy trình sản xuất và lịch sử cà phê Buôn Ma Thuột. Bạn có thể ghé thăm Bảo tàng Cà phê Thế giới để có cái nhìn sâu sắc hơn.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.\",\n          \"price\": \"50.000 - 150.000 VNĐ/người (bao gồm đồ uống và vé tham quan bảo tàng nếu có)\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Thưởng thức ẩm thực địa phương với các món ăn đặc sản Đắk Lắk như bún đỏ, phở khô hoặc nem nướng tại một nhà hàng ở trung tâm Buôn Ma Thuột.\",\n          \"address\": \"\",\n          \"transport\": \"Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.\",\n          \"price\": \"50.000 - 100.000 VNĐ/người\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Tham quan Buôn Akô Dhông, buôn làng Ê Đê cổ kính và đẹp nhất Buôn Ma Thuột. Tìm hiểu về kiến trúc nhà dài truyền thống, văn hóa và phong tục tập quán của người Ê Đê.\",\n          \"address\": \"TP. Buôn Ma Thuột, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc taxi khoảng 15 phút (~5km) từ trung tâm thành phố.\",\n          \"price\": \"Miễn phí (có thể phát sinh chi phí mua sắm quà lưu niệm)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Ăn tối tại một nhà hàng có không gian ấm cúng ở Buôn Ma Thuột và khám phá chợ đêm để mua sắm đặc sản địa phương hoặc quà lưu niệm.\",\n          \"address\": \"\",\n          \"transport\": \"Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.\",\n          \"price\": \"100.000 - 200.000 VNĐ/người\"\n        }\n      ]\n    },\n    {\n      \"day\": 2,\n      \"title\": \"Trải nghiệm Văn hóa Voi và Cảnh đẹp Hồ Lắk\",\n      \"items\": [\n        {\n          \"time\": \"Sáng\",\n          \"activity\": \"Khám phá Buôn Đôn, một làng văn hóa nổi tiếng với nghề săn bắt và thuần dưỡng voi. Bạn có thể tham quan nhà dài cổ, cầu treo và tìm hiểu về cuộc sống của người dân địa phương. Lưu ý: các hoạt động cưỡi voi đang dần được thay thế bằng các hoạt động thân thiện với voi hơn như cho voi ăn, tắm cho voi.\",\n          \"address\": \"Huyện Buôn Đôn, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc ô tô tự lái khoảng 1 giờ (~45km) từ trung tâm Buôn Ma Thuột.\",\n          \"price\": \"100.000 - 300.000 VNĐ/người (bao gồm vé vào cổng và các hoạt động trải nghiệm tại Buôn Đôn)\"\n        },\n        {\n          \"time\": \"Trưa\",\n          \"activity\": \"Ăn trưa tại nhà hàng địa phương ở Buôn Đôn hoặc trên đường đi Hồ Lắk, thưởng thức các món ăn mang đậm hương vị núi rừng Tây Nguyên.\",\n          \"address\": \"\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc ô tô.\",\n          \"price\": \"80.000 - 150.000 VNĐ/người\"\n        },\n        {\n          \"time\": \"Chiều\",\n          \"activity\": \"Tham quan Hồ Lắk, hồ nước ngọt tự nhiên lớn thứ 2 Việt Nam, gắn liền với văn hóa M\'nông. Bạn có thể trải nghiệm đi thuyền độc mộc trên hồ, ngắm cảnh hoàng hôn và thăm quan các buôn làng quanh hồ.\",\n          \"address\": \"Huyện Lắk, Đắk Lắk\",\n          \"transport\": \"Di chuyển bằng xe máy hoặc ô tô tự lái khoảng 1.5 - 2 giờ (~50km) từ Buôn Đôn hoặc khoảng 1.5 giờ (~50km) từ Buôn Ma Thuột. Nên đi theo quốc lộ 27.\",\n          \"price\": \"50.000 - 200.000 VNĐ/người (bao gồm vé vào cổng và chi phí thuê thuyền độc mộc nếu có)\"\n        },\n        {\n          \"time\": \"Tối\",\n          \"activity\": \"Ăn tối và nghỉ ngơi tại các homestay hoặc nhà nghỉ ven Hồ Lắk để tận hưởng không khí trong lành và yên bình của vùng đất này. Thưởng thức các món cá nước ngọt đặc sản của hồ.\",\n          \"address\": \"\",\n          \"transport\": \"Đi bộ hoặc di chuyển xe máy/ô tô ngắn trong khu vực Hồ Lắk.\",\n          \"price\": \"150.000 - 300.000 VNĐ/người (bao gồm bữa tối, chưa bao gồm chi phí lưu trú)\"\n        }\n      ]\n    }\n  ]\n}', '2026-07-06 08:02:51');

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
  `address` varchar(255) DEFAULT NULL,
  `transport` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `itinerary_items`
--

INSERT INTO `itinerary_items` (`id`, `itinerary_id`, `destination_id`, `day_number`, `time_slot`, `activity`, `address`, `transport`, `sort_order`) VALUES
(9, 4, NULL, 1, 'Sáng', 'Thưởng thức Cà phê Buôn Ma Thuột tại thủ phủ cà phê, tìm hiểu về văn hóa cà phê bản địa và dùng bữa sáng với món bún đỏ đặc sản.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng xe máy hoặc taxi trong khu vực trung tâm thành phố Buôn Ma Thuột.', 0),
(10, 4, NULL, 1, 'Trưa', 'Ăn trưa với các món ăn truyền thống của người Ê Đê như vếch bò, gà nướng bản địa.', '', 'Đi bộ hoặc đi xe máy khoảng 5-10 phút từ quán cà phê trung tâm.', 1),
(11, 4, NULL, 1, 'Chiều', 'Ghé thăm Buôn Akô Dhông để chiêm ngưỡng những ngôi nhà dài truyền thống của người Ê Đê và tìm hiểu đời sống văn hóa đặc sắc nơi đây.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng xe máy hoặc ô tô khoảng 3km từ trung tâm thành phố.', 2),
(12, 4, NULL, 1, 'Tối', 'Thư giãn, ngắm hoàng hôn và dạo mát tại Hồ Ea Kao, sau đó thưởng thức ẩm thực đường phố quanh hồ.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng xe máy hoặc taxi khoảng 20 phút (~12km) từ Buôn Akô Dhông.', 3),
(13, 4, NULL, 2, 'Sáng', 'Khám phá Hồ Lắk, trải nghiệm đi thuyền độc mộc trên hồ nước ngọt tự nhiên lớn thứ hai Việt Nam và tìm hiểu văn hoá của người M\'nông Gar.', 'Huyện Lắk, Đắk Lắk', 'Di chuyển bằng ô tô hoặc xe máy từ trung tâm TP. Buôn Ma Thuột đi theo quốc lộ 27 khoảng 1 giờ 15 phút (~56km).', 4),
(14, 4, NULL, 2, 'Trưa', 'Thưởng thức bữa trưa với các món đặc sản Hồ Lắk như chả cá thát lát, cá bống kho tộ.', 'Huyện Lắk, Đắk Lắk', 'Đi bộ từ khu vực ven hồ đến các nhà hàng ẩm thực lân cận.', 5),
(15, 4, NULL, 2, 'Chiều', 'Di chuyển tham quan Thác Dray Nur, chiêm ngưỡng sự hùng vĩ của một trong những thác nước đẹp nhất Tây Nguyên và chụp ảnh lưu niệm.', 'Huyện Krông Ana, Đắk Lắk', 'Di chuyển bằng ô tô hoặc xe máy khoảng 1 giờ 30 phút (~60km) từ Hồ Lắk theo hướng về Krông Ana.', 6),
(16, 4, NULL, 2, 'Tối', 'Trở về Buôn Ma Thuột, ăn tối nhẹ nhàng với món bánh ướt chồng dĩa đặc sản và mua sắm quà lưu niệm, cà phê trước khi kết thúc hành trình.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển khoảng 45 phút (~30km) từ Thác Dray Nur về lại trung tâm TP. Buôn Ma Thuột.', 7),
(17, 5, NULL, 1, 'Sáng', 'Khởi đầu ngày mới tại TP. Buôn Ma Thuột bằng việc thưởng thức Cà phê Buôn Ma Thuột, tìm hiểu về lịch sử và văn hoá cà phê của thủ phủ Tây Nguyên.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng xe máy hoặc taxi trong khu vực nội thành TP. Buôn Ma Thuột.', 0),
(18, 5, NULL, 1, 'Trưa', 'Ghé thăm Buôn Akô Dhông để chiêm ngưỡng những ngôi nhà dài truyền thống của người Ê Đê, tìm hiểu văn hóa bản địa và dùng bữa trưa với các món ăn đặc sản Tây Nguyên.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng ô tô hoặc xe máy khoảng 10 phút (~3km) từ trung tâm thành phố.', 1),
(19, 5, NULL, 1, 'Chiều', 'Thư giãn và dạo chơi tại Hồ Ea Kao, ngắm cảnh hoàng hôn yên bình, thích hợp cho các hoạt động gia đình và chụp ảnh lưu niệm.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng ô tô hoặc xe máy khoảng 20 phút (~9km) về hướng Đông Nam từ Buôn Akô Dhông.', 2),
(20, 5, NULL, 1, 'Tối', 'Trở về trung tâm thành phố ăn tối, tự do dạo phố đêm Buôn Ma Thuột và thưởng thức các món ăn đường phố.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng taxi hoặc xe máy cá nhân.', 3),
(21, 5, NULL, 2, 'Sáng', 'Hành trình trekking, khám phá vẻ đẹp hoang sơ và kỳ vĩ của Thác Dray Nur. Gia đình có thể đi dạo trên các lối mòn, chụp ảnh check-in và cảm nhận hơi nước mát lạnh.', 'Huyện Krông Ana, Đắk Lắk', 'Thuê ô tô tự lái hoặc xe máy di chuyển khoảng 50 phút (~30km) từ trung tâm TP. Buôn Ma Thuột theo hướng quốc lộ 14 rẽ đường tỉnh 682.', 4),
(22, 5, NULL, 2, 'Trưa', 'Thưởng thức bữa trưa dã ngoại hoặc dùng bữa tại nhà hàng ngay cạnh Thác Dray Nur với các món đặc sản như cơm lam, gà nướng.', 'Huyện Krông Ana, Đắk Lắk', 'Đi bộ trong khu vực cụm thác.', 5),
(23, 5, NULL, 2, 'Chiều', 'Tiếp tục hành trình trekking ngắn sang Thác Dray Sáp lân cận, chiêm ngưỡng dòng thác khói huyền thoại và khám phá hệ sinh thái rừng đặc dụng.', 'Huyện Krông Ana, Đắk Lắk', 'Đi bộ xuyên rừng qua cầu treo kết nối hoặc di chuyển xe máy/ô tô khoảng 5 phút giữa hai khu vực thác.', 6),
(24, 5, NULL, 2, 'Tối', 'Di chuyển về lại trung tâm TP. Buôn Ma Thuột, mua sắm quà lưu niệm (cà phê, đồ thổ cẩm) và kết thúc chuyến hành trình 2 ngày 1 đêm.', '', 'Di chuyển bằng ô tô hoặc xe máy khoảng 50 phút (~30km) để về lại thành phố.', 7),
(25, 6, NULL, 1, 'Sáng', 'Bắt đầu ngày mới với trải nghiệm văn hóa cà phê đặc trưng của Buôn Ma Thuột. Thưởng thức cà phê tại một quán cà phê địa phương, tìm hiểu về quy trình sản xuất và lịch sử cà phê Buôn Ma Thuột. Bạn có thể ghé thăm Bảo tàng Cà phê Thế giới để có cái nhìn sâu sắc hơn.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.', 0),
(26, 6, NULL, 1, 'Trưa', 'Thưởng thức ẩm thực địa phương với các món ăn đặc sản Đắk Lắk như bún đỏ, phở khô hoặc nem nướng tại một nhà hàng ở trung tâm Buôn Ma Thuột.', '', 'Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.', 1),
(27, 6, NULL, 1, 'Chiều', 'Tham quan Buôn Akô Dhông, buôn làng Ê Đê cổ kính và đẹp nhất Buôn Ma Thuột. Tìm hiểu về kiến trúc nhà dài truyền thống, văn hóa và phong tục tập quán của người Ê Đê.', 'TP. Buôn Ma Thuột, Đắk Lắk', 'Di chuyển bằng xe máy hoặc taxi khoảng 15 phút (~5km) từ trung tâm thành phố.', 2),
(28, 6, NULL, 1, 'Tối', 'Ăn tối tại một nhà hàng có không gian ấm cúng ở Buôn Ma Thuột và khám phá chợ đêm để mua sắm đặc sản địa phương hoặc quà lưu niệm.', '', 'Di chuyển bằng taxi hoặc xe máy trong trung tâm thành phố.', 3),
(29, 6, NULL, 2, 'Sáng', 'Khám phá Buôn Đôn, một làng văn hóa nổi tiếng với nghề săn bắt và thuần dưỡng voi. Bạn có thể tham quan nhà dài cổ, cầu treo và tìm hiểu về cuộc sống của người dân địa phương. Lưu ý: các hoạt động cưỡi voi đang dần được thay thế bằng các hoạt động thân thiện với voi hơn như cho voi ăn, tắm cho voi.', 'Huyện Buôn Đôn, Đắk Lắk', 'Di chuyển bằng xe máy hoặc ô tô tự lái khoảng 1 giờ (~45km) từ trung tâm Buôn Ma Thuột.', 4),
(30, 6, NULL, 2, 'Trưa', 'Ăn trưa tại nhà hàng địa phương ở Buôn Đôn hoặc trên đường đi Hồ Lắk, thưởng thức các món ăn mang đậm hương vị núi rừng Tây Nguyên.', '', 'Di chuyển bằng xe máy hoặc ô tô.', 5),
(31, 6, NULL, 2, 'Chiều', 'Tham quan Hồ Lắk, hồ nước ngọt tự nhiên lớn thứ 2 Việt Nam, gắn liền với văn hóa M\'nông. Bạn có thể trải nghiệm đi thuyền độc mộc trên hồ, ngắm cảnh hoàng hôn và thăm quan các buôn làng quanh hồ.', 'Huyện Lắk, Đắk Lắk', 'Di chuyển bằng xe máy hoặc ô tô tự lái khoảng 1.5 - 2 giờ (~50km) từ Buôn Đôn hoặc khoảng 1.5 giờ (~50km) từ Buôn Ma Thuột. Nên đi theo quốc lộ 27.', 6),
(32, 6, NULL, 2, 'Tối', 'Ăn tối và nghỉ ngơi tại các homestay hoặc nhà nghỉ ven Hồ Lắk để tận hưởng không khí trong lành và yên bình của vùng đất này. Thưởng thức các món cá nước ngọt đặc sản của hồ.', '', 'Đi bộ hoặc di chuyển xe máy/ô tô ngắn trong khu vực Hồ Lắk.', 7);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL COMMENT 'NULL = đánh giá dịch vụ website tổng thể',
  `rating` tinyint(4) NOT NULL DEFAULT 5 COMMENT '1-5 sao',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(1, 'nguyenvana', 'vunamphi1202@gmail.com', '$2y$10$HRMc5Qg/SEJrsxzjajCyQuK0HWMOqwp.0ra9f3qPB7q2I8sKhh2rC', 'user', '2026-06-28 08:46:59'),
(2, 'An', 'ntda@gmail.com', '$2y$10$40rnq.we4KFgSTZdM7pzXeCjaW5UiEMDofhtLDujIu4WdSkT6Tg1C', 'admin', '2026-07-06 07:31:02'),
(3, 'An', 'dinhan27107@gmail.com', '$2y$10$ivJUVCU.sOkIYR/nzR3yVeAgwA770DCu3Y1HOMD32Q5Z2bu.1rk9e', 'user', '2026-07-06 07:37:04');

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
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `admin_id` (`admin_id`);

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
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destination` (`destination_id`),
  ADD KEY `idx_user` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD CONSTRAINT `chat_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD CONSTRAINT `contact_replies_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
