-- Chạy file này nếu bạn ĐÃ có database daklak_travel từ trước
-- (database mới tạo từ schema.sql thì đã có sẵn cột này, không cần chạy).

ALTER TABLE itinerary_items
  ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER activity;
