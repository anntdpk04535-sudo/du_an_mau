-- Additive upgrade: điểm neo (origin) + thời tiết + hạ tầng geo cho lịch trình.
-- Chạy qua scripts/migrate_itinerary_geo.php; mọi statement đều idempotent.
-- Phụ thuộc: 20260806_upgrade.sql (foods/accommodations), 20260807_region_split.sql (region).

ALTER TABLE itineraries
  ADD COLUMN IF NOT EXISTS origin_type enum('none','current','accommodation','manual') NOT NULL DEFAULT 'none' AFTER preferences,
  ADD COLUMN IF NOT EXISTS origin_label varchar(255) DEFAULT NULL AFTER origin_type,
  ADD COLUMN IF NOT EXISTS origin_lat decimal(10,6) DEFAULT NULL AFTER origin_label,
  ADD COLUMN IF NOT EXISTS origin_lng decimal(10,6) DEFAULT NULL AFTER origin_lat,
  ADD COLUMN IF NOT EXISTS origin_accommodation_id int DEFAULT NULL AFTER origin_lng,
  ADD COLUMN IF NOT EXISTS radius_km decimal(5,1) DEFAULT NULL AFTER origin_accommodation_id,
  ADD COLUMN IF NOT EXISTS weather_snapshot longtext DEFAULT NULL AFTER radius_km;

-- Phân loại trong nhà / ngoài trời để lọc điểm đến theo thời tiết.
-- weather_sensitivity: 0 = không ảnh hưởng mưa, 3 = rất nhạy (thác, trekking).
ALTER TABLE destinations
  ADD COLUMN IF NOT EXISTS indoor_type enum('indoor','outdoor','mixed') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS weather_sensitivity tinyint DEFAULT NULL;

-- Nguồn tọa độ: biết dữ liệu đáng tin đến đâu (nhập tay > copy từ điểm đến > geocode).
ALTER TABLE foods
  ADD COLUMN IF NOT EXISTS geo_source enum('manual','destination','geocode','place') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS geocoded_at timestamp NULL DEFAULT NULL;

ALTER TABLE accommodations
  ADD COLUMN IF NOT EXISTS geo_source enum('manual','destination','geocode','place') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS geocoded_at timestamp NULL DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_destinations_geo ON destinations(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_foods_geo ON foods(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_accommodations_geo ON accommodations(latitude, longitude);

-- Cache geocode để tôn trọng rate limit Nominatim (1 req/s).
CREATE TABLE IF NOT EXISTS geocode_cache (
  id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  query_hash char(64) NOT NULL,
  query_text varchar(500) NOT NULL,
  latitude decimal(10,6) DEFAULT NULL,
  longitude decimal(10,6) DEFAULT NULL,
  display_name varchar(500) DEFAULT NULL,
  provider varchar(40) NOT NULL DEFAULT 'nominatim',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_geocode_query (query_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache dự báo thời tiết theo ô lưới ~0.01 độ, TTL xử lý ở tầng PHP.
CREATE TABLE IF NOT EXISTS weather_cache (
  id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  grid_key varchar(40) NOT NULL,
  payload longtext NOT NULL,
  fetched_at timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_weather_grid (grid_key),
  KEY idx_weather_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
