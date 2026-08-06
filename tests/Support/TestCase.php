<?php
declare(strict_types=1);

namespace Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = getDB();
    }

    /** Xóa sạch bảng theo đúng thứ tự truyền vào (con trước, cha sau). */
    protected function resetTables(array $tables): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->db->exec("TRUNCATE TABLE `{$table}`");
        }
        $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
