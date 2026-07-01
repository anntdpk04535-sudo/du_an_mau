-- =====================================================
-- Database: daklak_travel
-- Website Du lịch Đắk Lắk + AI
-- =====================================================

CREATE DATABASE IF NOT EXISTS daklak_travel
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE daklak_travel;

-- ---------------------------------------------------
-- Bảng danh mục điểm đến (Thác, Hồ, Buôn làng, Vườn QG, Ẩm thực...)
-- ---------------------------------------------------
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Bảng điểm đến du lịch
-- ---------------------------------------------------
CREATE TABLE destinations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  short_desc VARCHAR(500) DEFAULT NULL,
  description TEXT,
  address VARCHAR(255) DEFAULT NULL,
  image_url VARCHAR(500) DEFAULT NULL,
  avg_visit_hours DECIMAL(4,1) DEFAULT 2.0,   -- thời gian tham quan trung bình (giờ)
  price_level ENUM('free','low','medium','high') DEFAULT 'low',
  rating DECIMAL(2,1) DEFAULT 4.5,
  latitude DECIMAL(10,6) DEFAULT NULL,
  longitude DECIMAL(10,6) DEFAULT NULL,
  tags VARCHAR(255) DEFAULT NULL,             -- vd: 'thiên nhiên,thác nước,trekking'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Bảng người dùng
-- ---------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Bảng lịch trình do AI gợi ý / người dùng tạo
-- ---------------------------------------------------
CREATE TABLE itineraries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(255) NOT NULL,
  days INT NOT NULL DEFAULT 1,
  preferences VARCHAR(500) DEFAULT NULL,   -- sở thích người dùng nhập
  ai_raw_response TEXT,                    -- toàn văn AI trả về (lưu để tham khảo)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Bảng chi tiết từng mục trong lịch trình (theo ngày/giờ)
-- ---------------------------------------------------
CREATE TABLE itinerary_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  itinerary_id INT NOT NULL,
  destination_id INT NULL,
  day_number INT NOT NULL DEFAULT 1,
  time_slot VARCHAR(50) DEFAULT NULL,   -- vd: 'Sáng', 'Trưa', 'Chiều', 'Tối'
  activity TEXT,
  address VARCHAR(255) DEFAULT NULL,    -- địa chỉ cụ thể của hoạt động/điểm đến
  sort_order INT DEFAULT 0,
  FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE CASCADE,
  FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Bảng lưu lịch sử chat với AI (chatbot)
-- ---------------------------------------------------
CREATE TABLE chat_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(100) NOT NULL,
  user_id INT NULL,
  role ENUM('user','assistant') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

INSERT INTO categories (name, slug) VALUES
('Thác nước', 'thac-nuoc'),
('Hồ', 'ho'),
('Buôn làng - Văn hoá', 'buon-lang-van-hoa'),
('Vườn quốc gia', 'vuon-quoc-gia'),
('Ẩm thực', 'am-thuc');

INSERT INTO destinations
(category_id, name, slug, short_desc, description, address, image_url, avg_visit_hours, price_level, rating, latitude, longitude, tags)
VALUES
(2, 'Hồ Lắk', 'ho-lak',
 'Hồ nước ngọt tự nhiên lớn thứ 2 Việt Nam, gắn với văn hoá M''nông.',
 'Hồ Lắk là một trong những hồ nước ngọt tự nhiên lớn nhất Việt Nam, nằm tại huyện Lắk, tỉnh Đắk Lắk. Du khách có thể đi thuyền độc mộc, cưỡi voi, tham quan Biệt điện Bảo Đại và trải nghiệm văn hoá người M''nông quanh hồ.',
 'Huyện Lắk, Đắk Lắk', '', 3.0, 'low', 4.6, 12.4203, 108.1854, 'thiên nhiên,hồ,văn hoá,thuyền độc mộc'),

