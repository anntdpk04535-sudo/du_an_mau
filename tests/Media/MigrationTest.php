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

    /**
     * Case (a) reviewer yêu cầu: statement chỉ có MỘT clause DDL, lỗi "đã
     * tồn tại" (1060 cột trùng tên) vẫn phải được coi là vô hại — migration
     * hoàn tất và version được ghi vào schema_migrations, đúng hành vi gốc.
     */
    public function test_loi_don_clause_da_ton_tai_van_duoc_bo_qua_va_ghi_hoan_tat(): void
    {
        $table = 'test_single_clause_' . bin2hex(random_bytes(4));
        $version = 'test_single_clause_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            $this->db->exec("CREATE TABLE `{$table}` (id int PRIMARY KEY, note varchar(10) DEFAULT NULL)");
            // Cột `note` đã tồn tại sẵn — statement dưới đây chỉ có MỘT clause
            // DDL (`ADD COLUMN`) nên lỗi 1060 phải được coi là vô hại.
            file_put_contents($path, "ALTER TABLE `{$table}` ADD COLUMN note varchar(10) DEFAULT NULL;");

            $applied = \runMigrationFile($this->db, $path, $version);

            self::assertTrue($applied, 'migration đơn-clause với lỗi "đã tồn tại" phải được coi là hoàn tất');
            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(1, (int)$seen->fetchColumn(), 'version phải được ghi vào schema_migrations');
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * Case (b) reviewer yêu cầu: một ALTER TABLE NHIỀU clause là một thao
     * tác nguyên tử trong MariaDB — nếu một clause lỗi "đã tồn tại", TOÀN
     * BỘ statement bị huỷ (kể cả các clause khác chưa từng lỗi). Vì vậy lỗi
     * này KHÔNG được coi là vô hại: phải ném ra ngoài, và version KHÔNG
     * được ghi vào schema_migrations (migration coi như chưa xong).
     */
    public function test_loi_alter_table_nhieu_clause_khong_duoc_bo_qua_va_khong_ghi_hoan_tat(): void
    {
        $table = 'test_multi_clause_' . bin2hex(random_bytes(4));
        $version = 'test_multi_clause_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            $this->db->exec("CREATE TABLE `{$table}` (id int PRIMARY KEY, col_a varchar(10) DEFAULT NULL)");
            // `col_a` đã tồn tại (lỗi 1060 ở clause đầu), `col_b` thì chưa —
            // nhưng vì đây là MỘT statement 2 clause, lỗi ở clause đầu phải
            // làm hỏng luôn khả năng áp dụng clause sau.
            file_put_contents(
                $path,
                "ALTER TABLE `{$table}` ADD COLUMN col_a varchar(10) DEFAULT NULL, ADD COLUMN col_b varchar(10) DEFAULT NULL;"
            );

            $threw = false;
            try {
                \runMigrationFile($this->db, $path, $version);
            } catch (\PDOException $e) {
                $threw = true;
            }
            self::assertTrue($threw, 'ALTER TABLE nhiều clause lỗi "đã tồn tại" phải ném lỗi ra ngoài, không được nuốt');

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(0, (int)$seen->fetchColumn(), 'version KHÔNG được ghi vào schema_migrations khi migration chưa thật sự xong');
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    public function test_parse_cli_options_doc_dung_dry_run_va_limit(): void
    {
        self::assertSame(
            ['dryRun' => false, 'limit' => null],
            \parseMigrateCliOptions(['migrate_media.php'])
        );
        self::assertSame(
            ['dryRun' => true, 'limit' => null],
            \parseMigrateCliOptions(['migrate_media.php', '--dry-run'])
        );
        self::assertSame(
            ['dryRun' => false, 'limit' => 3],
            \parseMigrateCliOptions(['migrate_media.php', '--limit=3'])
        );
        self::assertSame(
            ['dryRun' => true, 'limit' => 2],
            \parseMigrateCliOptions(['migrate_media.php', '--dry-run', '--limit=2'])
        );
    }

    public function test_dry_run_khong_ap_dung_gi_va_khong_ghi_schema_migrations(): void
    {
        $table1 = 'test_dryrun_1_' . bin2hex(random_bytes(4));
        $version1 = 'test_dryrun_1_' . bin2hex(random_bytes(4));
        $path1 = sys_get_temp_dir() . '/' . $version1 . '.sql';

        try {
            file_put_contents($path1, "CREATE TABLE IF NOT EXISTS `{$table1}` (id int PRIMARY KEY);");

            $log = \runPendingMigrations($this->db, [$path1], true, null);

            self::assertSame(['SẼ ÁP DỤNG  ' . $version1], $log);

            $exists = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $exists->execute([$table1]);
            self::assertSame(0, (int)$exists->fetchColumn(), '--dry-run không được thực sự tạo bảng');

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version1]);
            self::assertSame(0, (int)$seen->fetchColumn(), '--dry-run không được ghi schema_migrations');
        } finally {
            @unlink($path1);
            $this->db->exec("DROP TABLE IF EXISTS `{$table1}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version1));
        }
    }

    public function test_limit_chi_ap_toi_da_so_migration_dang_cho(): void
    {
        $table1 = 'test_limit_1_' . bin2hex(random_bytes(4));
        $table2 = 'test_limit_2_' . bin2hex(random_bytes(4));
        $version1 = 'test_limit_1_' . bin2hex(random_bytes(4));
        $version2 = 'test_limit_2_' . bin2hex(random_bytes(4));
        $path1 = sys_get_temp_dir() . '/' . $version1 . '.sql';
        $path2 = sys_get_temp_dir() . '/' . $version2 . '.sql';

        try {
            file_put_contents($path1, "CREATE TABLE IF NOT EXISTS `{$table1}` (id int PRIMARY KEY);");
            file_put_contents($path2, "CREATE TABLE IF NOT EXISTS `{$table2}` (id int PRIMARY KEY);");

            $log = \runPendingMigrations($this->db, [$path1, $path2], false, 1);

            self::assertSame(['ĐÃ ÁP DỤNG  ' . $version1], $log, '--limit=1 chỉ được xử lý 1 migration đang chờ');

            $seen1 = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen1->execute([$version1]);
            self::assertSame(1, (int)$seen1->fetchColumn());

            $seen2 = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen2->execute([$version2]);
            self::assertSame(0, (int)$seen2->fetchColumn(), 'migration thứ 2 phải còn đang chờ, chưa được áp');
        } finally {
            @unlink($path1);
            @unlink($path2);
            $this->db->exec("DROP TABLE IF EXISTS `{$table1}`");
            $this->db->exec("DROP TABLE IF EXISTS `{$table2}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version1));
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version2));
        }
    }
}
