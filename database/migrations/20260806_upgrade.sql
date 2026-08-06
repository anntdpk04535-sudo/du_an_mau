-- Additive upgrade for Dak Lak Travel AI.
-- Safe to run through scripts/migrate_upgrade.php; all statements are idempotent.

CREATE TABLE IF NOT EXISTS schema_migrations (
  version varchar(120) NOT NULL PRIMARY KEY,
  applied_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE destinations
  ADD COLUMN IF NOT EXISTS province varchar(100) DEFAULT NULL AFTER address,
  ADD COLUMN IF NOT EXISTS opening_hours text DEFAULT NULL AFTER province,
  ADD COLUMN IF NOT EXISTS entrance_fee_min decimal(12,2) DEFAULT NULL AFTER opening_hours,
  ADD COLUMN IF NOT EXISTS entrance_fee_max decimal(12,2) DEFAULT NULL AFTER entrance_fee_min,
  ADD COLUMN IF NOT EXISTS contact_phone varchar(50) DEFAULT NULL AFTER entrance_fee_max,
  ADD COLUMN IF NOT EXISTS source_url varchar(500) DEFAULT NULL AFTER contact_phone,
  ADD COLUMN IF NOT EXISTS last_verified_at date DEFAULT NULL AFTER source_url,
  ADD COLUMN IF NOT EXISTS updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER created_at;

CREATE TABLE IF NOT EXISTS destination_images (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  destination_id int NOT NULL,
  image_url varchar(500) NOT NULL,
  alt_text varchar(255) DEFAULT NULL,
  caption varchar(500) DEFAULT NULL,
  is_primary tinyint(1) NOT NULL DEFAULT 0,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  KEY idx_destination_images_destination (destination_id),
  CONSTRAINT fk_destination_images_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS foods (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  destination_id int DEFAULT NULL,
  entity_type enum('dish','restaurant','cafe') NOT NULL DEFAULT 'dish',
  name varchar(200) NOT NULL,
  name_en varchar(255) DEFAULT NULL,
  slug varchar(220) NOT NULL,
  description text DEFAULT NULL,
  description_en text DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  latitude decimal(10,6) DEFAULT NULL,
  longitude decimal(10,6) DEFAULT NULL,
  price_min decimal(12,2) DEFAULT NULL,
  price_max decimal(12,2) DEFAULT NULL,
  opening_hours text DEFAULT NULL,
  contact_phone varchar(50) DEFAULT NULL,
  source_url varchar(500) DEFAULT NULL,
  last_verified_at date DEFAULT NULL,
  status enum('draft','published') NOT NULL DEFAULT 'published',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY uq_foods_slug (slug),
  KEY idx_foods_destination (destination_id),
  KEY idx_foods_type_status (entity_type,status),
  CONSTRAINT fk_foods_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS food_images (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  food_id int NOT NULL,
  image_url varchar(500) NOT NULL,
  alt_text varchar(255) DEFAULT NULL,
  caption varchar(500) DEFAULT NULL,
  is_primary tinyint(1) NOT NULL DEFAULT 0,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  KEY idx_food_images_food (food_id),
  CONSTRAINT fk_food_images_food FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accommodations (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  destination_id int DEFAULT NULL,
  accommodation_type enum('homestay','hotel','resort') NOT NULL DEFAULT 'homestay',
  name varchar(200) NOT NULL,
  name_en varchar(255) DEFAULT NULL,
  slug varchar(220) NOT NULL,
  description text DEFAULT NULL,
  description_en text DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  latitude decimal(10,6) DEFAULT NULL,
  longitude decimal(10,6) DEFAULT NULL,
  price_min decimal(12,2) DEFAULT NULL,
  price_max decimal(12,2) DEFAULT NULL,
  amenities text DEFAULT NULL,
  contact_phone varchar(50) DEFAULT NULL,
  source_url varchar(500) DEFAULT NULL,
  last_verified_at date DEFAULT NULL,
  status enum('draft','published') NOT NULL DEFAULT 'published',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY uq_accommodations_slug (slug),
  KEY idx_accommodations_destination (destination_id),
  CONSTRAINT fk_accommodations_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accommodation_images (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  accommodation_id int NOT NULL,
  image_url varchar(500) NOT NULL,
  alt_text varchar(255) DEFAULT NULL,
  caption varchar(500) DEFAULT NULL,
  is_primary tinyint(1) NOT NULL DEFAULT 0,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  KEY idx_accommodation_images_accommodation (accommodation_id),
  CONSTRAINT fk_accommodation_images_accommodation FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reviews
  ADD COLUMN IF NOT EXISTS updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

CREATE TABLE IF NOT EXISTS review_images (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  review_id int NOT NULL,
  image_url varchar(500) NOT NULL,
  alt_text varchar(255) DEFAULT NULL,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  KEY idx_review_images_review (review_id),
  CONSTRAINT fk_review_images_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_documents (
  id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type varchar(40) NOT NULL,
  entity_id bigint NOT NULL,
  locale varchar(10) NOT NULL DEFAULT 'vi',
  content longtext NOT NULL,
  content_hash char(64) NOT NULL,
  embedding_model varchar(80) DEFAULT NULL,
  embedding_json longtext DEFAULT NULL,
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY uq_search_document_entity_locale (entity_type,entity_id,locale),
  KEY idx_search_documents_type (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE itineraries
  ADD COLUMN IF NOT EXISTS version int NOT NULL DEFAULT 1 AFTER ai_raw_response,
  ADD COLUMN IF NOT EXISTS updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

ALTER TABLE itinerary_items
  ADD COLUMN IF NOT EXISTS food_id int DEFAULT NULL AFTER destination_id,
  ADD COLUMN IF NOT EXISTS accommodation_id int DEFAULT NULL AFTER food_id,
  ADD COLUMN IF NOT EXISTS reason text DEFAULT NULL AFTER activity,
  ADD COLUMN IF NOT EXISTS suggestion text DEFAULT NULL AFTER reason,
  ADD COLUMN IF NOT EXISTS community_impact text DEFAULT NULL AFTER suggestion,
  ADD COLUMN IF NOT EXISTS price_min decimal(12,2) DEFAULT NULL AFTER community_impact,
  ADD COLUMN IF NOT EXISTS price_max decimal(12,2) DEFAULT NULL AFTER price_min,
  ADD COLUMN IF NOT EXISTS is_locked tinyint(1) NOT NULL DEFAULT 0 AFTER price_max,
  ADD KEY IF NOT EXISTS idx_itinerary_items_food (food_id),
  ADD KEY IF NOT EXISTS idx_itinerary_items_accommodation (accommodation_id);

INSERT IGNORE INTO destination_images (destination_id,image_url,alt_text,is_primary,sort_order)
SELECT id,image_url,name,1,0 FROM destinations
WHERE image_url IS NOT NULL AND image_url <> ''
  AND NOT EXISTS (SELECT 1 FROM destination_images di WHERE di.destination_id=destinations.id);
