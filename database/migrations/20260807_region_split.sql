-- Đặt tên 20260807 dù nội dung viết ngày 20260806: migration này ALTER cột
-- `region ... AFTER province` trên destinations, mà cột `province` do
-- 20260806_upgrade.sql tạo ra. Migration nào cũng chạy theo thứ tự tên file
-- (glob + sort), nên file phụ thuộc phải mang tên sắp SAU file nó phụ thuộc.
-- Đừng "sửa lại cho đúng ngày viết" — làm vậy sẽ tái tạo lỗi thứ tự khiến
-- migration này sập trên DB test sạch (cột `province` chưa tồn tại).
--
-- Additive upgrade: regional split for Dak Lak Travel AI.
-- ĐÁ route: TP Buôn Ma Thuột and central/eastern districts (Krông Pác, Ea Kar,
-- M'Dra', Ea H'leo, Krông Năng, Cư M'gar...). TÂY: western/southern districts
-- (Buôn Đôn, Ea Súp, Lắk, Krông Bông, Krông Ana, Cư Kuin...).
-- Safe to run; all statements are idempotent.

ALTER TABLE destinations
  ADD COLUMN IF NOT EXISTS region enum('east','west') DEFAULT NULL AFTER province;

ALTER TABLE foods
  ADD COLUMN IF NOT EXISTS region enum('east','west') DEFAULT NULL AFTER destination_id;

ALTER TABLE accommodations
  ADD COLUMN IF NOT EXISTS region enum('east','west') DEFAULT NULL AFTER destination_id;

CREATE INDEX IF NOT EXISTS idx_destinations_region ON destinations(region);
CREATE INDEX IF NOT EXISTS idx_foods_region ON foods(region);
CREATE INDEX IF NOT EXISTS idx_accommodations_region ON accommodations(region);