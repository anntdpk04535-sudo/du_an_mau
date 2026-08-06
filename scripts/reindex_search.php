<?php
require_once __DIR__ . '/../includes/rag.php';
try { echo json_encode(ragUpsertDocuments(), JSON_UNESCAPED_UNICODE) . PHP_EOL; } catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
