-- Chỗ chứa dữ kiện lấy từ Google Places. MariaDB hỗ trợ IF NOT EXISTS nên chạy lại an toàn.
ALTER TABLE accommodations
  ADD COLUMN IF NOT EXISTS place_id      varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS rating        decimal(2,1) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS rating_count  int DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS price_level   tinyint DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS opening_hours text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS place_synced_at timestamp NULL DEFAULT NULL;

ALTER TABLE destinations ADD COLUMN IF NOT EXISTS place_id varchar(255) DEFAULT NULL;
ALTER TABLE foods        ADD COLUMN IF NOT EXISTS place_id varchar(255) DEFAULT NULL;
