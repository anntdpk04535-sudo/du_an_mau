<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$version = '20260806_region_split';
$path = __DIR__ . '/../database/migrations/20260806_region_split.sql';
$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version varchar(120) NOT NULL PRIMARY KEY, applied_at timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$check = $db->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
$check->execute([$version]);
if ($check->fetchColumn()) {
    echo "Already applied: {$version}\n";
    exit(0);
}

$sql = file_get_contents($path);
if ($sql === false) {
    throw new RuntimeException("Migration file not found: {$path}");
}
$statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
foreach ($statements as $statement) {
    $statement = trim(preg_replace('/^--.*$/m', '', $statement) ?? '');
    if ($statement !== '') {
        $db->exec($statement);
    }
}
$db->prepare('INSERT INTO schema_migrations(version) VALUES (?)')->execute([$version]);
echo "Applied: {$version}\n";