(1, 'Thác Dray Nur', 'thac-dray-nur',
 'Một trong những thác nước hùng vĩ nhất Tây Nguyên.',
 'Thác Dray Nur nằm trên dòng sông Sêrêpốk, là một trong những thác nước đẹp và hùng vĩ nhất khu vực Tây Nguyên, thích hợp cho trekking, chụp ảnh và tắm thác.',
 'Huyện Krông Ana, Đắk Lắk', '', 2.5, 'low', 4.7, 12.5667, 108.1167, 'thiên nhiên,thác nước,trekking'),

(1, 'Thác Dray Sáp', 'thac-dray-sap',
 'Thác "nước khói" nổi tiếng gần Buôn Ma Thuột.',
 'Thác Dray Sáp (nghĩa là "thác khói") nổi tiếng với dòng nước tung bọt trắng như sương khói, gần khu vực Dray Nur, thuận tiện kết hợp tham quan trong cùng ngày.',
 'Huyện Krông Ana, Đắk Lắk', '', 2.0, 'low', 4.5, 12.5728, 108.1083, 'thiên nhiên,thác nước'),

(3, 'Buôn Đôn', 'buon-don',
 'Làng văn hoá nổi tiếng với nghề săn bắt và thuần dưỡng voi.',
 'Buôn Đôn là điểm đến văn hoá nổi tiếng của Đắk Lắk, nơi du khách tìm hiểu về nghề thuần dưỡng voi, tham quan nhà dài truyền thống, cầu treo và mộ vua voi Khunjunob.',
 'Huyện Buôn Đôn, Đắk Lắk', '', 3.0, 'medium', 4.4, 13.0167, 107.8333, 'văn hoá,voi,nhà dài,cầu treo'),

(4, 'Vườn quốc gia Yok Đôn', 'vuon-quoc-gia-yok-don',
 'Vườn quốc gia lớn nhất Việt Nam, nơi sinh sống của voi rừng.',
 'Yok Đôn là vườn quốc gia lớn nhất Việt Nam, nổi bật với hệ sinh thái rừng khô đặc trưng Tây Nguyên và là nơi triển khai mô hình du lịch thân thiện với voi (không cưỡi voi).',
 'Huyện Buôn Đôn, Đắk Lắk', '', 4.0, 'medium', 4.5, 13.0833, 107.7833, 'thiên nhiên,rừng,voi,sinh thái'),

(5, 'Cà phê Buôn Ma Thuột', 'ca-phe-buon-ma-thuot',
 'Thủ phủ cà phê Việt Nam, trải nghiệm văn hoá cà phê đặc sắc.',
 'Buôn Ma Thuột được mệnh danh là "thủ phủ cà phê" của Việt Nam. Du khách có thể tham quan các đồn điền cà phê, Bảo tàng Thế giới Cà phê và thưởng thức cà phê nguyên chất Đắk Lắk.',
 'TP. Buôn Ma Thuột, Đắk Lắk', '', 2.0, 'free', 4.6, 12.6667, 108.0500, 'ẩm thực,cà phê,bảo tàng'),

(3, 'Buôn Akô Dhông', 'buon-ako-dhong',
 'Buôn làng Ê Đê đẹp nhất Buôn Ma Thuột.',
 'Buôn Akô Dhông nằm ngay trong lòng thành phố Buôn Ma Thuột, nổi tiếng với những ngôi nhà dài truyền thống của người Ê Đê và không gian xanh yên bình.',
 'TP. Buôn Ma Thuột, Đắk Lắk', '', 1.5, 'free', 4.3, 12.6833, 108.0333, 'văn hoá,nhà dài,Ê Đê'),

(2, 'Hồ Ea Kao', 'ho-ea-kao',
 'Hồ nước rộng lớn gần trung tâm thành phố, thích hợp cắm trại.',
 'Hồ Ea Kao là hồ nhân tạo rộng lớn nằm gần TP. Buôn Ma Thuột, không khí mát mẻ, phù hợp cho dã ngoại, cắm trại và chụp ảnh.',
 'TP. Buôn Ma Thuột, Đắk Lắk', '', 2.0, 'free', 4.2, 12.6167, 108.0833, 'thiên nhiên,hồ,cắm trại');
