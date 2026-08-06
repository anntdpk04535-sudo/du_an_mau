-- Nền tảng media dùng chung. Idempotent, chạy lại an toàn.

CREATE TABLE IF NOT EXISTS media_assets (
  id               bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  storage_path     varchar(300) DEFAULT NULL,
  content_hash     char(64) DEFAULT NULL,
  mime             varchar(40) DEFAULT NULL,
  width            int DEFAULT NULL,
  height           int DEFAULT NULL,
  bytes            int DEFAULT NULL,
  variants         varchar(200) DEFAULT NULL,
  source           enum('wikimedia','unsplash','google_places','upload') NOT NULL,
  source_url       varchar(600) DEFAULT NULL,
  author           varchar(200) DEFAULT NULL,
  license          varchar(80) DEFAULT NULL,
  license_url      varchar(300) DEFAULT NULL,
  attribution_text varchar(400) DEFAULT NULL,
  place_photo_ref  varchar(600) DEFAULT NULL,
  fetched_at       timestamp NULL DEFAULT NULL,
  created_at       timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_media_hash (content_hash),
  KEY idx_media_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_links (
  id           bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  asset_id     bigint NOT NULL,
  entity_type  varchar(32) NOT NULL,
  entity_id    bigint NOT NULL,
  role         enum('primary','gallery') NOT NULL DEFAULT 'gallery',
  authenticity enum('actual','illustrative') NOT NULL DEFAULT 'illustrative',
  alt_text     varchar(255) DEFAULT NULL,
  caption      varchar(500) DEFAULT NULL,
  sort_order   int NOT NULL DEFAULT 0,
  created_at   timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_link (entity_type, entity_id, asset_id),
  KEY idx_entity (entity_type, entity_id, role, sort_order),
  CONSTRAINT fk_media_links_asset FOREIGN KEY (asset_id)
    REFERENCES media_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_backfill_queue (
  id            bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type   varchar(32) NOT NULL,
  entity_id     bigint NOT NULL,
  status        enum('pending','done','failed') NOT NULL DEFAULT 'pending',
  attempts      int NOT NULL DEFAULT 0,
  source_tried  varchar(120) DEFAULT NULL,
  last_error    varchar(500) DEFAULT NULL,
  next_retry_at timestamp NULL DEFAULT NULL,
  updated_at    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY uq_queue_entity (entity_type, entity_id),
  KEY idx_queue_status (status, next_retry_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
