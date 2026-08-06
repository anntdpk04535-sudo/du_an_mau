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

    /**
     * Hướng 1 reviewer chứng minh bằng thực nghiệm: dấu ')' nằm TRONG một
     * chuỗi literal (`DEFAULT 'closed)temporarily'`) từng bị đếm nhầm là
     * đóng ngoặc, kéo $depth xuống -1, khiến dấu phẩy ngăn cách 2 clause
     * thật KHÔNG còn được đếm ở $depth === 0 — statement 2 clause bị nhận
     * nhầm là 1 clause, tức lỗi "đã tồn tại" lại bị nuốt sai (thủng lại
     * đúng lỗ hổng Important #1 vừa vá).
     */
    public function test_dem_clause_khong_bi_nham_boi_dau_ngoac_dong_trong_chuoi_literal(): void
    {
        $statement = "ALTER TABLE destinations\n"
            . "  ADD COLUMN note varchar(50) DEFAULT 'closed)temporarily',\n"
            . "  ADD COLUMN extra varchar(50) DEFAULT NULL";

        self::assertFalse(
            \statementHasSingleDdlClause($statement),
            "statement thực chất có 2 clause dù chuỗi literal chứa ')'"
        );
    }

    /**
     * Hướng 2 reviewer chứng minh: dấu ',' nằm TRONG một chuỗi literal
     * (`DEFAULT 'ok,pending'`) từng bị đếm nhầm là ranh giới clause ở
     * $depth === 0, khiến statement 1 clause bị nhận nhầm là nhiều clause —
     * phá tính idempotent (chạy lại migration lần 2 sẽ ném lỗi ra ngoài
     * thay vì được nuốt như lần đầu).
     */
    public function test_dem_clause_khong_bi_nham_boi_dau_phay_trong_chuoi_literal(): void
    {
        $statement = "ALTER TABLE destinations ADD COLUMN note varchar(50) DEFAULT 'ok,pending'";

        self::assertTrue(
            \statementHasSingleDdlClause($statement),
            "statement thực chất chỉ có 1 clause dù chuỗi literal chứa ','"
        );
    }

    /**
     * 3 ca hồi quy đã đúng ở bản trước (dấu ',' bên trong ngoặc `()` của cú
     * pháp SQL hợp lệ, KHÔNG phải trong string literal) — phải tiếp tục
     * đúng sau khi đổi statementHasSingleDdlClause() sang dùng sqlCharStates()
     * dùng chung với bộ tách statement.
     */
    public function test_dem_clause_van_dung_voi_cac_ca_hoi_quy_da_biet(): void
    {
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE foo ADD KEY idx (a, b)'),
            "dấu ',' trong ngoặc () của ADD KEY không được tính là ranh giới clause"
        );
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE foo ADD COLUMN price DECIMAL(12,2)'),
            "dấu ',' trong DECIMAL(12,2) không được tính là ranh giới clause"
        );
        self::assertTrue(
            \statementHasSingleDdlClause("ALTER TABLE foo ADD COLUMN status enum('a','b') DEFAULT 'a'"),
            "dấu ',' trong enum('a','b') không được tính là ranh giới clause"
        );
    }

    /**
     * Case (c) reviewer nêu: dấu ';' nằm TRONG một chuỗi literal không được
     * bộ tách statement coi là ranh giới statement — trước đây bộ tách chỉ
     * lọc dòng bắt đầu bằng `--` rồi explode(';') thô, không biết gì về
     * string literal.
     */
    public function test_tach_statement_khong_bi_cat_nham_boi_dau_cham_phay_trong_chuoi_literal(): void
    {
        $sql = "INSERT INTO foo (note) VALUES ('a;b');\nINSERT INTO foo (note) VALUES ('c');";

        $statements = \splitSqlStatements($sql);

        self::assertSame(
            ["INSERT INTO foo (note) VALUES ('a;b')", "INSERT INTO foo (note) VALUES ('c')"],
            $statements,
            "dấu ';' trong chuỗi literal không được coi là ranh giới statement"
        );
    }

    /**
     * Case (d) reviewer nêu: dấu ';' nằm TRONG một comment khối `/* ... * /`
     * (bỏ khoảng trắng giữa `*` và `/` khi đọc) nhiều dòng không được coi là
     * ranh giới statement — bản trước chỉ lọc DÒNG bắt đầu bằng `--`, không
     * xử lý comment khối, nên vẫn cắt sai ở trường hợp này.
     */
    public function test_tach_statement_khong_bi_cat_nham_boi_dau_cham_phay_trong_comment_khoi(): void
    {
        $sql = "/* ghi chú nhiều dòng có dấu ;\n   vẫn tiếp tục ở đây */\nCREATE TABLE foo (id int);";

        $statements = \splitSqlStatements($sql);

        self::assertCount(1, $statements, "comment khối chứa ';' không được tách thành statement riêng");
        self::assertStringContainsString('CREATE TABLE foo', $statements[0]);
    }

    /**
     * Một statement toàn comment (không còn SQL thật sau khi loại comment)
     * đứng ngay sau statement cuối cùng của file không được đưa vào danh
     * sách statement để exec() — gửi statement rỗng/toàn-comment cho
     * MariaDB sẽ bị lỗi 1065 "Query was empty".
     */
    public function test_migration_co_comment_don_o_cuoi_file_khong_bi_loi_query_rong(): void
    {
        $table = 'test_trailingcomment_' . bin2hex(random_bytes(4));
        $version = 'test_trailingcomment_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            file_put_contents(
                $path,
                "CREATE TABLE IF NOT EXISTS `{$table}` (id int PRIMARY KEY);\n-- Hết migration, không còn gì thêm\n"
            );

            $applied = \runMigrationFile($this->db, $path, $version);

            self::assertTrue($applied, 'migration có comment đơn ở cuối vẫn phải áp thành công');
            $exists = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $exists->execute([$table]);
            self::assertSame(1, (int)$exists->fetchColumn());
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * Nhỏ 2 reviewer yêu cầu: `--limit=abc` (giá trị không phải số) trước
     * đây bị lờ đi im lặng (regex không khớp, $limit giữ null, chạy KHÔNG
     * giới hạn) — im lặng theo hướng nguy hiểm hơn vì người gõ nhầm tưởng
     * mình đang giới hạn. Giờ phải ném lỗi rõ ràng.
     */
    public function test_parse_cli_options_nem_loi_khi_limit_khong_phai_so(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \parseMigrateCliOptions(['migrate_media.php', '--limit=abc']);
    }

    /**
     * Nhỏ 1 reviewer yêu cầu: `--dry-run` phải trung thực tuyệt đối — kể cả
     * khi bảng schema_migrations CHƯA TỪNG tồn tại (chưa ai chạy migration
     * thật lần nào), --dry-run vẫn không được tự tạo bảng đó. Test này xoá
     * tạm bảng schema_migrations khỏi DB test rồi phục hồi đầy đủ (kể cả dữ
     * liệu) ở khối finally — DB test được giữ xuyên suốt giữa các lần chạy
     * phpunit nên bắt buộc phải khôi phục đúng, kể cả khi assertion fail.
     */
    public function test_dry_run_khi_bang_schema_migrations_chua_ton_tai_khong_tu_tao_bang(): void
    {
        $backupRows = $this->db->query('SELECT version, applied_at FROM schema_migrations')->fetchAll(\PDO::FETCH_ASSOC);

        $table1 = 'test_dryrun_notable_' . bin2hex(random_bytes(4));
        $version1 = 'test_dryrun_notable_' . bin2hex(random_bytes(4));
        $path1 = sys_get_temp_dir() . '/' . $version1 . '.sql';

        try {
            $this->db->exec('DROP TABLE IF EXISTS schema_migrations');

            file_put_contents($path1, "CREATE TABLE IF NOT EXISTS `{$table1}` (id int PRIMARY KEY);");

            $log = \runPendingMigrations($this->db, [$path1], true, null);

            self::assertSame(
                ['SẼ ÁP DỤNG  ' . $version1],
                $log,
                '--dry-run vẫn phải coi migration là đang chờ khi bảng schema_migrations chưa tồn tại'
            );

            $existsAfter = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'"
            )->fetchColumn();
            self::assertSame(0, (int)$existsAfter, '--dry-run không được tự tạo bảng schema_migrations khi nó chưa tồn tại');
        } finally {
            @unlink($path1);
            $this->db->exec("DROP TABLE IF EXISTS `{$table1}`");
            // Khôi phục schema_migrations về đúng trạng thái trước test.
            ensureSchemaMigrationsTable($this->db);
            if ($backupRows !== false) {
                $insert = $this->db->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
                foreach ($backupRows as $row) {
                    $insert->execute([$row['version'], $row['applied_at']]);
                }
            }
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

    /**
     * Important vòng 3 reviewer nêu: statementHasSingleDdlClause() dùng
     * sqlCharStates() để đếm '('/')'/',' nhưng regex `^\s*ALTER\s+TABLE\b`
     * vẫn chạy trên $statement GỐC — nếu statement (lấy từ splitSqlStatements())
     * có một comment `--` đứng ngay trước, không có ';' xen giữa (đúng như
     * dòng đầu database/migrations/20260807_place_facts.sql), regex không
     * khớp vì comment đứng chắn phía trước, rơi vào nhánh `return true` mặc
     * định — bỏ qua đếm clause hoàn toàn. Case dưới đây tái hiện đúng dạng đó:
     * comment dòng ngay trước ALTER TABLE 2-clause, một clause lỗi "đã tồn
     * tại" — lỗi phải vẫn được ném ra ngoài, KHÔNG được nuốt sai.
     */
    public function test_comment_dong_ngay_truoc_alter_table_nhieu_clause_khong_bi_nuot_loi(): void
    {
        $table = 'test_leadcomment_dash_' . bin2hex(random_bytes(4));
        $version = 'test_leadcomment_dash_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            $this->db->exec("CREATE TABLE `{$table}` (id int PRIMARY KEY, col_a varchar(10) DEFAULT NULL)");
            // `col_a` đã tồn tại (lỗi 1060 ở clause đầu) — comment `--` đứng
            // ngay trước ALTER TABLE, không có ';' xen giữa, đúng hình dạng
            // thật của 20260807_place_facts.sql.
            file_put_contents(
                $path,
                "-- comment ngay truoc ALTER TABLE, khong co ';' xen giua\n"
                . "ALTER TABLE `{$table}` ADD COLUMN col_a varchar(10) DEFAULT NULL, ADD COLUMN col_b varchar(10) DEFAULT NULL;"
            );

            $threw = false;
            try {
                \runMigrationFile($this->db, $path, $version);
            } catch (\PDOException $e) {
                $threw = true;
            }
            self::assertTrue(
                $threw,
                'ALTER TABLE nhiều clause đứng ngay sau comment dòng "--" vẫn phải bị nhận diện đúng và ném lỗi, không được nuốt'
            );

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(0, (int)$seen->fetchColumn(), 'version KHÔNG được ghi vào schema_migrations khi migration chưa thật sự xong');
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * Biến thể của test trên, dùng comment khối `/ * ... * /` nhiều dòng thay
     * vì `--`, cũng đứng ngay trước ALTER TABLE không có ';' xen giữa. Cùng
     * gốc bug, khác dạng comment.
     */
    public function test_comment_khoi_ngay_truoc_alter_table_nhieu_clause_khong_bi_nuot_loi(): void
    {
        $table = 'test_leadcomment_block_' . bin2hex(random_bytes(4));
        $version = 'test_leadcomment_block_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            $this->db->exec("CREATE TABLE `{$table}` (id int PRIMARY KEY, col_a varchar(10) DEFAULT NULL)");
            file_put_contents(
                $path,
                "/* comment khoi nhieu dong\n   ngay truoc ALTER TABLE, khong co ';' xen giua */\n"
                . "ALTER TABLE `{$table}` ADD COLUMN col_a varchar(10) DEFAULT NULL, ADD COLUMN col_b varchar(10) DEFAULT NULL;"
            );

            $threw = false;
            try {
                \runMigrationFile($this->db, $path, $version);
            } catch (\PDOException $e) {
                $threw = true;
            }
            self::assertTrue(
                $threw,
                'ALTER TABLE nhiều clause đứng ngay sau comment khối "/* */" vẫn phải bị nhận diện đúng và ném lỗi, không được nuốt'
            );

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(0, (int)$seen->fetchColumn(), 'version KHÔNG được ghi vào schema_migrations khi migration chưa thật sự xong');
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * Test canary: chạy statementHasSingleDdlClause() trực tiếp trên văn bản
     * statement THẬT trích từ các file migration đang được track trong repo
     * (không phải chuỗi tự bịa trong test) — bắt mọi lần tái phát cùng loại
     * bug về sau, vì nó đọc thẳng file thật thay vì mô phỏng.
     *
     * (a) Statement đầu của 20260807_place_facts.sql: comment "--" đứng ngay
     *     trước, ALTER TABLE accommodations có 6 clause ADD COLUMN.
     * (b) Statement ALTER TABLE itinerary_items trong 20260806_upgrade.sql:
     *     đứng sau dấu ';' thật (không phải comment), 10 clause (8 ADD COLUMN
     *     + 2 ADD KEY) — ca hồi quy đối chứng cho (a).
     */
    public function test_canary_cac_statement_alter_table_nhieu_clause_that_trong_file_migration(): void
    {
        $migrationsDir = __DIR__ . '/../../database/migrations';

        $placeFactsSql = (string)file_get_contents($migrationsDir . '/20260807_place_facts.sql');
        self::assertNotSame('', $placeFactsSql, 'không đọc được 20260807_place_facts.sql');
        $placeFactsStatements = \splitSqlStatements($placeFactsSql);
        self::assertStringContainsString(
            'ALTER TABLE accommodations',
            $placeFactsStatements[0],
            'statement đầu tiên của 20260807_place_facts.sql phải là ALTER TABLE accommodations'
        );
        self::assertFalse(
            \statementHasSingleDdlClause($placeFactsStatements[0]),
            'ALTER TABLE accommodations (6 clause, đứng ngay sau comment "--") phải được nhận diện là nhiều clause'
        );

        $upgradeSql = (string)file_get_contents($migrationsDir . '/20260806_upgrade.sql');
        self::assertNotSame('', $upgradeSql, 'không đọc được 20260806_upgrade.sql');
        $upgradeStatements = \splitSqlStatements($upgradeSql);
        $itineraryItemsStatement = null;
        foreach ($upgradeStatements as $statement) {
            if (preg_match('/^\s*ALTER\s+TABLE\s+itinerary_items\b/i', $statement)) {
                $itineraryItemsStatement = $statement;
                break;
            }
        }
        self::assertNotNull($itineraryItemsStatement, 'không tìm thấy statement ALTER TABLE itinerary_items trong 20260806_upgrade.sql');
        self::assertFalse(
            \statementHasSingleDdlClause($itineraryItemsStatement),
            'ALTER TABLE itinerary_items (10 clause: 8 ADD COLUMN + 2 ADD KEY) phải được nhận diện là nhiều clause'
        );
    }

    // ---------------------------------------------------------------------
    // Vòng sửa 4 — sqlCharStates() mô hình hoá thiếu ngữ cảnh trích dẫn của
    // MariaDB. Mỗi dạng trích dẫn chưa được mô hình hoá là một lần trạng thái
    // lệch pha 'code' <-> 'string', kéo sập cả bộ tách statement lẫn bộ đếm
    // clause xây bên trên. Các test dưới đây khoá lại từng dạng.
    // ---------------------------------------------------------------------

    /**
     * F1 — MariaDB mặc định BẬT backslash escape (`sql_mode` không chứa
     * `NO_BACKSLASH_ESCAPES`; đã xác minh trên MariaDB 10.4.28 của máy này:
     * `SELECT 'M\'gar'` trả về `M'gar`). Nên `'Cư M\'gar'` là MỘT chuỗi.
     * Bộ quét cũ chỉ hiểu escape `''` nên `\'` đóng chuỗi sớm và đảo pha toàn
     * bộ phần SQL đứng sau.
     *
     * Không phải rủi ro lý thuyết: `database/daklak_travel.sql` có 128 lần
     * `\'`, và địa danh Đắk Lắk đầy dấu nháy (`Cư M'gar`, `Ea M'roh`, `M'rô`).
     */
    public function test_backslash_escape_khong_dao_pha_bo_dem_clause(): void
    {
        self::assertFalse(
            \statementHasSingleDdlClause(
                "ALTER TABLE destinations\n"
                . "  ADD COLUMN huyen varchar(50) DEFAULT 'Cư M\\'gar',\n"
                . "  ADD COLUMN ghi_chu varchar(50) DEFAULT NULL"
            ),
            "statement thực chất có 2 clause dù literal chứa backslash escape \\'"
        );

        self::assertTrue(
            \statementHasSingleDdlClause(
                "ALTER TABLE destinations ADD COLUMN huyen varchar(80) DEFAULT 'Ea H\\'leo, Krông Năng'"
            ),
            "statement thực chất chỉ có 1 clause: dấu ',' nằm trong literal có backslash escape"
        );
    }

    /**
     * F1 (tiếp) — dấu `;` phân cách hai statement bị che thành 'string' khi
     * `\'` đảo pha bộ quét, làm `splitSqlStatements()` gộp nhầm hai statement
     * làm một.
     */
    public function test_backslash_escape_khong_lam_gop_nham_hai_statement(): void
    {
        $sql = "-- Backfill tên huyện, dữ liệu thật có dấu nháy trong tên.\n"
            . "UPDATE destinations SET huyen = 'Cư M\\'gar' WHERE id = 1;\n"
            . "UPDATE destinations SET huyen = 'Ea M\\'roh' WHERE id = 2;";

        $statements = \splitSqlStatements($sql);

        self::assertCount(2, $statements, "backslash escape không được làm gộp nhầm hai statement");
        self::assertStringContainsString("WHERE id = 1", $statements[0]);
        self::assertStringContainsString("WHERE id = 2", $statements[1]);
    }

    /**
     * F1 (bất biến thật) — đây là hậu quả nghiêm trọng nhất của việc lệch
     * pha: một `ALTER TABLE` NHIỀU clause chứa `\'` bị đếm nhầm thành 1
     * clause, nên lỗi "đã tồn tại" bị NUỐT, và `schema_migrations` được ghi
     * dù cột thứ hai chưa hề tồn tại (ALTER TABLE nhiều clause là nguyên tử —
     * một clause lỗi thì cả statement bị huỷ).
     */
    public function test_alter_table_nhieu_clause_co_backslash_escape_khong_bi_nuot_loi(): void
    {
        $table = 'test_bs_multi_' . bin2hex(random_bytes(4));
        $version = 'test_bs_multi_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            $this->db->exec("CREATE TABLE `{$table}` (id int PRIMARY KEY, huyen varchar(50) DEFAULT NULL)");
            // `huyen` đã tồn tại (lỗi 1060 ở clause đầu), `ghi_chu` thì chưa.
            file_put_contents(
                $path,
                "-- Backfill theo tên huyện; tên thật có dấu nháy nên phải escape.\n"
                . "ALTER TABLE `{$table}`\n"
                . "  ADD COLUMN huyen varchar(50) DEFAULT 'Cư M\\'gar',\n"
                . "  ADD COLUMN ghi_chu varchar(50) DEFAULT NULL;"
            );

            $threw = false;
            try {
                \runMigrationFile($this->db, $path, $version);
            } catch (\PDOException $e) {
                $threw = true;
            }
            self::assertTrue(
                $threw,
                'ALTER TABLE nhiều clause chứa backslash escape phải ném lỗi ra ngoài, không được nuốt'
            );

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(0, (int)$seen->fetchColumn(), 'version KHÔNG được ghi khi migration chưa thật sự xong');

            $col = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
            );
            $col->execute([$table, 'ghi_chu']);
            self::assertSame(0, (int)$col->fetchColumn(), 'cột thứ hai thật sự KHÔNG được tạo — đúng bản chất nguyên tử của ALTER TABLE');
        } finally {
            @unlink($path);
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * F2 — `"..."` và `` `...` `` cũng phải được nhảy qua. Mặc định (`sql_mode`
     * không chứa `ANSI_QUOTES`, đã xác minh: `SELECT "Lee's"` trả về `Lee's`)
     * thì `"..."` LÀ string literal; bật `ANSI_QUOTES` nó thành định danh.
     * Nhưng dù là literal hay định danh, bộ quét vẫn phải nhảy qua nội dung —
     * nếu không, một dấu nháy đơn lạc bên trong sẽ mở nhầm state 'string' và
     * đảo pha toàn bộ phần sau.
     */
    public function test_nhay_kep_va_backtick_duoc_nhay_qua_khi_dem_clause(): void
    {
        self::assertFalse(
            \statementHasSingleDdlClause('ALTER TABLE t ADD COLUMN a int COMMENT "Lee\'s", ADD COLUMN b int'),
            'dấu nháy đơn lạc trong "..." không được mở state string'
        );
        self::assertFalse(
            \statementHasSingleDdlClause("ALTER TABLE t ADD COLUMN `M'gar` int, ADD COLUMN b int"),
            'dấu nháy đơn lạc trong `...` không được mở state string'
        );
    }

    /**
     * F2 (tiếp) — escape kiểu nhân đôi ký tự đóng phải tiếp tục hoạt động cho
     * cả ba loại nháy, và backslash KHÔNG phải ký tự escape bên trong backtick
     * (đã xác minh: `` SELECT `a\` `` là định danh hợp lệ trên MariaDB 10.4.28).
     */
    public function test_escape_nhan_doi_va_backslash_trong_backtick(): void
    {
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE t ADD COLUMN a int COMMENT "nói ""xin chào"", rồi đi"'),
            'escape nhân đôi "" phải giữ nguyên state string, dấu phẩy bên trong không phải ranh giới clause'
        );
        self::assertTrue(
            \statementHasSingleDdlClause("ALTER TABLE t ADD COLUMN `cot``la, ky` int"),
            'escape nhân đôi `` phải giữ nguyên state, dấu phẩy bên trong không phải ranh giới clause'
        );
        self::assertFalse(
            \statementHasSingleDdlClause("ALTER TABLE t ADD COLUMN `duong_dan\\` int, ADD COLUMN b int"),
            'backslash trong backtick là ký tự thường, không escape — backtick ngay sau nó vẫn đóng định danh'
        );
    }

    /**
     * F3 — `/*!40101 SET NAMES utf8mb4 * /` (bỏ khoảng trắng khi đọc) là MÃ
     * THI HÀNH THẬT với MariaDB, không phải comment. Đã xác minh trên máy này:
     * chạy `/*!40101 SELECT ... * /` cho ra kết quả thật.
     *
     * Bộ quét cũ coi nó là comment, rồi `splitSqlStatements()` loại chunk
     * toàn-comment → statement biến mất, không bao giờ tới `exec()`. Cả 7 dòng
     * `/*!` trong `database/daklak_travel.sql` đều bị nuốt như vậy.
     */
    public function test_conditional_comment_la_ma_thi_hanh_khong_bi_loai_bo(): void
    {
        $sql = "/*!40101 SET NAMES utf8mb4 */;\nCREATE TABLE foo (id int);";

        $statements = \splitSqlStatements($sql);

        self::assertCount(2, $statements, 'conditional comment /*! */ phải còn lại như một statement thi hành');
        self::assertStringContainsString('SET NAMES utf8mb4', $statements[0]);
        self::assertStringContainsString('CREATE TABLE foo', $statements[1]);
    }

    /**
     * F3 (tiếp) — canary trên bản dump nền THẬT: cả 7 dòng `/*!` phải sống
     * sót qua `splitSqlStatements()`, không bị loại như comment thuần.
     */
    public function test_canary_conditional_comment_trong_dump_nen_that(): void
    {
        $dump = (string)file_get_contents(__DIR__ . '/../../database/daklak_travel.sql');
        self::assertNotSame('', $dump, 'không đọc được database/daklak_travel.sql');

        $expected = preg_match_all('~/\*!~', $dump);
        self::assertGreaterThan(0, $expected, 'dump nền phải có ít nhất một conditional comment');

        $survived = 0;
        foreach (\splitSqlStatements($dump) as $statement) {
            $survived += preg_match_all('~/\*!~', $statement);
        }

        self::assertSame(
            $expected,
            $survived,
            'mọi conditional comment /*! trong dump nền phải sống sót qua bộ tách statement'
        );
    }

    /**
     * F3 (tiếp) — một file migration tách ra 0 statement thi hành được là
     * LỖI, không phải thành công. Trước đây `runMigrationFile()` chạy
     * `foreach` 0 vòng rồi vẫn `INSERT INTO schema_migrations` — một migration
     * mà mọi DDL đều bị bộ quét nuốt sẽ được đánh dấu "đã áp dụng" trong khi
     * không câu lệnh nào chạy.
     */
    public function test_file_migration_khong_co_statement_thi_hanh_phai_nem_loi(): void
    {
        $version = 'test_empty_' . bin2hex(random_bytes(4));
        $path = sys_get_temp_dir() . '/' . $version . '.sql';

        try {
            file_put_contents(
                $path,
                "-- Migration này chỉ còn comment, mọi DDL đã bị gỡ.\n"
                . "/* không còn câu lệnh nào ở đây */\n"
            );

            $threw = false;
            try {
                \runMigrationFile($this->db, $path, $version);
            } catch (\RuntimeException $e) {
                $threw = true;
            }
            self::assertTrue($threw, 'file migration không có statement thi hành được phải ném lỗi');

            $seen = $this->db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
            $seen->execute([$version]);
            self::assertSame(0, (int)$seen->fetchColumn(), 'version KHÔNG được ghi khi không statement nào chạy');
        } finally {
            @unlink($path);
            $this->db->exec('DELETE FROM schema_migrations WHERE version = ' . $this->db->quote($version));
        }
    }

    /**
     * F4 — nhánh mặc định `return true` bỏ sót các statement nguyên tử không
     * phải `ALTER TABLE`. `RENAME TABLE a TO b, c TO d` và `CREATE TABLE` không
     * có `IF NOT EXISTS` đều nguyên tử, nên lỗi 1050 ở đó KHÔNG được nuốt.
     * `CREATE INDEX`/`DROP INDEX` thì nhánh mặc định vẫn đúng (một đối tượng).
     */
    public function test_statement_nguyen_tu_khong_phai_alter_table_duoc_nhan_dien(): void
    {
        self::assertFalse(
            \statementHasSingleDdlClause('RENAME TABLE a TO b, c TO d'),
            'RENAME TABLE nhiều cặp là nguyên tử, lỗi "đã tồn tại" không được nuốt'
        );
        self::assertFalse(
            \statementHasSingleDdlClause('CREATE TABLE foo (id int PRIMARY KEY, name varchar(10))'),
            'CREATE TABLE không có IF NOT EXISTS: lỗi 1050 không được nuốt'
        );

        self::assertTrue(
            \statementHasSingleDdlClause('CREATE TABLE IF NOT EXISTS foo (id int PRIMARY KEY)'),
            'CREATE TABLE IF NOT EXISTS tự nó đã idempotent'
        );
        self::assertTrue(
            \statementHasSingleDdlClause('CREATE INDEX idx_foo ON foo (a, b)'),
            'CREATE INDEX chỉ tạo một đối tượng — nhánh mặc định vẫn đúng'
        );
        self::assertTrue(
            \statementHasSingleDdlClause('DROP INDEX idx_foo ON foo'),
            'DROP INDEX chỉ bỏ một đối tượng — nhánh mặc định vẫn đúng'
        );
    }

    /**
     * F5 — `ALGORITHM=`/`LOCK=`/`WAIT`/`NOWAIT` là TUỲ CHỌN của ALTER TABLE,
     * không phải clause DDL, nhưng vẫn phân tách bằng dấu phẩy ở độ sâu 0. Đếm
     * chúng như clause làm một statement 1-clause thật bị coi là nhiều clause,
     * phá tính idempotent (chạy lại lần 2 ném lỗi thay vì được nuốt).
     */
    public function test_algorithm_va_lock_khong_bi_dem_nhu_clause_ddl(): void
    {
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE t ADD COLUMN a int, ALGORITHM=INPLACE, LOCK=NONE'),
            'ALGORITHM=/LOCK= là tuỳ chọn, không phải clause DDL'
        );
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE t ADD COLUMN a int, ALGORITHM = COPY'),
            'ALGORITHM có khoảng trắng quanh dấu = vẫn là tuỳ chọn'
        );
        self::assertTrue(
            \statementHasSingleDdlClause('ALTER TABLE t WAIT 5 ADD COLUMN a int, LOCK=SHARED'),
            'WAIT/LOCK không phải clause DDL'
        );
        self::assertFalse(
            \statementHasSingleDdlClause('ALTER TABLE t ADD COLUMN a int, ADD COLUMN b int, ALGORITHM=INPLACE'),
            'bỏ qua tuỳ chọn không được che mất 2 clause DDL thật'
        );
    }

    /**
     * F6 — trong MariaDB, `--` chỉ mở comment khi theo sau là khoảng trắng,
     * tab hoặc xuống dòng. `DEFAULT 1--1` là `1 - (-1)` (đã xác minh:
     * `SELECT 1--1` trả về 2), không phải comment — bản cũ cắt cụt phần sau.
     */
    public function test_hai_gach_ngang_khong_co_khoang_trang_khong_phai_comment(): void
    {
        $sql = 'ALTER TABLE t ADD COLUMN a int DEFAULT 1--1';
        [$codeOnly] = \sqlCodeOnlyView($sql);
        self::assertSame($sql, $codeOnly, "'--' không có khoảng trắng theo sau không được coi là comment");

        $withComment = "ALTER TABLE t ADD COLUMN a int -- ghi chú thật\n";
        [$codeOnlyComment] = \sqlCodeOnlyView($withComment);
        self::assertStringNotContainsString(
            'ghi chú thật',
            $codeOnlyComment,
            "'-- ' có khoảng trắng theo sau vẫn phải là comment"
        );
    }

    /**
     * F9 (vòng sửa 5) — `sqlCodeOnlyView()` XOÁ HẲN ký tự comment thay vì thay
     * bằng khoảng trắng, nên hai token đứng hai bên một comment bị dính vào
     * nhau: `ALTER/* x * /TABLE ...` cho ra `ALTERTABLE ...`, regex nhận diện
     * `ALTER TABLE` không khớp, statement rơi vào nhánh mặc định.
     *
     * Comment là RANH GIỚI TỪ VỰNG trong SQL — bỏ nó đi phải để lại một khoảng
     * trắng, đúng như trình phân tích của MariaDB làm.
     */
    public function test_comment_giua_hai_token_khong_lam_dinh_token(): void
    {
        $statement = 'ALTER/* x */TABLE t ADD COLUMN a int, ADD COLUMN b int';

        [$codeOnly] = \sqlCodeOnlyView($statement);
        self::assertSame(
            'ALTER TABLE t ADD COLUMN a int, ADD COLUMN b int',
            trim((string)preg_replace('/\s+/', ' ', $codeOnly)),
            'comment phải được thay bằng khoảng trắng, không được xoá hẳn làm dính token'
        );

        self::assertFalse(
            \statementHasSingleDdlClause($statement),
            'ALTER TABLE 2 clause có comment chen giữa hai token vẫn phải bị nhận diện là nhiều clause'
        );
    }

    // ---------------------------------------------------------------------
    // Vòng sửa 5 — đóng LỚP "tầng trên quăng regex neo đầu chuỗi vào đầu ra
    // thô của một bộ quét đang tiến hoá".
    //
    // Bốn vòng trước đều hỏng theo cùng một kịch bản: sửa tầng dưới
    // (sqlCharStates / sqlCodeOnlyView) làm đổi HÌNH DẠNG chuỗi mà tầng trên
    // (statementHasSingleDdlClause) nhận, nhưng tầng trên vẫn giả định hình
    // dạng cũ. Hai thay đổi cấu trúc đóng lớp này:
    //
    //   1. sqlClassifierView() — một bước CHUẨN HOÁ bắt buộc giữa hai tầng.
    //      Tầng trên không bao giờ còn nhìn chuỗi thô nữa.
    //   2. Danh sách CHO PHÉP thay cho nhánh mặc định `return true`. Hình dạng
    //      lạ giờ rơi về "KHÔNG nuốt lỗi" — hướng an toàn. Một lỗ hổng cùng
    //      loại trong tương lai sẽ làm migration dừng ồn ào thay vì ghi
    //      schema_migrations trên một lược đồ chưa đầy đủ.
    // ---------------------------------------------------------------------

    /**
     * Bảng ca thử dùng chung cho các test bất biến bên dưới.
     *
     * @return array<string, array{0: string, 1: bool}> tên ca => [SQL, kỳ vọng single-clause]
     */
    private static function bangCaThuPhanLoai(): array
    {
        return [
            'ALTER 2 clause'            => ['ALTER TABLE t ADD COLUMN a int, ADD COLUMN b int', false],
            'ALTER 3 clause'            => ['ALTER TABLE t ADD COLUMN a int, ADD COLUMN b int, ADD KEY k (a)', false],
            'ALTER 1 clause'            => ['ALTER TABLE t ADD COLUMN a int', true],
            'ALTER 1 clause + ALGORITHM' => ['ALTER TABLE t ADD COLUMN a int, ALGORITHM=INPLACE', true],
            'ALTER 1 clause co literal' => ["ALTER TABLE t ADD COLUMN a varchar(20) DEFAULT 'x,y'", true],
            'RENAME TABLE nhieu cap'    => ['RENAME TABLE a TO b, c TO d', false],
            'CREATE TABLE khong INE'    => ['CREATE TABLE foo (id int, name varchar(10))', false],
            'CREATE TABLE IF NOT EXISTS' => ['CREATE TABLE IF NOT EXISTS foo (id int)', true],
            'CREATE INDEX'              => ['CREATE INDEX idx ON foo (a, b)', true],
            'DROP INDEX'                => ['DROP INDEX idx ON foo', true],
        ];
    }

    /**
     * F7 [Critical] — bản sửa F3 ở vòng 4 đánh `/*!` và `/*M!` là state 'code',
     * nên sqlCodeOnlyView() KHÔNG cắt chúng đi nữa: chuỗi tầng trên nhận bắt
     * đầu bằng `/*!100200 `. Cả ba regex trong statementHasSingleDdlClause()
     * đều neo `^\s*` nên không cái nào khớp, hàm rơi thẳng xuống nhánh mặc
     * định `return true` — tức NUỐT lỗi "đã tồn tại" trên một `ALTER TABLE`
     * nhiều clause mà MariaDB đã huỷ nguyên statement.
     *
     * `/*!100200 ... * /` là kỹ thuật MariaDB phổ thông (chính comment trong
     * runMigrationFile() gọi tên nó), nên đây không phải rủi ro lý thuyết.
     */
    public function test_gate_phien_ban_khong_lam_hong_phan_loai(): void
    {
        $alterHaiClause = 'ALTER TABLE t ADD COLUMN a int, ADD COLUMN b int';

        self::assertFalse(
            \statementHasSingleDdlClause("/*!100200 {$alterHaiClause} */"),
            'ALTER TABLE 2 clause bọc trong gate /*! vẫn phải bị nhận diện là nhiều clause'
        );
        self::assertFalse(
            \statementHasSingleDdlClause("/*M!100200 {$alterHaiClause} */"),
            'ALTER TABLE 2 clause bọc trong gate /*M! vẫn phải bị nhận diện là nhiều clause'
        );
        self::assertFalse(
            \statementHasSingleDdlClause("/*!100200 /*M!100300 {$alterHaiClause} */ */"),
            'gate lồng gate cũng phải được bóc hết'
        );
        self::assertFalse(
            \statementHasSingleDdlClause("/*!40000 {$alterHaiClause}"),
            'gate KHÔNG đóng (mysqldump hay cắt ngang) cũng không được che mất số clause'
        );

        // Gate không được biến một statement 1-clause thật thành nhiều clause.
        self::assertTrue(
            \statementHasSingleDdlClause('/*!40000 ALTER TABLE t DISABLE KEYS */'),
            'ALTER TABLE 1 clause bọc gate vẫn là 1 clause'
        );
    }

    /**
     * Ca đối chứng reviewer dùng làm chuẩn: sau bước bóc gate, bản chuẩn hoá
     * của một statement BỌC GATE phải giống Y HỆT bản chuẩn hoá của cùng
     * statement KHÔNG BỌC GATE. Đây là mệnh đề mạnh hơn "kết quả phân loại
     * bằng nhau": nó khoá lại chính cái ĐẦU VÀO mà tầng trên nhận được.
     */
    public function test_ban_chuan_hoa_bo_gate_giong_het_ban_khong_gate(): void
    {
        $goc = 'ALTER TABLE t ADD a, ADD b';

        [$chuanHoaGoc] = \sqlClassifierView($goc);
        self::assertSame($goc, $chuanHoaGoc, 'statement đã chuẩn tắc thì chuẩn hoá không được đổi gì');

        foreach ([
            "/*!100200 {$goc} */",
            "/*M!100200 {$goc} */",
            "/*!100200 /*!100300 {$goc} */ */",
            "  /*!100200\n  {$goc}\n  */  ",
        ] as $bienThe) {
            [$chuanHoa] = \sqlClassifierView($bienThe);
            self::assertSame(
                $chuanHoaGoc,
                $chuanHoa,
                'bọc gate không được làm đổi bản chuẩn hoá: ' . $bienThe
            );
        }
    }

    /**
     * TEST CANH GÁC — diễn đạt bất biến ở mức LỚP chứ không ở mức ca: với MỌI
     * mẫu SQL trong bảng ca thử, phiên bản BỌC GATE và phiên bản KHÔNG BỌC
     * GATE phải cho cùng một kết quả statementHasSingleDdlClause().
     *
     * Đây là thứ sẽ bắt được "vòng thứ sáu" nếu nó tồn tại: bất kỳ thay đổi
     * nào ở tầng dưới làm hình dạng đầu vào của tầng trên lệch đi giữa hai
     * phiên bản sẽ làm test này đỏ, kể cả khi lỗ hổng nằm ở một dạng SQL chưa
     * ai nghĩ tới.
     */
    public function test_canh_gac_gate_va_khong_gate_luon_cho_cung_ket_qua(): void
    {
        foreach (self::bangCaThuPhanLoai() as $ten => [$sql, $kyVong]) {
            $khongGate = \statementHasSingleDdlClause($sql);
            self::assertSame($kyVong, $khongGate, "ca '{$ten}' (không gate) phân loại sai");

            foreach (['/*!100200 %s */', '/*M!100200 %s */', '/*!100200 /*!100300 %s */ */'] as $khuon) {
                self::assertSame(
                    $khongGate,
                    \statementHasSingleDdlClause(sprintf($khuon, $sql)),
                    "ca '{$ten}': bọc gate " . $khuon . ' cho kết quả khác bản không gate'
                );
            }
        }
    }

    /**
     * F8 [Minor] — `/^\s*CREATE\s+(?:...)TABLE\s+(?!IF\s+NOT\s+EXISTS\b)/i`:
     * `\s+` tham lam rồi backtrack nhả lại một ký tự trắng, lookahead đứng ở
     * khoảng trắng THỨ HAI nên không thấy `IF` — `CREATE TABLE  IF NOT EXISTS`
     * (hai dấu cách) hay xuống dòng bị phân loại thành "không có IF NOT
     * EXISTS", tức nguyên tử, tức mất tính idempotent.
     *
     * Bước chuẩn hoá gộp khoảng trắng + danh sách cho phép dùng mệnh đề
     * KHẲNG ĐỊNH (`phải khớp IF NOT EXISTS`) thay cho lookahead PHỦ ĐỊNH nên
     * cái bẫy backtracking này biến mất theo cấu trúc.
     */
    public function test_khoang_trang_du_thua_khong_lam_hong_nhan_dien_if_not_exists(): void
    {
        foreach ([
            'CREATE TABLE IF NOT EXISTS foo (id int)',
            'CREATE TABLE  IF NOT EXISTS foo (id int)',
            "CREATE TABLE\n  IF NOT EXISTS foo (id int)",
            "CREATE  TABLE\tIF   NOT\nEXISTS foo (id int)",
            'CREATE TEMPORARY TABLE  IF NOT EXISTS foo (id int)',
        ] as $sql) {
            self::assertTrue(
                \statementHasSingleDdlClause($sql),
                'CREATE TABLE IF NOT EXISTS tự nó idempotent, khoảng trắng thừa không được đổi kết luận: ' . $sql
            );
        }

        foreach ([
            'CREATE TABLE foo (id int)',
            "CREATE  TABLE\n foo (id int)",
        ] as $sql) {
            self::assertFalse(
                \statementHasSingleDdlClause($sql),
                'CREATE TABLE không có IF NOT EXISTS vẫn phải là nguyên tử: ' . $sql
            );
        }
    }

    /**
     * Bất biến cấu trúc thứ hai: mặc định của bộ phân loại là AN TOÀN
     * (không nuốt lỗi). Statement có hình dạng không nằm trong danh sách cho
     * phép — kể cả rỗng, kể cả một dạng DDL chưa ai lường trước — phải trả
     * false.
     *
     * Chính nhánh mặc định `return true` cũ là thứ biến mỗi lỗ hổng nhận
     * diện thành một lỗi ÂM THẦM NUỐT. Đảo mặc định biến cùng lỗ hổng đó
     * thành một lỗi ồn ào, khắc phục được.
     */
    public function test_hinh_dang_la_thi_mac_dinh_khong_duoc_nuot_loi(): void
    {
        foreach ([
            '',
            '   ',
            'TRUNCATE TABLE foo',
            'CREATE TRIGGER trg BEFORE INSERT ON foo FOR EACH ROW SET NEW.a = 1',
            'ALTER DATABASE daklak_travel CHARACTER SET utf8mb4',
            'RENAME TABLE a TO b',
            'DROP TABLE foo',
            'MOT DANG SQL CHUA AI LUONG TRUOC',
        ] as $sql) {
            self::assertFalse(
                \statementHasSingleDdlClause($sql),
                'hình dạng không nằm trong danh sách cho phép phải rơi về "không nuốt lỗi": ' . $sql
            );
        }
    }

    /**
     * Canary mở rộng: mọi file migration THẬT trong repo phải tách ra ít nhất
     * một statement thi hành được, và không statement nào rỗng. Đọc file thật
     * nên bắt được mọi lần bộ quét tái phát bệnh nuốt statement.
     */
    public function test_canary_moi_file_migration_that_tach_ra_statement_hop_le(): void
    {
        $files = glob(__DIR__ . '/../../database/migrations/*.sql') ?: [];
        self::assertCount(5, $files, 'mong đợi đúng 5 file migration đang được track');

        foreach ($files as $file) {
            $statements = \splitSqlStatements((string)file_get_contents($file));
            self::assertNotEmpty($statements, 'file migration ' . basename($file) . ' phải tách ra ít nhất 1 statement');
            foreach ($statements as $statement) {
                self::assertNotSame('', trim($statement), 'không statement nào được rỗng: ' . basename($file));
            }
        }
    }
}
