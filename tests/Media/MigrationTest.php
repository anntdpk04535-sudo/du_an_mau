<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class MigrationTest extends TestCase
{
    public function test_ba_bang_media_ton_tai(): void
    {
        foreach (['media_assets', 'media_links', 'media_backfill_queue'] as $table) {
            $found = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $found->execute([$table]);
            self::assertSame(1, (int)$found->fetchColumn(), "thiếu bảng {$table}");
        }
    }

    public function test_content_hash_la_unique(): void
    {
        $this->resetTables(['media_links', 'media_assets']);
        $insert = $this->db->prepare(
            'INSERT INTO media_assets (storage_path, content_hash, source) VALUES (?, ?, ?)'
        );
        $insert->execute(['/assets/images/media/aa/bb/x.jpg', str_repeat('a', 64), 'upload']);

        $this->expectExceptionMessageMatches('/Duplicate|1062/');
        $insert->execute(['/assets/images/media/cc/dd/y.jpg', str_repeat('a', 64), 'upload']);
    }

    public function test_nhieu_ban_ghi_places_khong_co_hash_van_hop_le(): void
    {
        $this->resetTables(['media_links', 'media_assets']);
        $insert = $this->db->prepare(
            'INSERT INTO media_assets (content_hash, source, place_photo_ref) VALUES (NULL, ?, ?)'
        );
        $insert->execute(['google_places', 'ref-1']);
        $insert->execute(['google_places', 'ref-2']);

        self::assertSame(2, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_assets WHERE source='google_places'"
        )->fetchColumn());
    }

    public function test_accommodations_co_cho_chua_du_kien_places(): void
    {
        $columns = $this->db->query('SHOW COLUMNS FROM accommodations')->fetchAll(\PDO::FETCH_COLUMN, 0);
        foreach (['place_id', 'rating', 'rating_count', 'price_level', 'opening_hours', 'place_synced_at'] as $column) {
            self::assertContains($column, $columns, "thiếu cột accommodations.{$column}");
        }
    }
}
